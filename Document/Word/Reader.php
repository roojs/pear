<?php
/**
 * Reads a .docx (OOXML) package into {@see Document_Word} using {@see ZipArchive} and DOM.
 *
 * v1 scope: body paragraphs (runs, bold/italic/underline/size/color; w:rStyle resolved via
 * word/styles.xml and w:basedOn), line breaks, page breaks, external hyperlinks, embedded images
 * (DrawingML blip r:embed), simple tables, bullet and numbered lists (w:numPr + word/numbering.xml;
 * plain item text). Skips lvlOverride edge cases, headers/footers,
 * nested tables in cells, linked images (r:link), EMF/WMF/WebP, and most w:sdt blocks (unwraps trivial sdtContent).
 *
 * Manual smoke from repository `pear/` directory:
 *
 * ```
 * php -r '
 * $base = sys_get_temp_dir() . "/dw-docx-smoke-" . uniqid();
 * mkdir($base, 0777, true);
 * $docx = $base . "/smoke.docx";
 * $z = new ZipArchive();
 * $z->open($docx, ZipArchive::CREATE | ZipArchive::OVERWRITE);
 * $z->addFromString("[Content_Types].xml", "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<Types xmlns=\"http://schemas.openxmlformats.org/package/2006/content-types\"><Default Extension=\"rels\" ContentType=\"application/vnd.openxmlformats-package.relationships+xml\"/><Default Extension=\"xml\" ContentType=\"application/xml\"/><Override PartName=\"/word/document.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml\"/></Types>");
 * $z->addFromString("_rels/.rels", "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<Relationships xmlns=\"http://schemas.openxmlformats.org/package/2006/relationships\"><Relationship Id=\"rId1\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument\" Target=\"word/document.xml\"/></Relationships>");
 * $z->addFromString("word/_rels/document.xml.rels", "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<Relationships xmlns=\"http://schemas.openxmlformats.org/package/2006/relationships\"><Relationship Id=\"rId1\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink\" Target=\"https://example.com/\" TargetMode=\"External\"/></Relationships>");
 * $z->addFromString("word/document.xml", "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<w:document xmlns:w=\"http://schemas.openxmlformats.org/wordprocessingml/2006/main\" xmlns:r=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships\"><w:body><w:p><w:r><w:t>Hello DOCX</w:t></w:r></w:p><w:p><w:hyperlink r:id=\"rId1\"><w:r><w:t>Link</w:t></w:r></w:hyperlink></w:p></w:body></w:document>");
 * $z->addFromString("docProps/core.xml", "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n<cp:coreProperties xmlns:cp=\"http://schemas.openxmlformats.org/package/2006/metadata/core-properties\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\"><dc:title>SmokeTitle</dc:title><dc:creator>SmokeAuthor</dc:creator></cp:coreProperties>");
 * $z->close();
 * require_once "Document/Word.php";
 * require_once "Document/Word/IOFactory.php";
 * $w = new Document_Word($docx);
 * if (strpos($w->getProperties()->getTitle(), "SmokeTitle") === false) { fwrite(STDERR, "title fail\n"); exit(1); }
 * $html = Document_Word_IOFactory::createWriter($w, "HTML")->getContent();
 * if (strpos($html, "Hello DOCX") === false || strpos($html, "example.com") === false) { fwrite(STDERR, "html fail\n"); exit(1); }
 * echo "ok\n";
 * '
 * ```
 *
 * Embedded images: add `word/media/image1.png`, an `image` relationship (e.g. rId2 → `media/image1.png`), and a `w:drawing`/`a:blip` with `r:embed="rId2"`. After HTML export, assert `strpos($html, 'src="data:image/png;base64,') !== false` (local files are inlined as data URLs by the HTML writer).
 *
 * Lists: include `word/numbering.xml` (`abstractNum`/`num`), paragraphs with `w:pPr/w:numPr` (`w:ilvl`, `w:numId`). HTML export must contain `<ul` for bullet `numFmt` and `<ol` for decimal-style formats.
 *
 * @category Document_Word
 */
class Document_Word_Reader
{
    const NS_W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    const NS_R = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    const NS_REL_PKG = 'http://schemas.openxmlformats.org/package/2006/relationships';

    const NS_DC = 'http://purl.org/dc/elements/1.1/';

    const NS_A = 'http://schemas.openxmlformats.org/drawingml/2006/main';

    const NS_WP = 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing';

    /** @var ZipArchive|null */
    private $zip;

    /** @var array<string, array{type:string,target:string,external:bool}> */
    private $rels = array();

    /** @var array<int, int> w:numId to w:abstractNumId */
    private $numberingNumAbstract = array();

    /** @var array<int, array<int, string>> abstractNumId -> ilvl -> w:numFmt @w:val (lowercase) */
    private $numberingAbstractLevels = array();

    /** @var array<string, DOMElement> w:styleId -> w:style */
    private $stylesById = array();

    /** @var array<string, array> merged font partials from each style's w:rPr chain */
    private $charStyleResolvedCache = array();

    /** @var array<string, bool> */
    private $styleResolving = array();

    /** @var array<int, string> */
    private static $extractedImageTemps = array();

    private static $shutdownCleanupRegistered = false;

    /**
     * @param string $path Absolute or relative path to a .docx file
     * @param Document_Word $doc Document to populate; existing sections are cleared first
     * @return Document_Word same instance as $doc
     * @throws Exception
     */
    public function load($path, Document_Word $doc)
    {
        if (!is_string($path) || $path === '') {
            throw new Exception('Document_Word_Reader::load() requires a non-empty path.');
        }
        if (!is_readable($path)) {
            throw new Exception('Document_Word_Reader::load() cannot read file: ' . $path);
        }

        self::$shutdownCleanupRegistered = false;

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new Exception('Document_Word_Reader::load() could not open OOXML package: ' . $path);
        }

        $documentXml = $zip->getFromName('word/document.xml');
        if ($documentXml === false) {
            $zip->close();
            throw new Exception('Document_Word_Reader::load() missing word/document.xml in ' . $path);
        }

        $relsXml = $zip->getFromName('word/_rels/document.xml.rels');
        $this->rels = $relsXml !== false ? $this->parseRelsXml($relsXml) : array();
        $this->loadNumberingMaps($zip);
        $this->loadStylesMaps($zip);

        $dom = new DOMDocument();
        if (@$dom->loadXML($documentXml, LIBXML_NONET) === false) {
            $zip->close();
            throw new Exception('Document_Word_Reader::load() invalid XML in word/document.xml');
        }

        require_once __DIR__ . '/../Word.php';

        $xp = new DOMXPath($dom);
        $xp->registerNamespace('w', self::NS_W);
        $xp->registerNamespace('r', self::NS_R);

        $body = $xp->query('//w:body')->item(0);
        if (!$body instanceof DOMElement) {
            $zip->close();
            throw new Exception('Document_Word_Reader::load() missing w:body');
        }

        $doc->clearSections();
        $this->applyCoreProperties($zip, $doc);

        $this->zip = $zip;
        $section = $doc->createSection();
        $this->walkBlockContainer($section, $body, true, $xp);
        $this->zip = null;
        $zip->close();

        return $doc;
    }

    /**
     * @param string $xml
     * @return array<string, array{type:string,target:string,external:bool}>
     */
    private function parseRelsXml($xml)
    {
        $out = array();
        $dom = new DOMDocument();
        if (@$dom->loadXML($xml, LIBXML_NONET) === false) {
            return $out;
        }
        $xp = new DOMXPath($dom);
        $xp->registerNamespace('pkg', self::NS_REL_PKG);
        $nodes = $xp->query('//pkg:Relationship');
        if ($nodes === false) {
            return $out;
        }
        $prefix = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/';
        foreach ($nodes as $rel) {
            if (!$rel instanceof DOMElement) {
                continue;
            }
            $id = $rel->getAttribute('Id');
            if ($id === '') {
                continue;
            }
            $type = $rel->getAttribute('Type');
            $short = (strpos($type, $prefix) === 0) ? substr($type, strlen($prefix)) : $type;
            $out[$id] = array(
                'type' => $short,
                'target' => $rel->getAttribute('Target'),
                'external' => strcasecmp($rel->getAttribute('TargetMode'), 'External') === 0,
            );
        }

        return $out;
    }

    /**
     * Parse word/numbering.xml into maps for list paragraph handling (v1: no w:lvlOverride).
     *
     * @param ZipArchive $zip
     */
    private function loadNumberingMaps($zip)
    {
        $this->numberingNumAbstract = array();
        $this->numberingAbstractLevels = array();
        $xml = $zip->getFromName('word/numbering.xml');
        if ($xml === false) {
            return;
        }
        $dom = new DOMDocument();
        if (@$dom->loadXML($xml, LIBXML_NONET) === false) {
            return;
        }
        foreach ($dom->getElementsByTagNameNS(self::NS_W, 'abstractNum') as $an) {
            if (!$an instanceof DOMElement) {
                continue;
            }
            $aid = $an->getAttributeNS(self::NS_W, 'abstractNumId');
            if ($aid === '' || !ctype_digit($aid)) {
                continue;
            }
            $abstractId = (int) $aid;
            $this->numberingAbstractLevels[$abstractId] = array();
            foreach ($an->childNodes as $lvlCandidate) {
                if (!$lvlCandidate instanceof DOMElement || $lvlCandidate->namespaceURI !== self::NS_W || $lvlCandidate->localName !== 'lvl') {
                    continue;
                }
                $lvl = $lvlCandidate;
                $ilvl = $lvl->getAttributeNS(self::NS_W, 'ilvl');
                if ($ilvl === '' || !ctype_digit($ilvl)) {
                    continue;
                }
                $nfEl = $this->firstChildW($lvl, 'numFmt');
                if (!$nfEl instanceof DOMElement) {
                    continue;
                }
                $fmt = strtolower((string) $nfEl->getAttributeNS(self::NS_W, 'val'));
                if ($fmt !== '') {
                    $this->numberingAbstractLevels[$abstractId][(int) $ilvl] = $fmt;
                }
            }
        }
        foreach ($dom->getElementsByTagNameNS(self::NS_W, 'num') as $num) {
            if (!$num instanceof DOMElement) {
                continue;
            }
            $nid = $num->getAttributeNS(self::NS_W, 'numId');
            if ($nid === '' || !ctype_digit($nid)) {
                continue;
            }
            $abi = $this->firstChildW($num, 'abstractNumId');
            if (!$abi instanceof DOMElement) {
                continue;
            }
            $aval = $abi->getAttributeNS(self::NS_W, 'val');
            if ($aval !== '' && ctype_digit($aval)) {
                $this->numberingNumAbstract[(int) $nid] = (int) $aval;
            }
        }
    }

    /**
     * Index word/styles.xml by w:styleId for w:rStyle resolution (w:basedOn + w:rPr merge).
     *
     * @param ZipArchive $zip
     */
    private function loadStylesMaps($zip)
    {
        $this->stylesById = array();
        $this->charStyleResolvedCache = array();
        $this->styleResolving = array();
        $xml = $zip->getFromName('word/styles.xml');
        if ($xml === false) {
            return;
        }
        $dom = new DOMDocument();
        if (@$dom->loadXML($xml, LIBXML_NONET) === false) {
            return;
        }
        foreach ($dom->getElementsByTagNameNS(self::NS_W, 'style') as $st) {
            if (!$st instanceof DOMElement) {
                continue;
            }
            $sid = $st->getAttributeNS(self::NS_W, 'styleId');
            if ($sid === '') {
                continue;
            }
            $this->stylesById[$sid] = $st;
        }
    }

    /**
     * @param string $styleId w:rStyle/@w:val
     * @return array partial font style from w:basedOn chain + this style's w:rPr
     */
    private function resolvedCharStyleFont($styleId)
    {
        if ($styleId === '') {
            return array();
        }
        if (isset($this->charStyleResolvedCache[$styleId])) {
            return $this->charStyleResolvedCache[$styleId];
        }
        if (isset($this->styleResolving[$styleId])) {
            return array();
        }
        $this->styleResolving[$styleId] = true;
        $merged = array();
        $node = isset($this->stylesById[$styleId]) ? $this->stylesById[$styleId] : null;
        if ($node instanceof DOMElement) {
            $basePartial = array();
            $basedOn = $this->firstChildW($node, 'basedOn');
            if ($basedOn instanceof DOMElement) {
                $pid = $basedOn->getAttributeNS(self::NS_W, 'val');
                if ($pid !== '') {
                    $basePartial = $this->resolvedCharStyleFont($pid);
                }
            }
            $rPr = $this->firstChildW($node, 'rPr');
            $thisPartial = $rPr instanceof DOMElement ? $this->fontStylePartialFromRPr($rPr) : array();
            $merged = $this->mergeFontStylePartials($basePartial, $thisPartial);
        }
        unset($this->styleResolving[$styleId]);
        $this->charStyleResolvedCache[$styleId] = $merged;

        return $merged;
    }

    /**
     * @param array $base
     * @param array $overlay explicit run/style keys in $overlay replace $base
     * @return array
     */
    private function mergeFontStylePartials($base, $overlay)
    {
        if ($overlay === array()) {
            return $base;
        }
        if ($base === array()) {
            return $overlay;
        }
        $out = $base;
        foreach ($overlay as $k => $v) {
            $out[$k] = $v;
        }

        return $out;
    }

    /**
     * @param DOMElement $el w:b, w:i, …
     * @return bool
     */
    private function wOnOffIsTrue(DOMElement $el)
    {
        if (!$el->hasAttributeNS(self::NS_W, 'val')) {
            return true;
        }
        $v = strtolower($el->getAttributeNS(self::NS_W, 'val'));
        if ($v === '0' || $v === 'false' || $v === 'off' || $v === 'none') {
            return false;
        }

        return true;
    }

    /**
     * Font properties explicitly set on this w:rPr (skips w:rStyle). w:bCs/w:iCs ignored for Latin export.
     *
     * @param DOMElement $rPr
     * @return array
     */
    private function fontStylePartialFromRPr(DOMElement $rPr)
    {
        require_once __DIR__ . '/Style/Font.php';
        $style = array();
        $b = $this->firstChildW($rPr, 'b');
        if ($b instanceof DOMElement) {
            $style['bold'] = $this->wOnOffIsTrue($b);
        }
        $i = $this->firstChildW($rPr, 'i');
        if ($i instanceof DOMElement) {
            $style['italic'] = $this->wOnOffIsTrue($i);
        }
        $u = $this->firstChildW($rPr, 'u');
        if ($u instanceof DOMElement) {
            $uv = $u->getAttributeNS(self::NS_W, 'val');
            $mapped = $this->mapUnderline($uv);
            if ($mapped !== Document_Word_Style_Font::UNDERLINE_NONE) {
                $style['underline'] = $mapped;
            }
        }
        $sz = $this->firstChildW($rPr, 'sz');
        if ($sz instanceof DOMElement) {
            $half = $sz->getAttributeNS(self::NS_W, 'val');
            if ($half !== '' && ctype_digit($half)) {
                $style['size'] = max(1, (int) round(((int) $half) / 2));
            }
        }
        $color = $this->firstChildW($rPr, 'color');
        if ($color instanceof DOMElement) {
            $cv = $color->getAttributeNS(self::NS_W, 'val');
            if ($cv !== '' && strcasecmp($cv, 'auto') !== 0) {
                if (strlen($cv) === 8) {
                    $cv = substr($cv, 2);
                }
                $style['color'] = $cv;
            }
        }
        $fonts = $this->firstChildW($rPr, 'rFonts');
        if ($fonts instanceof DOMElement) {
            $name = $fonts->getAttributeNS(self::NS_W, 'ascii');
            if ($name === '') {
                $name = $fonts->getAttributeNS(self::NS_W, 'hAnsi');
            }
            if ($name !== '') {
                $style['name'] = $name;
            }
        }

        return $style;
    }

    /**
     * @param string $numFmtLower
     * @return bool true if ordered (decimal, letters, roman, …)
     */
    private function numFmtIsOrdered($numFmtLower)
    {
        static $unordered = array('bullet' => true, 'none' => true, 'image' => true);
        if ($numFmtLower === '') {
            return false;
        }
        if (isset($unordered[$numFmtLower])) {
            return false;
        }

        return true;
    }

    /**
     * @param int $numId
     * @param int $ilvl
     * @return bool
     */
    private function listNumIdIsOrdered($numId, $ilvl)
    {
        if (!isset($this->numberingNumAbstract[$numId])) {
            return false;
        }
        $abstractId = $this->numberingNumAbstract[$numId];
        if (!isset($this->numberingAbstractLevels[$abstractId])) {
            return false;
        }
        $levels = $this->numberingAbstractLevels[$abstractId];
        if (isset($levels[$ilvl])) {
            return $this->numFmtIsOrdered($levels[$ilvl]);
        }
        if (isset($levels[0])) {
            return $this->numFmtIsOrdered($levels[0]);
        }

        return false;
    }

    /**
     * @return array{numId:int,ilvl:int}|null
     */
    private function paragraphListNumPr(DOMElement $p)
    {
        $pPr = $this->firstChildW($p, 'pPr');
        if (!$pPr instanceof DOMElement) {
            return null;
        }
        $numPr = $this->firstChildW($pPr, 'numPr');
        if (!$numPr instanceof DOMElement) {
            return null;
        }
        $ilvlEl = $this->firstChildW($numPr, 'ilvl');
        $numIdEl = $this->firstChildW($numPr, 'numId');
        if (!$ilvlEl instanceof DOMElement || !$numIdEl instanceof DOMElement) {
            return null;
        }
        $iv = $ilvlEl->getAttributeNS(self::NS_W, 'val');
        $nid = $numIdEl->getAttributeNS(self::NS_W, 'val');
        if ($iv === '' || !ctype_digit($iv) || $nid === '' || !ctype_digit($nid)) {
            return null;
        }

        return array('numId' => (int) $nid, 'ilvl' => (int) $iv);
    }

    /**
     * @return array
     */
    private function firstParagraphRunFontStyle(DOMElement $p)
    {
        foreach ($p->getElementsByTagNameNS(self::NS_W, 'r') as $r) {
            if ($r instanceof DOMElement) {
                return $this->fontStyleFromRun($r);
            }
        }

        return array();
    }

    /**
     * @param ZipArchive $zip
     * @param Document_Word $doc
     */
    private function applyCoreProperties($zip, $doc)
    {
        $xml = $zip->getFromName('docProps/core.xml');
        if ($xml === false) {
            return;
        }
        $dom = new DOMDocument();
        if (@$dom->loadXML($xml, LIBXML_NONET) === false) {
            return;
        }
        $title = '';
        $creator = '';
        foreach ($dom->getElementsByTagNameNS(self::NS_DC, 'title') as $n) {
            $title = trim($n->textContent);
            break;
        }
        foreach ($dom->getElementsByTagNameNS(self::NS_DC, 'creator') as $n) {
            $creator = trim($n->textContent);
            break;
        }

        if ($title !== '') {
            $doc->getProperties()->setTitle($title);
        }
        if ($creator !== '') {
            $doc->getProperties()->setCreator($creator);
        }
    }

    public static function unlinkQueuedImageTemps()
    {
        foreach (self::$extractedImageTemps as $p) {
            @unlink($p);
        }
        self::$extractedImageTemps = array();
    }

    /**
     * @param string $rid
     * @return string|null Package path for ZipArchive::getFromName, or null
     */
    private function imagePackagePartPath($rid)
    {
        if ($rid === '' || !isset($this->rels[$rid])) {
            return null;
        }
        $rel = $this->rels[$rid];
        if ($rel['external'] || $rel['type'] !== 'image') {
            return null;
        }
        $t = str_replace('\\', '/', $rel['target']);
        $t = ltrim($t, '/');
        if ($t === '' || strpos($t, '../') !== false) {
            return null;
        }
        if (strpos($t, 'word/') !== 0) {
            $t = 'word/' . $t;
        }

        return $t;
    }

    /**
     * @param string $rid
     * @return string|null Absolute temp path
     */
    private function extractEmbeddedImageToTemp($rid)
    {
        if (!($this->zip instanceof ZipArchive)) {
            return null;
        }
        $part = $this->imagePackagePartPath($rid);
        if ($part === null) {
            return null;
        }
        $bytes = $this->zip->getFromName($part);
        if ($bytes === false || $bytes === '') {
            return null;
        }
        $ext = strtolower(pathinfo($part, PATHINFO_EXTENSION));
        if ($ext === '' || !in_array($ext, array('jpg', 'jpeg', 'gif', 'png', 'bmp', 'tif', 'tiff'), true)) {
            return null;
        }
        $tmp = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'dw-docx-img-' . uniqid('', true) . '.' . $ext;
        if (@file_put_contents($tmp, $bytes) === false) {
            return null;
        }

        self::$extractedImageTemps[] = $tmp;
        if (!self::$shutdownCleanupRegistered) {
            self::$shutdownCleanupRegistered = true;
            register_shutdown_function(array('Document_Word_Reader', 'unlinkQueuedImageTemps'));
        }

        return $tmp;
    }

    /**
     * @return array{width?:int,height?:int}
     */
    private function docxImageStyleFromDrawing(DOMElement $drawing)
    {
        foreach ($drawing->getElementsByTagNameNS(self::NS_WP, 'extent') as $ext) {
            if (!$ext instanceof DOMElement) {
                continue;
            }
            $cx = $ext->getAttribute('cx');
            $cy = $ext->getAttribute('cy');
            if ($cx === '' || $cy === '' || !ctype_digit($cx) || !ctype_digit($cy)) {
                return array();
            }

            return array(
                'width' => (int) max(1, round(((int) $cx) / 914400 * 96)),
                'height' => (int) max(1, round(((int) $cy) / 914400 * 96)),
            );
        }

        return array();
    }

    /**
     * @param Document_Word_Section_TextRun $tr
     */
    private function emitDrawing($tr, DOMElement $drawing)
    {
        $blips = $drawing->getElementsByTagNameNS(self::NS_A, 'blip');
        foreach ($blips as $blip) {
            if (!$blip instanceof DOMElement) {
                continue;
            }
            $embed = $blip->getAttributeNS(self::NS_R, 'embed');
            if ($embed === '') {
                continue;
            }
            $tmp = $this->extractEmbeddedImageToTemp($embed);
            if ($tmp === null) {
                continue;
            }
            $style = $this->docxImageStyleFromDrawing($drawing);
            $tr->addImage($tmp, $style === array() ? null : $style);
            break;
        }
    }

    /**
     * @param Document_Word_Section|Document_Word_Section_Table_Cell $target
     */
    private function walkBlockContainer($target, DOMElement $container, $isSection, DOMXPath $xp)
    {
        foreach ($container->childNodes as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }
            if ($child->namespaceURI !== self::NS_W) {
                continue;
            }
            $ln = $child->localName;
            if ($ln === 'p') {
                $this->readParagraph($target, $child, $isSection, $xp);
                continue;
            }
            if ($ln === 'tbl') {
                if (!$isSection || !($target instanceof Document_Word_Section)) {
                    continue;
                }
                $this->readTable($target, $child, $xp);
                continue;
            }
            if ($ln === 'sdt') {
                $content = $this->firstChildW($child, 'sdtContent');
                if ($content instanceof DOMElement) {
                    $this->walkBlockContainer($target, $content, $isSection, $xp);
                }
                continue;
            }
        }
    }

    /**
     * @param Document_Word_Section|Document_Word_Section_Table_Cell $target
     */
    private function readParagraph($target, DOMElement $p, $isSection, DOMXPath $xp)
    {
        if ($isSection && $target instanceof Document_Word_Section) {
            $pPr = $this->firstChildW($p, 'pPr');
            if ($pPr instanceof DOMElement && $this->firstChildW($pPr, 'pageBreakBefore') !== null) {
                $target->addPageBreak();
            }
        }

        if ($this->paragraphIsPageBreakOnly($p)) {
            if ($isSection && $target instanceof Document_Word_Section) {
                $target->addPageBreak();
            }
            return;
        }

        $listNum = $this->paragraphListNumPr($p);
        if ($listNum !== null) {
            $plainList = $this->paragraphPlainText($p);
            if ($plainList === '' && !$this->paragraphHasRenderableContent($p)) {
                return;
            }
            $depth = min(8, max(0, $listNum['ilvl']));
            $numId = $listNum['numId'];
            $ordered = $this->listNumIdIsOrdered($numId, $depth);
            $font = $this->firstParagraphRunFontStyle($p);
            $listItem = $target->addListItem($plainList, $depth, $font === array() ? null : $font, array(
                'listType' => $numId,
                'isOrdered' => $ordered,
            ));
            require_once __DIR__ . '/Section/TextRun.php';
            $tr = new Document_Word_Section_TextRun();
            $this->emitParagraphInlines($tr, $p, false);
            $listItem->setElements($tr->getElements());
            return;
        }

        $plain = $this->paragraphPlainText($p);
        if ($plain === '' && !$this->paragraphHasRenderableContent($p)) {
            return;
        }

        $headingDepth = $this->paragraphHeadingDepth($p);
        $hasHyperlink = $this->paragraphHasDirectHyperlink($p);

        if ($isSection && $target instanceof Document_Word_Section && $headingDepth > 0 && !$hasHyperlink && $plain !== '') {
            $target->addTitle($plain, $headingDepth);
            return;
        }

        if (!$isSection && $headingDepth > 0 && !$hasHyperlink && $plain !== '') {
            $font = array('bold' => true);
            if ($headingDepth >= 2) {
                $font['size'] = max(12, 16 - $headingDepth);
            }
            $target->addText($plain, $font);
            return;
        }

        $paraStyle = $this->paragraphStyleArray($p);
        if ($this->paragraphIsSingleUniformText($p) && !$hasHyperlink) {
            $r = $this->firstChildW($p, 'r');
            if ($r instanceof DOMElement) {
                $font = $this->fontStyleFromRun($r);
                $target->addText($plain, $font === array() ? null : $font, $paraStyle === array() ? null : $paraStyle);
                return;
            }
        }

        $tr = $target->createTextRun($paraStyle === array() ? null : $paraStyle);
        $this->emitParagraphInlines($tr, $p, $isSection && $target instanceof Document_Word_Section);
    }

    /**
     * @return bool
     */
    private function paragraphIsPageBreakOnly(DOMElement $p)
    {
        $text = $this->paragraphPlainText($p);
        if ($text !== '') {
            return false;
        }
        foreach ($p->getElementsByTagNameNS(self::NS_W, 'br') as $br) {
            if (!$br instanceof DOMElement) {
                continue;
            }
            if (strtolower($br->getAttributeNS(self::NS_W, 'type')) === 'page') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    private function paragraphHasRenderableContent(DOMElement $p)
    {
        foreach ($p->childNodes as $c) {
            if (!$c instanceof DOMElement || $c->namespaceURI !== self::NS_W) {
                continue;
            }
            if ($c->localName === 'r' || $c->localName === 'hyperlink') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Document_Word_Section_TextRun $tr
     * @param bool $allowPageBreak
     */
    private function emitParagraphInlines($tr, DOMElement $p, $allowPageBreak)
    {
        foreach ($p->childNodes as $c) {
            if (!$c instanceof DOMElement || $c->namespaceURI !== self::NS_W) {
                continue;
            }
            if ($c->localName === 'r') {
                $this->emitRun($tr, $c, $allowPageBreak);
                continue;
            }
            if ($c->localName === 'hyperlink') {
                $this->emitHyperlink($tr, $c);
                continue;
            }
            if ($c->localName === 'sdt') {
                $inner = $this->firstChildW($c, 'sdtContent');
                if ($inner instanceof DOMElement) {
                    foreach ($inner->childNodes as $cc) {
                        if (!$cc instanceof DOMElement || $cc->namespaceURI !== self::NS_W) {
                            continue;
                        }
                        if ($cc->localName === 'r') {
                            $this->emitRun($tr, $cc, $allowPageBreak);
                        }
                        if ($cc->localName === 'hyperlink') {
                            $this->emitHyperlink($tr, $cc);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Document_Word_Section_TextRun $tr
     * @param bool $allowPageBreak
     */
    private function emitRun($tr, DOMElement $r, $allowPageBreak)
    {
        $fontBase = $this->fontStyleFromRun($r);
        foreach ($r->childNodes as $c) {
            if (!$c instanceof DOMElement || $c->namespaceURI !== self::NS_W) {
                continue;
            }
            if ($c->localName === 't') {
                $text = $c->textContent;
                if ($text !== '') {
                    $tr->addText($text, $fontBase === array() ? null : $fontBase);
                }
                continue;
            }
            if ($c->localName === 'br') {
                $type = strtolower($c->getAttributeNS(self::NS_W, 'type'));
                if ($type === 'page') {
                    if ($allowPageBreak) {
                        $tr->addPageBreak();
                    }
                    continue;
                }
                $tr->addTextBreak(1);
                continue;
            }
            if ($c->localName === 'tab') {
                $tr->addText("\t", $fontBase === array() ? null : $fontBase);
                continue;
            }
            if ($c->localName === 'drawing' && $c->namespaceURI === self::NS_W) {
                $this->emitDrawing($tr, $c);
            }
        }
    }

    /**
     * @param Document_Word_Section_TextRun $tr
     */
    private function emitHyperlink($tr, DOMElement $h)
    {
        $rid = $h->getAttributeNS(self::NS_R, 'id');
        $href = $this->hyperlinkHref($rid);
        $label = $this->hyperlinkPlainText($h);
        $font = null;
        foreach ($h->getElementsByTagNameNS(self::NS_W, 'r') as $r) {
            if ($r instanceof DOMElement) {
                $f = $this->fontStyleFromRun($r);
                if ($f !== array()) {
                    $font = $f;
                }
                break;
            }
        }
        if ($href !== null) {
            $tr->addLink($href, $label !== '' ? $label : $href, $font);
            return;
        }
        foreach ($h->childNodes as $c) {
            if ($c instanceof DOMElement && $c->namespaceURI === self::NS_W && $c->localName === 'r') {
                $this->emitRun($tr, $c, false);
            }
        }
    }

    /**
     * @return string|null
     */
    private function hyperlinkHref($rid)
    {
        if ($rid === '' || !isset($this->rels[$rid])) {
            return null;
        }
        $rel = $this->rels[$rid];
        if ($rel['type'] !== 'hyperlink') {
            return null;
        }
        if ($rel['external']) {
            return $rel['target'];
        }

        return null;
    }

    /**
     * @param Document_Word_Section $section
     */
    private function readTable($section, DOMElement $tbl, DOMXPath $xp)
    {
        $table = $section->addTable();
        foreach ($tbl->childNodes as $rowEl) {
            if (!$rowEl instanceof DOMElement || $rowEl->namespaceURI !== self::NS_W || $rowEl->localName !== 'tr') {
                continue;
            }
            $table->addRow();
            foreach ($rowEl->childNodes as $cellEl) {
                if (!$cellEl instanceof DOMElement || $cellEl->namespaceURI !== self::NS_W || $cellEl->localName !== 'tc') {
                    continue;
                }
                $width = $this->cellWidthTwips($cellEl);
                var_dump($width);
                die('test');
                $cell = $table->addCell($width > 0 ? (int) max(1, round($width / 20)) : 0);
                foreach ($cellEl->childNodes as $inner) {
                    if (!$inner instanceof DOMElement || $inner->namespaceURI !== self::NS_W) {
                        continue;
                    }
                    if ($inner->localName === 'p') {
                        $this->readParagraph($cell, $inner, false, $xp);
                    }
                }
            }
        }
    }

    /**
     * @return int
     */
    private function cellWidthTwips(DOMElement $tc)
    {
        $tcPr = $this->firstChildW($tc, 'tcPr');
        if (!$tcPr instanceof DOMElement) {
            return 0;
        }
        $tcW = $this->firstChildW($tcPr, 'tcW');
        if (!$tcW instanceof DOMElement) {
            return 0;
        }
        $w = $tcW->getAttributeNS(self::NS_W, 'w');
        if ($w === '' || !ctype_digit($w)) {
            return 0;
        }

        return (int) $w;
    }

    /**
     * @return bool
     */
    private function paragraphHasDirectHyperlink(DOMElement $p)
    {
        foreach ($p->childNodes as $c) {
            if ($c instanceof DOMElement && $c->namespaceURI === self::NS_W && $c->localName === 'hyperlink') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    private function paragraphIsSingleUniformText(DOMElement $p)
    {
        $rCount = 0;
        foreach ($p->childNodes as $c) {
            if (!$c instanceof DOMElement || $c->namespaceURI !== self::NS_W) {
                continue;
            }
            if ($c->localName === 'hyperlink' || $c->localName === 'sdt') {
                return false;
            }
            if ($c->localName === 'r') {
                $rCount++;
                if ($rCount > 1) {
                    return false;
                }
                if (!$this->runIsPlainTextOnly($c)) {
                    return false;
                }
            }
        }

        return $rCount === 1;
    }

    /**
     * @return bool
     */
    private function runIsPlainTextOnly(DOMElement $r)
    {
        foreach ($r->childNodes as $c) {
            if (!$c instanceof DOMElement || $c->namespaceURI !== self::NS_W) {
                continue;
            }
            if ($c->localName !== 't' && $c->localName !== 'rPr') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return int 0 if not a heading
     */
    private function paragraphHeadingDepth(DOMElement $p)
    {
        $pPr = $this->firstChildW($p, 'pPr');
        if (!$pPr instanceof DOMElement) {
            return 0;
        }
        $outline = $this->firstChildW($pPr, 'outlineLvl');
        if ($outline instanceof DOMElement) {
            $v = $outline->getAttributeNS(self::NS_W, 'val');
            if ($v !== '' && ctype_digit($v)) {
                return min(6, max(1, (int) $v + 1));
            }
        }
        $pStyle = $this->firstChildW($pPr, 'pStyle');
        if (!$pStyle instanceof DOMElement) {
            return 0;
        }
        $val = $pStyle->getAttributeNS(self::NS_W, 'val');
        if ($val === '') {
            return 0;
        }
        if (preg_match('/heading\s*(\d+)/i', $val, $m)) {
            return min(6, max(1, (int) $m[1]));
        }
        if (preg_match('/^Heading\s*(\d+)$/i', $val, $m)) {
            return min(6, max(1, (int) $m[1]));
        }
        if (strcasecmp($val, 'Title') === 0) {
            return 1;
        }

        return 0;
    }

    /**
     * @return array
     */
    private function paragraphStyleArray(DOMElement $p)
    {
        $pPr = $this->firstChildW($p, 'pPr');
        if (!$pPr instanceof DOMElement) {
            return array();
        }
        $jc = $this->firstChildW($pPr, 'jc');
        if (!$jc instanceof DOMElement) {
            return array();
        }
        $val = $jc->getAttributeNS(self::NS_W, 'val');
        if ($val === '') {
            return array();
        }
        $map = array(
            'center' => 'center',
            'right' => 'right',
            'left' => 'left',
            'both' => 'both',
            'justify' => 'both',
        );
        $k = strtolower($val);
        if (!isset($map[$k])) {
            return array();
        }

        return array('align' => $map[$k]);
    }

    /**
     * @return array
     */
    private function fontStyleFromRun(DOMElement $r)
    {
        $rPr = $this->firstChildW($r, 'rPr');
        $rStyleId = '';
        if ($rPr instanceof DOMElement) {
            $rs = $this->firstChildW($rPr, 'rStyle');
            if ($rs instanceof DOMElement) {
                $rStyleId = $rs->getAttributeNS(self::NS_W, 'val');
            }
        }
        $fromStyle = $rStyleId !== '' ? $this->resolvedCharStyleFont($rStyleId) : array();
        if (!$rPr instanceof DOMElement) {
            return $fromStyle;
        }
        $fromRun = $this->fontStylePartialFromRPr($rPr);

        return $this->mergeFontStylePartials($fromStyle, $fromRun);
    }

    /**
     * @param string|null $val
     * @return string
     */
    private function mapUnderline($val)
    {
        require_once __DIR__ . '/Style/Font.php';
        if ($val === null || $val === '') {
            return Document_Word_Style_Font::UNDERLINE_SINGLE;
        }
        $v = strtolower($val);
        if ($v === 'none' || $v === 'false') {
            return Document_Word_Style_Font::UNDERLINE_NONE;
        }
        static $map = null;
        if ($map === null) {
            $map = array(
                'single' => Document_Word_Style_Font::UNDERLINE_SINGLE,
                'words' => Document_Word_Style_Font::UNDERLINE_WORDS,
                'double' => Document_Word_Style_Font::UNDERLINE_DOUBLE,
                'thick' => Document_Word_Style_Font::UNDERLINE_HEAVY,
                'dotted' => Document_Word_Style_Font::UNDERLINE_DOTTED,
                'dash' => Document_Word_Style_Font::UNDERLINE_DASH,
                'dotDash' => Document_Word_Style_Font::UNDERLINE_DOTHASH,
                'dotDotDash' => Document_Word_Style_Font::UNDERLINE_DOTDOTDASH,
                'wave' => Document_Word_Style_Font::UNDERLINE_WAVY,
            );
        }
        if (isset($map[$v])) {
            return $map[$v];
        }

        return Document_Word_Style_Font::UNDERLINE_SINGLE;
    }

    /**
     * @return string
     */
    private function paragraphPlainText(DOMElement $p)
    {
        $xp = new DOMXPath($p->ownerDocument);
        $xp->registerNamespace('w', self::NS_W);
        $nodes = $xp->query('.//w:t', $p);
        if ($nodes === false) {
            return '';
        }
        $parts = array();
        foreach ($nodes as $t) {
            if ($t instanceof DOMElement) {
                $parts[] = $t->textContent;
            }
        }

        return implode('', $parts);
    }

    /**
     * @return string
     */
    private function hyperlinkPlainText(DOMElement $h)
    {
        $xp = new DOMXPath($h->ownerDocument);
        $xp->registerNamespace('w', self::NS_W);
        $nodes = $xp->query('.//w:t', $h);
        if ($nodes === false) {
            return '';
        }
        $parts = array();
        foreach ($nodes as $t) {
            if ($t instanceof DOMElement) {
                $parts[] = $t->textContent;
            }
        }

        return implode('', $parts);
    }

    /**
     * First direct child in the w: namespace with the given local name.
     *
     * @param string $local
     * @return DOMElement|null
     */
    private function firstChildW(DOMElement $parent, $local)
    {
        foreach ($parent->childNodes as $c) {
            if ($c instanceof DOMElement && $c->namespaceURI === self::NS_W && $c->localName === $local) {
                return $c;
            }
        }

        return null;
    }
}

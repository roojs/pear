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
    private $_zip;

    /** @var array<string, array{type:string,target:string,external:bool}> */
    private $_rels = array();

    /** @var array<int, int> w:numId to w:abstractNumId */
    private $_numberingNumAbstract = array();

    /** @var array<int, array<int, string>> abstractNumId -> ilvl -> w:numFmt @w:val (lowercase) */
    private $_numberingAbstractLevels = array();

    /** @var array<string, DOMElement> w:styleId -> w:style */
    private $_stylesById = array();

    /** @var array<string, array> merged font partials from each style's w:rPr chain */
    private $_charStyleResolvedCache = array();

    /** @var array<string, bool> */
    private $_styleResolving = array();

    /** @var array<int, string> */
    private static $_extractedImageTemps = array();

    private static $_shutdownCleanupRegistered = false;

    /**
     * @param string $path Absolute or relative path to a .docx file
     * @return Document_Word
     * @throws Exception
     */
    public function load($path)
    {
        if (!is_string($path) || $path === '') {
            throw new Exception('Document_Word_Reader::load() requires a non-empty path.');
        }
        if (!is_readable($path)) {
            throw new Exception('Document_Word_Reader::load() cannot read file: ' . $path);
        }

        self::$_shutdownCleanupRegistered = false;

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
        $this->_rels = $relsXml !== false ? $this->_parseRelsXml($relsXml) : array();
        $this->_loadNumberingMaps($zip);
        $this->_loadStylesMaps($zip);

        $dom = new DOMDocument();
        if (@$dom->loadXML($documentXml, LIBXML_NONET) === false) {
            $zip->close();
            throw new Exception('Document_Word_Reader::load() invalid XML in word/document.xml');
        }

        require_once __DIR__ . '/../Word.php';
        $doc = new Document_Word();
        $this->_applyCoreProperties($zip, $doc);

        $xp = new DOMXPath($dom);
        $xp->registerNamespace('w', self::NS_W);
        $xp->registerNamespace('r', self::NS_R);

        $body = $xp->query('//w:body')->item(0);
        if (!$body instanceof DOMElement) {
            $zip->close();
            throw new Exception('Document_Word_Reader::load() missing w:body');
        }

        $this->_zip = $zip;
        $section = $doc->createSection();
        $this->_walkBlockContainer($section, $body, true, $xp);
        $this->_zip = null;
        $zip->close();

        return $doc;
    }

    /**
     * @param string $xml
     * @return array<string, array{type:string,target:string,external:bool}>
     */
    private function _parseRelsXml($xml)
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
    private function _loadNumberingMaps($zip)
    {
        $this->_numberingNumAbstract = array();
        $this->_numberingAbstractLevels = array();
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
            $this->_numberingAbstractLevels[$abstractId] = array();
            foreach ($an->childNodes as $lvlCandidate) {
                if (!$lvlCandidate instanceof DOMElement || !$this->_isW($lvlCandidate) || $lvlCandidate->localName !== 'lvl') {
                    continue;
                }
                $lvl = $lvlCandidate;
                $ilvl = $lvl->getAttributeNS(self::NS_W, 'ilvl');
                if ($ilvl === '' || !ctype_digit($ilvl)) {
                    continue;
                }
                $nfEl = $this->_firstChildW($lvl, 'numFmt');
                if (!$nfEl instanceof DOMElement) {
                    continue;
                }
                $fmt = strtolower((string) $nfEl->getAttributeNS(self::NS_W, 'val'));
                if ($fmt !== '') {
                    $this->_numberingAbstractLevels[$abstractId][(int) $ilvl] = $fmt;
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
            $abi = $this->_firstChildW($num, 'abstractNumId');
            if (!$abi instanceof DOMElement) {
                continue;
            }
            $aval = $abi->getAttributeNS(self::NS_W, 'val');
            if ($aval !== '' && ctype_digit($aval)) {
                $this->_numberingNumAbstract[(int) $nid] = (int) $aval;
            }
        }
    }

    /**
     * Index word/styles.xml by w:styleId for w:rStyle resolution (w:basedOn + w:rPr merge).
     *
     * @param ZipArchive $zip
     */
    private function _loadStylesMaps($zip)
    {
        $this->_stylesById = array();
        $this->_charStyleResolvedCache = array();
        $this->_styleResolving = array();
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
            $this->_stylesById[$sid] = $st;
        }
    }

    /**
     * @param string $styleId w:rStyle/@w:val
     * @return array partial font style from w:basedOn chain + this style's w:rPr
     */
    private function _resolvedCharStyleFont($styleId)
    {
        if ($styleId === '') {
            return array();
        }
        if (isset($this->_charStyleResolvedCache[$styleId])) {
            return $this->_charStyleResolvedCache[$styleId];
        }
        if (isset($this->_styleResolving[$styleId])) {
            return array();
        }
        $this->_styleResolving[$styleId] = true;
        $merged = array();
        $node = isset($this->_stylesById[$styleId]) ? $this->_stylesById[$styleId] : null;
        if ($node instanceof DOMElement) {
            $basePartial = array();
            $basedOn = $this->_firstChildW($node, 'basedOn');
            if ($basedOn instanceof DOMElement) {
                $pid = $basedOn->getAttributeNS(self::NS_W, 'val');
                if ($pid !== '') {
                    $basePartial = $this->_resolvedCharStyleFont($pid);
                }
            }
            $rPr = $this->_firstChildW($node, 'rPr');
            $thisPartial = $rPr instanceof DOMElement ? $this->_fontStylePartialFromRPr($rPr) : array();
            $merged = $this->_mergeFontStylePartials($basePartial, $thisPartial);
        }
        unset($this->_styleResolving[$styleId]);
        $this->_charStyleResolvedCache[$styleId] = $merged;

        return $merged;
    }

    /**
     * @param array $base
     * @param array $overlay explicit run/style keys in $overlay replace $base
     * @return array
     */
    private function _mergeFontStylePartials($base, $overlay)
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
    private function _wOnOffIsTrue(DOMElement $el)
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
    private function _fontStylePartialFromRPr(DOMElement $rPr)
    {
        require_once __DIR__ . '/Style/Font.php';
        $style = array();
        $b = $this->_firstChildW($rPr, 'b');
        if ($b instanceof DOMElement) {
            $style['bold'] = $this->_wOnOffIsTrue($b);
        }
        $i = $this->_firstChildW($rPr, 'i');
        if ($i instanceof DOMElement) {
            $style['italic'] = $this->_wOnOffIsTrue($i);
        }
        $u = $this->_firstChildW($rPr, 'u');
        if ($u instanceof DOMElement) {
            $uv = $u->getAttributeNS(self::NS_W, 'val');
            $mapped = $this->_mapUnderline($uv);
            if ($mapped !== Document_Word_Style_Font::UNDERLINE_NONE) {
                $style['underline'] = $mapped;
            }
        }
        $sz = $this->_firstChildW($rPr, 'sz');
        if ($sz instanceof DOMElement) {
            $half = $sz->getAttributeNS(self::NS_W, 'val');
            if ($half !== '' && ctype_digit($half)) {
                $style['size'] = max(1, (int) round(((int) $half) / 2));
            }
        }
        $color = $this->_firstChildW($rPr, 'color');
        if ($color instanceof DOMElement) {
            $cv = $color->getAttributeNS(self::NS_W, 'val');
            if ($cv !== '' && strcasecmp($cv, 'auto') !== 0) {
                if (strlen($cv) === 8) {
                    $cv = substr($cv, 2);
                }
                $style['color'] = $cv;
            }
        }
        $fonts = $this->_firstChildW($rPr, 'rFonts');
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
    private function _numFmtIsOrdered($numFmtLower)
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
    private function _listNumIdIsOrdered($numId, $ilvl)
    {
        if (!isset($this->_numberingNumAbstract[$numId])) {
            return false;
        }
        $abstractId = $this->_numberingNumAbstract[$numId];
        if (!isset($this->_numberingAbstractLevels[$abstractId])) {
            return false;
        }
        $levels = $this->_numberingAbstractLevels[$abstractId];
        if (isset($levels[$ilvl])) {
            return $this->_numFmtIsOrdered($levels[$ilvl]);
        }
        if (isset($levels[0])) {
            return $this->_numFmtIsOrdered($levels[0]);
        }

        return false;
    }

    /**
     * @return array{numId:int,ilvl:int}|null
     */
    private function _paragraphListNumPr(DOMElement $p)
    {
        $pPr = $this->_firstChildW($p, 'pPr');
        if (!$pPr instanceof DOMElement) {
            return null;
        }
        $numPr = $this->_firstChildW($pPr, 'numPr');
        if (!$numPr instanceof DOMElement) {
            return null;
        }
        $ilvlEl = $this->_firstChildW($numPr, 'ilvl');
        $numIdEl = $this->_firstChildW($numPr, 'numId');
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
    private function _firstParagraphRunFontStyle(DOMElement $p)
    {
        foreach ($p->getElementsByTagNameNS(self::NS_W, 'r') as $r) {
            if ($r instanceof DOMElement) {
                return $this->_fontStyleFromRun($r);
            }
        }

        return array();
    }

    /**
     * @param ZipArchive $zip
     * @param Document_Word $doc
     */
    private function _applyCoreProperties($zip, $doc)
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

    public static function _unlinkQueuedImageTemps()
    {
        foreach (self::$_extractedImageTemps as $p) {
            @unlink($p);
        }
        self::$_extractedImageTemps = array();
    }

    /**
     * @param string $path
     */
    private function _queueImageTemp($path)
    {
        self::$_extractedImageTemps[] = $path;
        if (!self::$_shutdownCleanupRegistered) {
            self::$_shutdownCleanupRegistered = true;
            register_shutdown_function(array('Document_Word_Reader', '_unlinkQueuedImageTemps'));
        }
    }

    /**
     * @param string $rid
     * @return string|null Package path for ZipArchive::getFromName, or null
     */
    private function _imagePackagePartPath($rid)
    {
        if ($rid === '' || !isset($this->_rels[$rid])) {
            return null;
        }
        $rel = $this->_rels[$rid];
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
     * @return array<int, string>
     */
    private function _supportedRasterImageExtensions()
    {
        return array('jpg', 'jpeg', 'gif', 'png', 'bmp', 'tif', 'tiff');
    }

    /**
     * @param string $rid
     * @return string|null Absolute temp path
     */
    private function _extractEmbeddedImageToTemp($rid)
    {
        if (!($this->_zip instanceof ZipArchive)) {
            return null;
        }
        $part = $this->_imagePackagePartPath($rid);
        if ($part === null) {
            return null;
        }
        $bytes = $this->_zip->getFromName($part);
        if ($bytes === false || $bytes === '') {
            return null;
        }
        $ext = strtolower(pathinfo($part, PATHINFO_EXTENSION));
        if ($ext === '' || !in_array($ext, $this->_supportedRasterImageExtensions(), true)) {
            return null;
        }
        $tmp = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'dw-docx-img-' . uniqid('', true) . '.' . $ext;
        if (@file_put_contents($tmp, $bytes) === false) {
            return null;
        }
        $this->_queueImageTemp($tmp);

        return $tmp;
    }

    /**
     * @return array{width?:int,height?:int}
     */
    private function _docxImageStyleFromDrawing(DOMElement $drawing)
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
    private function _emitDrawing($tr, DOMElement $drawing)
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
            $tmp = $this->_extractEmbeddedImageToTemp($embed);
            if ($tmp === null) {
                continue;
            }
            $style = $this->_docxImageStyleFromDrawing($drawing);
            $tr->addImage($tmp, $style === array() ? null : $style);
            break;
        }
    }

    /**
     * @param Document_Word_Section|Document_Word_Section_Table_Cell $target
     */
    private function _walkBlockContainer($target, DOMElement $container, $isSection, DOMXPath $xp)
    {
        foreach ($container->childNodes as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }
            if (!$this->_isW($child)) {
                continue;
            }
            $ln = $child->localName;
            if ($ln === 'p') {
                $this->_readParagraph($target, $child, $isSection, $xp);
                continue;
            }
            if ($ln === 'tbl') {
                if (!$isSection || !($target instanceof Document_Word_Section)) {
                    continue;
                }
                $this->_readTable($target, $child, $xp);
                continue;
            }
            if ($ln === 'sdt') {
                $content = $this->_firstChildW($child, 'sdtContent');
                if ($content instanceof DOMElement) {
                    $this->_walkBlockContainer($target, $content, $isSection, $xp);
                }
                continue;
            }
        }
    }

    /**
     * @param Document_Word_Section|Document_Word_Section_Table_Cell $target
     */
    private function _readParagraph($target, DOMElement $p, $isSection, DOMXPath $xp)
    {
        if ($isSection && $target instanceof Document_Word_Section) {
            $pPr = $this->_firstChildW($p, 'pPr');
            if ($pPr instanceof DOMElement && $this->_firstChildW($pPr, 'pageBreakBefore') !== null) {
                $target->addPageBreak();
            }
        }

        if ($this->_paragraphIsPageBreakOnly($p)) {
            if ($isSection && $target instanceof Document_Word_Section) {
                $target->addPageBreak();
            }
            return;
        }

        $listNum = $this->_paragraphListNumPr($p);
        if ($listNum !== null) {
            $plainList = $this->_paragraphPlainText($p);
            if ($plainList === '' && !$this->_paragraphHasRenderableContent($p)) {
                return;
            }
            $depth = min(8, max(0, $listNum['ilvl']));
            $numId = $listNum['numId'];
            $ordered = $this->_listNumIdIsOrdered($numId, $depth);
            $font = $this->_firstParagraphRunFontStyle($p);
            $target->addListItem($plainList, $depth, $font === array() ? null : $font, array(
                'listType' => $numId,
                'isOrdered' => $ordered,
            ));
            return;
        }

        $plain = $this->_paragraphPlainText($p);
        if ($plain === '' && !$this->_paragraphHasRenderableContent($p)) {
            return;
        }

        $headingDepth = $this->_paragraphHeadingDepth($p);
        $hasHyperlink = $this->_paragraphHasDirectHyperlink($p);

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

        $paraStyle = $this->_paragraphStyleArray($p);
        if ($this->_paragraphIsSingleUniformText($p) && !$hasHyperlink) {
            $r = $this->_firstChildW($p, 'r');
            if ($r instanceof DOMElement) {
                $font = $this->_fontStyleFromRun($r);
                $target->addText($plain, $font === array() ? null : $font, $paraStyle === array() ? null : $paraStyle);
                return;
            }
        }

        $tr = $target->createTextRun($paraStyle === array() ? null : $paraStyle);
        $this->_emitParagraphInlines($tr, $p, $isSection && $target instanceof Document_Word_Section);
    }

    /**
     * @return bool
     */
    private function _paragraphIsPageBreakOnly(DOMElement $p)
    {
        $text = $this->_paragraphPlainText($p);
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
    private function _paragraphHasRenderableContent(DOMElement $p)
    {
        foreach ($p->childNodes as $c) {
            if (!$c instanceof DOMElement || !$this->_isW($c)) {
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
    private function _emitParagraphInlines($tr, DOMElement $p, $allowPageBreak)
    {
        foreach ($p->childNodes as $c) {
            if (!$c instanceof DOMElement || !$this->_isW($c)) {
                continue;
            }
            if ($c->localName === 'r') {
                $this->_emitRun($tr, $c, $allowPageBreak);
                continue;
            }
            if ($c->localName === 'hyperlink') {
                $this->_emitHyperlink($tr, $c);
                continue;
            }
            if ($c->localName === 'sdt') {
                $inner = $this->_firstChildW($c, 'sdtContent');
                if ($inner instanceof DOMElement) {
                    foreach ($inner->childNodes as $cc) {
                        if (!$cc instanceof DOMElement || !$this->_isW($cc)) {
                            continue;
                        }
                        if ($cc->localName === 'r') {
                            $this->_emitRun($tr, $cc, $allowPageBreak);
                        }
                        if ($cc->localName === 'hyperlink') {
                            $this->_emitHyperlink($tr, $cc);
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
    private function _emitRun($tr, DOMElement $r, $allowPageBreak)
    {
        $fontBase = $this->_fontStyleFromRun($r);
        foreach ($r->childNodes as $c) {
            if (!$c instanceof DOMElement || !$this->_isW($c)) {
                continue;
            }
            if ($c->localName === 't') {
                $text = $this->_textFromT($c);
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
                $this->_emitDrawing($tr, $c);
            }
        }
    }

    /**
     * @param Document_Word_Section_TextRun $tr
     */
    private function _emitHyperlink($tr, DOMElement $h)
    {
        $rid = $h->getAttributeNS(self::NS_R, 'id');
        $href = $this->_hyperlinkHref($rid);
        $label = $this->_hyperlinkPlainText($h);
        $font = null;
        foreach ($h->getElementsByTagNameNS(self::NS_W, 'r') as $r) {
            if ($r instanceof DOMElement) {
                $f = $this->_fontStyleFromRun($r);
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
            if ($c instanceof DOMElement && $this->_isW($c) && $c->localName === 'r') {
                $this->_emitRun($tr, $c, false);
            }
        }
    }

    /**
     * @return string|null
     */
    private function _hyperlinkHref($rid)
    {
        if ($rid === '' || !isset($this->_rels[$rid])) {
            return null;
        }
        $rel = $this->_rels[$rid];
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
    private function _readTable($section, DOMElement $tbl, DOMXPath $xp)
    {
        $table = $section->addTable();
        foreach ($tbl->childNodes as $rowEl) {
            if (!$rowEl instanceof DOMElement || !$this->_isW($rowEl) || $rowEl->localName !== 'tr') {
                continue;
            }
            $table->addRow();
            foreach ($rowEl->childNodes as $cellEl) {
                if (!$cellEl instanceof DOMElement || !$this->_isW($cellEl) || $cellEl->localName !== 'tc') {
                    continue;
                }
                $width = $this->_cellWidthTwips($cellEl);
                $cell = $table->addCell($width > 0 ? (int) max(1, round($width / 20)) : 0);
                foreach ($cellEl->childNodes as $inner) {
                    if (!$inner instanceof DOMElement || !$this->_isW($inner)) {
                        continue;
                    }
                    if ($inner->localName === 'p') {
                        $this->_readParagraph($cell, $inner, false, $xp);
                    }
                }
            }
        }
    }

    /**
     * @return int
     */
    private function _cellWidthTwips(DOMElement $tc)
    {
        $tcPr = $this->_firstChildW($tc, 'tcPr');
        if (!$tcPr instanceof DOMElement) {
            return 0;
        }
        $tcW = $this->_firstChildW($tcPr, 'tcW');
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
    private function _paragraphHasDirectHyperlink(DOMElement $p)
    {
        foreach ($p->childNodes as $c) {
            if ($c instanceof DOMElement && $this->_isW($c) && $c->localName === 'hyperlink') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    private function _paragraphIsSingleUniformText(DOMElement $p)
    {
        $rCount = 0;
        foreach ($p->childNodes as $c) {
            if (!$c instanceof DOMElement || !$this->_isW($c)) {
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
                if (!$this->_runIsPlainTextOnly($c)) {
                    return false;
                }
            }
        }

        return $rCount === 1;
    }

    /**
     * @return bool
     */
    private function _runIsPlainTextOnly(DOMElement $r)
    {
        foreach ($r->childNodes as $c) {
            if (!$c instanceof DOMElement || !$this->_isW($c)) {
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
    private function _paragraphHeadingDepth(DOMElement $p)
    {
        $pPr = $this->_firstChildW($p, 'pPr');
        if (!$pPr instanceof DOMElement) {
            return 0;
        }
        $outline = $this->_firstChildW($pPr, 'outlineLvl');
        if ($outline instanceof DOMElement) {
            $v = $outline->getAttributeNS(self::NS_W, 'val');
            if ($v !== '' && ctype_digit($v)) {
                return min(6, max(1, (int) $v + 1));
            }
        }
        $pStyle = $this->_firstChildW($pPr, 'pStyle');
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
    private function _paragraphStyleArray(DOMElement $p)
    {
        $pPr = $this->_firstChildW($p, 'pPr');
        if (!$pPr instanceof DOMElement) {
            return array();
        }
        $jc = $this->_firstChildW($pPr, 'jc');
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
    private function _fontStyleFromRun(DOMElement $r)
    {
        $rPr = $this->_firstChildW($r, 'rPr');
        $rStyleId = '';
        if ($rPr instanceof DOMElement) {
            $rs = $this->_firstChildW($rPr, 'rStyle');
            if ($rs instanceof DOMElement) {
                $rStyleId = $rs->getAttributeNS(self::NS_W, 'val');
            }
        }
        $fromStyle = $rStyleId !== '' ? $this->_resolvedCharStyleFont($rStyleId) : array();
        if (!$rPr instanceof DOMElement) {
            return $fromStyle;
        }
        $fromRun = $this->_fontStylePartialFromRPr($rPr);

        return $this->_mergeFontStylePartials($fromStyle, $fromRun);
    }

    /**
     * @param string|null $val
     * @return string
     */
    private function _mapUnderline($val)
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
    private function _paragraphPlainText(DOMElement $p)
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
                $parts[] = $this->_textFromT($t);
            }
        }

        return implode('', $parts);
    }

    /**
     * @return string
     */
    private function _hyperlinkPlainText(DOMElement $h)
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
                $parts[] = $this->_textFromT($t);
            }
        }

        return implode('', $parts);
    }

    /**
     * @return string
     */
    private function _textFromT(DOMElement $t)
    {
        return $t->textContent;
    }

    /**
     * @return bool
     */
    private function _isW(DOMElement $el)
    {
        return $el->namespaceURI === self::NS_W;
    }

    /**
     * First direct child in the w: namespace with the given local name.
     *
     * @param string $local
     * @return DOMElement|null
     */
    private function _firstChildW(DOMElement $parent, $local)
    {
        foreach ($parent->childNodes as $c) {
            if ($c instanceof DOMElement && $this->_isW($c) && $c->localName === $local) {
                return $c;
            }
        }

        return null;
    }
}

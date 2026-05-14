<?php
/**
 * HTML writer for {@see Document_Word}.
 * Serializes in-memory documents to a minimal HTML5 file via IOFactory createWriter(..., 'HTML').
 */

require_once __DIR__ . '/../../Word.php';
require_once __DIR__ . '/IWriter.php';
require_once __DIR__ . '/../Section/Footer/PreserveText.php';

class Document_Word_Writer_HTML implements Document_Word_Writer_IWriter
{
    /** @var Document_Word|null */
    private $document;

    public function __construct(?Document_Word $PHPWord = null)
    {
        $this->document = $PHPWord;
    }

    /**
     * @param string|null $pFilename
     */
    public function save($pFilename = null)
    {
        $content = $this->getContent();
        $originalFilename = $pFilename;
        if (strtolower((string) $pFilename) === 'php://output' || strtolower((string) $pFilename) === 'php://stdout') {
            $pFilename = @tempnam(sys_get_temp_dir(), 'phpwordhtml');
            if ($pFilename === '') {
                $pFilename = $originalFilename;
            }
        }
        if ($pFilename === null || $pFilename === '') {
            throw new Exception('HTML writer requires a filename or php://output');
        }
        if (@file_put_contents($pFilename, $content) === false) {
            throw new Exception('Could not write HTML to ' . $pFilename);
        }
        if ($originalFilename !== $pFilename && is_string($originalFilename)
            && (strtolower($originalFilename) === 'php://output' || strtolower($originalFilename) === 'php://stdout')) {
            readfile($pFilename);
            @unlink($pFilename);
        }
    }

    /**
     * @return string
     */
    public function getContent()
    {
        $title = '';
        if ($this->document !== null) {
            $props = $this->document->getProperties();
            if ($props !== null && method_exists($props, 'getTitle')) {
                $title = (string) $props->getTitle();
            }
        }

        $body = $this->writeBody();
        $safeTitle = htmlspecialchars($title !== '' ? $title : 'Document', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<!DOCTYPE html>' . "\n"
            . '<html lang="en">' . "\n"
            . '<head>' . "\n"
            . '<meta charset="UTF-8">' . "\n"
            . '<title>' . $safeTitle . '</title>' . "\n"
            . '</head>' . "\n"
            . '<body>' . "\n"
            . $body
            . '</body>' . "\n"
            . '</html>' . "\n";
    }

    /**
     * @return string
     */
    private function writeBody()
    {
        if ($this->document === null) {
            return '';
        }
        $html = '';
        foreach ($this->document->getSections() as $section) {
            $html .= $this->writeElementList($section->getElements());
        }
        return $html;
    }

    /**
     * @param array $elements
     * @return string
     */
    private function writeElementList($elements)
    {
        if (!is_array($elements)) {
            return '';
        }
        $html = '';
        $n = count($elements);
        for ($i = 0; $i < $n; $i++) {
            $element = $elements[$i];
            if ($element instanceof Document_Word_Section_ListItem) {
                $group = array($element);
                $j = $i + 1;
                while ($j < $n && $elements[$j] instanceof Document_Word_Section_ListItem) {
                    $group[] = $elements[$j];
                    $j++;
                }
                $html .= $this->writeListItemGroup($group);
                $i = $j - 1;
                continue;
            }
            $html .= $this->writeElement($element);
        }
        return $html;
    }

    /**
     * @param array<int, Document_Word_Section_ListItem> $items
     * @return string
     */
    private function writeListItemGroup($items)
    {
        if ($items === array()) {
            return '';
        }

        $roots = array();
        $stack = array();

        foreach ($items as $item) {
            $depth = max(0, (int) $item->getDepth());
            if ($depth > count($stack)) {
                $depth = count($stack);
            }
            while (count($stack) > $depth) {
                array_pop($stack);
            }

            $style = $item->getStyle();

            $node = array(
                'item' => $item,
                'tag' => $style->getIsOrdered() ? 'ol' : 'ul',
                'children' => array(),
            );

            if ($depth === 0) {
                $roots[] = $node;
                $stack[] = &$roots[count($roots) - 1];
                continue;
            }

            $parent = &$stack[$depth - 1];
            $parent['children'][] = $node;
            $stack[] = &$parent['children'][count($parent['children']) - 1];
        }

        return $this->writeListNodeLevel($roots);
    }

    /**
     * @param array<int, array{item:Document_Word_Section_ListItem,tag:string,children:array}> $nodes
     * @return string
     */
    private function writeListNodeLevel($nodes)
    {
        if ($nodes === array()) {
            return '';
        }

        $html = '';
        $currentTag = '';
        foreach ($nodes as $node) {
            $tag = $node['tag'];
            if ($currentTag !== $tag) {
                if ($currentTag !== '') {
                    $html .= '</' . $currentTag . ">\n";
                }
                $html .= '<' . $tag . ' style="margin:0.3em 0;padding-left:1.5em">' . "\n";
                $currentTag = $tag;
            }

            $inner = '';
            $inlineElements = $node['item']->getElements();
            if (is_array($inlineElements) && $inlineElements !== array()) {
                foreach ($inlineElements as $inline) {
                    $inner .= $this->writeInlineElement($inline);
                }
            }
            if ($inner === '') {
                $textObj = $node['item']->getTextObject();
                $inner = $this->writeTextRunContentFromText($textObj);
            }
            if ($node['children'] !== array()) {
                $inner .= "\n" . $this->writeListNodeLevel($node['children']);
            }
            $html .= '<li>' . $inner . "</li>\n";
        }
        if ($currentTag !== '') {
            $html .= '</' . $currentTag . ">\n";
        }

        return $html;
    }

    /**
     * @param mixed $element
     * @return string
     */
    private function writeElement($element)
    {
        if ($element instanceof Document_Word_Section_Text) {
            return $this->writeParagraphFromText($element);
        }
        if ($element instanceof Document_Word_Section_TextRun) {
            return $this->writeTextRunBlock($element);
        }
        if ($element instanceof Document_Word_Section_Link) {
            return $this->writeLinkBlock($element);
        }
        if ($element instanceof Document_Word_Section_Title) {
            return $this->writeTitle($element);
        }
        if ($element instanceof Document_Word_Section_TextBreak) {
            return "<br>\n";
        }
        if ($element instanceof Document_Word_Section_PageBreak) {
            return '<div style="page-break-before:always"></div>' . "\n";
        }
        if ($element instanceof Document_Word_Section_Table) {
            return $this->writeTable($element);
        }
        if ($element instanceof Document_Word_Section_ListItem) {
            return $this->writeListItemGroup(array($element));
        }
        if ($element instanceof Document_Word_Section_Image
            || $element instanceof Document_Word_Section_MemoryImage) {
            return $this->writeImageBlock($element);
        }
        if ($element instanceof Document_Word_Section_Object) {
            return "<!-- embedded OLE object omitted -->\n";
        }
        if ($element instanceof Document_Word_TOC) {
            return "<!-- table of contents omitted -->\n";
        }

        return '';
    }

    /**
     * @param $table
     * @return string
     */
    private function writeTable($table)
    {
        $html = "<table border=\"1\" style=\"border-collapse:collapse\">\n";
        foreach ($table->getRows() as $row) {
            $html .= "<tr>\n";
            foreach ($row as $cell) {
                $html .= '<td';
                $w = $cell->getWidth();
                if ($w !== null) {
                    $html .= ' style="width:' . (int) $w . 'px"';
                }
                $html .= '>';
                $html .= $this->writeElementList($cell->getElements());
                $html .= "</td>\n";
            }
            $html .= "</tr>\n";
        }
        $html .= "</table>\n";
        return $html;
    }

    /**
     * @param $text
     * @return string
     */
    private function writeTextRunContentFromText($text)
    {
        require_once __DIR__ . '/../Shared/String.php';
        $raw = Document_Word_Shared_String::ControlCharacterPHP2OOXML($text->getText());
        $inner = htmlspecialchars($raw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return $this->wrapWithFontStyle($inner, $text->getFontStyle());
    }

    /**
     * @param $text
     * @return string
     */
    private function writeParagraphFromText($text)
    {
        $pOpen = $this->paragraphOpenTag($text->getParagraphStyle());
        return $pOpen . $this->writeTextRunContentFromText($text) . "</p>\n";
    }

    /**
     * @param mixed $styleParagraph
     * @return string opening tag
     */
    private function paragraphOpenTag($styleParagraph)
    {
        $attr = $this->paragraphStyleAttr($styleParagraph);
        if ($attr === '') {
            return '<p>';
        }
        return '<p style="' . $attr . '">';
    }

    /**
     * @param mixed $styleParagraph
     * @return string CSS for style attribute
     */
    private function paragraphStyleAttr($styleParagraph)
    {
        if (!($styleParagraph instanceof Document_Word_Style_Paragraph)) {
            return '';
        }
        $parts = array();
        $align = $styleParagraph->getAlign();
        if ($align !== null && $align !== '') {
            if ($align === 'both') {
                $parts[] = 'text-align:justify';
            }
            if ($align === 'center' || $align === 'right' || $align === 'left') {
                $parts[] = 'text-align:' . $align;
            }
        }
        $sb = $styleParagraph->getSpaceBefore();
        if ($sb !== null && is_numeric($sb)) {
            $parts[] = 'margin-top:' . round(((float) $sb) / 20) . 'px';
        }
        $sa = $styleParagraph->getSpaceAfter();
        if ($sa !== null && is_numeric($sa)) {
            $parts[] = 'margin-bottom:' . round(((float) $sa) / 20) . 'px';
        }
        return implode(';', $parts);
    }

    /**
     * @param $textrun
     * @return string
     */
    private function writeTextRunBlock($textrun)
    {
        $html = $this->paragraphOpenTag($textrun->getParagraphStyle());
        foreach ($textrun->getElements() as $el) {
            $html .= $this->writeInlineElement($el);
        }
        return $html . "</p>\n";
    }

    /**
     * @param mixed $el
     * @return string
     */
    private function writeInlineElement($el)
    {
        if ($el instanceof Document_Word_Section_Text) {
            return $this->writeTextRunContentFromText($el);
        }
        if ($el instanceof Document_Word_Section_Link) {
            return $this->writeLinkInline($el);
        }
        if ($el instanceof Document_Word_Section_Image
            || $el instanceof Document_Word_Section_MemoryImage) {
            return $this->writeImageInline($el);
        }
        if ($el instanceof Document_Word_Section_TextBreak) {
            return '<br>';
        }
        if ($el instanceof Document_Word_Section_PageBreak) {
            return '<span style="page-break-before:always"></span>';
        }
        if ($el instanceof Document_Word_Section_Footer_PreserveText) {
            return $this->writePreserveTextInline($el);
        }

        return '';
    }

    /**
     * @param $pt
     * @return string
     */
    private function writePreserveTextInline($pt)
    {
        $t = $pt->getText();
        require_once __DIR__ . '/../Shared/String.php';
        if (!is_array($t)) {
            $raw = Document_Word_Shared_String::ControlCharacterPHP2OOXML((string) $t);
            return $this->wrapWithFontStyle(htmlspecialchars($raw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), $pt->getFontStyle());
        }
        $chunk = '';
        foreach ($t as $part) {
            $raw = Document_Word_Shared_String::ControlCharacterPHP2OOXML((string) $part);
            $chunk .= $this->wrapWithFontStyle(htmlspecialchars($raw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), $pt->getFontStyle());
        }
        return $chunk;
    }

    /**
     * @param $link
     * @return string
     */
    private function writeLinkBlock($link)
    {
        return $this->paragraphOpenTag($link->getParagraphStyle()) . $this->writeLinkInline($link) . "</p>\n";
    }

    /**
     * @param $link
     * @return string
     */
    private function writeLinkInline($link)
    {
        $href = htmlspecialchars($link->getLinkSrc(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $label = $link->getLinkName();
        if ($label === null || $label === '') {
            $label = $link->getLinkSrc();
        }
        require_once __DIR__ . '/../Shared/String.php';
        $raw = Document_Word_Shared_String::ControlCharacterPHP2OOXML((string) $label);
        $inner = $this->wrapWithFontStyle(htmlspecialchars($raw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), $link->getFontStyle());
        return '<a href="' . $href . '">' . $inner . '</a>';
    }

    /**
     * @param $title
     * @return string
     */
    private function writeTitle($title)
    {
        $level = $title->getDepth();
        if ($level < 1) {
            $level = 1;
        }
        if ($level > 6) {
            $level = 6;
        }
        $t = htmlspecialchars($title->getText(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $h = 'h' . $level;
        $id = $title->getAnchor();
        if ($id !== null && $id !== '') {
            $safeId = htmlspecialchars((string) $id, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            return '<' . $h . ' id="h-' . $safeId . '">' . $t . '</' . $h . ">\n";
        }
        return '<' . $h . '>' . $t . '</' . $h . ">\n";
    }

    /**
     * @param Document_Word_Section_Image|Document_Word_Section_MemoryImage $img
     * @return string
     */
    private function writeImageBlock($img)
    {
        return '<p>' . $this->writeImageInline($img) . "</p>\n";
    }

    /**
     * @param Document_Word_Section_Image|Document_Word_Section_MemoryImage $img
     * @return string
     */
    private function writeImageInline($img)
    {
        $src = $img->getSource();
        if ($src === null || $src === '') {
            return '';
        }
        if (stripos($src, 'data:') !== 0 && stripos($src, 'http://') !== 0 && stripos($src, 'https://') !== 0 && stripos($src, '//') !== 0) {
            if (is_file($src) && is_readable($src)) {
                $info = @getimagesize($src);
                $mime = is_array($info) && !empty($info['mime']) ? $info['mime'] : '';
                if ($mime === '' && function_exists('finfo_open')) {
                    $fi = @finfo_open(FILEINFO_MIME_TYPE);
                    if ($fi) {
                        $mime = (string) @finfo_file($fi, $src);
                        finfo_close($fi);
                    }
                }
                if ($mime !== '' && strpos($mime, 'image/') === 0) {
                    $raw = @file_get_contents($src);
                    if ($raw !== false && $raw !== '') {
                        $src = 'data:' . $mime . ';base64,' . base64_encode($raw);
                    }
                }
            }
        }
        $esc = htmlspecialchars($src, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $style = $img->getStyle();
        $attrs = ' src="' . $esc . '" alt=""';
        if ($style !== null) {
            $w = $style->getWidth();
            $h = $style->getHeight();
            if ($w !== null) {
                $attrs .= ' width="' . (int) $w . '"';
            }
            if ($h !== null) {
                $attrs .= ' height="' . (int) $h . '"';
            }
        }
        return '<img' . $attrs . '>';
    }

    /**
     * @param string $escaped utf-8, already entity-escaped
     * @param mixed $styleFont
     * @return string
     */
    private function wrapWithFontStyle($escaped, $styleFont)
    {
        require_once __DIR__ . '/../Style/Font.php';
        if ($styleFont instanceof Document_Word_Style_Font) {
            $f = $styleFont;
            $inner = $escaped;
            if ($f->getSuperScript()) {
                $inner = '<sup>' . $inner . '</sup>';
            }
            if ($f->getSubScript()) {
                $inner = '<sub>' . $inner . '</sub>';
            }
            if ($f->getBold()) {
                $inner = '<b>' . $inner . '</b>';
            }
            if ($f->getItalic()) {
                $inner = '<i>' . $inner . '</i>';
            }
            if ($f->getUnderline() !== null
                && $f->getUnderline() !== Document_Word_Style_Font::UNDERLINE_NONE) {
                $inner = '<u>' . $inner . '</u>';
            }
            if ($f->getStrikethrough()) {
                $inner = '<s>' . $inner . '</s>';
            }
            $spanStyles = array();
            $name = $f->getName();
            if ($name !== null && $name !== '' && $name !== 'Arial') {
                $spanStyles[] = 'font-family:' . htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
            $size = $f->getSize();
            if ($size !== null && is_numeric($size)) {
                $spanStyles[] = 'font-size:' . (((float) $size) / 2) . 'pt';
            }
            $color = $f->getColor();
            if ($color !== null && $color !== '' && strtolower($color) !== '000000') {
                $hex = strlen($color) === 6 ? '#' . $color : $color;
                $spanStyles[] = 'color:' . htmlspecialchars($hex, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
            if (count($spanStyles) > 0) {
                $inner = '<span style="' . implode(';', $spanStyles) . '">' . $inner . '</span>';
            }
            return $inner;
        }
        if (is_string($styleFont) && $styleFont !== '') {
            return '<span class="' . htmlspecialchars($styleFont, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">' . $escaped . '</span>';
        }

        return $escaped;
    }
}

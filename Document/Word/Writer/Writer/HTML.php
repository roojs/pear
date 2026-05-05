<?php
/**
 * HTML writer for Document_Word_Writer (legacy PHPWord 0.6.x API).
 * Serializes in-memory documents to a minimal HTML5 file via IOFactory createWriter(..., 'HTML').
 */

require_once __DIR__ . '/IWriter.php';
require_once __DIR__ . '/../Section/Footer/PreserveText.php';

class Document_Word_Writer_Writer_HTML implements Document_Word_Writer_Writer_IWriter
{
    /** @var Document_Word|Document_Word_Writer|null */
    private $_document;

    public function __construct($PHPWord = null)
    {
        $this->_document = $PHPWord;
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
        if ($this->_document !== null) {
            $props = $this->_document->getProperties();
            if ($props !== null && method_exists($props, 'getTitle')) {
                $title = (string) $props->getTitle();
            }
        }

        $body = $this->_writeBody();
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
    private function _writeBody()
    {
        if ($this->_document === null) {
            return '';
        }
        $html = '';
        foreach ($this->_document->getSections() as $section) {
            $html .= $this->_writeElementList($section->getElements());
        }
        return $html;
    }

    /**
     * @param array $elements
     * @return string
     */
    private function _writeElementList($elements)
    {
        $html = '';
        foreach ($elements as $element) {
            $html .= $this->_writeElement($element);
        }
        return $html;
    }

    /**
     * @param mixed $element
     * @return string
     */
    private function _writeElement($element)
    {
        if ($element instanceof Document_Word_Writer_Section_Text || $element instanceof Document_Word_Section_Text) {
            return $this->_writeParagraphFromText($element);
        }
        if ($element instanceof Document_Word_Writer_Section_TextRun || $element instanceof Document_Word_Section_TextRun) {
            return $this->_writeTextRunBlock($element);
        }
        if ($element instanceof Document_Word_Writer_Section_Link || $element instanceof Document_Word_Section_Link) {
            return $this->_writeLinkBlock($element);
        }
        if ($element instanceof Document_Word_Writer_Section_Title || $element instanceof Document_Word_Section_Title) {
            return $this->_writeTitle($element);
        }
        if ($element instanceof Document_Word_Writer_Section_TextBreak || $element instanceof Document_Word_Section_TextBreak) {
            return "<br>\n";
        }
        if ($element instanceof Document_Word_Writer_Section_PageBreak || $element instanceof Document_Word_Section_PageBreak) {
            return '<div style="page-break-before:always"></div>' . "\n";
        }
        if ($element instanceof Document_Word_Writer_Section_Table || $element instanceof Document_Word_Section_Table) {
            return $this->_writeTable($element);
        }
        if ($element instanceof Document_Word_Writer_Section_ListItem || $element instanceof Document_Word_Section_ListItem) {
            return $this->_writeListItem($element);
        }
        if ($element instanceof Document_Word_Writer_Section_Image || $element instanceof Document_Word_Section_Image
            || $element instanceof Document_Word_Writer_Section_MemoryImage || $element instanceof Document_Word_Section_MemoryImage) {
            return $this->_writeImageBlock($element);
        }
        if ($element instanceof Document_Word_Writer_Section_Object || $element instanceof Document_Word_Section_Object) {
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
    private function _writeTable($table)
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
                $html .= $this->_writeElementList($cell->getElements());
                $html .= "</td>\n";
            }
            $html .= "</tr>\n";
        }
        $html .= "</table>\n";
        return $html;
    }

    /**
     * @param $item
     * @return string
     */
    private function _writeListItem($item)
    {
        $textObj = $item->getTextObject();
        $inner = $this->_writeTextRunContentFromText($textObj);
        $depth = (int) $item->getDepth();
        $margin = 1.5 * (1 + max(0, $depth));
        return '<ul style="margin:0.3em 0;padding-left:' . $margin . 'em"><li>' . $inner . "</li></ul>\n";
    }

    /**
     * @param $text
     * @return string
     */
    private function _writeTextRunContentFromText($text)
    {
        require_once __DIR__ . '/../Shared/String.php';
        $raw = Document_Word_Shared_String::ControlCharacterPHP2OOXML($text->getText());
        $inner = $this->_escapeHtml($raw);
        return $this->_wrapWithFontStyle($inner, $text->getFontStyle());
    }

    /**
     * @param $text
     * @return string
     */
    private function _writeParagraphFromText($text)
    {
        $pOpen = $this->_paragraphOpenTag($text->getParagraphStyle());
        return $pOpen . $this->_writeTextRunContentFromText($text) . "</p>\n";
    }

    /**
     * @param mixed $styleParagraph
     * @return string opening tag
     */
    private function _paragraphOpenTag($styleParagraph)
    {
        $attr = $this->_paragraphStyleAttr($styleParagraph);
        if ($attr === '') {
            return '<p>';
        }
        return '<p style="' . $attr . '">';
    }

    /**
     * @param mixed $styleParagraph
     * @return string CSS for style attribute
     */
    private function _paragraphStyleAttr($styleParagraph)
    {
        if (!($styleParagraph instanceof Document_Word_Writer_Style_Paragraph)) {
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
    private function _writeTextRunBlock($textrun)
    {
        $html = $this->_paragraphOpenTag($textrun->getParagraphStyle());
        foreach ($textrun->getElements() as $el) {
            $html .= $this->_writeInlineElement($el);
        }
        return $html . "</p>\n";
    }

    /**
     * @param mixed $el
     * @return string
     */
    private function _writeInlineElement($el)
    {
        if ($el instanceof Document_Word_Writer_Section_Text || $el instanceof Document_Word_Section_Text) {
            return $this->_writeTextRunContentFromText($el);
        }
        if ($el instanceof Document_Word_Writer_Section_Link || $el instanceof Document_Word_Section_Link) {
            return $this->_writeLinkInline($el);
        }
        if ($el instanceof Document_Word_Writer_Section_Image || $el instanceof Document_Word_Section_Image
            || $el instanceof Document_Word_Writer_Section_MemoryImage || $el instanceof Document_Word_Section_MemoryImage) {
            return $this->_writeImageInline($el);
        }
        if ($el instanceof Document_Word_Writer_Section_TextBreak || $el instanceof Document_Word_Section_TextBreak) {
            return '<br>';
        }
        if ($el instanceof Document_Word_Writer_Section_PageBreak || $el instanceof Document_Word_Section_PageBreak) {
            return '<span style="page-break-before:always"></span>';
        }
        if ($el instanceof Document_Word_Writer_Section_Footer_PreserveText || $el instanceof Document_Word_Section_Footer_PreserveText) {
            return $this->_writePreserveTextInline($el);
        }

        return '';
    }

    /**
     * @param $pt
     * @return string
     */
    private function _writePreserveTextInline($pt)
    {
        $t = $pt->getText();
        require_once __DIR__ . '/../Shared/String.php';
        if (!is_array($t)) {
            $raw = Document_Word_Shared_String::ControlCharacterPHP2OOXML((string) $t);
            return $this->_wrapWithFontStyle($this->_escapeHtml($raw), $pt->getFontStyle());
        }
        $chunk = '';
        foreach ($t as $part) {
            $raw = Document_Word_Shared_String::ControlCharacterPHP2OOXML((string) $part);
            $chunk .= $this->_wrapWithFontStyle($this->_escapeHtml($raw), $pt->getFontStyle());
        }
        return $chunk;
    }

    /**
     * @param $link
     * @return string
     */
    private function _writeLinkBlock($link)
    {
        return $this->_paragraphOpenTag($link->getParagraphStyle()) . $this->_writeLinkInline($link) . "</p>\n";
    }

    /**
     * @param $link
     * @return string
     */
    private function _writeLinkInline($link)
    {
        $href = $this->_escapeHtml($link->getLinkSrc());
        $label = $link->getLinkName();
        if ($label === null || $label === '') {
            $label = $link->getLinkSrc();
        }
        require_once __DIR__ . '/../Shared/String.php';
        $raw = Document_Word_Shared_String::ControlCharacterPHP2OOXML((string) $label);
        $inner = $this->_wrapWithFontStyle($this->_escapeHtml($raw), $link->getFontStyle());
        return '<a href="' . $href . '">' . $inner . '</a>';
    }

    /**
     * @param $title
     * @return string
     */
    private function _writeTitle($title)
    {
        $level = $title->getDepth();
        if ($level < 1) {
            $level = 1;
        }
        if ($level > 6) {
            $level = 6;
        }
        $t = $this->_escapeHtml($title->getText());
        $h = 'h' . $level;
        $id = $title->getAnchor();
        if ($id !== null && $id !== '') {
            return '<' . $h . ' id="h-' . (int) $id . '">' . $t . '</' . $h . ">\n";
        }
        return '<' . $h . '>' . $t . '</' . $h . ">\n";
    }

    /**
     * @param Document_Word_Writer_Section_Image|$img
     * @return string
     */
    private function _writeImageBlock($img)
    {
        return '<p>' . $this->_writeImageInline($img) . "</p>\n";
    }

    /**
     * @param Document_Word_Writer_Section_Image|$img
     * @return string
     */
    private function _writeImageInline($img)
    {
        $src = $img->getSource();
        if ($src === null || $src === '') {
            return '';
        }
        $esc = $this->_escapeHtml($src);
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
    private function _wrapWithFontStyle($escaped, $styleFont)
    {
        if ($styleFont instanceof Document_Word_Writer_Style_Font) {
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
                && $f->getUnderline() !== Document_Word_Writer_Style_Font::UNDERLINE_NONE) {
                $inner = '<u>' . $inner . '</u>';
            }
            if ($f->getStrikethrough()) {
                $inner = '<s>' . $inner . '</s>';
            }
            $spanStyles = array();
            $name = $f->getName();
            if ($name !== null && $name !== '' && $name !== 'Arial') {
                $spanStyles[] = 'font-family:' . $this->_escapeHtml($name);
            }
            $size = $f->getSize();
            if ($size !== null && is_numeric($size)) {
                $spanStyles[] = 'font-size:' . (((float) $size) / 2) . 'pt';
            }
            $color = $f->getColor();
            if ($color !== null && $color !== '' && strtolower($color) !== '000000') {
                $hex = strlen($color) === 6 ? '#' . $color : $color;
                $spanStyles[] = 'color:' . $this->_escapeHtml($hex);
            }
            if (count($spanStyles) > 0) {
                $inner = '<span style="' . implode(';', $spanStyles) . '">' . $inner . '</span>';
            }
            return $inner;
        }
        if (is_string($styleFont) && $styleFont !== '') {
            return '<span class="' . $this->_escapeHtml($styleFont) . '">' . $escaped . '</span>';
        }

        return $escaped;
    }

    /**
     * @param string $s
     * @return string
     */
    private function _escapeHtml($s)
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

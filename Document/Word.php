<?php
require_once __DIR__ . '/Word/Writer.php';

/**
 * Generic document type: same behaviour as {@see Document_Word_Writer} (sections, properties,
 * template, writers) using implementations under Document/Word/Writer/.
 *
 * Style registration uses {@see Document_Word_Style} (Document/Word/Style.php). Output writers
 * under Writer/ still read the legacy style registry until those are migrated.
 *
 * @category Document_Word
 */
class Document_Word extends Document_Word_Writer
{
    public function addParagraphStyle($styleName, $styles)
    {
        require_once __DIR__ . '/Word/Style.php';
        Document_Word_Style::addParagraphStyle($styleName, $styles);
    }

    public function addFontStyle($styleName, $styleFont, $styleParagraph = null)
    {
        require_once __DIR__ . '/Word/Style.php';
        Document_Word_Style::addFontStyle($styleName, $styleFont, $styleParagraph);
    }

    public function addTableStyle($styleName, $styleTable, $styleFirstRow = null)
    {
        require_once __DIR__ . '/Word/Style.php';
        Document_Word_Style::addTableStyle($styleName, $styleTable, $styleFirstRow);
    }

    public function addTitleStyle($titleCount, $styleFont, $styleParagraph = null)
    {
        require_once __DIR__ . '/Word/Style.php';
        Document_Word_Style::addTitleStyle($titleCount, $styleFont, $styleParagraph);
    }

    public function addLinkStyle($styleName, $styles)
    {
        require_once __DIR__ . '/Word/Style.php';
        Document_Word_Style::addLinkStyle($styleName, $styles);
    }
}

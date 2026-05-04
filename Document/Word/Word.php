<?php
/**
 * Generic document facade over the legacy {@see Document_Word_Writer} model.
 *
 * Phase 1: thin wrapper only; DOCX loading will attach via {@see Document_Word_IOFactory::load()} later.
 */

/**
 * @category Document_Word
 */
class Document_Word
{
    /** @var Document_Word_Writer */
    private $_writer;

    public function __construct()
    {
        require_once __DIR__ . '/Writer.php';
        $this->_writer = new Document_Word_Writer();
    }

    /**
     * Underlying legacy writer instance (for writers and existing integrations).
     *
     * @return Document_Word_Writer
     */
    public function getWriterDocument()
    {
        return $this->_writer;
    }

    public function getProperties()
    {
        return $this->_writer->getProperties();
    }

    public function setProperties(Document_Word_Writer_DocumentProperties $value)
    {
        return $this->_writer->setProperties($value);
    }

    public function createSection($settings = null)
    {
        return $this->_writer->createSection($settings);
    }

    public function getDefaultFontName()
    {
        return $this->_writer->getDefaultFontName();
    }

    public function setDefaultFontName($pValue)
    {
        $this->_writer->setDefaultFontName($pValue);
    }

    public function getDefaultFontSize()
    {
        return $this->_writer->getDefaultFontSize();
    }

    public function setDefaultFontSize($pValue)
    {
        $this->_writer->setDefaultFontSize($pValue);
    }

    public function addParagraphStyle($styleName, $styles)
    {
        $this->_writer->addParagraphStyle($styleName, $styles);
    }

    public function addFontStyle($styleName, $styleFont, $styleParagraph = null)
    {
        $this->_writer->addFontStyle($styleName, $styleFont, $styleParagraph);
    }

    public function addTableStyle($styleName, $styleTable, $styleFirstRow = null)
    {
        $this->_writer->addTableStyle($styleName, $styleTable, $styleFirstRow);
    }

    public function addTitleStyle($titleCount, $styleFont, $styleParagraph = null)
    {
        $this->_writer->addTitleStyle($titleCount, $styleFont, $styleParagraph);
    }

    public function addLinkStyle($styleName, $styles)
    {
        $this->_writer->addLinkStyle($styleName, $styles);
    }

    public function getSections()
    {
        return $this->_writer->getSections();
    }

    public function loadTemplate($strFilename)
    {
        return $this->_writer->loadTemplate($strFilename);
    }
}

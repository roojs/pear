<?php
/**
 * In-memory Word document model (generic namespace).
 *
 * Legacy duplicate: {@see Document_Word_Writer} in Document/Word/Writer.php (same logic; remove after migration).
 *
 * @category Document_Word
 */
class Document_Word
{
    /** @var Document_Word_DocumentProperties */
    private $_properties;

    /** @var string */
    private $_defaultFontName;

    /** @var int */
    private $_defaultFontSize;

    /** @var array */
    private $_sectionCollection = array();

    public function __construct()
    {
        require_once __DIR__ . '/Word/Core/DocumentProperties.php';
        $this->_properties = new Document_Word_DocumentProperties();
        $this->_defaultFontName = 'Arial';
        $this->_defaultFontSize = 20;
    }

    /** @return Document_Word_DocumentProperties */
    public function getProperties()
    {
        return $this->_properties;
    }

    /** @return Document_Word */
    public function setProperties(Document_Word_DocumentProperties $value)
    {
        $this->_properties = $value;

        return $this;
    }

    /** @return Document_Word_Section */
    public function createSection($settings = null)
    {
        require_once __DIR__ . '/Word/Core/Section.php';
        $sectionCount = $this->_countSections() + 1;
        $section = new Document_Word_Section($sectionCount, $settings);
        $this->_sectionCollection[] = $section;

        return $section;
    }

    public function getDefaultFontName()
    {
        return $this->_defaultFontName;
    }

    public function setDefaultFontName($pValue)
    {
        $this->_defaultFontName = $pValue;
    }

    public function getDefaultFontSize()
    {
        return $this->_defaultFontSize;
    }

    public function setDefaultFontSize($pValue)
    {
        $pValue = $pValue * 2;
        $this->_defaultFontSize = $pValue;
    }

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

    /** @return Document_Word_Section[] */
    public function getSections()
    {
        return $this->_sectionCollection;
    }

    private function _countSections()
    {
        return count($this->_sectionCollection);
    }

    /** @return Document_Word_Template */
    public function loadTemplate($strFilename)
    {
        require_once __DIR__ . '/Word/Core/Template.php';
        if (file_exists($strFilename)) {
            return new Document_Word_Template($strFilename);
        }
        trigger_error('Template file '.$strFilename.' not found.', E_USER_ERROR);
    }
}

<?php
/**
 * In-memory Word document (generic namespace).
 *
 * Pass a file path (e.g. `.docx`) to load from disk; omit for an empty document.
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

    /**
     * @param string|null $filePath Path to a document file (e.g. OOXML `.docx`), or null for an empty document
     * @throws Exception
     */
    public function __construct($filePath = null)
    {
        require_once __DIR__ . '/Word/DocumentProperties.php';
        $this->_properties = new Document_Word_DocumentProperties();
        $this->_defaultFontName = 'Arial';
        $this->_defaultFontSize = 20;

        if ($filePath === null || $filePath === '') {
            return;
        }
        if (!is_string($filePath)) {
            throw new Exception('Document_Word::__construct() expects file path as string or null.');
        }

        require_once __DIR__ . '/Word/Document_Word_Reader.php';
        $loaded = (new Document_Word_Reader())->load($filePath);
        $this->_properties = $loaded->getProperties();
        $this->_defaultFontName = $loaded->getDefaultFontName();
        $this->_defaultFontSize = $loaded->getDefaultFontSize();
        $this->_sectionCollection = $loaded->getSections();
    }

    /** @return Document_Word_DocumentProperties */
    public function getProperties()
    {
        return $this->_properties;
    }

    /**
     * @param Document_Word_DocumentProperties $value
     * @return Document_Word
     */
    public function setProperties($value)
    {
        $this->_properties = $value;

        return $this;
    }

    /** @return Document_Word_Section */
    public function createSection($settings = null)
    {
        require_once __DIR__ . '/Word/Section.php';
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
        require_once __DIR__ . '/Word/Template.php';
        if (file_exists($strFilename)) {
            return new Document_Word_Template($strFilename);
        }
        trigger_error('Template file '.$strFilename.' not found.', E_ERROR);
    }
}

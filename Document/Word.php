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
    private $properties;

    /** @var string */
    private $defaultFontName;

    /** @var int */
    private $defaultFontSize;

    /** @var array */
    private $sectionCollection = array();

    /**
     * @param string|null $filePath Path to a document file (e.g. OOXML `.docx`), or null for an empty document
     * @throws Exception
     */
    public function __construct($filePath = null)
    {
        require_once __DIR__ . '/Word/DocumentProperties.php';
        $this->properties = new Document_Word_DocumentProperties();
        $this->defaultFontName = 'Arial';
        $this->defaultFontSize = 20;

        if ($filePath === null || $filePath === '') {
            return;
        }
        if (!is_string($filePath)) {
            throw new Exception('Document_Word::__construct() expects file path as string or null.');
        }

        require_once __DIR__ . '/Word/Reader.php';
        (new Document_Word_Reader())->load($filePath, $this);
    }

    /** @return Document_Word_DocumentProperties */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param Document_Word_DocumentProperties $value
     * @return Document_Word
     */
    public function setProperties($value)
    {
        $this->properties = $value;

        return $this;
    }

    /** @return Document_Word_Section */
    public function createSection($settings = null)
    {
        require_once __DIR__ . '/Word/Section.php';
        $sectionCount = $this->countSections() + 1;
        $section = new Document_Word_Section($sectionCount, $settings);
        $this->sectionCollection[] = $section;

        return $section;
    }

    public function getDefaultFontName()
    {
        return $this->defaultFontName;
    }

    public function setDefaultFontName($pValue)
    {
        $this->defaultFontName = $pValue;
    }

    public function getDefaultFontSize()
    {
        return $this->defaultFontSize;
    }

    public function setDefaultFontSize($pValue)
    {
        $pValue = $pValue * 2;
        $this->defaultFontSize = $pValue;
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
        return $this->sectionCollection;
    }

    /**
     * Remove all sections (used by {@see Document_Word_Reader::load} before repopulating from a file).
     *
     * @return Document_Word
     */
    public function clearSections()
    {
        $this->sectionCollection = array();

        return $this;
    }

    /**
     * Export this document using a named writer (e.g. Word2007, HTML).
     *
     * @param string $format
     * @param string $filename
     * @return Document_Word
     * @throws Exception
     */
    public function exportAs($format, $filename)
    {
        require_once __DIR__ . '/Word/IOFactory.php';
        Document_Word_IOFactory::createWriter($this, $format)->save($filename);

        return $this;
    }

    private function countSections()
    {
        return count($this->sectionCollection);
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

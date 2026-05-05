<?php
/**
 * Generic IO entry points for {@see Document_Word}.
 *
 * Writer discovery matches the former {@see Document_Word_Writer_IOFactory} (search locations,
 * IWriter loop) so custom {@see Document_Word_IOFactory::addSearchLocation} registrations keep working
 * after {@see Document_Word_Writer_IOFactory} is removed.
 *
 * Example (HTML export via writers under Document/Word/Writer/Writer):
 *
 * ```php
 * require_once 'Document/Word.php';
 * require_once 'Document/Word/IOFactory.php';
 *
 * $doc = new Document_Word();
 * $doc->createSection()->addText('Hello');
 * Document_Word_IOFactory::createWriter($doc, 'HTML')->save('/path/out.html');
 *
 * Load OOXML (.docx) and export to HTML:
 *
 * ```php
 * $word = Document_Word_IOFactory::load('/path/in.docx');
 * Document_Word_IOFactory::createWriter($word, 'HTML')->save('/path/out.html');
 * ```
 *
 * @category Document_Word
 */
class Document_Word_IOFactory
{
    /**
     * Search locations (same shape as legacy Document_Word_Writer_IOFactory).
     *
     * @var array
     */
    private static $_searchLocations = array(
        array('type' => 'IWriter', 'path' => 'Document/Word/Writer/Writer/{0}.php', 'class' => 'Document_Word_Writer_Writer_{0}')
    );

    /**
     * Autoresolve classes (carried over for parity with legacy IOFactory; unused by createWriter).
     *
     * @var array
     */
    private static $_autoResolveClasses = array(
        'Serialized'
    );

    private function __construct()
    {
    }

    /**
     * @return array
     */
    public static function getSearchLocations()
    {
        return self::$_searchLocations;
    }

    /**
     * @param array $value
     * @throws Exception
     */
    public static function setSearchLocations($value)
    {
        if (is_array($value)) {
            self::$_searchLocations = $value;
            return;
        }
        throw new Exception('Invalid parameter passed.');
    }

    /**
     * @param string $type       Example: IWriter
     * @param string $location   Example: Document/Word/Writer/Writer/{0}.php
     * @param string $classname  Example: Document_Word_Writer_Writer_{0}
     */
    public static function addSearchLocation($type = '', $location = '', $classname = '')
    {
        self::$_searchLocations[] = array('type' => $type, 'path' => $location, 'class' => $classname);
    }

    /**
     * @param string $readerType e.g. Docx
     * @return Document_Word_Reader_Docx
     * @throws Exception
     */
    public static function createReader($readerType = 'Docx')
    {
        require_once __DIR__ . '/Reader/' . $readerType . '.php';
        $class = 'Document_Word_Reader_' . $readerType;
        if (!class_exists($class, false)) {
            throw new Exception('Document_Word_IOFactory::createReader() unknown reader type: ' . $readerType);
        }

        return new $class();
    }

    /**
     * Load a document from disk into {@see Document_Word}.
     *
     * @param string $path Path to source file (e.g. .docx)
     * @return Document_Word
     * @throws Exception
     */
    public static function load($path)
    {
        return self::createReader()->load($path);
    }

    /**
     * @param Document_Word $documentWord
     * @param string $writerType e.g. Word2007, HTML
     * @return Document_Word_Writer_Writer_IWriter
     * @throws Exception
     */
    public static function createWriter(Document_Word $documentWord, $writerType = '')
    {
        require_once __DIR__ . '/Writer/Writer/' . $writerType . '.php';
        $searchType = 'IWriter';

        foreach (self::$_searchLocations as $searchLocation) {
            if ($searchLocation['type'] == $searchType) {
                $className = str_replace('{0}', $writerType, $searchLocation['class']);
                $instance = new $className($documentWord);
                if (!is_null($instance)) {
                    return $instance;
                }
            }
        }

        throw new Exception("No $searchType found for type $writerType");
    }
}

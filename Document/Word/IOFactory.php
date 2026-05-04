<?php
/**
 * Generic IO entry points for {@see Document_Word}.
 *
 * Example (HTML export):
 *
 * ```php
 * require_once 'Document/Word.php';
 * require_once 'Document/Word/IOFactory.php';
 *
 * $doc = new Document_Word();
 * $doc->createSection()->addText('Hello');
 * Document_Word_IOFactory::createWriter($doc, 'HTML')->save('/path/out.html');
 * ```
 *
 * Legacy duplicate: {@see Document_Word_Writer_IOFactory} (remove after migration).
 *
 * @category Document_Word
 */
class Document_Word_IOFactory
{
    private function __construct()
    {
    }

    /**
     * Load a document from disk into {@see Document_Word}.
     *
     * @param string $path Path to source file (e.g. .docx)
     * @return Document_Word
     * @throws Exception Not implemented yet
     */
    public static function load($path)
    {
        throw new Exception('Document_Word_IOFactory::load() is not implemented yet.');
    }

    /**
     * @param Document_Word $documentWord
     * @param string $writerType e.g. Word2007, HTML
     * @return Document_Word_Output_IWriter
     * @throws Exception
     */
    public static function createWriter(Document_Word $documentWord, $writerType = '')
    {
        $file = __DIR__ . '/Core/Output/' . $writerType . '.php';
        if (!is_readable($file)) {
            throw new Exception('No IWriter found for type '.$writerType);
        }
        require_once $file;
        $className = 'Document_Word_Output_'.$writerType;

        return new $className($documentWord);
    }
}

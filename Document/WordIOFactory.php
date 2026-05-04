<?php
/**
 * Generic IO entry points for {@see Document_Word}.
 *
 * Example (HTML export via legacy writers):
 *
 * ```php
 * require_once 'Document/Word.php';
 * require_once 'Document/WordIOFactory.php';
 *
 * $doc = new Document_Word();
 * $doc->createSection()->addText('Hello');
 * Document_Word_IOFactory::createWriter($doc, 'HTML')->save('/path/out.html');
 * ```
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
     * @throws Exception Not implemented in Phase 1
     */
    public static function load($path)
    {
        throw new Exception('Document_Word_IOFactory::load() is not implemented yet.');
    }

    /**
     * Create a writer for the wrapped legacy {@see Document_Word_Writer}.
     *
     * @param Document_Word $documentWord
     * @param string $writerType e.g. Word2007, HTML
     * @return Document_Word_Writer_Writer_IWriter
     */
    public static function createWriter(Document_Word $documentWord, $writerType = '')
    {
        require_once __DIR__ . '/Word/Writer/IOFactory.php';

        return Document_Word_Writer_IOFactory::createWriter($documentWord->getWriterDocument(), $writerType);
    }
}

<?php
/**
 * Autoloads generic {@see Document_Word} classes under Document/Word/.
 *
 * Does not load {@see Document_Word_Writer} hierarchy (legacy PEAR paths / require_once elsewhere).
 *
 * @category Document_Word
 */
class Document_Word_Autoloader
{
    public static function register()
    {
        return spl_autoload_register(array('Document_Word_Autoloader', 'load'));
    }

    /**
     * @param string $className
     * @return bool
     */
    public static function load($className)
    {
        if (strpos($className, 'Document_Word_Writer') === 0) {
            return false;
        }
        if (strpos($className, 'Document_Word') !== 0) {
            return false;
        }
        if (class_exists($className, false)) {
            return false;
        }

        $map = array(
            'Document_Word' => __DIR__ . '/Word.php',
            'Document_Word_IOFactory' => __DIR__ . '/IOFactory.php',
            'Document_Word_Autoloader' => __DIR__ . '/Autoloader.php',
        );
        if (!isset($map[$className])) {
            return false;
        }
        $file = $map[$className];
        if (!is_readable($file)) {
            return false;
        }
        require_once $file;

        return true;
    }
}

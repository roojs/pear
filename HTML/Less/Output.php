<?php

/**
 * Parser output
 *
 * @package Less
 * @subpackage output
 */
class HTML_Less_Output {

    /**
     * Output holder
     *
     * @var string
     */
    protected $strs = array();

    /**
     * Adds a chunk to the stack
     *
     * @param string $chunk The chunk to output
     * @param HTML_Less_FileInfo $fileInfo The file information
     * @param integer $index The index
     * @param mixed $mapLines
     */
    public function add($chunk, $fileInfo = null, $index = 0, $mapLines = null) {
        $this->strs[] = $chunk;
    }

    /**
     * Is the output empty?
     *
     * @return boolean
     */
    public function isEmpty() {
        return count($this->strs) === 0;
    }

    /**
     * Converts the output to string
     *
     * @return string
     */
    public function toString() {
        return implode('', $this->strs);
    }

}

<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Parser output
 *
 * @package ILess
 * @subpackage util
 */
class ILess_Output implements Countable
{
    /**
     * Output holder
     *
     * @var array
     */
    protected $output = array();

    /**
     * Adds a chunk to the stack
     *
     * @param string $chunk The chunk to output
     * @param ILess_FileInfo $fileInfo The file information
     * @param integer $index The index
     * @param mixed $mapLines
     * @return ILess_Output
     */
    public function add($chunk, ILess_FileInfo $fileInfo = null, $index = 0, $mapLines = null)
    {
        $this->output[] = array(
            // commented out to save some memory
            $chunk //, $fileInfo, $index, $mapLines
        );

        return $this;
    }

    /**
     * Is the output empty?
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return count($this->output) === 0;
    }

    /**
     * Returns number of lines in the output
     *
     * @return integer
     */
    public function count()
    {
        return count($this->output);
    }

    /**
     * Converts the output to string
     *
     * @return string
     */
    public function toString()
    {
        $result = array();
        foreach ($this->output as $o) {
            $result[] = $o[0];
        }

        return join('', $result);
    }

    /**
     * Returns the output
     *
     * @return array
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Converts the output to string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

}

<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Output;

use ILess\FileInfo;

/**
 * Standard output.
 */
class StandardOutput implements OutputInterface
{
    /**
     * Output holder.
     *
     * @var array
     */
    protected $output = [];

    /**
     * Adds a chunk to the stack.
     *
     * @param string $chunk The chunk to output
     * @param FileInfo $fileInfo The file information
     * @param int $index The index
     * @param mixed $mapLines
     *
     * @return StandardOutput
     */
    public function add($chunk, FileInfo $fileInfo = null, $index = 0, $mapLines = null)
    {
        $this->output[] = [
            $chunk,
        ];

        return $this;
    }

    /**
     * Is the output empty?
     *
     * @return bool
     */
    public function isEmpty()
    {
        return count($this->output) === 0;
    }

    /**
     * Converts the output to string.
     *
     * @return string
     */
    public function toString()
    {
        $result = [];
        foreach ($this->getOutput() as $o) {
            $result[] = $o[0];
        }

        return implode('', $result);
    }

    /**
     * Returns the output.
     *
     * @return array
     */
    protected function getOutput()
    {
        return $this->output;
    }
}

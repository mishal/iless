<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Output;

use ILess\FileInfo;
use ILess\SourceMap\Generator;

/**
 * Output with source map.
 */
class MappedOutput extends StandardOutput
{
    /**
     * The source map generator.
     *
     * @var Generator
     */
    protected $generator;

    /**
     * Current line.
     *
     * @var int
     */
    protected $lineNumber = 0;

    /**
     * Current column.
     *
     * @var int
     */
    protected $column = 0;

    /**
     * Array of contents map (file and its content).
     *
     * @var array
     */
    protected $contentsMap = [];

    /**
     * Constructor.
     *
     * @param array $contentsMap Array of filename to contents map
     * @param Generator $generator
     */
    public function __construct(array $contentsMap, Generator $generator)
    {
        $this->contentsMap = $contentsMap;
        $this->generator = $generator;
    }

    /**
     * Adds a chunk to the stack.
     *
     * @param string $chunk
     * @param string $fileInfo
     * @param int $index
     * @param mixed $mapLines
     *
     * @return StandardOutput
     */
    public function add($chunk, FileInfo $fileInfo = null, $index = 0, $mapLines = null)
    {
        // nothing to do
        if ($chunk == '') {
            return $this;
        }

        $lines = explode("\n", $chunk);
        $columns = end($lines);

        if ($fileInfo) {
            $inputSource = substr($this->contentsMap[$fileInfo->importedFile->getPath()], 0, $index);
            $sourceLines = explode("\n", $inputSource);
            $sourceColumns = end($sourceLines);
            $sourceLinesCount = count($sourceLines);
            $sourceColumnsLength = strlen($sourceColumns);

            if (!$mapLines) {
                $this->generator->addMapping(
                    $this->lineNumber + 1, $this->column, // generated
                    $sourceLinesCount, $sourceColumnsLength, // original
                    $fileInfo->filename
                );
            } else {
                for ($i = 0, $count = count($lines); $i < $count; ++$i) {
                    $this->generator->addMapping(
                        $this->lineNumber + $i + 1, $i === 0 ? $this->column : 0, // generated
                        $sourceLinesCount + $i, $i === 0 ? $sourceColumnsLength : 0, // original
                        $fileInfo->filename
                    );
                }
            }
        }

        if (count($lines) === 1) {
            $this->column += strlen($columns);
        } else {
            $this->lineNumber += count($lines) - 1;
            $this->column = strlen($columns);
        }

        // add only chunk
        return parent::add($chunk);
    }

    /**
     * Returns the generator.
     *
     * @return Generator
     */
    public function getGenerator()
    {
        return $this->generator;
    }

    /**
     * Sets the generator.
     *
     * @param Generator $generator
     *
     * @return MappedOutput
     */
    public function setGenerator(Generator $generator)
    {
        $this->generator = $generator;

        return $this;
    }
}

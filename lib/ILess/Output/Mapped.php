<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Parser output with source map
 *
 * @package ILess
 * @subpackage Output
 */
class ILess_Output_Mapped extends ILess_Output
{
    /**
     * The source map generator
     *
     * @var ILess_SourceMap_Generator
     */
    protected $generator;

    /**
     * Current line
     *
     * @var integer
     */
    protected $lineNumber = 0;

    /**
     * Current column
     *
     * @var integer
     */
    protected $column = 0;

    /**
     * Array of contents map (file and its content)
     *
     * @var array
     */
    protected $contentsMap = array();

    /**
     * Constructor
     *
     * @param array $contentsMap Array of filename to contents map
     * @param ILess_SourceMap_Generator $generator
     */
    public function __construct(array $contentsMap, ILess_SourceMap_Generator $generator)
    {
        $this->contentsMap = $contentsMap;
        $this->generator = $generator;
    }

    /**
     * Adds a chunk to the stack
     *
     * @param string $chunk
     * @param string $fileInfo
     * @param integer $index
     * @param mixed $mapLines
     * @return ILess_Output
     */
    public function add($chunk, ILess_FileInfo $fileInfo = null, $index = 0, $mapLines = null)
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
                for ($i = 0, $count = count($lines); $i < $count; $i++) {
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
     * Returns the generator
     *
     * @return ILess_SourceMap_Generator
     */
    public function getGenerator()
    {
        return $this->generator;
    }

    /**
     * Sets the generator
     *
     * @param ILess_SourceMap_Generator $generator
     * @return ILess_Output_Mapped
     */
    public function setGenerator(ILess_SourceMap_Generator $generator)
    {
        $this->generator = $generator;

        return $this;
    }

}

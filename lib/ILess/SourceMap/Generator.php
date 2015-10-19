<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\SourceMap;

use ILess\Configurable;
use ILess\Context;
use ILess\Exception\IOException;
use ILess\Node\RulesetNode;
use InvalidArgumentException;
use ILess\Output\MappedOutput;
use ILess\Util;

/**
 * Source map generator.
 */
class Generator extends Configurable
{
    /**
     * What version of source map does the generator generate?
     */
    const VERSION = 3;

    /**
     * Array of default options.
     *
     * @var array
     */
    protected $defaultOptions = [
        // an optional source root, useful for relocating source files
        // on a server or removing repeated values in the 'sources' entry.
        // This value is prepended to the individual entries in the 'source' field.
        'sourceRoot' => '',
        // an optional name of the generated code that this source map is associated with.
        'filename' => null,
        // url of the map
        'url' => null,
        // absolute path to a file to write the map to
        'write_to' => null,
        // output source contents?
        'source_contents' => false,
        // base path for filename normalization
        'base_path' => '',
        // encode inline map using base64?
        'inline_encode_base64' => true,
    ];

    /**
     * The base64 VLQ encoder.
     *
     * @var Base64VLQ
     */
    protected $encoder;

    /**
     * Array of mappings.
     *
     * @var array
     */
    protected $mappings = [];

    /**
     * The root node.
     *
     * @var RulesetNode
     */
    protected $root;

    /**
     * Array of contents map.
     *
     * @var array
     */
    protected $contentsMap = [];

    /**
     * File to content map.
     *
     * @var array
     */
    protected $sources = [];

    /**
     * Constructor.
     *
     * @param RulesetNode $root The root node
     * @param array $contentsMap Array of file contents map
     * @param array $options Array of options
     * @param Base64VLQ $encoder The encoder
     */
    public function __construct(
        RulesetNode $root,
        array $contentsMap,
        $options = [],
        Base64VLQ $encoder = null
    ) {
        $this->root = $root;
        $this->contentsMap = $contentsMap;
        $this->encoder = $encoder ? $encoder : new Base64VLQ();
        parent::__construct($options);
    }

    /**
     * Setups the generator.
     */
    protected function setup()
    {
        // fix windows paths
        if ($basePath = $this->getOption('base_path')) {
            $this->setOption('base_path', Util::normalizePath($basePath));
        }
    }

    /**
     * Generates the CSS.
     *
     * @param Context $context
     *
     * @return string
     */
    public function generateCSS(Context $context)
    {
        $output = new MappedOutput($this->contentsMap, $this);

        // catch the output
        $this->root->generateCSS($context, $output);

        // prepare sources
        foreach ($this->contentsMap as $filename => $contents) {
            // match md5 hash in square brackets _[#HASH#]_
            // see ILess\Parser\Core::parseString()
            if (preg_match('/(\[__[0-9a-f]{32}__\])+$/', $filename)) {
                $filename = substr($filename, 0, -38);
            }

            $this->sources[$this->normalizeFilename($filename)] = $contents;
        }

        $sourceMapUrl = null;
        if ($url = $this->getOption('url')) {
            $sourceMapUrl = $url;
        } elseif ($path = $this->getOption('filename')) {
            $sourceMapUrl = $this->normalizeFilename($path);
            // naming conventions, make it foobar.css.map
            if (!preg_match('/\.map$/', $sourceMapUrl)) {
                $sourceMapUrl = sprintf('%s.map', $sourceMapUrl);
            }
        }

        $sourceMapContent = $this->generateJson();

        // write map to a file
        if ($file = $this->getOption('write_to')) {
            $this->saveMap($file, $sourceMapContent);
        } // inline the map
        else {
            $sourceMap = 'data:application/json;';
            if ($this->getOption('inline_encode_base64')) {
                $sourceMap .= 'base64,';
                $sourceMapContent = base64_encode($sourceMapContent);
            } else {
                $sourceMapContent = Util::encodeURIComponent($sourceMapContent);
            }
            $sourceMapUrl = $sourceMap . $sourceMapContent;
        }

        if ($sourceMapUrl) {
            $output->add(sprintf('/*# sourceMappingURL=%s */', $sourceMapUrl));
        }

        return $output->toString();
    }

    /**
     * Saves the source map to a file.
     *
     * @param string $file The absolute path to a file
     * @param string $content The content to write
     *
     * @throws IOException If the file could not be saved
     * @throws InvalidArgumentException If the directory to write the map to does not exist or is not writable
     *
     * @return true
     */
    protected function saveMap($file, $content)
    {
        $dir = dirname($file);

        if (!is_dir($dir) || !is_writable($dir)) {
            throw new InvalidArgumentException(sprintf('The directory "%s" does not exist or is not writable. Cannot save the source map.',
                $dir));
        }

        if (@file_put_contents($file, $content, LOCK_EX) === false) {
            throw new IOException(sprintf('Cannot save the source map to "%s".', $file));
        }

        return true;
    }

    /**
     * Normalizes the filename.
     *
     * @param string $filename
     *
     * @return string
     */
    protected function normalizeFilename($filename)
    {
        $filename = Util::normalizePath($filename);
        if (($basePath = $this->getOption('base_path'))
            && ($pos = strpos($filename, $basePath)) !== false
        ) {
            $filename = substr($filename, $pos + strlen($basePath));

            if (strpos($filename, '/') === 0) {
                $filename = substr($filename, 1);
            }
        }

        return $this->getOption('root_path') . $filename;
    }

    /**
     * Adds a mapping.
     *
     * @param int $generatedLine The line number in generated file
     * @param int $generatedColumn The column number in generated file
     * @param int $originalLine The line number in original file
     * @param int $originalColumn The column number in original file
     * @param string $sourceFile The original source file
     *
     * @return Generator
     */
    public function addMapping(
        $generatedLine,
        $generatedColumn,
        $originalLine,
        $originalColumn,
        $sourceFile
    ) {
        $this->mappings[] = [
            'generated_line' => $generatedLine,
            'generated_column' => $generatedColumn,
            'original_line' => $originalLine,
            'original_column' => $originalColumn,
            'source_file' => $sourceFile,
        ];

        return $this;
    }

    /**
     * Clear the mappings.
     *
     * @return Generator
     */
    public function clear()
    {
        $this->mappings = [];

        return $this;
    }

    /**
     * Sets the encoder.
     *
     * @param Base64VLQ $encoder
     *
     * @return Generator
     */
    public function setEncoder(Base64VLQ $encoder)
    {
        $this->encoder = $encoder;

        return $this;
    }

    /**
     * Returns the encoder.
     *
     * @return Base64VLQ
     */
    public function getEncoder()
    {
        return $this->encoder;
    }

    /**
     * Generates the JSON source map.
     *
     * @return string
     *
     * @see https://docs.google.com/document/d/1U1RGAehQwRypUTovF1KRlpiOFze0b-_2gc6fAH0KY0k/edit#
     */
    protected function generateJson()
    {
        $sourceMap = [
            // File version (always the first entry in the object) and must be a positive integer.
            'version' => self::VERSION,
            // An optional name of the generated code that this source map is associated with.
            'file' => $this->getOption('filename'),
            // An optional source root, useful for relocating source files on a server or removing repeated values in the 'sources' entry.  This value is prepended to the individual entries in the 'source' field.
            'sourceRoot' => $this->getOption('sourceRoot'),
            // A list of original sources used by the 'mappings' entry.
            'sources' => array_keys($this->sources),
        ];

        // A list of symbol names used by the 'mappings' entry.
        $sourceMap['names'] = [];
        // A string with the encoded mapping data.
        $sourceMap['mappings'] = $this->generateMappings();

        if ($this->getOption('source_contents')) {
            // An optional list of source content, useful when the 'source' can't be hosted.
            // The contents are listed in the same order as the sources above.
            // 'null' may be used if some original sources should be retrieved by name.
            $sourceMap['sourcesContent'] = $this->getSourcesContent();
        }

        // less.js compatibility fixes
        if (count($sourceMap['sources']) && !($sourceMap['sourceRoot'])) {
            unset($sourceMap['sourceRoot']);
        }

        return json_encode($sourceMap);
    }

    /**
     * Returns the sources contents.
     *
     * @return array|null
     */
    protected function getSourcesContent()
    {
        if (empty($this->sources)) {
            return;
        }

        // FIXME: we should output only those which were used
        return array_values($this->sources);
    }

    /**
     * Generates the mappings string.
     *
     * @return string
     */
    public function generateMappings()
    {
        if (!count($this->mappings)) {
            return '';
        }

        // group mappings by generated line number.
        $groupedMap = $groupedMapEncoded = [];
        foreach ($this->mappings as $m) {
            $groupedMap[$m['generated_line']][] = $m;
        }
        ksort($groupedMap);

        $lastGeneratedLine = $lastOriginalIndex = $lastOriginalLine = $lastOriginalColumn = 0;

        foreach ($groupedMap as $lineNumber => $line_map) {
            while (++$lastGeneratedLine < $lineNumber) {
                $groupedMapEncoded[] = ';';
            }

            $lineMapEncoded = [];
            $lastGeneratedColumn = 0;

            foreach ($line_map as $m) {
                $mapEncoded = $this->encoder->encode($m['generated_column'] - $lastGeneratedColumn);
                $lastGeneratedColumn = $m['generated_column'];

                // find the index
                if ($m['source_file'] &&
                    ($index = $this->findFileIndex($this->normalizeFilename($m['source_file']))) !== false
                ) {
                    $mapEncoded .= $this->encoder->encode($index - $lastOriginalIndex);
                    $lastOriginalIndex = $index;

                    // lines are stored 0-based in SourceMap spec version 3
                    $mapEncoded .= $this->encoder->encode($m['original_line'] - 1 - $lastOriginalLine);
                    $lastOriginalLine = $m['original_line'] - 1;

                    $mapEncoded .= $this->encoder->encode($m['original_column'] - $lastOriginalColumn);
                    $lastOriginalColumn = $m['original_column'];
                }

                $lineMapEncoded[] = $mapEncoded;
            }

            $groupedMapEncoded[] = implode(',', $lineMapEncoded) . ';';
        }

        return rtrim(implode($groupedMapEncoded), ';');
    }

    /**
     * Finds the index for the filename.
     *
     * @param string $filename
     *
     * @return int|false
     */
    protected function findFileIndex($filename)
    {
        return array_search($filename, array_keys($this->sources));
    }
}

<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess;

use ILess\Node\RulesetNode;
use InvalidArgumentException;

/**
 * Context.
 */
class Context
{
    /**
     * Array of frames.
     *
     * @var array
     */
    public $frames = [];

    /**
     * Custom variables.
     *
     * @var RulesetNode
     */
    public $customVariables;

    /**
     * Compress the output?
     *
     * @var bool
     */
    public $compress = false;

    /**
     * Can shorten colors?
     *
     * @var bool
     */
    public $canShortenColors = true;

    /**
     * The math precision.
     *
     * @var int
     */
    public $numPrecision = null;

    /**
     * @var bool
     */
    public $strictImports = false;

    /**
     * Adjust URL's to be relative?
     *
     * @var bool
     */
    public $relativeUrls = false;

    /**
     * Root path.
     *
     * @var string
     */
    public $rootPath;

    /**
     * @var array
     */
    public $mediaBlocks = [];

    /**
     * @var array
     */
    public $mediaPath = [];

    /**
     * @var array
     */
    public $paths = [];

    /**
     * Math is required to be in parenthesis like: <pre>(1+1)</pre>.
     *
     * @var bool
     */
    public $strictMath = false;

    /**
     * Validate the units used?
     *
     * @var bool
     */
    public $strictUnits = true;

    /**
     * Process imports?
     *
     * @var bool
     */
    public $processImports = true;

    /**
     * IE8 data-uri compatibility.
     *
     * @var bool
     */
    public $ieCompat = true;

    /**
     * Dump line numbers?
     *
     * @var false|string all|comment|mediaquery
     */
    public $dumpLineNumbers = false;

    /**
     * Tab level.
     *
     * @var int
     */
    public $tabLevel = 0;

    /**
     * First selector flag.
     *
     * @var bool
     */
    public $firstSelector = false;

    /**
     * Last rule flag.
     *
     * @var bool
     */
    public $lastRule = false;

    /**
     * Important flag (currently not implemented in less.js).
     *
     * @var bool
     */
    public $isImportant = false;

    /**
     * Selectors.
     *
     * @var array
     */
    public $selectors = [];

    /**
     * Parens stack.
     *
     * @var array
     */
    protected $parensStack = [];

    /**
     * Current file information. For error reporting,
     * importing and making urls relative etc.
     *
     * @var FileInfo
     */
    public $currentFileInfo;

    /**
     * What is this for?
     *
     * @var bool
     */
    public $importMultiple = false;

    /**
     * Source map flag.
     *
     * @var bool
     */
    public $sourceMap = false;

    /**
     * Array of source map options.
     *
     * @var array
     */
    public $sourceMapOptions = [];

    /**
     * Filename to contents of all parsed the files.
     *
     * @var array
     */
    public $contentsMap = [];

    /**
     * The function registry.
     *
     * @var FunctionRegistry
     */
    protected $functionRegistry;

    /**
     * Used to bubble up !important statements.
     *
     * @var array
     */
    public $importantScope = [];

    /**
     * Whether to add args into url tokens.
     *
     * @var string
     */
    public $urlArgs = '';

    /**
     * Constructor.
     *
     * @param FunctionRegistry $registry The function registry
     * @param array $options
     *
     * @throws InvalidArgumentException If passed options are invalid
     */
    public function __construct(array $options = [], FunctionRegistry $registry = null)
    {
        if ($registry) {
            $this->setFunctionRegistry($registry);
        }

        $invalid = [];

        // underscored property names
        $properties = array_keys(get_class_vars(__CLASS__));
        $properties = array_combine($properties, $properties);
        $properties = array_change_key_case(array_flip(preg_replace('/((?<=[a-z]|\d)[A-Z]|(?<!^)[A-Z](?=[a-z]))/',
            '_\\1', $properties)));

        foreach ($options as $option => $value) {
            if (isset($properties[$option])) {
                $option = $properties[$option];
            } elseif (!property_exists($this, $option)) {
                $invalid[] = $option;
                continue;
            }

            switch ($option) {
                case 'dumpLineNumbers':
                    if (!in_array($value, [
                        true,
                        DebugInfo::FORMAT_ALL,
                        DebugInfo::FORMAT_COMMENT,
                        DebugInfo::FORMAT_MEDIA_QUERY,
                    ], true)
                    ) {
                        // FIXME: report possible values?
                        $invalid[] = $option;
                    }
                    break;

                case 'strictUnits':
                case 'compress':
                case 'importMultiple':
                case 'ieCompat':
                    $value = (boolean) $value;
                    break;
            }

            $this->$option = $value;
        }

        if (count($invalid)) {
            throw new InvalidArgumentException(sprintf('Invalid options "%s" given.', implode(', ', $invalid)));
        }
    }

    /**
     * Returns the function registry.
     *
     * @return FunctionRegistry
     */
    public function getFunctionRegistry()
    {
        return $this->functionRegistry;
    }

    /**
     * Returns the contents map.
     *
     * @return array
     */
    public function getContentsMap()
    {
        return $this->contentsMap;
    }

    /**
     * Sets file contents to the map.
     *
     * @param string $filePath
     * @param string $content
     *
     * @return Context
     */
    public function setFileContent($filePath, $content)
    {
        $this->contentsMap[$filePath] = $content;

        return $this;
    }

    /**
     * Sets the function registry, also links the registry with this environment instance.
     *
     * @param FunctionRegistry $registry
     *
     * @return Context
     */
    public function setFunctionRegistry(FunctionRegistry $registry)
    {
        $this->functionRegistry = $registry;
        // provide access to The context, which is need to access generateCSS()
        $this->functionRegistry->setEnvironment($this);

        return $this;
    }

    /**
     * Sets current file.
     *
     * @param string $file The path to a file
     */
    public function setCurrentFile($file)
    {
        $file = Util::normalizePath($file);
        $dirname = preg_replace('/[^\/\\\\]*$/', '', $file);

        $this->currentFileInfo = new FileInfo([
            'currentDirectory' => $dirname,
            'filename' => $file,
            'rootPath' => $this->currentFileInfo && $this->currentFileInfo->rootPath ?
                $this->currentFileInfo->rootPath : $this->rootPath,
            'entryPath' => $dirname,
        ]);
    }

    /**
     * Creates a copy of The context.
     *
     * @param Context $context
     * @param array $frames
     *
     * @return Context
     */
    public static function createCopy(Context $context, array $frames = [])
    {
        // what to copy?
        $copyProperties = [
            // options
            'compress', // whether to compress
            'canShortenColors', // can shorten colors?
            'ieCompat', // whether to enforce IE compatibility (IE8 data-uri)
            'strictMath', // whether math has to be within parenthesis
            'strictUnits', // whether units need to evaluate correctly
            'sourceMap', // whether to output a source map
            'sourceMapOptions', // options for source map generator
            'importMultiple', // whether we are currently importing multiple copies,
            'relativeUrls', // adjust relative urls?,
            'rootPath', // root path
            'dumpLineNumbers', // dump line numbers?
            'contentsMap', // filename to contents of all the files
            'urlArgs', // whether to add args into url tokens
            'customVariables', // variables from the php API
            'currentFileInfo', // current file information object
            'importantScope', // current file information object
        ];

        $target = new self([], $context->getFunctionRegistry());
        self::copyFromOriginal($context, $target, $copyProperties);

        $target->frames = $frames;

        return $target;
    }

    /**
     * @param Context $context
     * @param array $frames
     *
     * @return Context
     */
    public static function createCopyForCompilation(Context $context, array $frames = [])
    {
        $copyProperties = [
            'compress',        // whether to compress
            'ieCompat',        // whether to enforce IE compatibility (IE8 data-uri)
            'strictMath',      // whether math has to be within parenthesis
            'strictUnits',     // whether units need to evaluate correctly
            'sourceMap',       // whether to output a source map
            'importMultiple',  // whether we are currently importing multiple copies
            'dumpLineNumbers', // dump line numbers?
            'urlArgs',         // whether to add args into url tokens
            'importantScope',  // used to bubble up !important statements
            'customVariables', // variables from the php API
        ];

        $target = new self([], $context->getFunctionRegistry());
        self::copyFromOriginal($context, $target, $copyProperties);

        $target->frames = $frames;

        return $target;
    }

    private static function copyFromOriginal(Context $original, Context $targetEnv, $copyProperties)
    {
        foreach ($copyProperties as $property) {
            if (property_exists($original, $property)) {
                $targetEnv->$property = $original->$property;
            }
        }
    }

    /**
     * Is math on?
     *
     * @return bool
     */
    public function isMathOn()
    {
        return $this->strictMath ? ($this->parensStack && count($this->parensStack)) : true;
    }

    /**
     * @return Context
     */
    public function inParenthesis()
    {
        $this->parensStack[] = true;

        return $this;
    }

    /**
     * @return Context
     */
    public function outOfParenthesis()
    {
        array_pop($this->parensStack);

        return $this;
    }

    /**
     * @param Node $frame
     */
    public function unshiftFrame($frame)
    {
        array_unshift($this->frames, $frame);
    }

    /**
     * @return array
     */
    public function shiftFrame()
    {
        return array_shift($this->frames);
    }

    /**
     * @param array $frame
     */
    public function addFrame($frame)
    {
        $this->frames[] = $frame;
    }

    /**
     * @param array $frames
     */
    public function addFrames(array $frames)
    {
        $this->frames = array_merge($this->frames, $frames);
    }
}

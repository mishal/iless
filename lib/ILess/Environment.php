<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Environment
 *
 * @package ILess
 * @subpackage environment
 */
class ILess_Environment
{
    /**
     * Array of frames
     *
     * @var array
     */
    public $frames = array();

    /**
     * Custom variables
     *
     * @var ILess_Node_Ruleset
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
     * @var boolean
     */
    public $canShortenColors = true;

    /**
     * The math precision
     *
     * @var integer
     */
    public $precision = 16;

    /**
     * Debug flag
     *
     * @var boolean
     */
    public $debug = false;

    /**
     * @var boolean
     */
    public $strictImports = false;

    /**
     * Adjust URL's to be relative?
     *
     * @var boolean
     */
    public $relativeUrls = false;

    /**
     * Root path
     *
     * @var string
     */
    public $rootPath;

    /**
     * @var array
     */
    public $mediaBlocks = array();

    /**
     * @var array
     */
    public $mediaPath = array();

    /**
     * @var array
     */
    public $paths = array();

    /**
     * Math is required to be in parenthesis like: <pre>(1+1)</pre>
     *
     * @var boolean
     */
    public $strictMath = false;

    /**
     * Validate the units used?
     *
     * @var boolean
     */
    public $strictUnits = false;

    /**
     * Process imports?
     *
     * @var boolean
     */
    public $processImports = true;

    /**
     * IE8 data-uri compatibility
     *
     * @var boolean
     */
    public $ieCompat = true;

    /**
     * Dump line numbers?
     *
     * @var false|all|comment|mediaquery
     */
    public $dumpLineNumbers = false;

    /**
     * Tab level
     *
     * @var integer
     */
    public $tabLevel = 0;

    /**
     * First selector flag
     *
     * @var boolean
     */
    public $firstSelector = false;

    /**
     * Last rule flag
     *
     * @var boolean
     */
    public $lastRule = false;

    /**
     * Important flag (currently not implemented in less.js)
     *
     * @var boolean
     */
    public $isImportant = false;

    /**
     * Selectors
     *
     * @var array
     */
    public $selectors = array();

    /**
     * Parens stack
     *
     * @var array
     */
    protected $parensStack = array();

    /**
     * Current file information. For error reporting,
     * importing and making urls relative etc.
     *
     * @var ILess_FileInfo
     */
    public $currentFileInfo;

    /**
     * What is this for?
     *
     * @var boolean
     */
    public $importMultiple = false;

    /**
     * Source map flag
     *
     * @var boolean
     */
    public $sourceMap = false;

    /**
     * Array of source map options
     *
     * @var array
     */
    public $sourceMapOptions = array();

    /**
     * Filename to contents of all parsed the files
     *
     * @var array
     */
    public $contentsMap = array();

    /**
     * The function registry
     *
     * @var ILess_FunctionRegistry
     */
    protected $functionRegistry;

    /**
     * Constructor
     *
     * @param ILess_FunctionRegistry $registry The function registry
     * @param array $options
     * @throws InvalidArgumentException If passed options are invalid
     */
    public function __construct(array $options = array(), ILess_FunctionRegistry $registry = null)
    {
        if ($registry) {
            $this->setFunctionRegistry($registry);
        }

        $invalid = array();

        // underscored property names
        $properties = array_keys(get_class_vars(__CLASS__));
        $properties = array_combine($properties, $properties);
        $properties = array_change_key_case(array_flip(preg_replace('/((?<=[a-z]|\d)[A-Z]|(?<!^)[A-Z](?=[a-z]))/', '_\\1', $properties)));

        foreach ($options as $option => $value) {
            if (isset($properties[$option])) {
                $option = $properties[$option];
            } elseif (!property_exists($this, $option)) {
                $invalid[] = $option;
                continue;
            }

            switch ($option) {
                case 'dumpLineNumbers':
                    if( !in_array($value, array(
                        true,
                        ILess_DebugInfo::FORMAT_ALL,
                        ILess_DebugInfo::FORMAT_COMMENT,
                        ILess_DebugInfo::FORMAT_MEDIA_QUERY), true)) {
                        // FIXME: report possible values?
                        $invalid[] = $option;
                    }
                    break;

                case 'strictUnits':
                case 'compress':
                case 'importMultiple':
                case 'ieCompat':
                    $value = (boolean)$value;
                    break;
            }

            $this->$option = $value;
        }

        if (count($invalid)) {
            throw new InvalidArgumentException(sprintf('Invalid options "%s" given.', join(', ', $invalid)));
        }

    }

    /**
     * Returns the function registry
     *
     * @return ILess_FunctionRegistry
     */
    public function getFunctionRegistry()
    {
        return $this->functionRegistry;
    }

    /**
     * Returns the contents map
     *
     * @return array
     */
    public function getContentsMap()
    {
        return $this->contentsMap;
    }

    /**
     * Sets file contents to the map
     *
     * @param string $filePath
     * @param string $content
     * @return ILess_Environment
     */
    public function setFileContent($filePath, $content)
    {
        $this->contentsMap[$filePath] = $content;

        return $this;
    }

    /**
     * Sets the function registry, also links the registry with this environment instance
     *
     * @param ILess_FunctionRegistry $registry
     * @return ILess_Environment
     */
    public function setFunctionRegistry(ILess_FunctionRegistry $registry)
    {
        $this->functionRegistry = $registry;
        // provide access to the environment, which is need to access generateCSS()
        $this->functionRegistry->setEnvironment($this);

        return $this;
    }

    /**
     * Sets current file
     *
     * @param string $file The path to a file
     */
    public function setCurrentFile($file)
    {
        $file = ILess_Util::normalizePath($file);
        $dirname = preg_replace('/[^\/\\\\]*$/', '', $file);

        $this->currentFileInfo = new ILess_FileInfo(array(
            'currentDirectory' => $dirname,
            'filename' => $file,
            'rootPath' => $this->currentFileInfo && $this->currentFileInfo->rootPath ?
                          $this->currentFileInfo->rootPath : $this->rootPath,
            'entryPath' => $dirname
        ));
    }

    /**
     * Creates a copy of the environment
     *
     * @param ILess_Environment $env
     * @param array $frames
     * @return ILess_Environment
     */
    public static function createCopy(ILess_Environment $env, array $frames = array())
    {
        // what to copy?
        $copyProperties = array(
            // options
            'compress', // whether to compress
            'canShortenColors', // can shorten colors?
            'precision', // math precision
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
            // properties
            'customVariables', // variables from the php API
            'currentFileInfo', // current file information object
        );

        $copy = new ILess_Environment(array(), $env->getFunctionRegistry());
        foreach ($copyProperties as $property) {
            if (property_exists($env, $property)) {
                $copy->$property = $env->$property;
            }
        }
        $copy->frames = $frames;

        return $copy;
    }

    /**
     * Is math on?
     *
     * @return boolean
     */
    public function isMathOn()
    {
        return $this->strictMath ? ($this->parensStack && count($this->parensStack)) : true;
    }

    /**
     * @return ILess_Environment
     */
    public function inParenthesis()
    {
        $this->parensStack[] = true;

        return $this;
    }

    /**
     * @return ILess_Environment
     */
    public function outOfParenthesis()
    {
        array_pop($this->parensStack);

        return $this;
    }

    /**
     * @param ILess_Node $frame
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

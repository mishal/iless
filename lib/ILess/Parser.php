<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Parser
 *
 * @package ILess
 * @subpackage parser
 */
class ILess_Parser extends ILess_Parser_Core
{
    /**
     * Parser version
     *
     */
    const VERSION = '1.6.3';

    /**
     * Array of output filters
     *
     * @var array
     */
    protected $outputFilters = array();

    /**
     * The cache
     *
     * @var ILess_CacheInterface
     */
    protected $cache;

    /**
     * Constructor
     *
     * @param array $options Array of options
     * @param ILess_CacheInterface The cache implementation
     * @param ILess_Importer $importer The importer
     * @param array $outputFilters Array of output filters
     */
    public function __construct(array $options = array(),
                                ILess_CacheInterface $cache = null,
                                array $importers = array(), array $outputFilters = array())
    {
        $importDirs = array();
        // we have an import dirs option
        if (isset($options['import_dirs'])) {
            $importDirs = (array)$options['import_dirs'];
            unset($options['import_dirs']);
        }

        $env = new ILess_Environment($options, new ILess_FunctionRegistry());

        if (!$importers) {
            $importers = array(
                new ILess_Importer_FileSystem($importDirs)
            );
        }

        // output filters
        foreach ($outputFilters as $filter) {
            $this->appendFilter($filter);
        }

        $this->cache = $cache ? $cache : new ILess_Cache_None();

        parent::__construct($env, new ILess_Importer($env, $importers, $this->cache));
    }

    /**
     * Converts the ruleset to CSS. Applies the output filters to the output.
     *
     * @param ILess_Node_Ruleset $ruleset
     * @param array $variables
     * @return string The generated CSS code
     */
    protected function toCSS(ILess_Node_Ruleset $ruleset, array $variables)
    {
        // the cache key consists of:
        // 1) parsed rules
        // 2) assigned variables via the API
        // 3) environment options
        $cacheKey = $this->generateCacheKey(serialize($this->rules).serialize($variables).serialize(
            array(
                // FIXME: verify
                $this->env->compress, $this->env->sourceMap,
                $this->env->sourceMapOptions, $this->env->relativeUrls,
                $this->env->precision, $this->env->debug, $this->env->dumpLineNumbers,
                $this->env->canShortenColors, $this->env->ieCompat, $this->env->strictMath,
                $this->env->strictUnits
            )
        ));

        $rebuild = true;
        if ($this->cache->has($cacheKey)) {
            $rebuild = false;
            list($css, $importedFiles) = $this->cache->get($cacheKey);
            // we need to check if the file has been modified
            foreach($importedFiles as $importedFileArray) {
                list($lastModifiedBefore, $path, $currentFileInfo) = $importedFileArray;
                $lastModified = $this->importer->getLastModified($path, $currentFileInfo);
                if ($lastModifiedBefore != $lastModified) {
                    $rebuild = true;
                    // no need to continue, we will rebuild the CSS
                    break;
                }
            }
        }

        if ($rebuild) {
            $css = parent::toCSS($ruleset, $variables);
            // what have been imported?
            $importedFiles = array();
            foreach($this->importer->getImportedFiles() as $importedFile) {
                // we need to save original path, last modified timestamp and currentFileInfo object
                // see ILess_Importer::setImportedFile()
                $importedFiles[] = array($importedFile[0]->getLastModified(), $importedFile[1], $importedFile[2]);
            }
            $this->cache->set($cacheKey, array($css, $importedFiles));
        }

        return $this->filter($css);
    }

    /**
     * Filters the output
     *
     * @param string $output
     * @return string
     */
    protected function filter($output)
    {
        foreach ($this->outputFilters as $filter) {
            /* @var $filter ILess_OutputFilterInterface */
            $output = $filter->filter($output);
        }

        return $output;
    }

    /**
     * Appends an output filter
     *
     * @param ILess_OutputFilterInterface $filter
     * @return ILess_Parser
     */
    public function appendFilter(ILess_OutputFilterInterface $filter)
    {
        $this->outputFilters[] = $filter;

        return $this;
    }

    /**
     * Prepends a filter
     *
     * @param ILess_OutputFilterInterface $filter
     * @return ILess_Parser
     */
    public function prependFilter(ILess_OutputFilterInterface $filter)
    {
        array_unshift($this->outputFilters, $filter);

        return $this;
    }

    /**
     * Adds a function to the functions.
     *
     * @param string $functionName
     * @param callable $callable
     * @param string|array $aliases The array of aliases
     * @return ILess_FunctionRegistry
     * @throws InvalidArgumentException If the callable is not valid
     */
    public function addFunction($functionName, $callable, $aliases = array())
    {
        return $this->getEnvironment()->getFunctionRegistry()->addFunction($functionName, $callable, $aliases);
    }

    /**
     * Returns the cache
     *
     * @return ILess_CacheInterface
     */
    public function getCache()
    {
        return $this->cache;
    }

}

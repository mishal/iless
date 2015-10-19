<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess;

use ILess\Cache\CacheInterface;
use ILess\Cache\NoCache;
use ILess\Importer\FileSystemImporter;
use ILess\Node\RulesetNode;
use ILess\OutputFilter\OutputFilterInterface;
use ILess\Parser\Core;
use InvalidArgumentException;

/**
 * Parser.
 */
class Parser extends Core
{
    /**
     * Array of output filters.
     *
     * @var array
     */
    protected $outputFilters = [];

    /**
     * The cache.
     *
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Constructor.
     *
     * @param array $options Array of options
     * @param CacheInterface $cache The cache implementation
     * @param array $importers Array of importers
     * @param array $outputFilters Array of output filters - deprecated
     */
    public function __construct(
        array $options = [],
        CacheInterface $cache = null,
        array $importers = [],
        array $outputFilters = []
    ) {
        $importDirs = [];
        // we have an import dirs option
        if (isset($options['import_dirs'])) {
            $importDirs = (array) $options['import_dirs'];
            unset($options['import_dirs']);
        }

        $context = new Context($options, new FunctionRegistry());

        if (!$importers) {
            $importers = [
                new FileSystemImporter($importDirs),
            ];
        }

        // output filters
        foreach ($outputFilters as $filter) {
            $this->appendFilter($filter);
        }

        $this->cache = $cache ? $cache : new NoCache();
        $this->pluginManager = new PluginManager($this);

        parent::__construct($context,
            new Importer($context, $importers, $this->cache, $manager = new PluginManager($this)), $manager);
    }

    /**
     * Converts the ruleset to CSS. Applies the output filters to the output.
     *
     * @param RulesetNode $ruleset
     * @param array $variables
     *
     * @return string The generated CSS code
     */
    protected function toCSS(RulesetNode $ruleset, array $variables)
    {
        // the cache key consists of:
        // 1) parsed rules
        // 2) assigned variables via the API
        // 3) environment options
        $cacheKey = $this->generateCacheKey(
            serialize($this->rules) . serialize($variables) . serialize(
                [
                    $this->context->compress,
                    $this->context->sourceMap,
                    $this->context->sourceMapOptions,
                    $this->context->relativeUrls,
                    $this->context->numPrecision,
                    $this->context->dumpLineNumbers,
                    $this->context->canShortenColors,
                    $this->context->ieCompat,
                    $this->context->strictMath,
                    $this->context->strictUnits,
                    $this->context->urlArgs,
                    $this->context->dumpLineNumbers,
                    $this->context->strictImports,
                ]
            )
        );

        $rebuild = true;
        $css = null;
        if ($this->cache->has($cacheKey)) {
            $rebuild = false;
            list($css, $importedFiles) = $this->cache->get($cacheKey);
            // we need to check if the file has been modified
            foreach ($importedFiles as $importedFileArray) {
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
            $importedFiles = [];
            foreach ($this->importer->getImportedFiles() as $importedFile) {
                // we need to save original path, last modified timestamp and currentFileInfo object
                // see ILess\Importer::setImportedFile()
                $importedFiles[] = [$importedFile[0]->getLastModified(), $importedFile[1], $importedFile[2]];
            }
            $this->cache->set($cacheKey, [$css, $importedFiles]);
        }

        return $this->filter($css);
    }

    /**
     * Filters the output.
     *
     * @param string $output
     *
     * @return string
     *
     * @deprecated
     */
    protected function filter($output)
    {
        foreach ($this->outputFilters as $filter) {
            /* @var $filter OutputFilterInterface */
            $output = $filter->filter($output);
        }

        return $output;
    }

    /**
     * Appends an output filter.
     *
     * @param OutputFilterInterface $filter
     *
     * @return Parser
     *
     * @deprecated
     */
    public function appendFilter(OutputFilterInterface $filter)
    {
        $this->outputFilters[] = $filter;

        return $this;
    }

    /**
     * Prepends a filter.
     *
     * @param OutputFilterInterface $filter
     *
     * @return Parser
     *
     * @deprecated
     */
    public function prependFilter(OutputFilterInterface $filter)
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
     *
     * @return $this
     *
     * @throws InvalidArgumentException If the callable is not valid
     */
    public function addFunction($functionName, $callable, $aliases = [])
    {
        $this->getContext()->getFunctionRegistry()->addFunction($functionName, $callable, $aliases);

        return $this;
    }

    /**
     * Adds multiple functions at once.
     *
     * @param array $functions
     *
     * @return $this
     */
    public function addFunctions(array $functions)
    {
        $this->getContext()->getFunctionRegistry()->addFunctions($functions);

        return $this;
    }

    /**
     * Returns the cache.
     *
     * @return CacheInterface
     */
    public function getCache()
    {
        return $this->cache;
    }
}

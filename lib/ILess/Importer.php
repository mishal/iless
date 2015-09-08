<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess;

use ILess\Cache\CacheInterface;
use ILess\Exception\Exception;
use ILess\Exception\ParserException;
use ILess\Exception\ImportException;
use ILess\Importer\ImporterInterface;
use ILess\Node;
use ILess\Node\RulesetNode;
use ILess\Parser\Core;
use ILess\Util;

/**
 * Import
 *
 * @package ILess
 * @subpackage Import
 */
class Importer
{
    /**
     * The context
     *
     * @var Context
     */
    protected $context;

    /**
     * The cache
     *
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Array of importers
     *
     * @var array
     */
    protected $importers = array();

    /**
     * Array of imported files
     *
     * @var array
     */
    protected $importedFiles = array();

    /**
     * Constructor
     *
     * @param Context $context The context
     * @param array $importers Array of importers
     * @param CacheInterface $cache The cache
     */
    public function __construct(Context $context, array $importers, CacheInterface $cache)
    {
        $this->context = $context;
        $this->registerImporters($importers);
        $this->cache = $cache;
    }

    /**
     * Sets The context
     *
     * @param Context $context
     * @return Importer
     */
    public function setEnvironment(Context $context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Sets the cache
     *
     * @param CacheInterface $cache
     * @return Importer
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Returns the cache
     *
     * @return CacheInterface
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Returns the context
     *
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Imports the file
     *
     * @param string $path The path to import. Path will be searched by the importers
     * @param bool $tryAppendLessExtension Whether to try appending the less extension (if the path has no extension)
     * @param array $importOptions Import options
     * @param integer $index Current index
     * @return array
     * @throws ImportException If the $path could not be imported
     */
    public function import(
        $path,
        $tryAppendLessExtension = false,
        FileInfo $currentFileInfo,
        array $importOptions = array(),
        $index = 0
    ) {
        $cacheKey = $this->generateCacheKey($currentFileInfo->currentDirectory.$path);
        // do we have a file in the cache?
        if ($this->cache->has($cacheKey)) {
            // check the modified timestamp
            $file = $this->cache->get($cacheKey);
            // search
            foreach ($this->importers as $importer) {
                /* @var $file ImportedFile */
                /* @var $importer ImporterInterface */
                $lastModified = $importer->getLastModified($path, $currentFileInfo);
                if ($lastModified !== false && $lastModified == $file->getLastModified()) {
                    // the modification time is the same, take the one from cache
                    return $this->doImport($file, $path, $currentFileInfo, $importOptions, true);
                }
            }
        }

        $plugin = isset($importOptions['plugin']) && $importOptions['plugin'];

        if ($tryAppendLessExtension && !pathinfo($path, PATHINFO_EXTENSION)) {
            if ($plugin) {
                $path .= '.php';
            } else {
                $path .= '.less';
            }
        }

        foreach ($this->importers as $importer) {
            /* @var $importer ImporterInterface */
            $file = $importer->import($path, $currentFileInfo);

            // import is handled by the importer
            if ($file instanceof ImportedFile) {
                if ($plugin) {

                    // create dummy ruleset which will hold the functions
                    $ruleset = new RulesetNode(array(), array());
                    $ruleset->root = false;
                    $ruleset->functions[] = function (FunctionRegistry $registry) use ($file) {
                        $registry->loadPlugin($file->getPath());
                    };

                    $file->setRuleset($ruleset);

                    return array(
                        true,
                        $file,
                    );

                } else {
                    $result = $this->doImport($file, $path, $currentFileInfo, $importOptions);
                    /* @var $file ImportedFile */
                    list(, $file) = $result;
                    // save the cache
                    $this->cache->set($cacheKey, $file);

                    return $result;
                }
            }
        }

        throw new ImportException(sprintf("'%s' wasn't found.", $path), $index, $currentFileInfo);
    }

    /**
     * Does the import
     *
     * @param ImportedFile $file The imported file
     * @param string $path The original path
     * @param FileInfo $currentFileInfo Current file info
     * @param array $importOptions Import options
     * @param boolean $fromCache Is the imported file coming from cache?
     * @throws ParserException
     * @throws Exception
     * @return array
     */
    protected function doImport(
        ImportedFile $file,
        $path,
        FileInfo $currentFileInfo,
        array $importOptions = array(),
        $fromCache = false
    ) {
        $newEnv = Context::createCopyForCompilation($this->context, $this->context->frames);

        $newFileInfo = clone $currentFileInfo;

        if ($this->context->relativeUrls) {
            // Pass on an updated rootPath if path of imported file is relative and file
            // is in a (sub|sup) directory
            //
            // Examples:
            // - If path of imported file is 'module/nav/nav.less' and rootPath is 'less/',
            //   then rootPath should become 'less/module/nav/'
            // - If path of imported file is '../mixins.less' and rootPath is 'less/',
            //   then rootPath should become 'less/../'
            if (!Util::isPathAbsolute($path) && (($lastSlash = strrpos($path, '/')) !== false)) {
                $relativeSubDirectory = substr($path, 0, $lastSlash + 1);
                $newFileInfo->rootPath = $newFileInfo->rootPath.$relativeSubDirectory;
            }
        }

        // we need to clone here, to prevent modification of node current info object
        $newEnv->currentFileInfo = $newFileInfo;
        $newEnv->processImports = false;

        if ($currentFileInfo->reference
            || (isset($importOptions['reference']) && $importOptions['reference'])
        ) {
            $newEnv->currentFileInfo->reference = true;
        }

        $key = $file->getPath();
        $root = null;
        $alreadyImported = false;

        // check for already imported file
        if (isset($this->importedFiles[$key])) {
            $alreadyImported = true;
        } elseif (!$file->getRuleset()) {
            $parser = new Core($newEnv, $this);
            try {
                // we do not parse the root but load the file as is
                if (isset($importOptions['inline']) && $importOptions['inline']) {
                    $root = $file->getContent();
                } else {
                    // $root = new ILess\ILess\Node\RulesetNode(array(), $parser->parse($file->getContent()));
                    $root = $parser->parseFile($file, true);
                    $root->root = false;
                    $root->firstRoot = false;
                }

                $file->setRuleset($root);
                // we need to catch parse exceptions
            } catch (Exception $e) {
                // rethrow
                throw $e;
            } catch (\Exception $error) {
                $file->setError($error);
            }

            $this->setImportedFile($key, $file, $path, $currentFileInfo);
        } else {
            $this->setImportedFile($key, $file, $path, $currentFileInfo);
        }

        if ($fromCache) {
            $ruleset = $this->importedFiles[$key][0]->getRuleset();
            if ($ruleset instanceof Node) {
                // this is a workaround for reference and import one issues when taken cache
                $this->updateReferenceInCurrentFileInfo($ruleset, $newEnv->currentFileInfo->reference);
            }
        }

        return array(
            $alreadyImported,
            $this->importedFiles[$key][0],
        );
    }

    /**
     * Updates the currentFileInfo object to the $value
     *
     * @param Node $node The node to update
     * @param boolean $value The value
     */
    protected function updateReferenceInCurrentFileInfo(Node $node, $value)
    {
        if (isset($node->currentFileInfo)) {
            $node->currentFileInfo->reference = $value;
        }
        if (Node::propertyExists($node, 'rules')) {
            foreach ($node->rules as $rule) {
                $this->updateReferenceInCurrentFileInfo($rule, $value);
            }
        }
    }

    /**
     * Returns the last modification time of the file
     *
     * @param string $path
     * @param FileInfo $currentFileInfo
     * @return integer
     * @throws Exception If there was an error
     */
    public function getLastModified($path, FileInfo $currentFileInfo)
    {
        foreach ($this->importers as $importer) {
            /* @var $importer ImporterInterface */
            $result = $importer->getLastModified($path, $currentFileInfo);
            if ($result !== null) {
                return $result;
            }
        }

        throw new ImportException(sprintf('Error getting last modification time of the file "%s".', $path));
    }

    /**
     * Registers an importer
     *
     * @param ImporterInterface $importer
     * @param string $name The importer name (only for developer reference)
     * @param boolean $prepend Prepend before current importers?
     * @return Importer
     */
    public function registerImporter(ImporterInterface $importer, $name = null, $prepend = false)
    {
        // FIXME: what about more than one importer with the same class?
        $name = !is_null($name) ? $name : get_class($importer);

        if ($prepend) {
            // array unshift with preservation of keys
            $importers = array_reverse($this->importers, true);
            $importers[$name] = $importer;
            $this->importers = array_reverse($importers, true);
        } else {
            $this->importers[$name] = $importer;
        }

        return $this;
    }

    /**
     * Returns the importer with given name
     *
     * @param string $name
     * @return ImporterInterface
     */
    public function getImporter($name)
    {
        return isset($this->importers[$name]) ? $this->importers[$name] : null;
    }

    /**
     * Returns registered importers
     *
     * @return array
     */
    public function getImporters()
    {
        return $this->importers;
    }

    /**
     * Registers an array of importers
     *
     * @param array $importers
     * @return Importer
     */
    public function registerImporters(array $importers)
    {
        foreach ($importers as $name => $importer) {
            $this->registerImporter($importer, is_numeric($name) ? null : $name);
        }

        return $this;
    }

    /**
     * Clears all importers
     *
     * @return Importer
     */
    public function clearImporters()
    {
        $this->importers = array();

        return $this;
    }

    /**
     * Returns a list of imported files
     *
     * @return array
     */
    public function getImportedFiles()
    {
        return $this->importedFiles;
    }

    /**
     * Sets the imported file
     *
     * @param string $pathAbsolute The absolute path
     * @param ImportedFile $file The imported file
     * @param string $path The original path to import
     * @param FileInfo $currentFileInfo
     * @return Importer
     */
    public function setImportedFile($pathAbsolute, ImportedFile $file, $path, FileInfo $currentFileInfo)
    {
        $this->importedFiles[$pathAbsolute] = array($file, $path, $currentFileInfo);
        // save for source map generation
        $this->context->setFileContent($pathAbsolute, $file->getContent());

        return $this;
    }

    /**
     * Returns the imported file
     *
     * @param string $absolutePath The absolute path of the file
     * @param mixed $default The default when no file with given $path is already imported
     * @return array Array(ImportedFile, $originalPath, CurrentFileInfo)
     */
    public function getImportedFile($absolutePath, $default = null)
    {
        return isset($this->importedFiles[$absolutePath]) ? $this->importedFiles[$absolutePath] : $default;
    }

    /**
     * Generates unique cache key for given $filename
     *
     * @param string $filename
     * @return string
     */
    protected function generateCacheKey($filename)
    {
        return Util::generateCacheKey($filename);
    }

}

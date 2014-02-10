<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Import
 *
 * @package ILess
 * @subpackage import
 */
class ILess_Importer
{
    /**
     * The environment
     *
     * @var ILess_Environment
     */
    protected $env;

    /**
     * The cache
     *
     * @var ILess_CacheInterface
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
     * @param ILess_Environment $env The environment
     * @param array $importers Array of importers
     * @param ILess_CacheInterface $cache The cache
     */
    public function __construct(ILess_Environment $env, array $importers, ILess_CacheInterface $cache)
    {
        $this->env = $env;
        $this->registerImporters($importers);
        $this->cache = $cache;
    }

    /**
     * Sets the environment
     *
     * @param ILess_Environment $env
     * @return ILess_Importer
     */
    public function setEnvironment(ILess_Environment $env)
    {
        $this->env = $env;

        return $this;
    }

    /**
     * Sets the cache
     *
     * @param ILess_CacheInterface $cache
     * @return ILess_Importer
     */
    public function setCache(ILess_CacheInterface $cache)
    {
        $this->cache = $cache;

        return $this;
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

    /**
     * Returns the environment
     *
     * @return ILess_Environment
     */
    public function getEnvironment()
    {
        return $this->env;
    }

    /**
     * Imports the file
     *
     * @param string $path The path to import. Path will be searched by the importers
     * @param ILess_FileInfo $currentFileInfo Current file information
     * @param array $importOptions Import options
     * @param integer $index Current index
     * @return array
     * @throws ILess_Exception_Import If the $path could not be imported
     */
    public function import($path, ILess_FileInfo $currentFileInfo, array $importOptions = array(), $index = 0)
    {
        $cacheKey = $this->generateCacheKey($currentFileInfo->currentDirectory . $path);
        // do we have a file in the cache?
        if ($this->cache->has($cacheKey)) {
            // check the modified timestamp
            $file = $this->cache->get($cacheKey);
            // search
            foreach ($this->importers as $importer) {
                /* @var $file ILess_ImportedFile */
                $lastModified = $importer->getLastModified($path, $currentFileInfo);
                if ($lastModified !== false && $lastModified == $file->getLastModified()) {
                    // the modification time is the same, take the one from cache
                    return $this->doImport($file, $path, $currentFileInfo, $importOptions, true);
                }
            }
        }

        foreach ($this->importers as $importer) {
            /* @var $importer ILess_ImporterInterface */
            $file = $importer->import($path, $currentFileInfo);
            // import is handled by the importer
            if ($file instanceof ILess_ImportedFile) {
                $result = $this->doImport($file, $path, $currentFileInfo, $importOptions);
                /* @var $file ILess_ImportedFile */
                list(, $file) = $result;
                // save the cache
                $this->cache->set($cacheKey, $file);

                return $result;
            }
        }

        throw new ILess_Exception_Import(sprintf("'%s' wasn't found.", $path), $index, $currentFileInfo);
    }

    /**
     * Does the import
     *
     * @param ILess_ImportedFile $file The imported file
     * @param string $path The original path
     * @param ILess_FileInfo $currentFileInfo Current file info
     * @param array $importOptions Import options
     * @param boolean $fromCache Is the imported file coming from cache?
     * @return array
     */
    protected function doImport(ILess_ImportedFile $file,
                                $path,
                                ILess_FileInfo $currentFileInfo, array $importOptions = array(), $fromCache = false)
    {
        $newEnv = ILess_Environment::createCopy($this->env, $this->env->frames);

        $newFileInfo = clone $currentFileInfo;

        if ($this->env->relativeUrls) {
            // Pass on an updated rootPath if path of imported file is relative and file
            // is in a (sub|sup) directory
            //
            // Examples:
            // - If path of imported file is 'module/nav/nav.less' and rootPath is 'less/',
            //   then rootPath should become 'less/module/nav/'
            // - If path of imported file is '../mixins.less' and rootPath is 'less/',
            //   then rootPath should become 'less/../'
            if (!ILess_Util::isPathAbsolute($path) && (($lastSlash = strrpos($path, '/')) !== false)) {
                $relativeSubDirectory = substr($path, 0, $lastSlash + 1);
                $newFileInfo->rootPath = $newFileInfo->rootPath . $relativeSubDirectory;
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
        $error = $root = null;
        $alreadyImported = false;

        // check for already imported file
        if (isset($this->importedFiles[$key])) {
            $alreadyImported = true;
        } elseif (!$file->getRuleset()) {
            $parser = new ILess_Parser_Core($newEnv, $this);
            try {
                // we do not parse the root but load the file as is
                if (isset($importOptions['inline']) && $importOptions['inline']) {
                    $root = $file->getContent();
                } else {
                    $root = $parser->parseFile($file, true);
                    $root->root = false;
                    $root->firstRoot = false;
                }

                $file->setRuleset($root);
            // we need to catch parse exceptions
            } catch (ILess_Exception_Parser $e) {
                // rethrow
                throw $e;
            } catch (Exception $error) {
                // FIXME: what other exceptions are allowed here?
                $file->setError($error);
            }

            $this->setImportedFile($key, $file, $path, $currentFileInfo);
        } else {
            $this->setImportedFile($key, $file, $path, $currentFileInfo);
        }

        if ($fromCache) {
            $ruleset = $this->importedFiles[$key][0]->getRuleset();
            if ($ruleset instanceof ILess_Node) {
                // FIXME: this is a workaround for reference and import one issues
                // when taken cache
                $this->updateReferenceInCurrentFileInfo($ruleset, $newEnv->currentFileInfo->reference);
            }
        }

        return array(
            $alreadyImported, $this->importedFiles[$key][0]
        );
    }

    /**
     * Updates the currentFileInfo object to the $value
     *
     * @param ILess_Node $node The node to update
     * @param boolean $value The value
     */
    protected function updateReferenceInCurrentFileInfo(ILess_Node $node, $value)
    {
        if (isset($node->currentFileInfo)) {
            $node->currentFileInfo->reference = $value;
        }
        if (ILess_Node::propertyExists($node, 'rules')) {
            foreach ($node->rules as $rule) {
                $this->updateReferenceInCurrentFileInfo($rule, $value);
            }
        }
    }

    /**
     * Returns the last modification time of the file
     *
     * @param string $path
     * @param ILess_FileInfo $currentFileInfo
     * @return integer
     * @throws ILess_Exception If there was an error
     */
    public function getLastModified($path, ILess_FileInfo $currentFileInfo)
    {
        foreach ($this->importers as $importer) {
            /* @var $importer ILess_ImporterInterface */
            $result = $importer->getLastModified($path, $currentFileInfo);
            if ($result !== null) {
                return $result;
            }
        }

        throw new ILess_Exception_Import(sprintf('Error getting last modification time of the file "%s".', $path));
    }

    /**
     * Registers an importer
     *
     * @param ILess_ImporterInterface $importer
     * @param string $name The importer name (only for developer reference)
     * @param boolean $prepend Prepend before current importers?
     * @return ILess_Importer
     */
    public function registerImporter(ILess_ImporterInterface $importer, $name = null, $prepend = false)
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
     * @return ILess_ImporterInterface
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
     * @return ILess_Importer
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
     * @return ILess_Importer
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
     * @param ILess_ImportedFile $file The imported file
     * @param string $path The original path to import
     * @param ILess_FileInfo $currentFileInfo
     * @return ILess_Importer
     */
    public function setImportedFile($pathAbsolute, ILess_ImportedFile $file, $path, ILess_FileInfo $currentFileInfo)
    {
        $this->importedFiles[$pathAbsolute] = array($file, $path, $currentFileInfo);
        // save for source map generation
        $this->env->setFileContent($pathAbsolute, $file->getContent());

        return $this;
    }

    /**
     * Returns the imported file
     *
     * @param string $absolutePath The absolute path of the file
     * @param mixed $default The default when no file with given $path is already imported
     * @return array Array(ILess_ImportedFile, $originalPath, ILess_CurrentFileInfo)
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
        return ILess_Util::generateCacheKey($filename);
    }

}

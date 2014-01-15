<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * File system importer. Possible options:
 *
 * `clear_stat_cache` (boolean) - clear stat cache?
 *
 * @package ILess
 * @subpackage import
 */
class ILess_Importer_FileSystem extends ILess_Configurable implements ILess_ImporterInterface {

  /**
   * Array of import paths
   *
   * @var array
   */
  protected $importDirs = array();

  /**
   * Array of default options
   *
   * @var array
   */
  protected $defaultOptions = array(
    'clear_stat_cache' => true // clear the stat cache?
  );

  /**
   * Constructor
   *
   * @param array $importDirs Array of import paths to search
   * @param array $options Array of options
   */
  public function __construct($importDirs = array(), $options = array())
  {
    $this->importDirs = (array)$importDirs;
    parent::__construct($options);
  }

  /**
   * Setups the importer
   */
  protected function setup()
  {
    if($this->getOption('clear_stat_cache'))
    {
      clearstatcache();
    }
  }

  /**
   * @see ILess_ImporterInterface::import
   */
  public function import($dir, ILess_FileInfo $currentFileInfo)
  {
    if($file = $this->find($dir, $currentFileInfo))
    {
      return new ILess_ImportedFile($file, file_get_contents($file), filemtime($file));
    }
    return false;
  }

  /**
   * @see ILess_Importer::getLastModified
   */
  public function getLastModified($dir, ILess_FileInfo $currentFileInfo)
  {
    if($file = $this->find($dir, $currentFileInfo))
    {
      return filemtime($file);
    }
    return false;
  }

  /**
   * Tries to find a file
   *
   * @param string $dir The path to a file
   * @param ILess_FileInfo $currentFileInfo
   * @return string|false
   */
  protected function find($dir, ILess_FileInfo $currentFileInfo)
  {
    // try import dirs first
    foreach($this->importDirs as $importDir)
    {
      if(is_readable($importDir . '/' . $dir))
      {
        return realpath($importDir . '/' . $dir);
      }
    }

    if(is_readable($dir))
    {
      return realpath($dir);
    }
    elseif(is_readable($currentFileInfo->currentDirectory . '/' . $dir))
    {
      return realpath($currentFileInfo->currentDirectory . '/' . $dir);
    }

    return false;
  }

  /**
   * Returns an array of import paths
   *
   * @return array
   */
  public function getImportPaths()
  {
    return $this->importDirs;
  }

  /**
   * Adds import directory
   *
   * @param string $dir The path
   * @param boolean $prepend Prepend?
   * @return ILess_Importer_FileSystem
   */
  public function addImportDir($dir, $prepend = false)
  {
    if($prepend)
    {
      array_unshift($this->importDirs, $dir);
    }
    else
    {
      $this->importDirs[] = $dir;
    }
    return $this;
  }

  /**
   * Set import directories
   *
   * @param array $dirs
   * @return ILess_Importer_FileSystem
   */
  public function setImportDirs($dirs)
  {
    $this->importDirs = (array)$dirs;
    return $this;
  }

}

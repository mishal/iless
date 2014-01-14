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
class ILess_Parser extends ILess_Parser_Core {

  /**
   * Parser version
   *
   */
  const VERSION = '0.9.0-dev';

  /**
   * Array of output filters
   *
   * @var array
   */
  protected $outputFilters = array();

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
    if(isset($options['import_dirs']))
    {
      $importDirs = (array)$options['import_dirs'];
      unset($options['import_dirs']);
    }

    $env = new ILess_Environment($options, new ILess_FunctionRegistry());

    if(!$importers)
    {
      $importers = array(
        new ILess_Importer_FileSystem($importDirs)
      );
    }

    // output filters
    foreach($outputFilters as $filter)
    {
      $this->appendFilter($filter);
    }

    parent::__construct($env, new ILess_Importer($env, $importers, $cache ? $cache : new ILess_Cache_None()));
  }

  /**
   * Converts the ruleset to CSS. Applies the output filters to the output
   *
   * @param ILess_Node_Ruleset $ruleset
   * @param array $variables
   * @return string The generated CSS code
   */
  protected function toCSS(ILess_Node_Ruleset $ruleset, array $variables)
  {
    return $this->filter(parent::toCSS($ruleset, $variables));
  }

  /**
   * Filters the output
   *
   * @param string $output
   * @return string
   */
  protected function filter($output)
  {
    foreach($this->outputFilters as $filter)
    {
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

}

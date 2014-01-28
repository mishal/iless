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
 * @subpackage node
 */
class ILess_Node_Import extends ILess_Node implements ILess_Node_VisitableInterface
{
    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'Import';

    /**
     * The path
     *
     * @var ILess_Node_Quoted|ILess_Node_Url
     */
    public $path;

    /**
     * Current file index
     *
     * @var integer
     */
    public $index = 0;

    /**
     * Array of options
     *
     * @var array
     */
    public $options = array(
        'inline' => false
    );

    /**
     * The features
     *
     * @var ILess_Node
     */
    public $features;

    /**
     * Import CSS flag
     *
     * @var boolean
     */
    public $css = false;

    /**
     * Skip import?
     *
     * @var boolean
     * @see ILess_Visitor_Import::visitImport
     */
    public $skip = false;

    /**
     * The root node
     *
     * @var ILess_Node
     */
    public $root;

    /**
     * Error
     *
     * @var ILess_Exception
     */
    public $error;

    /**
     * Imported filename
     *
     * @var string
     */
    public $importedFilename;

    /**
     * Constructor
     *
     * @param ILess_Node $path The path
     * @param ILess_Node $features The features
     * @param array $options Array of options
     * @param integer $index The index
     * @param ILess_FileInfo $currentFileInfo Current file info
     */
    public function __construct(ILess_Node $path, ILess_Node $features = null, array $options = array(), $index = 0, ILess_FileInfo $currentFileInfo = null)
    {
        $this->path = $path;
        $this->features = $features;
        $this->options = array_merge($this->options, $options);
        $this->index = $index;
        $this->currentFileInfo = $currentFileInfo;
        if (isset($this->options['less']) || $this->options['inline']) {
            $this->css = !isset($this->options['less']) || !$this->options['less'] || $this->options['inline'];
        } else {
            $path = $this->getPath();
            if ($path && preg_match('/css([\?;].*)?$/', $path)) {
                $this->css = true;
            }
        }
    }

    /**
     * Accepts a visit
     *
     * @param ILess_Visitor $visitor
     */
    public function accept(ILess_Visitor $visitor)
    {
        $this->features = $visitor->visit($this->features);
        $this->path = $visitor->visit($this->path);
        if (!$this->getOption('inline') && $this->root) {
            $this->root = $visitor->visit($this->root);
        }
    }

    /**
     * Returns the path
     *
     * @return string|null
     */
    public function getPath()
    {
        if ($this->path instanceof ILess_Node_Quoted) {
            $path = $this->path->value;

            return ($this->css || preg_match('/(\.[a-z]*$)|([\?;].*)$/', $path)) ? $path : $path . '.less';
        } elseif ($this->path instanceof ILess_Node_Url) {
            return $this->path->value->value;
        }
    }

    /**
     * @see ILess_Node
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        if ($this->skip) {
            return array();
        }

        if ($this->getOption('inline')) {
            // FIXME: this section is marked as "todo" in less.js project
            // see: lib/less/tree/import.js
            // original comment: todo needs to reference css file not import
            // $this->root is string here!
            $contents = new ILess_Node_Anonymous($this->root, 0, new ILess_FileInfo(array(
                'filename' => $this->importedFilename
            )), true);

            return $this->features ? new ILess_Node_Media(array($contents), $this->features->value) : array($contents);
        } elseif ($this->css) {
            $features = $this->features ? $this->features->compile($env) : null;
            $import = new ILess_Node_Import($this->compilePath($env), $features, $this->options, $this->index);
            if (!$import->css && $import->hasError()) {
                throw $import->getError();
            }

            return $import;
        } else {
            $ruleset = new ILess_Node_Ruleset(array(), $this->root ? $this->root->rules : array());
            $ruleset->compileImports($env);

            return $this->features ? new ILess_Node_Media($ruleset->rules, $this->features->value) : $ruleset->rules;
        }
    }

    /**
     * Compiles the path
     *
     * @param ILess_Environment $env
     * @return ILess_Node_Url
     */
    public function compilePath(ILess_Environment $env)
    {
        $path = $this->path->compile($env);
        if (!($path instanceof ILess_Node_Url)) {
            $rootPath = $this->currentFileInfo && $this->currentFileInfo->rootPath ? $this->currentFileInfo->rootPath : false;
            if ($rootPath) {
                $pathValue = $path->value;
                // Add the base path if the import is relative
                if ($pathValue && ILess_Util::isPathRelative($pathValue)) {
                    $path->value = $rootPath . $pathValue;
                }
            }
            $path->value = ILess_Util::normalizePath($path->value);
        }

        return $path;
    }

    /**
     * Compiles the node for import
     *
     * @param ILess_Environment $env
     * @return ILess_Node_Import
     */
    public function compileForImport(ILess_Environment $env)
    {
        return new ILess_Node_Import($this->path->compile($env),
            $this->features, $this->options, $this->index, $this->currentFileInfo);
    }

    /**
     * @see ILess_Node
     */
    public function generateCSS(ILess_Environment $env, ILess_Output $output)
    {
        if ($this->css) {
            $output->add('@import ', $this->currentFileInfo, $this->index);
            $this->path->generateCSS($env, $output);
            if ($this->features) {
                $output->add(' ');
                $this->features->generateCSS($env, $output);
            }
            $output->add(';');
        }

        return $output;
    }

    /**
     * Returns the option with given $name
     *
     * @param string $name The option name
     * @param string $default The default value
     * @return mixed
     */
    public function getOption($name, $default = null)
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }

    /**
     * Has the import an error?
     *
     * @return boolean
     */
    public function hasError()
    {
        return $this->error !== null;
    }

    /**
     * Returns the error
     *
     * @return null|ILess_Exception
     */
    public function getError()
    {
        return $this->error;
    }

}

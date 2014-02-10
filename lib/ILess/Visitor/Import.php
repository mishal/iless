<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Import visitor
 *
 * @package ILess
 * @subpackage visitor
 */
class ILess_Visitor_Import extends ILess_Visitor
{
    /**
     * Is replacing flag
     *
     * @var boolean
     */
    protected $isReplacing = true;

    /**
     * The importer
     *
     * @var ILess_Importer
     */
    protected $importer;

    /**
     * Finished flag
     *
     * @var boolean
     */
    protected $isFinished = false;

    /**
     * The environment
     *
     * @var ILess_Environment
     */
    protected $env;

    /**
     * Constructor
     *
     * @param ILess_Environment $env The environment
     * @param ILess_Importer $importer The importer
     */
    public function __construct(ILess_Environment $env, ILess_Importer $importer)
    {
        parent::__construct();
        $this->env = $env;
        $this->importer = $importer;
    }

    /**
     * @see ILess_Visitor::run
     */
    public function run($root)
    {
        return $this->visit($root);
    }

    /**
     * Visits a import node
     *
     * @param ILess_Node_Import $node The node
     * @param ILess_Visitor_Arguments $arguments The arguments
     */
    public function visitImport(ILess_Node_Import $node, ILess_Visitor_Arguments $arguments)
    {
        $arguments->visitDeeper = false;

        $inlineCSS = $node->getOption('inline');
        if (!$node->css || $inlineCSS) {
            $e = null;
            try {
                $compiledNode = $node->compileForImport($this->env);
            } catch (ILess_Exception $e) {
                $compiledNode = false;
                if (!$e->getCurrentFile()) {
                    if ($node->currentFileInfo) {
                        $e->setCurrentFile($node->currentFileInfo, $node->index);
                    } else {
                        $e->setIndex($node->index);
                    }
                }
                $node->css = true;
                $node->error = $e;
            }

            if ($compiledNode && (!$compiledNode->css || $inlineCSS)) {
                $node = $compiledNode;

                $env = ILess_Environment::createCopy($this->env, $this->env->frames);
                if ($node->getOption('multiple')) {
                    $env->importMultiple = true;
                }

                // import the file
                list($alreadyImported, $file) = $this->importer->import($node->getPath(),
                    $node->currentFileInfo, $node->options, $node->index);

                /* @var $file ILess_ImportedFile */
                if ($alreadyImported &&
                    $node->currentFileInfo && $node->currentFileInfo->reference
                ) {
                    $node->skip = true;
                } elseif ($alreadyImported && !$env->importMultiple) {
                    $node->skip = true;
                }

                if ($root = $file->getRuleset()) {
                    /* @var $root ILess_Node_Ruleset */
                    $node->root = $root;
                    $node->importedFilename = $file->getPath();
                    if (!$inlineCSS && !$node->skip) {
                        $visitor = new ILess_Visitor_Import($env, $this->importer);
                        $visitor->visit($root);
                    }
                }
            }
        }

        return $node;
    }

    /**
     * Visits a rule node
     *
     * @param ILess_Node_Rule $node The node
     * @param ILess_Visitor_Arguments $arguments The arguments
     */
    public function visitRule(ILess_Node_Rule $node, ILess_Visitor_Arguments $arguments)
    {
        $arguments->visitDeeper = false;

        return $node;
    }

    /**
     * Visits a directive node
     *
     * @param ILess_Node_Directive $node The node
     * @param ILess_Visitor_Arguments $arguments The arguments
     * @return ILess_Node_Directive
     */
    public function visitDirective(ILess_Node_Directive $node, ILess_Visitor_Arguments $arguments)
    {
        array_unshift($this->env->frames, $node);

        return $node;
    }

    /**
     * Visits a directive node (!again)
     *
     * @param ILess_Node_Directive $node The node
     * @param ILess_Visitor_Arguments $arguments The arguments
     * @return ILess_Node_Directive
     */
    public function visitDirectiveOut(ILess_Node_Directive $node, ILess_Visitor_Arguments $arguments)
    {
        array_shift($this->env->frames);
    }

    /**
     * Visits a mixin definition node
     *
     * @param ILess_Node_MixinDefinition $node The node
     * @param ILess_Visitor_Arguments $arguments The arguments
     * @return ILess_Node_MixinDefinition
     */
    public function visitMixinDefinition(ILess_Node_MixinDefinition $node, ILess_Visitor_Arguments $arguments)
    {
        array_unshift($this->env->frames, $node);

        return $node;
    }

    /**
     * Visits a mixin definition node (!again)
     *
     * @param ILess_Node_MixinDefinition $node The node
     * @param ILess_Visitor_Arguments $arguments The arguments
     * @return ILess_Node_MixinDefinition
     */
    public function visitMixinDefinitionOut(ILess_Node_MixinDefinition $node, ILess_Visitor_Arguments $arguments)
    {
        array_shift($this->env->frames);
    }

    /**
     * Visits a ruleset node
     *
     * @param ILess_Node_Rule $node The node
     * @param ILess_Visitor_Arguments $arguments The arguments
     */
    public function visitRuleset(ILess_Node_Ruleset $node, ILess_Visitor_Arguments $arguments)
    {
        array_unshift($this->env->frames, $node);

        return $node;
    }

    /**
     * Visits a ruleset node (!again)
     *
     * @param ILess_Node_Rule $node The node
     * @param ILess_Visitor_Arguments $arguments The arguments
     */
    public function visitRulesetOut(ILess_Node_Ruleset $node, ILess_Visitor_Arguments $arguments)
    {
        array_shift($this->env->frames);
    }

    /**
     * Visits a media node
     *
     * @param ILess_Node_Rule $node The node
     * @param ILess_Visitor_Arguments $arguments The arguments
     */
    public function visitMedia(ILess_Node_Media $node, ILess_Visitor_Arguments $arguments)
    {
        array_unshift($this->env->frames, $node->rules);

        return $node;
    }

    /**
     * Visits a media node (!again)
     *
     * @param ILess_Node_Rule $node The node
     * @param ILess_Visitor_Arguments $arguments The arguments
     */
    public function visitMediaOut(ILess_Node_Media $node, ILess_Visitor_Arguments $arguments)
    {
        array_shift($this->env->frames);
    }

}

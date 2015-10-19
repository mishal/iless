<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Visitor;

use ILess\Context;
use ILess\Exception\Exception;
use ILess\Exception\ImportException;
use ILess\ImportedFile;
use ILess\Importer;
use ILess\ImportSequencer;
use ILess\Node\DetachedRulesetNode;
use ILess\Node\DirectiveNode;
use ILess\Node\ImportNode;
use ILess\Node\MediaNode;
use ILess\Node\MixinDefinitionNode;
use ILess\Node\RuleNode;
use ILess\Node\RulesetNode;

/**
 * Import visitor.
 */
class ImportVisitor extends Visitor
{
    /**
     * The importer.
     *
     * @var Importer
     */
    protected $importer;

    /**
     * Finished flag.
     *
     * @var bool
     */
    protected $isFinished = false;

    /**
     * The context.
     *
     * @var Context
     */
    protected $context;

    /**
     * @var Exception
     */
    protected $error;

    /**
     * @var int
     */
    private $importCount = 0;

    /**
     * @var string
     */
    protected $type = VisitorInterface::TYPE_PRE_COMPILE;

    /**
     * Constructor.
     *
     * @param Context $context The context
     * @param Importer $importer The importer
     */
    public function __construct(Context $context, Importer $importer)
    {
        parent::__construct();

        $this->context = Context::createCopyForCompilation($context);
        $this->importer = $importer;
        $this->sequencer = new ImportSequencer(
            function () {
                if (!$this->isFinished) {
                    return;
                }
                if ($this->error) {
                    throw $this->error;
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function run($root)
    {
        $this->visit($root);

        $this->isFinished = true;
        $this->sequencer->tryRun();
    }

    /**
     * Visits a import node.
     *
     * @param ImportNode $node The node
     * @param VisitorArguments $arguments The arguments
     *
     * @return ImportNode
     */
    public function visitImport(ImportNode $node, VisitorArguments $arguments)
    {
        if (!$node->css || $node->getOption('inline')) {
            $context = Context::createCopyForCompilation($this->context, $this->context->frames);
            $importParent = $context->frames[0];
            ++$this->importCount;

            if ($node->isVariableImport()) {
                $this->sequencer->addVariableImport(
                    function () use ($node, $context, $importParent) {
                        $this->processImportNode($node, $context, $importParent);
                    }
                );
            } else {
                $this->sequencer->addImport(
                    function () use ($node, $context, $importParent) {
                        $this->processImportNode($node, $context, $importParent);
                    }
                );
            }
        }

        $arguments->visitDeeper = false;

        return $node;
    }

    private function processImportNode(ImportNode $node, Context $context, $importParent)
    {
        $e = null;
        try {
            $compiledNode = $node->compileForImport($context);
        } catch (Exception $e) {
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

        $inlineCSS = $node->getOption('inline');
        $isPlugin = $node->getOption('plugin');

        if ($compiledNode && (!$compiledNode->css || $inlineCSS)) {
            if ($node->getOption('multiple')) {
                $context->importMultiple = true;
            }

            for ($i = 0; $i < count($importParent->rules); ++$i) {
                if ($importParent->rules[$i] === $node) {
                    $importParent->rules[$i] = $compiledNode;
                    break;
                }
            }

            $tryAppendLessExtension = !$compiledNode->css;

            try {
                // import the file
                list($alreadyImported, $file) = $this->importer->import(
                    $compiledNode->getPath(),
                    $tryAppendLessExtension,
                    $compiledNode->currentFileInfo,
                    $compiledNode->options,
                    $compiledNode->index
                );

                /* @var $file ImportedFile */
                if (!$context->importMultiple) {
                    if ($alreadyImported) {
                        $compiledNode->skip = true;
                    }
                }

                if ($root = $file->getRuleset()) {
                    /* @var $root RulesetNode */
                    $compiledNode->root = $root;
                    $compiledNode->importedFilename = $file->getPath();
                    if (!$inlineCSS && !$isPlugin && ($context->importMultiple || !$alreadyImported)) {
                        $oldEnv = $this->context;
                        $this->context = $context;
                        try {
                            $this->visit($root);
                        } catch (Exception $e) {
                            $this->error = $e;
                        }

                        $this->end = $oldEnv;
                    }
                }
            } catch (ImportException $e) {
                // optional import
                if (isset($compiledNode->options['optional']) && $compiledNode->options['optional']) {
                    // optional import
                } else {
                    $this->error = $e;
                }
            } catch (Exception $e) {
                $this->error = $e;
            }
        }

        --$this->importCount;
        if ($this->isFinished) {
            $this->sequencer->tryRun();
        }
    }

    /**
     * Visits a rule node.
     *
     * @param RuleNode $node The node
     * @param VisitorArguments $arguments The arguments
     *
     * @return RuleNode
     */
    public function visitRule(RuleNode $node, VisitorArguments $arguments)
    {
        if ($node->value instanceof DetachedRulesetNode) {
            array_unshift($this->context->frames, $node);
        } else {
            $arguments->visitDeeper = false;
        }

        return $node;
    }

    /**
     * Visits a rule node (!again).
     *
     * @param RuleNode $node The node
     * @param VisitorArguments $arguments The arguments
     *
     * @return RuleNode
     */
    public function visitRuleOut(RuleNode $node, VisitorArguments $arguments)
    {
        if ($node->value instanceof DetachedRulesetNode) {
            array_shift($this->context->frames);
        }

        return $node;
    }

    /**
     * Visits a directive node.
     *
     * @param DirectiveNode $node The node
     * @param VisitorArguments $arguments The arguments
     *
     * @return DirectiveNode
     */
    public function visitDirective(DirectiveNode $node, VisitorArguments $arguments)
    {
        array_unshift($this->context->frames, $node);

        return $node;
    }

    /**
     * Visits a directive node (!again).
     *
     * @param DirectiveNode $node The node
     * @param VisitorArguments $arguments The arguments
     *
     * @return DirectiveNode
     */
    public function visitDirectiveOut(DirectiveNode $node, VisitorArguments $arguments)
    {
        array_shift($this->context->frames);

        return $node;
    }

    /**
     * Visits a mixin definition node.
     *
     * @param MixinDefinitionNode $node The node
     * @param VisitorArguments $arguments The arguments
     *
     * @return MixinDefinitionNode
     */
    public function visitMixinDefinition(MixinDefinitionNode $node, VisitorArguments $arguments)
    {
        array_unshift($this->context->frames, $node);

        return $node;
    }

    /**
     * Visits a mixin definition node (!again).
     *
     * @param MixinDefinitionNode $node The node
     * @param VisitorArguments $arguments The arguments
     *
     * @return MixinDefinitionNode
     */
    public function visitMixinDefinitionOut(MixinDefinitionNode $node, VisitorArguments $arguments)
    {
        array_shift($this->context->frames);

        return $node;
    }

    /**
     * Visits a ruleset node.
     *
     * @param RulesetNode $node The node
     * @param VisitorArguments $arguments The arguments
     *
     * @return RulesetNode
     */
    public function visitRuleset(RulesetNode $node, VisitorArguments $arguments)
    {
        array_unshift($this->context->frames, $node);

        return $node;
    }

    /**
     * Visits a ruleset node (!again).
     *
     * @param RulesetNode $node The node
     * @param VisitorArguments $arguments The arguments
     *
     * @return RulesetNode
     */
    public function visitRulesetOut(RulesetNode $node, VisitorArguments $arguments)
    {
        array_shift($this->context->frames);

        return $node;
    }

    /**
     * Visits a media node.
     *
     * @param MediaNode $node The node
     * @param VisitorArguments $arguments The arguments
     *
     * @return MediaNode
     */
    public function visitMedia(MediaNode $node, VisitorArguments $arguments)
    {
        array_unshift($this->context->frames, $node->rules[0]);

        return $node;
    }

    /**
     * Visits a media node (!again).
     *
     * @param MediaNode $node The node
     * @param VisitorArguments $arguments The arguments
     *
     * @return MediaNode
     */
    public function visitMediaOut(MediaNode $node, VisitorArguments $arguments)
    {
        array_shift($this->context->frames);

        return $node;
    }
}

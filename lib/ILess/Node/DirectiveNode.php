<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Node;

use ILess\Context;
use ILess\DebugInfo;
use ILess\FileInfo;
use ILess\Node;
use ILess\Output\OutputInterface;
use ILess\Visitor\VisitorInterface;

/**
 * Directive.
 */
class DirectiveNode extends Node implements
    MarkableAsReferencedInterface, ReferencedInterface
{
    /**
     * The type.
     *
     * @var string
     */
    protected $type = 'Directive';

    /**
     * The directive name.
     *
     * @var string
     */
    public $name;

    /**
     * Array of rules.
     *
     * @var array
     */
    public $rules = [];

    /**
     * Current index.
     *
     * @var int
     */
    public $index = 0;

    /**
     * Array of variables.
     *
     * @var array
     */
    protected $variables;

    /**
     * Is referenced?
     *
     * @var bool
     */
    public $isReferenced = false;

    /**
     * @var bool
     */
    public $isRooted = false;

    /**
     * @var array
     */
    public $allExtends = [];

    /**
     * Constructor.
     *
     * @param string $name The name
     * @param array|string $value The value
     * @param RulesetNode|null|array $rules
     * @param int $index The index
     * @param FileInfo $currentFileInfo Current file info
     * @param DebugInfo $debugInfo The debug information
     * @param bool $isReferenced Is referenced?
     * @param bool $isRooted Is rooted?
     */
    public function __construct(
        $name,
        $value,
        $rules = null,
        $index = 0,
        FileInfo $currentFileInfo = null,
        DebugInfo $debugInfo = null,
        $isReferenced = false,
        $isRooted = false
    ) {
        $this->name = $name;

        parent::__construct($value);

        if (null !== $rules) {
            if (is_array($rules)) {
                $this->rules = $rules;
            } else {
                $this->rules = [$rules];
                $selectors = new SelectorNode([], [], null, $index, $currentFileInfo);
                $this->rules[0]->selectors = $selectors->createEmptySelectors();
            }
            for ($i = 0; $i < count($this->rules); ++$i) {
                $this->rules[$i]->allowImports = true;
            }
        } else {
            // null
            $this->rules = $rules;
        }

        $this->index = $index;
        $this->currentFileInfo = $currentFileInfo;
        $this->debugInfo = $debugInfo;
        $this->isReferenced = $isReferenced;
        $this->isRooted = $isRooted;
    }

    /**
     * {@inheritdoc}
     */
    public function accept(VisitorInterface $visitor)
    {
        if ($this->rules) {
            $this->rules = $visitor->visitArray($this->rules);
        }

        if ($this->value) {
            $this->value = $visitor->visit($this->value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function generateCSS(Context $context, OutputInterface $output)
    {
        $value = $this->value;
        $rules = $this->rules;

        $output->add($this->name, $this->currentFileInfo, $this->index);

        if ($value) {
            $output->add(' ');
            $value->generateCSS($context, $output);
        }

        if (is_array($rules)) {
            $this->outputRuleset($context, $output, $rules);
        } else {
            $output->add(';');
        }
    }

    /**
     * Compiles the node.
     *
     * @param Context $context The context
     * @param array|null $arguments Array of arguments
     * @param bool|null $important Important flag
     *
     * @return DirectiveNode
     */
    public function compile(Context $context, $arguments = null, $important = null)
    {
        $rules = $this->rules;
        $value = $this->value;

        // media stored inside other directive should not bubble over it
        // backup media bubbling information
        $mediaPathBackup = $context->mediaPath;
        $mediaBlocksBackup = $context->mediaBlocks;

        $context->mediaPath = [];
        $context->mediaBlocks = [];

        if ($value) {
            $value = $value->compile($context);
        }

        if ($rules) {
            $rules = [$rules[0]->compile($context)];
            $rules[0]->root = true;
        }

        $context->mediaPath = $mediaPathBackup;
        $context->mediaBlocks = $mediaBlocksBackup;

        return new self($this->name, $value, $rules, $this->index, $this->currentFileInfo,
            $this->debugInfo, $this->isReferenced, $this->isRooted);
    }

    /**
     * Returns the variable.
     *
     * @param string $name
     *
     * @return RuleNode|null
     */
    public function variable($name)
    {
        if ($this->rules) {
            // assuming that there is only one rule at this point - that is how parser constructs the rule
            return $this->rules[0]->variable($name);
        }
    }

    /**
     * Finds a selector.
     *
     * @param Node $selector
     * @param Context $context
     * @param null $filter
     *
     * @return mixed
     */
    public function find(Node $selector, Context $context, $filter = null)
    {
        if ($this->rules) {
            // assuming that there is only one rule at this point - that is how parser constructs the rule
            return $this->rules[0]->find($selector, $context, $this, $filter);
        }
    }

    /**
     * Returns the rulesets.
     */
    public function rulesets()
    {
        if ($this->rules) {
            // assuming that there is only one rule at this point - that is how parser constructs the rule
            return $this->rules[0]->rulesets();
        }
    }

    /**
     * Mark the directive as referenced.
     */
    public function markReferenced()
    {
        $this->isReferenced = true;
        if ($this->rules) {
            for ($i = 0; $i < count($this->rules); ++$i) {
                if ($this->rules[$i] instanceof MarkableAsReferencedInterface) {
                    $this->rules[$i]->markReferenced();
                }
            }
        }
    }

    /**
     * Is the directive charset directive?
     *
     * @return bool
     */
    public function isCharset()
    {
        return $this->name === '@charset';
    }

    /**
     * @return bool
     */
    public function isRulesetLike()
    {
        return is_array($this->rules) && !$this->isCharset();
    }

    /**
     * @return bool
     */
    public function getIsReferenced()
    {
        return !$this->currentFileInfo || !$this->currentFileInfo->reference || $this->isReferenced;
    }
}

<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Node;

use ILess\Context;
use ILess\Node;
use ILess\Visitor\VisitorInterface;

/**
 * Detached ruleset.
 */
class DetachedRulesetNode extends Node
{
    /**
     * @var string
     */
    protected $type = 'DetachedRuleset';

    /**
     * @var RulesetNode
     */
    public $ruleset;

    /**
     * @var array
     */
    public $frames = [];

    /**
     * @var bool
     */
    public $compileFirst = true;

    /**
     * Constructor.
     *
     * @param RulesetNode $ruleset
     * @param array $frames
     */
    public function __construct(RulesetNode $ruleset, $frames = [])
    {
        $this->ruleset = $ruleset;
        $this->frames = $frames;
    }

    /**
     * {@inheritdoc}
     */
    public function accept(VisitorInterface $visitor)
    {
        $this->ruleset = $visitor->visit($this->ruleset);
    }

    /**
     * @param Context $context
     *
     * @return Node|RulesetNode
     */
    public function callCompile(Context $context)
    {
        if ($this->frames) {
            return $this->ruleset->compile(
                Context::createCopyForCompilation($context, array_merge($this->frames, $context->frames))
            );
        }

        return $this->ruleset->compile($context);
    }

    /**
     * Compiles the node.
     *
     * @param Context $context The context
     * @param array|null $arguments Array of arguments
     * @param bool|null $important Important flag
     *
     * @return DetachedRulesetNode
     */
    public function compile(Context $context, $arguments = null, $important = null)
    {
        if ($this->frames) {
            $frames = $this->frames;
        } else {
            $frames = $context->frames;
        }

        return new self($this->ruleset, $frames);
    }
}

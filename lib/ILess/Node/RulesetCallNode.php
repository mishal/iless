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

/**
 * Ruleset call.
 */
class RulesetCallNode extends Node
{
    /**
     * Node type.
     *
     * @var string
     */
    protected $type = 'RulesetCall';

    /**
     * @var Node
     */
    public $variable;

    /**
     * Constructor.
     *
     * @param string $variable
     */
    public function __construct($variable)
    {
        $this->variable = $variable;
    }

    /**
     * Compiles the node.
     *
     * @param Context $context The context
     * @param array|null $arguments Array of arguments
     * @param bool|null $important Important flag
     *
     * @return Node
     */
    public function compile(Context $context, $arguments = null, $important = null)
    {
        $variable = new VariableNode($this->variable);
        $detachedRuleset = $variable->compile($context);

        return $detachedRuleset->callCompile($context);
    }
}

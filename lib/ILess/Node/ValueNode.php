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
use ILess\Output\OutputInterface;
use ILess\Visitor\Visitor;

/**
 * Value
 *
 * @package ILess\Node
 */
class ValueNode extends Node
{
    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'Value';

    /**
     * Constructor
     *
     * @param array $value Array of value
     */
    public function __construct(array $value)
    {
        parent::__construct($value);
    }

    /**
     * Accepts a visit
     *
     * @param Visitor $visitor
     */
    public function accept(Visitor $visitor)
    {
        $this->value = $visitor->visitArray($this->value);
    }

    /**
     * Compiles the node
     *
     * @param Context $context The context
     * @param array|null $arguments Array of arguments
     * @param boolean|null $important Important flag
     * @return ValueNode
     */
    public function compile(Context $context, $arguments = null, $important = null)
    {
        if (count($this->value) == 1) {
            return $this->value[0]->compile($context);
        }

        $return = array();
        foreach ($this->value as $v) {
            $return[] = $v->compile($context);
        }

        return new ValueNode($return);
    }

    /**
     * @inheritdoc
     */
    public function generateCSS(Context $context, OutputInterface $output)
    {
        for ($i = 0, $count = count($this->value); $i < $count; $i++) {
            $this->value[$i]->generateCSS($context, $output);
            if ($i + 1 < $count) {
                $output->add($context->compress ? ',' : ', ');
            }
        }
    }

}
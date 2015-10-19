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

/**
 * Negative node.
 */
class NegativeNode extends Node
{
    /**
     * Node type.
     *
     * @var string
     */
    protected $type = 'Negative';

    /**
     * {@inheritdoc}
     */
    public function generateCSS(Context $context, OutputInterface $output)
    {
        $output->add('-');
        $this->value->generateCSS($context, $output);
    }

    /**
     * Compiles the node.
     *
     * @param Context $context The context
     * @param array|null $arguments Array of arguments
     * @param bool|null $important Important flag
     *
     * @return NegativeNode|Node
     */
    public function compile(Context $context, $arguments = null, $important = null)
    {
        if ($context->isMathOn()) {
            $operation = new OperationNode('*', [
                new DimensionNode('-1'),
                $this->value,
            ]);

            return $operation->compile($context);
        } else {
            return new self($this->value->compile($context));
        }
    }
}

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
 * Alpha.
 */
class AlphaNode extends Node
{
    /**
     * Node type.
     *
     * @var string
     */
    protected $type = 'Alpha';

    /**
     * {@inheritdoc}
     */
    public function generateCSS(Context $context, OutputInterface $output)
    {
        $output->add('alpha(opacity=');
        if ($this->value instanceof GenerateCSSInterface) {
            $this->value->generateCSS($context, $output);
        } else {
            $output->add((string) $this->value);
        }
        $output->add(')');
    }

    /**
     * {@inheritdoc}
     */
    public function compile(Context $context, $arguments = null, $important = null)
    {
        if ($this->value instanceof CompilableInterface) {
            return new self($this->value->compile($context));
        }

        return $this;
    }
}

<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Node;

use ILess\Context;
use ILess\Exception\Exception;
use ILess\Node;
use ILess\Output\OutputInterface;

/**
 * Keyword.
 */
class KeywordNode extends Node
{
    /**
     * Node type.
     *
     * @var string
     */
    protected $type = 'Keyword';

    /**
     * {@inheritdoc}
     */
    public function generateCSS(Context $context, OutputInterface $output)
    {
        if ($this->value === '%') {
            throw new Exception('Invalid % without number');
        }

        $output->add($this->value);
    }

    /**
     * Converts the value to string.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->value;
    }
}

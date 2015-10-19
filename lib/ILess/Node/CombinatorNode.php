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
 * Combinator.
 */
class CombinatorNode extends Node
{
    /**
     * Node type.
     *
     * @var string
     */
    protected $type = 'Combinator';

    /**
     * @var array
     */
    protected $noSpaceCombinators = [
        '' => true,
        ' ' => true,
        '|' => true,
    ];

    /**
     * @var bool
     */
    public $emptyOrWhitespace;

    /**
     * Constructor.
     *
     * @param string $value The string
     */
    public function __construct($value = null)
    {
        if ($value === ' ') {
            $this->emptyOrWhitespace = true;
        } else {
            $value = trim($value);
            $this->emptyOrWhitespace = $value === '';
        }
        parent::__construct($value);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCSS(Context $context, OutputInterface $output)
    {
        $spaceOrEmpty = ($context->compress || (isset($this->noSpaceCombinators[$this->value]))) ? '' : ' ';
        $output->add($spaceOrEmpty . $this->value . $spaceOrEmpty);
    }
}

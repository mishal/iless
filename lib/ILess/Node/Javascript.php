<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Javascript
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_Javascript extends ILess_Node
{
    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'Javascript';

    /**
     * Escaped?
     *
     * @var boolean
     */
    protected $escaped = false;

    /**
     * Current index
     *
     * @var integer
     */
    protected $index = 0;

    /**
     * The javascript expression
     *
     * @var string
     */
    protected $expression;

    /**
     * Constructor
     *
     * @param string $expression The javascript expression
     * @param integer $index The index
     * @param boolean $escaped Is the value escaped?
     */
    public function __construct($expression, $index = 0, $escaped = false)
    {
        $this->expression = $expression;
        $this->index = $index;
        $this->escaped = $escaped;
    }

    /**
     * @see ILess_Node
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        return $this;
    }

    /**
     * @see ILess_Node::generateCSS
     */
    public function generateCSS(ILess_Environment $env, ILess_Output $output)
    {
        // we do not care about compression flag here, so the developer knows whats going on
        $output->add('/*Sorry, unable to do javascript evaluation in PHP... With men it is impossible, but not with God: for with God all things are possible. Mark 10:27*/');
    }

}

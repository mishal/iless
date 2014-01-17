<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Anonymous node
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_Anonymous extends ILess_Node
{
    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'Anonymous';

    /**
     * Current index
     *
     * @var integer
     */
    public $index = 0;

    /**
     * Map lines flag
     *
     * @var boolean
     */
    public $mapLines;

    /**
     * Constructor
     *
     * @param string|ILess_Node_Value $value
     * @param integer $index
     * @param string $currentFileInfo
     * @param boolean $mapLines
     */
    public function __construct($value, $index = 0, ILess_FileInfo $currentFileInfo = null, $mapLines = false)
    {
        $this->value = (string)($value instanceof ILess_Node ? $value->value : $value);
        $this->index = $index;
        $this->currentFileInfo = $currentFileInfo;
        $this->mapLines = $mapLines;
    }

    /**
     * @see ILess_Node::generateCSS
     */
    public function generateCSS(ILess_Environment $env, ILess_Output $output)
    {
        $output->add($this->value, $this->currentFileInfo, $this->index, $this->mapLines);
    }

    /**
     * Converts to CSS
     *
     * @param ILess_Environment $env
     * @return string
     */
    public function toCSS(ILess_Environment $env)
    {
        return $this->value;
    }

    /**
     * Compiles the node
     *
     * @param ILess_Environment $env
     * @return ILess_Node_Anonymous
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        return new ILess_Node_Anonymous($this->value, $this->index, $this->currentFileInfo, $this->mapLines);
    }

    /**
     * Compares the another node
     *
     * @param ILess_Node $other
     * @return int
     */
    public function compare(ILess_Node $other)
    {
        if (!self::methodExists($other, 'toCSS')) {
            return -1;
        }

        // we need to provide the environment for those
        $env = new ILess_Environment();
        $left = $this->toCSS($env);
        $right = $other->toCSS($env);

        if ($left === $right) {
            return 0;
        }

        return $left < $right ? -1 : 1;
    }

}

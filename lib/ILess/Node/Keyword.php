<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Keyword node
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_Keyword extends ILess_Node implements ILess_Node_ComparableInterface
{
    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'Keyword';

    /**
     * Compiles the node
     *
     * @param ILess_Environment $env
     * @return ILess_Node_Keyword
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
        $output->add($this->value);
    }

    /**
     * Compares the keyword with another one
     *
     * @param ILess_Node $other
     * @return integer
     */
    public function compare(ILess_Node $other)
    {
        if ($other instanceof ILess_Node_Keyword) {
            return $other->value === $this->value ? 0 : 1;
        } else {
            return -1;
        }
    }

    /**
     * Converts the value to string
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->value;
    }

}

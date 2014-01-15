<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Unicode descriptor
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_UnicodeDescriptor extends ILess_Node
{
    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'UnicodeDescriptor';

    /**
     * @see ILess_Node::compile
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

}

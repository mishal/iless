<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Alpha
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_Alpha extends ILess_Node implements ILess_Node_VisitableInterface
{
    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'Alpha';

    /**
     * @see ILess_Node_VisitableInterface::accept
     */
    public function accept(ILess_Visitor $visitor)
    {
        $this->value = $visitor->visit($this->value);
    }

    /**
     * @see ILess_Node::generateCSS
     */
    public function generateCSS(ILess_Environment $env, ILess_Output $output)
    {
        $output->add('alpha(opacity=');
        if (self::methodExists($this->value, 'generateCSS')) {
            $this->value->generateCSS($env, $output);
        } else {
            $output->add((string)$this->value);
        }
        $output->add(')');
    }

    /**
     * @see ILess_Node::compile
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        if (self::methodExists($this->value, 'compile')) {
            $this->value = $this->value->compile($env);
        }

        return $this;
    }

}

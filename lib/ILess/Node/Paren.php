<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Paren node
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_Paren extends ILess_Node implements ILess_Node_VisitableInterface
{
    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'Paren';

    /**
     * Constructor
     *
     * @param ILess_Node $value
     */
    public function __construct(ILess_Node $value)
    {
        parent::__construct($value);
    }

    /**
     * @see ILess_Node_VisitableInterface
     */
    public function accept(ILess_Visitor $visitor)
    {
        $this->value = $visitor->visit($this->value);
    }

    /**
     * @see ILess_Node::compile
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        if (!is_object($this->value)) {
            return $this;
        }

        return new ILess_Node_Paren($this->value->compile($env));
    }

    /**
     * @see ILess_Node::generateCSS
     */
    public function generateCSS(ILess_Environment $env, ILess_Output $output)
    {
        $output->add('(');
        $this->value->generateCSS($env, $output);
        $output->add(')');
    }

}

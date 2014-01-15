<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Negative
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_Negative extends ILess_Node implements ILess_Node_VisitableInterface
{
    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'Negative';

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
     * Accepts a visit
     *
     * @param ILess_Visitor $visitor
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
        $output->add('-');
        $this->value->generateCSS($env, $output);
    }

    /**
     * Compiles the node
     *
     * @param ILess_Environment $env
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        if ($env->isMathOn()) {
            $operation = new ILess_Node_Operation('*', array(
                new ILess_Node_Dimension('-1'),
                $this->value
            ));

            return $operation->compile($env);
        } else {
            return new ILess_Node_Negative($this->value->compile($env));
        }
    }

}

<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Node value
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_Value extends ILess_Node implements ILess_Node_VisitableInterface
{
    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'Value';

    /**
     * The value holder
     *
     * @var array
     */
    public $value = array();

    /**
     * Constructor
     *
     * @param array $value Array of value
     */
    public function __construct(array $value)
    {
        $this->value = $value;
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
     * @see ILess_Node
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        if (count($this->value) == 1) {
            return $this->value[0]->compile($env);
        }

        $return = array();
        foreach ($this->value as $v) {
            $return[] = $v->compile($env);
        }

        return new ILess_Node_Value($return);
    }

    /**
     * @see ILess_Node::generateCSS
     */
    public function generateCSS(ILess_Environment $env, ILess_Output $output)
    {
        for ($i = 0, $count = count($this->value); $i < $count; $i++) {
            $this->value[$i]->generateCSS($env, $output);
            if ($i + 1 < $count) {
                $output->add($env->compress ? ',' : ', ');
            }
        }
    }

}

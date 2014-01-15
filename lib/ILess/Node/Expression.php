<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Expression
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_Expression extends ILess_Node implements ILess_Node_VisitableInterface
{
    /**
     * The value holder
     *
     * @var array
     */
    public $value = array();

    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'Expression';

    /**
     * Parens flag
     *
     * @var boolean
     */
    public $parens = false;

    /**
     * Parens in operator flag
     *
     * @var boolean
     */
    public $parensInOp = false;

    /**
     * Constructor
     *
     * @param array $value
     */
    public function __construct(array $value)
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
     * Compiles the node
     *
     * @param ILess_Environment $env
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        $inParenthesis = $this->parens && !$this->parensInOp;
        $doubleParen = false;

        if ($inParenthesis) {
            $env->inParenthesis();
        }

        $count = count($this->value);
        if ($count > 1) {
            $compiled = array();
            foreach ($this->value as $v) {
                $compiled[] = $v->compile($env);
            }
            $return = new ILess_Node_Expression($compiled);
        } elseif ($count === 1) {
            if (!isset($this->value[0])) {
                $this->value = array_slice($this->value, 0);
            }

            if (property_exists($this->value[0], 'parens') && $this->value[0]->parens
                && property_exists($this->value[0], 'parensInOp') && !$this->value[0]->parensInOp
            ) {
                $doubleParen = true;
            }
            $return = $this->value[0]->compile($env);
        } else {
            $return = $this;
        }

        if ($inParenthesis) {
            $env->outOfParenthesis();
        }

        if ($this->parens && $this->parensInOp && !$env->isMathOn() && !$doubleParen) {
            $return = new ILess_Node_Paren($return);
        }

        return $return;
    }

    /**
     * @see ILess_Node::generateCSS
     */
    public function generateCSS(ILess_Environment $env, ILess_Output $output)
    {
        for ($i = 0, $count = count($this->value); $i < $count; $i++) {
            $this->value[$i]->generateCSS($env, $output);
            if ($i + 1 < $count) {
                $output->add(' ');
            }
        }
    }

    public function throwAwayComments()
    {
        if (is_array($this->value)) {
            $new = array();
            foreach ($this->value as $v) {
                if ($v instanceof ILess_Node_Comment) {
                    continue;
                }
                $new[] = $v;
            }
            $this->value = $new;
        }
    }

}

<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Condition
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_Condition extends ILess_Node implements ILess_Node_VisitableInterface
{
    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'Condition';

    /**
     * The operator
     * @var string
     */
    private $op;

    /**
     * The left operand
     *
     * @var ILess_Node
     */
    private $lvalue;

    /**
     * The right operand
     *
     * @var ILess_Node
     */
    private $rvalue;

    /**
     * Current index
     *
     * @var integer
     */
    private $index = 0;

    /**
     * Negate the result?
     *
     * @var boolean
     */
    private $negate = false;

    /**
     * Constructor
     *
     * @param string $op The operator
     * @param ILess_Node $l The left operand
     * @param ILess_Node $r The right operand
     * @param integer $i
     * @param boolean $negate
     */
    public function __construct($op, ILess_Node $l, ILess_Node $r, $i = 0, $negate = false)
    {
        $this->op = trim($op);
        $this->lvalue = $l;
        $this->rvalue = $r;
        $this->index = $i;
        $this->negate = (boolean)$negate;
    }

    /**
     * Accepts a visitor
     *
     * @param ILess_Visitor $visitor
     * @return void
     */
    public function accept(ILess_Visitor $visitor)
    {
        $this->lvalue = $visitor->visit($this->lvalue);
        $this->rvalue = $visitor->visit($this->rvalue);
    }

    /**
     * @see ILess_Node::generateCSS
     */
    public function generateCSS(ILess_Environment $env, ILess_Output $output)
    {
    }

    /**
     * Compiles the node
     *
     * @param ILess_Environment $env
     * @return boolean
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        $a = $this->lvalue->compile($env);
        $b = $this->rvalue->compile($env);

        switch ($this->op) {
            case 'and':
                $result = $a && $b;
                break;

            case 'or':
                $result = $a || $b;
                break;

            default:

                if (self::methodExists($a, 'compare')) {
                    $result = $a->compare($b);
                } elseif (self::methodExists($b, 'compare')) {
                    $result = $b->compare($a);
                } else {
                    throw new ILess_Exception_Compiler('Unable to perform the comparison', $this->index, $env->currentFileInfo);
                }

                // FIXME: Operators has beed modified. its seems that there is a bug in less.js
                // The operator "=>" is missing in case 0 and case 1
                // Less.js version:
                /*
                switch (result) {
                    case -1: return op === '<' || op === '=<' || op === '<=';
                    case  0: return op === '=' || op === '>=' || op === '=<' || op === '<=';
                    case  1: return op === '>' || op === '>=';
                }
                 */
                switch ($result) {
                    case -1:
                        $result = $this->op === '<' || $this->op === '=<' || $this->op === '<=';
                        break;

                    case 0:
                        $result = $this->op === '=' || $this->op === '>=' || $this->op === '=>' || $this->op === '=<' || $this->op === '<=';
                        break;

                    case 1:
                        $result = $this->op === '>' || $this->op === '>=' || $this->op === '=>';
                        break;
                }
                break;
        }

        return $this->negate ? !$result : $result;
    }

}

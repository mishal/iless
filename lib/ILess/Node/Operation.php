<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Operation
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_Operation extends ILess_Node implements ILess_Node_VisitableInterface
{
    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'Operation';

    /**
     * Operator
     *
     * @var string
     */
    protected $operator;

    /**
     * Array of operands
     *
     * @var array
     */
    protected $operands;

    /**
     * Is spaced flag
     *
     * @var boolean
     */
    public $isSpaced = false;

    /**
     * Parens
     *
     * @var boolean
     */
    public $parensInOp = false;

    /**
     * Constructor
     *
     * @param string $operator The operator
     * @param array $operands Array of operands
     * @param boolean $isSpaced Is spaced?
     */
    public function __construct($operator, array $operands, $isSpaced = false)
    {
        $this->operator = trim($operator);

        if (count($operands) !== 2) {
            throw new InvalidArgumentException('Invalid operands given. Accepted is an array with 2 operands.');
        }

        $this->operands = $operands;
        $this->isSpaced = $isSpaced;
    }

    /**
     * @see ILess_Node_VisitableInterface::accept
     */
    public function accept(ILess_Visitor $visitor)
    {
        $this->operands = $visitor->visit($this->operands);
    }

    /**
     * @see ILess_Node::compile
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        $a = $this->operands[0]->compile($env);
        $b = $this->operands[1]->compile($env);

        if ($env->isMathOn()) {
            if ($a instanceof ILess_Node_Dimension && $b instanceof ILess_Node_Color) {
                if ($this->operator === '*' || $this->operator === '+') {
                    $temp = $b;
                    $b = $a;
                    $a = $temp;
                } else {
                    throw new ILess_Exception_Compiler('Can\'t substract or divide a color from a number.');
                }
            }

            if (!self::methodExists($a, 'operate')) {
                throw new ILess_Exception_Compiler('Operation on an invalid type.');
            }

            return $a->operate($env, $this->operator, $b);
        } else {
            return new ILess_Node_Operation($this->operator, array($a, $b), $this->isSpaced);
        }
    }

    /**
     * @see ILess_Node::generateCSS
     */
    public function generateCSS(ILess_Environment $env, ILess_Output $output)
    {
        $this->operands[0]->generateCSS($env, $output);

        if ($this->isSpaced) {
            $output->add(' ');
        }

        $output->add($this->operator);

        if ($this->isSpaced) {
            $output->add(' ');
        }

        $this->operands[1]->generateCSS($env, $output);
    }

}

<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Attribute
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_Attribute extends ILess_Node implements ILess_Node_VisitableInterface
{
    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'Attribute';

    /**
     * The key
     *
     * @var string|ILess_Node
     */
    public $key;

    /**
     * The operator
     *
     * @var string
     */
    public $operator;

    /**
     * Constructor
     *
     * @param string|ILess_Node $key
     * @param string $operator
     * @param string|ILess_Node $value
     */
    public function __construct($key, $operator, $value)
    {
        $this->key = $key;
        $this->operator = $operator;
        parent::__construct($value);
    }

    /**
     * Accepts a visit
     *
     * @param ILess_Visitor $visitor The visitor
     */
    public function accept(ILess_Visitor $visitor)
    {
        $this->value = $visitor->visit($this->value);
    }

    /**
     * Compiles the node
     *
     * @param ILess_Environment $env
     * @return ILess_Node_Attribute
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        return new ILess_Node_Attribute(
            self::methodExists($this->key, 'compile') ? $this->key->compile($env) : $this->key,
            $this->operator,
            self::methodExists($this->value, 'compile') ? $this->value->compile($env) : $this->value
        );
    }

    /**
     * @see ILess_Node::generateCSS
     */
    public function generateCSS(ILess_Environment $env, ILess_Output $output)
    {
        $output->add($this->toCSS($env));
    }

    /**
     * Converts to CSS
     *
     * @param ILess_Environment $env
     * @return string
     */
    public function toCSS(ILess_Environment $env)
    {
        $value = self::methodExists($this->key, 'toCSS') ? $this->key->toCSS($env) : $this->key;

        if ($this->operator) {
            $value .= $this->operator;
            $value .= (self::methodExists($this->value, 'toCSS') ? $this->value->toCSS($env) : $this->value);
        }

        return '[' . $value . ']';
    }

}

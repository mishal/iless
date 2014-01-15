<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Assignment
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_Assignment extends ILess_Node implements ILess_Node_VisitableInterface
{
    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'Assignment';

    /**
     * The assignment key
     *
     * @var string
     */
    private $key;

    /**
     * Constructor
     *
     * @param string $key
     * @param string|ILess_INode $value
     */
    public function __construct($key, $value)
    {
        parent::__construct($value);
        $this->key = $key;
    }

    /**
     * Accepts a visitor
     *
     * @param ILess_Visitor $visitor
     * @return void
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
        $output->add(sprintf('%s=', $this->key));
        if (self::methodExists($this->value, 'generateCSS')) {
            $this->value->generateCSS($env, $output);
        } else {
            $output->add($this->value);
        }
    }

    /**
     * Compiles the node
     *
     * @param ILess_Environment $env
     * @return ILess_Node_Assignment
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        if (self::methodExists($this->value, 'compile')) {
            return new ILess_Node_Assignment($this->key, $this->value->compile($env));
        }

        return $this;
    }

}

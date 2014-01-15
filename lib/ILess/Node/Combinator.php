<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Combinator
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_Combinator extends ILess_Node
{
    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'Combinator';

    /**
     * Output map
     *
     * @var array
     */
    protected static $outputMap = array(
        '' => '',
        ' ' => ' ',
        ':' => ' :',
        '+' => ' + ',
        '~' => ' ~ ',
        '>' => ' > ',
        '|' => '|'
    );

    /**
     * Output map for compressed output
     *
     * @var array
     */
    protected static $outputMapCompressed = array(
        '' => '',
        ' ' => ' ',
        ':' => ' :',
        '+' => '+',
        '~' => '~',
        '>' => '>',
        '|' => '|'
    );

    /**
     * Constructor
     *
     * @param string $value The string
     */
    public function __construct($value = null)
    {
        if ($value == ' ') {
            $value = ' ';
        } else {
            $value = trim($value);
        }
        parent::__construct($value);
    }

    /**
     * Compiles the node
     *
     * @param ILess_Environment $env
     * @return ILess_Node_Combinator
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
        $output->add($env->compress ? self::$outputMapCompressed[$this->value] : self::$outputMap[$this->value]);
    }

}

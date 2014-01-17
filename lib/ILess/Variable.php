<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Variable represents custom variable passed by the API (not from less string or file)
 *
 * @package ILess
 * @subpackage core
 */
class ILess_Variable
{
    /**
     * Dimension detection regexp
     *
     */
    const DIMENSION_REGEXP = '/^([+-]?\d*\.?\d+)(%|[a-z]+)?$/';

    /**
     * Quoted detection regexp
     *
     */
    const QUOTED_REGEXP = '/^"((?:[^"\\\\\r\n]|\\\\.)*)"|\'((?:[^\'\\\\\r\n]|\\\\.)*)\'$/';

    /**
     * Important flag
     *
     * @var boolean
     */
    protected $important = false;

    /**
     * The name
     *
     * @var string
     */
    protected $name;

    /**
     * The value
     *
     * @var ILess_Node
     */
    protected $value;

    /**
     * Constructor
     *
     * @param string $name The name of the variable
     * @param ILess_Node $value The value of the variable
     * @param boolean $important Important?
     */
    public function __construct($name, ILess_Node $value, $important = false)
    {
        $this->name = ltrim($name, '@');
        $this->value = $value;
        $this->important = (boolean)$important;
    }

    /**
     * Creates the variable. Detects the type.
     *
     * @param string $name The name of the variable
     * @param mixed $value The value of the variable
     * @return ILess_Variable
     */
    public static function create($name, $value)
    {
        $important = false;
        // name is marked as !name
        if (strpos($name, '!') === 0) {
            $important = true;
            $name = substr($name, 1);
        }

        // Color
        if (ILess_Color::isNamedColor($value) || $value === 'transparent' || strpos($value, '#') === 0) {
            $value = new ILess_Node_Color(new ILess_Color($value));
        } // Quoted string
        elseif (preg_match(self::QUOTED_REGEXP, $value, $matches)) {
            $value = new ILess_Node_Quoted($matches[0], $matches[0][0] == '"' ? $matches[1] : $matches[2]);
        } // URL
        elseif (strpos($value, 'http://') === 0 || strpos($value, 'https://') === 0) {
            $value = new ILess_Node_Anonymous($value);
        } // Dimension
        elseif (preg_match(self::DIMENSION_REGEXP, $value, $matches)) {
            $value = new ILess_Node_Dimension($matches[1], isset($matches[2]) ? $matches[2] : null);
        } // everything else
        else {
            $value = new ILess_Node_Anonymous($value);
        }

        return new ILess_Variable($name, $value, $important);
    }

    /**
     * Converts the variable to the node
     *
     * @return ILess_Node_Rule
     */
    public function toNode()
    {
        return new ILess_Node_Rule('@' . $this->name, new ILess_Node_Value(array(
            $this->value
        )), $this->important ? '!important' : '');
    }

    /**
     * Returns the variable name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the variable value
     *
     * @return ILess_Node
     */
    public function getValue()
    {
        return $this->value;
    }

}

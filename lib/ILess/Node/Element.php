<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Element
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_Element extends ILess_Node implements ILess_Node_VisitableInterface
{
    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'Element';

    /**
     * Node combinator
     *
     * @var ILess_Node_Combinator
     */
    public $combinator;

    /**
     * The current index
     *
     * @var integer
     */
    public $index = 0;

    /**
     * Constructor
     *
     * @param ILess_Node_Combinator|string $combinator The combinator
     * @param string|ILess_Node $value The value
     * @param integer $index The current index
     * @param array $currentFileInfo Current file information
     */
    public function __construct($combinator, $value, $index = 0, ILess_FileInfo $currentFileInfo = null)
    {
        if (!($combinator instanceof ILess_Node_Combinator)) {
            $combinator = new ILess_Node_Combinator($combinator);
        }

        if (is_string($value)) {
            $this->value = trim($value);
        } elseif ($value) {
            $this->value = $value;
        } else {
            $this->value = '';
        }

        $this->combinator = $combinator;
        $this->index = $index;
        $this->currentFileInfo = $currentFileInfo;
    }

    /**
     * Accepts a visitor
     *
     * @param ILess_Visitor $visitor
     */
    public function accept(ILess_Visitor $visitor)
    {
        $this->combinator = $visitor->visit($this->combinator);
        $this->value = $visitor->visit($this->value);
    }

    /**
     * @see ILess_Node::generateCSS
     */
    public function generateCSS(ILess_Environment $env, ILess_Output $output)
    {
        $output->add($this->toCSS($env), $this->currentFileInfo, $this->index);
    }

    /**
     * Convert to CSS
     *
     * @param ILess_Environment $env The environment
     * @return string
     */
    public function toCSS(ILess_Environment $env)
    {
        $value = self::methodExists($this->value, 'toCSS') ? $this->value->toCSS($env) : $this->value;
        if ($value === '' && strlen($this->combinator->value) && strpos($this->combinator->value, '&') === 0) {
            return '';
        } else {
            return $this->combinator->toCSS($env) . $value;
        }
    }

    /**
     * Compiles the node
     *
     * @param ILess_Environment $env The environment
     * @return ILess_Node_Element
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        return new ILess_Node_Element($this->combinator, is_string($this->value) ? $this->value :
            $this->value->compile($env), $this->index, $this->currentFileInfo);
    }

}

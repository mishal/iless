<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Rule
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_Rule extends ILess_Node implements ILess_Node_VisitableInterface, ILess_Node_MakeableImportantInterface
{
    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'Rule';

    /**
     * The name
     *
     * @var string
     */
    public $name;

    /**
     * Important keyword ( "!important")
     *
     * @var string
     */
    public $important;

    /**
     * Current index
     *
     * @var integer
     */
    public $index = 0;

    /**
     * Inline flag
     *
     * @var boolean
     */
    public $inline = false;

    /**
     * Is variable?
     *
     * @var boolean
     */
    public $variable = false;

    /**
     * Merge flag
     *
     * @var boolean
     */
    public $merge = false;

    /**
     * Constructor
     *
     * @param string $name The rule name
     * @param string|ILess_Node_Value $value The value
     * @param string $important Important keyword
     * @param boolean $merge Merge?
     * @param integer $index Current index
     * @param ILess_FileInfo $currentFileInfo The current file info
     * @param boolean $inline Inline flag
     */
    public function __construct($name, $value, $important = null, $merge = null, $index = 0, ILess_FileInfo $currentFileInfo = null, $inline = false)
    {
        $this->name = $name;
        $this->value = ($value instanceof ILess_Node_Value) ? $value : new ILess_Node_Value(array($value));
        $this->important = $important ? ' ' . trim($important) : '';
        $this->merge = $merge;
        $this->index = $index;
        $this->currentFileInfo = $currentFileInfo;
        $this->inline = (boolean)$inline;

        if ($name[0] === '@') {
            $this->variable = true;
        }
    }

    /**
     * @see ILess_Node_VisitableInterface::accept
     */
    public function accept(ILess_Visitor $visitor)
    {
        $this->value = $visitor->visit($this->value);
    }

    /**
     * @see ILess_Node::compile
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        $strictMathBypass = false;
        if ($this->name === 'font' && !$env->strictMath) {
            $strictMathBypass = true;
            $env->strictMath = true;
        }

        $e = null;
        try {
            $return = new ILess_Node_Rule($this->name,
                $this->value->compile($env),
                $this->important, $this->merge,
                $this->index, $this->currentFileInfo,
                $this->inline
            );
        } catch (Exception $e) {

            if ($e instanceof ILess_Exception) {
                if ($e->getCurrentFile() === null && $e->getIndex() === null)
                {
                    $e->setCurrentFile($this->currentFileInfo, $this->index);
                }
            }
        }

        if ($strictMathBypass) {
            $env->strictMath = false;
        }

        if (isset($e)) {
            throw $e;
        }

        return $return;
    }

    /**
     * @see ILess_Node::generateCSS
     */
    public function generateCSS(ILess_Environment $env, ILess_Output $output)
    {
        $output->add($this->name . ($env->compress ? ':' : ': '), $this->currentFileInfo, $this->index);
        try {
            $this->value->generateCSS($env, $output);
        } catch (ILess_Exception $e) {
            if ($this->currentFileInfo) {
                $e->setCurrentFile($this->currentFileInfo, $this->index);
            }
            // rethrow
            throw $e;
        }
        $output->add($this->important . (($this->inline || ($env->lastRule && $env->compress)) ? '' : ';'), $this->currentFileInfo, $this->index);
    }

    /**
     * Makes the node important
     *
     * @return ILess_Node_Rule
     */
    public function makeImportant()
    {
        return new ILess_Node_Rule(
            $this->name,
            $this->value,
            '!important',
            $this->merge, $this->index, $this->currentFileInfo, $this->inline);
    }

}

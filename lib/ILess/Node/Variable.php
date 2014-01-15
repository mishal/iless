<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Variable
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_Variable extends ILess_Node
{
    /**
     * The name
     *
     * @var string
     */
    public $name;

    /**
     * Current index
     *
     * @var integer
     */
    public $index = 0;

    /**
     * Current file information
     *
     * @var ILess_FileInfo
     */
    public $currentFileInfo;

    /**
     * Evaluating flag
     *
     * @var boolean
     */
    protected $evaluating = false;

    /**
     * Constructor
     *
     * @param string $name The variable name
     * @param integer $index The current index
     * @param ILess_FileInf $currentFileInfo The current file information
     */
    public function __construct($name, $index = 0, ILess_FileInfo $currentFileInfo = null)
    {
        $this->name = $name;
        $this->index = $index;
        $this->currentFileInfo = $currentFileInfo;
    }

    /**
     * @see ILess_Node::compile
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        $name = $this->name;
        if (strpos($name, '@@') === 0) {
            $v = new ILess_Node_Variable(substr($name, 1), $this->index + 1);
            $name = '@' . $v->compile($env)->value;
        }

        if ($this->evaluating) {
            throw new ILess_Exception_Compiler(
                sprintf('Recursive variable definition for %s', $name),
                null, $this->index,
                $this->currentFileInfo
            );
        }

        $this->evaluating = true;

        // variables from the API take precedence
        if ($env->customVariables &&
            $v = $env->customVariables->variable($name)
        ) {
            $this->evaluating = false;

            return $v->value->compile($env);
        }

        // search for the variable
        foreach ($env->frames as $frame) {
            if ($v = $frame->variable($name)) {
                $this->evaluating = false;

                return $v->value->compile($env);
            }
        }

        throw new ILess_Exception_Compiler(
            sprintf('The variable `%s` is not defined.', $name),
            null, $this->index,
            $this->currentFileInfo);
    }

    /**
     * @see ILess_Node::generateCSS
     */
    public function generateCSS(ILess_Environment $env, ILess_Output $output)
    {
    }

}

<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Function call
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_Call extends ILess_Node implements ILess_Node_VisitableInterface
{
    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'Call';

    /**
     * The function name
     *
     * @var string
     */
    protected $name;

    /**
     * Array of arguments
     *
     * @var array
     */
    protected $args = array();

    /**
     * The index
     *
     * @var integer
     */
    protected $index = 0;

    /**
     * Constructor
     *
     * @param string $name Name of the function
     * @param array $args Array of arguments
     * @param integer $index The current index
     * @param ILess_FileInfo $currentFileInfo The current file info
     */
    public function __construct($name, array $args, $index = 0, ILess_FileInfo $currentFileInfo = null)
    {
        $this->name = $name;
        $this->args = $args;
        $this->index = $index;
        $this->currentFileInfo = $currentFileInfo;
    }

    /**
     * Accepts a visitor
     *
     * @param ILess_Visitor $visitor
     * @return void
     */
    public function accept(ILess_Visitor $visitor)
    {
        $this->args = $visitor->visit($this->args);
    }

    /**
     * Compiles the node
     *
     * @param ILess_Environment $env
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        // prepare arguments
        $args = array();
        foreach ($this->args as $arg) {
            $args[] = $arg->compile($env);
        }
        try {
            $result = $env->getFunctionRegistry()->call($this->name, $args);
            if ($result === null) {
                $result = new ILess_Node_Call($this->name, $args, $this->index, $this->currentFileInfo);
            }
        } catch (Exception $e) {
            throw new ILess_Exception_Function(sprintf('Error evaluating function `%s`', $this->name), $this->index, $this->currentFileInfo, $e);
        }

        return $result;
    }

    /**
     * @see ILess_Node::generateCSS
     */
    public function generateCSS(ILess_Environment $env, ILess_Output $output)
    {
        // handle IE filters, cannot accept shortened colors
        if (strpos($this->name, 'progid:') === 0) {
            $canShortenColors = $env->canShortenColors;
            $env->canShortenColors = false;
        }

        $output->add(sprintf('%s(', $this->name), $this->currentFileInfo, $this->index);
        for ($i = 0, $count = count($this->args); $i < $count; $i++) {
            $this->args[$i]->generateCSS($env, $output);
            if ($i + 1 < $count) {
                $output->add(', ');
            }
        }

        if (isset($canShortenColors)) {
            // put it back
            $env->canShortenColors = $canShortenColors;
        }

        $output->add(')');
    }

}

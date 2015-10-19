<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Node;

use ILess\Context;
use ILess\Exception\CompilerException;
use ILess\FileInfo;
use ILess\Node;

/**
 * Variable.
 */
class VariableNode extends Node
{
    /**
     * The name.
     *
     * @var string
     */
    public $name;

    /**
     * Current index.
     *
     * @var int
     */
    public $index = 0;

    /**
     * Evaluating flag.
     *
     * @var bool
     */
    protected $evaluating = false;

    /**
     * Constructor.
     *
     * @param string $name The variable name
     * @param int $index The current index
     * @param FileInfo $currentFileInfo The current file information
     */
    public function __construct($name, $index = 0, FileInfo $currentFileInfo = null)
    {
        $this->name = $name;
        $this->index = $index;
        $this->currentFileInfo = $currentFileInfo;
    }

    /**
     * Compiles the node.
     *
     * @param Context $context The context
     * @param array|null $arguments Array of arguments
     * @param bool|null $important Important flag
     *
     * @return Node
     *
     * @throws CompilerException
     */
    public function compile(Context $context, $arguments = null, $important = null)
    {
        $name = $this->name;
        if (strpos($name, '@@') === 0) {
            $v = new self(substr($name, 1), $this->index, $this->currentFileInfo);
            $name = '@' . $v->compile($context)->value;
        }

        if ($this->evaluating) {
            throw new CompilerException(
                sprintf('Recursive variable definition for %s', $name),
                $this->index, $this->currentFileInfo
            );
        }

        $this->evaluating = true;

        $variable = null;
        // variables from the API take precedence
        if ($context->customVariables &&
            $v = $context->customVariables->variable($name)
        ) {
            $variable = $v->value->compile($context);
        } else {
            // search for the variable
            foreach ($context->frames as $frame) {
                /* @var $frame RulesetNode */
                if ($v = $frame->variable($name)) {
                    /* @var $v RuleNode */
                    if ($v->important) {
                        $importantScope = &$context->importantScope[count($context->importantScope) - 1];
                        $importantScope['important'] = $v->important;
                    }

                    $variable = $v->value->compile($context);
                    break;
                }
            }
        }

        if ($variable) {
            $this->evaluating = false;

            return $variable;
        } else {
            throw new CompilerException(sprintf('variable %s is undefined', $name), $this->index,
                $this->currentFileInfo
            );
        }
    }
}

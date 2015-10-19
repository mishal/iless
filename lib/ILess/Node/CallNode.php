<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Node;

use ILess\Context;
use ILess\Exception\FunctionException;
use ILess\FileInfo;
use ILess\FunctionRegistry;
use ILess\Node;
use ILess\Output\OutputInterface;
use ILess\Visitor\VisitorInterface;

/**
 * Function call.
 */
class CallNode extends Node
{
    /**
     * Node type.
     *
     * @var string
     */
    protected $type = 'Call';

    /**
     * The function name.
     *
     * @var string
     */
    protected $name;

    /**
     * Array of arguments.
     *
     * @var array
     */
    protected $args = [];

    /**
     * The index.
     *
     * @var int
     */
    protected $index = 0;

    /**
     * Constructor.
     *
     * @param string $name Name of the function
     * @param array $args Array of arguments
     * @param int $index The current index
     * @param FileInfo $currentFileInfo The current file info
     */
    public function __construct($name, array $args, $index = 0, FileInfo $currentFileInfo = null)
    {
        $this->name = $name;
        $this->args = $args;
        $this->index = $index;
        $this->currentFileInfo = $currentFileInfo;
    }

    /**
     * {@inheritdoc}
     */
    public function accept(VisitorInterface $visitor)
    {
        if ($this->args) {
            $this->args = $visitor->visitArray($this->args);
        }
    }

    /**
     * Compiles the node.
     *
     * @param Context $context The context
     * @param array|null $arguments Array of arguments
     * @param bool|null $important Important flag
     *
     * @return CallNode|Node
     *
     * @throws FunctionException
     */
    public function compile(Context $context, $arguments = null, $important = null)
    {
        // prepare arguments
        $args = [];
        foreach ($this->args as $arg) {
            /* @var $arg Node */
            $args[] = $arg->compile($context);
        }

        try {
            $registry = $context->frames[0]->functionRegistry;
            /* @var $registry FunctionRegistry */
            $registry->setCurrentFile($this->currentFileInfo);
            $result = $registry->call($this->name, $args);

            if ($result === null) {
                $result = new self($this->name, $args, $this->index, $this->currentFileInfo);
            }
        } catch (\Exception $e) {
            throw new FunctionException(sprintf('Error evaluating function `%s`', $this->name), $this->index,
                $this->currentFileInfo, $e);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function generateCSS(Context $context, OutputInterface $output)
    {
        // handle IE filters, cannot accept shortened colors
        if (strpos($this->name, 'progid:') === 0) {
            $canShortenColors = $context->canShortenColors;
            $context->canShortenColors = false;
        }

        $output->add($this->name . '(', $this->currentFileInfo, $this->index);
        for ($i = 0, $count = count($this->args); $i < $count; ++$i) {
            $this->args[$i]->generateCSS($context, $output);
            if ($i + 1 < $count) {
                $output->add(', ');
            }
        }

        if (isset($canShortenColors)) {
            // put it back
            $context->canShortenColors = $canShortenColors;
        }

        $output->add(')');
    }
}

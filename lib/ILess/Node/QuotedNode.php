<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Node;

use ILess\Context;
use ILess\FileInfo;
use ILess\Node;
use ILess\Output\OutputInterface;
use ILess\Util;

/**
 * Quoted node.
 */
class QuotedNode extends Node implements ComparableInterface
{
    /**
     * The content.
     *
     * @var string
     */
    public $content;

    /**
     * The quote.
     *
     * @var string
     */
    public $quote;

    /**
     * Current index.
     *
     * @var int
     */
    public $index = 0;

    /**
     * Node type.
     *
     * @var string
     */
    protected $type = 'Quoted';

    /**
     * Escaped?
     *
     * @var bool
     */
    public $escaped = false;

    /**
     * Constructor.
     *
     * @param string $string The quote
     * @param string $content The string
     * @param bool $escaped Is the string escaped?
     * @param int $index Current index
     * @param FileInfo $currentFileInfo The current file info
     */
    public function __construct($string, $content = '', $escaped = false, $index = 0, FileInfo $currentFileInfo = null)
    {
        parent::__construct($content);

        $this->escaped = $escaped;

        $this->quote = isset($string[0]) ? $string[0] : '';
        $this->index = $index;
        $this->currentFileInfo = $currentFileInfo;
    }

    /**
     * {@inheritdoc}
     */
    public function generateCSS(Context $context, OutputInterface $output)
    {
        if (!$this->escaped) {
            $output->add($this->quote, $this->currentFileInfo, $this->index);
        }

        $output->add($this->value);

        if (!$this->escaped) {
            $output->add($this->quote);
        }
    }

    /**
     * @return bool
     */
    public function containsVariables()
    {
        return (bool) preg_match('/(`([^`]+)`)|@\{([\w-]+)\}/', $this->value);
    }

    /**
     * Compiles the node.
     *
     * @param Context $context The context
     * @param array|null $arguments Array of arguments
     * @param bool|null $important Important flag
     *
     * @return QuotedNode
     */
    public function compile(Context $context, $arguments = null, $important = null)
    {
        $value = $this->value;

        $iterativeReplace = function ($value, $regexp, $replacementFunc) {
            $evaluatedValue = $value;
            do {
                $value = $evaluatedValue;
                $evaluatedValue = preg_replace_callback($regexp, $replacementFunc, $value);
            } while ($value !== $evaluatedValue);

            return $evaluatedValue;
        };

        // javascript replacement
        $value = $iterativeReplace($value, '/`([^`]+)`/', function ($matches) use ($context) {
            $js = new JavascriptNode($matches[1], $this->index, true);

            return $js->compile($context)->value;
        });

        // interpolation replacement
        $value = $iterativeReplace($value, '/@\{([\w-]+)\}/', function ($matches) use (&$context) {
            $v = new VariableNode('@' . $matches[1], $this->index, $this->currentFileInfo);
            $canShorted = $context->canShortenColors;
            $context->canShortenColors = false;
            $v = $v->compile($context);
            $v = ($v instanceof QuotedNode) ? $v->value : $v->toCSS($context);
            $context->canShortenColors = $canShorted;

            return $v;
        });

        return new self($this->quote . $value . $this->quote, $value, $this->escaped, $this->index,
            $this->currentFileInfo);
    }

    /**
     * Compares with another node.
     *
     * @param Node $other
     *
     * @return int|null
     */
    public function compare(Node $other)
    {
        if ($other instanceof self && !$this->escaped && !$other->escaped) {
            return Util::numericCompare($this->value, $other->value);
        } else {
            $context = new Context();

            return $other->toCSS($context) === $this->toCSS($context) ? 0 : null;
        }
    }
}

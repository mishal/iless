<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Node;

use ILess\Context;
use ILess\Exception\Exception;
use ILess\Node;
use ILess\Output\OutputInterface;
use ILess\Visitor\VisitorInterface;

/**
 * Expression.
 */
class ExpressionNode extends Node implements MarkableAsReferencedInterface
{
    /**
     * Node type.
     *
     * @var string
     */
    protected $type = 'Expression';

    /**
     * Parens flag.
     *
     * @var bool
     */
    public $parens = false;

    /**
     * Parens in operator flag.
     *
     * @var bool
     */
    public $parensInOp = false;

    /**
     * Constructor.
     *
     * @param array $value
     *
     * @throws Exception
     */
    public function __construct(array $value)
    {
        parent::__construct($value);
    }

    /**
     * {@inheritdoc}
     */
    public function accept(VisitorInterface $visitor)
    {
        $this->value = $visitor->visitArray($this->value);
    }

    /**
     * Compiles the node.
     *
     * @param Context $context The context
     * @param array|null $arguments Array of arguments
     * @param bool|null $important Important flag
     *
     * @return ParenNode|ExpressionNode|Node
     */
    public function compile(Context $context, $arguments = null, $important = null)
    {
        $inParenthesis = $this->parens && !$this->parensInOp;
        $doubleParen = false;

        if ($inParenthesis) {
            $context->inParenthesis();
        }
        $count = count($this->value);
        if ($count > 1) {
            $compiled = [];
            foreach ($this->value as $v) {
                /* @var $v Node */
                $compiled[] = $v->compile($context);
            }
            $return = new self($compiled);
        } elseif ($count === 1) {
            if (property_exists($this->value[0], 'parens') && $this->value[0]->parens
                && property_exists($this->value[0], 'parensInOp') && !$this->value[0]->parensInOp
            ) {
                $doubleParen = true;
            }
            $return = $this->value[0]->compile($context);
        } else {
            $return = $this;
        }

        if ($inParenthesis) {
            $context->outOfParenthesis();
        }

        if ($this->parens && $this->parensInOp && !($context->isMathOn()) && !$doubleParen) {
            $return = new ParenNode($return);
        }

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function generateCSS(Context $context, OutputInterface $output)
    {
        for ($i = 0, $count = count($this->value); $i < $count; ++$i) {
            $this->value[$i]->generateCSS($context, $output);
            if ($i + 1 < $count) {
                $output->add(' ');
            }
        }
    }

    public function throwAwayComments()
    {
        if (is_array($this->value)) {
            $new = [];
            foreach ($this->value as $v) {
                if ($v instanceof CommentNode) {
                    continue;
                }
                $new[] = $v;
            }
            $this->value = $new;
        }
    }

    /**
     * Marks as referenced.
     */
    public function markReferenced()
    {
        foreach ($this->value as $value) {
            if ($value instanceof MarkableAsReferencedInterface) {
                $value->markReferenced();
            }
        }
    }
}

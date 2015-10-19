<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Node;

use ILess\Context;
use ILess\Node;
use ILess\Visitor\VisitorInterface;

/**
 * Extend.
 */
class ExtendNode extends Node
{
    /**
     * Node type.
     *
     * @var string
     */
    protected $type = 'Extend';

    /**
     * The selector.
     *
     * @var SelectorNode
     */
    public $selector;

    /**
     * The option (all).
     *
     * @var string
     */
    public $option;

    /**
     * Current index.
     *
     * @var int
     */
    public $index = 0;

    /**
     * Allow before flag.
     *
     * @var bool
     */
    public $allowBefore = false;

    /**
     * Allow after flag.
     *
     * @var bool
     */
    public $allowAfter = false;

    /**
     * Array of self selectors.
     *
     * @var array
     */
    public $selfSelectors = [];

    /**
     * First extend on this path flag.
     *
     * @var bool
     *
     * @see Visitor_ExtendFinder::visitRuleset
     */
    public $firstExtendOnThisSelectorPath = false;

    /**
     * The ruleset.
     *
     * @var RulesetNode
     */
    public $ruleset;

    /**
     * @var int
     */
    public static $nextId = 0;

    /**
     * @var int
     */
    public $objectId = 0;

    /**
     * @var bool
     */
    public $hasFoundMatches = false;

    /**
     * Constructor.
     *
     * @param SelectorNode $selector The selector
     * @param string $option The option
     * @param int $index The index
     */
    public function __construct(SelectorNode $selector, $option, $index = 0)
    {
        $this->selector = $selector;
        $this->option = $option;
        $this->index = $index;
        $this->objectId = self::$nextId++;
        $this->parentIds = [$this->objectId];

        switch ($option) {
            case 'all':
                $this->allowBefore = true;
                $this->allowAfter = true;
                break;
            default:
                $this->allowBefore = false;
                $this->allowAfter = false;
                break;
        }
    }

    /**
     * @var array
     */
    public $parentIds = [];

    /**
     * {@inheritdoc}
     */
    public function accept(VisitorInterface $visitor)
    {
        $this->selector = $visitor->visit($this->selector);
    }

    /**
     * Compiles the node.
     *
     * @param Context $context The context
     * @param array|null $arguments Array of arguments
     * @param bool|null $important Important flag
     *
     * @return ExtendNode
     */
    public function compile(Context $context, $arguments = null, $important = null)
    {
        return new self($this->selector->compile($context), $this->option, $this->index);
    }

    /**
     * Finds selectors.
     *
     * @param array $selectors Array of ILess\ILess\Node\SelectorNode instances
     */
    public function findSelfSelectors($selectors)
    {
        $selfElements = [];
        for ($i = 0; $i < count($selectors); ++$i) {
            $selectorElements = $selectors[$i]->elements;
            if ($i > 0 && count($selectorElements) && $selectorElements[0]->combinator->value === '') {
                $selectorElements[0]->combinator->value = ' ';
            }
            $selfElements = array_merge($selfElements, $selectors[$i]->elements);
        }

        $this->selfSelectors = [
            (object) ['elements' => (array) $selfElements],
        ];
    }
}

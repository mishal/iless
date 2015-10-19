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
use ILess\Visitor\VisitorInterface;

/**
 * Selector.
 */
class SelectorNode extends Node implements MarkableAsReferencedInterface, ReferencedInterface
{
    /**
     * Node type.
     *
     * @var string
     */
    protected $type = 'Selector';

    /**
     * Array of elements.
     *
     * @var array
     */
    public $elements = [];

    /**
     * Array of extend definitions.
     *
     * @var array
     */
    public $extendList = [];

    /**
     * The condition.
     *
     * @var ConditionNode
     */
    public $condition;

    /**
     * Current index.
     *
     * @var int
     */
    public $index = 0;

    /**
     * Referenced flag.
     *
     * @var bool
     */
    public $isReferenced = false;

    /**
     * Compiled condition.
     *
     * @var bool
     */
    public $compiledCondition = false;

    /**
     * @var bool
     */
    public $mediaEmpty = false;

    /**
     * @var array|null
     */
    public $cachedElements;

    /**
     * Constructor.
     *
     * @param array $elements Array of elements
     * @param array $extendList Extended list
     * @param ConditionNode $condition The condition
     * @param int $index Current index
     * @param array $currentFileInfo Current file information
     * @param bool $isReferenced Referenced flag
     */
    public function __construct(
        array $elements,
        array $extendList = [],
        ConditionNode $condition = null,
        $index = 0,
        FileInfo $currentFileInfo = null,
        $isReferenced = false
    ) {
        $this->elements = $elements;
        $this->extendList = $extendList;
        $this->index = $index;
        $this->currentFileInfo = $currentFileInfo;
        $this->isReferenced = $isReferenced;
        $this->condition = $condition;
        if (!$condition) {
            $this->compiledCondition = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function accept(VisitorInterface $visitor)
    {
        $this->elements = $visitor->visitArray($this->elements);
        $this->extendList = $visitor->visitArray($this->extendList);

        if ($this->condition) {
            $this->condition = $visitor->visit($this->condition);
        }
    }

    /**
     * Compiles the node.
     *
     * @param Context $context The context
     * @param array|null $arguments Array of arguments
     * @param bool|null $important Important flag
     *
     * @return SelectorNode
     */
    public function compile(Context $context, $arguments = null, $important = null)
    {
        $elements = $extendList = [];
        // compile elements
        foreach ($this->elements as $e) {
            $elements[] = $e->compile($context);
        }
        // compile extended list
        foreach ($this->extendList as $e) {
            $extendList[] = $e->compile($context);
        }
        // compile condition
        $compiledCondition = null;
        if ($this->condition) {
            $compiledCondition = $this->condition->compile($context);
        }

        return $this->createDerived($elements, $extendList, $compiledCondition);
    }

    /**
     * Creates derived selector from given arguments.
     *
     * @param array $elements Array of elements
     * @param array $extendList Array of extends definitions
     * @param bool|ConditionNode $compiledCondition Compiled condition
     *
     * @return SelectorNode
     */
    public function createDerived(array $elements, array $extendList = [], $compiledCondition = null)
    {
        $compiledCondition = !is_null($compiledCondition) ? $compiledCondition : $this->compiledCondition;
        $selector = new self($elements, count($extendList) ? $extendList : $this->extendList, null,
            $this->index, $this->currentFileInfo, $this->isReferenced);
        $selector->compiledCondition = $compiledCondition;
        $selector->mediaEmpty = $this->mediaEmpty;

        return $selector;
    }

    /**
     * Returns an array of default selectors.
     *
     * @return array
     */
    public function createEmptySelectors()
    {
        $element = new ElementNode('', '&', $this->index, $this->currentFileInfo);

        $selector = new self([$element], [], null, $this->index, $this->currentFileInfo);
        $selector->mediaEmpty = true;

        return [$selector];
    }

    /**
     * {@inheritdoc}
     */
    public function generateCSS(Context $context, OutputInterface $output)
    {
        if (!$context->firstSelector && $this->elements[0]->combinator->value === '') {
            $output->add(' ', $this->currentFileInfo, $this->index);
        }

        foreach ($this->elements as $e) {
            /* @var $e ElementNode */
            $e->generateCSS($context, $output);
        }
    }

    /**
     * @see Node_MarkableAsReferencedInterface::markReferenced
     */
    public function markReferenced()
    {
        $this->isReferenced = true;
    }

    /**
     * Is referenced?
     *
     * @return bool
     */
    public function getIsReferenced()
    {
        return !$this->currentFileInfo || !$this->currentFileInfo->reference || $this->isReferenced;
    }

    /**
     * Returns the condition.
     *
     * @return bool|ConditionNode
     */
    public function getIsOutput()
    {
        return $this->compiledCondition;
    }

    /**
     * Matches with other node?
     *
     * @param SelectorNode $other
     *
     * @return int
     */
    public function match(SelectorNode $other)
    {
        $other->cacheElements();

        $count = count($this->elements);
        $olen = count($other->cachedElements);
        if ($olen === 0 || $count < $olen) {
            return 0;
        } else {
            for ($i = 0; $i < $olen; ++$i) {
                if ($this->elements[$i]->value !== $other->cachedElements[$i]) {
                    return 0;
                }
            }
        }

        return $olen; // return number of matched elements
    }

    /**
     * Cache elements.
     */
    public function cacheElements()
    {
        if (null !== $this->cachedElements) {
            return;
        }

        $css = '';
        foreach ($this->elements as $element) {
            /* @var $element ElementNode */
            $css .= $element->combinator . ($element->value instanceof Node ? $element->value->value : $element->value);
        }

        if (preg_match_all('/[,&#\*\.\w-](?:[\w-]|(?:\\\\.))*/', $css, $matches)) {
            $elements = $matches[0];
            if ($elements[0] === '&') {
                array_shift($elements);
            }
        } else {
            $elements = [];
        }

        $this->cachedElements = $elements;
    }

    /**
     * @return bool
     */
    public function isJustParentSelector()
    {
        return !$this->mediaEmpty && count($this->elements) === 1 && $this->elements[0]->value === '&' &&
        ($this->elements[0]->combinator->value === ' ' || $this->elements[0]->combinator->value === '');
    }
}

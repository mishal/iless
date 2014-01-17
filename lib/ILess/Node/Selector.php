<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Selector
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_Selector extends ILess_Node implements ILess_Node_VisitableInterface, ILess_Node_MarkableAsReferencedInterface
{
    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'Selector';

    /**
     * Array of elements
     *
     * @var array
     */
    public $elements = array();

    /**
     * Array of extend definitions
     *
     * @var array
     */
    public $extendList = array();

    /**
     * The condition
     *
     * @var ILess_Node_Condition
     */
    public $condition;

    /**
     * Current index
     *
     * @var integer
     */
    public $index = 0;

    /**
     * Referenced flag
     *
     * @var boolean
     */
    public $isReferenced = false;

    /**
     * Compiled condition
     *
     * @var boolean
     */
    public $compiledCondition = false;

    /**
     * Constructor
     *
     * @param array $elements Array of elements
     * @param array $extendList Extended list
     * @param ILess_Node_Condition $condition The condition
     * @param integer $index Current index
     * @param array $currentFileInfo Current file information
     * @param boolean $isReferenced Referenced flag
     */
    public function __construct(array $elements, array $extendList = array(), ILess_Node_Condition $condition = null, $index = 0, ILess_FileInfo $currentFileInfo = null, $isReferenced = false)
    {
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
     * Accepts a visit
     *
     * @param ILess_Visitor $visitor
     */
    public function accept(ILess_Visitor $visitor)
    {
        $this->elements = $visitor->visit($this->elements);
        $this->extendList = $visitor->visit($this->extendList);
        $this->condition = $visitor->visit($this->condition);
    }

    /**
     * @see ILess_Node
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        $elements = $extendList = array();
        // compile elements
        foreach ($this->elements as $e) {
            $elements[] = $e->compile($env);
        }
        // compile extended list
        foreach ($this->extendList as $e) {
            $extendList[] = $e->compile($env);
        }
        // compile condition
        $compiledCondition = null;
        if ($this->condition) {
            $compiledCondition = $this->condition->compile($env);
        }

        return $this->createDerived($elements, $extendList, $compiledCondition);
    }

    /**
     * Creates a dderived selector from given arguments
     *
     * @param array $elements Array of elements
     * @param array $extendList Array of extends definitions
     * @param boolean|ILess_Node_Condition $compiledCondition Compiled condition
     * @return ILess_Node_Selector
     */
    public function createDerived(array $elements, array $extendList = array(), $compiledCondition = null)
    {
        $compiledCondition = !is_null($compiledCondition) ? $compiledCondition : $this->compiledCondition;
        $selector = new ILess_Node_Selector($elements, count($extendList) ? $extendList : $this->extendList, $this->condition, $this->index, $this->currentFileInfo, $this->isReferenced);
        $selector->compiledCondition = $compiledCondition;

        return $selector;
    }

    /**
     * @see ILess_Node::generateCSS
     */
    public function generateCSS(ILess_Environment $env, ILess_Output $output)
    {
        if (!$env->firstSelector && $this->elements[0]->combinator->value === '') {
            $output->add(' ', $this->currentFileInfo, $this->index);
        }

        foreach ($this->elements as $e) {
            /* @var $e ILess_Node_Element */
            $e->generateCSS($env, $output);
        }
    }

    /**
     * @see ILess_Node_MarkableAsReferencedInterface::markReferenced
     */
    public function markReferenced()
    {
        $this->isReferenced = true;
    }

    /**
     * Is referenced?
     *
     * @return boolean
     */
    public function getIsReferenced()
    {
        return !$this->currentFileInfo || !$this->currentFileInfo->reference || $this->isReferenced;
    }

    /**
     * Returns the condition
     *
     * @return boolean|ILess_Node_Condition
     */
    public function getIsOutput()
    {
        return $this->compiledCondition;
    }

    /**
     * Matches with other node?
     *
     * @param ILess_Node_Selector $other
     * @return integer
     */
    public function match(ILess_Node_Selector $other)
    {
        if (!$other) {
            return 0;
        }

        $offset = 0;
        $olen = count($other->elements);
        if ($olen) {
            if ($other->elements[0]->value === '&') {
                $offset = 1;
            }
            $olen -= $offset;
        }

        if ($olen === 0) {
            return 0;
        }

        $len = count($this->elements);
        if ($len < $olen) {
            return 0;
        }

        $max = min($len, $olen);

        for ($i = 0; $i < $max; $i++) {
            if ($this->elements[$i]->value !== $other->elements[$i + $offset]->value) {
                return 0;
            }
        }

        return $max; // return number of matched selectors
    }

}

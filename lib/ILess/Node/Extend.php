<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Extend
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_Extend extends ILess_Node implements ILess_Node_VisitableInterface
{
    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'Extend';

    /**
     * The selector
     *
     * @var ILess_Node_Selector
     */
    public $selector;

    /**
     * The option (all)
     *
     * @var string
     */
    public $option;

    /**
     * Current index
     *
     * @var integer
     */
    public $index = 0;

    /**
     * Allow before flag
     *
     * @var boolean
     */
    public $allowBefore = false;

    /**
     * Allow after flag
     *
     * @var boolean
     */
    public $allowAfter = false;

    /**
     * Array of self selectors
     *
     * @var array
     */
    public $selfSelectors = array();

    /**
     * Extend on every path flag
     *
     * @var boolean
     * @see ILess_Visitor_ExtendFinder::visitRuleset
     */
    public $extendOnEveryPath = false;

    /**
     * First extend on this path flag
     *
     * @var boolean
     * @see ILess_Visitor_ExtendFinder::visitRuleset
     */
    public $firstExtendOnThisSelectorPath = false;

    /**
     * Parents
     *
     * @var array
     * @see ILess_Visitor_ProcessExtend::inInheritanceChain
     */
    public $parents;

    /**
     * The ruleset
     *
     * @var ILess_Node_Ruleset
     */
    public $ruleset;

    /**
     * Constructor
     *
     * @param ILess_Node_Selector $selector The selector
     * @param string $option The option
     * @param integer $index The index
     */
    public function __construct(ILess_Node_Selector $selector, $option, $index = 0)
    {
        $this->selector = $selector;
        $this->option = $option;
        $this->index = $index;
        if ($option == 'all') {
            $this->allowBefore = true;
            $this->allowAfter = true;
        }
    }

    /**
     * Accepts a visit
     *
     * @param ILess_Visitor $visitor
     */
    public function accept(ILess_Visitor $visitor)
    {
        $this->selector = $visitor->visit($this->selector);
    }

    /**
     * @see ILess_Node
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        return new ILess_Node_Extend($this->selector->compile($env), $this->option, $this->index);
    }

    /**
     * @see ILess_Node::generateCSS
     */
    public function generateCSS(ILess_Environment $env, ILess_Output $output)
    {
    }

    /**
     * Finds selectors
     *
     * @param array $selectors Array of ILess_Node_Selector instances
     */
    public function findSelfSelectors($selectors)
    {
        $selfElements = array();
        for ($i = 0, $count = count($selectors); $i < $count; $i++) {
            $selectorElements = $selectors[$i]->elements;
            if ($i > 0 && count($selectorElements) && $selectorElements[0]->combinator->value == '') {
                $selectorElements[0]->combinator->value = ' ';
            }
            $selfElements = array_merge($selfElements, $selectors[$i]->elements);
        }
        $this->selfSelectors = array(new ILess_Node_Selector($selfElements));
    }

}

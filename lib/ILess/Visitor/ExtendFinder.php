<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * ExtendFinder Visitor
 *
 * @package ILess
 * @subpackage visitor
 */
class ILess_Visitor_ExtendFinder extends ILess_Visitor
{
    /**
     * @var array
     */
    protected $contexts = array();

    /**
     * @var array
     */
    protected $allExtendsStack = array(array());

    /**
     * Found extends flag
     *
     * @var boolean
     */
    public $foundExtends = false;

    /**
     * @see ILess_Visitor::run
     */
    public function run($root)
    {
        $root = $this->visit($root);
        if (is_object($root)) {
            $root->allExtends = & $this->allExtendsStack[0];
        }

        return $root;
    }

    /**
     * Visits a rule node
     *
     * @param ILess_Node_Rule $node The node
     * @param ILess_Visitor_Arguments $arguments The arguments
     */
    public function visitRule(ILess_Node_Rule $node, ILess_Visitor_Arguments $arguments)
    {
        $arguments->visitDeeper = false;
    }

    /**
     * Visits a mixin definition node
     *
     * @param ILess_Node_MixinDefinition $node The node
     * @param ILess_Visitor_Arguments $arguments The arguments
     */
    public function visitMixinDefinition(ILess_Node_MixinDefinition $node, ILess_Visitor_Arguments $arguments)
    {
        $arguments->visitDeeper = false;
    }

    /**
     * Visits a ruleset node
     *
     * @param ILess_Node_Ruleset $node The node
     * @param ILess_Visitor_Arguments $arguments The arguments
     */
    public function visitRuleset(ILess_Node_Ruleset $node, ILess_Visitor_Arguments $arguments)
    {
        if ($node->root) {
            return;
        }

        $allSelectorsExtendList = array();
        // get &:extend(.a); rules which apply to all selectors in this ruleset
        for ($i = 0, $count = count($node->rules); $i < $count; $i++) {
            if ($node->rules[$i] instanceof ILess_Node_Extend) {
                $allSelectorsExtendList[] = $node->rules[$i];
                $node->extendOnEveryPath = true;
            }
        }

        // now find every selector and apply the extends that apply to all extends
        // and the ones which apply to an individual extend
        for ($i = 0, $count = count($node->paths); $i < $count; $i++) {
            $selectorPath = $node->paths[$i];
            $selector = end($selectorPath);
            $list = array_merge($selector->extendList, $allSelectorsExtendList);
            $extendList = array();
            foreach ($list as $allSelectorsExtend) {
                $extendList[] = clone $allSelectorsExtend;
            }

            for ($j = 0, $extendsCount = count($extendList); $j < $extendsCount; $j++) {
                $this->foundExtends = true;
                $extend = $extendList[$j];
                $extend->findSelfSelectors($selectorPath);
                $extend->ruleset = $node;
                if ($j === 0) {
                    $extend->firstExtendOnThisSelectorPath = true;
                }
                $temp = count($this->allExtendsStack) - 1;
                $this->allExtendsStack[$temp][] = $extend;
            }
        }
        $this->contexts[] = $node->selectors;
    }

    /**
     * Visits the ruleset (again!)
     *
     * @param ILess_Node_Ruleset $node The node
     * @param ILess_Visitor_Arguments $arguments The arguments
     */
    public function visitRulesetOut(ILess_Node_Ruleset $node, ILess_Visitor_Arguments $arguments)
    {
        if (!is_object($node) || !$node->root) {
            array_pop($this->contexts);
        }
    }

    /**
     * Visits a media node
     *
     * @param ILess_Node_Media $node
     * @param ILess_Visitor_Arguments $arguments The arguments
     */
    public function visitMedia(ILess_Node_Media $node, ILess_Visitor_Arguments $argument)
    {
        $node->allExtends = array();
        $this->allExtendsStack[] = & $node->allExtends;
    }

    /**
     * Visits a media node (again!)
     *
     * @param ILess_Node_Media $node
     * @param ILess_Visitor_Arguments $arguments The arguments
     */
    public function visitMediaOut(ILess_Node_Media $node, ILess_Visitor_Arguments $argument)
    {
        array_pop($this->allExtendsStack);
    }

    /**
     * Visits a directive node
     *
     * @param ILess_Node_Directive $node The node
     * @param ILess_Visitor_Arguments $arguments The arguments
     * @return ILess_Node_Directive
     */
    public function visitDirective(ILess_Node_Directive $node, ILess_Visitor_Arguments $arguments)
    {
        $node->allExtends = array();
        $this->allExtendsStack[] = & $node->allExtends;
    }

    /**
     * Visits a directive node (again!)
     *
     * @param ILess_Node_Directive $node The node
     * @param ILess_Visitor_Arguments $arguments The arguments
     * @return ILess_Node_Directive
     */
    public function visitDirectiveOut(ILess_Node_Directive $node, ILess_Visitor_Arguments $arguments)
    {
        array_pop($this->allExtendsStack);
    }

}

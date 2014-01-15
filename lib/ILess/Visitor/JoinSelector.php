<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Join Selector Visitor
 *
 * @package ILess
 * @subpackage visitor
 */
class ILess_Visitor_JoinSelector extends ILess_Visitor
{
    /**
     * Array of contexts
     *
     * @var array
     */
    protected $contexts = array(array());

    /**
     * @see ILess_Visitor::run
     */
    public function run($root)
    {
        return $this->visit($root);
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
        $paths = array();
        if (!$node->root) {
            $selectors = array();
            foreach ($node->selectors as $selector) {
                if ($selector->getIsOutput()) {
                    $selectors[] = $selector;
                }
            }

            if (!count($selectors)) {
                $node->selectors = array();
                $node->rules = array();
            } else {
                $context = end($this->contexts);
                $paths = $node->joinSelectors($context, $selectors);
            }

            $node->paths = $paths;
        }

        $this->contexts[] = $paths;
    }

    /**
     * Visits the ruleset (again!)
     *
     * @param ILess_Node_Ruleset $node The node
     * @param ILess_Visitor_Arguments $arguments The arguments
     */
    public function visitRulesetOut(ILess_Node_Ruleset $node, ILess_Visitor_Arguments $arguments)
    {
        array_pop($this->contexts);
    }

    /**
     * Visits a media node
     *
     * @param ILess_Node_Media $node
     * @param ILess_Visitor_Arguments $arguments The arguments
     */
    public function visitMedia(ILess_Node_Media $node, ILess_Visitor_Arguments $argument)
    {
        $context = end($this->contexts);
        if (!count($context) || (is_object($context[0]) && $context[0]->multiMedia)) {
            $node->rules[0]->root = true;
        }
    }

}

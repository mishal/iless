<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Ruleset
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_Ruleset extends ILess_Node implements ILess_Node_VisitableInterface, ILess_Node_MarkableAsReferencedInterface, ILess_Node_MakeableImportantInterface
{
    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'Ruleset';

    /**
     * Array of lookups
     *
     * @var array
     */
    protected $lookups = array();

    /**
     * Ruleset paths like: `#foo #bar`
     *
     * @var array
     */
    public $paths = array();

    /**
     * Strict imports flag
     *
     * @var boolean
     */
    public $strictImports = false;

    /**
     * Array of selectors
     *
     * @var array
     */
    public $selectors = array();

    /**
     * Is first root?
     *
     * @var boolean
     */
    public $firstRoot = false;

    /**
     * Is root?
     *
     * @var boolean
     */
    public $root = false;

    /**
     * Variables cache array
     *
     * @var array
     * @see resetCache
     */
    protected $variables;

    /**
     * Internal flag. Selectors are referenced
     *
     * @var boolean
     * @see markAsReferenced
     */
    protected $isReferenced = false;

    /**
     * Array of rules
     *
     * @var array
     */
    public $rules;

    /**
     * Allow imports flag
     *
     * @var boolean
     */
    public $allowImports = false;

    /**
     * Multi media flag
     *
     * @var boolean
     */
    public $multiMedia = false;

    /**
     * Array of extends
     *
     * @var array
     */
    public $allExtends;

    /**
     * Ruleset increment
     *
     * @var integer
     */
    private static $rulesetIncrement = 0;

    /**
     * Original ruleset id
     *
     * @var integer
     */
    public $originalRulesetId;

    /**
     * The id
     *
     * @var integer
     */
    public $rulesetId;

    /**
     * Constructor
     *
     * @param array $selectors Array of selectors
     * @param array $rules Array of rules
     * @param boolean $strictImports Strict imports?
     */
    public function __construct(array $selectors, array $rules, $strictImports = false)
    {
        $this->selectors = $selectors;
        $this->rules = $rules;
        $this->strictImports = (boolean)$strictImports;
        $this->setRulesetId();
    }

    /**
     * Sets ruleset identifier
     *
     * @return void
     */
    public function setRulesetId()
    {
        $this->rulesetId = self::$rulesetIncrement++;
        $this->originalRulesetId = $this->rulesetId;
    }

    /**
     * Accepts a visit
     *
     * @param ILess_Visitor $visitor
     */
    public function accept(ILess_Visitor $visitor)
    {
        if (count($this->paths)) {
            for ($i = 0, $count = count($this->paths); $i < $count; $i++) {
                $this->paths[$i] = $visitor->visit($this->paths[$i]);
            }
        } else {
            $this->selectors = $visitor->visit($this->selectors);
        }
        $this->rules = $visitor->visit($this->rules);
    }

    /**
     * @see ILess_Node
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        // compile selectors
        $selectors = array();
        foreach ($this->selectors as $s) {
            if (self::methodExists($s, 'compile')) {
                $selectors[] = $s->compile($env);
            }
        }

        $ruleset = new ILess_Node_Ruleset($selectors, $this->rules, $this->strictImports);
        $ruleset->originalRulesetId = $this->originalRulesetId;
        $ruleset->root = $this->root;
        $ruleset->firstRoot = $this->firstRoot;
        $ruleset->allowImports = $this->allowImports;

        if ($this->debugInfo) {
            $ruleset->debugInfo = $this->debugInfo;
        }

        // push the current ruleset to the frames stack
        $env->unshiftFrame($ruleset);

        // currrent selectors
        array_unshift($env->selectors, $this->selectors);

        // Evaluate imports
        if ($ruleset->root || $ruleset->allowImports || !$ruleset->strictImports) {
            $ruleset->compileImports($env);
        }

        // Store the frames around mixin definitions,
        // so they can be evaluated like closures when the time comes.
        foreach ($ruleset->rules as $i => $rule) {
            if ($rule instanceof ILess_Node_MixinDefinition) {
                $ruleset->rules[$i]->frames = $env->frames;
            }
        }

        $mediaBlockCount = count($env->mediaBlocks);
        // Evaluate mixin calls.
        $ruleset_len = count($ruleset->rules);
        for ($i = 0; $i < $ruleset_len; $i++) {
            $rule = $ruleset->rules[$i];

            if ($rule instanceof ILess_Node_MixinCall) {
                $rules = $rule->compile($env);
                $temp = array();
                foreach ($rules as $r) {
                    if (($r instanceof ILess_Node_Rule) && $r->variable) {
                        // do not pollute the scope if the variable is
                        // already there. consider returning false here
                        // but we need a way to "return" variable from mixins
                        if (!$ruleset->variable($r->name)) {
                            $temp[] = $r;
                        }
                    } else {
                        $temp[] = $r;
                    }
                }
                $rules = $temp;
                array_splice($ruleset->rules, $i, 1, $rules);
                $ruleset_len = count($ruleset->rules);
                $i += count($rules) - 1;
                $ruleset->resetCache();
            }
        }

        // Evaluate everything else
        for ($i = 0, $count = count($ruleset->rules); $i < $count; $i++) {
            if (!($ruleset->rules[$i] instanceof ILess_Node_MixinDefinition)) {
                $ruleset->rules[$i] = self::methodExists($ruleset->rules[$i], 'compile') ?
                    $ruleset->rules[$i]->compile($env) : $ruleset->rules[$i];
            }
        }

        // Pop the stack
        $env->shiftFrame();
        array_shift($env->selectors);

        if ($mediaBlockCount) {
            for ($i = $mediaBlockCount, $count = count($env->mediaBlocks); $i < $count; $i++) {
                $env->mediaBlocks[$i]->bubbleSelectors($selectors);
            }
        }

        return $ruleset;
    }

    /**
     * @see ILess_Node::generateCSS
     */
    public function generateCSS(ILess_Environment $env, ILess_Output $output)
    {
        if (!$this->root) {
            $env->tabLevel++;
        }

        $tabRuleStr = $tabSetStr = '';
        if (!$env->compress && $env->tabLevel) {
            $tabRuleStr = str_repeat('  ', $env->tabLevel);
            $tabSetStr = str_repeat('  ', $env->tabLevel - 1);
        }

        $ruleNodes = $rulesetNodes = array();
        for ($i = 0, $rulesCount = count($this->rules); $i < $rulesCount; $i++) {
            $rule = $this->rules[$i];
            if ((self::propertyExists($rule, 'rules') && $rule->rules) ||
                $rule instanceof ILess_Node_Media ||
                $rule instanceof ILess_Node_Directive ||
                ($this->root && $rule instanceof ILess_Node_Comment)
            ) {
                $rulesetNodes[] = $rule;
            } else {
                $ruleNodes[] = $rule;
            }
        }

        // If this is the root node, we don't render
        // a selector, or {}.
        if (!$this->root) {
            if ($this->debugInfo) {
                // debug?
                $debugInfo = self::getDebugInfo($env, $this, $tabSetStr);
                if ($debugInfo) {
                    $output->add($debugInfo)
                        ->add($tabSetStr);
                }
            }

            for ($i = 0, $count = count($this->paths); $i < $count; $i++) {
                $path = $this->paths[$i];
                $env->firstSelector = true;

                for ($j = 0, $pathCount = count($path); $j < $pathCount; $j++) {
                    $path[$j]->generateCSS($env, $output);
                    $env->firstSelector = false;
                }

                if ($i + 1 < $count) {
                    $output->add($env->compress ? ',' : (",\n" . $tabSetStr));
                }
            }

            $output->add(($env->compress ? '{' : " {\n") . $tabRuleStr);
        }

        $rulesetNodesCount = count($rulesetNodes);
        $ruleNodesCount = count($ruleNodes);

        // Compile rules and rulesets
        for ($i = 0; $i < $ruleNodesCount; $i++) {
            $rule = $ruleNodes[$i];
            // @page{ directive ends up with root elements inside it, a mix of rules and rulesets
            // In this instance we do not know whether it is the last property
            if ($i + 1 === $ruleNodesCount && (!$this->root || $rulesetNodesCount === 0 || $this->firstRoot)) {
                $env->lastRule = true;
            }

            $rule->generateCSS($env, $output);

            if (!$env->lastRule) {
                $output->add($env->compress ? '' : ("\n" . $tabRuleStr));
            } else {
                $env->lastRule = false;
            }
        }

        if (!$this->root) {
            $output->add($env->compress ? '}' : ("\n" . $tabSetStr . '}'));
            $env->tabLevel--;
        }

        $firstRuleset = true;
        for ($i = 0; $i < $rulesetNodesCount; $i++) {
            if ($ruleNodesCount && $firstRuleset) {
                $output->add($env->compress ? '' : "\n" . ($this->root ? $tabRuleStr : $tabSetStr));
            }
            if (!$firstRuleset) {
                $output->add($env->compress ? '' : "\n" . ($this->root ? $tabRuleStr : $tabSetStr));
            }
            $firstRuleset = false;
            $rulesetNodes[$i]->generateCSS($env, $output);
        }

        if (!$output->isEmpty() && !$env->compress && $this->firstRoot) {
            $output->add("\n");
        }
    }

    /**
     * Marks as referenced
     *
     */
    public function markReferenced()
    {
        foreach ($this->selectors as $s) {
            $s->markReferenced();
        }
        $this->isReferenced = true;
    }

    /**
     * Is referenced?
     *
     * @return boolean
     */
    public function isReferenced()
    {
        return $this->isReferenced;
    }

    /**
     * Compiles the imports
     *
     * @param ILess_Environment $env
     */
    public function compileImports(ILess_Environment $env)
    {
        for ($i = 0; $i < count($this->rules); $i++) {
            $rule = $this->rules[$i];
            if (!($rule instanceof ILess_Node_Import)) {
                continue;
            }

            $rules = $rule->compile($env);
            if (is_array($rules)) {
                array_splice($this->rules, $i, 1, $rules);
            } else {
                array_splice($this->rules, $i, 1, array($rules));
            }

            if (count($rules)) {
                $i += count($rules) - 1;
            }

            $this->resetCache();
        }
    }

    /**
     * Returns ruleset with nodes marked as important
     *
     * @return ILess_Node_Ruleset
     */
    public function makeImportant()
    {
        $importantRules = array();
        foreach ($this->rules as $rule) {
            if ($rule instanceof ILess_Node_MakeableImportantInterface) {
                $importantRules[] = $rule->makeImportant();
            } else {
                $importantRules[] = $rule;
            }
        }

        return new ILess_Node_Ruleset($this->selectors, $importantRules, $this->strictImports);
    }

    /**
     * Match arguments
     *
     * @param array $args
     * @param ILess_Environment $env
     * @return boolean
     */
    public function matchArgs(array $args, ILess_Environment $env)
    {
        return !is_array($args) || count($args) === 0;
    }

    /**
     * Match condition
     *
     * @param array $arguments
     * @param ILess_Environment $env
     * @return boolean
     */
    public function matchCondition(array $arguments, ILess_Environment $env)
    {
        $lastSelector = end($this->selectors);
        if ($lastSelector->condition &&
            !$lastSelector->condition->compile(ILess_Environment::createCopy($env, $env->frames))
        ) {
            return false;
        }

        return true;
    }

    /**
     * Resets the cache for variables and lookups
     *
     * @return void
     */
    public function resetCache()
    {
        $this->variables = null;
        $this->lookups = array();
    }

    /**
     * Returns an array of variables
     *
     * @return array
     */
    public function variables()
    {
        if ($this->variables === null) {
            $this->variables = array();
            foreach ($this->rules as $r) {
                if ($r instanceof ILess_Node_Rule && $r->variable == true) {
                    $this->variables[$r->name] = $r;
                }
            }
        }

        return $this->variables;
    }

    /**
     * Returns the variable by name
     *
     * @param string $name
     * @return ILess_Node_Rule
     */
    public function variable($name)
    {
        $vars = $this->variables();

        return isset($vars[$name]) ? $vars[$name] : null;
    }

    /**
     * Returns an array of rulesets
     *
     * @return array
     */
    public function rulesets()
    {
        $result = array();
        foreach ($this->rules as $rule) {
            if ($rule instanceof ILess_Node_Ruleset || $rule instanceof ILess_Node_MixinDefinition) {
                $result[] = $rule;
            }
        }

        return $result;
    }

    /**
     * Finds a selector
     *
     * @param ILess_Node $selector
     * @param ILess_Node_Ruleset $self
     * @param ILess_Environment $env
     * @return array
     */
    public function find(ILess_Node $selector, ILess_Environment $env, ILess_Node_Ruleset $self = null)
    {
        $key = $selector->toCSS($env);
        if (!$self) {
            $self = $this;
        }

        if (!array_key_exists($key, $this->lookups)) {
            $this->lookups[$key] = array();
            foreach ($this->rules as $rule) {
                if ($rule === $self) {
                    continue;
                }
                if (($rule instanceof ILess_Node_Ruleset) || ($rule instanceof ILess_Node_MixinDefinition)) {
                    foreach ($rule->selectors as $ruleSelector) {
                        $match = $selector->match($ruleSelector);
                        if ($match) {
                            if (count($selector->elements) > $match) {
                                $this->lookups[$key] = array_merge($this->lookups[$key], $rule->find(
                                    new ILess_Node_Selector(array_slice($selector->elements, $match)), $env, $self));
                            } else {
                                $this->lookups[$key][] = $rule;
                            }
                            break;
                        }
                    }
                }
            }
        }

        return $this->lookups[$key];
    }

    /**
     * Joins selectors
     *
     * @param array $context
     * @param array $selectors
     */
    public function joinSelectors(array $context, array $selectors)
    {
        $paths = array();
        foreach ($selectors as $selector) {
            $this->joinSelector($paths, $context, $selector);
        }

        return $paths;
    }

    /**
     * Joins a selector
     *
     * @param array $paths
     * @param array $context
     * @param ILess_Node_Selector $selector The selector
     */
    protected function joinSelector(array &$paths, array $context, ILess_Node_Selector $selector)
    {
        $hasParentSelector = false;
        foreach ($selector->elements as $el) {
            if ($el->value === '&') {
                $hasParentSelector = true;
            }
        }

        if (!$hasParentSelector) {
            if (count($context) > 0) {
                foreach ($context as $contextEl) {
                    $paths[] = array_merge($contextEl, array($selector));
                }
            } else {
                $paths[] = array($selector);
            }

            return;
        }


        // The paths are [[Selector]]
        // The first list is a list of comma seperated selectors
        // The inner list is a list of inheritance seperated selectors
        // e.g.
        // .a, .b {
        //   .c {
        //   }
        // }
        // == [[.a] [.c]] [[.b] [.c]]
        //

        // the elements from the current selector so far
        $currentElements = array();
        // the current list of new selectors to add to the path.
        // We will build it up. We initiate it with one empty selector as we "multiply" the new selectors
        // by the parents
        $newSelectors = array(array());
        foreach ($selector->elements as $el) {
            // non parent reference elements just get added
            if ($el->value !== '&') {
                $currentElements[] = $el;
            } else {
                // the new list of selectors to add
                $selectorsMultiplied = array();

                // merge the current list of non parent selector elements
                // on to the current list of selectors to add
                if (count($currentElements) > 0) {
                    $this->mergeElementsOnToSelectors($currentElements, $newSelectors);
                }

                // loop through our current selectors
                foreach ($newSelectors as $sel) {
                    // if we don't have any parent paths, the & might be in a mixin so that it can be used
                    // whether there are parents or not
                    if (!count($context)) {
                        // the combinator used on el should now be applied to the next element instead so that
                        // it is not lost
                        if (count($sel) > 0) {
                            $sel[0]->elements = array_slice($sel[0]->elements, 0);
                            $sel[0]->elements[] = new ILess_Node_Element($el->combinator, '', $el->index, $el->currentFileInfo);
                        }
                        $selectorsMultiplied[] = $sel;
                    } else {
                        // and the parent selectors
                        foreach ($context as $parentSel) {
                            // We need to put the current selectors
                            // then join the last selector's elements on to the parents selectors
                            // our new selector path
                            $newSelectorPath = array();
                            // selectors from the parent after the join
                            $afterParentJoin = array();
                            $newJoinedSelectorEmpty = true;

                            //construct the joined selector - if & is the first thing this will be empty,
                            // if not newJoinedSelector will be the last set of elements in the selector
                            if (count($sel) > 0) {
                                $newSelectorPath = $sel;
                                $lastSelector = array_pop($newSelectorPath);
                                $newJoinedSelector = $selector->createDerived(array_slice($lastSelector->elements, 0));
                                $newJoinedSelectorEmpty = false;
                            } else {
                                $newJoinedSelector = $selector->createDerived(array());
                            }

                            //put together the parent selectors after the join
                            if (count($parentSel) > 1) {
                                $afterParentJoin = array_merge($afterParentJoin, array_slice($parentSel, 1));
                            }

                            if (count($parentSel) > 0) {
                                $newJoinedSelectorEmpty = false;
                                // join the elements so far with the first part of the parent
                                $newJoinedSelector->elements[] = new ILess_Node_Element(
                                    $el->combinator, $parentSel[0]->elements[0]->value, $el->index, $el->currentFileInfo
                                );
                                $newJoinedSelector->elements = array_merge($newJoinedSelector->elements, array_slice($parentSel[0]->elements, 1));
                            }

                            if (!$newJoinedSelectorEmpty) {
                                // now add the joined selector
                                $newSelectorPath[] = $newJoinedSelector;
                            }
                            // and the rest of the parent
                            $newSelectorPath = array_merge($newSelectorPath, $afterParentJoin);
                            // add that to our new set of selectors
                            $selectorsMultiplied[] = $newSelectorPath;
                        }
                    }
                }

                // our new selectors has been multiplied, so reset the state
                $newSelectors = $selectorsMultiplied;
                $currentElements = array();
            }
        }

        // if we have any elements left over (e.g. .a& .b == .b)
        // add them on to all the current selectors
        if (count($currentElements) > 0) {
            $this->mergeElementsOnToSelectors($currentElements, $newSelectors);
        }

        foreach ($newSelectors as $newSel) {
            if (count($newSel)) {
                $paths[] = $newSel;
            }
        }
    }

    public function mergeElementsOnToSelectors(array $elements, array &$selectors)
    {
        if (!count($selectors)) {
            $selectors[] = array(new ILess_Node_Selector($elements));

            return;
        }

        foreach ($selectors as &$sel) {
            // if the previous thing in sel is a parent this needs to join on to it
            if (count($sel) > 0) {
                $last = count($sel) - 1;
                $sel[$last] = $sel[$last]->createDerived(array_merge($sel[$last]->elements, $elements));
            } else {
                $sel[] = new ILess_Node_Selector($elements);
            }
        }
    }

    public function __toString()
    {
        return 'There is probably a bug in the parser. Please report it.';
    }

}

<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Node;

use ILess\Context;
use ILess\DefaultFunc;
use ILess\Exception\ParserException;
use ILess\Node;
use ILess\Output\OutputInterface;
use ILess\Util\Serializer;
use ILess\Visitor\VisitorInterface;

/**
 * Ruleset.
 */
class RulesetNode extends Node implements MarkableAsReferencedInterface,
    MakeableImportantInterface, ConditionMatchableInterface,
    ReferencedInterface, \Serializable
{
    /**
     * Ruleset paths like: `#foo #bar`.
     *
     * @var array
     */
    public $paths = [];

    /**
     * Strict imports flag.
     *
     * @var bool
     */
    public $strictImports = false;

    /**
     * Array of selectors.
     *
     * @var array
     */
    public $selectors = [];

    /**
     * Is first root?
     *
     * @var bool
     */
    public $firstRoot = false;

    /**
     * Is root?
     *
     * @var bool
     */
    public $root = false;

    /**
     * Array of rules.
     *
     * @var array
     */
    public $rules;

    /**
     * Allow imports flag.
     *
     * @var bool
     */
    public $allowImports = false;

    /**
     * Multi media flag.
     *
     * @var bool
     */
    public $multiMedia = false;

    /**
     * Array of extends.
     *
     * @var array
     */
    public $allExtends;

    /**
     * Extend on every path flag.
     *
     * @var bool
     */
    public $extendOnEveryPath = false;

    /**
     * Original ruleset id.
     *
     * @var RulesetNode|null
     */
    public $originalRuleset;

    /**
     * The id.
     *
     * @var int
     */
    public $rulesetId;

    /**
     * Node type.
     *
     * @var string
     */
    protected $type = 'Ruleset';

    /**
     * Array of lookups.
     *
     * @var array
     */
    protected $lookups = [];

    /**
     * Variables cache array.
     *
     * @var array
     *
     * @see resetCache
     */
    protected $variables;

    /**
     * Internal flag. Selectors are referenced.
     *
     * @var bool
     *
     * @see markAsReferenced
     */
    protected $isReferenced = false;

    /**
     * @var \ILess\FunctionRegistry
     */
    public $functionRegistry;

    /**
     * @var array|null
     */
    public $functions;

    /**
     * Constructor.
     *
     * @param array $selectors Array of selectors
     * @param array $rules Array of rules
     * @param bool $strictImports Strict imports?
     */
    public function __construct(array $selectors, array $rules, $strictImports = false)
    {
        $this->selectors = $selectors;
        $this->rules = $rules;
        $this->strictImports = (boolean) $strictImports;
    }

    /**
     * {@inheritdoc}
     */
    public function accept(VisitorInterface $visitor)
    {
        if ($this->paths) {
            $visitor->visitArray($this->paths, true);
        } elseif ($this->selectors) {
            $this->selectors = $visitor->visitArray($this->selectors);
        }
        if ($this->rules) {
            $this->rules = $visitor->visitArray($this->rules);
        }
    }

    /**
     * Compiles the node.
     *
     * @param Context $context The context
     * @param array|null $arguments Array of arguments
     * @param bool|null $important Important flag
     *
     * @throws ParserException
     *
     * @return RulesetNode
     */
    public function compile(Context $context, $arguments = null, $important = null)
    {
        // compile selectors
        $selectors = [];
        $hasOnePassingSelector = false;

        if ($count = count($this->selectors)) {
            DefaultFunc::error(new ParserException('it is currently only allowed in parametric mixin guards'));
            for ($i = 0; $i < $count; ++$i) {
                $selector = $this->selectors[$i]->compile($context);
                /* @var $selector SelectorNode */
                $selectors[] = $selector;
                if ($selector->compiledCondition) {
                    $hasOnePassingSelector = true;
                }
            }
            DefaultFunc::reset();
        } else {
            $hasOnePassingSelector = true;
        }

        $ruleset = new self($selectors, $this->rules, $this->strictImports);
        $ruleset->originalRuleset = $this;
        $ruleset->root = $this->root;
        $ruleset->firstRoot = $this->firstRoot;
        $ruleset->allowImports = $this->allowImports;

        if ($this->debugInfo) {
            $ruleset->debugInfo = $this->debugInfo;
        }

        if (!$hasOnePassingSelector) {
            $ruleset->rules = [];
        }

        // inherit a function registry from the frames stack when possible;
        // otherwise from the global registry
        $found = null;
        foreach ($context->frames as $i => $frame) {
            if ($frame->functionRegistry) {
                $found = $frame->functionRegistry;
                break;
            }
        }

        $registry = $found ? $found : $context->getFunctionRegistry();
        $ruleset->functionRegistry = $registry->inherit();

        // push the current ruleset to the frames stack
        $context->unshiftFrame($ruleset);

        // current selectors
        array_unshift($context->selectors, $this->selectors);

        // Evaluate imports
        if ($ruleset->root || $ruleset->allowImports || !$ruleset->strictImports) {
            $ruleset->compileImports($context);
        }

        // count after compile imports was called
        $rulesetCount = count($ruleset->rules);

        // Store the frames around mixin definitions,
        // so they can be evaluated like closures when the time comes.
        foreach ($ruleset->rules as $i => $rule) {
            /* @var $rule RuleNode */
            if ($rule && $rule->compileFirst()) {
                $ruleset->rules[$i] = $rule->compile($context);
            }
        }

        $mediaBlockCount = count($context->mediaBlocks);

        // Evaluate mixin calls.
        for ($i = 0; $i < $rulesetCount; ++$i) {
            $rule = $ruleset->rules[$i];

            if ($rule instanceof MixinCallNode) {
                $rule = $rule->compile($context);
                $temp = [];
                foreach ($rule as $r) {
                    if (($r instanceof RuleNode) && $r->variable) {
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
                $tempCount = count($temp) - 1;
                array_splice($ruleset->rules, $i, 1, $temp);
                $rulesetCount += $tempCount;
                $i += $tempCount;
                $ruleset->resetCache();
            } elseif ($rule instanceof RulesetCallNode) {
                $rule = $rule->compile($context);
                $rules = [];
                foreach ($rule->rules as $r) {
                    if (($r instanceof RuleNode && $r->variable)) {
                        continue;
                    }
                    $rules[] = $r;
                }

                array_splice($ruleset->rules, $i, 1, $rules);
                $tempCount = count($rules);
                $rulesetCount += $tempCount - 1;
                $i += $tempCount - 1;
                $ruleset->resetCache();
            }
        }

        // Evaluate everything else
        for ($i = 0; $i < count($ruleset->rules); ++$i) {
            $rule = $ruleset->rules[$i];
            /* @var $rule Node */
            if ($rule && !$rule->compileFirst()) {
                $ruleset->rules[$i] = $rule instanceof CompilableInterface ? $rule->compile($context) : $rule;
            }
        }

        // Evaluate everything else
        for ($i = 0; $i < count($ruleset->rules); ++$i) {
            $rule = $ruleset->rules[$i];
            // for rulesets, check if it is a css guard and can be removed
            if ($rule instanceof self && count($rule->selectors) === 1) {
                // check if it can be folded in (e.g. & where)
                if ($rule->selectors[0]->isJustParentSelector()) {
                    array_splice($ruleset->rules, $i--, 1);
                    for ($j = 0; $j < count($rule->rules); ++$j) {
                        $subRule = $rule->rules[$j];
                        if (!($subRule instanceof RuleNode) || !$subRule->variable) {
                            array_splice($ruleset->rules, ++$i, 0, [$subRule]);
                        }
                    }
                }
            }
        }

        // Pop the stack
        $context->shiftFrame();
        array_shift($context->selectors);

        if ($mediaBlockCount) {
            for ($i = $mediaBlockCount, $count = count($context->mediaBlocks); $i < $count; ++$i) {
                $context->mediaBlocks[$i]->bubbleSelectors($selectors);
            }
        }

        return $ruleset;
    }

    /**
     * Compiles the imports.
     *
     * @param Context $context
     */
    public function compileImports(Context $context)
    {
        for ($i = 0; $i < count($this->rules); ++$i) {
            $rule = $this->rules[$i];

            if (!($rule instanceof ImportNode)) {
                continue;
            }

            $importRules = $rule->compile($context);

            if (is_array($importRules) && count($importRules)) {
                array_splice($this->rules, $i, 1, $importRules);
                $i += count($importRules) - 1;
            } else {
                array_splice($this->rules, $i, 1, [$importRules]);
            }

            $this->resetCache();
        }
    }

    /**
     * Resets the cache for variables and lookups.
     */
    public function resetCache()
    {
        $this->variables = null;
        $this->rulesets = [];
        $this->lookups = [];
    }

    /**
     * Returns the variable by name.
     *
     * @param string $name
     *
     * @return RuleNode
     */
    public function variable($name)
    {
        $vars = $this->variables();

        return isset($vars[$name]) ? $vars[$name] : null;
    }

    /**
     * Returns an array of variables.
     *
     * @return array
     */
    public function variables()
    {
        if ($this->variables === null) {
            $this->variables = [];
            foreach ($this->rules as $r) {
                if ($r instanceof RuleNode && $r->variable == true) {
                    $this->variables[$r->name] = $r;
                }
                // when evaluating variables in an import statement, imports have not been eval'd
                // so we need to go inside import statements.
                // guard against root being a string (in the case of inlined less)
                if ($r instanceof ImportNode && self::methodExists($r->root, 'variables')) {
                    $vars = $r->root->variables();
                    foreach ($vars as $name => $value) {
                        $this->variables[$name] = $value;
                    }
                }
            }
        }

        return $this->variables;
    }

    /**
     * {@inheritdoc}
     */
    public function generateCSS(Context $context, OutputInterface $output)
    {
        if (!$this->root) {
            ++$context->tabLevel;
        }

        $tabRuleStr = $tabSetStr = '';
        if (!$context->compress && $context->tabLevel) {
            $tabRuleStr = str_repeat('  ', $context->tabLevel);
            $tabSetStr = str_repeat('  ', $context->tabLevel - 1);
        }

        $ruleNodes = $rulesetNodes = $charsetRuleNodes = [];
        $charsetNodeIndex = 0;
        $importNodeIndex = 0;

        for ($i = 0; $i < count($this->rules); ++$i) {
            $rule = $this->rules[$i];
            if ($rule instanceof CommentNode) {
                if ($importNodeIndex === $i) {
                    ++$importNodeIndex;
                }
                array_push($ruleNodes, $rule);
            } elseif ($rule instanceof DirectiveNode && $rule->isCharset()) {
                array_splice($ruleNodes, $charsetNodeIndex, 0, [$rule]);
                ++$charsetNodeIndex;
                ++$importNodeIndex;
            } elseif ($rule instanceof ImportNode) {
                array_splice($ruleNodes, $importNodeIndex, 0, [$rule]);
                ++$importNodeIndex;
            } else {
                array_push($ruleNodes, $rule);
            }
        }

        $ruleNodes = array_merge($charsetRuleNodes, $ruleNodes);

        // If this is the root node, we don't render
        // a selector, or {}.
        if (!$this->root) {
            if ($this->debugInfo) {
                // debug?
                $debugInfo = self::getDebugInfo($context, $this, $tabSetStr);
                if ($debugInfo) {
                    $output->add($debugInfo)->add($tabSetStr);
                }
            }

            $sep = $context->compress ? ',' : (",\n" . $tabSetStr);
            for ($i = 0, $count = count($this->paths); $i < $count; ++$i) {
                $path = $this->paths[$i];
                /* @var $path SelectorNode */
                if (!($pathSubCnt = count($path))) {
                    continue;
                }

                if ($i > 0) {
                    $output->add($sep);
                }

                $context->firstSelector = true;
                $path[0]->generateCSS($context, $output);
                $context->firstSelector = false;

                for ($j = 1; $j < $pathSubCnt; ++$j) {
                    $path[$j]->generateCSS($context, $output);
                }
            }

            $output->add(($context->compress ? '{' : " {\n") . $tabRuleStr);
        }

        // Compile rules and rulesets
        for ($i = 0, $ruleNodesCount = count($ruleNodes); $i < $ruleNodesCount; ++$i) {
            $rule = $ruleNodes[$i];
            /* @var $rule RuleNode */
            if ($i + 1 === $ruleNodesCount) {
                $context->lastRule = true;
            }

            $currentLastRule = $context->lastRule;

            if ($rule->isRulesetLike()) {
                $context->lastRule = false;
            }

            if ($rule instanceof GenerateCSSInterface) {
                $rule->generateCSS($context, $output);
            } elseif ($rule->value) {
                $output->add((string) $rule->value);
            }

            $context->lastRule = $currentLastRule;

            if (!$context->lastRule) {
                $output->add($context->compress ? '' : ("\n" . $tabRuleStr));
            } else {
                $context->lastRule = false;
            }
        }

        if (!$this->root) {
            $output->add($context->compress ? '}' : "\n" . $tabSetStr . '}');
            --$context->tabLevel;
        }

        if (!$output->isEmpty() && !$context->compress && $this->firstRoot) {
            $output->add("\n");
        }
    }

    /**
     * Marks as referenced.
     */
    public function markReferenced()
    {
        foreach ($this->selectors as $s) {
            /* @var $s SelectorNode */
            $s->markReferenced();
        }

        foreach ($this->rules as $r) {
            if ($r instanceof MarkableAsReferencedInterface) {
                $r->markReferenced();
            }
        }
    }

    /**
     * Is referenced?
     *
     * @return bool
     */
    public function getIsReferenced()
    {
        foreach ($this->paths as $path) {
            foreach ($path as $p) {
                if ($p instanceof ReferencedInterface && $p->getIsReferenced()) {
                    return true;
                }
            }
        }

        foreach ($this->selectors as $selector) {
            if ($selector instanceof ReferencedInterface && $selector->getIsReferenced()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns ruleset with nodes marked as important.
     *
     * @return RulesetNode
     */
    public function makeImportant()
    {
        $importantRules = [];
        foreach ($this->rules as $rule) {
            if ($rule instanceof MakeableImportantInterface) {
                $importantRules[] = $rule->makeImportant();
            } else {
                $importantRules[] = $rule;
            }
        }

        return new self($this->selectors, $importantRules, $this->strictImports);
    }

    /**
     * Match arguments.
     *
     * @param array $args
     * @param Context $context
     *
     * @return bool
     */
    public function matchArgs(array $args, Context $context)
    {
        return !is_array($args) || count($args) === 0;
    }

    /**
     * Match condition.
     *
     * @param array $arguments
     * @param Context $context
     *
     * @return bool
     */
    public function matchCondition(array $arguments, Context $context)
    {
        $lastSelector = $this->selectors[count($this->selectors) - 1];
        if (!$lastSelector->compiledCondition) {
            return false;
        }

        if ($lastSelector->condition &&
            !$lastSelector->condition->compile(Context::createCopyForCompilation($context, $context->frames))
        ) {
            return false;
        }

        return true;
    }

    /**
     * Returns an array of rulesets.
     *
     * @return array
     */
    public function rulesets()
    {
        $result = [];
        foreach ($this->rules as $rule) {
            if ($rule instanceof self || $rule instanceof MixinDefinitionNode) {
                $result[] = $rule;
            }
        }

        return $result;
    }

    /**
     * Finds a selector.
     *
     * @param SelectorNode $selector
     * @param RulesetNode $self
     * @param Context $context
     * @param mixed $filter
     *
     * @return array
     */
    public function find(SelectorNode $selector, Context $context, RulesetNode $self = null, $filter = null)
    {
        $key = $selector->toCSS($context);
        if (!$self) {
            $self = $this;
        }

        if (!array_key_exists($key, $this->lookups)) {
            $rules = [];

            foreach ($this->rulesets() as $rule) {
                if ($rule === $self) {
                    continue;
                }

                foreach ($rule->selectors as $ruleSelector) {
                    /* @var $ruleSelector SelectorNode */
                    $match = $selector->match($ruleSelector);
                    if ($match) {
                        if (count($selector->elements) > $match) {
                            if (!$filter || call_user_func($filter, $rule)) {
                                /* @var $rule RulesetNode */
                                $foundMixins = $rule->find(new SelectorNode(array_slice($selector->elements, $match)),
                                    $context, $self, $filter);
                                for ($i = 0; $i < count($foundMixins); ++$i) {
                                    array_push($foundMixins[$i]['path'], $rule);
                                }
                                $rules = array_merge($rules, $foundMixins);
                            }
                        } else {
                            $rules[] = [
                                'rule' => $rule,
                                'path' => [],
                            ];
                        }
                        break;
                    }
                }
            }

            $this->lookups[$key] = $rules;
        }

        return $this->lookups[$key];
    }

    /**
     * Joins selectors.
     *
     * @param array $context
     * @param array $selectors
     *
     * @return array
     */
    public function joinSelectors(array $context, array $selectors)
    {
        $paths = [];

        foreach ($selectors as $selector) {
            $this->joinSelector($paths, $context, $selector);
        }

        return $paths;
    }

    /**
     * Replace all parent selectors inside `$inSelector` by content of `$context` array
     * resulting selectors are returned inside `$paths` array
     * returns true if `$inSelector` contained at least one parent selector.
     *
     * @param array $paths
     * @param array $context
     * @param $inSelector
     *
     * @return bool
     */
    private function replaceParentSelector(array &$paths, array $context, $inSelector)
    {
        $hadParentSelector = false;
        $currentElements = [];
        $newSelectors = [[]];

        for ($i = 0; $i < count($inSelector->elements); ++$i) {
            $el = $inSelector->elements[$i];
            if ($el->value !== '&') {
                $nestedSelector = $this->findNestedSelector($el);
                if ($nestedSelector !== null) {
                    $this->mergeElementsOnToSelectors($currentElements, $newSelectors);
                    $nestedPaths = $replacedNewSelectors = [];
                    $replaced = $this->replaceParentSelector($nestedPaths, $context, $nestedSelector);
                    $hadParentSelector = $hadParentSelector || $replaced;
                    // the nestedPaths array should have only one member - replaceParentSelector does not multiply selectors
                    for ($k = 0; $k < count($nestedPaths); ++$k) {
                        $replacementSelector = $this->createSelector($this->createParenthesis($nestedPaths[$k], $el),
                            $el);
                        $this->addAllReplacementsIntoPath($newSelectors, [$replacementSelector], $el, $inSelector,
                            $replacedNewSelectors);
                    }
                    $newSelectors = $replacedNewSelectors;
                    $currentElements = [];
                } else {
                    $currentElements[] = $el;
                }
            } else {
                $hadParentSelector = true;
                // the new list of selectors to add
                $selectorsMultiplied = [];
                // merge the current list of non parent selector elements
                // on to the current list of selectors to add
                $this->mergeElementsOnToSelectors($currentElements, $newSelectors);

                for ($j = 0; $j < count($newSelectors); ++$j) {
                    $sel = $newSelectors[$j];
                    // if we don't have any parent paths, the & might be in a mixin so that it can be used
                    // whether there are parents or not
                    if (count($context) === 0) {
                        // the combinator used on el should now be applied to the next element instead so that
                        // it is not lost
                        if (count($sel) > 0) {
                            $sel[0]->elements[] = new ElementNode($el->combinator, '', $el->index,
                                $el->currentFileInfo);
                        }
                        $selectorsMultiplied[] = $sel;
                    } else {
                        // and the parent selectors
                        for ($k = 0; $k < count($context); ++$k) {
                            // We need to put the current selectors
                            // then join the last selector's elements on to the parents selectors
                            $newSelectorPath = $this->addReplacementIntoPath($sel, $context[$k], $el, $inSelector);
                            $selectorsMultiplied[] = $newSelectorPath;
                        }
                    }
                }

                // our new selectors has been multiplied, so reset the state
                $newSelectors = $selectorsMultiplied;

                $currentElements = [];
            }
        }

        // if we have any elements left over (e.g. .a& .b == .b)
        // add them on to all the current selectors
        $this->mergeElementsOnToSelectors($currentElements, $newSelectors);

        for ($i = 0; $i < count($newSelectors); ++$i) {
            $count = count($newSelectors[$i]);
            if ($count > 0) {
                $paths[] = &$newSelectors[$i]; // reference the selector!
                $lastSelector = $newSelectors[$i][$count - 1];
                /* @var $lastSelector SelectorNode */
                $newSelectors[$i][$count - 1] = $lastSelector->createDerived($lastSelector->elements,
                    $inSelector->extendList);
            }
        }

        return $hadParentSelector;
    }

    private function findNestedSelector($element)
    {
        if (!($element->value instanceof ParenNode)) {
            return;
        }

        /* @var $element ParenNode */
        $mayBeSelector = $element->value->value;
        if (!($mayBeSelector instanceof SelectorNode)) {
            return;
        }

        return $mayBeSelector;
    }

    /**
     * @param $containedElement
     * @param $originalElement
     *
     * @return SelectorNode
     */
    private function createSelector($containedElement, $originalElement)
    {
        $element = new ElementNode(null, $containedElement, $originalElement->index, $originalElement->currentFileInfo);
        $selector = new SelectorNode([$element]);

        return $selector;
    }

    /**
     * @param $elementsToPak
     * @param $originalElement
     *
     * @return ParenNode
     */
    private function createParenthesis($elementsToPak, $originalElement)
    {
        if (count($elementsToPak) === 0) {
            $replacementParen = new ParenNode($elementsToPak[0]);
        } else {
            $insideParent = [];
            for ($j = 0; $j < count($elementsToPak); ++$j) {
                $insideParent[] = new ElementNode(null, $elementsToPak[$j], $originalElement->index,
                    $originalElement->currentFileInfo);
            }
            $replacementParen = new ParenNode(new SelectorNode($insideParent));
        }

        return $replacementParen;
    }

    /**
     * @param $beginningPath
     * @param $addPath
     * @param ElementNode $replacedElement
     * @param SelectorNode $originalSelector
     *
     * @return array
     */
    private function addReplacementIntoPath(
        $beginningPath,
        $addPath,
        ElementNode $replacedElement,
        SelectorNode $originalSelector
    ) {
        // our new selector path
        $newSelectorPath = [];

        // construct the joined selector - if & is the first thing this will be empty,
        // if not newJoinedSelector will be the last set of elements in the selector
        if (count($beginningPath) > 0) {
            $newSelectorPath = $beginningPath;
            $lastSelector = array_pop($newSelectorPath);
            $newJoinedSelector = $originalSelector->createDerived($lastSelector->elements);
        } else {
            $newJoinedSelector = $originalSelector->createDerived([]);
        }

        if (count($addPath) > 0) {
            $combinator = $replacedElement->combinator;
            $parentEl = $addPath[0]->elements[0];
            /* @var $parentEl ElementNode */
            if ($combinator->emptyOrWhitespace && !$parentEl->combinator->emptyOrWhitespace) {
                $combinator = $parentEl->combinator;
            }
            $newJoinedSelector->elements[] = new ElementNode($combinator, $parentEl->value, $replacedElement->index,
                $replacedElement->currentFileInfo);
            $newJoinedSelector->elements = array_merge($newJoinedSelector->elements,
                array_slice($addPath[0]->elements, 1));
        }

        // now add the joined selector - but only if it is not empty
        if (count($newJoinedSelector->elements) !== 0) {
            $newSelectorPath[] = $newJoinedSelector;
        }

        if (count($addPath) > 1) {
            $newSelectorPath = array_merge($newSelectorPath, array_slice($addPath, 1));
        }

        return $newSelectorPath;
    }

    private function addAllReplacementsIntoPath(
        $beginningPath,
        $addPaths,
        $replacedElement,
        $originalSelector,
        &$result
    ) {
        for ($j = 0; $j < count($beginningPath); ++$j) {
            $newSelectorPath = $this->addReplacementIntoPath($beginningPath[$j], $addPaths, $replacedElement,
                $originalSelector);
            $result[] = $newSelectorPath;
        }

        return $result;
    }

    /**
     * Joins a selector.
     *
     * @param array $paths
     * @param array $context
     * @param SelectorNode $selector The selector
     */
    private function joinSelector(array &$paths, array $context, SelectorNode $selector)
    {
        $newPaths = [];
        $hasParentSelector = $this->replaceParentSelector($newPaths, $context, $selector);

        if (!$hasParentSelector) {
            if (count($context) > 0) {
                $newPaths = [];
                for ($i = 0; $i < count($context); ++$i) {
                    $newPaths[] = array_merge($context[$i], [$selector]);
                }
            } else {
                $newPaths = [[$selector]];
            }
        }

        for ($i = 0; $i < count($newPaths); ++$i) {
            $paths[] = $newPaths[$i];
        }
    }

    public function mergeElementsOnToSelectors(array $elements, array &$selectors)
    {
        if (count($elements) === 0) {
            return;
        }

        if (count($selectors) === 0) {
            $selectors[] = [new SelectorNode($elements)];

            return;
        }

        foreach ($selectors as &$sel) {
            // if the previous thing in sel is a parent this needs to join on to it
            if (count($sel) > 0) {
                $last = count($sel) - 1;
                $sel[$last] = $sel[$last]->createDerived(array_merge($sel[$last]->elements, $elements));
            } else {
                $sel[] = new SelectorNode($elements);
            }
        }
    }

    /**
     * @return bool
     */
    public function isRulesetLike()
    {
        return true;
    }

    public function serialize()
    {
        $vars = get_object_vars($this);

        return Serializer::serialize($vars);
    }

    public function unserialize($serialized)
    {
        $unserialized = Serializer::unserialize($serialized);
        foreach ($unserialized as $var => $val) {
            $this->$var = $val;
        }
    }
}

<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Visitor;

use ILess\Context;
use ILess\Exception\Exception;
use ILess\Exception\ParserException;
use ILess\Node\AttributeNode;
use ILess\Node\DirectiveNode;
use ILess\Node\ElementNode;
use ILess\Node\ExtendNode;
use ILess\Node\MediaNode;
use ILess\Node\MixinDefinitionNode;
use ILess\Node\SelectorNode;
use ILess\Node\RulesetNode;
use ILess\Node\RuleNode;

/**
 * Process extends visitor.
 */
class ProcessExtendsVisitor extends Visitor
{
    /**
     * Extends stack.
     *
     * @var array
     */
    public $allExtendsStack = [];

    /**
     * @var array
     */
    public $extendIndicies = [];

    /**
     * @var int
     */
    private $extendChainCount = 0;

    /**
     * {@inheritdoc}
     */
    public function run($root)
    {
        $this->extendIndicies = [];

        $finder = new ExtendFinderVisitor();
        $finder->run($root);

        if (!$finder->foundExtends) {
            return $root;
        }

        $root->allExtends = $this->doExtendChaining($root->allExtends, $root->allExtends);
        $this->allExtendsStack = [&$root->allExtends];

        $newRoot = $this->visit($root);
        $this->checkExtendsForNonMatched($root->allExtends);

        return $newRoot;
    }

    private function checkExtendsForNonMatched($extendList)
    {
        $process = [];
        foreach ($extendList as $extend) {
            if (!$extend->hasFoundMatches && count($extend->parentIds) === 1) {
                $process[] = $extend;
            }
        }

        $context = new Context();
        foreach ($process as $extend) {
            $selector = '_unknown_';
            /* @var $extend ExtendNode */
            try {
                $selector = $extend->selector->toCSS($context);
            } catch (Exception $e) {
            }

            if (!isset($this->extendIndicies[$extend->index . ' ' . $selector])) {
                $this->extendIndicies[$extend->index . ' ' . $selector] = true;
                // FIXME: less.js uses logger to warn here:
                // echo "extend '$selector' has no matches";
                // logger.warn("extend '" + selector + "' has no matches");
            }
        }
    }

    /**
     * @param array $extendsList
     * @param array $extendsListTarget
     * @param int $iterationCount
     *
     * @return array
     *
     * @throws ParserException
     */
    private function doExtendChaining(array $extendsList, array $extendsListTarget, $iterationCount = 0)
    {
        // chaining is different from normal extension.. if we extend an extend then we are not just copying, altering and pasting
        // the selector we would do normally, but we are also adding an extend with the same target selector
        // this means this new extend can then go and alter other extends
        //
        // this method deals with all the chaining work - without it, extend is flat and doesn't work on other extend selectors
        // this is also the most expensive.. and a match on one selector can cause an extension of a selector we had already processed if
        // we look at each selector at a time, as is done in visitRuleset
        $extendsToAdd = [];
        // loop through comparing every extend with every target extend.
        // a target extend is the one on the ruleset we are looking at copy/edit/pasting in place
        // e.g. .a:extend(.b) {} and .b:extend(.c) {} then the first extend extends the second one
        // and the second is the target.
        // the separation into two lists allows us to process a subset of chains with a bigger set, as is the
        // case when processing media queries
        for ($extendIndex = 0, $extendsListCount = count($extendsList); $extendIndex < $extendsListCount; ++$extendIndex) {
            for ($targetExtendIndex = 0; $targetExtendIndex < count($extendsListTarget); ++$targetExtendIndex) {
                $extend = $extendsList[$extendIndex];
                $targetExtend = $extendsListTarget[$targetExtendIndex];

                /* @var $extend ExtendNode */
                /* @var $targetExtend ExtendNode */
                // look for circular references
                if (in_array($targetExtend->objectId, $extend->parentIds)) {
                    continue;
                }

                // find a match in the target extends self selector (the bit before :extend)
                $selectorPath = [$targetExtend->selfSelectors[0]];

                $matches = $this->findMatch($extend, $selectorPath);
                if (count($matches)) {
                    $extend->hasFoundMatches = true;
                    // we found a match, so for each self selector..
                    foreach ($extend->selfSelectors as $selfSelector) {
                        // process the extend as usual
                        $newSelector = $this->extendSelector($matches, $selectorPath, $selfSelector);
                        // but now we create a new extend from it
                        $newExtend = new ExtendNode($targetExtend->selector, $targetExtend->option);
                        $newExtend->selfSelectors = $newSelector;
                        // add the extend onto the list of extends for that selector
                        end($newSelector)->extendList = [$newExtend];
                        // record that we need to add it.
                        $extendsToAdd[] = $newExtend;
                        $newExtend->ruleset = $targetExtend->ruleset;

                        // remember its parents for circular references
                        $newExtend->parentIds = array_merge($newExtend->parentIds, $targetExtend->parentIds,
                            $extend->parentIds);

                        // only process the selector once.. if we have :extend(.a,.b) then multiple
                        // extends will look at the same selector path, so when extending
                        // we know that any others will be duplicates in terms of what is added to the css
                        if ($targetExtend->firstExtendOnThisSelectorPath) {
                            $newExtend->firstExtendOnThisSelectorPath = true;
                            $targetExtend->ruleset->paths[] = $newSelector;
                        }
                    }
                }
            }
        }

        if ($extendsToAdd) {
            ++$this->extendChainCount;
            if ($iterationCount > 100) {
                $selectorOne = '{unable to calculate}';
                $selectorTwo = '{unable to calculate}';
                try {
                    $context = new Context();
                    $selectorOne = $extendsToAdd[0]->selfSelectors[0]->toCSS($context);
                    $selectorTwo = $extendsToAdd[0]->selector->toCSS($context);
                } catch (Exception $e) {
                    // cannot calculate
                }
                throw new ParserException(
                    sprintf('Extend circular reference detected. One of the circular extends is currently: %s:extend(%s).',
                        $selectorOne, $selectorTwo)
                );
            }

            // now process the new extends on the existing rules so that we can handle a extending b extending c extending d extending e...
            $extendsToAdd = $this->doExtendChaining($extendsToAdd, $extendsListTarget, $iterationCount + 1);
        }

        return array_merge($extendsList, $extendsToAdd);
    }

    /**
     * Visits a rule node.
     *
     * @param RuleNode $node The node
     * @param VisitorArguments $arguments The arguments
     *
     * @return array|RuleNode
     */
    public function visitRule(RuleNode $node, VisitorArguments $arguments)
    {
        $arguments->visitDeeper = false;
    }

    /**
     * Visits a mixin definition node.
     *
     * @param MixinDefinitionNode $node The node
     * @param VisitorArguments $arguments The arguments
     */
    public function visitMixinDefinition(MixinDefinitionNode $node, VisitorArguments $arguments)
    {
        $arguments->visitDeeper = false;
    }

    /**
     * Visits a selector node.
     *
     * @param SelectorNode $node The node
     * @param VisitorArguments $arguments The arguments
     */
    public function visitSelector(SelectorNode $node, VisitorArguments $arguments)
    {
        $arguments->visitDeeper = false;
    }

    /**
     * Visits a ruleset node.
     *
     * @param RulesetNode $node The node
     * @param VisitorArguments $arguments The arguments
     */
    public function visitRuleset(RulesetNode $node, VisitorArguments $arguments)
    {
        if ($node->root) {
            return;
        }

        $allExtends = $this->allExtendsStack[count($this->allExtendsStack) - 1];
        $pathsCount = count($node->paths);
        $selectorsToAdd = [];

        // look at each selector path in the ruleset, find any extend matches and then copy, find and replace
        for ($extendIndex = 0, $allExtendCount = count($allExtends); $extendIndex < $allExtendCount; ++$extendIndex) {
            for ($pathIndex = 0; $pathIndex < $pathsCount; ++$pathIndex) {
                $selectorPath = $node->paths[$pathIndex];
                // extending extends happens initially, before the main pass
                if ($node->extendOnEveryPath) {
                    continue;
                }
                if (end($selectorPath)->extendList) {
                    continue;
                }
                $matches = $this->findMatch($allExtends[$extendIndex], $selectorPath);
                if ($matches) {
                    $allExtends[$extendIndex]->hasFoundMatches = true;
                    foreach ($allExtends[$extendIndex]->selfSelectors as $selfSelector) {
                        $selectorsToAdd[] = $this->extendSelector($matches, $selectorPath, $selfSelector);
                    }
                }
            }
        }

        $node->paths = array_merge($node->paths, $selectorsToAdd);
    }

    /**
     * Visits a ruleset node.
     *
     * @param RulesetNode $node The node
     * @param VisitorArguments $arguments The arguments
     */
    public function visitRulesetOut(RulesetNode $node, VisitorArguments $arguments)
    {
    }

    /**
     * Visits a media node.
     *
     * @param RuleNode $node The node
     * @param VisitorArguments $arguments The arguments
     */
    public function visitMedia(MediaNode $node, VisitorArguments $arguments)
    {
        $newAllExtends = array_merge($node->allExtends, end($this->allExtendsStack));
        $this->allExtendsStack[] = $this->doExtendChaining($newAllExtends, $node->allExtends);
    }

    /**
     * Visits a media node (!again).
     *
     * @param RuleNode $node The node
     * @param VisitorArguments $arguments The arguments
     */
    public function visitMediaOut(MediaNode $node, VisitorArguments $arguments)
    {
        array_pop($this->allExtendsStack);
    }

    /**
     * Visits a directive node.
     *
     * @param RuleNode $node The node
     * @param VisitorArguments $arguments The arguments
     */
    public function visitDirective(DirectiveNode $node, VisitorArguments $arguments)
    {
        $newAllExtends = array_merge($node->allExtends, end($this->allExtendsStack));
        $this->allExtendsStack[] = $this->doExtendChaining($newAllExtends, $node->allExtends);
    }

    /**
     * Visits a directive node (!again).
     *
     * @param RuleNode $node The node
     * @param VisitorArguments $arguments The arguments
     */
    public function visitDirectiveOut(DirectiveNode $node, VisitorArguments $arguments)
    {
        array_pop($this->allExtendsStack);
    }

    /**
     * @param ExtendNode $extend
     * @param $haystackSelectorPath
     *
     * @return array
     */
    private function findMatch(ExtendNode $extend, $haystackSelectorPath)
    {
        // look through the haystack selector path to try and find the needle - extend.selector
        // returns an array of selector matches that can then be replaced
        $needleElements = $extend->selector->elements;
        $needleElementsCount = false;
        $potentialMatches = [];
        $potentialMatchesCount = 0;
        $potentialMatch = null;
        $matches = [];

        // loop through the haystack elements
        for ($haystackSelectorIndex = 0, $haystackPathCount = count($haystackSelectorPath); $haystackSelectorIndex < $haystackPathCount; ++$haystackSelectorIndex) {
            $hackstackSelector = $haystackSelectorPath[$haystackSelectorIndex];
            for ($hackstackElementIndex = 0, $haystackElementsCount = count($hackstackSelector->elements); $hackstackElementIndex < $haystackElementsCount; ++$hackstackElementIndex) {
                $haystackElement = $hackstackSelector->elements[$hackstackElementIndex];
                // if we allow elements before our match we can add a potential match every time. otherwise only at the first element.
                if ($extend->allowBefore || ($haystackSelectorIndex === 0 && $hackstackElementIndex === 0)) {
                    $potentialMatches[] = [
                        'pathIndex' => $haystackSelectorIndex,
                        'index' => $hackstackElementIndex,
                        'matched' => 0,
                        'initialCombinator' => $haystackElement->combinator,
                    ];
                    ++$potentialMatchesCount;
                }

                for ($i = 0; $i < $potentialMatchesCount; ++$i) {
                    $potentialMatch = &$potentialMatches[$i];

                    // selectors add " " onto the first element. When we use & it joins the selectors together, but if we don't
                    // then each selector in haystackSelectorPath has a space before it added in the toCSS phase. so we need to work out
                    // what the resulting combinator will be
                    $targetCombinator = $haystackElement->combinator->value;
                    if ($targetCombinator === '' && $hackstackElementIndex === 0) {
                        $targetCombinator = ' ';
                    }

                    // if we don't match, null our match to indicate failure
                    if (!$this->isElementValuesEqual($needleElements[$potentialMatch['matched']]->value,
                            $haystackElement->value) ||
                        ($potentialMatch['matched'] > 0 && $needleElements[$potentialMatch['matched']]->combinator->value !== $targetCombinator)
                    ) {
                        $potentialMatch = null;
                    } else {
                        ++$potentialMatch['matched'];
                    }

                    // if we are still valid and have finished, test whether we have elements after and whether these are allowed
                    if ($potentialMatch) {
                        if ($needleElementsCount === false) {
                            $needleElementsCount = count($needleElements);
                        }

                        $potentialMatch['finished'] = ($potentialMatch['matched'] === $needleElementsCount);

                        if ($potentialMatch['finished'] &&
                            (!$extend->allowAfter && ($hackstackElementIndex + 1 < count($hackstackSelector->elements) || $haystackSelectorIndex + 1 < $haystackPathCount))
                        ) {
                            $potentialMatch = null;
                        }
                    }
                    // if null we remove, if not, we are still valid, so either push as a valid match or continue
                    if ($potentialMatch) {
                        if ($potentialMatch['finished']) {
                            $potentialMatch['length'] = $needleElementsCount;
                            $potentialMatch['endPathIndex'] = $haystackSelectorIndex;
                            $potentialMatch['endPathElementIndex'] = $hackstackElementIndex + 1; // index after end of match
                            $potentialMatches = []; // we don't allow matches to overlap, so start matching again
                            $potentialMatchesCount = 0;
                            $matches[] = $potentialMatch;
                        }
                    } else {
                        array_splice($potentialMatches, $i, 1);
                        --$potentialMatchesCount;
                        --$i;
                    }
                }
            }
        }

        return $matches;
    }

    private function isElementValuesEqual($elementValue1, $elementValue2)
    {
        if (is_string($elementValue1) || is_string($elementValue2)) {
            return $elementValue1 === $elementValue2;
        }

        if ($elementValue1 instanceof AttributeNode) {
            if ($elementValue1->operator !== $elementValue2->operator || $elementValue1->key !== $elementValue2->key) {
                return false;
            }

            if (!$elementValue1->value || !$elementValue2->value) {
                if ($elementValue1->value || $elementValue2->value) {
                    return false;
                }

                return true;
            }
            $elementValue1 = ($elementValue1->value->value ? $elementValue1->value->value : $elementValue1->value);
            $elementValue2 = ($elementValue2->value->value ? $elementValue2->value->value : $elementValue2->value);

            return $elementValue1 === $elementValue2;
        }

        $elementValue1 = $elementValue1->value;
        $elementValue2 = $elementValue2->value;

        if ($elementValue1 instanceof SelectorNode) {
            if (!($elementValue2 instanceof SelectorNode) || count($elementValue1->elements) !== count($elementValue2->elements)) {
                return false;
            }

            for ($i = 0; $i < count($elementValue1->elements); ++$i) {
                if ($elementValue1->elements[$i]->combinator->value !== $elementValue2->elements[$i]->combinator->value) {
                    if ($i !== 0 || ($elementValue1->elements[$i]->combinator->value ? $elementValue1->elements[$i]->combinator->value : ' ') !== ($elementValue2->elements[$i]->combinator->value ? $elementValue2->elements[$i]->combinator->value : ' ')) {
                        return false;
                    }
                }
                if (!$this->isElementValuesEqual($elementValue1->elements[$i]->value,
                    $elementValue2->elements[$i]->value)
                ) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    public function extendSelector($matches, $selectorPath, $replacementSelector)
    {
        // for a set of matches, replace each match with the replacement selector
        $currentSelectorPathIndex = 0;
        $currentSelectorPathElementIndex = 0;
        $path = [];
        $selectorPathCount = count($selectorPath);

        for ($matchIndex = 0, $matchesCount = count($matches); $matchIndex < $matchesCount; ++$matchIndex) {
            $match = $matches[$matchIndex];
            $selector = $selectorPath[$match['pathIndex']];
            $firstElement = new ElementNode(
                $match['initialCombinator'],
                $replacementSelector->elements[0]->value,
                $replacementSelector->elements[0]->index,
                $replacementSelector->elements[0]->currentFileInfo
            );

            if ($match['pathIndex'] > $currentSelectorPathIndex && $currentSelectorPathElementIndex > 0) {
                $lastPath = end($path);
                $lastPath->elements = array_merge($lastPath->elements,
                    array_slice($selectorPath[$currentSelectorPathIndex]->elements, $currentSelectorPathElementIndex));
                $currentSelectorPathElementIndex = 0;
                ++$currentSelectorPathIndex;
            }

            $newElements = array_merge(
            // last parameter of array_slice is different than the last parameter of javascript's slice
                array_slice($selector->elements, $currentSelectorPathElementIndex,
                    ($match['index'] - $currentSelectorPathElementIndex)),
                [$firstElement],
                array_slice($replacementSelector->elements, 1)
            );

            if ($currentSelectorPathIndex === $match['pathIndex'] && $matchIndex > 0) {
                $lastKey = count($path) - 1;
                $path[$lastKey]->elements = array_merge($path[$lastKey]->elements, $newElements);
            } else {
                $path = array_merge($path, array_slice($selectorPath, $currentSelectorPathIndex, $match['pathIndex']));
                $path[] = new SelectorNode($newElements);
            }

            $currentSelectorPathIndex = $match['endPathIndex'];
            $currentSelectorPathElementIndex = $match['endPathElementIndex'];
            if ($currentSelectorPathElementIndex >= count($selectorPath[$currentSelectorPathIndex]->elements)) {
                $currentSelectorPathElementIndex = 0;
                ++$currentSelectorPathIndex;
            }
        }

        if ($currentSelectorPathIndex < $selectorPathCount && $currentSelectorPathElementIndex > 0) {
            $lastPath = end($path);
            $lastPath->elements = array_merge($lastPath->elements,
                array_slice($selectorPath[$currentSelectorPathIndex]->elements, $currentSelectorPathElementIndex));
            ++$currentSelectorPathIndex;
        }

        $sliceLength = count($selectorPath) - $currentSelectorPathIndex;
        $path = array_merge($path, array_slice($selectorPath, $currentSelectorPathIndex, $sliceLength));

        return $path;
    }
}

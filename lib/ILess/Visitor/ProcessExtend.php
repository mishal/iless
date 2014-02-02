<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * ProcessExtend visitor
 *
 * @package ILess
 * @subpackage visitor
 */
class ILess_Visitor_ProcessExtend extends ILess_Visitor
{
    /**
     * Extends stack
     *
     * @var array
     */
    public $allExtendsStack = array();

    /**
     * Runs the visitor
     *
     * @param ILess_Node|array $root The root node
     */
    public function run($root)
    {
        $finder = new ILess_Visitor_ExtendFinder();
        $finder->run($root);

        if (!$finder->foundExtends) {
            return $root;
        }

        $root->allExtends = $this->doExtendChaining($root->allExtends, $root->allExtends);
        $this->allExtendsStack = array();
        $this->allExtendsStack[] = & $root->allExtends;

        return $this->visit($root);
    }

    protected function doExtendChaining(array $extendsList, array $extendsListTarget, $iterationCount = 0)
    {
        // chaining is different from normal extension.. if we extend an extend then we are not just copying, altering and pasting
        // the selector we would do normally, but we are also adding an extend with the same target selector
        // this means this new extend can then go and alter other extends
        //
        // this method deals with all the chaining work - without it, extend is flat and doesn't work on other extend selectors
        // this is also the most expensive.. and a match on one selector can cause an extension of a selector we had already processed if
        // we look at each selector at a time, as is done in visitRuleset
        $extendsToAdd = array();
        // loop through comparing every extend with every target extend.
        // a target extend is the one on the ruleset we are looking at copy/edit/pasting in place
        // e.g. .a:extend(.b) {} and .b:extend(.c) {} then the first extend extends the second one
        // and the second is the target.
        // the seperation into two lists allows us to process a subset of chains with a bigger set, as is the
        // case when processing media queries
        for ($extendIndex = 0, $extendsListCount = count($extendsList); $extendIndex < $extendsListCount; $extendIndex++) {
            for ($targetExtendIndex = 0; $targetExtendIndex < count($extendsListTarget); $targetExtendIndex++) {
                $extend = $extendsList[$extendIndex];
                $targetExtend = $extendsListTarget[$targetExtendIndex];
                // look for circular references
                if ($this->inInheritanceChain($targetExtend, $extend)) {
                    continue;
                }

                // find a match in the target extends self selector (the bit before :extend)
                $selectorPath = array($targetExtend->selfSelectors[0]);
                $matches = $this->findMatch($extend, $selectorPath);
                if ($matches) {
                    // we found a match, so for each self selector..
                    foreach ($extend->selfSelectors as $selfSelector) {
                        // process the extend as usual
                        $newSelector = $this->extendSelector($matches, $selectorPath, $selfSelector);
                        // but now we create a new extend from it
                        $newExtend = new ILess_Node_Extend($targetExtend->selector, $targetExtend->option);
                        $newExtend->selfSelectors = $newSelector;
                        // add the extend onto the list of extends for that selector
                        end($newSelector)->extendList = array($newExtend);
                        // record that we need to add it.
                        $extendsToAdd[] = $newExtend;
                        $newExtend->ruleset = $targetExtend->ruleset;

                        // remember its parents for circular references
                        $newExtend->parents = array($targetExtend, $extend);

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
            if ($iterationCount > 100) {
                $selectorOne = '{unable to calculate}';
                $selectorTwo = '{unable to calculate}';
                try {
                    $selectorOne = $extendsToAdd[0]->selfSelectors[0]->toCSS();
                    $selectorTwo = $extendsToAdd[0]->selector->toCSS();
                } catch (Exception $e) {
                    // cannot calculate
                }
                throw new ILess_Exception_Parser(
                    sprintf('Extend circular reference detected. One of the circular extends is currently: %s:extend(%s).', $selectorOne, $selectorTwo)
                );
            }

            // now process the new extends on the existing rules so that we can handle a extending b extending c extending d extending e...
            $extendsToAdd = $this->doExtendChaining($extendsToAdd, $extendsListTarget, $iterationCount + 1);
        }

        return array_merge($extendsList, $extendsToAdd);
    }

    /**
     * Checks the inheritance chain
     *
     * @param ILess_Node_Extend $possibleParent
     * @param ILess_Node_Extend $possibleChild
     * @return boolean
     */
    protected function inInheritanceChain(ILess_Node_Extend $possibleParent, ILess_Node_Extend $possibleChild)
    {
        if ($possibleParent === $possibleChild) {
            return true;
        }

        if ($possibleChild->parents) {
            if ($this->inInheritanceChain($possibleParent, $possibleChild->parents[0])) {
                return true;
            }
            if ($this->inInheritanceChain($possibleParent, $possibleChild->parents[1])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Visits a rule node
     *
     * @param ILess_Node_Rule $node The node
     * @param ILess_VisitorArguments $arguments The arguments
     * @return array|ILess_Node_Rule
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
     * Visits a selector node
     *
     * @param ILess_Node_Selector $node The node
     * @param ILess_Visitor_Arguments $arguments The arguments
     */
    public function visitSelector(ILess_Node_Selector $node, ILess_Visitor_Arguments $arguments)
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

        $allExtends = end($this->allExtendsStack);
        $pathsCount = count($node->paths);

        // look at each selector path in the ruleset, find any extend matches and then copy, find and replace
        for ($extendIndex = 0, $allExtendCount = count($allExtends); $extendIndex < $allExtendCount; $extendIndex++) {
            for ($pathIndex = 0; $pathIndex < $pathsCount; $pathIndex++) {
                $selectorPath = $node->paths[$pathIndex];
                // extending extends happens initially, before the main pass
                if (ILess_Node::propertyExists($node, 'extendOnEveryPath') && $node->extendOnEveryPath) {
                    continue;
                }
                if (end($selectorPath)->extendList) {
                    continue;
                }
                $matches = $this->findMatch($allExtends[$extendIndex], $selectorPath);
                if ($matches) {
                    foreach ($allExtends[$extendIndex]->selfSelectors as $selfSelector) {
                        $node->paths[] = $this->extendSelector($matches, $selectorPath, $selfSelector);
                    }
                }
            }
        }
    }

    /**
     * Visits a media node
     *
     * @param ILess_Node_Rule $node The node
     * @param ILess_Visitor_Arguments $arguments The arguments
     */
    public function visitMedia(ILess_Node_Media $node, ILess_Visitor_Arguments $arguments)
    {
        $newAllExtends = array_merge($node->allExtends, end($this->allExtendsStack));
        $this->allExtendsStack[] = $this->doExtendChaining($newAllExtends, $node->allExtends);
    }

    /**
     * Visits a media node (!again)
     *
     * @param ILess_Node_Rule $node The node
     * @param ILess_Visitor_Arguments $arguments The arguments
     */
    public function visitMediaOut(ILess_Node_Media $node, ILess_Visitor_Arguments $arguments)
    {
        array_pop($this->allExtendsStack);
    }

    /**
     * Visits a directive node
     *
     * @param ILess_Node_Rule $node The node
     * @param ILess_Visitor_Arguments $arguments The arguments
     */
    public function visitDirective(ILess_Node_Directive $node, ILess_Visitor_Arguments $arguments)
    {
        $newAllExtends = array_merge($node->allExtends, end($this->allExtendsStack));
        $this->allExtendsStack[] = $this->doExtendChaining($newAllExtends, $node->allExtends);
    }

    /**
     * Visits a directive node (!again)
     *
     * @param ILess_Node_Rule $node The node
     * @param ILess_Visitor_Arguments $arguments The arguments
     */
    public function visitDirectiveOut(ILess_Node_Directive $node, ILess_Visitor_Arguments $arguments)
    {
        array_pop($this->allExtendsStack);
    }

    protected function findMatch(ILess_Node_Extend $extend, $haystackSelectorPath)
    {
        // look through the haystack selector path to try and find the needle - extend.selector
        // returns an array of selector matches that can then be replaced
        $needleElements = $extend->selector->elements;
        $needleElements_len = false;
        $potentialMatches = array();
        $potentialMatches_len = 0;
        $potentialMatch = null;
        $matches = array();

        // loop through the haystack elements
        for ($haystackSelectorIndex = 0, $haystack_path_len = count($haystackSelectorPath); $haystackSelectorIndex < $haystack_path_len; $haystackSelectorIndex++) {
            $hackstackSelector = $haystackSelectorPath[$haystackSelectorIndex];

            for ($hackstackElementIndex = 0, $haystack_elements_len = count($hackstackSelector->elements); $hackstackElementIndex < $haystack_elements_len; $hackstackElementIndex++) {
                $haystackElement = $hackstackSelector->elements[$hackstackElementIndex];

                // if we allow elements before our match we can add a potential match every time. otherwise only at the first element.
                if ($extend->allowBefore || ($haystackSelectorIndex === 0 && $hackstackElementIndex === 0)) {
                    $potentialMatches[] = array('pathIndex' => $haystackSelectorIndex, 'index' => $hackstackElementIndex, 'matched' => 0, 'initialCombinator' => $haystackElement->combinator);
                    $potentialMatches_len++;
                }

                for ($i = 0; $i < $potentialMatches_len; $i++) {
                    $potentialMatch = & $potentialMatches[$i];

                    // selectors add " " onto the first element. When we use & it joins the selectors together, but if we don't
                    // then each selector in haystackSelectorPath has a space before it added in the toCSS phase. so we need to work out
                    // what the resulting combinator will be
                    $targetCombinator = $haystackElement->combinator->value;
                    if ($targetCombinator === '' && $hackstackElementIndex === 0) {
                        $targetCombinator = ' ';
                    }

                    // if we don't match, null our match to indicate failure
                    if (!$this->isElementValuesEqual($needleElements[$potentialMatch['matched']]->value, $haystackElement->value) ||
                        ($potentialMatch['matched'] > 0 && $needleElements[$potentialMatch['matched']]->combinator->value !== $targetCombinator)
                    ) {
                        $potentialMatch = null;
                    } else {
                        $potentialMatch['matched']++;
                    }

                    // if we are still valid and have finished, test whether we have elements after and whether these are allowed
                    if ($potentialMatch) {
                        if ($needleElements_len === false) {
                            $needleElements_len = count($needleElements);
                        }

                        $potentialMatch['finished'] = ($potentialMatch['matched'] === $needleElements_len);

                        if ($potentialMatch['finished'] &&
                            (!$extend->allowAfter && ($hackstackElementIndex + 1 < $haystack_elements_len || $haystackSelectorIndex + 1 < $haystack_path_len))
                        ) {
                            $potentialMatch = null;
                        }
                    }
                    // if null we remove, if not, we are still valid, so either push as a valid match or continue
                    if ($potentialMatch) {
                        if ($potentialMatch['finished']) {
                            $potentialMatch['length'] = $needleElements_len;
                            $potentialMatch['endPathIndex'] = $haystackSelectorIndex;
                            $potentialMatch['endPathElementIndex'] = $hackstackElementIndex + 1; // index after end of match
                            $potentialMatches = array(); // we don't allow matches to overlap, so start matching again
                            $potentialMatches_len = 0;
                            $matches[] = $potentialMatch;
                        }
                    } else {
                        array_splice($potentialMatches, $i, 1);
                        $potentialMatches_len--;
                        $i--;
                    }
                }
            }
        }

        return $matches;
    }

    protected function isElementValuesEqual($elementValue1, $elementValue2)
    {
        if ($elementValue1 === $elementValue2) {
            return true;
        }
        if (is_string($elementValue1) || is_string($elementValue2)) {
            return false;
        }

        if ($elementValue1 instanceof ILess_Node_Attribute) {
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
        if ($elementValue1 instanceof ILess_Node_Selector) {
            $elementValue2 = $elementValue2->value;
            if (!($elementValue2 instanceof ILess_Node_Selector) || count($elementValue1->elements) !== count($elementValue2->elements)) {
                return false;
            }
            for ($i = 0; $i < count($elementValue1->elements); $i++) {
                if ($elementValue1->elements[$i]->combinator->value !== $elementValue2->elements[$i]->combinator->value) {
                    if ($i !== 0 || ($elementValue1->elements[$i]->combinator->value || ' ') !== ($elementValue2->elements[$i]->combinator->value || ' ')) {
                        return false;
                    }
                }
                if (!$this->isElementValuesEqual($elementValue1->elements[$i]->value, $elementValue2->elements[$i]->value)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    public function extendSelector($matches, $selectorPath, $replacementSelector)
    {
        //for a set of matches, replace each match with the replacement selector
        $currentSelectorPathIndex = 0;
        $currentSelectorPathElementIndex = 0;
        $path = array();
        $selectorPath_len = count($selectorPath);

        for ($matchIndex = 0, $matches_len = count($matches); $matchIndex < $matches_len; $matchIndex++) {
            $match = $matches[$matchIndex];
            $selector = $selectorPath[$match['pathIndex']];
            $firstElement = new ILess_Node_Element(
                $match['initialCombinator'], $replacementSelector->elements[0]->value, $replacementSelector->elements[0]->index, $replacementSelector->elements[0]->currentFileInfo
            );

            if ($match['pathIndex'] > $currentSelectorPathIndex && $currentSelectorPathElementIndex > 0) {
                $last_path = end($path);
                $last_path->elements = array_merge($last_path->elements, array_slice($selectorPath[$currentSelectorPathIndex]->elements, $currentSelectorPathElementIndex));
                $currentSelectorPathElementIndex = 0;
                $currentSelectorPathIndex++;
            }

            $newElements = array_merge(
                array_slice($selector->elements, $currentSelectorPathElementIndex, ($match['index'] - $currentSelectorPathElementIndex)) // last parameter of array_slice is different than the last parameter of javascript's slice
                , array($firstElement)
                , array_slice($replacementSelector->elements, 1)
            );

            if ($currentSelectorPathIndex === $match['pathIndex'] && $matchIndex > 0) {
                $last_key = count($path) - 1;
                $path[$last_key]->elements = array_merge($path[$last_key]->elements, $newElements);
            } else {
                $path = array_merge($path, array_slice($selectorPath, $currentSelectorPathIndex, $match['pathIndex']));
                $path[] = new ILess_Node_Selector($newElements);
            }

            $currentSelectorPathIndex = $match['endPathIndex'];
            $currentSelectorPathElementIndex = $match['endPathElementIndex'];
            if ($currentSelectorPathElementIndex >= count($selectorPath[$currentSelectorPathIndex]->elements)) {
                $currentSelectorPathElementIndex = 0;
                $currentSelectorPathIndex++;
            }
        }

        if ($currentSelectorPathIndex < $selectorPath_len && $currentSelectorPathElementIndex > 0) {
            $last_path = end($path);
            $last_path->elements = array_merge($last_path->elements, array_slice($selectorPath[$currentSelectorPathIndex]->elements, $currentSelectorPathElementIndex));
            $currentSelectorPathIndex++;
        }

        $slice_len = $selectorPath_len - $currentSelectorPathIndex;
        $path = array_merge($path, array_slice($selectorPath, $currentSelectorPathIndex, $slice_len));

        return $path;
    }

}

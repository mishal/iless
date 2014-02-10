<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * To CSS Visitor
 *
 * @package ILess
 * @subpackage visitor
 */
class ILess_Visitor_ToCSS extends ILess_Visitor
{
    /**
     * The environment
     *
     * @var ILess_Environment
     */
    protected $env;

    /**
     * Is replacing flag
     *
     * @var boolean
     */
    protected $isReplacing = true;

    /**
     * Charset flag
     *
     * @var boolean
     */
    public $charset = false;

    /**
     * Constructor
     *
     * @param ILess_Environment $env
     */
    public function __construct(ILess_Environment $env)
    {
        parent::__construct();
        $this->env = $env;
    }

    /**
     * @see ILess_Visitor::run
     */
    public function run($root)
    {
        return $this->visit($root);
    }

    /**
     * Returns the environment
     *
     * @return ILess_Environment
     */
    public function getEnvironment()
    {
        return $this->env;
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
        if ($node->variable) {
            return array();
        }

        return $node;
    }

    /**
     * Visits a mixin definition
     *
     * @param ILess_Node_MixinDefinition $node The node
     * @param ILess_VisitorArguments $arguments The arguments
     * @return array
     */
    public function visitMixinDefinition(ILess_Node_MixinDefinition $node, ILess_Visitor_Arguments $arguments)
    {
        // mixin definitions do not get compiled - this means they keep state
        // so we have to clear that state here so it isn't used if toCSS is called twice
        $node->frames = array();
        return array();
    }

    /**
     * Visits a extend node
     *
     * @param ILess_Node_Extend $node The node
     * @param ILess_VisitorArguments $arguments The arguments
     * @return array
     */
    public function visitExtend(ILess_Node_Extend $node, ILess_Visitor_Arguments $arguments)
    {
        return array();
    }

    /**
     * Visits a comment node
     *
     * @param ILess_Node_Comment $node The node
     * @param ILess_VisitorArguments $arguments The arguments
     * @return ILess_Node_Comment
     */
    public function visitComment(ILess_Node_Comment $node, ILess_Visitor_Arguments $arguments)
    {
        if ($node->isSilent($this->getEnvironment())) {
            return array();
        }

        return $node;
    }

    /**
     * Visits a media node
     *
     * @param ILess_Node_Media $node The node
     * @param ILess_Visitor_Arguments $arguments The arguments
     * @return ILess_Node_Media
     */
    public function visitMedia(ILess_Node_Media $node, ILess_Visitor_Arguments $arguments)
    {
        $node->accept($this);
        $arguments->visitDeeper = false;
        if (!count($node->rules)) {
            return array();
        }

        return $node;
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
        if (($node->currentFileInfo && $node->currentFileInfo->reference) && !$node->isReferenced) {
            return array();
        }

        if ($node->name === '@charset') {
            // Only output the debug info together with subsequent @charset definitions
            // a comment (or @media statement) before the actual @charset directive would
            // be considered illegal css as it has to be on the first line
            if ($this->charset) {
                if ($node->debugInfo) {
                    $comment = new ILess_Node_Comment(sprintf("/* %s */\n", str_replace("\n", '', $node->toCSS($this->getEnvironment()))));
                    $comment->debugInfo = $node->debugInfo;

                    return $this->visit($comment);
                }

                return array();
            }
            $this->charset = true;
        }

        return $node;
    }

    /**
     * Visits a ruleset node
     *
     * @param ILess_Node_Ruleset $node The node
     * @param ILess_Visitor_Arguments $arguments The arguments
     * @return array|ILess_Node_Ruleset
     */
    public function visitRuleset(ILess_Node_Ruleset $node, ILess_Visitor_Arguments $arguments)
    {
        $arguments->visitDeeper = false;
        $rulesets = array();

        if ($node->firstRoot) {
            $this->checkPropertiesInRoot($node->rules);
        }

        if (!$node->root) {
            $paths = array();
            foreach ($node->paths as $p) {
                if ($p[0]->elements[0]->combinator->value === ' ') {
                    $p[0]->elements[0]->combinator = new ILess_Node_Combinator('');
                }
                if ($p[0]->getIsReferenced() && $p[0]->getIsOutput()) {
                    $paths[] = $p;
                }
            }

            $node->paths = $paths;

            // Compile rules and rulesets
            for ($i = 0, $count = count($node->rules); $i < $count;) {
                $rule = $node->rules[$i];
                //if ($rule instanceof ILess_Node_Rule || $rule instanceof ILess_Node_Ruleset) {
                //if ($rule instanceof ILess_Node_Ruleset) {
                if (ILess_Node::propertyExists($rule, 'rules')) {
                    // visit because we are moving them out from being a child
                    $rulesets[] = $this->visit($rule);
                    array_splice($node->rules, $i, 1);
                    $count--;
                    continue;
                }
                $i++;
            }

            // accept the visitor to remove rules and refactor itself
            // then we can decide now whether we want it or not
            if ($count > 0) {
                $node->accept($this);
                $count = count($node->rules);
                if ($count > 0) {
                    if ($count > 1) {
                        $this->mergeRules($node->rules);
                        $this->removeDuplicateRules($node->rules);
                    }
                    // now decide whether we keep the ruleset
                    if (count($node->paths) > 0) {
                        array_splice($rulesets, 0, 0, array($node));
                    }
                }
            } else {
                $node->rules = array();
            }
        } else {
            $node->accept($this);
            $arguments->visitDeeper = false;
            if ($node->firstRoot || count($node->rules) > 0) {
                array_splice($rulesets, 0, 0, array($node));
            }
        }
        if (count($rulesets) === 1) {
            return $rulesets[0];
        }

        return $rulesets;
    }

    /**
     * Checks properties for presence in selector blocks
     *
     * @param array $rules
     * @throws ILess_CompilerException
     */
    protected function checkPropertiesInRoot($rules)
    {
        for ($i = 0, $count = count($rules); $i < $count; $i++) {
            $ruleNode = $rules[$i];
            if ($ruleNode instanceof ILess_Node_Rule && !$ruleNode->variable) {
                throw new ILess_Exception_Compiler(
                    'Properties must be inside selector blocks, they cannot be in the root.',
                    $ruleNode->index,
                    $ruleNode->currentFileInfo
                );
            }
        }
    }

    /**
     * Merges rules
     *
     * @param array $rules
     */
    protected function mergeRules(array &$rules)
    {
        $groups = array();
        for ($i = 0; $i < count($rules); $i++) {
            $rule = $rules[$i];
            if (($rule instanceof ILess_Node_Rule) && $rule->merge) {
                $key = $rule->name;
                if ($rule->important) {
                    $key .= ',!';
                }
                if (!isset($groups[$key])) {
                    $groups[$key] = array();
                    $parts = & $groups[$key];
                } else {
                    array_splice($rules, $i--, 1);
                }
                $parts[] = $rule;
            }
        }

        foreach ($groups as $parts) {
            if (count($parts) > 1) {
                $rule = $parts[0];
                $values = array();
                foreach ($parts as $p) {
                    $values[] = $p->value;
                }
                $rule->value = new ILess_Node_Value($values);
            }
        }
    }

    /**
     * Removes duplicates
     *
     * @param array $rules
     */
    protected function removeDuplicateRules(array &$rules)
    {
        // remove duplicates
        $ruleCache = array();
        for ($i = count($rules) - 1; $i >= 0; $i--) {
            $rule = $rules[$i];
            if ($rule instanceof ILess_Node_Rule) {
                if (!isset($ruleCache[$rule->name])) {
                    $ruleCache[$rule->name] = $rule;
                } else {
                    $ruleList = & $ruleCache[$rule->name];
                    if ($ruleList instanceof ILess_Node_Rule) {
                        $ruleList = $ruleCache[$rule->name] = array($ruleCache[$rule->name]->toCSS($this->getEnvironment()));
                    }
                    $ruleCSS = $rule->toCSS($this->getEnvironment());
                    if (array_search($ruleCSS, $ruleList) !== false) {
                        array_splice($rules, $i, 1);
                    } else {
                        $ruleList[] = $ruleCSS;
                    }
                }
            }
        }
    }

}

<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Visitor;

use ILess\Context;
use ILess\Exception\CompilerException;
use ILess\Node;
use ILess\Node\AnonymousNode;
use ILess\Node\CombinatorNode;
use ILess\Node\CommentNode;
use ILess\Node\DirectiveNode;
use ILess\Node\ExpressionNode;
use ILess\Node\ExtendNode;
use ILess\Node\ImportNode;
use ILess\Node\MediaNode;
use ILess\Node\MixinDefinitionNode;
use ILess\Node\ReferencedInterface;
use ILess\Node\RuleNode;
use ILess\Node\RulesetNode;
use ILess\Node\SelectorNode;
use ILess\Node\ValueNode;

/**
 * To CSS visitor.
 */
class ToCSSVisitor extends Visitor
{
    /**
     * The context.
     *
     * @var Context
     */
    protected $context;

    /**
     * Is replacing flag.
     *
     * @var bool
     */
    protected $isReplacing = true;

    /**
     * Charset flag.
     *
     * @var bool
     */
    public $charset = false;

    /**
     * Constructor.
     *
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        parent::__construct();
        $this->context = $context;
    }

    /**
     * Returns the context.
     *
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
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
        if ($node->variable) {
            return;
        }

        return $node;
    }

    /**
     * Visits a mixin definition.
     *
     * @param MixinDefinitionNode $node The node
     * @param VisitorArguments $arguments The arguments
     *
     * @return array
     */
    public function visitMixinDefinition(MixinDefinitionNode $node, VisitorArguments $arguments)
    {
        // mixin definitions do not get compiled - this means they keep state
        // so we have to clear that state here so it isn't used if toCSS is called twice
        $node->frames = [];
    }

    /**
     * Visits a extend node.
     *
     * @param ExtendNode $node The node
     * @param VisitorArguments $arguments The arguments
     */
    public function visitExtend(ExtendNode $node, VisitorArguments $arguments)
    {
    }

    /**
     * Visits a comment node.
     *
     * @param CommentNode $node The node
     * @param VisitorArguments $arguments The arguments
     *
     * @return CommentNode|null
     */
    public function visitComment(CommentNode $node, VisitorArguments $arguments)
    {
        if ($node->isSilent($this->getContext())) {
            return;
        }

        return $node;
    }

    /**
     * Visits a media node.
     *
     * @param MediaNode $node The node
     * @param VisitorArguments $arguments The arguments
     *
     * @return MediaNode|null
     */
    public function visitMedia(MediaNode $node, VisitorArguments $arguments)
    {
        $node->accept($this);
        $arguments->visitDeeper = false;

        if (!count($node->rules)) {
            return;
        }

        return $node;
    }

    /**
     * Visits a import node.
     *
     * @param ImportNode $node The node
     * @param VisitorArguments $arguments The arguments
     *
     * @return ImportNode|null
     */
    public function visitImport(ImportNode $node, VisitorArguments $arguments)
    {
        if ($node->path->currentFileInfo->reference !== false && $node->css) {
            return;
        }

        return $node;
    }

    /**
     * Visits a directive node.
     *
     * @param DirectiveNode $node The node
     * @param VisitorArguments $arguments The arguments
     *
     * @return DirectiveNode|null
     */
    public function visitDirective(DirectiveNode $node, VisitorArguments $arguments)
    {
        if ($node->name === '@charset') {
            if (!$node->getIsReferenced()) {
                return;
            }
            // Only output the debug info together with subsequent @charset definitions
            // a comment (or @media statement) before the actual @charset directive would
            // be considered illegal css as it has to be on the first line
            if ($this->charset) {
                if ($node->debugInfo) {
                    $comment = new CommentNode(sprintf("/* %s */\n",
                        str_replace("\n", '', $node->toCSS($this->getContext()))));
                    $comment->debugInfo = $node->debugInfo;

                    return $this->visit($comment);
                }

                return;
            }
            $this->charset = true;
        }

        if (count($node->rules)) {
            // it is still true that it is only one ruleset in array
            // this is last such moment
            $this->mergeRules($node->rules[0]->rules);

            $node->accept($this);
            $arguments->visitDeeper = false;
            // the directive was directly referenced and therefore needs to be shown in the output
            if ($node->getIsReferenced()) {
                return $node;
            }

            if (!count($node->rules)) {
                return;
            }

            // the directive was not directly referenced - we need to check whether some of its children
            // was referenced
            if ($this->hasVisibleChild($node)) {
                // marking as referenced in case the directive is stored inside another directive
                $node->markReferenced();

                return $node;
            }

            // The directive was not directly referenced and does not contain anything that
            // was referenced. Therefore it must not be shown in output.
            return;
        } else {
            if (!$node->getIsReferenced()) {
                return;
            }
        }

        return $node;
    }

    /**
     * @param DirectiveNode $node
     *
     * @return bool
     */
    private function hasVisibleChild(DirectiveNode $node)
    {
        $bodyRules = &$node->rules;

        // if there is only one nested ruleset and that one has no path, then it is
        // just fake ruleset that got not replaced and we need to look inside it to
        // get real children
        if (count($bodyRules) === 1 && (count($bodyRules[0]->paths) === 0)) {
            $bodyRules = $bodyRules[0]->rules;
        }

        for ($r = 0; $r < count($bodyRules); ++$r) {
            $rule = $bodyRules[$r];
            if ($rule instanceof ReferencedInterface && $rule->getIsReferenced()) {
                // the directive contains something that was referenced (likely by extend)
                // therefore it needs to be shown in output too
                return true;
            }
        }

        return false;
    }

    /**
     * Visits a ruleset node.
     *
     * @param RulesetNode $node The node
     * @param VisitorArguments $arguments The arguments
     *
     * @return array|RulesetNode
     */
    public function visitRuleset(RulesetNode $node, VisitorArguments $arguments)
    {
        if ($node->firstRoot) {
            $this->checkPropertiesInRoot($node->rules);
        }

        $rulesets = [];

        if (!$node->root) {
            $paths = [];
            foreach ($node->paths as $p) {
                if ($p[0]->elements[0]->combinator->value === ' ') {
                    $p[0]->elements[0]->combinator = new CombinatorNode('');
                }
                foreach ($p as $pi) {
                    /* @var $pi SelectorNode */
                    if ($pi->getIsReferenced() && $pi->getIsOutput()) {
                        $paths[] = $p;
                        break;
                    }
                }
            }

            $node->paths = $paths;

            // Compile rules and rulesets
            for ($i = 0, $count = count($node->rules); $i < $count;) {
                $rule = $node->rules[$i];
                //if ($rule instanceof ILess\ILess\Node\RuleNode || $rule instanceof ILess\ILess\Node\RulesetNode) {
                //if ($rule instanceof ILess\ILess\Node\RulesetNode) {
                if (Node::propertyExists($rule, 'rules')) {
                    // visit because we are moving them out from being a child
                    $rulesets[] = $this->visit($rule);
                    array_splice($node->rules, $i, 1);
                    --$count;
                    continue;
                }
                ++$i;
            }

            // accept the visitor to remove rules and refactor itself
            // then we can decide now whether we want it or not
            if ($count > 0) {
                $node->accept($this);
            } else {
                $node->rules = [];
            }

            $arguments->visitDeeper = false;

            // accept the visitor to remove rules and refactor itself
            // then we can decide now whether we want it or not
            if ($node->rules) {
                // passed by reference
                $this->mergeRules($node->rules);
            }

            if ($node->rules) {
                // passed by reference
                $this->removeDuplicateRules($node->rules);
            }

            // now decide whether we keep the ruleset
            if ($node->rules && $node->paths) {
                array_splice($rulesets, 0, 0, [$node]);
            }
        } else {
            $node->accept($this);
            $arguments->visitDeeper = false;
            if ($node->firstRoot || count($node->rules) > 0) {
                array_splice($rulesets, 0, 0, [$node]);
            }
        }

        if (count($rulesets) === 1) {
            return $rulesets[0];
        }

        return $rulesets;
    }

    /**
     * Visits anonymous node.
     *
     * @param AnonymousNode $node
     * @param VisitorArguments $arguments
     */
    public function visitAnonymous(AnonymousNode $node, VisitorArguments $arguments)
    {
        if (!$node->getIsReferenced()) {
            return;
        }

        $node->accept($this);

        return $node;
    }

    /**
     * Checks properties for presence in selector blocks.
     *
     * @param array $rules
     *
     * @throws CompilerException
     */
    private function checkPropertiesInRoot($rules)
    {
        for ($i = 0, $count = count($rules); $i < $count; ++$i) {
            $ruleNode = $rules[$i];
            if ($ruleNode instanceof RuleNode && !$ruleNode->variable) {
                throw new CompilerException(
                    'Properties must be inside selector blocks, they cannot be in the root.',
                    $ruleNode->index,
                    $ruleNode->currentFileInfo
                );
            }
        }
    }

    /**
     * Merges rules.
     *
     * @param array $rules
     */
    private function mergeRules(array &$rules)
    {
        $groups = [];
        for ($i = 0, $rulesCount = count($rules); $i < $rulesCount; ++$i) {
            $rule = $rules[$i];
            if (($rule instanceof RuleNode) && $rule->merge) {
                $key = $rule->name;
                if ($rule->important) {
                    $key .= ',!';
                }
                if (!isset($groups[$key])) {
                    $groups[$key] = [];
                } else {
                    array_splice($rules, $i--, 1);
                    --$rulesCount;
                }
                $groups[$key][] = $rule;
            }
        }

        foreach ($groups as $parts) {
            if (count($parts) > 1) {
                $rule = $parts[0];
                $spacedGroups = [];
                $lastSpacedGroup = [];
                foreach ($parts as $p) {
                    if ($p->merge === '+') {
                        if (count($lastSpacedGroup) > 0) {
                            $spacedGroups[] = $this->toExpression($lastSpacedGroup);
                        }
                        $lastSpacedGroup = [];
                    }
                    $lastSpacedGroup[] = $p;
                }
                $spacedGroups[] = $this->toExpression($lastSpacedGroup);
                $rule->value = $this->toValue($spacedGroups);
            }
        }
    }

    /**
     * Removes duplicates.
     *
     * @param array $rules
     */
    private function removeDuplicateRules(array &$rules)
    {
        // remove duplicates
        $ruleCache = [];
        for ($i = count($rules) - 1; $i >= 0; --$i) {
            $rule = $rules[$i];
            if ($rule instanceof RuleNode) {
                $key = serialize($rule->name);
                if (!isset($ruleCache[$key])) {
                    $ruleCache[$key] = $rule;
                } else {
                    $ruleList = &$ruleCache[$key];
                    if ($ruleList instanceof RuleNode) {
                        $ruleList = $ruleCache[$key] = [$ruleCache[$key]->toCSS($this->getContext())];
                    }
                    $ruleCSS = $rule->toCSS($this->getContext());
                    if (array_search($ruleCSS, $ruleList) !== false) {
                        array_splice($rules, $i, 1);
                    } else {
                        $ruleList[] = $ruleCSS;
                    }
                }
            }
        }
    }

    /**
     * @param $values
     *
     * @return ExpressionNode
     */
    private function toExpression($values)
    {
        $mapped = [];
        foreach ($values as $p) {
            $mapped[] = $p->value;
        }

        return new ExpressionNode($mapped);
    }

    /**
     * @param $values
     *
     * @return ValueNode
     */
    private function toValue($values)
    {
        return new ValueNode($values);
    }
}

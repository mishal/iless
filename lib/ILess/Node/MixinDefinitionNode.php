<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Node;

use ILess\Context;
use ILess\Exception\CompilerException;
use ILess\Visitor\VisitorInterface;

/**
 * MixinDefinition.
 */
class MixinDefinitionNode extends RulesetNode
{
    /**
     * Node type.
     *
     * @var string
     */
    protected $type = 'MixinDefinition';

    /**
     * The definition name.
     *
     * @var string
     */
    public $name;

    /**
     * Array of selectors.
     *
     * @var array
     */
    public $selectors;

    /**
     * Array of parameters.
     *
     * @var array
     */
    public $params = [];

    /**
     * The arity.
     *
     * @var int
     */
    public $arity = 0;

    /**
     * Array of rules.
     *
     * @var array
     */
    public $rules = [];

    /**
     * Lookups cache array.
     *
     * @var array
     */
    public $lookups = [];

    /**
     * Number of required parameters.
     *
     * @var int
     */
    public $required = 0;

    /**
     * Frames array.
     *
     * @var array
     */
    public $frames = [];

    /**
     * The condition.
     *
     * @var ConditionNode
     */
    public $condition;

    /**
     * Variadic flag.
     *
     * @var bool
     */
    public $variadic = false;

    /**
     * @var bool
     */
    public $compileFirst = true;

    /**
     * @var array
     */
    protected $optionalParameters = [];

    /**
     * Constructor.
     *
     * @param string $name
     * @param array $params Array of parameters
     * @param array $rules
     * @param ConditionNode $condition
     * @param bool $variadic
     */
    public function __construct(
        $name,
        array $params = [],
        array $rules = [],
        $condition = null,
        $variadic = false,
        $frames = []
    ) {
        $this->name = $name;
        $this->selectors = [new SelectorNode([new ElementNode(null, $name)])];

        $this->params = $params;
        $this->condition = $condition;
        $this->variadic = (boolean) $variadic;
        $this->rules = $rules;
        $this->required = 0;

        if ($params) {
            $this->arity = count($params);
            foreach ($params as $p) {
                if (!isset($p['name']) || ($p['name'] && !isset($p['value']))) {
                    ++$this->required;
                } else {
                    $this->optionalParameters[] = $p['name'];
                }
            }
        }

        $this->frames = $frames;
    }

    /**
     * {@inheritdoc}
     */
    public function accept(VisitorInterface $visitor)
    {
        if ($this->params) {
            $this->params = $visitor->visitArray($this->params);
        }

        $this->rules = $visitor->visitArray($this->rules);

        if ($this->condition) {
            $this->condition = $visitor->visit($this->condition);
        }
    }

    /**
     * Compiles the node.
     *
     * @param Context $context The context
     * @param array|null $arguments Array of arguments
     * @param bool|null $important Important flag
     *
     * @return MixinDefinitionNode
     */
    public function compile(Context $context, $arguments = null, $important = null)
    {
        return new self(
            $this->name,
            $this->params,
            $this->rules,
            $this->condition,
            $this->variadic,
            count($this->frames) ? $this->frames : $context->frames
        );
    }

    /**
     * {@inheritdoc}
     */
    public function compileCall(Context $context, $arguments = null, $important = null)
    {
        $mixinFrames = array_merge($this->frames, $context->frames);
        $mixinEnv = Context::createCopyForCompilation($context, $mixinFrames);

        $compiledArguments = [];
        $frame = $this->compileParams($context, $mixinEnv, (array) $arguments, $compiledArguments);

        // use array_values so the array keys are reset
        $ex = new ExpressionNode(array_values($compiledArguments));
        array_unshift($frame->rules, new RuleNode('@arguments', $ex->compile($context)));

        $ruleset = new RulesetNode([], $this->rules);
        $ruleset->originalRuleset = $this;

        $ruleSetEnv = Context::createCopyForCompilation($context, array_merge([$this, $frame], $mixinFrames));
        $ruleset = $ruleset->compile($ruleSetEnv);

        if ($important) {
            $ruleset = $ruleset->makeImportant();
        }

        return $ruleset;
    }

    /**
     * Compile parameters.
     *
     * @param Context $context The context
     * @param Context $mixinEnv The mixin environment
     * @param array $arguments Array of arguments
     * @param array $compiledArguments The compiled arguments
     *
     * @throws
     *
     * @return mixed
     */
    public function compileParams(
        Context $context,
        Context $mixinEnv,
        $arguments = [],
        array &$compiledArguments = []
    ) {
        $frame = new RulesetNode([], []);
        $params = $this->params;
        $argsCount = 0;

        if (isset($mixinEnv->frames[0]) && $mixinEnv->frames[0]->functionRegistry) {
            $frame->functionRegistry = $mixinEnv->frames[0]->functionRegistry->inherit();
        }

        // create a copy of mixin environment
        $mixinEnv = Context::createCopyForCompilation($mixinEnv, array_merge([$frame], $mixinEnv->frames));

        if ($arguments) {
            $argsCount = count($arguments);
            for ($i = 0; $i < $argsCount; ++$i) {
                if (!isset($arguments[$i])) {
                    continue;
                }
                $arg = $arguments[$i];
                if (isset($arg['name']) && $name = $arg['name']) {
                    $isNamedFound = false;
                    foreach ($params as $j => $param) {
                        if (!isset($compiledArguments[$j]) && $name === $params[$j]['name']) {
                            $compiledArguments[$j] = $arg['value']->compile($context);
                            array_unshift($frame->rules, new RuleNode($name, $arg['value']->compile($context)));
                            $isNamedFound = true;
                            break;
                        }
                    }
                    if ($isNamedFound) {
                        array_splice($arguments, $i, 1);
                        --$i;
                        continue;
                    } else {
                        throw new CompilerException(sprintf('The named argument for `%s` %s was not found.',
                            $this->name, $arguments[$i]['name']));
                    }
                }
            }
        }

        $argIndex = 0;
        foreach ($params as $i => $param) {
            if (array_key_exists($i, $compiledArguments)) {
                continue;
            }
            $arg = null;
            if (array_key_exists($argIndex, $arguments)) {
                $arg = $arguments[$argIndex];
            }
            if (isset($param['name']) && ($name = $param['name'])) {
                if (isset($param['variadic']) && $param['variadic']) {
                    $varArgs = [];
                    for ($j = $argIndex; $j < $argsCount; ++$j) {
                        $varArgs[] = $arguments[$j]['value']->compile($context);
                    }
                    $expression = new ExpressionNode($varArgs);
                    array_unshift($frame->rules, new RuleNode($name, $expression->compile($context)));
                } else {
                    $val = ($arg && $arg['value']) ? $arg['value'] : false;
                    if ($val) {
                        $val = $val->compile($context);
                    } elseif (isset($param['value'])) {
                        $val = $param['value']->compile($mixinEnv);
                        $frame->resetCache();
                    } else {
                        throw new CompilerException(
                            sprintf('Wrong number of arguments for `%s` (%s for %s)',
                                $this->name, count($arguments), $this->arity)
                        );
                    }

                    array_unshift($frame->rules, new RuleNode($name, $val));
                    $compiledArguments[$i] = $val;
                }
            }

            if (isset($param['variadic']) && $param['variadic'] && $arguments) {
                for ($j = $argIndex; $j < $argsCount; ++$j) {
                    $compiledArguments[$j] = $arguments[$j]['value']->compile($context);
                }
            }
            ++$argIndex;
        }

        ksort($compiledArguments);

        return $frame;
    }

    /**
     * Match a condition.
     *
     * @param array $arguments
     * @param Context $context
     *
     * @return bool
     */
    public function matchCondition(array $arguments, Context $context)
    {
        if (!$this->condition) {
            return true;
        }

        $frame = $this->compileParams(
            $context,
            Context::createCopyForCompilation($context, array_merge($this->frames, $context->frames)),
            $arguments
        );

        $compileEnv = Context::createCopyForCompilation($context, array_merge(
            [$frame], $this->frames, $context->frames
        ));

        if (!$this->condition->compile($compileEnv)) {
            return false;
        }

        return true;
    }

    /**
     * Matches arguments.
     *
     * @param array $args
     * @param Context $context
     *
     * @return bool
     */
    public function matchArgs(array $args, Context $context)
    {
        $argsLength = count($args);

        $requiredArgsCount = 0;
        foreach ($args as $arg) {
            if (!isset($arg['name']) || !in_array($arg['name'], $this->optionalParameters)) {
                ++$requiredArgsCount;
            }
        }

        if (!$this->variadic) {
            if ($requiredArgsCount < $this->required) {
                return false;
            }
            if ($argsLength > count($this->params)) {
                return false;
            }
        } else {
            if ($requiredArgsCount < ($this->required - 1)) {
                return false;
            }
        }

        $len = min($requiredArgsCount, $this->arity);

        for ($i = 0; $i < $len; ++$i) {
            if (!isset($this->params[$i]['name']) && !isset($this->params[$i]['variadic'])) {
                if ($args[$i]['value']->compile($context)->toCSS($context) != $this->params[$i]['value']->compile($context)->toCSS($context)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Returns ruleset with nodes marked as important.
     *
     * @return MixinDefinitionNode
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

        return new self($this->name, $this->params, $importantRules, $this->condition, $this->variadic,
            $this->frames);
    }
}

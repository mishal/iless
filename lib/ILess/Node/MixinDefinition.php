<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * MixinDefinition
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_MixinDefinition extends ILess_Node_Ruleset
{
    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'MixinDefinition';

    /**
     * The definition name
     *
     * @var string
     */
    public $name;

    /**
     * Array of selectors
     *
     * @var array
     */
    public $selectors;

    /**
     * Array of parameters
     *
     * @var array
     */
    public $params = array();

    /**
     * The arity
     *
     * @var integer
     */
    public $arity = 0;

    /**
     * Array of rules
     *
     * @var array
     */
    public $rules = array();

    /**
     * Lookups cache array
     *
     * @var array
     */
    public $lookups = array();

    /**
     * Number of required parameters
     *
     * @var integer
     */
    public $required = 0;

    /**
     * Frames array
     *
     * @var array
     */
    public $frames = array();

    /**
     * The condition
     *
     * @var ILess_Node_Condition
     */
    public $condition;

    /**
     * Variadic flag
     *
     * @var boolean
     */
    public $variadic = false;

    /**
     * Constructor
     *
     * @param string $name
     * @param array $params Array of parameters
     * @param array $rules
     * @param ILess_Node_Condition $condition
     * @param boolean $variadic
     */
    public function __construct($name, array $params = array(), array $rules = array(), $condition = null, $variadic = false)
    {
        $this->name = $name;
        $this->selectors = array(new ILess_Node_Selector(array(new ILess_Node_Element(null, $name))));

        $this->params = $params;
        $this->condition = $condition;
        $this->variadic = (boolean)$variadic;
        $this->arity = count($params);
        $this->rules = $rules;

        $this->required = 0;
        foreach ($params as $p) {
            if (!isset($p['name']) || ($p['name'] && !isset($p['value']))) {
                $this->required++;
            }
        }

        $this->setRulesetId();
    }

    /**
     * @see ILess_Node_VisitableInterface::accept
     */
    public function accept(ILess_Visitor $visitor)
    {
        if (count($this->params)) {
            // FIXME: is its correct format?
            // array of (name => $name, value => ILess_Expression instance)
            foreach ($this->params as &$param) {
                if (!isset($param['value']) || !($param['value'] instanceof ILess_Node)) {
                    continue;
                }
                $param['value'] = $visitor->visit($param['value']);
            }
        }
        $this->rules = $visitor->visit($this->rules);
        $this->condition = $visitor->visit($this->condition);
    }

    /**
     * @see ILess_Node::compile
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        $mixinFrames = array_merge($this->frames, $env->frames);
        $mixinEnv = ILess_Environment::createCopy($env, $mixinFrames);

        $compiledArguments = array();
        $frame = $this->compileParams($env, $mixinEnv, (array)$arguments, $compiledArguments);

        $ex = new ILess_Node_Expression($compiledArguments);
        array_unshift($frame->rules, new ILess_Node_Rule('@arguments', $ex->compile($env)));

        $rules = array_slice($this->rules, 0);
        $ruleset = new ILess_Node_Ruleset(array(), $rules);
        $ruleset->originalRulesetId = $this->rulesetId;

        $ruleSetEnv = ILess_Environment::createCopy($env, array_merge(array($this, $frame), $mixinFrames));
        $ruleset = $ruleset->compile($ruleSetEnv);

        if ($important) {
            $ruleset = $ruleset->makeImportant();
        }

        return $ruleset;
    }

    /**
     * Compile parameters
     *
     * @param ILess_Environment $env The environment
     * @param ILess_Environment $mixinEnv The mixin environment
     * @param array $arguments Array of arguments
     * @param array $compiledArguments The compiled arguments
     */
    public function compileParams(ILess_Environment $env, ILess_Environment $mixinEnv, $arguments = array(), array &$compiledArguments = array())
    {
        $frame = new ILess_Node_Ruleset(array(), array());
        $params = $this->params;

        // create a copy of mixin environment
        $mixinEnv = ILess_Environment::createCopy($mixinEnv, array_merge(array($frame), $mixinEnv->frames));

        for ($i = 0; $i < count($arguments); $i++) {
            $arg = $arguments[$i];
            if ($name = $arg['name']) {
                $isNamedFound = false;
                foreach ($params as $j => $param) {
                    if (!isset($compiledArguments[$j]) && $name === $params[$j]['name']) {
                        $compiledArguments[$j] = $arg['value']->compile($env);
                        array_unshift($frame->rules, new ILess_Node_Rule($name, $arg['value']->compile($env)));
                        $isNamedFound = true;
                        break;
                    }
                }
                if ($isNamedFound) {
                    array_splice($arguments, $i, 1);
                    $i--;
                    continue;
                } else {
                    throw new ILess_Exception_Compiler(sprintf('The named argument for `%s` %s was not found.', $this->name, $arguments[$i]['name']));
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
                if (isset($param['variadic']) && $arguments) {
                    $varArgs = array();
                    for ($j = $argIndex, $count = count($arguments); $j < $count; $j++) {
                        $varArgs[] = $arguments[$j]['value']->compile($env);
                    }
                    $expression = new ILess_Node_Expression($varArgs);
                    array_unshift($frame->rules, new ILess_Node_Rule($name, $expression->compile($env)));
                } else {
                    $val = ($arg && $arg['value']) ? $arg['value'] : false;
                    if ($val) {
                        $val = $val->compile($env);
                    } elseif (isset($param['value'])) {
                        $val = $param['value']->compile($mixinEnv);
                        $frame->resetCache();
                    } else {
                        throw new ILess_Exception_Compiler(
                            sprintf('Wrong number of arguments for `%s` (%s for %s)',
                                $this->name, count($arguments), $this->arity)
                        );
                    }

                    array_unshift($frame->rules, new ILess_Node_Rule($name, $val));
                    $compiledArguments[$i] = $val;
                }
            }

            if (isset($param['variadic']) && $arguments) {
                for ($j = $argIndex, $count = count($arguments); $j < $count; $j++) {
                    $compiledArguments[$j] = $arguments[$j]['value']->compile($env);
                }
            }
            $argIndex++;
        }

        ksort($compiledArguments);

        return $frame;
    }

    /**
     * Match a condition
     *
     * @param array $arguments
     * @param ILess_Environment $env
     * @return boolean
     */
    public function matchCondition(array $arguments, ILess_Environment $env)
    {
        if (!$this->condition) {
            return true;
        }

        $frame = $this->compileParams(
            $env,
            ILess_Environment::createCopy($env, array_merge($this->frames, $env->frames)),
            $arguments
        );

        $compileEnv = ILess_Environment::createCopy($env, array_merge(
            array($frame), $this->frames, $env->frames
        ));

        if (!$this->condition->compile($compileEnv)) {
            return false;
        }

        return true;
    }

    /**
     * Matches arguments
     *
     * @param array $args
     * @param ILess_Environment $env
     * @return boolean
     */
    public function matchArgs(array $args, ILess_Environment $env)
    {
        $argsLength = count($args);

        if (!$this->variadic) {
            if ($argsLength < $this->required) {
                return false;
            }
            if ($argsLength > count($this->params)) {
                return false;
            }
        } else {
            if ($argsLength < ($this->required - 1)) {
                return false;
            }
        }

        $len = min($argsLength, $this->arity);

        for ($i = 0; $i < $len; $i++) {
            if (!isset($this->params[$i]['name']) && !isset($this->params[$i]['variadic'])) {
                if ($args[$i]['value']->compile($env)->toCSS($env) != $this->params[$i]['value']->compile($env)->toCSS($env)) {
                    return false;
                }
            }
        }

        return true;
    }

}

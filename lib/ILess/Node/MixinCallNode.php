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
use ILess\Exception\Exception;
use ILess\Exception\CompilerException;
use ILess\Exception\ParserException;
use ILess\FileInfo;
use ILess\Node;
use ILess\Visitor\VisitorInterface;

/**
 * Mixin call.
 */
class MixinCallNode extends Node
{
    /**
     * Node type.
     *
     * @var string
     */
    protected $type = 'MixinCall';

    /**
     * The selector.
     *
     * @var SelectorNode
     */
    public $selector;

    /**
     * Array of arguments.
     *
     * @var array
     */
    public $arguments = [];

    /**
     * Current index.
     *
     * @var int
     */
    public $index = 0;

    /**
     * The important flag.
     *
     * @var bool
     */
    public $important = false;

    /**
     * Constructor.
     *
     * @param array $elements The elements
     * @param array $arguments The array of arguments
     * @param int $index The current index
     * @param FileInfo $currentFileInfo
     * @param bool $important
     */
    public function __construct(
        array $elements,
        array $arguments = [],
        $index = 0,
        FileInfo $currentFileInfo = null,
        $important = false
    ) {
        $this->selector = new SelectorNode($elements);

        $this->arguments = $arguments;
        $this->index = $index;
        $this->currentFileInfo = $currentFileInfo;
        $this->important = (boolean) $important;
    }

    /**
     * {@inheritdoc}
     */
    public function accept(VisitorInterface $visitor)
    {
        if ($this->selector) {
            $this->selector = $visitor->visit($this->selector);
        }

        if ($this->arguments) {
            $this->arguments = $visitor->visitArray($this->arguments);
        }
    }

    /**
     * Compiles the node.
     *
     * @param Context $context The context
     * @param array|null $arguments Array of arguments
     * @param bool|null $important Important flag
     *
     * @return Node
     *
     * @throws
     */
    public function compile(Context $context, $arguments = null, $important = null)
    {
        $rules = [];
        $match = false;
        $isOneFound = false;
        $candidates = [];
        $conditionResult = [];

        $args = [];
        foreach ($this->arguments as $a) {
            $aValue = $a['value']->compile($context);
            if ($a['expand'] && is_array($aValue->value)) {
                $aValue = $aValue->value;
                for ($m = 0; $m < count($aValue); ++$m) {
                    $args[] = ['value' => $aValue[$m]];
                }
            } else {
                $args[] = [
                    'name' => $a['name'],
                    'value' => $aValue,
                ];
            }
        }

        /*
         * @param $rule
         * @return bool
         */
        $noArgumentsFilter = function ($rule) use ($context) {
            /* @var $rule RulesetNode */
            return $rule->matchArgs([], $context);
        };

        // return values for the function
        $defFalseEitherCase = -1;
        $defNone = 0;
        $defTrue = 1;
        $defFalse = 2;

        /*
         * Calculate
         * @param $mixin
         * @param $mixinPath
         * @return int
         */
        $calcDefGroup = function ($mixin, $mixinPath) use (
            $context,
            $args,
            $defTrue,
            $defFalse,
            $defNone,
            $defFalseEitherCase,
            &$conditionResult
        ) {
            $namespace = null;
            for ($f = 0; $f < 2; ++$f) {
                $conditionResult[$f] = true;
                DefaultFunc::value($f);
                for ($p = 0; $p < count($mixinPath) && $conditionResult[$f]; ++$p) {
                    $namespace = $mixinPath[$p];
                    if ($namespace instanceof ConditionMatchableInterface) {
                        $conditionResult[$f] = $conditionResult[$f] && $namespace->matchCondition([], $context);
                    }
                }
                if ($mixin instanceof ConditionMatchableInterface) {
                    $conditionResult[$f] = $conditionResult[$f] && $mixin->matchCondition($args, $context);
                }
            }

            if ($conditionResult[0] || $conditionResult[1]) {
                if ($conditionResult[0] != $conditionResult[1]) {
                    return $conditionResult[1] ? $defTrue : $defFalse;
                }

                return $defNone;
            }

            return $defFalseEitherCase;
        };

        foreach ($context->frames as $frame) {
            /* @var $frame RulesetNode */
            $mixins = $frame->find($this->selector, $context, null, $noArgumentsFilter);
            if ($mixins) {
                $isOneFound = true;
                for ($m = 0; $m < count($mixins); ++$m) {
                    $mixin = $mixins[$m]['rule'];
                    $mixinPath = $mixins[$m]['path'];
                    $isRecursive = false;
                    foreach ($context->frames as $recurFrame) {
                        if ((!($mixin instanceof MixinDefinitionNode)) && ($mixin === $recurFrame->originalRuleset || $mixin === $recurFrame)) {
                            $isRecursive = true;
                            break;
                        }
                    }

                    if ($isRecursive) {
                        continue;
                    }

                    if ($mixin->matchArgs($args, $context)) {
                        $candidate = [
                            'mixin' => $mixin,
                            'group' => $calcDefGroup($mixin, $mixinPath),
                        ];

                        if ($candidate['group'] !== $defFalseEitherCase) {
                            $candidates[] = $candidate;
                        }

                        $match = true;
                    }
                }

                DefaultFunc::reset();

                $count = [0, 0, 0];
                for ($m = 0; $m < count($candidates); ++$m) {
                    ++$count[$candidates[$m]['group']];
                }

                if ($count[$defNone] > 0) {
                    $defaultResult = $defFalse;
                } else {
                    $defaultResult = $defTrue;
                    if (($count[$defTrue] + $count[$defFalse]) > 1) {
                        throw new ParserException(sprintf('Ambiguous use of `default()` found when matching for `%s`',
                            $this->formatArgs($args)),
                            $this->index,
                            $this->currentFileInfo
                        );
                    }
                }

                for ($m = 0; $m < count($candidates); ++$m) {
                    $candidate = $candidates[$m]['group'];
                    if (($candidate === $defNone) || ($candidate === $defaultResult)) {
                        try {
                            $mixin = $candidates[$m]['mixin'];
                            if (!($mixin instanceof MixinDefinitionNode)) {
                                $originalRuleset = $mixin->originalRuleset ? $mixin->originalRuleset : $mixin;
                                $mixin = new MixinDefinitionNode('', [], $mixin->rules, null, false);
                                $mixin->originalRuleset = $originalRuleset;
                            }
                            $compiled = $mixin->compileCall($context, $args, $this->important);
                            $rules = array_merge($rules, $compiled->rules);
                        } catch (Exception $e) {
                            throw new CompilerException($e->getMessage(), $this->index, $this->currentFileInfo,
                                $e);
                        }
                    }
                }

                if ($match) {
                    if (!$this->currentFileInfo || !$this->currentFileInfo->reference) {
                        foreach ($rules as $rule) {
                            if ($rule instanceof MarkableAsReferencedInterface) {
                                $rule->markReferenced();
                            }
                        }
                    }

                    return $rules;
                }
            }
        }

        if ($isOneFound) {
            throw new CompilerException(
                sprintf('No matching definition was found for `%s`', $this->formatArgs($args)),
                $this->index, $this->currentFileInfo);
        } else {
            throw new CompilerException(
                sprintf('%s is undefined', trim($this->selector->toCSS($context))), $this->index,
                $this->currentFileInfo);
        }
    }

    /**
     * Format arguments to be used for exception message.
     *
     * @param array $args
     *
     * @return string
     */
    private function formatArgs($args)
    {
        $context = new Context();
        $message = $argsFormatted = [];
        $message[] = trim($this->selector->toCSS($context));

        if ($args) {
            $message[] = '(';
            foreach ($args as $a) {
                $argValue = '';
                if (isset($a['name'])) {
                    $argValue .= $a['name'] . ':';
                }
                if ($a['value'] instanceof GenerateCSSInterface) {
                    $argValue .= $a['value']->toCSS($context);
                } else {
                    $argValue .= '???';
                }
                $argsFormatted[] = $argValue;
            }
            $message[] = implode(', ', $argsFormatted);
            $message[] = ')';
        }

        return implode('', $message);
    }
}

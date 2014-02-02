<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Mixin call
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_MixinCall extends ILess_Node implements ILess_Node_VisitableInterface
{
    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'MixinCall';

    /**
     * The selector
     *
     * @var ILess_Node_Selector
     */
    public $selector;

    /**
     * Array of arguments
     *
     * @var array
     */
    public $arguments = array();

    /**
     * Current index
     *
     * @var integer
     */
    public $index = 0;

    /**
     * The important flag
     *
     * @var boolean
     */
    public $important = false;

    /**
     * Constructor
     *
     * @param array $elements The elements
     * @param array $arguments The array of arguments
     * @param integer $index The current index
     * @param ILess_FileInfo $currentFileInfo
     * @param boolean $important
     */
    public function __construct(array $elements, array $arguments = array(), $index = 0, ILess_FileInfo $currentFileInfo = null, $important = false)
    {
        $this->selector = new ILess_Node_Selector($elements);
        $this->arguments = (array)$arguments;
        $this->index = $index;
        $this->currentFileInfo = $currentFileInfo;
        $this->important = (boolean)$important;
    }

    /**
     * Accepts a visit
     *
     * @param ILess_Visitor $visitor
     */
    public function accept(ILess_Visitor $visitor)
    {
        $this->selector = $visitor->visit($this->selector);
        if (count($this->arguments)) {
            foreach ($this->arguments as &$argument) {
                $argument['value'] = $visitor->visit($argument['value']);
            }
        }
    }

    /**
     * @see ILess_Node::compile
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        $rules = array();
        $match = false;
        $isOneFound = false;

        $args = array();
        foreach ($this->arguments as $a) {
            $args[] = array('name' => $a['name'], 'value' => $a['value']->compile($env));
        }

        foreach ($env->frames as $frame) {
            $mixins = $frame->find($this->selector, $env);
            if (!$mixins) {
                continue;
            }

            $isOneFound = true;
            for ($m = 0; $m < count($mixins); $m++) {
                $mixin = $mixins[$m];
                $isRecursive = false;
                foreach ($env->frames as $recurFrame) {
                    if (!($mixin instanceof ILess_Node_MixinDefinition)) {
                        if ((isset($recurFrame->originalRulesetId) && $mixin->rulesetId === $recurFrame->originalRulesetId)
                            || ($mixin === $recurFrame)
                        ) {
                            $isRecursive = true;
                            break;
                        }
                    }
                }
                if ($isRecursive) {
                    continue;
                }
                if ($mixin->matchArgs($args, $env)) {
                    if (!ILess_Node::methodExists($mixin, 'matchCondition') || $mixin->matchCondition($args, $env)) {
                        try {
                            if (!$mixin instanceof ILess_Node_MixinDefinition) {
                                $mixin = new ILess_Node_MixinDefinition('', array(), $mixin->rules, null, false);
                                $mixin->originalRulesetId = $mixins[$m]->originalRulesetId ? $mixins[$m]->originalRulesetId : $mixin->originalRulesetId;
                            }
                            $rules = array_merge($rules, $mixin->compile($env, $args, $this->important)->rules);
                        } catch (Exception $e) {
                            throw new ILess_Exception_Compiler($e->getMessage(), $this->index, $this->currentFileInfo, $e);
                        }
                    }
                    $match = true;
                }
            }
            if ($match) {
                if (!$this->currentFileInfo || !$this->currentFileInfo->reference) {
                    foreach ($rules as $rule) {
                        if ($rule instanceof ILess_Node_MarkableAsReferencedInterface) {
                            $rule->markReferenced();
                        }
                    }
                }

                return $rules;
            }
        }

        if ($isOneFound) {
            $message = array();
            if ($args) {
                foreach ($args as $a) {
                    $argValue = '';
                    if ($a['name']) {
                        $argValue .= $a['name'] . ':';
                    }
                    if (ILess_Node::methodExists($a['value'], 'toCSS')) {
                        $argValue .= $a['value']->toCSS($env);
                    } else {
                        $argValue .= '???';
                    }
                    $message[] = $argValue;
                }
            }

            throw new ILess_Exception_Compiler(
                sprintf('No matching definition was found for `%s(%s)`', trim($this->selector->toCSS($env)), join(',', $message)),
                $this->index, $this->currentFileInfo);
        } else {
            throw new ILess_Exception_Compiler(
                sprintf('%s is undefined.', trim($this->selector->toCSS($env))), $this->index, $this->currentFileInfo);
        }
    }

    /**
     * @see ILess_Node::generateCSS
     */
    public function generateCSS(ILess_Environment $env, ILess_Output $output)
    {
    }

}

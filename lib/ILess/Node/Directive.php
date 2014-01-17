<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Directive
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_Directive extends ILess_Node implements ILess_Node_VisitableInterface, ILess_Node_MarkableAsReferencedInterface
{
    /**
     * The type
     *
     * @var string
     */
    protected $type = 'Directive';

    /**
     * The directive name
     *
     * @var string
     */
    public $name;

    /**
     * Arary of rules
     *
     * @var array
     */
    public $rules = array();

    /**
     * Current index
     *
     * @var integer
     */
    public $index = 0;

    /**
     * Array of variables
     *
     * @var array
     */
    protected $variables;

    /**
     * Is referenced?
     *
     * @var boolean
     */
    public $isReferenced = false;

    /**
     * Constructor
     *
     * @param string $name The name
     * @param array|string $value The value
     * @param integer $index The index
     * @param array $currentFileInfo Current file info
     */
    public function __construct($name, $value, $index = 0, ILess_FileInfo $currentFileInfo = null)
    {
        $this->name = $name;
        if (is_array($value)) {
            $ruleset = new ILess_Node_Ruleset(array(), $value);
            $ruleset->allowImports = true;
            $this->rules = array($ruleset);
        } else {
            if ($value && !$value instanceof ILess_Node) {
                throw new InvalidArgumentException('Invalid value given. It should be an instance of ILess_Node');
            }
            parent::__construct($value);
        }

        $this->index = $index;
        $this->currentFileInfo = $currentFileInfo;
    }

    /**
     * Accepts a visitor
     *
     * @param ILess_Visitor $visitor
     */
    public function accept(ILess_Visitor $visitor)
    {
        $this->rules = $visitor->visit($this->rules);
        $this->value = $visitor->visit($this->value);
    }

    /**
     * @see ILess_Node::generateCSS
     */
    public function generateCSS(ILess_Environment $env, ILess_Output $output)
    {
        $output->add($this->name, $this->currentFileInfo, $this->index);
        if (count($this->rules)) {
            $this->outputRuleset($env, $output, $this->rules);
        } else {
            $output->add(' ');
            $this->value->generateCSS($env, $output);
            $output->add(';');
        }
    }

    /**
     * Compiles the node
     *
     * @param ILess_Environment $env
     * @return ILess_Node_Directive
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        $evaldDirective = $this;
        if ($this->rules) {
            $env->unshiftFrame($this);
            $evaldDirective = new ILess_Node_Directive($this->name, null, $this->index, $this->currentFileInfo);
            $evaldDirective->rules = array($this->rules[0]->compile($env));
            $evaldDirective->rules[0]->root = true;
            $env->shiftFrame();
        }

        return $evaldDirective;
    }

    /**
     * Returns the variable
     *
     * @param string $name
     * @return
     */
    public function variable($name)
    {
        if (isset($this->rules[0])) {
            return $this->rules[0]->variable($name);
        }
    }

    /**
     * Finds a selector
     *
     * @param string $selector
     * @return type
     */
    public function find($selector, ILess_Environment $env)
    {
        if (isset($this->rules[0])) {
            return $this->rules[0]->find($selector, $this, $env);
        }
    }

    /**
     * Returns the rulesets
     * @return
     */
    public function rulesets()
    {
        if (isset($this->rules[0])) {
            return $this->rules[0]->rulesets();
        }
    }

    /**
     * Mark the directive as referenced
     *
     */
    public function markReferenced()
    {
        $this->isReferenced = true;
        if ($this->rules) {
            $rules = $this->rules[0]->rules;
            for ($i = 0; $i < count($rules); $i++) {
                if ($rules[$i] instanceof ILess_Node_MarkableAsReferencedInterface) {
                    $rules[$i]->markReferenced();
                }
            }
        }
    }

}

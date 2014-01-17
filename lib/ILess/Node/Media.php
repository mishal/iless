<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Media
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_Media extends ILess_Node implements ILess_Node_VisitableInterface, ILess_Node_MarkableAsReferencedInterface
{
    /**
     * Media type
     *
     * @var string
     */
    protected $type = 'Media';

    /**
     * Current index
     * @var integer
     */
    public $index = 0;

    /**
     * Features
     *
     * @var ILess_Node_Value
     */
    public $features;

    /**
     * Rules
     *
     * @var array
     */
    public $rules = array();

    /**
     * Referenced flag
     *
     * @var boolean
     */
    public $isReferenced = false;

    /**
     * Constructor
     *
     * @param array $value The array of values
     * @param type $features The array of features
     * @param integer $index The index
     * @param ILess_FileInfo $currentFileInfo The current file info
     */
    public function __construct(array $value = array(), array $features = array(), $index = 0, ILess_FileInfo $currentFileInfo = null)
    {
        $this->index = $index;
        $this->currentFileInfo = $currentFileInfo;

        $selectors = $this->emptySelectors();
        $this->features = new ILess_Node_Value($features);
        $this->rules = array(new ILess_Node_Ruleset($selectors, $value));
        $this->rules[0]->allowImports = true;
    }

    /**
     * Accepts a visit
     *
     * @param ILess_Visitor $visitor
     */
    public function accept(ILess_Visitor $visitor)
    {
        $this->features = $visitor->visit($this->features);
        $this->rules = $visitor->visit($this->rules);
    }

    /**
     * Returns an array of default selectors
     *
     * @return array
     */
    public function emptySelectors()
    {
        $element = new ILess_Node_Element('', '&', $this->index, $this->currentFileInfo);

        return array(
            new ILess_Node_Selector(array($element), array(), null, $this->index, $this->currentFileInfo)
        );
    }

    /**
     * @see ILess_Node::generateCSS
     */
    public function generateCSS(ILess_Environment $env, ILess_Output $output)
    {
        $output->add('@media ', $this->currentFileInfo, $this->index);
        $this->features->generateCSS($env, $output);
        $this->outputRuleset($env, $output, $this->rules);
    }

    /**
     * @see ILess_Node
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        $media = new ILess_Node_Media(array(), array(), $this->index, $this->currentFileInfo);

        if ($this->debugInfo) {
            $this->rules[0]->debugInfo = $this->debugInfo;
            $media->debugInfo = $this->debugInfo;
        }

        $strictMathBypass = false;
        if (!$env->strictMath) {
            $strictMathBypass = true;
            $env->strictMath = true;
        }

        try {
            $media->features = $this->features->compile($env);
        } catch (Exception $e) {
            // empty on purpose
        }

        if ($strictMathBypass) {
            $env->strictMath = false;
        }

        $env->mediaPath[] = $media;
        $env->mediaBlocks[] = $media;

        array_unshift($env->frames, $this->rules[0]);
        $media->rules = array($this->rules[0]->compile($env));
        array_shift($env->frames);

        array_pop($env->mediaPath);

        return count($env->mediaPath) == 0 ? $media->compileTop($env) : $media->compileNested($env);
    }

    /**
     * Compiles top media
     *
     * @param ILess_Environment $env
     * @return ILess_Node_Ruleset
     */
    public function compileTop(ILess_Environment $env)
    {
        $result = $this;
        if (count($env->mediaBlocks) > 1) {
            $selectors = $this->emptySelectors();
            $result = new ILess_Node_Ruleset($selectors, $env->mediaBlocks);
            $result->multiMedia = true;
        }
        $env->mediaBlocks = array();
        $env->mediaPath = array();

        return $result;
    }

    /**
     * Compiles nested media
     *
     * @param ILess_Environment $env
     * @return ILess_Node_Ruleset
     */
    public function compileNested(ILess_Environment $env)
    {
        $path = array_merge($env->mediaPath, array($this));

        // Extract the media-query conditions separated with `,` (OR).
        foreach ($path as $key => $p) {
            $value = $p->features instanceof ILess_Node_Value ? $p->features->value : $p->features;
            $path[$key] = is_array($value) ? $value : array($value);
        }

        // Trace all permutations to generate the resulting media-query.
        //
        // (a, b and c) with nested (d, e) ->
        //a and d
        //a and e
        //b and c and d
        //b and c and e

        $permuted = $this->permute($path);
        $expressions = array();
        foreach ($permuted as $path) {
            for ($i = 0, $len = count($path); $i < $len; $i++) {
                $path[$i] = self::methodExists($path[$i], 'toCSS') ? $path[$i] : new ILess_Node_Anonymous($path[$i]);
            }
            for ($i = count($path) - 1; $i > 0; $i--) {
                array_splice($path, $i, 0, array(new ILess_Node_Anonymous('and')));
            }
            $expressions[] = new ILess_Node_Expression($path);
        }

        $this->features = new ILess_Node_Value($expressions);

        // Fake a tree-node that doesn't output anything.
        return new ILess_Node_Ruleset(array(), array());
    }

    /**
     * Creates permutations
     *
     * @param array $array The array
     * @return array
     */
    public function permute(array $array)
    {
        if (!count($array)) {
            return array();
        } elseif (count($array) === 1) {
            return $array[0];
        } else {
            $result = array();
            $rest = $this->permute(array_slice($array, 1));
            foreach ($rest as $r) {
                foreach ($array[0] as $a) {
                    $result[] = array_merge(
                        is_array($a) ? $a : array($a), is_array($r) ? $r : array($r)
                    );
                }
            }
        }

        return $result;
    }

    /**
     * Bubbles the selectors
     *
     * @param array $selectors
     */
    public function bubbleSelectors(array $selectors)
    {
        $this->rules = array(
            new ILess_Node_Ruleset($selectors, array($this->rules[0]))
        );
    }

    /**
     * Marks all rules as referenced
     *
     */
    public function markReferenced()
    {
        $this->isReferenced = true;
        $rules = $this->rules[0]->rules;
        for ($i = 0; $i < count($rules); $i++) {
            if ($rules[$i] instanceof ILess_Node_MarkableAsReferencedInterface) {
                $rules[$i]->markReferenced();
            }
        }
    }

    /**
     * Returns a variable by its name
     *
     * @param string $name
     * @return
     */
    public function variable($name)
    {
        return $this->rules[0]->variable($name);
    }

    /**
     * Finds a selector
     *
     * @param string $selector
     * @return
     */
    public function find($selector)
    {
        return $this->rules[0]->find($selector, $this, new ILess_Environment());
    }

    /**
     * Returns an array of rulesets
     *
     * @return array
     */
    public function rulesets()
    {
        return $this->rules[0]->rulesets();
    }

}

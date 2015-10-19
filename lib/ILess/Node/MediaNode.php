<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Node;

use ILess\Context;
use ILess\Exception\Exception;
use ILess\FileInfo;
use ILess\Output\OutputInterface;
use ILess\Visitor\VisitorInterface;

/**
 * Media.
 */
class MediaNode extends DirectiveNode
{
    /**
     * Media type.
     *
     * @var string
     */
    protected $type = 'Media';

    /**
     * Current index.
     *
     * @var int
     */
    public $index = 0;

    /**
     * Features.
     *
     * @var ValueNode
     */
    public $features;

    /**
     * Rules.
     *
     * @var array
     */
    public $rules = [];

    /**
     * Referenced flag.
     *
     * @var bool
     */
    public $isReferenced = false;

    /**
     * @var array
     */
    public $allExtends = [];

    /**
     * Constructor.
     *
     * @param array $value The array of values
     * @param array $features The array of features
     * @param int $index The index
     * @param FileInfo $currentFileInfo The current file info
     */
    public function __construct(
        array $value = [],
        array $features = [],
        $index = 0,
        FileInfo $currentFileInfo = null
    ) {
        $this->index = $index;
        $this->currentFileInfo = $currentFileInfo;

        $selector = new SelectorNode([], [], null, $this->index, $this->currentFileInfo);
        $selectors = $selector->createEmptySelectors();

        $this->features = new ValueNode($features);
        $this->rules = [new RulesetNode($selectors, $value)];
        $this->rules[0]->allowImports = true;
    }

    /**
     * {@inheritdoc}
     */
    public function accept(VisitorInterface $visitor)
    {
        if ($this->features) {
            $this->features = $visitor->visit($this->features);
        }

        $this->rules = $visitor->visitArray($this->rules);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCSS(Context $context, OutputInterface $output)
    {
        $output->add('@media ', $this->currentFileInfo, $this->index);
        $this->features->generateCSS($context, $output);
        $this->outputRuleset($context, $output, $this->rules);
    }

    /**
     * Compiles the node.
     *
     * @param Context $context The context
     * @param array|null $arguments Array of arguments
     * @param bool|null $important Important flag
     *
     * @return RulesetNode
     */
    public function compile(Context $context, $arguments = null, $important = null)
    {
        if (!$context->mediaBlocks) {
            $context->mediaBlocks = [];
            $context->mediaPath = [];
        }

        $media = new self([], [], $this->index, $this->currentFileInfo);

        if ($this->debugInfo) {
            $this->rules[0]->debugInfo = $this->debugInfo;
            $media->debugInfo = $this->debugInfo;
        }

        $strictMathBypass = false;
        if (!$context->strictMath) {
            $strictMathBypass = true;
            $context->strictMath = true;
        }

        try {
            $media->features = $this->features->compile($context);
        } catch (Exception $e) {
            // empty on purpose
        }

        if ($strictMathBypass) {
            $context->strictMath = false;
        }

        $context->mediaPath[] = $media;
        $context->mediaBlocks[] = $media;

        $this->rules[0]->functionRegistry =
            isset($context->frames[0]) && $context->frames[0]->functionRegistry ?
                $context->frames[0]->functionRegistry->inherit() :
                $context->getFunctionRegistry()->inherit();

        array_unshift($context->frames, $this->rules[0]);
        $media->rules = [$this->rules[0]->compile($context)];
        array_shift($context->frames);

        array_pop($context->mediaPath);

        return count($context->mediaPath) == 0 ? $media->compileTop($context) : $media->compileNested($context);
    }

    /**
     * Compiles top media.
     *
     * @param Context $context
     *
     * @return RulesetNode
     */
    public function compileTop(Context $context)
    {
        $result = $this;
        if (count($context->mediaBlocks) > 1) {
            $selector = new SelectorNode([], [], null, $this->index, $this->currentFileInfo);
            $selectors = $selector->createEmptySelectors();

            $result = new RulesetNode($selectors, $context->mediaBlocks);
            $result->multiMedia = true;
        }
        $context->mediaBlocks = [];
        $context->mediaPath = [];

        return $result;
    }

    /**
     * Compiles nested media.
     *
     * @param Context $context
     *
     * @return RulesetNode
     */
    public function compileNested(Context $context)
    {
        $path = array_merge($context->mediaPath, [$this]);

        // Extract the media-query conditions separated with `,` (OR).
        foreach ($path as $key => $p) {
            $value = $p->features instanceof ValueNode ? $p->features->value : $p->features;
            $path[$key] = is_array($value) ? $value : [$value];
        }

        // Trace all permutations to generate the resulting media-query.
        //
        // (a, b and c) with nested (d, e) ->
        //a and d
        //a and e
        //b and c and d
        //b and c and e

        $permuted = $this->permute($path);
        $expressions = [];
        foreach ($permuted as $path) {
            for ($i = 0, $len = count($path); $i < $len; ++$i) {
                $path[$i] = $path[$i] instanceof GenerateCSSInterface ? $path[$i] : new AnonymousNode($path[$i]);
            }
            for ($i = count($path) - 1; $i > 0; --$i) {
                array_splice($path, $i, 0, [new AnonymousNode('and')]);
            }
            $expressions[] = new ExpressionNode($path);
        }

        $this->features = new ValueNode($expressions);

        // Fake a tree-node that doesn't output anything.
        return new RulesetNode([], []);
    }

    /**
     * Creates permutations.
     *
     * @param array $array The array
     *
     * @return array
     */
    public function permute(array $array)
    {
        if (!count($array)) {
            return [];
        } elseif (count($array) === 1) {
            return $array[0];
        } else {
            $result = [];
            $rest = $this->permute(array_slice($array, 1));
            foreach ($rest as $r) {
                foreach ($array[0] as $a) {
                    $result[] = array_merge(
                        is_array($a) ? $a : [$a], is_array($r) ? $r : [$r]
                    );
                }
            }
        }

        return $result;
    }

    /**
     * Bubbles the selectors.
     *
     * @param array $selectors
     */
    public function bubbleSelectors(array $selectors)
    {
        if (!$selectors) {
            return;
        }

        $this->rules = [
            new RulesetNode($selectors, [$this->rules[0]]),
        ];
    }

    /**
     * @return bool
     */
    public function isRulesetLike()
    {
        return true;
    }
}

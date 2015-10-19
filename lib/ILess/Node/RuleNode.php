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
use ILess\Node;
use ILess\Output\OutputInterface;
use ILess\Output\StandardOutput;

/**
 * Rule.
 */
class RuleNode extends Node implements MakeableImportantInterface, MarkableAsReferencedInterface
{
    /**
     * Node type.
     *
     * @var string
     */
    protected $type = 'Rule';

    /**
     * The name.
     *
     * @var string
     */
    public $name;

    /**
     * Important keyword ( "!important").
     *
     * @var string
     */
    public $important;

    /**
     * Current index.
     *
     * @var int
     */
    public $index = 0;

    /**
     * Inline flag.
     *
     * @var bool
     */
    public $inline = false;

    /**
     * Is variable?
     *
     * @var bool
     */
    public $variable = false;

    /**
     * Merge flag.
     *
     * @var bool
     */
    public $merge = false;

    /**
     * Constructor.
     *
     * @param string|array $name The rule name
     * @param string|ValueNode $value The value
     * @param string $important Important keyword
     * @param string|null $merge Merge?
     * @param int $index Current index
     * @param FileInfo $currentFileInfo The current file info
     * @param bool $inline Inline flag
     * @param bool|null $variable
     */
    public function __construct(
        $name,
        $value,
        $important = null,
        $merge = null,
        $index = 0,
        FileInfo $currentFileInfo = null,
        $inline = false,
        $variable = null
    ) {
        parent::__construct(($value instanceof Node) ? $value : new ValueNode([$value]));

        $this->name = $name;
        $this->important = $important ? ' ' . trim($important) : '';
        $this->merge = $merge;
        $this->index = $index;
        $this->currentFileInfo = $currentFileInfo;
        $this->inline = (boolean) $inline;
        $this->variable = null !== $variable ? $variable : (is_string($name) && $name[0] === '@');
    }

    /**
     * Compiles the node.
     *
     * @param Context $context The context
     * @param array|null $arguments Array of arguments
     * @param bool|null $important Important flag
     *
     * @return RuleNode
     *
     * @throws
     */
    public function compile(Context $context, $arguments = null, $important = null)
    {
        $strictMathBypass = false;
        $return = null;
        $name = $this->name;
        $variable = $this->variable;

        if (!is_string($name)) {
            // expand 'primitive' name directly to get
            // things faster (~10% for benchmark.less):
            $name = (count($name) === 1 && $name[0] instanceof KeywordNode) ? $name[0]->value : $this->evalName($context,
                $name);
            $variable = false; // never treat expanded interpolation as new variable name
        }

        if ($name === 'font' && !$context->strictMath) {
            $strictMathBypass = true;
            $context->strictMath = true;
        }

        $e = null;
        try {
            array_push($context->importantScope, []);
            $compiledValue = $this->value->compile($context);

            if (!$this->variable && $compiledValue instanceof DetachedRulesetNode) {
                throw new Exception('Rulesets cannot be evaluated on a property.', $this->index,
                    $this->currentFileInfo);
            }

            $important = $this->important;
            $importantResult = array_pop($context->importantScope);

            if (!$important && isset($importantResult['important'])) {
                $important = $importantResult['important'];
            }

            $return = new self($name,
                $compiledValue,
                $important, $this->merge,
                $this->index, $this->currentFileInfo,
                $this->inline,
                $variable
            );
        } catch (Exception $e) {
            if ($e instanceof Exception) {
                if ($e->getCurrentFile() === null && $e->getIndex() === null) {
                    $e->setCurrentFile($this->currentFileInfo, $this->index);
                }
            }
        }

        if ($strictMathBypass) {
            $context->strictMath = false;
        }

        if (isset($e)) {
            throw $e;
        }

        return $return;
    }

    /**
     * @param Context $context
     * @param array $name
     *
     * @return string
     */
    private function evalName(Context $context, $name)
    {
        $output = new StandardOutput();
        for ($i = 0, $n = count($name); $i < $n; ++$i) {
            $name[$i]->compile($context)->generateCss($context, $output);
        }

        return $output->toString();
    }

    /**
     * {@inheritdoc}
     */
    public function generateCSS(Context $context, OutputInterface $output)
    {
        $output->add($this->name . ($context->compress ? ':' : ': '), $this->currentFileInfo, $this->index);
        try {
            $this->value->generateCSS($context, $output);
        } catch (Exception $e) {
            if ($this->currentFileInfo) {
                $e->setCurrentFile($this->currentFileInfo, $this->index);
            }
            // rethrow
            throw $e;
        }
        $output->add($this->important . (($this->inline || ($context->lastRule && $context->compress)) ? '' : ';'),
            $this->currentFileInfo, $this->index);
    }

    /**
     * Makes the node important.
     *
     * @return RuleNode
     */
    public function makeImportant()
    {
        return new self(
            $this->name,
            $this->value,
            '!important',
            $this->merge,
            $this->index,
            $this->currentFileInfo,
            $this->inline
        );
    }

    /**
     * Marks as referenced.
     */
    public function markReferenced()
    {
        if ($this->value) {
            $this->markReferencedRecursive($this->value);
        }
    }

    private function markReferencedRecursive(&$value)
    {
        if (!is_array($value)) {
            if ($value instanceof MarkableAsReferencedInterface) {
                $value->markReferenced();
            }
        } else {
            foreach ($value as &$v) {
                $this->markReferencedRecursive($v);
            }
        }
    }
}

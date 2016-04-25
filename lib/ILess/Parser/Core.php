<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Parser;

use ILess\Color;
use ILess\Context;
use ILess\DebugInfo;
use ILess\Exception\CompilerException;
use ILess\Exception\ParserException;
use ILess\ImportedFile;
use ILess\Importer;
use ILess\Node;
use ILess\Node\AlphaNode;
use ILess\Node\AnonymousNode;
use ILess\Node\AssignmentNode;
use ILess\Node\AttributeNode;
use ILess\Node\CallNode;
use ILess\Node\ColorNode;
use ILess\Node\CombinatorNode;
use ILess\Node\CommentNode;
use ILess\Node\ConditionNode;
use ILess\Node\DetachedRulesetNode;
use ILess\Node\DimensionNode;
use ILess\Node\DirectiveNode;
use ILess\Node\ElementNode;
use ILess\Node\ExpressionNode;
use ILess\Node\ExtendNode;
use ILess\Node\ImportNode;
use ILess\Node\JavascriptNode;
use ILess\Node\KeywordNode;
use ILess\Node\MediaNode;
use ILess\Node\MixinCallNode;
use ILess\Node\MixinDefinitionNode;
use ILess\Node\NegativeNode;
use ILess\Node\OperationNode;
use ILess\Node\ParenNode;
use ILess\Node\QuotedNode;
use ILess\Node\RuleNode;
use ILess\Node\RulesetCallNode;
use ILess\Node\RulesetNode;
use ILess\Node\SelectorNode;
use ILess\Node\UnicodeDescriptorNode;
use ILess\Node\UrlNode;
use ILess\Node\ValueNode;
use ILess\Node\VariableNode;
use ILess\Plugin\PostProcessorInterface;
use ILess\Plugin\PreProcessorInterface;
use ILess\PluginManager;
use ILess\SourceMap\Generator;
use ILess\Util;
use ILess\Variable;
use ILess\Visitor\ImportVisitor;
use ILess\Visitor\JoinSelectorVisitor;
use ILess\Visitor\ProcessExtendsVisitor;
use ILess\Visitor\ToCSSVisitor;
use ILess\Visitor\Visitor;
use InvalidArgumentException;

/**
 * Parser core.
 */
class Core
{
    /**
     * Parser version.
     */
    const VERSION = '2.2.0';

    /**
     * Less.js compatibility version.
     */
    const LESS_JS_VERSION = '2.5.x';

    /**
     * The context.
     *
     * @var Context
     */
    protected $context;

    /**
     * The importer.
     *
     * @var Importer
     */
    protected $importer;

    /**
     * Array of variables.
     *
     * @var array
     */
    protected $variables = [];

    /**
     * Array of parsed rules.
     *
     * @var array
     */
    protected $rules = [];

    /**
     * @var ParserInput
     */
    protected $input;

    /**
     * @var PluginManager|null
     */
    protected $pluginManager;

    /**
     * Constructor.
     *
     * @param Context $context The context
     * @param Importer $importer The importer
     * @param PluginManager $pluginManager The plugin manager
     */
    public function __construct(Context $context, Importer $importer, PluginManager $pluginManager = null)
    {
        $this->context = $context;
        $this->importer = $importer;
        $this->pluginManager = $pluginManager;
        $this->input = new ParserInput();
    }

    /**
     * Parse a Less string from a given file.
     *
     * @throws ParserException
     *
     * @param string|ImportedFile $file The file to parse (Will be loaded via the importer)
     * @param bool $returnRuleset Indicates whether the parsed rules should be wrapped in a ruleset.
     *
     * @return mixed If $returnRuleset is true, ILess\Parser\Core, ILess\ILess\Node\RulesetNode otherwise
     */
    public function parseFile($file, $returnRuleset = false)
    {
        // save the previous information
        $previousFileInfo = $this->context->currentFileInfo;

        if (!($file instanceof ImportedFile)) {
            $this->context->setCurrentFile($file);

            if ($previousFileInfo) {
                $this->context->currentFileInfo->reference = $previousFileInfo->reference;
            }

            // try to load it via importer
            list(, $file) = $this->importer->import($file, true, $this->context->currentFileInfo);

            /* @var $file ImportedFile */
            $this->context->setCurrentFile($file->getPath());
            $this->context->currentFileInfo->importedFile = $file;

            $ruleset = $file->getRuleset();
        } else {
            $this->context->setCurrentFile($file->getPath());

            if ($previousFileInfo) {
                $this->context->currentFileInfo->reference = $previousFileInfo->reference;
            }

            $this->context->currentFileInfo->importedFile = $file;

            $ruleset = $file->getRuleset();
            if (!$ruleset) {
                $file->setRuleset(
                    ($ruleset = new RulesetNode([], $this->parse($file->getContent())))
                );
            }
        }

        if ($previousFileInfo) {
            $this->context->currentFileInfo = $previousFileInfo;
        }

        if ($returnRuleset) {
            return $ruleset;
        }

        $this->rules = array_merge($this->rules, $ruleset->rules);

        return $this;
    }

    /**
     * Parses a string.
     *
     * @param string $string The string to parse
     * @param string $filename The filename for reference (will be visible in the source map) or path to a fake file which directory will be used for imports
     * @param bool $returnRuleset Return the ruleset?
     *
     * @return $this
     */
    public function parseString($string, $filename = '__string_to_parse__', $returnRuleset = false)
    {
        $string = Util::normalizeString((string) $string);

        // we need unique key
        $key = sprintf('%s[__%s__]', $filename, md5($string));

        // create a dummy information, since we are not parsing a real file,
        // but a string coming from outside
        $this->context->setCurrentFile($filename);

        $importedFile = new ImportedFile($key, $string, time());

        // save information, so the exceptions can handle errors in the string
        // and source map is generated for the string
        $this->context->currentFileInfo->importedFile = $importedFile;
        $this->importer->setImportedFile($key, $importedFile, $key, $this->context->currentFileInfo);

        if ($this->context->sourceMap) {
            $this->context->setFileContent($key, $string);
        }

        $importedFile->setRuleset(
            ($ruleset = new RulesetNode([], $this->parse($string)))
        );

        if ($returnRuleset) {
            return $ruleset;
        }

        $this->rules = array_merge($this->rules, $ruleset->rules);

        return $this;
    }

    /**
     * Adds variables.
     *
     * @param array $variables Array of variables
     *
     * @return $this
     */
    public function addVariables(array $variables)
    {
        $this->variables = array_merge($this->variables, $variables);

        return $this;
    }

    /**
     * Clears all assigned variables.
     *
     * @return $this
     */
    public function clearVariables()
    {
        $this->variables = [];

        return $this;
    }

    /**
     * Sets variables.
     *
     * @param array $variables
     *
     * @return $this
     */
    public function setVariables(array $variables)
    {
        $this->variables = $variables;

        return $this;
    }

    /**
     * Unsets a previously set variable.
     *
     * @param string|array $variable The variable name(s) to unset as string or an array
     *
     * @see setVariables, addVariables
     *
     * @return $this
     */
    public function unsetVariable($variable)
    {
        if (!is_array($variable)) {
            $variable = [$variable];
        }

        foreach ($variable as $name) {
            if (isset($this->variables[$name])) {
                unset($this->variables[$name]);
            } elseif (isset($this->variables['!' . $name])) {
                unset($this->variables['!' . $name]);
            }
        }

        return $this;
    }

    /**
     * Parse a Less string into nodes.
     *
     * @param string $string The string to parse
     *
     * @return array
     *
     * @throws ParserException If there was an error in parsing the string
     */
    protected function parse($string)
    {
        $string = Util::normalizeString($string);

        if ($this->pluginManager) {
            $preProcessors = $this->pluginManager->getPreProcessors();
            foreach ($preProcessors as $preProcessor) {
                /* @var $preProcessor PreProcessorInterface */
                $string = $preProcessor->process($string, [
                    'context' => $this->context,
                    'file_info' => $this->context->currentFileInfo,
                    'importer' => $this->importer,
                ]);
            }
        }

        $this->input = new ParserInput();
        $this->input->start($string);
        $rules = $this->parsePrimary();

        $endInfo = $this->input->end();
        $error = null;

        if (!$endInfo->isFinished) {
            $message = $endInfo->furthestPossibleErrorMessage;
            if (!$message) {
                $message = 'Unrecognised input';
                if ($endInfo->furthestChar === '}') {
                    $message .= '. Possibly missing opening \'{\'';
                } elseif ($endInfo->furthestChar === ')') {
                    $message .= '. Possibly missing opening \'(\'';
                } elseif ($endInfo->furthestReachedEnd) {
                    $message .= '. Possibly missing something';
                }
            }
            $error = new ParserException($message, $endInfo->furthest, $this->context->currentFileInfo);
        }

        if ($error) {
            throw $error;
        }

        return $rules;
    }

    /**
     * Resets the parser.
     *
     * @param bool $variables Reset also assigned variables via the API?
     *
     * @return $this
     */
    public function reset($variables = true)
    {
        $this->rules = [];

        if ($variables) {
            $this->clearVariables();
        }

        return $this;
    }

    /**
     * Returns the plugin manager.
     *
     * @return PluginManager|null
     */
    public function getPluginManager()
    {
        return $this->pluginManager;
    }

    /**
     * Generates unique cache key for given $filename.
     *
     * @param string $filename
     *
     * @return string
     */
    protected function generateCacheKey($filename)
    {
        return Util::generateCacheKey($filename);
    }

    /**
     * Returns the CSS.
     *
     * @return string
     */
    public function getCSS()
    {
        if (!count($this->rules)) {
            return '';
        }

        return $this->toCSS($this->getRootRuleset(), $this->variables);
    }

    /**
     * Returns root ruleset.
     *
     * @return RulesetNode
     */
    protected function getRootRuleset()
    {
        $root = new RulesetNode([], $this->rules);
        $root->root = true;
        $root->firstRoot = true;
        $root->allowImports = true;

        return $root;
    }

    /**
     * Converts the ruleset to CSS.
     *
     * @param RulesetNode $ruleset
     * @param array $variables
     *
     * @return string The generated CSS code
     *
     * @throws
     */
    protected function toCSS(RulesetNode $ruleset, array $variables)
    {
        $precision = ini_set('precision', 16);
        $locale = setlocale(LC_NUMERIC, 0);
        setlocale(LC_NUMERIC, 'C');

        if (extension_loaded('xdebug')) {
            $level = ini_set('xdebug.max_nesting_level', PHP_INT_MAX);
        }

        $e = $css = null;
        try {
            $this->prepareVariables($this->context, $variables);

            // pre compilation visitors
            foreach ($this->getPreCompileVisitors() as $visitor) {
                /* @var $visitor Visitor */
                $visitor->run($ruleset);
            }

            // compile the ruleset
            $compiled = $ruleset->compile($this->context);

            // post compilation visitors
            foreach ($this->getPostCompileVisitors() as $visitor) {
                /* @var $visitor Visitor */
                $visitor->run($compiled);
            }

            $context = $this->getContext();
            $context->numPrecision = 8; // less.js compatibility

            if ($context->sourceMap) {
                $generator = new Generator(
                    $compiled,
                    $this->context->getContentsMap(), $this->context->sourceMapOptions
                );
                // will also save file
                $css = $generator->generateCSS($this->context);
            } else {
                $generator = null;
                $css = $compiled->toCSS($this->context);
            }

            if ($this->pluginManager) {
                // post process
                $postProcessors = $this->pluginManager->getPostProcessors();
                foreach ($postProcessors as $postProcessor) {
                    /* @var $postProcessor PostProcessorInterface */
                    $css = $postProcessor->process($css, [
                        'context' => $this->context,
                        'source_map' => $generator,
                        'importer' => $this->importer,
                    ]);
                }
            }

            if ($this->context->compress) {
                $css = preg_replace('/(^(\s)+)|((\s)+$)/', '', $css);
            }
        } catch (\Exception $e) {
        }

        // restore
        setlocale(LC_NUMERIC, $locale);
        ini_set('precision', $precision);

        if (extension_loaded('xdebug')) {
            ini_set('xdebug.max_nesting_level', $level);
        }

        if ($e) {
            throw $e;
        }

        return $css;
    }

    /**
     * Prepare variable to be used as nodes.
     *
     * @param Context $context
     * @param array $variables
     */
    protected function prepareVariables(Context $context, array $variables)
    {
        // FIXME: flag to mark variables as prepared!
        $prepared = [];
        foreach ($variables as $name => $value) {
            // user provided node, no need to process it further
            if ($value instanceof Node) {
                $prepared[] = $value;
                continue;
            }
            // this is not an "real" variable
            if (!$value instanceof Variable) {
                $value = Variable::create($name, $value);
            }
            $prepared[] = $value->toNode();
        }

        if (count($prepared)) {
            $context->customVariables = new RulesetNode([], $prepared);
        }
    }

    /**
     * Returns array of pre compilation visitors.
     *
     * @return array
     */
    protected function getPreCompileVisitors()
    {
        $preCompileVisitors = [];

        if ($this->context->processImports) {
            $preCompileVisitors[] = new ImportVisitor($this->getContext(), $this->getImporter());
        }

        if ($this->pluginManager) {
            $preCompileVisitors = array_merge(
                $preCompileVisitors,
                $this->pluginManager->getPreCompileVisitors()
            );
        }

        return $preCompileVisitors;
    }

    /**
     * Returns an array of post compilation visitors.
     *
     * @return array
     */
    protected function getPostCompileVisitors()
    {
        // core visitors
        $postCompileVisitors = [
            new JoinSelectorVisitor(),
            new ProcessExtendsVisitor(),
        ];

        if ($this->pluginManager) {
            $postCompileVisitors = array_merge(
                $this->pluginManager->getPostCompileVisitors(),
                $postCompileVisitors
            );
        }

        $postCompileVisitors[] = new ToCSSVisitor($this->getContext());

        return $postCompileVisitors;
    }

    /**
     * @return array
     */
    protected function parsePrimary()
    {
        $root = [];
        while (true) {
            while (true) {
                $node = $this->parseComment();
                if (!$node) {
                    break;
                }
                $root[] = $node;
            }

            if ($this->input->finished) {
                break;
            }

            if ($this->input->peek('}')) {
                break;
            }

            $node = $this->parseExtendRule();
            if ($node) {
                $root = array_merge($root, $node);
                continue;
            }

            $node = $this->matchFuncs(
                [
                    'parseMixinDefinition',
                    'parseRule',
                    'parseRuleset',
                    'parseMixinCall',
                    'parseRulesetCall',
                    'parseDirective',
                ]
            );

            if ($node) {
                $root[] = $node;
            } else {
                $foundSemiColon = false;
                while ($this->input->char(';')) {
                    $foundSemiColon = true;
                }
                if (!$foundSemiColon) {
                    break;
                }
            }
        }

        return $root;
    }

    /**
     * comments are collected by the main parsing mechanism and then assigned to nodes
     * where the current structure allows it.
     *
     * @return CommentNode|null
     */
    protected function parseComment()
    {
        if (count($this->input->commentStore)) {
            $comment = array_shift($this->input->commentStore);

            return new CommentNode(
                $comment['text'],
                isset($comment['isLineComment']) ? $comment['isLineComment'] : false,
                $comment['index'],
                $this->context->currentFileInfo
            );
        }
    }

    // The variable part of a variable definition. Used in the `rule` parser
    //
    // @fink();
    //
    protected function parseRulesetCall()
    {
        if ($this->input->currentChar() === '@' && ($name = $this->input->re('/\\G(@[\w-]+)\s*\(\s*\)\s*;/'))) {
            return new RulesetCallNode($name[1]);
        }
    }

    /**
     * Parses a mixin definition.
     *
     * @return MixinDefinitionNode|null
     */
    protected function parseMixinDefinition()
    {
        if (($this->input->currentChar() !== '.' && $this->input->currentChar() !== '#') ||
            $this->input->peekReg('/\\G^[^{]*\}/')
        ) {
            return;
        }

        $this->input->save();

        if ($match = $this->input->re('/\\G([#.](?:[\w-]|\\\(?:[A-Fa-f0-9]{1,6} ?|[^A-Fa-f0-9]))+)\s*\(/')) {
            $cond = null;
            $name = $match[1];
            $argInfo = $this->parseMixinArgs(false);
            $params = $argInfo['args'];
            $variadic = $argInfo['variadic'];

            // .mixincall("@{a}");
            // looks a bit like a mixin definition..
            // also
            // .mixincall(@a: {rule: set;});
            // so we have to be nice and restore
            if (!$this->input->char(')')) {
                $this->input->restore("Missing closing ')'");

                return;
            }

            $this->input->commentStore = [];

            // Guard
            if ($this->input->str('when')) {
                $cond = $this->expect('parseConditions', 'Expected conditions');
            }

            $ruleset = $this->parseBlock();
            if (is_array($ruleset)) {
                $this->input->forget();

                return new MixinDefinitionNode($name, $params, $ruleset, $cond, $variadic);
            } else {
                $this->input->restore();
            }
        } else {
            $this->input->forget();
        }
    }

    /**
     * Parses a mixin call with an optional argument list.
     *
     *   #mixins > .square(#fff);
     *    .rounded(4px, black);
     *   .button;
     *
     * The `while` loop is there because mixins can be
     * namespaced, but we only support the child and descendant
     * selector for now.
     *
     * @return MixinCallNode|null
     */
    protected function parseMixinCall()
    {
        $s = $this->input->currentChar();
        $important = false;
        $index = $this->input->i;
        $c = null;
        $args = [];

        if ($s !== '.' && $s !== '#') {
            return;
        }

        $this->input->save(); // stop us absorbing part of an invalid selector

        $elements = [];
        while (true) {
            $elemIndex = $this->input->i;
            $e = $this->input->re('/\\G[#.](?:[\w-]|\\\\(?:[A-Fa-f0-9]{1,6} ?|[^A-Fa-f0-9]))+/');
            if (!$e) {
                break;
            }
            $elements[] = new ElementNode($c, $e, $elemIndex, $this->context->currentFileInfo);
            $c = $this->input->char('>');
        }

        if ($elements) {
            if ($this->input->char('(')) {
                $args = $this->parseMixinArgs(true);
                $args = $args['args'];
                $this->expect(')');
            }

            if ($this->parseImportant()) {
                $important = true;
            }

            if ($this->parseEnd()) {
                $this->input->forget();

                return new MixinCallNode($elements, $args, $index, $this->context->currentFileInfo, $important);
            }
        }

        $this->input->restore();
    }

    /**
     * Parses mixin arguments.
     *
     * @param bool $isCall The definition or function call?
     *
     * @return array
     *
     * @throws CompilerException If there is an error the definition of arguments
     */
    protected function parseMixinArgs($isCall)
    {
        $expressions = [];
        $argsSemiColon = [];
        $isSemiColonSeparated = null;
        $argsComma = [];
        $expressionContainsNamed = null;
        $name = null;
        $expand = null;
        $returner = ['args' => null, 'variadic' => false];

        $this->input->save();

        while (true) {
            if ($isCall) {
                $arg = $this->matchFuncs(['parseDetachedRuleset', 'parseExpression']);
            } else {
                $this->input->commentStore = [];
                if ($this->input->str('...')) {
                    $returner['variadic'] = true;
                    if ($this->input->char(';') && !$isSemiColonSeparated) {
                        $isSemiColonSeparated = true;
                    }

                    if ($isSemiColonSeparated) {
                        $argsSemiColon[] = ['variadic' => true];
                    } else {
                        $argsComma[] = ['variadic' => true];
                    }
                    break;
                }
                $arg = $this->matchFuncs(
                    ['parseEntitiesVariable', 'parseEntitiesLiteral', 'parseEntitiesKeyword']
                );
            }

            if (!$arg) {
                break;
            }

            $nameLoop = null;
            if ($arg instanceof ExpressionNode) {
                $arg->throwAwayComments();
            }

            $value = $arg;
            $val = null;

            if ($isCall) {
                // ILess\Variable
                if (count($arg->value) == 1) {
                    $val = $arg->value[0];
                }
            } else {
                $val = $arg;
            }

            if ($val instanceof VariableNode) {
                if ($this->input->char(':')) {
                    if (count($expressions) > 0) {
                        if ($isSemiColonSeparated) {
                            throw new CompilerException(
                                'Cannot mix ; and , as delimiter types',
                                $this->input->i,
                                $this->context->currentFileInfo
                            );
                        }
                        $expressionContainsNamed = true;
                    }

                    $value = $this->matchFuncs(['parseDetachedRuleset', 'parseExpression']);
                    if (!$value) {
                        if ($isCall) {
                            throw new CompilerException(
                                'Could not understand value for named argument',
                                $this->input->i,
                                $this->context->currentFileInfo
                            );
                        } else {
                            $this->input->restore();
                            $returner['args'] = [];

                            return $returner;
                        }
                    }

                    $nameLoop = ($name = $val->name);
                } elseif ($this->input->str('...')) {
                    if (!$isCall) {
                        $returner['variadic'] = true;

                        if ($this->input->char(';') && !$isSemiColonSeparated) {
                            $isSemiColonSeparated = true;
                        }

                        if ($isSemiColonSeparated) {
                            $argsSemiColon[] = ['name' => $arg->name, 'variadic' => true];
                        } else {
                            $argsComma[] = ['name' => $arg->name, 'variadic' => true];
                        }
                        break;
                    } else {
                        $expand = true;
                    }
                } elseif (!$isCall) {
                    $name = $nameLoop = $val->name;
                    $value = null;
                }
            }

            if ($value) {
                $expressions[] = $value;
            }

            $argsComma[] = ['name' => $nameLoop, 'value' => $value, 'expand' => $expand];

            if ($this->input->char(',')) {
                continue;
            }

            if ($this->input->char(';') || $isSemiColonSeparated) {
                if ($expressionContainsNamed) {
                    throw new CompilerException(
                        'Cannot mix ; and , as delimiter types',
                        $this->input->i,
                        $this->context->currentFileInfo
                    );
                }

                $isSemiColonSeparated = true;
                if (count($expressions) > 1) {
                    $value = new ValueNode($expressions);
                }
                $argsSemiColon[] = ['name' => $name, 'value' => $value, 'expand' => $expand];
                $name = null;
                $expressions = [];
                $expressionContainsNamed = false;
            }
        }

        $this->input->forget();
        $returner['args'] = ($isSemiColonSeparated ? $argsSemiColon : $argsComma);

        return $returner;
    }

    /**
     * Parses a rule.
     *
     * @param bool $tryAnonymous
     *
     * @return RuleNode|null
     */
    protected function parseRule($tryAnonymous = false)
    {
        $merge = null;
        $startOfRule = $this->input->i;
        $value = null;
        $merge = null;
        $important = null;
        $c = $this->input->currentChar();

        if ($c === '.' || $c === '#' || $c === '&' || $c === ':') {
            return;
        }

        $this->input->save();

        if ($name = $this->matchFuncs(['parseVariable', 'parseRuleProperty'])) {
            $isVariable = is_string($name);
            if ($isVariable) {
                $value = $this->parseDetachedRuleset();
            }

            $this->input->commentStore = [];

            if (!$value) {
                if (is_array($name) && count($name) > 1) {
                    $tmp = array_pop($name);
                    $merge = !$isVariable && $tmp->value ? $tmp->value : false;
                }

                $tryValueFirst = !$tryAnonymous && ($this->context->compress || $isVariable);

                if ($tryValueFirst) {
                    $value = $this->parseValue();
                }

                if (!$value) {
                    $value = $this->parseAnonymousValue();
                    if ($value) {
                        $this->input->forget();

                        return new RuleNode(
                            $name,
                            $value,
                            false,
                            $merge,
                            $startOfRule,
                            $this->context->currentFileInfo
                        );
                    }
                }

                if (!$tryValueFirst && !$value) {
                    $value = $this->parseValue();
                }

                $important = $this->parseImportant();
            }

            if ($value && $this->parseEnd()) {
                $this->input->forget();

                return new RuleNode(
                    $name, $value, $important, $merge, $startOfRule, $this->context->currentFileInfo
                );
            } else {
                $this->input->restore();
                if ($value && !$tryAnonymous) {
                    return $this->parseRule(true);
                }
            }
        }
    }

    /**
     * Parses an anonymous value.
     *
     * @return AnonymousNode|null
     */
    protected function parseAnonymousValue()
    {
        if ($match = $this->input->re('/\\G([^@+\/\'"*`(;{}-]*);/')) {
            return new AnonymousNode($match[1]);
        }
    }

    /**
     * Parses a ruleset like: `div, .class, body > p {...}`.
     *
     * @return RulesetNode|null
     *
     * @throws ParserException
     */
    protected function parseRuleset()
    {
        $selectors = [];

        $this->input->save();

        $debugInfo = null;
        if ($this->context->dumpLineNumbers) {
            $debugInfo = $this->getDebugInfo($this->input->i);
        }

        while (true) {
            $s = $this->parseLessSelector();
            if (!$s) {
                break;
            }
            $selectors[] = $s;
            $this->input->commentStore = [];
            if ($s->condition && count($selectors) > 1) {
                throw new ParserException(
                    'Guards are only currently allowed on a single selector.',
                    $this->input->i,
                    $this->context->currentFileInfo
                );
            }

            if (!$this->input->char(',')) {
                break;
            }

            if ($s->condition) {
                throw new ParserException(
                    'Guards are only currently allowed on a single selector.',
                    $this->input->i,
                    $this->context->currentFileInfo
                );
            }

            $this->input->commentStore = [];
        }

        if ($selectors && is_array($rules = $this->parseBlock())) {
            $this->input->forget();
            $ruleset = new RulesetNode($selectors, $rules, $this->context->strictImports);
            if ($debugInfo) {
                $ruleset->debugInfo = $debugInfo;
            }

            return $ruleset;
        } else {
            $this->input->restore();
        }
    }

    /**
     * Parses a selector with less extensions e.g. the ability to extend and guard.
     *
     * @return SelectorNode|null
     */
    protected function parseLessSelector()
    {
        return $this->parseSelector(true);
    }

    /**
     * Parses a CSS selector.
     *
     * @param bool $isLess Is this a less sector? (ie. has ability to extend and guard)
     *
     * @return SelectorNode|null
     *
     * @throws ParserException
     */
    protected function parseSelector($isLess = false)
    {
        $elements = [];
        $extendList = [];
        $allExtends = [];
        $condition = null;
        $when = false;
        $e = null;
        $c = null;
        $index = $this->input->i;

        while (($isLess && ($extendList = $this->parseExtend()))
            || ($isLess && ($when = $this->input->str('when'))) || ($e = $this->parseElement())) {
            if ($when) {
                $condition = $this->expect('parseConditions', 'Expected condition');
            } elseif ($condition) {
                throw new ParserException(
                    'CSS guard can only be used at the end of selector.',
                    $index,
                    $this->context->currentFileInfo
                );
            } elseif ($extendList) {
                $allExtends = array_merge($allExtends, $extendList);
            } else {
                if ($allExtends) {
                    throw new ParserException(
                        'Extend can only be used at the end of selector.',
                        $this->input->i,
                        $this->context->currentFileInfo
                    );
                }
                $c = $this->input->currentChar();
                $elements[] = $e;
                $e = null;
            }

            if ($c === '{' || $c === '}' || $c === ';' || $c === ',' || $c === ')') {
                break;
            }
        }

        if ($elements) {
            return new SelectorNode($elements, $allExtends, $condition, $index, $this->context->currentFileInfo);
        }

        if ($allExtends) {
            throw new ParserException(
                'Extend must be used to extend a selector, it cannot be used on its own',
                $this->input->i,
                $this->context->currentFileInfo
            );
        }
    }

    /**
     * Parses extend.
     *
     * @param bool $isRule Is is a rule?
     *
     * @return ExtendNode|null
     *
     * @throws CompilerException
     */
    protected function parseExtend($isRule = false)
    {
        $extendList = [];
        $index = $this->input->i;

        if (!$this->input->str($isRule ? '&:extend(' : ':extend(')) {
            return;
        }

        do {
            $option = null;
            $elements = [];
            while (!($option = $this->input->re('/\\G(all)(?=\s*(\)|,))/'))) {
                $e = $this->parseElement();
                if (!$e) {
                    break;
                }
                $elements[] = $e;
            }

            if ($option) {
                $option = $option[1];
            }

            if (!$elements) {
                throw new CompilerException(
                    'Missing target selector for :extend()',
                    $index,
                    $this->context->currentFileInfo
                );
            }

            $extendList[] = new ExtendNode(new SelectorNode($elements), $option, $index);
        } while ($this->input->char(','));

        $this->expect('/\\G\)/');

        if ($isRule) {
            $this->expect('/\\G;/');
        }

        return $extendList;
    }

    /**
     * Parses extend rule.
     *
     * @return ExtendNode|null
     */
    protected function parseExtendRule()
    {
        return $this->parseExtend(true);
    }

    /**
     * Parses a selector element.
     *
     *  * `div`
     *  * `+ h1`
     *  * `#socks`
     *  * `input[type="text"]`
     *
     * Elements are the building blocks for selectors,
     * they are made out of a `combinator` and an element name, such as a tag a class, or `*`.
     *
     * @return ElementNode|null
     */
    protected function parseElement()
    {
        $index = $this->input->i;

        $c = $this->parseCombinator();

        $e = $this->match(
            [
                '/\\G^(?:\d+\.\d+|\d+)%/',
                // http://stackoverflow.com/questions/3665962/regular-expression-error-no-ending-delimiter
                '/\\G^(?:[.#]?|:*)(?:[\w-]|[^\\x{00}-\\x{9f}]|\\\\(?:[A-Fa-f0-9]{1,6} ?|[^A-Fa-f0-9]))+/',
                '*',
                '&',
                'parseAttribute',
                '/\\G^\([^&()@]+\)/',
                '/\\G^[\.#:](?=@)/',
                'parseEntitiesVariableCurly',
            ]
        );

        if (!$e) {
            $this->input->save();
            if ($this->input->char('(')) {
                if (($v = $this->parseSelector()) && $this->input->char(')')) {
                    $e = new ParenNode($v);
                    $this->input->forget();
                } else {
                    $this->input->restore("Missing closing ')'");
                }
            } else {
                $this->input->forget();
            }
        }

        if ($e) {
            return new ElementNode($c, $e, $index, $this->context->currentFileInfo);
        }
    }

    /**
     * Parses a combinator. Combinators combine elements together, in a selector.
     *
     * Because our parser isn't white-space sensitive, special care
     * has to be taken, when parsing the descendant combinator, ` `,
     * as it's an empty space. We have to check the previous character
     * in the input, to see if it's a ` ` character.
     *
     * @return CombinatorNode|null
     */
    protected function parseCombinator()
    {
        $c = $this->input->currentChar();

        if ($c === '/') {
            $this->input->save();
            $slashedCombinator = $this->input->re('/\\G^\/[a-z]+\//i');
            if ($slashedCombinator) {
                $this->input->forget();

                return new CombinatorNode($slashedCombinator);
            }
            $this->input->restore();
        }

        if ($c === '>' || $c === '+' || $c === '~' || $c === '|' || $c === '^') {
            ++$this->input->i;
            if ($c === '^' && $this->input->currentChar() === '^') {
                $c = '^^';
                ++$this->input->i;
            }
            while ($this->input->isWhitespace()) {
                ++$this->input->i;
            }

            return new CombinatorNode($c);
        } elseif ($this->input->isWhiteSpace(-1)) {
            return new CombinatorNode(' ');
        } else {
            return new CombinatorNode();
        }
    }

    /**
     * Parses an attribute.
     *
     * @return AttributeNode|null
     */
    protected function parseAttribute()
    {
        if (!$this->input->char('[')) {
            return;
        }

        $key = $this->parseEntitiesVariableCurly();
        if (!$key) {
            $key = $this->expect('/\\G(?:[_A-Za-z0-9-\*]*\|)?(?:[_A-Za-z0-9-]|\\\\.)+/');
        }

        $val = null;
        if (($op = $this->input->re('/\\G[|~*$^]?=/'))) {
            $val = $this->match(
                [
                    'parseEntitiesQuoted',
                    '/\\G[0-9]+%/',
                    '/\\G[\w-]+/',
                    'parseEntitiesVariableCurly',
                ]
            );
        }

        $this->expect(']');

        return new AttributeNode($key, $op, $val);
    }

    /**
     * Parses a value - a comma-delimited list of expressions like:.
     *
     * `font-family: Baskerville, Georgia, serif;`
     *
     * @return ValueNode|null
     */
    protected function parseValue()
    {
        $e = null;
        $expressions = [];
        do {
            $e = $this->parseExpression();
            if ($e) {
                $expressions[] = $e;
                if (!$this->input->char(',')) {
                    break;
                }
            }
        } while ($e);

        if (count($expressions) > 0) {
            return new ValueNode($expressions);
        }
    }

    /**
     * Parses the `!important` keyword.
     *
     * @return string|null
     */
    protected function parseImportant()
    {
        if ($this->input->currentChar() === '!') {
            return $this->input->re('/\\G! *important/');
        }
    }

    /**
     * Parses a variable.
     *
     * @return string
     */
    protected function parseVariable()
    {
        if ($this->input->currentChar() == '@' && ($name = $this->input->re('/\\G(@[\w-]+)\s*:/'))) {
            return $name[1];
        }
    }

    /**
     * Parses a variable entity using the protective `{}` like: `@{variable}`.
     *
     * @return VariableNode|null
     */
    protected function parseEntitiesVariableCurly()
    {
        $index = $this->input->i;
        if ($this->input->currentChar() === '@' && ($curly = $this->input->re('/\\G@\{([\w-]+)\}/'))) {
            return new VariableNode('@' . $curly[1], $index, $this->context->currentFileInfo);
        }
    }

    /**
     * Parses rule property.
     *
     * @return array
     */
    protected function parseRuleProperty()
    {
        $this->input->save();
        $index = [];
        $name = [];

        $simpleProperty = $this->input->re('/\\G([_a-zA-Z0-9-]+)\s*:/');
        if ($simpleProperty) {
            $name = new KeywordNode($simpleProperty[1]);
            $this->input->forget();

            return [$name];
        }

        // In PHP 5.3 we cannot use $this in the closure
        $input = $this->input;
        $match = function ($re) use (&$index, &$name, $input) {
            $i = $input->i;
            $chunk = $input->re($re);
            if ($chunk) {
                $index[] = $i;
                $name[] = $chunk[1];

                return count($name);
            }
        };

        $match('/\\G(\*?)/');

        while (true) {
            if (!$match('/\\G((?:[\w-]+)|(?:@\{[\w-]+\}))/')) {
                break;
            }
        }

        if (count($name) > 1 && $match('/\\G((?:\+_|\+)?)\s*:/')) {
            $this->input->forget();

            // at last, we have the complete match now. move forward,
            // convert name particles to tree objects and return:
            if ($name[0] === '') {
                array_shift($name);
                array_shift($index);
            }

            for ($k = 0; $k < count($name); ++$k) {
                $s = $name[$k];
                // intentionally @, the name can be an empty string
                $name[$k] = @$s[0] !== '@' ?
                    new KeywordNode($s) :
                    new VariableNode('@' . substr($s, 2, -1), $index[$k], $this->context->currentFileInfo);
            }

            return $name;
        }

        $this->input->restore();
    }

    /**
     * Parses an addition operation.
     *
     * @return OperationNode|null
     */
    protected function parseAddition()
    {
        $operation = false;
        if ($m = $this->parseMultiplication()) {
            $isSpaced = $this->input->isWhitespace(-1);
            while (true) {
                $op = ($op = $this->input->re('/\\G[-+]\s+/')) ? $op : (!$isSpaced ? ($this->match(
                    ['+', '-']
                )) : false);
                if (!$op) {
                    break;
                }

                $a = $this->parseMultiplication();
                if (!$a) {
                    break;
                }

                $m->parensInOp = true;
                $a->parensInOp = true;

                $operation = new OperationNode($op, [$operation ? $operation : $m, $a], $isSpaced);
                $isSpaced = $this->input->isWhitespace(-1);
            }

            return $operation ? $operation : $m;
        }
    }

    /**
     * Parses multiplication operation.
     *
     * @return OperationNode|null
     */
    protected function parseMultiplication()
    {
        $operation = null;

        if ($m = $this->parseOperand()) {
            $isSpaced = $this->input->isWhitespace(-1);
            while (true) {
                if ($this->input->peek('/\\G\/[*\/]/')) {
                    break;
                }

                $this->input->save();

                $op = $this->match(['/', '*']);

                if (!$op) {
                    $this->input->forget();
                    break;
                }

                $a = $this->parseOperand();

                if (!$a) {
                    $this->input->restore();
                    break;
                }

                $this->input->forget();

                $m->parensInOp = true;
                $a->parensInOp = true;

                $operation = new OperationNode($op, [$operation ? $operation : $m, $a], $isSpaced);
                $isSpaced = $this->input->isWhitespace(-1);
            }

            return $operation ? $operation : $m;
        }
    }

    /**
     * Parses the conditions.
     *
     * @return ConditionNode|null
     */
    protected function parseConditions()
    {
        $index = $this->input->i;
        $condition = null;
        if ($a = $this->parseCondition()) {
            while (true) {
                if (!$this->input->peekReg('/\\G,\s*(not\s*)?\(/') || !$this->input->char(',')) {
                    break;
                }
                $b = $this->parseCondition();
                if (!$b) {
                    break;
                }

                $condition = new ConditionNode('or', $condition ? $condition : $a, $b, $index);
            }

            return $condition ? $condition : $a;
        }
    }

    /**
     * Parses condition.
     *
     * @return ConditionNode|null
     *
     * @throws ParserException
     */
    protected function parseCondition()
    {
        $index = $this->input->i;
        $negate = false;

        if ($this->input->str('not')) {
            $negate = true;
        }

        $this->expect('(');
        if ($a = ($this->matchFuncs(['parseAddition', 'parseEntitiesKeyword', 'parseEntitiesQuoted']))) {
            $op = null;
            if ($this->input->char('>')) {
                if ($this->input->char('=')) {
                    $op = '>=';
                } else {
                    $op = '>';
                }
            } elseif ($this->input->char('<')) {
                if ($this->input->char('=')) {
                    $op = '<=';
                } else {
                    $op = '<';
                }
            } elseif ($this->input->char('=')) {
                if ($this->input->char('>')) {
                    $op = '=>';
                } elseif ($this->input->char('<')) {
                    $op = '=<';
                } else {
                    $op = '=';
                }
            }

            $c = null;
            if ($op) {
                $b = $this->matchFuncs(['parseAddition', 'parseEntitiesKeyword', 'parseEntitiesQuoted']);
                if ($b) {
                    $c = new ConditionNode($op, $a, $b, $index, $negate);
                } else {
                    throw new ParserException('Unexpected expression', $index, $this->context->currentFileInfo);
                }
            } else {
                $c = new ConditionNode('=', $a, new KeywordNode('true'), $index, $negate);
            }

            $this->expect(')');

            return $this->input->str('and') ? new ConditionNode('and', $c, $this->parseCondition()) : $c;
        }
    }

    /**
     * Parses a sub-expression.
     *
     * @return ExpressionNode|null
     */
    protected function parseSubExpression()
    {
        $this->input->save();

        if ($this->input->char('(')) {
            $a = $this->parseAddition();
            if ($a && $this->input->char(')')) {
                $this->input->forget();
                $e = new ExpressionNode([$a]);
                $e->parens = true;

                return $e;
            }

            $this->input->restore("Expected ')'");

            return;
        }

        $this->input->restore();
    }

    /**
     * Parses an operand. An operand is anything that can be part of an operation,
     * such as a color, or a variable.
     *
     * @return NegativeNode|null
     */
    protected function parseOperand()
    {
        $negate = false;
        if ($this->input->peekReg('/\\G^-[@\(]/')) {
            $negate = $this->input->char('-');
        }

        $o = $this->matchFuncs(
            [
                'parseSubExpression',
                'parseEntitiesDimension',
                'parseEntitiesColor',
                'parseEntitiesVariable',
                'parseEntitiesCall',
            ]
        );

        if ($negate) {
            $o->parensInOp = true;
            $o = new NegativeNode($o);
        }

        return $o;
    }

    /**
     * Parses a block. The `block` rule is used by `ruleset` and `mixin definition`.
     * It's a wrapper around the `primary` rule, with added `{}`.
     *
     * @return array
     */
    protected function parseBlock()
    {
        if ($this->input->char('{') && (is_array($content = $this->parsePrimary())) && $this->input->char('}')) {
            return $content;
        }
    }

    /**
     * @return Node|RulesetNode|null
     */
    protected function parseBlockRuleset()
    {
        $block = $this->parseBlock();
        if (null !== $block) {
            $block = new RulesetNode([], $block);
        }

        return $block;
    }

    /**
     * @return DetachedRulesetNode|null
     */
    protected function parseDetachedRuleset()
    {
        $blockRuleset = $this->parseBlockRuleset();
        if ($blockRuleset) {
            return new DetachedRulesetNode($blockRuleset);
        }
    }

    /**
     * Parses comments.
     *
     * @return array Array of comments
     */
    protected function parseComments()
    {
        $comments = [];
        while ($comment = $this->parseComment()) {
            $comments[] = $comment;
        }

        return $comments;
    }

    /**
     * Parses the CSS directive like:.
     *
     * <pre>
     *
     * @charset "utf-8";
     * </pre>
     *
     * @return DirectiveNode|null
     *
     * @throws ParserException
     */
    protected function parseDirective()
    {
        $hasBlock = true;
        $hasIdentifier = false;
        $hasExpression = false;
        $isRooted = true;
        $rules = null;
        $hasUnknown = null;
        $index = $this->input->i;

        if ($this->input->currentChar() !== '@') {
            return;
        }

        $value = $this->matchFuncs(['parseImport', 'parsePlugin', 'parseMedia']);

        if ($value) {
            return $value;
        }

        $this->input->save();

        $name = $this->input->re('/\\G@[a-z-]+/');

        if (!$name) {
            return;
        }

        $nonVendorSpecificName = $name;
        $pos = strpos($name, '-', 2);
        if ($name[1] == '-' && $pos > 0) {
            $nonVendorSpecificName = '@' . substr($name, $pos + 1);
        }

        switch ($nonVendorSpecificName) {
            /*
            case '@font-face':
            case '@viewport':
            case '@top-left':
            case '@top-left-corner':
            case '@top-center':
            case '@top-right':
            case '@top-right-corner':
            case '@bottom-left':
            case '@bottom-left-corner':
            case '@bottom-center':
            case '@bottom-right':
            case '@bottom-right-corner':
            case '@left-top':
            case '@left-middle':
            case '@left-bottom':
            case '@right-top':
            case '@right-middle':
            case '@right-bottom':
                $hasBlock = true;
                $isRooted = true;
                break;
            */
            case '@counter-style':
                $hasIdentifier = true;
                $hasBlock = true;
                break;
            case '@charset':
                $hasIdentifier = true;
                $hasBlock = false;
                break;
            case '@namespace':
                $hasExpression = true;
                $hasBlock = false;
                break;
            case '@keyframes':
                $hasIdentifier = true;
                break;
            case '@host':
            case '@page':
                $hasUnknown = true;
                break;
            case '@document':
            case '@supports':
                $hasUnknown = true;
                $isRooted = false;
                break;

        }

        $this->input->commentStore = [];

        if ($hasIdentifier) {
            $value = $this->parseEntity();
            if (!$value) {
                throw new ParserException(sprintf('Expected %s identifier', $name));
            }
        } elseif ($hasExpression) {
            $value = $this->parseExpression();
            if (!$value) {
                throw new ParserException(sprintf('Expected %s expression', $name));
            }
        } elseif ($hasUnknown) {
            $value = $this->input->re('/\\G^[^{;]+/');
            $value = trim((string) $value);
            if ($value) {
                $value = new AnonymousNode($value);
            }
        }

        if ($hasBlock) {
            $rules = $this->parseBlockRuleset();
        }

        if ($rules || (!$hasBlock && $value && $this->input->char(';'))) {
            $this->input->forget();

            return new DirectiveNode(
                $name, $value, $rules, $index, $this->context->currentFileInfo,
                $this->context->dumpLineNumbers ? $this->getDebugInfo($index) : null,
                false, $isRooted
            );
        }

        $this->input->restore('Directive options not recognised');
    }

    /**
     * Entities are the smallest recognized token, and can be found inside a rule's value.
     *
     * @return Node|null
     */
    protected function parseEntity()
    {
        return $this->matchFuncs(
            [
                'parseComment',
                'parseEntitiesLiteral',
                'parseEntitiesVariable',
                'parseEntitiesUrl',
                'parseEntitiesCall',
                'parseEntitiesKeyword',
                'parseEntitiesJavascript',
            ]
        );
    }

    /**
     * Parse entities literal.
     *
     * @return Node|null
     */
    protected function parseEntitiesLiteral()
    {
        return $this->matchFuncs(
            [
                'parseEntitiesDimension',
                'parseEntitiesColor',
                'parseEntitiesQuoted',
                'parseUnicodeDescriptor',
            ]
        );
    }

    /**
     * Parses an entity variable.
     *
     * @return VariableNode|null
     */
    protected function parseEntitiesVariable()
    {
        $index = $this->input->i;
        if ($this->input->currentChar() === '@' && ($name = $this->input->re('/\\G^@@?[\w-]+/'))) {
            return new VariableNode($name, $index, $this->context->currentFileInfo);
        }
    }

    /**
     * Parse entities dimension (a number and a unit like 0.5em, 95%).
     *
     * @return DimensionNode|null
     */
    protected function parseEntitiesDimension()
    {
        if ($this->input->peekNotNumeric()) {
            return;
        }

        if ($value = $this->input->re('/\\G^([+-]?\d*\.?\d+)(%|[a-z]+)?/i')) {
            return new DimensionNode($value[1], isset($value[2]) ? $value[2] : null);
        }
    }

    /**
     * Parses a hexadecimal color.
     *
     * @return ColorNode
     *
     * @throws ParserException
     */
    protected function parseEntitiesColor()
    {
        // we are more tolerate here than in less.js, which can use regexp input property
        // to get the regular expression input, the regexp includes A-z but the color hex code is only A-F
        if ($this->input->currentChar() === '#' && ($rgb = $this->input->re('/\\G#([A-Za-z0-9]{6}|[A-Za-z0-9]{3})/'))) {
            $colorCandidate = $rgb[1];
            // verify if candidate consists only of allowed HEX characters
            if (!preg_match('/^[A-Fa-f0-9]+$/', $colorCandidate)) {
                throw new ParserException('Invalid HEX color code', $this->input->i,
                    $this->context->currentFileInfo);
            }

            return new ColorNode($colorCandidate, null, '#' . $colorCandidate);
        }
    }

    /**
     * Parses a string, which supports escaping " and '
     * "milky way" 'he\'s the one!'.
     *
     * @return QuotedNode|null
     */
    protected function parseEntitiesQuoted()
    {
        $isEscaped = false;
        $index = $this->input->i;

        $this->input->save();

        if ($this->input->char('~')) {
            $isEscaped = true;
        }

        $str = $this->input->quoted();

        if (!$str) {
            $this->input->restore();

            return;
        }

        $this->input->forget();

        return new QuotedNode(
            $str[0],
            substr($str, 1, strlen($str) - 2),
            $isEscaped,
            $index,
            $this->context->currentFileInfo
        );
    }

    /**
     * Parses an unicode descriptor, as is used in unicode-range U+0?? or U+00A1-00A9.
     *
     * @return UnicodeDescriptorNode|null
     */
    protected function parseUnicodeDescriptor()
    {
        if ($ud = $this->input->re('/\\G(U\+[0-9a-fA-F?]+)(\-[0-9a-fA-F?]+)?/')) {
            return new UnicodeDescriptorNode($ud[0]);
        }
    }

    /**
     * A catch-all word, such as: `black border-collapse`.
     *
     * @return ColorNode|KeywordNode|null
     */
    protected function parseEntitiesKeyword()
    {
        $k = $this->input->char('%');
        if (!$k) {
            $k = $this->input->re('/\\G[_A-Za-z-][_A-Za-z0-9-]*/');
        }

        if ($k) {
            // detected named color and "transparent" keyword
            if ($color = Color::fromKeyword($k)) {
                return new ColorNode($color);
            } else {
                return new KeywordNode($k);
            }
        }
    }

    /**
     * Parses url() tokens.
     *
     * @return UrlNode|null
     */
    protected function parseEntitiesUrl()
    {
        $index = $this->input->i;
        $this->input->autoCommentAbsorb = false;

        if (!$this->input->str('url(')) {
            $this->input->autoCommentAbsorb = true;

            return;
        }

        $value = $this->match(
            [
                'parseEntitiesQuoted',
                'parseEntitiesVariable',
                '/\\G(?>[^\\(\\)\'"]+|(?<=\\\\)[\\(\\)\'"])+/',
            ]
        );

        $this->input->autoCommentAbsorb = true;

        $this->expect(')');

        return new UrlNode(
            (isset($value->value) || $value instanceof VariableNode) ? $value : new AnonymousNode(
                (string) $value
            ),
            $index,
            $this->context->currentFileInfo
        );
    }

    /**
     * Parses a function call.
     *
     * @return CallNode|null
     */
    protected function parseEntitiesCall()
    {
        if ($this->input->peekReg('/\\G^url\(/i')) {
            return;
        }

        $index = $this->input->i;

        $this->input->save();

        $name = $this->input->re('/\\G([\w-]+|%|progid:[\w\.]+)\(/');

        if (!$name) {
            $this->input->forget();

            return;
        }

        $name = $name[1];
        $nameLC = strtolower($name);

        if ($nameLC === 'alpha') {
            $alpha = $this->parseAlpha();
            if ($alpha) {
                $this->input->forget();

                return $alpha;
            }
        }

        $args = $this->parseEntitiesArguments();

        if (!$this->input->char(')')) {
            $this->input->restore("Could not parse call arguments or missing ')'");

            return;
        }

        $this->input->forget();

        return new CallNode($name, $args, $index, $this->context->currentFileInfo);
    }

    /**
     * Parse a list of arguments.
     *
     * @return array
     */
    protected function parseEntitiesArguments()
    {
        $args = [];

        while (true) {
            $arg = $this->matchFuncs(['parseEntitiesAssignment', 'parseExpression']);
            if (!$arg) {
                break;
            }
            $args[] = $arg;
            if (!$this->input->char(',')) {
                break;
            }
        }

        return $args;
    }

    /**
     * Parses an assignments (argument entities for calls).
     * They are present in ie filter properties as shown below.
     * filter: progid:DXImageTransform.Microsoft.Alpha( *opacity=50* ).
     *
     * @return AssignmentNode|null
     */
    protected function parseEntitiesAssignment()
    {
        $this->input->save();
        $key = $this->input->re('/\\G\w+(?=\s?=)/i');
        if (!$key) {
            $this->input->restore();

            return;
        }

        if (!$this->input->char('=')) {
            $this->input->restore();

            return;
        }

        $value = $this->parseEntity();
        if ($value) {
            $this->input->forget();

            return new AssignmentNode($key, $value);
        } else {
            $this->input->restore();
        }
    }

    /**
     * Parses an expression. Expressions either represent mathematical operations,
     * or white-space delimited entities like: `1px solid black`, `@var * 2`.
     *
     * @return ExpressionNode|null
     */
    protected function parseExpression()
    {
        $entities = [];
        $e = null;
        do {
            $e = $this->parseComment();
            if ($e) {
                $entities[] = $e;
                continue;
            }

            $e = $this->matchFuncs(['parseAddition', 'parseEntity']);
            if ($e) {
                $entities[] = $e;
                // operations do not allow keyword "/" dimension (e.g. small/20px) so we support that here
                if (!$this->input->peekReg('/\\G\/[\/*]/')) {
                    $delim = $this->input->char('/');
                    if ($delim) {
                        $entities[] = new AnonymousNode($delim);
                    }
                }
            }
        } while ($e);

        if (count($entities) > 0) {
            return new ExpressionNode($entities);
        }
    }

    /**
     * Parses IE's alpha function `alpha(opacity=88)`.
     *
     * @return AlphaNode|null
     */
    protected function parseAlpha()
    {
        if (!$this->input->re('/\\G^opacity=/i')) {
            return;
        }

        $value = $this->input->re('/\\G^\d+/');
        if ($value === null) {
            $value = $this->parseEntitiesVariable();
            if (!$value) {
                throw new ParserException('Could not parse alpha', $this->input->i, $this->context->currentFileInfo);
            }
        }

        $this->expect(')');

        return new AlphaNode($value);
    }

    /**
     * Parses a javascript code.
     *
     * @return JavascriptNode|null
     */
    protected function parseEntitiesJavascript()
    {
        $index = $this->input->i;

        $this->input->save();

        $escape = $this->input->char('~');
        $jsQuote = $this->input->char('`');

        if (!$jsQuote) {
            $this->input->restore();

            return;
        }

        if ($js = $this->input->re('/\\G^[^`]*`/')) {
            $this->input->forget();

            return new JavascriptNode(
                substr($js, 0, strlen($js) - 1),
                (bool) $escape,
                $index,
                $this->context->currentFileInfo
            );
        } else {
            $this->input->restore('Invalid javascript definition');
        }
    }

    /**
     * Parses a @import directive.
     *
     * @return ImportNode|null
     *
     * @throws ParserException
     */
    protected function parseImport()
    {
        $index = $this->input->i;
        $dir = $this->input->re('/\\G^@import?\s+/');

        if ($dir) {
            $options = $this->parseImportOptions();
            if (!$options) {
                $options = [];
            }

            if (($path = $this->matchFuncs(['parseEntitiesQuoted', 'parseEntitiesUrl']))) {
                $features = $this->parseMediaFeatures();

                if (!$this->input->char(';')) {
                    $this->input->i = $index;
                    throw new ParserException(
                        'Missing semi-colon or unrecognised media features on import',
                        $index,
                        $this->context->currentFileInfo
                    );
                }

                if ($features) {
                    $features = new ValueNode($features);
                }

                return new ImportNode($path, $features, $options, $index, $this->context->currentFileInfo);
            } else {
                $this->input->i = $index;
                throw new ParserException('Malformed import statement', $index, $this->context->currentFileInfo);
            }
        }
    }

    /**
     * Parses import options.
     *
     * @return array
     */
    protected function parseImportOptions()
    {
        // list of options, surrounded by parens
        if (!$this->input->char('(')) {
            return;
        }

        $options = [];

        do {
            if ($o = $this->parseImportOption()) {
                $optionName = $o;
                $value = true;
                switch ($optionName) {
                    case 'css':
                        $optionName = 'less';
                        $value = false;
                        break;
                    case 'once':
                        $optionName = 'multiple';
                        $value = false;
                        break;
                }
                $options[$optionName] = $value;
                if (!$this->input->char(',')) {
                    break;
                }
            }
        } while ($o);

        $this->expect(')');

        return $options;
    }

    /**
     * Parses import option.
     *
     * @return string|null
     */
    protected function parseImportOption()
    {
        if (($opt = $this->input->re('/\\G(less|css|multiple|once|inline|reference|optional)/'))) {
            return $opt[1];
        }
    }

    /**
     * Parses media block.
     *
     * @return MediaNode|null
     */
    protected function parseMedia()
    {
        $debugInfo = null;
        if ($this->context->dumpLineNumbers) {
            $debugInfo = $this->getDebugInfo($this->input->i);
        }

        $this->input->save();

        if ($this->input->str('@media')) {
            $features = $this->parseMediaFeatures();
            $rules = $this->parseBlock();

            if (null === $rules) {
                $this->input->restore('Media definitions require block statements after any features');

                return;
            }

            $this->input->forget();

            $media = new MediaNode($rules, $features, $this->input->i, $this->context->currentFileInfo);

            if ($debugInfo) {
                $media->debugInfo = $debugInfo;
            }

            return $media;
        }

        $this->input->restore();
    }

    /**
     * Parses media features.
     *
     * @return array
     */
    protected function parseMediaFeatures()
    {
        $features = [];
        do {
            if ($e = $this->parseMediaFeature()) {
                $features[] = $e;
                if (!$this->input->char(',')) {
                    break;
                }
            } elseif ($e = $this->parseEntitiesVariable()) {
                $features[] = $e;
                if (!$this->input->char(',')) {
                    break;
                }
            }
        } while ($e);

        return $features ? $features : null;
    }

    /**
     * Parses single media feature.
     *
     * @return ExpressionNode|null
     */
    protected function parseMediaFeature()
    {
        $nodes = [];
        $this->input->save();

        do {
            if ($e = $this->matchFuncs(['parseEntitiesKeyword', 'parseEntitiesVariable'])) {
                $nodes[] = $e;
            } elseif ($this->input->char('(')) {
                $p = $this->parseProperty();
                $e = $this->parseValue();
                if ($this->input->char(')')) {
                    if ($p && $e) {
                        $nodes[] = new ParenNode(
                            new RuleNode($p, $e, null, null, $this->input->i, $this->context->currentFileInfo, true)
                        );
                    } elseif ($e) {
                        $nodes[] = new ParenNode($e);
                    } else {
                        $this->input->restore('Badly formed media feature definition');

                        return;
                    }
                } else {
                    $this->input->restore("Missing closing ')'");

                    return;
                }
            }
        } while ($e);

        $this->input->forget();

        if ($nodes) {
            return new ExpressionNode($nodes);
        }
    }

    /**
     * A @plugin directive, used to import compiler extensions dynamically. `@plugin "lib"`;.
     *
     * @return ImportNode|null
     *
     * @throws ParserException
     */
    protected function parsePlugin()
    {
        $index = $this->input->i;
        $dir = $this->input->re('/\\G^@plugin?\s+/');
        if ($dir) {
            $options = ['plugin' => true];
            if (($path = $this->matchFuncs(['parseEntitiesQuoted', 'parseEntitiesUrl']))) {
                if (!$this->input->char(';')) {
                    $this->input->i = $index;
                    throw new ParserException('Missing semi-colon on plugin');
                }

                return new ImportNode($path, null, $options, $index, $this->context->currentFileInfo);
            } else {
                $this->input->i = $index;
                throw new ParserException('Malformed plugin statement');
            }
        }
    }

    /**
     * Parses the property.
     *
     * @return string|null
     */
    protected function parseProperty()
    {
        if ($name = $this->input->re('/\\G(\*?-?[_a-zA-Z0-9-]+)\s*:/')) {
            return $name[1];
        }
    }

    /**
     * Parses a rule terminator.
     *
     * @return string
     */
    protected function parseEnd()
    {
        return ($end = $this->input->char(';')) ? $end : $this->input->peek('}');
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
     * Set the current parser environment.
     *
     * @param Context $context
     *
     * @return $this
     */
    public function setContext(Context $context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Returns the importer.
     *
     * @return Importer
     */
    public function getImporter()
    {
        return $this->importer;
    }

    /**
     * Set the importer.
     *
     * @param Importer $importer
     *
     * @return $this
     */
    public function setImporter(Importer $importer)
    {
        $this->importer = $importer;

        return $this;
    }

    /**
     * Parse from a token, regexp or string, and move forward if match.
     *
     * @param array|string $token The token
     *
     * @return null|bool|object
     */
    protected function match($token)
    {
        if (!is_array($token)) {
            $token = [$token];
        }

        foreach ($token as $t) {
            if (strlen($t) === 1) {
                $match = $this->input->char($t);
            } elseif ($t[0] !== '/') {
                // Non-terminal, match using a function call
                $match = $this->$t();
            } else {
                $match = $this->input->re($t);
            }

            if (null !== $match) {
                return $match;
            }
        }
    }

    /**
     * Matches given functions. Returns the result of the first which returns
     * any non null value.
     *
     * @param array $functions The array of functions to call
     *
     * @throws InvalidArgumentException If the function does not exist
     *
     * @return Node|mixed
     */
    protected function matchFuncs(array $functions)
    {
        foreach ($functions as $func) {
            if (!method_exists($this, $func)) {
                throw new InvalidArgumentException(sprintf('The function "%s" does not exist.', $func));
            }
            $match = $this->$func();
            if ($match !== null) {
                return $match;
            }
        }
    }

    /**
     * Expects a string to be present at the current position.
     *
     * @param string $token The single character
     * @param string $message The error message for the exception
     *
     * @return Node|null
     *
     * @throws ParserException If the expected token does not match
     */
    protected function expect($token, $message = null)
    {
        $result = $this->match($token);
        if (!$result) {
            throw new ParserException(
                $message ? $message :
                    sprintf(
                        'Expected \'%s\' got \'%s\' at index %s',
                        $token,
                        $this->input->currentChar(),
                        $this->input->i
                    ),
                $this->input->i, $this->context->currentFileInfo
            );
        }

        return $result;
    }

    /**
     * Returns the debug information.
     *
     * @param int $index The index
     *
     * @return \ILess\DebugInfo
     */
    protected function getDebugInfo($index)
    {
        list($lineNumber) = Util::getLocation($this->input->getInput(), $index);

        return new DebugInfo($this->context->currentFileInfo->filename, $lineNumber);
    }
}

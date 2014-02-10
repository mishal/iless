<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Parser core
 *
 * @package ILess
 * @subpackage parser
 */
class ILess_Parser_Core
{
    /**
     * The environment
     *
     * @var ILess_Environment
     */
    protected $env;

    /**
     * The importer
     *
     * @var ILess_Importer
     */
    protected $importer;

    /**
     * The string to be parsed
     *
     * @var string
     */
    protected $input = '';

    /**
     * The length of the string to be parsed
     *
     * @var integer
     */
    protected $length = 0;

    /**
     * The current position in the input
     *
     * @var integer
     */
    protected $position = 0;

    /**
     * The furthest index the parser has gone to
     *
     * @var integer
     */
    protected $furthestPosition = 0;

    /**
     * The saved position
     *
     * @var integer
     * @see save(), restore()
     */
    private $savedPosition = 0;

    /**
     * Array of variables
     *
     * @var array
     */
    protected $variables = array();

    /**
     * Array of parsed rules
     *
     * @var array
     */
    protected $rules = array();

    /**
     * Constructor
     *
     * @param ILess_Environment $env The environment
     * @param ILess_Importer $importer The importer
     */
    public function __construct(ILess_Environment $env, ILess_Importer $importer)
    {
        $this->env = $env;
        $this->importer = $importer;
    }

    /**
     * Parse a Less string from a given file
     *
     * @throws ILess_Exception_Parser
     * @param string|ILess_ImportedFile $fil The file to parse (Will be loaded via the importers)
     * @param boolean $returnRuleset Indicates whether the parsed rules should be wrapped in a ruleset.
     * @return mixed If $returnRuleset is true, ILess_Parser_Core, ILess_Node_Ruleset otherwise
     */
    public function parseFile($file, $returnRuleset = false)
    {
        // save the previous information
        $previousFileInfo = $this->env->currentFileInfo;

        if (!($file instanceof ILess_ImportedFile)) {
            $this->env->setCurrentFile($file);

            if ($previousFileInfo) {
                $this->env->currentFileInfo->reference = $previousFileInfo->reference;
            }

            // try to load it via importer
            list(, $file) = $this->importer->import($file, $this->env->currentFileInfo);
            $this->env->setCurrentFile($file->getPath());

        } else {
            $this->env->setCurrentFile($file->getPath());

            if ($previousFileInfo) {
                $this->env->currentFileInfo->reference = $previousFileInfo->reference;
            }
        }

        $this->env->currentFileInfo->importedFile = $file;

        $rules = $this->parse($file->getContent());

        if ($previousFileInfo) {
            $this->env->currentFileInfo = $previousFileInfo;
        }

        if ($returnRuleset) {
            return new ILess_Node_Ruleset(array(), $rules);
        }

        $this->rules = array_merge($this->rules, $rules);

        return $this;
    }

    /**
     * Parses a string
     *
     * @param string $string The string to parse
     * @param string $filename The filename for reference (will be visible in the source map)
     * @return ILess_Parser_Core
     */
    public function parseString($string, $filename = '__string_to_parse__')
    {
        $string = ILess_Util::normalizeString((string)$string);

        // we need unique key
        $key = sprintf('%s[__%s__]', $filename, md5($string));

        // create a dummy information, since we are not parsing a real file,
        // but a string comming from outside
        $this->env->setCurrentFile($filename);
        $importedFile = new ILess_ImportedFile($key, $string, time());

        // save information, so the exceptions can handle errors in the string
        // and source map is generated for the string
        $this->env->currentFileInfo->importedFile = $importedFile;
        $this->importer->setImportedFile($key, $importedFile, $key, $this->env->currentFileInfo);

        if ($this->env->sourceMap) {
            $this->env->setFileContent($key, $string);
        }

        $this->rules = array_merge($this->rules, $this->parse($string));

        return $this;
    }

    /**
     * Adds variables
     *
     * @param array $variables Array of variables
     * @return ILess_Parser_Core
     */
    public function addVariables(array $variables)
    {
        $this->variables = array_merge($this->variables, $variables);

        return $this;
    }

    /**
     * Clears all assigned variables
     *
     * @return ILess_Parser_Core
     */
    public function clearVariables()
    {
        $this->variables = array();

        return $this;
    }

    /**
     * Sets variables
     *
     * @param array $variables
     * @return ILess_Parser_Core
     */
    public function setVariables(array $variables)
    {
        $this->variables = $variables;

        return $this;
    }

    /**
     * Unsets a previously set variable
     *
     * @param string|array $variable The variable name(s) to unset as string or an array
     * @see setVariables, addVariables
     * @return ILess_Parser_Core
     */
    public function unsetVariable($variable)
    {
        if (!is_array($variable)) {
            $variable = array($variable);
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
     * Parse a Less string into nodes
     *
     * @param string $string The string to parse
     * @return array
     * @throws ILess_Exception_Parser If there was an error in parsing the string
     */
    protected function parse($string)
    {
        $this->setStringToBeParsed($string);
        $this->preParse();
        $rules = $this->parsePrimary();

        // has the whole string been parsed?
        if ($this->furthestPosition < $this->length - 1) {
            throw new ILess_Exception_Parser('Unrecognised input.', $this->furthestPosition, $this->env->currentFileInfo);
        }

        return $rules;
    }

    /**
     * Validates the string.
     *
     * @return true
     * @throws ILess_Exception_Parser If the string is not parsable
     */
    protected function preParse()
    {
        // FIXME: what about utf8?
        $level = $parenLevel = 0;
        $lastOpening = $lastClosing = $lastOpeningParen = $lastMultiComment = $lastMultiCommentEndBrace = $matched = null;
        $length = strlen($this->input);

        for ($parserCurrentIndex = 0; $parserCurrentIndex < $length; $parserCurrentIndex++) {

            // FIXME: what about utf8?
            $cc = ord($this->input[$parserCurrentIndex]);
            if ((($cc >= 97) && ($cc <= 122)) || ($cc < 34)) {
                // a-z or whitespace
                continue;
            }

            switch ($cc) {
                case 40: // (
                    $parenLevel++;
                    continue;
                case 41: // )
                    $parenLevel--;
                    continue;
                case 59:
                    continue;
                case 123: // {
                    $level++;
                    $lastOpening = $parserCurrentIndex;
                    continue;
                case 125: // }
                    $level--;
                    $lastClosing = $parserCurrentIndex;
                    continue;
                case 92: // \
                    if ($parserCurrentIndex < $length - 1) {
                        $parserCurrentIndex++;
                        continue;
                    }
                    throw new ILess_Exception_Parser('Unescaped `\\`.', $parserCurrentIndex, $this->env->currentFileInfo);
                case 34:
                case 39:
                case 96:  // ", ' and `
                    $matched = 0;
                    $currentChunkStartIndex = $parserCurrentIndex;
                    for ($parserCurrentIndex = $parserCurrentIndex + 1; $parserCurrentIndex < $length; $parserCurrentIndex++) {

                        $cc2 = ord($this->input[$parserCurrentIndex]);
                        if ($cc2 > 96) {
                            continue;
                        }
                        if ($cc2 == $cc) {
                            $matched = 1;
                            break;
                        }
                        if ($cc2 == 92) { // \
                            if ($parserCurrentIndex == $length - 1) {
                                throw new ILess_Exception_Parser('Unescaped `\\`.', $parserCurrentIndex, $this->env->currentFileInfo);
                            }
                            $parserCurrentIndex++;
                        }
                    }
                    if ($matched) {
                        continue;
                    }

                    throw new ILess_Exception_Parser(sprintf('Unmatched `%s`.', chr($cc)), $currentChunkStartIndex, $this->env->currentFileInfo);
                case 47: // /, check for comment
                    if ($parenLevel || ($parserCurrentIndex == $length - 1)) {
                        continue;
                    }
                    $cc2 = ord($this->input[$parserCurrentIndex + 1]);
                    if ($cc2 == 47) {
                        // //, find lnfeed
                        for ($parserCurrentIndex = $parserCurrentIndex + 2; $parserCurrentIndex < $length; $parserCurrentIndex++) {
                            $cc2 = ord($this->input[$parserCurrentIndex]);
                            if (($cc2 <= 13) && (($cc2 == 10) || ($cc2 == 13))) {
                                break;
                            }
                        }
                    } elseif ($cc2 == 42) {
                        // /*, find */
                        $lastMultiComment = $currentChunkStartIndex = $parserCurrentIndex;
                        for ($parserCurrentIndex = $parserCurrentIndex + 2; $parserCurrentIndex < $length - 1; $parserCurrentIndex++) {
                            $cc2 = ord($this->input[$parserCurrentIndex]);
                            if ($cc2 == 125) {
                                $lastMultiCommentEndBrace = $parserCurrentIndex;
                            }
                            if ($cc2 != 42) {
                                continue;
                            }
                            if (ord($this->input[($parserCurrentIndex + 1)]) == 47) {
                                break;
                            }
                        }
                        if ($parserCurrentIndex == $length - 1) {
                            throw new ILess_Exception_Parser('Missing closing `*/`.', $currentChunkStartIndex, $this->env->currentFileInfo);
                        }
                    }
                    continue;

                case 42: // *, check for unmatched */
                    if (($parserCurrentIndex < $length - 1) && (ord($this->input[$parserCurrentIndex + 1]) == 47)) {
                        throw new ILess_Exception_Parser('Unmatched `/*`', $parserCurrentIndex, $this->env->currentFileInfo);
                    }
                    continue;
            }
        }

        if ($level !== 0) {
            if($level > 0)
            {
                if (($lastMultiComment > $lastOpening) && ($lastMultiCommentEndBrace > $lastMultiComment)) {
                    throw new ILess_Exception_Parser('Missing closing `}` or `*/`.', $lastOpening, $this->env->currentFileInfo);
                } else {
                    throw new ILess_Exception_Parser('Missing closing `}`', $lastOpening, $this->env->currentFileInfo);
                }
            }

            throw new ILess_Exception_Parser('Missing opening `{`', $lastClosing, $this->env->currentFileInfo);

        } else if ($parenLevel !== 0) {

            if ($parenLevel > 0) {
                throw new ILess_Exception_Parser('Missing closing `)`.', $parserCurrentIndex, $this->env->currentFileInfo);
            } else {
                throw new ILess_Exception_Parser('Missing opening `(`.', $parserCurrentIndex, $this->env->currentFileInfo);
            }
        }

        return true;
    }

    /**
     * Setup the math precision
     *
     * @return void
     */
    private function setupMathAndLocale()
    {
        ILess_Math::setup($this->env->precision);
        ILess_UnitConversion::setup();
    }

    /**
     * Restores the math precision
     *
     * @return void
     */
    private function restoreMathAndLocale()
    {
        ILess_Math::restore();
        ILess_UnitConversion::restore();
    }

    /**
     * Resets the parser
     *
     * @param boolean $variables Reset also assigned variables via the API?
     * @return ILess_Parser_Core
     */
    public function reset($variables = true)
    {
        $this->setStringToBeParsed(null);
        $this->rules = array();

        if ($variables)
        {
            $this->variables = array();
        }

        return $this;
    }

    /**
     * Generates unique cache key for given $filename
     *
     * @param string $filename
     * @return string
     */
    protected function generateCacheKey($filename)
    {
        return ILess_Util::generateCacheKey($filename);
    }

    /**
     * Returns the CSS
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
     * Returns root ruleset
     *
     * @return ILess_Node_Ruleset
     * @todo Make private
     */
    public function getRootRuleset()
    {
        $root = new ILess_Node_Ruleset(array(), $this->rules);
        $root->root = true;
        $root->firstRoot = true;

        return $root;
    }

    /**
     * Converts the ruleset to CSS
     *
     * @param ILess_Node_Ruleset $ruleset
     * @param array $variables
     * @return string The generated CSS code
     */
    protected function toCSS(ILess_Node_Ruleset $ruleset, array $variables)
    {
        $this->setupMathAndLocale();

        // precompilation visitors
        foreach ($this->getPreCompileVisitors() as $visitor) {
            $visitor->run($ruleset);
        }

        $this->prepareVariables($this->env, $variables);

        // compile the ruleset
        $compiled = $ruleset->compile($this->env);

        // post compilation visitors
        foreach ($this->getPostCompileVisitors() as $visitor) {
            $visitor->run($compiled);
        }

        if ($this->getEnvironment()->sourceMap) {
            $generator = new ILess_SourceMap_Generator($compiled,
                $this->env->getContentsMap(), $this->env->sourceMapOptions);
            // will also save file
            // FIXME: should happen somewhere else?
            $css = $generator->generateCSS($this->env);
        } else {
            $css = $compiled->toCSS($this->env);
        }

        if ($this->env->compress) {
            $css = preg_replace('/(^(\s)+)|((\s)+$)/', '', $css);
        }

        $this->restoreMathAndLocale();

        return $css;
    }

    /**
     * Prepare variable to be used as nodes
     *
     * @param ILess_Environment $env
     * @param array $variables
     */
    protected function prepareVariables(ILess_Environment $env, array $variables)
    {
        // FIXME: flag to mark variables as prepared!
        $prepared = array();
        foreach ($variables as $name => $value) {
            // user provided node, no need to process it further
            if ($value instanceof ILess_Node) {
                $prepared[] = $value;
                continue;
            }
            // this is not an "real" variable
            if (!$value instanceof ILess_Variable) {
                $value = ILess_Variable::create($name, $value);
            }
            $prepared[] = $value->toNode();
        }

        if (count($prepared)) {
            $env->customVariables = new ILess_Node_Ruleset(array(), $prepared);
        }
    }

    /**
     * Returns array of precompilation visitors
     *
     * @return array
     */
    protected function getPreCompileVisitors()
    {
        $preCompileVisitors = array();
        // only if process is allowed
        if ($this->env->processImports) {
            $preCompileVisitors[] = new ILess_Visitor_Import($this->getEnvironment(), $this->getImporter());
        }

        // FIXME: allow plugins to hook here
        return $preCompileVisitors;
    }

    /**
     * Returns an array of post compilation visitors
     *
     * @return array
     */
    protected function getPostCompileVisitors()
    {
        $postCompileVisitors = array(
            new ILess_Visitor_JoinSelector(),
            new ILess_Visitor_ProcessExtend(),
            new ILess_Visitor_ToCSS($this->getEnvironment())
        );

        // FIXME: allow plugins to hook here
        return $postCompileVisitors;
    }

    /**
     * Sets the string to be parsed.
     *
     * @param string $string The string to be parsed
     * @return self
     */
    protected function setStringToBeParsed($string)
    {
        // reset the position
        $this->position = $this->savedPosition = $this->furthestPosition = 0;
        $this->input = ILess_Util::normalizeString($string);
        // FIXME: What about UTF-8?
        $this->length = strlen($this->input);

        return $this;
    }

    /**
     * Parses a primary nodes
     *
     * @return array
     */
    protected function parsePrimary()
    {
        $this->skipWhitespace(0);
        $root = array();

        while (($node = $this->matchFuncs(array(
                'parseExtendRule',
                'parseMixinDefinition',
                'parseRule',
                'parseRuleset',
                'parseMixinCall',
                'parseComment',
                'parseDirective'))
            ) || $this->skipSemicolons()) {
            if ($node) {
                // we need to take care of arrays
                if (is_array($node)) {
                    $root = array_merge($root, $node);
                } else {
                    $root[] = $node;
                }
            }
        }

        return $root;
    }

    /**
     * Parses a mixin definition
     *
     * @return ILess_Node_MixinDefinition|null
     */
    protected function parseMixinDefinition()
    {
        if ((!$this->peekChar('.') && !$this->peekChar('#')) || $this->peekReg('/\\G[^{]*\}/')) {
            return;
        }

        $this->save();

        if ($match = $this->matchReg('/\\G([#.](?:[\w-]|\\\(?:[A-Fa-f0-9]{1,6} ?|[^A-Fa-f0-9]))+)\s*\(/')) {
            $cond = null;
            $name = $match[1];
            $argInfo = $this->parseMixinArgs(false);
            $params = $argInfo['args'];
            $variadic = $argInfo['variadic'];

            // .mixincall("@{a}");
            // looks a bit like a mixin definition.. so we have to be nice and restore
            if (!$this->matchChar(')')) {
                $this->furthestPosition = $this->position;
                $this->restore();
            }

            $this->parseComments();

            // Guard
            if ($this->matchString('when')) {
                $cond = $this->expect('parseConditions', 'Expected conditions');
            }

            $ruleset = $this->parseBlock();

            if (is_array($ruleset)) {
                return new ILess_Node_MixinDefinition($name, $params, $ruleset, $cond, $variadic);
            } else {
                $this->restore();
            }
        }
    }

    /**
     * Parses a mixin call with an optional argument list
     *
     *   #mixins > .square(#fff);
     *    .rounded(4px, black);
     *   .button;
     *
     * The `while` loop is there because mixins can be
     * namespaced, but we only support the child and descendant
     * selector for now.
     *
     * @return ILess_Node_MixinCall
     */
    protected function parseMixinCall()
    {
        $elements = array();
        $index = $this->position;
        $important = false;
        $args = null;
        $c = null;

        if (!$this->peekChar('.') && !$this->peekChar('#')) {
            return;
        }

        $this->save(); // stop us absorbing part of an invalid selector

        while ($e = $this->matchReg('/\\G[#.](?:[\w-]|\\\\(?:[A-Fa-f0-9]{1,6} ?|[^A-Fa-f0-9]))+/')) {
            $elements[] = new ILess_Node_Element($c, $e, $this->position, $this->env->currentFileInfo);
            $c = $this->matchChar('>');
        }

        if ($this->matchChar('(')) {
            $returned = $this->parseMixinArgs(true);
            $args = $returned['args'];
            $this->expect(')');
        }

        if (!$args) {
            $args = array();
        }

        if ($this->parseImportant()) {
            $important = true;
        }

        if (count($elements) > 0 && ($this->matchChar(';') || $this->peekChar('}'))) {
            return new ILess_Node_MixinCall($elements, $args, $index, $this->env->currentFileInfo, $important);
        }

        $this->restore();
    }

    /**
     * Parses a rule
     *
     * @param boolean $tryAnonymous
     * @return ILess_Node_Rule
     */
    protected function parseRule($tryAnonymous = false)
    {
        $merge = false;
        $start = $this->position;
        $this->save();

        if (isset($this->input[$this->position])) {
            $c = $this->input[$this->position];
            if ($c === '.' || $c === '#' || $c === '&') {
                return;
            }
        }

        if ($name = $this->matchFuncs(array('parseVariable', 'parseRuleProperty'))) {
            // prefer to try to parse first if its a variable or we are compressing
            // but always fallback on the other one
            if (!$tryAnonymous && ($this->env->compress || ($name[0] === '@'))) {
                $value = $this->matchFuncs(array('parseValue', 'parseAnonymousValue'));
            } else {
                $value = $this->matchFuncs(array('parseAnonymousValue', 'parseValue'));
            }

            $important = $this->parseImportant();

            if (substr($name, -1) === '+') {
                $merge = true;
                $name = substr($name, 0, -1);
            }

            if ($value && $this->parseEnd()) {
                return new ILess_Node_Rule($name, $value, $important, $merge, $start, $this->env->currentFileInfo);
            } else {
                $this->furthestPosition = $this->position;
                $this->restore();
                if ($value && !$tryAnonymous) {
                    return $this->parseRule(true);
                }
            }
        }
    }

    /**
     * Parses an anonymous value
     *
     * @return ILess_Node_Anonymous|null
     */
    protected function parseAnonymousValue()
    {
        if (preg_match('/\\G([^@+\/\'"*`(;{}-]*);/', $this->input, $match, 0, $this->position)) {
            $this->position += strlen($match[0]) - 1;

            return new ILess_Node_Anonymous($match[1]);
        }
    }

    /**
     * Parses a ruleset like: `div, .class, body > p {...}`
     *
     * @return ILess_Node_Ruleset|null
     * @throws ILess_Exception_Parser
     */
    protected function parseRuleset()
    {
        $selectors = array();
        $start = $this->position;

        $debugInfo = null;
        // debugging
        if ($this->env->dumpLineNumbers) {
            $debugInfo = $this->getDebugInfo($this->position, $this->input, $this->env);
        }

        while ($s = $this->parseLessSelector()) {
            $selectors[] = $s;
            $this->parseComments();
            if (!$this->matchChar(',')) {
                break;
            }

            if ($s->condition) {
                throw new ILess_Exception_Compiler('Guards are only currently allowed on a single selector.', $this->position, $this->env->currentFileInfo);
            }

            $this->parseComments();
        }

        if (count($selectors) > 0 && (is_array($rules = $this->parseBlock()))) {
            $ruleset = new ILess_Node_Ruleset($selectors, $rules, $this->env->strictImports);
            if ($debugInfo) {
                $ruleset->debugInfo = $debugInfo;
            }

            return $ruleset;
        } else {
            $this->furthestPosition = $this->position;
            // Backtrack
            $this->position = $start;
        }
    }

    /**
     * Parses a selector wich extensions (the ability to extend and guard)
     *
     * @return ILess_Node_Selector
     * @see parseSelector()
     */
    protected function parseLessSelector()
    {
        return $this->parseSelector(true);
    }

    /**
     * Parses a CSS selector.
     *
     * @param boolean $isLess Is this a less sector? (ie. has ability to extend and guard)
     * @return ILess_Node_Selector
     * @throws ILess_Exception_Parser
     */
    protected function parseSelector($isLess = false)
    {
        $elements = array();
        $extendList = array();
        $condition = null;
        $when = false;
        $extend = false;
        $c = null;

        while (($isLess && ($extend = $this->parseExtend()))
            || ($isLess && ($when = $this->matchString('when'))) || ($e = $this->parseElement())) {
            if ($when) {
                $condition = $this->expect('parseConditions', 'Expected condition');
            } elseif ($condition) {
                throw new ILess_Exception_Parser('CSS guard can only be used at the end of selector.', $this->position, $this->env->currentFileInfo);
            } elseif ($extend) {
                $extendList = array_merge($extendList, $extend);
            } else {
                if (count($extendList)) {
                    throw new ILess_Exception_Compiler('Extend can only be used at the end of selector.', $this->position, $this->env->currentFileInfo);
                }
                $c = $this->input[$this->position];
                $elements[] = $e;
                $e = null;
            }

            if ($c === '{' || $c === '}' || $c === ';' || $c === ',' || $c === ')') {
                break;
            }
        }

        if (count($elements)) {
            return new ILess_Node_Selector($elements, $extendList, $condition, $this->position, $this->env->currentFileInfo);
        }

        if (count($extendList)) {
            throw new ILess_Exception_Compiler('Extend must be used to extend a selector, it cannot be used on its own.', $this->position, $this->env->currentFileInfo);
        }
    }

    /**
     * Parses extend
     *
     * @param boolean $isRule Is is a rule?
     * @return ILess_Node_Extend|null
     */
    protected function parseExtend($isRule = false)
    {
        $index = $this->position;
        $extendList = array();

        if (!$this->matchString($isRule ? '&:extend(' : ':extend(')) {
            return;
        }
        do {
            $option = null;
            $elements = array();
            while (true) {
                $option = $this->matchReg('/\\G(all)(?=\s*(\)|,))/');
                if ($option) {
                    break;
                }
                $e = $this->parseElement();
                if (!$e) {
                    break;
                }
                $elements[] = $e;
            }

            if ($option) {
                $option = $option[1];
            }

            $extendList[] = new ILess_Node_Extend(new ILess_Node_Selector($elements), $option, $index);
        } while ($this->matchChar(','));

        $this->expect('/\\G\)/');

        if ($isRule) {
            $this->expect('/\\G;/');
        }

        return $extendList;
    }

    /**
     * Parses extend rule
     *
     * @return ILess_Node_Extend
     */
    protected function parseExtendRule()
    {
        return $this->parseExtend(true);
    }

    /**
     * Parses a selector element
     *
     *  * `div`
     *  * `+ h1`
     *  * `#socks`
     *  * `input[type="text"]`
     *
     * Elements are the building blocks for selectors,
     * they are made out of a `combinator` and an element name, such as a tag a class, or `*`.
     *
     * @return ILess_Node_Element|null
     */
    protected function parseElement()
    {
        $c = $this->parseCombinator();

        $e = $this->match(array(
            '/\\G(?:\d+\.\d+|\d+)%/',
            //'/\\G(?:[.#]?|:*)(?:[\w-]|[^\x00-\x9f]|\\\\(?:[A-Fa-f0-9]{1,6} ?|[^A-Fa-f0-9]))+/',
            // http://stackoverflow.com/questions/3665962/regular-expression-error-no-ending-delimiter
            '/\\G(?:[.#]?|:*)(?:[\w-]|[^\\x{00}-\\x{9f}]|\\\\(?:[A-Fa-f0-9]{1,6} ?|[^A-Fa-f0-9]))+/',
            '*',
            '&',
            'parseAttribute',
            '/\\G\([^()@]+\)/',
            '/\\G[\.#](?=@)/',
            'parseEntitiesVariableCurly'
        ));

        if (!$e) {
            if ($this->matchChar('(')) {
                if (($v = $this->parseSelector()) && $this->matchChar(')')) {
                    $e = new ILess_Node_Paren($v);
                }
            }
        }

        if ($e) {
            return new ILess_Node_Element($c, $e, $this->position, $this->env->currentFileInfo);
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
     * @return ILess_Node_Combinator|null
     */
    protected function parseCombinator()
    {
        $c = isset($this->input[$this->position]) ? $this->input[$this->position] : '';
        if ($c === '>' || $c === '+' || $c === '~' || $c === '|') {
            $this->position++;
            while ($this->isWhitespace()) {
                $this->position++;
            }

            return new ILess_Node_Combinator($c);
        } elseif ($this->position > 0 && (preg_match('/\s/', $this->input[$this->position - 1]))) {
            return new ILess_Node_Combinator(' ');
        } else {
            return new ILess_Node_Combinator();
        }
    }

    /**
     * Parses an attribute
     *
     * @return ILess_Node_Attribute|null
     */
    protected function parseAttribute()
    {
        if (!$this->matchChar('[')) {
            return;
        }

        if (!($key = $this->parseEntitiesVariableCurly())) {
            $key = $this->expect('/\\G(?:[_A-Za-z0-9-\*]*\|)?(?:[_A-Za-z0-9-]|\\\\.)+/');
        }

        $val = null;
        if (($op = $this->matchReg('/\\G[|~*$^]?=/'))) {
            $val = $this->match(array(
                'parseEntitiesQuoted',
                '/\\G[0-9]+%/',
                '/\\G[\w-]+/',
                'parseEntitiesVariableCurly'
            ));
        }

        $this->expect(']');

        return new ILess_Node_Attribute($key, $op, $val);
    }

    /**
     * Parses a value - a comma-delimited list of expressions like:
     *
     * `font-family: Baskerville, Georgia, serif;`
     *
     * @return ILess_Node_Value|null
     */
    protected function parseValue()
    {
        $expressions = array();
        while ($e = $this->parseExpression()) {
            $expressions[] = $e;
            if (!$this->matchChar(',')) {
                break;
            }
        }
        if (count($expressions) > 0) {
            return new ILess_Node_Value($expressions);
        }
    }

    /**
     * Parses the `!important` keyword
     *
     * @return boolean
     */
    protected function parseImportant()
    {
        if ($this->peekChar('!')) {
            return $this->matchReg('/\\G! *important/');
        }
    }

    /**
     * Parses a variable entity using the protective `{}` like: `@{variable}`
     *
     * @return ILess_Node_Variable|null
     */
    protected function parseEntitiesVariableCurly()
    {
        $index = $this->position;
        if ($this->length > ($this->position + 1) && $this->input[$this->position] === '@' &&
            ($curly = $this->matchReg('/\\G@\{([\w-]+)\}/'))
        ) {
            return new ILess_Node_Variable('@' . $curly[1], $index, $this->env->currentFileInfo);
        }
    }

    /**
     * Parses a variable
     *
     * @return string
     */
    protected function parseVariable()
    {
        if ($this->peekChar('@') && ($name = $this->matchReg('/\\G(@[\w-]+)\s*:/'))) {
            return $name[1];
        }
    }

    /**
     * Parses rule property
     *
     * @return string
     */
    protected function parseRuleProperty()
    {
        if ($name = $this->matchReg('/\\G(\*?-?[_a-zA-Z0-9-]+)\s*(\+?)\s*:/')) {
            return $name[1] . (isset($name[2]) ? $name[2] : '');
        }
    }

    /**
     * Parses mixin arguments
     *
     * @param boolean $isCall The definition or function call?
     * @return array
     * @throws ILess_Exception_Parser If there is an error the definition of arguments
     */
    protected function parseMixinArgs($isCall)
    {
        $expressions = array();
        $argsSemiColon = array();
        $isSemiColonSeperated = null;
        $argsComma = array();
        $expressionContainsNamed = null;
        $name = null;
        $returner = array('args' => null, 'variadic' => false);
        while (true) {
            if ($isCall) {
                $arg = $this->parseExpression();
            } else {
                $this->parseComments();
                if ($this->input[$this->position] === '.' && $this->matchReg('/\\G\.{3}/')) {
                    $returner['variadic'] = true;
                    if ($this->matchChar(";") && !$isSemiColonSeperated) {
                        $isSemiColonSeperated = true;
                    }

                    if ($isSemiColonSeperated) {
                        $argsSemiColon[] = array('variadic' => true);
                    } else {
                        $argsComma[] = array('variadic' => true);
                    }
                    break;
                }
                $arg = $this->matchFuncs(array('parseEntitiesVariable', 'parseEntitiesLiteral', 'parseEntitiesKeyword'));
            }

            if (!$arg) {
                break;
            }

            $nameLoop = null;
            if ($arg instanceof ILess_Node_Expression) {
                $arg->throwAwayComments();
            }

            $value = $arg;
            $val = null;

            if ($isCall) {
                // Variable
                if (count($arg->value) == 1) {
                    $val = $arg->value[0];
                }
            } else {
                $val = $arg;
            }

            if ($val instanceof ILess_Node_Variable) {
                if ($this->matchChar(':')) {
                    if (count($expressions) > 0) {
                        if ($isSemiColonSeperated) {
                            throw new ILess_Exception_Compiler('Cannot mix ; and , as delimiter types', $this->position, $this->env->currentFileInfo);
                        }
                        $expressionContainsNamed = true;
                    }
                    $value = $this->expect('parseExpression');
                    $nameLoop = ($name = $val->name);
                } elseif (!$isCall && $this->matchReg('/\\G\.{3}/')) {
                    $returner['variadic'] = true;
                    if ($this->matchChar(";") && !$isSemiColonSeperated) {
                        $isSemiColonSeperated = true;
                    }
                    if ($isSemiColonSeperated) {
                        $argsSemiColon[] = array('name' => $arg->name, 'variadic' => true);
                    } else {
                        $argsComma[] = array('name' => $arg->name, 'variadic' => true);
                    }
                    break;
                } elseif (!$isCall) {
                    $name = $nameLoop = $val->name;
                    $value = null;
                }
            }

            if ($value) {
                $expressions[] = $value;
            }

            $argsComma[] = array('name' => $nameLoop, 'value' => $value);

            if ($this->matchChar(',')) {
                continue;
            }

            if ($this->matchChar(';') || $isSemiColonSeperated) {
                if ($expressionContainsNamed) {
                    throw new ILess_Exception_Compiler('Cannot mix ; and , as delimiter types', $this->position, $this->env->currentFileInfo);
                }

                $isSemiColonSeperated = true;
                if (count($expressions) > 1) {
                    $value = new ILess_Node_Value($expressions);
                }
                $argsSemiColon[] = array('name' => $name, 'value' => $value);
                $name = null;
                $expressions = array();
                $expressionContainsNamed = false;
            }
        }
        $returner['args'] = ($isSemiColonSeperated ? $argsSemiColon : $argsComma);

        return $returner;
    }

    /**
     * Parses an addition operation
     *
     * @return ILess_Node_Operation|null
     */
    protected function parseAddition()
    {
        $operation = false;
        if ($m = $this->parseMultiplication()) {
            $isSpaced = $this->isWhitespace(-1);
            while (($op = ($op = $this->matchReg('/\\G[-+]\s+/')) ? $op : (!$isSpaced ? ($this->match(array('+', '-'))) : false))
                && ($a = $this->parseMultiplication())) {
                $m->parensInOp = true;
                $a->parensInOp = true;
                $operation = new ILess_Node_Operation($op, array($operation ? $operation : $m, $a), $isSpaced);
                $isSpaced = $this->isWhitespace(-1);
            }

            return $operation ? $operation : $m;
        }
    }

    /**
     * Parses multiplication operation
     *
     * @return ILess_Node_Operation|null
     */
    protected function parseMultiplication()
    {
        $operation = false;

        if ($m = $this->parseOperand()) {
            $isSpaced = $this->isWhitespace(-1);
            while (!$this->peekReg('/\\G\/[*\/]/') && ($op = $this->match(array('/', '*')))) {
                if ($a = $this->parseOperand()) {
                    $m->parensInOp = true;
                    $a->parensInOp = true;
                    $operation = new ILess_Node_Operation($op, array($operation ? $operation : $m, $a), $isSpaced);
                    $isSpaced = $this->isWhitespace(-1);
                } else {
                    break;
                }
            }

            return ($operation ? $operation : $m);
        }
    }

    /**
     * Parses the conditions
     *
     * @return ILess_Node_Condition|null
     */
    protected function parseConditions()
    {
        $index = $this->position;
        $condition = null;
        if ($a = $this->parseCondition()) {
            while ($this->peekReg('/\\G,\s*(not\s*)?\(/') && $this->matchChar(',') && ($b = $this->parseCondition())) {
                $condition = new ILess_Node_Condition('or', $condition ? $condition : $a, $b, $index);
            }

            return $condition ? $condition : $a;
        }
    }

    /**
     * Parses condition
     *
     * @return ILess_Node_Condition|null
     * @throws ILess_Exception_Parser
     */
    protected function parseCondition()
    {
        $index = $this->position;
        $negate = false;

        if ($this->matchString('not')) {
            $negate = true;
        }

        $this->expect('(');
        if ($a = ($this->matchFuncs(array('parseAddition', 'parseEntitiesKeyword', 'parseEntitiesQuoted')))) {
            if ($op = $this->matchReg('/\\G(?:>=|<=|=<|[<=>])/')) {
                if ($b = ($this->matchFuncs(array('parseAddition', 'parseEntitiesKeyword', 'parseEntitiesQuoted')))) {
                    $c = new ILess_Node_Condition($op, $a, $b, $index, $negate);
                } else {
                    throw new ILess_Exception_Parser('Unexpected expression', $this->position, $this->env->currentFileInfo);
                }
            } else {
                $c = new ILess_Node_Condition('=', $a, new ILess_Node_Keyword('true'), $index, $negate);
            }
            $this->expect(')');

            return $this->matchString('and') ? new ILess_Node_Condition('and', $c, $this->parseCondition()) : $c;
        }
    }

    /**
     * Parses a sub-expression
     *
     * @return ILess_Node_Expression|null
     */
    protected function parseSubExpression()
    {
        if ($this->matchChar('(')) {
            if ($a = $this->parseAddition()) {
                $e = new ILess_Node_Expression(array($a));
                $this->expect(')');
                $e->parens = true;

                return $e;
            }
        }
    }

    /**
     * Parses an operand. An operand is anything that can be part of an operation,
     * such as a color, or a variable
     *
     * @return ILess_Node_Negative|null
     */
    protected function parseOperand()
    {
        $negate = false;
        if ($this->peekChar('@', 1) || $this->peekChar('(', 1)) {
            $negate = $this->matchChar('-');
        }

        $o = $this->matchFuncs(array(
            'parseSubExpression',
            'parseEntitiesDimension',
            'parseEntitiesColor',
            'parseEntitiesVariable',
            'parseEntitiesCall'
        ));

        if ($negate) {
            $o->parensInOp = true;
            $o = new ILess_Node_Negative($o);
        }

        return $o;
    }

    /**
     * Parses a block. The `block` rule is used by `ruleset` and `mixin definition`.
     * It's a wrapper around the `primary` rule, with added `{}`.
     *
     * @return ILess_Node
     */
    protected function parseBlock()
    {
        if ($this->matchChar('{') && (is_array($content = $this->parsePrimary())) && $this->matchChar('}')) {
            return $content;
        }
    }

    /**
     * Parses comments. We create a comment node for CSS comments but keep the
     * Less comments `//` silent, by just skipping over them.
     *
     * @return ILess_Node_Comment|null
     */
    protected function parseComment()
    {
        if (!$this->peekChar('/')) {
            return;
        }

        if ($this->peekChar('/', 1)) {
            return new ILess_Node_Comment($this->matchReg('/\G\/\/.*/'), true, $this->position, $this->env->currentFileInfo);
        } //elseif($comment = $this->matchReg('/\G\/\*(?:[^*]|\*+[^\/*])*\*+\/\n?/'))
        elseif ($comment = $this->matchReg('/\\G\/\*(?s).*?\*+\/\n?/')) {
            return new ILess_Node_Comment($comment, false, $this->position, $this->env->currentFileInfo);
        }
    }

    /**
     * Parses comments
     *
     * @return array Array of comments
     */
    protected function parseComments()
    {
        $comments = array();
        while ($comment = $this->parseComment()) {
            $comments[] = $comment;
        }

        return $comments;
    }

    /**
     * Parses the CSS directive like:
     *
     * <pre>
     * @charset "utf-8";
     * </pre>
     *
     * @return ILess_Node_Directive|null
     */
    protected function parseDirective()
    {
        $hasBlock = false;
        $hasIdentifier = false;
        $hasExpression = false;

        if (!$this->peekChar('@')) {
            return;
        }

        $value = $this->matchFuncs(array('parseImport', 'parseMedia'));
        if ($value) {
            return $value;
        }

        $this->save();
        $name = $this->matchReg('/\\G@[a-z-]+/');

        if (!$name) {
            return;
        }

        $nonVendorSpecificName = $name;
        $pos = strpos($name, '-', 2);
        if ($name[1] == '-' && $pos > 0) {
            $nonVendorSpecificName = '@' . substr($name, $pos + 1);
        }

        switch ($nonVendorSpecificName) {
            case '@font-face':
                $hasBlock = true;
                break;
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
                break;
            case '@host':
            case '@page':
            case '@document':
            case '@supports':
            case '@keyframes':
                $hasBlock = true;
                $hasIdentifier = true;
                break;
            case '@namespace':
                $hasExpression = true;
                break;
        }

        if ($hasIdentifier) {
            $identifier = $this->matchReg('/\\G[^{]+/');
            if ($identifier) {
                $name .= ' ' . trim($identifier);
            }
        }

        if ($hasBlock) {
            if ($rules = $this->parseBlock()) {
                return new ILess_Node_Directive($name, $rules, $this->position, $this->env->currentFileInfo);
            }
        } else {
            if (($value = $hasExpression ? $this->parseExpression() : $this->parseEntity()) && $this->matchChar(';')) {
                $directive = new ILess_Node_Directive($name, $value, $this->position, $this->env->currentFileInfo);
                if ($this->env->dumpLineNumbers) {
                    $directive->debugInfo = $this->getDebugInfo($this->position, $this->input, $this->env);
                }

                return $directive;
            }
        }

        $this->restore();
    }

    /**
     * Entities are the smallest recognized token, and can be found inside a rule's value.
     *
     * @return ILess_Node
     */
    protected function parseEntity()
    {
        return $this->matchFuncs(array(
            'parseEntitiesLiteral',
            'parseEntitiesVariable',
            'parseEntitiesUrl',
            'parseEntitiesCall',
            'parseEntitiesKeyword',
            'parseEntitiesJavascript',
            'parseComment'
        ));
    }

    /**
     * Parse entities literal
     *
     * @return ILess_Node|null
     */
    protected function parseEntitiesLiteral()
    {
        return $this->matchFuncs(array(
            'parseEntitiesDimension',
            'parseEntitiesColor',
            'parseEntitiesQuoted',
            'parseUnicodeDescriptor'
        ));
    }

    /**
     * Parses an entity variable
     *
     * @return ILess_Node_Variable|null
     */
    protected function parseEntitiesVariable()
    {
        $index = $this->position;
        if ($this->peekChar('@') && ($name = $this->matchReg('/\\G@@?[\w-]+/'))) {
            return new ILess_Node_Variable($name, $index, $this->env->currentFileInfo);
        }
    }

    /**
     * Parse entities dimension (a number and a unit like 0.5em, 95%)
     *
     * @return ILess_Node_Dimension|null
     */
    protected function parseEntitiesDimension()
    {
        $c = @ord($this->input[$this->position]);

        // Is the first char of the dimension 0-9, '.', '+' or '-'
        if (($c > 57 || $c < 43) || $c === 47 || $c == 44) {
            return;
        }

        if ($value = $this->matchReg('/\\G([+-]?\d*\.?\d+)(%|[a-z]+)?/')) {
            return new ILess_Node_Dimension($value[1], isset($value[2]) ? $value[2] : null);
        }
    }

    /**
     * Parses a hexadecimal color.
     *
     * @return ILess_Node_Color
     */
    protected function parseEntitiesColor()
    {
        if ($this->peekChar('#') && ($rgb = $this->matchReg('/\\G#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})/'))) {
            return new ILess_Node_Color($rgb[1]);
        }
    }

    /**
     * Parses a string, which supports escaping " and '
     * "milky way" 'he\'s the one!'
     *
     * @return ILess_Node_Quoted|null
     */
    protected function parseEntitiesQuoted()
    {
        $j = 0;
        $e = false;
        $index = $this->position;

        if ($this->peekChar('~')) {
            $j++;
            $e = true; // Escaped strings
        }

        if (!$this->peekChar('"', $j) && !$this->peekChar("'", $j)) {
            return;
        }

        if ($e) {
            $this->matchChar('~');
        }

        if ($str = $this->matchReg('/\\G"((?:[^"\\\\\r\n]|\\\\.)*)"|\'((?:[^\'\\\\\r\n]|\\\\.)*)\'/')) {
            $result = $str[0][0] == '"' ? $str[1] : $str[2];

            return new ILess_Node_Quoted($str[0], $result, $e, $index, $this->env->currentFileInfo);
        }
    }

    /**
     * Parses an unicode descriptor, as is used in unicode-range U+0?? or U+00A1-00A9
     *
     * @return ILess_Node_UnicodeDescriptor|null
     */
    protected function parseUnicodeDescriptor()
    {
        if ($ud = $this->matchReg('/\\G(U\+[0-9a-fA-F?]+)(\-[0-9a-fA-F?]+)?/')) {
            return new ILess_Node_UnicodeDescriptor($ud[0]);
        }
    }

    /**
     * A catch-all word, such as: `black border-collapse`
     *
     * @return ILess_Node_Color|ILess_Node_Keyword
     */
    protected function parseEntitiesKeyword()
    {
        if ($k = $this->matchReg('/\\G[_A-Za-z-][_A-Za-z0-9-]*/')) {
            // detected named color and "transparent" keyword
            if ($color = ILess_Color::fromKeyword($k)) {
                return new ILess_Node_Color($color);
            } else {
                return new ILess_Node_Keyword($k);
            }
        }
    }

    /**
     * Parses url() tokens
     *
     * @return ILess_Node_Url|null
     */
    protected function parseEntitiesUrl()
    {
        if ($this->input[$this->position] !== 'u' || !$this->matchReg('/\\Gurl\(/')) {
            return;
        }

        $value = $this->match(array(
            'parseEntitiesQuoted',
            'parseEntitiesVariable',
            '/\\G(?:(?:\\\\[\(\)\'"])|[^\(\)\'"])+/',
        ));

        if (!$value) {
            $value = '';
        }

        $this->expect(')');

        return new ILess_Node_Url((isset($value->value) || $value instanceof ILess_Node_Variable) ? $value : new ILess_Node_Anonymous($value), $this->env->currentFileInfo);
    }

    /**
     * Parses a function call
     *
     * @return ILess_Node_Call|null
     */
    protected function parseEntitiesCall()
    {
        $index = $this->position;

        if (!preg_match('/\\G([\w-]+|%|progid:[\w\.]+)\(/', $this->input, $name, 0, $this->position)) {
            return;
        }

        $name = $name[1];
        $nameLC = strtolower($name);

        if ($nameLC === 'url') {
            return null;
        } else {
            $this->position += strlen($name);
        }

        if ($nameLC === 'alpha') {
            if ($alpha = $this->parseAlpha()) {
                return $alpha;
            }
        }

        // Parse the '(' and consume whitespace.
        $this->matchChar('(');
        $args = $this->parseEntitiesArguments();

        if (!$this->matchChar(')')) {
            return;
        }

        if ($name) {
            return new ILess_Node_Call($name, $args, $index, $this->env->currentFileInfo);
        }
    }

    /**
     * Parse a list of arguments
     *
     * @return array
     */
    protected function parseEntitiesArguments()
    {
        $args = array();
        while ($arg = $this->matchFuncs(array('parseEntitiesAssignment', 'parseExpression'))) {
            $args[] = $arg;
            if (!$this->matchChar(',')) {
                break;
            }
        }

        return $args;
    }

    /**
     * Parses an assignments (argument entities for calls).
     * They are present in ie filter properties as shown below.
     * filter: progid:DXImageTransform.Microsoft.Alpha( *opacity=50* )
     *
     * @return ILess_Node_Assignment|null
     */
    protected function parseEntitiesAssignment()
    {
        if (($key = $this->matchReg('/\\G\w+(?=\s?=)/')) && $this->matchChar('=') && ($value = $this->parseEntity())) {
            return new ILess_Node_Assignment($key, $value);
        }
    }

    /**
     * Parses an expression. Expressions either represent mathematical operations,
     * or white-space delimited entities like: `1px solid black`, `@var * 2`
     *
     * @return ILess_Node_Expression|null
     */
    protected function parseExpression()
    {
        $entities = array();
        while ($e = $this->matchFuncs(array('parseAddition', 'parseEntity'))) {
            $entities[] = $e;
            // operations do not allow keyword "/" dimension (e.g. small/20px) so we support that here
            if (!$this->peekReg('/\\G\/[\/*]/') && ($delim = $this->matchChar('/'))) {
                $entities[] = new ILess_Node_Anonymous($delim);
            }
        }
        if (count($entities) > 0) {
            return new ILess_Node_Expression($entities);
        }
    }

    /**
     * Parses IE's alpha function `alpha(opacity=88)`
     *
     * @return ILess_Node_Alpha|null
     */
    protected function parseAlpha()
    {
        if (!$this->matchString('(opacity=')) {
            return;
        }

        $value = $this->matchReg('/\\G[0-9]+/');
        if ($value === null) {
            $value = $this->parseEntitiesVariable();
        }

        if ($value !== null) {
            $this->expect(')');

            return new ILess_Node_Alpha($value);
        }
    }

    /**
     * Parses a javascript code
     *
     * @return ILess_Node_Javascript|null
     */
    protected function parseEntitiesJavascript()
    {
        $e = false;
        $offset = 0;
        if ($this->peekChar('~')) {
            $e = true;
            $offset++;
        }
        if (!$this->peekChar('`', $offset)) {
            return;
        }
        if ($e) {
            $this->matchChar('~');
        }
        if ($str = $this->matchReg('/\\G`([^`]*)`/')) {
            return new ILess_Node_Javascript($str[1], $this->position, $e);
        }
    }

    /**
     * Parses a @import directive
     *
     * @return ILess_Node_Import|null
     */
    protected function parseImport()
    {
        $this->save();

        $dir = $this->matchString('@import');
        $options = array();
        if ($dir) {
            $options = $this->parseImportOptions();
            if (!$options) {
                $options = array();
            }
        }

        if ($dir && ($path = $this->matchFuncs(array('parseEntitiesQuoted', 'parseEntitiesUrl')))) {
            $features = $this->parseMediaFeatures();
            if ($this->matchChar(';')) {
                if ($features) {
                    $features = new ILess_Node_Value($features);
                }

                return new ILess_Node_Import($path, $features, $options, $this->savedPosition, $this->env->currentFileInfo);
            }
        }

        $this->restore();
    }

    /**
     * Parses import options
     *
     * @return array
     */
    protected function parseImportOptions()
    {
        $options = array();
        // list of options, surrounded by parens
        if (!$this->matchChar('(')) {
            return null;
        }
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
                if (!$this->matchChar(',')) {
                    break;
                }
            }
        } while ($o);
        $this->expect(')');

        return $options;
    }

    /**
     * Parses import option
     *
     * @return string
     */
    protected function parseImportOption()
    {
        if (($opt = $this->matchReg('/\\G(less|css|multiple|once|inline|reference)/'))) {
            return $opt[1];
        }
    }

    /**
     * Parses media block
     *
     * @return ILess_Node_Media|null
     */
    protected function parseMedia()
    {
        $debugInfo = null;
        if ($this->env->dumpLineNumbers) {
            $debugInfo = $this->getDebugInfo($this->position, $this->input, $this->env);
        }

        if ($this->matchReg('/\\G@media/')) {
            $features = $this->parseMediaFeatures();
            if ($rules = $this->parseBlock()) {
                $media = new ILess_Node_Media($rules, $features, $this->position, $this->env->currentFileInfo);
                if ($debugInfo) {
                    $media->debugInfo = $debugInfo;
                }

                return $media;
            }
        }
    }

    /**
     * Parses media features
     *
     * @return array
     */
    protected function parseMediaFeatures()
    {
        $features = array();
        do {
            if ($e = $this->parseMediaFeature()) {
                $features[] = $e;
                if (!$this->matchChar(',')) {
                    break;
                }
            } elseif ($e = $this->parseEntitiesVariable()) {
                $features[] = $e;
                if (!$this->matchChar(',')) {
                    break;
                }
            }
        } while ($e);

        return $features ? $features : null;
    }


    /**
     * Parses single media feature
     *
     * @return null|ILess_Node_Expression
     */
    protected function parseMediaFeature()
    {
        $nodes = array();
        do {
            if ($e = $this->matchFuncs(array('parseEntitiesKeyword', 'parseEntitiesVariable'))) {
                $nodes[] = $e;
            } elseif ($this->matchChar('(')) {
                $p = $this->parseProperty();
                $e = $this->parseValue();
                if ($this->matchChar(')')) {
                    if ($p && $e) {
                        $nodes[] = new ILess_Node_Paren(new ILess_Node_Rule($p, $e, null, null, $this->position, $this->env->currentFileInfo, true));
                    } elseif ($e) {
                        $nodes[] = new ILess_Node_Paren($e);
                    } else {
                        return null;
                    }
                } else {
                    return null;
                }
            }
        } while ($e);

        if ($nodes) {
            return new ILess_Node_Expression($nodes);
        }
    }

    /**
     * Parses the property
     *
     * @return string
     */
    protected function parseProperty()
    {
        if ($name = $this->matchReg('/\\G(\*?-?[_a-zA-Z0-9-]+)\s*:/')) {
            return $name[1];
        }
    }

    /**
     * Parses a rule terminator. Note that we use `peekChar()` to check for '}',
     * because the `block` rule will be expecting it, but we still need to make sure
     * it's there, if ';' was ommitted.
     *
     * @return string
     */
    protected function parseEnd()
    {
        return ($end = $this->matchChar(';')) ? $end : $this->peekChar('}');
    }

    /**
     * Returns the environment
     *
     * @return ILess_Environment
     */
    public function getEnvironment()
    {
        return $this->env;
    }

    /**
     * Set the current parser environment
     *
     * @param ILess_Environment $env
     * @return self
     */
    public function setEnvironment(ILess_Environment $env)
    {
        $this->env = $env;

        return $this;
    }

    /**
     * Returns the importer
     *
     * @return ILess_Importer
     */
    public function getImporter()
    {
        return $this->importer;
    }

    /**
     * Set the importer
     *
     * @param ILess_Importer $importer
     * @return self
     */
    public function setImporter(ILess_Importer $importer)
    {
        $this->importer = $importer;

        return $this;
    }

    /**
     * Peeks a character in the given offset
     *
     * @param string $token The token to look
     * @param integer $offset The offset
     * @return boolean
     */
    protected function peekChar($tok, $offset = 0)
    {
        $offset += $this->position;

        return ($offset < $this->length) && ($this->input[$offset] === $tok);
    }

    /**
     * Peeks a regular expresion. Does not move the position of the parser.
     *
     * @param string $regexp The regular expression
     * @return boolean
     */
    protected function peekReg($regexp)
    {
        return preg_match($regexp, $this->input, $match, 0, $this->position);
    }

    /**
     * Parse from a token, regexp or string, and move forward if match
     *
     * @param array|string $token The token
     * @return null|boolean|object
     */
    protected function match($token)
    {
        if (!is_array($token)) {
            $token = array($token);
        }

        foreach ($token as $t) {
            if (strlen($t) === 1) {
                $match = $this->matchChar($t);
            } elseif ($t[0] !== '/') {
                // Non-terminal, match using a function call
                $match = $this->$t();
            } else {
                $match = $this->matchReg($t);
            }

            if ($match) {
                return $match;
            }
        }
    }

    /**
     * Matches given functions. Returns the result of the first which returns
     * any non null value.
     *
     * @param array The array of functions to call
     * @throws InvalidArgumentException If the function does not exist
     * @return ILess_Node
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
     * Matches a regular expression from the current start point
     *
     * @param string $regexp The regular expression
     * @return string The matched string
     */
    protected function matchReg($regexp)
    {
        if (preg_match($regexp, $this->input, $match, 0, $this->position)) {
            $this->skipWhitespace(strlen($match[0]));

            return count($match) === 1 ? $match[0] : $match;
        }
    }

    /**
     * Matches a single character in the input
     *
     * @param string $char
     * @return string
     */
    protected function matchChar($char)
    {
        if (($this->position < $this->length) && ($this->input[$this->position] === $char)) {
            $this->skipWhitespace(1);

            return $char;
        }
    }

    /**
     * Matches a string
     *
     * @param string $string The string to match
     * @return string|null
     */
    protected function matchString($string)
    {
        $len = strlen($string);
        if (($this->length >= ($this->position + $len))
            && substr_compare($this->input, $string, $this->position, $len, true) === 0
        ) {
            $this->skipWhitespace($len);

            return $string;
        }
    }

    /**
     * Expects a string to be present at the current position
     *
     * @param string $token The single character, regular expression
     * @param string $message The error message for the exception
     * @return string
     * @throws ILess_Exception_Parser If the expected token does not match
     */
    protected function expect($token, $message = null)
    {
        $result = $this->match($token);
        if (!$result) {
            throw new ILess_Exception_Compiler(
                $message ? $message :
                    sprintf('Expected \'%s\' got \'%s\'', $token, $this->input[$this->position]),
                    $this->position, $this->env->currentFileInfo);
        } else {
            return $result;
        }
    }

    /**
     * Skips whitespace
     *
     * @param integer $length The length to skip
     */
    protected function skipWhitespace($length)
    {
        $this->position += $length;
        $this->position += strspn($this->input, "\n\r\t ", $this->position);
    }

    /**
     * Is the character at the offset a whitespace character?
     *
     * @param integer $offset
     * @return boolean
     */
    protected function isWhitespace($offset = 0)
    {
        return ctype_space($this->input[$this->position + $offset]);
    }

    /**
     * Skip semicolons ";"
     *
     * @return boolean
     */
    protected function skipSemicolons()
    {
        $length = strspn($this->input, ';', $this->position);

        if ($length) {
            $this->skipWhitespace($length);

            return true;
        }
    }

    /**
     * Saves current position
     *
     * @return ILess_Parser
     */
    protected function save()
    {
        $this->savedPosition = $this->position;

        return $this;
    }

    /**
     * Restores the position
     *
     * @return ILess_Parser
     */
    protected function restore()
    {
        $this->position = $this->savedPosition;

        return $this;
    }

    /**
     * Returns the debug information
     *
     * @param integer $index The index
     * @param string $input The input
     * @param ILess_Environment $env The environment
     * @return ILess_DebugInfo
     */
    protected function getDebugInfo($index, $input, ILess_Environment $env)
    {
        list($lineNumber) = ILess_Util::getLocation($input, $index);

        return new ILess_DebugInfo($env->currentFileInfo->filename, $lineNumber);
    }

}

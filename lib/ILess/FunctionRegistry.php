<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess;

use Exception;
use ILess\Exception\CompilerException;
use ILess\Exception\FunctionException;
use ILess\Exception\IOException;
use ILess\Node\AnonymousNode;
use ILess\Node\ColorNode;
use ILess\Node\CommentNode;
use ILess\Node\DimensionNode;
use ILess\Node\ExpressionNode;
use ILess\Node\KeywordNode;
use ILess\Node\UrlNode;
use ILess\Node\UnitNode;
use ILess\Node\ToColorConvertibleInterface;
use ILess\Node\QuotedNode;
use ILess\Node\OperationNode;
use ILess\Util\Mime;
use InvalidArgumentException;
use RuntimeException;

/**
 * Function registry.
 *
 * @see http://lesscss.org/#reference
 */
class FunctionRegistry
{
    /**
     * Maximum allowed size of data uri for IE8.
     */
    const IE8_DATA_URI_MAX = 32768;

    /**
     * Less environment.
     *
     * @var Context
     */
    protected $context;

    /**
     * Array of function aliases.
     *
     * @var array
     */
    protected $aliases = [
        '%' => 'template',
        'data-uri' => 'dataUri',
        'get-unit' => 'getunit',
        'svg-gradient' => 'svggradient',
        'default' => 'defaultFunc',
        'image-size' => 'imageSize',
        'image-width' => 'imageWidth',
        'image-height' => 'imageHeight',
    ];

    /**
     * @var FileInfo
     */
    protected $currentFileInfo;

    /**
     * @var FunctionRegistry
     */
    private $parent;

    /**
     * Array of callable functions.
     *
     * @var array
     */
    private $functions = [
        'abs' => true,
        'acos' => true,
        'alpha' => true,
        'argb' => true,
        'asin' => true,
        'atan' => true,
        'average' => true,
        'blue' => true,
        'call' => true,
        'ceil' => true,
        'clamp' => true,
        'color' => true,
        'contrast' => true,
        'convert' => true,
        'cos' => true,
        'darken' => true,
        'dataUri' => true,
        'desaturate' => true,
        'difference' => true,
        'e' => true,
        'escape' => true,
        'exclusion' => true,
        'extract' => true,
        'fade' => true,
        'fadein' => true,
        'fadeout' => true,
        'floor' => true,
        'green' => true,
        'greyscale' => true,
        'hardlight' => true,
        'hsl' => true,
        'hsla' => true,
        'hsv' => true,
        'hsva' => true,
        'hsvhue' => true,
        'hsvsaturation' => true,
        'hsvvalue' => true,
        'hue' => true,
        'iscolor' => true,
        'isem' => true,
        'iskeyword' => true,
        'isnumber' => true,
        'ispercentage' => true,
        'ispixel' => true,
        'isstring' => true,
        'isunit' => true,
        'isurl' => true,
        'isruleset' => true,
        'length' => true,
        'lighten' => true,
        'lightness' => true,
        'luma' => true,
        'luminance' => true,
        'max' => true,
        'min' => true,
        'mix' => true,
        'mod' => true,
        'multiply' => true,
        'negation' => true,
        'number' => true,
        'overlay' => true,
        'percentage' => true,
        'pi' => true,
        'pow' => true,
        'red' => true,
        'replace' => true,
        'rgb' => true,
        'rgba' => true,
        'round' => true,
        'saturate' => true,
        'saturation' => true,
        'screen' => true,
        'shade' => true,
        'sin' => true,
        'softlight' => true,
        'spin' => true,
        'sqrt' => true,
        'svggradient' => true,
        'tan' => true,
        'template' => true,
        'tint' => true,
        'unit' => true,
        'getunit' => true,
        'defaultFunc' => true,
        'imageSize' => true,
        'imageWidth' => true,
        'imageHeight' => true,
    ];

    /**
     * Constructor.
     *
     * @param array $aliases Array of function aliases in the format array(alias => function)
     * @param Context $context The context
     */
    public function __construct($aliases = [], Context $context = null)
    {
        $this->context = $context;
        $this->addAliases($aliases);
    }

    /**
     * @param FileInfo $file
     *
     * @return $this
     */
    public function setCurrentFile(FileInfo $file)
    {
        $this->currentFileInfo = $file;

        return $this;
    }

    /**
     * Adds a function to the functions.
     *
     * @param string $functionName
     * @param callable $callable
     * @param string|array $aliases The array of aliases
     *
     * @return FunctionRegistry
     *
     * @throws InvalidArgumentException If the callable is not valid
     */
    public function addFunction($functionName, $callable, $aliases = [])
    {
        if (!is_callable($callable, null, $callableName)) {
            throw new InvalidArgumentException(
                sprintf('The callable "%s" for function "%s" is not valid.', $callableName, $functionName)
            );
        }

        // all functions are case insensitive
        $functionName = strtolower($functionName);

        $this->functions[$functionName] = $callable;

        if (!is_array($aliases)) {
            $aliases = [$aliases];
        }

        foreach ($aliases as $alias) {
            $this->addAlias($alias, $functionName);
        }

        return $this;
    }

    /**
     * Adds functions.
     *
     * <pre>
     * $registry->addFunctions(array('name' => function() {}));
     * $registry->addFunctions(array(array('name' => 'load', 'callable' => 'load'));
     * $registry->addFunctions(array(array('name' => 'load', 'callable' => 'load', 'alias' => 'l'));
     * </pre>
     *
     * @param array $functions Array of functions
     *
     * @return FunctionRegistry
     */
    public function addFunctions(array $functions)
    {
        foreach ($functions as $key => $function) {
            if (is_numeric($key)) {
                $this->addFunction(
                    $function['name'],
                    $function['callable'],
                    isset($function['alias']) ? $function['alias'] : []
                );
            } else {
                // alternative syntax without aliases
                $this->addFunction($key, $function);
            }
        }

        return $this;
    }

    /**
     * Sets the parent registry.
     *
     * @param FunctionRegistry $parent
     *
     * @return $this
     */
    public function setParent(FunctionRegistry $parent)
    {
        // verify
        if ($parent === $this) {
            throw new RuntimeException('Invalid parent registry. The parent is the same object.');
        }

        $this->parent = $parent;

        return $this;
    }

    /**
     * Loads a plugin from given path.
     *
     * @param string $path
     *
     * @return $this
     *
     * @throws RuntimeException
     */
    public function loadPlugin($path)
    {
        if (!is_readable($path)) {
            throw new RuntimeException(
                sprintf('The plugin cannot be loaded. The given file "%s" does not exist or is not readable', $path)
            );
        }

        // FIXME: what about security?
        // FIXME: php syntax check?
        require $path;

        return $this;
    }

    /**
     * Clones the registry and setups the parent.
     *
     * @return FunctionRegistry
     */
    public function inherit()
    {
        $new = clone $this;

        $new->setParent($this);

        return $new;
    }

    /**
     * Returns names of registered functions.
     *
     * @return array
     */
    public function getFunctions()
    {
        return array_keys($this->functions);
    }

    /**
     * Does the function exist?
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasFunction($name)
    {
        $name = strtolower($name);

        return isset($this->functions[$name]) || isset($this->aliases[$name]);
    }

    /**
     * Returns aliases for registered functions.
     *
     * @param bool $withFunctions Include function names?
     *
     * @return array
     */
    public function getAliases($withFunctions = false)
    {
        return $withFunctions ? $this->aliases : array_keys($this->aliases);
    }

    /**
     * Adds a function alias.
     *
     * @param string $alias The alias name
     * @param string $function The function name
     *
     * @return FunctionRegistry
     */
    public function addAlias($alias, $function)
    {
        $functionLower = strtolower($function);
        if (!isset($this->functions[$functionLower])) {
            throw new InvalidArgumentException(
                sprintf('Invalid alias "%s" for "%s" given. The "%s" does not exist.', $alias, $function)
            );
        }
        $this->aliases[strtolower($alias)] = $functionLower;

        return $this;
    }

    /**
     * Adds aliases.
     *
     * @param array $aliases
     *
     * @return FunctionRegistry
     */
    public function addAliases(array $aliases)
    {
        foreach ($aliases as $alias => $function) {
            $this->addAlias($alias, $function);
        }

        return $this;
    }

    /**
     * Sets The context.
     *
     * @param Context $context
     */
    public function setEnvironment(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Returns the context instance.
     *
     * @return mixed
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Calls a method with given arguments.
     *
     * @param string $methodName
     * @param array $arguments
     *
     * @return mixed
     *
     * @throws FunctionException If the method does not exist
     */
    public function call($methodName, $arguments = [])
    {
        $methodName = strtolower($methodName);

        if (isset($this->aliases[$methodName])) {
            $methodName = $this->aliases[$methodName];
        }

        if (isset($this->functions[$methodName])) {
            $arguments = $this->prepareArguments($arguments);

            if ($this->functions[$methodName] === true) {
                // built in function
                return call_user_func_array([$this, $methodName], $arguments);
            } else {
                // this is a external callable
                // provide access to function registry (pass as first parameter)
                array_unshift($arguments, $this);

                return call_user_func_array($this->functions[$methodName], $arguments);
            }
        } else {
            if ($this->parent) {
                return $this->parent->call($methodName, $arguments);
            }
        }
    }

    /**
     * Prepares the arguments before function calls.
     * This does the same as in less.js's functionCaller object.
     *
     * @param array $arguments
     *
     * @return array
     */
    protected function prepareArguments(array $arguments)
    {
        $return = array_filter(
            $arguments,
            function ($item) {
                if ($item instanceof CommentNode) {
                    return false;
                }

                return true;
            }
        );

        $return = array_map(
            function (&$item) {
                if ($item instanceof ExpressionNode) {
                    $subNodes = array_filter(
                        $item->value,
                        function ($i) {
                            if ($i instanceof CommentNode) {
                                return false;
                            }

                            return true;
                        }
                    );
                    if (count($subNodes) === 1) {
                        return $subNodes[0];
                    } else {
                        return new ExpressionNode($subNodes);
                    }
                }

                return $item;
            },
            $return
        );

        return $return;
    }

    /**
     * Applies URL-encoding to special characters found in the input string.
     *
     * * Following characters are exceptions and not encoded: `,, /, ?, @, &, +, ', ~, ! $`
     * * Most common encoded characters are: `<space>`, #, ^, (, ), {, }, |, :, >, <, ;, ], [ =`
     *
     * @param Node $string A string to escape
     *
     * @return AnonymousNode Escaped string content without quotes.
     */
    public function escape(Node $string)
    {
        return new AnonymousNode(urlencode((string) $string));
    }

    /**
     * CSS escaping similar to ~"value" syntax. It expects string as a parameter and return
     * its content as is, but without quotes. It can be used to output CSS value which is either not valid CSS syntax,
     * or uses proprietary syntax which LESS doesn't recognize.
     *
     * @param string $string A string to escape
     *
     * @return AnonymousNode Content without quotes.
     */
    public function e(Node $string)
    {
        return new AnonymousNode(str_replace(['~"', '"'], '', (string) $string));
    }

    /**
     * Returns the number of items.
     *
     * @param Node $values
     *
     * @return DimensionNode
     */
    public function length(Node $values)
    {
        return new DimensionNode(is_array($values->value) ? count($values->value) : 1);
    }

    /**
     * Min.
     *
     * @return DimensionNode
     */
    public function min()
    {
        return $this->doMinmax(true, func_get_args());
    }

    /**
     * Max.
     *
     * @return DimensionNode
     */
    public function max()
    {
        return $this->doMinmax(false, func_get_args());
    }

    /**
     * Extract.
     *
     * @param Node $node
     * @param Node $index
     *
     * @return null|Node
     */
    public function extract(Node $node, Node $index)
    {
        $index = (int) $index->value - 1; // (1-based index)
        $values = $this->getItemsFromNode($node);
        if (isset($values[$index])) {
            return $values[$index];
        }

        return;
    }

    /**
     * @param Node $node
     *
     * @return array
     */
    private function getItemsFromNode(Node $node)
    {
        // handle non-array values as an array of length 1
        // return 'undefined' if index is invalid
        if (is_array($node->value)) {
            $items = $node->value;
        } else {
            $items = [$node];
        }

        // reset array keys!
        return array_values($items);
    }

    /**
     * Formats a string. Less equivalent is: `%`.
     * The first argument is string with placeholders.
     * All placeholders start with percentage symbol % followed by letter s,S,d,D,a, or A.
     * Remaining arguments contain expressions to replace placeholders.
     * If you need to print the percentage symbol, escape it by another percentage %%.*.
     *
     * @param Node $string
     *
     * @return QuotedNode
     */
    public function template(Node $string /* , $value1, $value2, ... */)
    {
        $args = func_get_args();
        array_shift($args);

        $result = $string->value;
        foreach ($args as $arg) {
            if (preg_match('/%[sda]/i', $result, $token)) {
                $token = $token[0];

                if ($arg instanceof QuotedNode && stristr($token, 's')) {
                    $value = $arg->value;
                } else {
                    $value = $arg->toCSS($this->context);
                }

                $value = preg_match('/[A-Z]$/', $token) ? Util::encodeURIComponent($value) : $value;
                $result = preg_replace('/%[sda]/i', $value, $result, 1);
            }
        }
        $result = str_replace('%%', '%', $result);

        return new QuotedNode(
            isset($string->quote) ? $string->quote : '',
            $result,
            isset($string->escaped) ? $string->escaped : true
        );
    }

    /**
     * Replaces a string or regexp pattern within a string.
     *
     * @param Node $string The string to search and replace in.
     * @param Node $pattern A string or regular expression pattern to search for.
     * @param Node $replacement The string to replace the matched pattern with.
     * @param Node $flags (Optional) regular expression flags.
     *
     * @return QuotedNode
     *
     * @see http://lesscss.org/functions/#string-functions-replace
     */
    public function replace(Node $string, Node $pattern, Node $replacement, Node $flags = null)
    {
        $result = $string->value;

        if ($replacement instanceof QuotedNode) {
            $replacement = $replacement->value;
        } else {
            $replacement = $replacement->toCSS($this->context);
        }

        $limit = 1;
        // we have some flags
        if ($flags) {
            $flags = $flags->value;
            // global replacement
            $global = strpos($flags, 'g') !== false;
            // strip js php non compatible flags
            $flags = str_replace('g', '', $flags);
            if ($global) {
                $limit = -1;
            }
        } else {
            $flags = '';
        }

        // we cannot use preg_quote here, since the expression is already quoted in less.js
        $regexp = str_replace('/', '\\/', $pattern->value);
        $result = preg_replace('/' . $regexp . '/' . $flags, $replacement, $result, $limit);

        return new QuotedNode(
            isset($string->quote) ? $string->quote : '',
            $result,
            isset($string->escaped) ? $string->escaped : true
        );
    }

    /**
     * Inlines a resource and falls back to url() if the ieCompat option is on
     * and the resource is too large, or if you use the function in the browser.
     * If the mime is not given then node uses the mime package to determine the correct mime type.
     *
     * @param Node $mimeType A mime type string
     * @param Node $url The URL of the file to inline.
     *
     * @return UrlNode
     *
     * @throws IOException
     */
    public function dataUri(Node $mimeType, Node $filePath = null)
    {
        if (func_num_args() < 2) {
            $path = $mimeType->value;
            $mime = false; // we will detect it later
        } else {
            $path = $filePath->value;
            $mime = $mimeType->value;
        }

        $path = $this->getFilePath($path);
        list($fragment, $path) = Util::getFragmentAndPath($path);

        if ($mime === false) {
            $mime = Mime::lookup($path);
            if ($mime === 'image/svg+xml') {
                $useBase64 = false;
            } else {
                // use base 64 unless it's an ASCII or UTF-8 format
                $charset = Mime::charsetsLookup($mime);
                $useBase64 = !in_array($charset, ['US-ASCII', 'UTF-8']);
            }
            if ($useBase64) {
                $mime .= ';base64';
            }
        } else {
            $useBase64 = (bool) preg_match('/;base64$/', $mime);
        }

        // the file was not found
        // FIXME: warn
        if (!is_readable($path)) {
            $url = new UrlNode(
                ($filePath ? $filePath : $mimeType),
                0, // FIXME: we don't have access to current index here!
                $this->context->currentFileInfo
            );

            return $url->compile($this->context);
        }

        $buffer = file_get_contents($path);
        $buffer = $useBase64 ? base64_encode($buffer) : Util::encodeURIComponent($buffer);

        $uri = 'data:' . $mime . ',' . $buffer . $fragment;

        // IE8 cannot handle a data-uri larger than 32KB. If this is exceeded
        // and the ieCompat option is enabled, return normal url() instead.
        if ($this->context->ieCompat) {
            if (strlen($uri) >= self::IE8_DATA_URI_MAX) {
                // FIXME: warn that we cannot use data uri here
                // FIXME: we don't have access to current index here!
                $url = new UrlNode(($filePath ? $filePath : $mimeType));

                return $url->compile($this->context);
            }
        }

        // FIXME: we don't have any information about current index here!
        return new UrlNode(new QuotedNode('"' . $uri . '"', $uri, false));
    }

    /**
     * Rounds up to an integer.
     *
     * @param Node $number
     *
     * @return DimensionNode
     */
    public function ceil($number)
    {
        return new DimensionNode(ceil($this->number($number)), $number->unit);
    }

    /**
     * Rounds down to an integer.
     *
     * @param Node $number
     *
     * @return Node
     */
    public function floor($number)
    {
        return $this->doMath('floor', $number);
    }

    /**
     * Converts to a %, e.g. 0.5 -> 50%.
     *
     * @param Node $number
     *
     * @return DimensionNode
     */
    public function percentage(Node $number)
    {
        return $this->doMath(function ($n) {
                return $n * 100;
        }, $number, '%');
    }

    /**
     * Rounds a number to a number of places.
     *
     * @param string|Node $number The number to round
     * @param int $places The precision
     *
     * @return DimensionNode
     */
    public function round($number, DimensionNode $places = null)
    {
        if ($number instanceof DimensionNode) {
            $unit = $number->unit;
            $number = $number->value;
        } else {
            $unit = null;
        }

        $rounded = round(floatval($number), $places ? $places->value : 0);

        return new DimensionNode($rounded, $unit);
    }

    /**
     * Calculates square root of a number.
     *
     * @param mixed $number
     *
     * @return mixed
     */
    public function sqrt($number)
    {
        return $this->doMath('sqrt', $number);
    }

    /**
     * Absolute value of a number.
     *
     * @param mixed $number The number
     *
     * @return mixed
     */
    public function abs($number)
    {
        return $this->doMath('abs', $number);
    }

    /**
     * Sine function.
     *
     * @param string $number The number
     *
     * @return mixed
     */
    public function sin($number)
    {
        return $this->doMath('sin', $number, '');
    }

    /**
     * Arcsine - inverse of sine function.
     *
     * @param string $number
     *
     * @return mixed
     */
    public function asin($number)
    {
        return $this->doMath('asin', $number, 'rad');
    }

    /**
     * Cosine function.
     *
     * @param string $number
     *
     * @return mixed
     */
    public function cos($number)
    {
        return $this->doMath('cos', $number, '');
    }

    /**
     * Arc cosine - inverse of cosine function.
     *
     * @param string $number
     *
     * @return mixed
     */
    public function acos($number)
    {
        return $this->doMath('acos', $number, 'rad');
    }

    /**
     * Tangent function.
     *
     * @param mixed $number
     *
     * @return mixed
     */
    public function tan($number)
    {
        return $this->doMath('tan', $number, '');
    }

    /**
     * Arc tangent - inverse of tangent function.
     *
     * @param mixed $number
     *
     * @return mixed
     */
    public function atan($number)
    {
        return $this->doMath('atan', $number, 'rad');
    }

    /**
     * Does the math using Math.
     *
     * @param string $func The math function like sqrt, floor...
     * @param DimensionNode|int $number The number
     * @param UnitNode|string $unit The unit
     * @param mixed ...$argument1 Argument for the mathematical function
     * @param mixed ...$argument2 Argument for the mathematical function
     *
     * @return mixed
     *
     * @throws CompilerException
     */
    protected function doMath($func, $number, $unit = null /*, $arguments...*/)
    {
        $arguments = [];
        if (func_num_args() > 3) {
            foreach (func_get_args() as $i => $arg) {
                // skip first 3 arguments
                if ($i < 3) {
                    continue;
                }
                $arguments[] = $arg;
            }
        }

        if ($number instanceof DimensionNode) {
            if ($unit === null) {
                $unit = $number->unit;
            } else {
                $number = $number->unify();
            }
            $number = floatval($number->value);

            array_unshift($arguments, $number);

            return new DimensionNode(call_user_func_array($func, $arguments), $unit);
        } elseif (is_numeric($number)) {
            array_unshift($arguments, $number);

            return call_user_func_array($func, $arguments);
        }

        throw new CompilerException('argument must be a number');
    }

    /**
     * Returns pi.
     *
     * @return DimensionNode
     */
    public function pi()
    {
        return new DimensionNode(M_PI);
    }

    /**
     * First argument raised to the power of the second argument.
     *
     * @param Node $number
     * @param Node $exponent
     *
     * @throws CompilerException
     *
     * @return DimensionNode
     */
    public function pow($number, $exponent)
    {
        if (is_numeric($number) && is_numeric($exponent)) {
            $number = new DimensionNode($number);
            $exponent = new DimensionNode($exponent);
        } elseif (!($number instanceof DimensionNode)
            || !($exponent instanceof DimensionNode)
        ) {
            throw new CompilerException('Arguments must be numbers.');
        }

        return new DimensionNode(pow($number->value, $exponent->value), $number->unit);
    }

    /**
     * First argument modulus second argument.
     *
     * @param DimensionNode $number1
     * @param DimensionNode $number2
     *
     * @return DimensionNode
     */
    public function mod(DimensionNode $number1, DimensionNode $number2)
    {
        return new DimensionNode($number1->value % $number2->value, $number1->unit);
    }

    /**
     * Converts between number types.
     *
     * @param DimensionNode $number
     * @param Node $units
     *
     * @return DimensionNode|null
     */
    public function convert(Node $number, Node $units)
    {
        if (!$number instanceof DimensionNode) {
            return;
        }

        return $number->convertTo($units->value);
    }

    /**
     * Changes number units without converting it.
     *
     * @param Node $number The dimension
     * @param Node $unit The unit
     *
     * @throws CompilerException
     *
     * @return DimensionNode
     */
    public function unit(Node $number, Node $unit = null)
    {
        if (!$number instanceof DimensionNode) {
            throw new CompilerException(
                sprintf(
                    'The first argument to unit must be a number%s',
                    ($number instanceof OperationNode ? '. Have you forgotten parenthesis?' : '.')
                )
            );
        }

        if ($unit) {
            if ($unit instanceof KeywordNode) {
                $unit = $unit->value;
            } else {
                $unit = $unit->toCSS($this->context);
            }
        } else {
            $unit = '';
        }

        return new DimensionNode($number->value, $unit);
    }

    /**
     * Returns units of a number.
     *
     * @param DimensionNode $node
     *
     * @return AnonymousNode
     *
     * @see http://lesscss.org/functions/#misc-functions-get-unit
     */
    public function getunit(DimensionNode $node)
    {
        return new AnonymousNode($node->unit);
    }

    /**
     * Converts string or escaped value into color.
     *
     * @param Node $string
     *
     * @throws CompilerException
     * @returns ColorNode
     */
    public function color(Node $string)
    {
        if ($string instanceof QuotedNode &&
            preg_match('/^#([a-f0-9]{6}|[a-f0-9]{3})$/i', $string->value)
        ) {
            return new ColorNode(substr($string->value, 1));
        }

        if ($string instanceof ColorNode) {
            // remove keyword, so the color is not output as `plum` but in hex code
            $string->value->keyword = null;

            return $string;
        } else {
            if (Color::isNamedColor($string->value)) {
                return new ColorNode(Color::color($string->value));
            }
        }

        throw new CompilerException('Argument must be a color keyword or 3/6 digit hex e.g. #FFF');
    }

    /**
     * Converts to a color.
     *
     * @param Node|int $red The red component of a color
     * @param Node|int $green The green component of a color
     * @param Node|int $blue The blue component of a color
     *
     * @return ColorNode
     */
    public function rgb($red, $green, $blue)
    {
        return $this->rgba($red, $green, $blue, 1);
    }

    /**
     * Converts to a color.
     *
     * @param Node $red The red component of a color
     * @param Node $green The green component of a color
     * @param Node $blue The blue component of a color
     * @param Node|float $alpha The alpha channel
     *
     * @return ColorNode
     */
    public function rgba($red, $green, $blue, $alpha)
    {
        $rgb = array_map([$this, 'scaled'], [$red, $green, $blue]);

        return new ColorNode($rgb, $this->number($alpha));
    }

    /**
     * Scales the number to percentage.
     *
     * @param DimensionNode $n The number
     * @param int $size
     *
     * @return float
     */
    protected function scaled($n, $size = 255)
    {
        if ($n instanceof DimensionNode && $n->unit->is('%')) {
            return $n->value * $size / 100;
        } else {
            return $this->number($n);
        }
    }

    /**
     * Converts the $number to "real" number.
     *
     * @param DimensionNode|int|float $number
     *
     * @return float
     *
     * @throws InvalidArgumentException
     */
    public function number($number)
    {
        if ($number instanceof DimensionNode) {
            return $number->unit->is('%') ? $number->value / 100 : $number->value;
        } elseif (is_numeric($number)) {
            return $number;
        } else {
            throw new InvalidArgumentException(
                sprintf('Color functions take numbers as parameters. "%s" given.', gettype($number))
            );
        }
    }

    /**
     * Creates a `#AARRGGBB`.
     *
     * @param Color $color The color
     *
     * @return AnonymousNode
     */
    public function argb(Node $color)
    {
        if (!$color instanceof ColorNode) {
            return $color;
        }

        return new AnonymousNode($color->toARGB());
    }

    /**
     * Creates a color.
     *
     * @param Node $hue The hue
     * @param Node $saturation The saturation
     * @param Node $lightness The lightness
     *
     * @return ColorNode
     */
    public function hsl($hue, $saturation, $lightness)
    {
        return $this->hsla($hue, $saturation, $lightness, 1);
    }

    /**
     * Creates a color from hsla color namespace.
     *
     * @param mixed $hue
     * @param mixed $saturation
     * @param mixed $lightness
     * @param mixed $alpha
     *
     * @return ColorNode
     */
    public function hsla($hue, $saturation, $lightness, $alpha)
    {
        $hue = fmod($this->number($hue), 360) / 360; // Classic % operator will change float to int
        $saturation = $this->clamp($this->number($saturation));
        $lightness = $this->clamp($this->number($lightness));
        $alpha = $this->clamp($this->number($alpha));

        $m2 = $lightness <= 0.5 ? $lightness * ($saturation + 1) : $lightness + $saturation - $lightness * $saturation;
        $m1 = $lightness * 2 - $m2;

        return $this->rgba(
            $this->hslaHue($hue + 1 / 3, $m1, $m2) * 255,
            $this->hslaHue($hue, $m1, $m2) * 255,
            $this->hslaHue($hue - 1 / 3, $m1, $m2) * 255,
            $alpha
        );
    }

    /**
     * Helper for hsla().
     *
     * @param float $h
     * @param float $m1
     * @param float $m2
     *
     * @return float
     */
    protected function hslaHue($h, $m1, $m2)
    {
        $h = $h < 0 ? $h + 1 : ($h > 1 ? $h - 1 : $h);
        if ($h * 6 < 1) {
            return $m1 + ($m2 - $m1) * $h * 6;
        } elseif ($h * 2 < 1) {
            return $m2;
        } elseif ($h * 3 < 2) {
            return $m1 + ($m2 - $m1) * (2 / 3 - $h) * 6;
        }

        return $m1;
    }

    /**
     * Creates a color.
     *
     * @param int $hue The hue
     * @param int $saturation The saturation
     * @param int $value The value
     */
    public function hsv($hue, $saturation, $value)
    {
        return $this->hsva($hue, $saturation, $value, 1);
    }

    /**
     * Creates a color.
     *
     * @param int $hue The hue
     * @param int $saturation The saturation
     * @param int $value The value
     * @param int $alpha The alpha channel
     */
    public function hsva($hue, $saturation, $value, $alpha)
    {
        $hue = (($this->number($hue) % 360) / 360) * 360;
        $saturation = $this->number($saturation);
        $value = $this->number($value);
        $alpha = $this->number($alpha);

        $i = floor(($hue / 60) % 6);
        $f = ($hue / 60) - $i;

        $vs = [
            $value,
            $value * (1 - $saturation),
            $value * (1 - $f * $saturation),
            $value * (1 - (1 - $f) * $saturation),
        ];

        $perm = [
            [0, 3, 1],
            [2, 0, 1],
            [1, 0, 3],
            [1, 2, 0],
            [3, 1, 0],
            [0, 1, 2],
        ];

        return $this->rgba(
            $vs[$perm[$i][0]] * 255,
            $vs[$perm[$i][1]] * 255,
            $vs[$perm[$i][2]] * 255,
            $alpha
        );
    }

    /**
     * Returns the `hue` channel of the $color in the HSL space.
     *
     * @param ColorNode $color
     *
     * @return DimensionNode
     */
    public function hue(ColorNode $color)
    {
        return $color->getHue();
    }

    /**
     * Returns the `saturation` channel of the $color in the HSL space.
     *
     * @param ColorNode $color
     *
     * @return DimensionNode
     */
    public function saturation(ColorNode $color)
    {
        return $color->getSaturation();
    }

    /**
     * Returns the 'lightness' channel of @color in the HSL space.
     *
     * @param ColorNode $color
     *
     * @return DimensionNode
     */
    public function lightness(ColorNode $color)
    {
        return $color->getLightness();
    }

    /**
     * Returns the `hue` channel of @color in the HSV space.
     *
     * @param ColorNode $color
     *
     * @return string
     */
    public function hsvhue(Node $color)
    {
        if (!$color instanceof ColorNode) {
            return $color;
        }
        $hsv = $color->toHSV();

        return new DimensionNode(Math::round($hsv['h']));
    }

    /**
     * Returns the `saturation` channel of @color in the HSV space.
     *
     * @param ColorNode $color
     *
     * @return string
     */
    public function hsvsaturation(Node $color)
    {
        if (!$color instanceof ColorNode) {
            return $color;
        }
        $hsv = $color->toHSV();

        return new DimensionNode(Math::round($hsv['s'] * 100), '%');
    }

    /**
     * Returns the 'value' channel of @color in the HSV space.
     *
     * @param ColorNode $color
     *
     * @return string
     */
    public function hsvvalue(Node $color)
    {
        if (!$color instanceof ColorNode) {
            return $color;
        }
        $hsv = $color->toHSV();

        return new DimensionNode(Math::round($hsv['v'] * 100), '%');
    }

    /**
     * Returns the 'red' channel of @color.
     *
     * @param ColorNode $color
     *
     * @return string
     */
    public function red(ColorNode $color)
    {
        return $color->getRed();
    }

    /**
     * Returns the 'green' channel of @color.
     *
     * @param ColorNode $color
     *
     * @return string
     */
    public function green(ColorNode $color)
    {
        return $color->getGreen();
    }

    /**
     * Returns the 'blue' channel of @color.
     *
     * @param ColorNode $color
     *
     * @return DimensionNode
     */
    public function blue(ColorNode $color)
    {
        return $color->getBlue();
    }

    /**
     * Returns the 'alpha' channel of the $color.
     *
     * @param ColorNode $color The color
     *
     * @return DimensionNode
     */
    public function alpha(Node $color)
    {
        if (!$color instanceof ColorNode) {
            return $color;
        }

        return $color->getAlpha();
    }

    /**
     * Returns the 'luma' value (perceptual brightness) of the $color.
     *
     * @param ColorNode $color
     *
     * @return DimensionNode
     */
    public function luma(ColorNode $color)
    {
        return $color->getLuma();
    }

    /**
     * Returns the luminance of the color.
     *
     * @param ColorNode $color
     *
     * @return DimensionNode
     */
    public function luminance(ColorNode $color)
    {
        return $color->getLuminance();
    }

    /**
     * Return a color 10% points *more* saturated.
     *
     * @param ColorNode $color
     * @param Node $percentage The percentage
     * @param Node $method The color method
     *
     * @throws InvalidArgumentException
     *
     * @return ColorNode*
     */
    public function saturate(Node $color, Node $percentage = null, Node $method = null)
    {
        // filter: saturate(3.2);
        // should be kept as is, so check for color
        if ($color instanceof DimensionNode) {
            return;
        }

        $color = $this->getColorNode($color, 'Cannot saturate the color');

        $hsl = $color->toHSL();
        $percentage = $percentage ? $percentage->value / 100 : 10;

        // relative
        if ($method && $method->value === 'relative') {
            $hsl['s'] += $hsl['s'] * $percentage;
        } else {
            $hsl['s'] += $percentage;
        }

        return $this->hsla($hsl['h'], $hsl['s'], $hsl['l'], $color->getAlpha());
    }

    /**
     * Return a color 10% points *less* saturated.
     *
     * @param ColorNode $color
     * @param Node $percentage The percentage
     * @param Node $method The color method
     *
     * @throws InvalidArgumentException
     *
     * @return ColorNode
     */
    public function desaturate(ColorNode $color, Node $percentage = null, Node $method = null)
    {
        $color = $this->getColorNode($color, 'Cannot desaturate the color');

        $hsl = $color->toHSL();
        $percentage = $percentage ? $percentage->value / 100 : 10;

        // relative
        if ($method && $method->value === 'relative') {
            $hsl['s'] -= $hsl['s'] * $percentage;
        } else {
            $hsl['s'] -= $percentage;
        }

        return $this->hsla($hsl['h'], $hsl['s'], $hsl['l'], $color->getAlpha());
    }

    /**
     * Return a color 10% points *lighter*.
     *
     * @param Node $color
     * @param Node $percentage The percentage (Default to 10%)
     * @param Node $method The color method
     *
     * @throws InvalidArgumentException
     *
     * @return ColorNode
     */
    public function lighten(Node $color, Node $percentage = null, Node $method = null)
    {
        $color = $this->getColorNode($color, 'Cannot lighten the color');
        $hsl = $color->toHSL();
        $percentage = $percentage ? $percentage->value / 100 : 10;

        // relative
        if ($method && $method->value === 'relative') {
            $hsl['l'] += $hsl['l'] * $percentage;
        } else {
            $hsl['l'] += $percentage;
        }

        return $this->hsla($hsl['h'], $hsl['s'], $hsl['l'], $color->getAlpha());
    }

    /**
     * Return a color 10% points *darker*.
     *
     * @param Node $color
     * @param DimensionNode $percentage The percentage (Default to 10%)
     * @param Node $method The method
     *
     * @return ColorNode
     *
     * @throws InvalidArgumentException
     */
    public function darken(Node $color, DimensionNode $percentage = null, Node $method = null)
    {
        $color = $this->getColorNode($color, 'Cannot darken the color');
        $hsl = $color->toHSL();
        $percentage = $percentage ? $percentage->value / 100 : 10;

        // relative
        if ($method && $method->value === 'relative') {
            $hsl['l'] -= $hsl['l'] * $percentage;
        } else {
            $hsl['l'] -= $percentage;
        }

        return $this->hsla($hsl['h'], $hsl['s'], $hsl['l'], $color->getAlpha());
    }

    /**
     * Return a color 10% points *less* transparent.
     *
     * @param ColorNode $color
     * @param DimensionNode $percentage The percentage (Default to 10%)
     * @param Node $method The method
     *
     * @return ColorNode
     *
     * @throws InvalidArgumentException
     */
    public function fadein(ColorNode $color, DimensionNode $percentage = null, Node $method = null)
    {
        $color = $this->getColorNode($color, 'Cannot fade in the color');
        $hsl = $color->toHSL();
        $percentage = $percentage ? $percentage->value / 100 : 10;

        // relative
        if ($method && $method->value === 'relative') {
            $hsl['a'] += $hsl['a'] * $percentage;
        } else {
            $hsl['a'] += $percentage;
        }

        return $this->hsla($hsl['h'], $hsl['s'], $hsl['l'], $hsl['a']);
    }

    /**
     * Return a color 10% points *more* transparent.
     *
     * @param ColorNode $color
     * @param DimensionNode $percentage The percentage (Default to 10%)
     * @param Node $method The method
     *
     * @return ColorNode
     *
     * @throws InvalidArgumentException
     */
    public function fadeout(ColorNode $color, DimensionNode $percentage = null, Node $method = null)
    {
        $color = $this->getColorNode($color, 'Cannot fade in the color');
        $hsl = $color->toHSL();
        $percentage = $percentage ? $percentage->value / 100 : 10;

        // relative
        if ($method && $method->value === 'relative') {
            $hsl['a'] -= $hsl['a'] * $percentage;
        } else {
            $hsl['a'] -= $percentage;
        }

        return $this->hsla($hsl['h'], $hsl['s'], $hsl['l'], $hsl['a']);
    }

    /**
     * Return $color with 50% transparency.
     *
     * @param ColorNode $color
     * @param DimensionNode $percentage
     *
     * @return string
     */
    public function fade(ColorNode $color, DimensionNode $percentage = null)
    {
        $hsl = $color->toHSL();

        if ($percentage && $percentage->unit->is('%')) {
            $hsl['a'] = $percentage->value / 100;
        } else {
            $hsl['a'] = $percentage ? $percentage->value : 50;
        }

        $hsl['a'] = $this->clamp($hsl['a']);

        return $this->hsla($hsl['h'], $hsl['s'], $hsl['l'], $hsl['a']);
    }

    /**
     * Return a color with a 10 degree larger in hue.
     *
     * @param ColorNode $color
     * @param DimensionNode $degrees
     *
     * @return ColorNode
     */
    public function spin(ColorNode $color, DimensionNode $degrees = null)
    {
        $degrees = $degrees ? $degrees->value : 10;
        $hue = (string) fmod($color->getHue(true) + $degrees, 360);
        $hue = $hue < 0 ? 360 + $hue : $hue;

        return $this->hsla($hue, $color->getSaturation(true), $color->getLightness(true), $color->getAlpha());
    }

    /**
     * Return a mix of $color1 and $color2 with given $weightPercentage (defaults to 50%).
     *
     * @param Node $color1
     * @param Node $color2
     * @param DimensionNode $weightPercentage
     *
     * @return ColorNode
     *
     * @link http://sass-lang.com
     *
     * @copyright 2006-2009 Hampton Catlin, Nathan Weizenbaum, and Chris Eppstein
     */
    public function mix(Node $color1, Node $color2, DimensionNode $weightPercentage = null)
    {
        if (!$color1 instanceof ColorNode) {
            return $color1;
        } elseif (!$color2 instanceof ColorNode) {
            return $color1;
        }

        if (!$weightPercentage) {
            $weightPercentage = new DimensionNode(50);
        }

        $p = $weightPercentage->value / 100.0;
        $w = $p * 2 - 1;
        $a = $color1->getAlpha(true) - $color2->getAlpha(true);

        $w1 = (((($w * $a) == -1) ? $w : ($w + $a) / (1 + $w * $a)) + 1) / 2;
        $w2 = 1 - $w1;

        $color1Rgb = $color1->getRGB();
        $color2Rgb = $color2->getRGB();

        $rgb = [
            $color1Rgb[0] * $w1 + $color2Rgb[0] * $w2,
            $color1Rgb[1] * $w1 + $color2Rgb[1] * $w2,
            $color1Rgb[2] * $w1 + $color2Rgb[2] * $w2,
        ];

        $alpha = $color1->getAlpha(true) * $p + $color2->getAlpha(true) * (1 - $p);

        return new ColorNode($rgb, $alpha);
    }

    /**
     * Return a color mixed 10% with white.
     *
     * @param Node $color
     *
     * @return string
     */
    public function tint(Node $color, Node $percentage = null)
    {
        return $this->mix($this->rgb(255, 255, 255), $color, $percentage);
    }

    /**
     * Return a color mixed 10% with black.
     *
     * @param Node $color
     * @param Node $percentage
     *
     * @return string
     */
    public function shade(Node $color, Node $percentage = null)
    {
        return $this->mix($this->rgb(0, 0, 0), $color, $percentage);
    }

    /**
     * Returns a grey, 100% desaturated color.
     *
     * @param ColorNode $color
     *
     * @return string
     */
    public function greyscale(ColorNode $color)
    {
        return $this->desaturate($color, new DimensionNode(100));
    }

    /**
     * Return @darkColor if @color is > 43% luma otherwise return @lightColor, see notes.
     *
     * @param Node $color
     * @param ColorNode|null $darkColor
     * @param ColorNode|null $lightColor
     * @param DimensionNode|null $thresholdPercentage
     *
     * @return ColorNode
     */
    public function contrast(
        Node $color,
        ColorNode $darkColor = null,
        ColorNode $lightColor = null,
        DimensionNode $thresholdPercentage = null
    ) {
        // ping pong back
        // filter: contrast(3.2);
        // should be kept as is, so check for color
        if (!$color instanceof ColorNode) {
            if ($color instanceof DimensionNode ||
                !$color instanceof ToColorConvertibleInterface
            ) {
                return;
            }
            $color = $color->toColor();
        }

        if (!$lightColor) {
            $lightColor = $this->rgba(255, 255, 255, 1);
        }

        if (!$darkColor) {
            $darkColor = $this->rgba(0, 0, 0, 1);
        }

        //Figure out which is actually light and dark!
        if ($darkColor->getLuma(true) > $lightColor->getLuma(true)) {
            $t = $lightColor;
            $lightColor = $darkColor;
            $darkColor = $t;
        }

        if (!$thresholdPercentage) {
            $thresholdPercentage = 0.43;
        } else {
            $thresholdPercentage = $this->number($thresholdPercentage);
        }

        if (($color->getLuma(true)) < $thresholdPercentage) {
            return $lightColor;
        } else {
            return $darkColor;
        }
    }

    /**
     * Multiplies the $color1 with $color2.
     *
     * @param ColorNode $color1 The first color
     * @param ColorNode $color2 The second color
     *
     * @return ColorNode
     */
    public function multiply(ColorNode $color1, ColorNode $color2)
    {
        return $this->colorBlend([$this, 'colorBlendMultiply'], $color1->getColor(), $color2->getColor());
    }

    /**
     * Screen.
     *
     * @param ColorNode $color1 The first color
     * @param ColorNode $color2 The second color
     *
     * @return ColorNode
     */
    public function screen(ColorNode $color1, ColorNode $color2)
    {
        return $this->colorBlend([$this, 'colorBlendScreen'], $color1->getColor(), $color2->getColor());
    }

    /**
     * Combines the effects of both multiply and screen. Conditionally make light channels lighter
     * and dark channels darker. Note: The results of the conditions are determined by the
     * first color parameter.
     *
     * @param ColorNode $color1 A base color object. Also the determinant color to make the result lighter or darker.
     * @param ColorNode $color2 A color object to overlay.
     *
     * @return ColorNode
     *
     * @see http://lesscss.org/functions/#color-blending-overlay
     */
    public function overlay(ColorNode $color1, ColorNode $color2)
    {
        return $this->colorBlend([$this, 'colorBlendOverlay'], $color1->getColor(), $color2->getColor());
    }

    /**
     * Softlight - Similar to overlay but avoids pure black resulting in pure black,
     * and pure white resulting in pure white.
     *
     * @param ColorNode $color1 The first color
     * @param ColorNode $color2 The second color
     *
     * @return ColorNode
     *
     * @see http://lesscss.org/functions/#color-blending-softlight
     */
    public function softlight(ColorNode $color1, ColorNode $color2)
    {
        return $this->colorBlend([$this, 'colorBlendSoftlight'], $color1->getColor(), $color2->getColor());
    }

    /**
     * Hardlight filter.
     *
     * @param ColorNode $color1 The first color
     * @param ColorNode $color2 The second color
     *
     * @return ColorNode
     */
    public function hardlight(ColorNode $color1, ColorNode $color2)
    {
        return $this->colorBlend([$this, 'colorBlendHardlight'], $color1->getColor(), $color2->getColor());
    }

    /**
     * Difference.
     *
     * @param ColorNode $color1 The first color
     * @param ColorNode $color2 The second color
     *
     * @return ColorNode
     */
    public function difference(ColorNode $color1, ColorNode $color2)
    {
        return $this->colorBlend([$this, 'colorBlendDifference'], $color1->getColor(), $color2->getColor());
    }

    /**
     * Exclusion.
     *
     * @param ColorNode $color1 The first color
     * @param ColorNode $color2 The second color
     *
     * @return ColorNode
     */
    public function exclusion(ColorNode $color1, ColorNode $color2)
    {
        return $this->colorBlend([$this, 'colorBlendExclusion'], $color1->getColor(), $color2->getColor());
    }

    /**
     * Average.
     *
     * @param ColorNode $color1 The first color
     * @param ColorNode $color2 The second color
     *
     * @return ColorNode
     */
    public function average(ColorNode $color1, ColorNode $color2)
    {
        return $this->colorBlend([$this, 'colorBlendAverage'], $color1->getColor(), $color2->getColor());
    }

    /**
     * Negation.
     *
     * @param ColorNode $color1 The first color
     * @param ColorNode $color2 The second color
     *
     * @return ColorNode
     */
    public function negation(ColorNode $color1, ColorNode $color2)
    {
        return $this->colorBlend([$this, 'colorBlendNegation'], $color1->getColor(), $color2->getColor());
    }

    /**
     * Returns true if passed a color, including keyword colors.
     *
     * @param Node $colorOrAnything
     *
     * @return KeywordNode
     */
    public function iscolor(Node $colorOrAnything)
    {
        return $this->isA($colorOrAnything, 'ILess\Node\ColorNode');
    }

    /**
     * Returns true if a number of any unit.
     *
     * @param Node $numberOrAnything
     *
     * @return KeywordNode
     */
    public function isnumber(Node $numberOrAnything)
    {
        return $this->isA($numberOrAnything, 'ILess\Node\DimensionNode');
    }

    /**
     * Returns true if it is passed a string.
     *
     * @param Node $stringOrAnything
     *
     * @return KeywordNode
     */
    public function isstring(Node $stringOrAnything)
    {
        return $this->isA($stringOrAnything, 'ILess\Node\QuotedNode');
    }

    /**
     * Returns true if it is passed keyword.
     *
     * @param Node $numberOrAnything
     *
     * @return KeywordNode
     */
    public function iskeyword(Node $keywordOrAnything)
    {
        return $this->isA($keywordOrAnything, 'ILess\Node\KeywordNode');
    }

    /**
     * Returns true if it is a string and a url.
     *
     * @param mixed $urlOrAnything
     *
     * @return bool
     */
    public function isurl(Node $urlOrAnything)
    {
        return $this->isA($urlOrAnything, 'ILess\Node\UrlNode');
    }

    /**
     * Returns true if it is a number and a px.
     *
     * @param Node $urlOrAnything The node to check
     *
     * @return KeywordNode
     */
    public function ispixel(Node $pixelOrAnything)
    {
        if ($this->isA($pixelOrAnything, 'ILess\Node\DimensionNode') && $pixelOrAnything->unit->is('px')) {
            return new KeywordNode('true');
        }

        return new KeywordNode('false');
    }

    /**
     * Returns true if it is a number and a %.
     *
     * @param Node $percentageOrAnything
     *
     * @return KeywordNode
     */
    public function ispercentage(Node $percentageOrAnything)
    {
        if ($this->isA($percentageOrAnything, 'ILess\Node\DimensionNode') && $percentageOrAnything->unit->is('%')) {
            return new KeywordNode('true');
        }

        return new KeywordNode('false');
    }

    /**
     * Returns true if it is a number and an em.
     *
     * @param Node $emOrAnything
     *
     * @return KeywordNode
     */
    public function isem(Node $emOrAnything)
    {
        if ($this->isA($emOrAnything, 'ILess\Node\DimensionNode') && $emOrAnything->unit->is('em')) {
            return new KeywordNode('true');
        }

        return new KeywordNode('false');
    }

    /**
     * returns if a parameter is a number and is in a particular unit.
     *
     * @param Node $node
     * @param Node $unit The unit to check
     *
     * @return bool
     */
    public function isunit(Node $node, Node $unit = null)
    {
        if ($this->isA($node, 'ILess\Node\DimensionNode')
            && $node->unit->is((property_exists($unit, 'value') ? $unit->value : $unit))
        ) {
            return new KeywordNode('true');
        }

        return new KeywordNode('false');
    }

    /**
     * Returns true if the node is detached ruleset.
     *
     * @param Node $node
     *
     * @return bool
     */
    public function isruleset(Node $node)
    {
        return $this->isA($node, 'ILess\Node\DetachedRulesetNode');
    }

    /**
     * Creates a SVG gradient.
     *
     * @param Node $direction
     * @param Node ...$stop1
     *
     * @return UrlNode
     *
     * @throws CompilerException If the arguments are invalid
     */
    public function svggradient(Node $direction /*  $stop1, $stop2, ... */)
    {
        $numArgs = func_num_args();
        $arguments = func_get_args();

        if ($numArgs === 2) {
            // a list of colors
            if (is_array($arguments[1]->value) && count($arguments[1]->value) < 2) {
                throw new CompilerException(
                    'svg-gradient expects direction, start_color [start_position], [color position,]..., end_color [end_position]'
                );
            }
            $stops = $arguments[1]->value;
        } elseif ($numArgs < 3) {
            throw new CompilerException(
                'svg-gradient expects direction, start_color [start_position], [color position,]..., end_color [end_position]'
            );
        } else {
            $stops = array_slice($arguments, 1);
        }

        $gradientType = 'linear';
        $rectangleDimension = 'x="0" y="0" width="1" height="1"';
        $renderEnv = new Context([
            'compress' => false,
        ]);
        $directionValue = $direction->toCSS($renderEnv);

        switch ($directionValue) {
            case 'to bottom':
                $gradientDirectionSvg = 'x1="0%" y1="0%" x2="0%" y2="100%"';
                break;
            case 'to right':
                $gradientDirectionSvg = 'x1="0%" y1="0%" x2="100%" y2="0%"';
                break;
            case 'to bottom right':
                $gradientDirectionSvg = 'x1="0%" y1="0%" x2="100%" y2="100%"';
                break;
            case 'to top right':
                $gradientDirectionSvg = 'x1="0%" y1="100%" x2="100%" y2="0%"';
                break;
            case 'ellipse':
            case 'ellipse at center':
                $gradientType = 'radial';
                $gradientDirectionSvg = 'cx="50%" cy="50%" r="75%"';
                $rectangleDimension = 'x="-50" y="-50" width="101" height="101"';
                break;
            default:
                throw new CompilerException(
                    "svg-gradient direction must be 'to bottom', 'to right', 'to bottom right', 'to top right' or 'ellipse at center'"
                );
        }

        $returner = '<?xml version="1.0" ?>' .
            '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="100%" height="100%" viewBox="0 0 1 1" preserveAspectRatio="none">' .
            '<' . $gradientType . 'Gradient id="gradient" gradientUnits="userSpaceOnUse" ' . $gradientDirectionSvg . '>';

        for ($i = 0; $i < count($stops); ++$i) {
            if ($stops[$i] instanceof ExpressionNode) {
                $color = $stops[$i]->value[0];
                $position = $stops[$i]->value[1];
            } else {
                $color = $stops[$i];
                $position = null;
            }

            if (!($color instanceof ColorNode)
                || (!(($i === 0 || $i + 1 === count($stops)) && $position === null)
                    && !($position instanceof DimensionNode))
            ) {
                throw new CompilerException(
                    'svg-gradient expects direction, start_color [start_position], [color position,]..., end_color [end_position] or direction, color list'
                );
            }

            if ($position) {
                $positionValue = $position->toCSS($renderEnv);
            } elseif ($i === 0) {
                $positionValue = '0%';
            } else {
                $positionValue = '100%';
            }

            $colorValue = $color->getColor()->toRGB();

            $alpha = $color->getAlpha(true);
            $returner .= '<stop offset="' . $positionValue . '" stop-color="' .
                $colorValue . '"' .
                ($alpha < 1 ? ' stop-opacity="' . $alpha . '"' : '') . '/>';
        }

        $returner .= '</' . $gradientType . 'Gradient><rect ' . $rectangleDimension . ' fill="url(#gradient)" /></svg>';

        $returner = Util::encodeURIComponent($returner);
        $returner = 'data:image/svg+xml,' . $returner;

        return new UrlNode(new QuotedNode("'" . $returner . "'", $returner, false));
    }

    /**
     * Default function.
     *
     * @return KeywordNode|null
     *
     * @throws Exception
     */
    public function defaultFunc()
    {
        return DefaultFunc::compile();
    }

    /**
     * Returns the image size.
     *
     * @param Node $node The path node
     *
     * @return ExpressionNode
     *
     * @throws IOException
     */
    public function imageSize(Node $node)
    {
        $size = $this->getImageSize($node);

        return new ExpressionNode(
            [
                new DimensionNode($size['width'], 'px'),
                new DimensionNode($size['height'], 'px'),
            ]
        );
    }

    /**
     * Returns the image width.
     *
     * @param Node $node The path node
     *
     * @return DimensionNode
     *
     * @throws IOException
     */
    public function imageWidth(Node $node)
    {
        $size = $this->getImageSize($node);

        return new DimensionNode($size['width'], 'px');
    }

    /**
     * Returns the image height.
     *
     * @param Node $node The path node
     *
     * @return DimensionNode
     *
     * @throws IOException
     */
    public function imageHeight(Node $node)
    {
        $size = $this->getImageSize($node);

        return new DimensionNode($size['height'], 'px');
    }

    /**
     * Returns the width and height of the image.
     *
     * @param Node $path
     *
     * @throws IOException
     *
     * @return array
     */
    protected function getImageSize(Node $path)
    {
        $filePath = $path->value;

        $fragmentStart = strpos($filePath, '#');
        // $fragment = '';
        if ($fragmentStart !== false) {
            // $fragment = substr($filePath, $fragmentStart);
            $filePath = substr($filePath, 0, $fragmentStart);
        }

        $filePath = $this->getFilePath($filePath);

        if (!is_readable($filePath)) {
            throw new IOException(sprintf('The file "%s" is does not exist or is not readable', $filePath));
        }

        $size = @getimagesize($filePath);

        if ($size === false) {
            throw new IOException(
                sprintf('The file "%s" dimension could not be read. It is an image?', $filePath)
            );
        }

        return [
            'width' => $size[0],
            'height' => $size[1],
        ];
    }

    /**
     * Returns the file path, takes care about relative urls.
     *
     * @param string $path
     *
     * @return mixed|string
     */
    protected function getFilePath($path)
    {
        $path = Util::sanitizePath($path);

        if (Util::isPathRelative($path) && $this->currentFileInfo) {
            if ($this->context->relativeUrls) {
                $path = $this->currentFileInfo->currentDirectory . $path;
            } else {
                $path = $this->currentFileInfo->entryPath . $path;
            }
            $path = Util::normalizePath($path);
        }

        return $path;
    }

    protected function doMinmax($isMin, $args)
    {
        switch (count($args)) {
            case 0:
                throw new CompilerException('One or more arguments required.');
        }

        $order = []; // elems only contains original argument values.
        $values = []; // key is the unit.toString() for unified tree.Dimension values,
        $unitClone = $unitStatic = $j = null;

        // value is the index into the order array.
        for ($i = 0; $i < count($args); ++$i) {
            $current = $args[$i];
            if (!($current instanceof DimensionNode)) {
                if (is_array($args[$i])) {
                    $args[] = $args[$i]->value;
                }
                continue;
            }

            if ($current->unit->toString() === '' && $unitClone !== null) {
                $dim = new DimensionNode($current->value, $unitClone);
                $currentUnified = $dim->unify();
            } else {
                $currentUnified = $current->unify();
            }

            $unit = $currentUnified->unit->toString() === '' && $unitStatic !== null ? $unitStatic : $currentUnified->unit->toString();
            // $unitStatic = $unit !== '' && $unitStatic === null || $unit !== '' && $order[0]->unify()->unit->toString() === '' ? $unit : $unitStatic;

            if ($unit !== '' && !$unitStatic || $unit !== '' && $order[0]->unify()->unit->toString() === '') {
                $unitStatic = $unit;
            }

            $unitClone = $unit !== '' && $unitClone === null ? $current->unit->toString() : $unitClone;

            if (isset($values['']) && $unit !== '' && $unit === $unitStatic) {
                $j = $values[''];
            } elseif (isset($values[$unit])) {
                $j = $values[$unit];
            } else {
                if ($unitStatic !== null && $unit !== $unitStatic) {
                    throw new RuntimeException(sprintf('Incompatible types "%s" and "%s" given', $unitStatic, $unit));
                }

                $values[$unit] = count($order);
                $order[] = $current;
                continue;
            }

            if ($order[$j]->unit->toString() === '' && $unitClone !== null) {
                $dim = new DimensionNode($order[$j]->value, $unitClone);
                $referenceUnified = $dim->unify();
            } else {
                $referenceUnified = $order[$j]->unify();
            }

            if (($isMin && $currentUnified->value < $referenceUnified->value) ||
                (!$isMin && $currentUnified->value > $referenceUnified->value)
            ) {
                $order[$j] = $current;
            }
        }

        if (count($order) === 1) {
            return $order[0];
        }

        foreach ($order as $k => $a) {
            $order[$k] = $a->toCSS($this->context);
        }

        $args = implode(($this->context->compress ? ',' : ', '), $order);

        return new AnonymousNode(($isMin ? 'min' : 'max') . '(' . $args . ')');
    }

    /**
     * Checks if the given object is of this class or has this class as one of its parents.
     *
     * @param Node $node
     * @param string $className The className to check
     *
     * @return KeywordNode
     */
    protected function isA(Node $node, $className)
    {
        if (is_a($node, $className)) {
            return new KeywordNode('true');
        }

        return new KeywordNode('false');
    }

    /**
     * Clamps the value.
     *
     * @param int $value
     *
     * @return int
     */
    protected function clamp($value)
    {
        return min(1, max(0, $value));
    }

    /**
     * Convert the given node to color node.
     *
     * @param Node $node The node
     * @param null $exceptionMessage ILess\Exception\Exception message if the node could not be converted to color node
     *
     * @return ColorNode
     *
     * @throws InvalidArgumentException If the node could not be converted to color
     */
    protected function getColorNode(Node $node, $exceptionMessage = null)
    {
        if ($node instanceof ColorNode) {
            return $node;
        }

        // this is a keyword
        if ($node instanceof KeywordNode && Color::isNamedColor($node->value)) {
            $node = new ColorNode(Color::color($node->value));
        } elseif ($node instanceof ToColorConvertibleInterface) {
            $node = $node->toColor();
        }

        if (!$node instanceof ColorNode) {
            throw new InvalidArgumentException($exceptionMessage ? $exceptionMessage : 'Cannot convert node to color');
        }

        return $node;
    }

    /**
     * Color blending.
     *
     * @param callable $mode
     * @param Color $color1
     * @param Color $color2
     *
     * @return ColorNode
     */
    protected function colorBlend(callable $mode, Color $color1, Color $color2)
    {
        $ab = $color1->getAlpha();    // backdrop
        $as = $color2->getAlpha();    // source
        $r = [];            // result

        $ar = $as + $ab * (1 - $as);
        $rgb1 = $color1->rgb;
        $rgb2 = $color2->rgb;
        for ($i = 0; $i < 3; ++$i) {
            $cb = $rgb1[$i] / 255;
            $cs = $rgb2[$i] / 255;
            $cr = call_user_func($mode, $cb, $cs);
            if ($ar) {
                $cr = ($as * $cs + $ab * ($cb - $as * ($cb + $cs - $cr))) / $ar;
            }
            $r[$i] = $cr * 255;
        }

        return new ColorNode($r, $ar);
    }

    private function colorBlendMultiply($cb, $cs)
    {
        return $cb * $cs;
    }

    private function colorBlendScreen($cb, $cs)
    {
        return $cb + $cs - $cb * $cs;
    }

    private function colorBlendOverlay($cb, $cs)
    {
        $cb *= 2;

        return ($cb <= 1)
            ? $this->colorBlendMultiply($cb, $cs)
            : $this->colorBlendScreen($cb - 1, $cs);
    }

    private function colorBlendSoftlight($cb, $cs)
    {
        $d = 1;
        $e = $cb;
        if ($cs > 0.5) {
            $e = 1;
            $d = ($cb > 0.25) ? sqrt($cb)
                : ((16 * $cb - 12) * $cb + 4) * $cb;
        }

        return $cb - (1 - 2 * $cs) * $e * ($d - $cb);
    }

    private function colorBlendHardlight($cb, $cs)
    {
        return $this->colorBlendOverlay($cs, $cb);
    }

    private function colorBlendDifference($cb, $cs)
    {
        return abs($cb - $cs);
    }

    private function colorBlendExclusion($cb, $cs)
    {
        return $cb + $cs - 2 * $cb * $cs;
    }

    private function colorBlendAverage($cb, $cs)
    {
        return ($cb + $cs) / 2;
    }

    private function colorBlendNegation($cb, $cs)
    {
        return 1 - abs($cb + $cs - 1);
    }
}

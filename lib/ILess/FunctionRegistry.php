<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Builtin functions
 *
 * @package ILess
 * @subpackage function
 * @see http://lesscss.org/#reference
 */
class ILess_FunctionRegistry
{
    /**
     * Maximum allowed size of data uri for IE8 in kB
     */
    const IE8_DATA_URI_MAX_KB = 32;

    /**
     * Less environment
     *
     * @var ILess_Environment
     */
    protected $env;

    /**
     * Array of function aliases
     *
     * @var array
     */
    protected $aliases = array(
        '%' => 'template',
        'data-uri' => 'dataUri',
        'svg-gradient' => 'svggradient'
    );

    /**
     * Array of callable functions
     *
     * @var array
     */
    private $functions = array(
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
        'length' => true,
        'lighten' => true,
        'lightness' => true,
        'luma' => true,
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
        'unit' => true
    );

    /**
     * Constructor
     *
     * @param array $aliases Array of function aliases in the format array(alias => function)
     * @param ILess_Environment $env The environment
     */
    public function __construct($aliases = array(), ILess_Environment $env = null)
    {
        $this->env = $env;
        $this->addAliases($aliases);
    }

    /**
     * Adds a function to the functions
     *
     * @param string $functionName
     * @param callable $callable
     * @param string|array $aliases The array of aliases
     * @return ILess_FunctionRegistry
     * @throws InvalidArgumentException If the callable is not valid
     */
    public function addFunction($functionName, $callable, $aliases = array())
    {
        if (!is_callable($callable, false, $callableName)) {
            throw new InvalidArgumentException(sprintf('The callable "%s" for function "%s" is not valid.', $callableName, $functionName));
        }

        // all functions are case insensitive
        $functionName = strtolower($functionName);

        $this->functions[$functionName] = $callable;

        if (!is_array($aliases)) {
            $aliases = array($aliases);
        }

        foreach ($aliases as $alias) {
            $this->addAlias($alias, $functionName);
        }

        return $this;
    }

    /**
     * Adds functions
     *
     * <pre>
     * $registry->addFunctions(array(array('name' => 'load', 'callable' => 'load'));
     * $registry->addFunctions(array(array('name' => 'load', 'callable' => 'load', 'alias' => 'l'));
     * </pre>
     *
     * @param array $functions Array of functions in format (''
     * @return ILess_FunctionRegistry
     */
    public function addFunctions(array $functions)
    {
        foreach ($functions as $function) {
            $this->addFunction($function['name'], $function['callable'], isset($function['alias']) ? $function['alias'] : array());
        }

        return $this;
    }

    /**
     * Returns names of registered functions
     *
     * @return array
     */
    public function getFunctions()
    {
        return array_keys($this->functions);
    }

    /**
     * Returns aliases for registered functions
     *
     * @param boolean $withFunctions Include function names?
     * @return array
     */
    public function getAliases($withFunctions = false)
    {
        return $withFunctions ? $this->aliases : array_keys($this->aliases);
    }

    /**
     * Adds a function alias
     *
     * @param string $alias The alias name
     * @param string $function The function name
     * @return ILess_FunctionRegistry
     */
    public function addAlias($alias, $function)
    {
        $functionLower = strtolower($function);
        if (!isset($this->functions[$functionLower])) {
            throw new InvalidArgumentException(sprintf('Invalid alias "%s" for "%s" given. The "%s" does not exist.', $alias, $function));
        }
        $this->aliases[strtolower($alias)] = $functionLower;

        return $this;
    }

    /**
     * Adds aliases
     *
     * @param array $aliases
     * @return ILess_FunctionRegistry
     */
    public function addAliases(array $aliases)
    {
        foreach ($aliases as $alias => $function) {
            $this->addAlias($alias, $function);
        }

        return $this;
    }

    /**
     * Sets the environment
     *
     * @param ILess_Environment $env
     */
    public function setEnvironment(ILess_Environment $env)
    {
        $this->env = $env;
    }

    /**
     * Returns the environment instance
     *
     * @return mixed
     */
    public function getEnvironment()
    {
        return $this->env;
    }

    /**
     * Calls a method with given arguments
     *
     * @param string $methodName
     * @param array $arguments
     * @return mixed
     * @throws ILess_Exception_Function If the method does not exist
     */
    public function call($methodName, $arguments = array())
    {
        $methodName = strtolower($methodName);

        if (isset($this->aliases[$methodName])) {
            $methodName = $this->aliases[$methodName];
        }

        if (isset($this->functions[$methodName])) {
            if ($this->functions[$methodName] === true) {
                // built in function
                return call_user_func_array(array($this, $methodName), $arguments);
            } else {
                // this is a external callable
                // provide access to function registry (pass as first parameter)
                array_unshift($arguments, $this);

                return call_user_func_array($this->functions[$methodName], $arguments);
            }
        }
    }

    /**
     * Applies URL-encoding to special characters found in the input string.
     *
     * * Following characters are exceptions and not encoded: `,, /, ?, @, &, +, ', ~, ! $`
     * * Most common encoded characters are: `<space>`, #, ^, (, ), {, }, |, :, >, <, ;, ], [ =`
     *
     * @param ILess_Node $string A string to escape
     * @return ILess_Node_Anonymous Escaped string content without quotes.
     */
    public function escape(ILess_Node $string)
    {
        return new ILess_Node_Anonymous(urlencode((string)$string));
    }

    /**
     * CSS escaping similar to ~"value" syntax. It expects string as a parameter and return
     * its content as is, but without quotes. It can be used to output CSS value which is either not valid CSS syntax,
     * or uses proprietary syntax which LESS doesn't recognize.
     *
     * @param string $string A string to escape
     * @return ILess_Node_Anonymous Content without quotes.
     */
    public function e(ILess_Node $string)
    {
        return new ILess_Node_Anonymous(str_replace(array('~"', '"'), '', (string)$string));
    }

    /**
     * Returns the number of items
     *
     * @param ILess_Node $values
     * @return ILess_Node_Dimension
     */
    public function length(ILess_Node $values)
    {
        return new ILess_Node_Dimension(is_array($values->value) ? count($values->value) : 1);
    }

    /**
     * Min
     *
     * @return ILess_Node_Dimension
     */
    public function min()
    {
        // php 5.2 compat, func_get_args() can't be used as a function parameter
        $args = func_get_args();
        return $this->doMinmax(true, $args);
    }

    /**
     * Max
     *
     * @return ILess_Node_Dimension
     */
    public function max()
    {
        // php 5.2 compat, func_get_args() can't be used as a function parameter
        $args = func_get_args();
        return $this->doMinmax(false, $args);
    }

    /**
     * Extract
     *
     * @param ILess_Node $values
     * @param ILess_Node $index
     * @return null|ILess_Node
     */
    public function extract(ILess_Node $values, ILess_Node $index)
    {
        $index = (int)$index->value - 1; // (1-based index)
        // handle non-array values as an array of length 1
        // return 'undefined' if index is invalid
        if (is_array($values->value)) {
            if (isset($values->value[$index])) {
                return $values->value[$index];
            }
        } elseif ($index === 0) {
            return $values;
        }
    }

    /**
     * Formats a string. Less equivalent is: `%`.
     * The first argument is string with placeholders.
     * All placeholders start with percentage symbol % followed by letter s,S,d,D,a, or A.
     * Remaining arguments contain expressions to replace placeholders.
     * If you need to print the percentage symbol, escape it by another percentage %%.*
     *
     * @param object $string
     * @param $value1
     * @param $value2
     * @return ILess_Node_Quoted
     */
    public function template(ILess_Node $string /* , $value1, $value2, ... */)
    {
        $args = func_get_args();
        array_shift($args);
        $string = $string->value;
        foreach ($args as $arg) {
            if (preg_match('/%[sda]/i', $string, $token)) {
                $token = $token[0];
                $value = stristr($token, 's') ? $arg->value : $arg->toCSS($this->env);
                $value = preg_match('/[A-Z]$/', $token) ? urlencode($value) : $value;
                $string = preg_replace('/%[sda]/i', $value, $string, 1);
            }
        }
        $string = str_replace('%%', '%', $string);

        return new ILess_Node_Quoted('"' . $string . '"', $string);
    }

    /**
     * Inlines a resource and falls back to url() if the ieCompat option is on
     * and the resource is too large, or if you use the function in the browser.
     * If the mime is not given then node uses the mime package to determine the correct mime type.
     *
     * @param string $mimeType A mime type string
     * @param string $url The URL of the file to inline.
     */
    public function dataUri(ILess_Node $mimeType, ILess_Node $filePath = null)
    {
        if (func_num_args() < 2) {
            $path = $mimeType->value;
            $mime = false; // we will detect it later
        } else {
            $path = $filePath->value;
            $mime = $mimeType->value;
        }

        $path = ILess_Util::sanitizePath($path);

        if (ILess_Util::isPathRelative($path)) {
            if ($this->env->relativeUrls) {
                $path = $this->env->currentFileInfo->currentDirectory . $path;
            } else {
                $path = $this->env->currentFileInfo->entryPath . $path;
            }
            $path = ILess_Util::normalizePath($path);
        }

        if ($mime === false) {
            $mime = ILess_Mime::lookup($path);
            // use base 64 unless it's an ASCII or UTF-8 format
            $charset = ILess_Mime::charsetsLookup($mime);
            $useBase64 = !in_array($charset, array('US-ASCII', 'UTF-8'));
            if ($useBase64) {
                $mime .= ';base64';
            }
        } else {
            $useBase64 = preg_match('/;base64$/', $mime);
        }

        $buffer = false;
        if (is_readable($path)) {
            $buffer = file_get_contents($path);
        }

        // IE8 cannot handle a data-uri larger than 32KB. If this is exceeded
        // and the --ieCompat option is enabled, return a normal url() instead.
        if ($this->env->ieCompat && $buffer !== false) {
            $fileSizeInKB = round(strlen($buffer) / 1024);
            if ($fileSizeInKB >= self::IE8_DATA_URI_MAX_KB) {
                $url = new ILess_Node_Url(($filePath ? $filePath : $mimeType), $this->env->currentFileInfo);

                return $url->compile($this->env);
            }
        }

        if ($buffer !== false) {
            $buffer = $useBase64 ? base64_encode($buffer) : rawurlencode($buffer);
            $path = "'data:" . $mime . ',' . $buffer . "'";
        }

        return new ILess_Node_Url(new ILess_Node_Anonymous($path));
    }

    /**
     * Rounds up to an integer
     *
     * @param mixed $number
     * @return ILess_Node_Dimension
     */
    public function ceil($number)
    {
        return new ILess_Node_Dimension(ceil($this->number($number)), $number->unit);
    }

    /**
     * Rounds down to an integer
     *
     * @param mixed $number
     * @return mixed
     */
    public function floor($number)
    {
        return $this->doMath('floor', $number);
    }

    /**
     * Converts to a %, e.g. 0.5 -> 50%
     *
     * @param ILess_Node $number
     */
    public function percentage(ILess_Node $number)
    {
        return new ILess_Node_Dimension($number->value * 100, '%');
    }

    /**
     * Rounds a number to a number of places
     *
     * @param string|ILess_Node $number The number to round
     * @param integer $places The precision
     * @return ILess_Node_Dimension
     */
    public function round($number, ILess_Node_Dimension $places = null)
    {
        if ($number instanceof ILess_Node_Dimension) {
            $unit = $number->unit;
            $number = $number->value;
        } else {
            $unit = null;
        }

        $rounded = ILess_Math::round($number, $places ? $places->value : 0);

        return new ILess_Node_Dimension($rounded, $unit);
    }

    /**
     * Calculates square root of a number
     *
     * @param mixed $number
     * @return mixed
     */
    public function sqrt($number)
    {
        return $this->doMath('sqrt', $number);
    }

    /**
     * Absolute value of a number
     *
     * @param mixed $number The number
     * @return mixed
     */
    public function abs($number)
    {
        return $this->doMath('abs', $number);
    }

    /**
     * Sine function
     *
     * @param string $number The number
     * @return mixed
     */
    public function sin($number)
    {
        return $this->doMath('sin', $number, '');
    }

    /**
     * Arcsine - inverse of sine function
     *
     * @param string $number
     * @return mixed
     */
    public function asin($number)
    {
        return $this->doMath('asin', $number, 'rad');
    }

    /**
     * Cosine function
     *
     * @param string $number
     * @return mixed
     */
    public function cos($number)
    {
        return $this->doMath('cos', $number, '');
    }

    /**
     * Arccosine - inverse of cosine function
     *
     * @param string $number
     * @return mixed
     */
    public function acos($number)
    {
        return $this->doMath('acos', $number, 'rad');
    }

    /**
     * Tangent function
     *
     * @param mixed $number
     * @return mixed
     */
    public function tan($number)
    {
        return $this->doMath('tan', $number, '');
    }

    /**
     * Arctangent - inverse of tangent function
     *
     * @param mixed $number
     * @return mixed
     */
    public function atan($number)
    {
        return $this->doMath('atan', $number, 'rad');
    }

    /**
     * Does the math using ILess_Math
     *
     * @param string $func The math function like sqrt, floor...
     * @param ILess_Node_Dimension|integer $number The number
     * @param ILess_Node_DimensionUnit|string $unit The unit
     * @param mixed $argument1 Argument for the mathematical function
     * @param mixed $argument2 Argument for the mathematical function
     * @return mixed
     * @throws ILess_Exception_Compiler
     */
    protected function doMath($func, $number, $unit = null /*, $arguments...*/)
    {
        $arguments = array();
        if (func_num_args() > 3) {
            foreach (func_get_args() as $i => $arg) {
                // skip first 3 arguments
                if ($i < 3) {
                    continue;
                }
                $arguments[] = $arg;
            }
        }

        if ($number instanceof ILess_Node_Dimension) {
            if ($unit === null) {
                $unit = $number->unit;
            } else {
                $number = $number->unify();
            }
            $number = $number->value;
            array_unshift($arguments, $number);
            // We have to deal this with ILess_Math to be precious
            return new ILess_Node_Dimension(call_user_func_array(array('ILess_Math', $func), $arguments), $unit);
        } elseif (is_numeric($number)) {
            array_unshift($arguments, $number);

            return call_user_func_array(array('ILess_Math', $func), $arguments);
        }

        throw new ILess_Exception_Compiler('The math functions take numbers as parameters');
    }

    /**
     * Returns pi
     *
     * @return ILess_Node_Dimension
     */
    public function pi()
    {
        return new ILess_Node_Dimension(pi());
    }

    /**
     * First argument raised to the power of the second argument
     *
     * @param string $number
     * @param string $exponent
     * @return string
     */
    public function pow($number, $exponent)
    {
        if (is_numeric($number) && is_numeric($exponent)) {
            $number = new ILess_Node_Dimension($number);
            $exponent = new ILess_Node_Dimension($exponent);
        } elseif (!($number instanceof ILess_Node_Dimension)
            || !($exponent instanceof ILess_Node_Dimension)
        ) {
            throw new ILess_Exception_Compiler('Arguments must be numbers.');
        }

        return new ILess_Node_Dimension(ILess_Math::power($number->value, $exponent->value), $number->unit);
    }

    /**
     * First argument modulus second argument
     *
     * @param ILess_Node_Dimension $number1
     * @param ILess_Node_Dimension $number2
     * @return ILess_Node_Dimension
     */
    public function mod(ILess_Node_Dimension $number1, ILess_Node_Dimension $number2)
    {
        return new ILess_Node_Dimension($number1->value % $number2->value, $number1->unit);
    }

    /**
     * Converts between number types
     *
     * @param ILess_Node_Dimension $number
     * @param string $units
     */
    public function convert(ILess_Node $number, $units)
    {
        if (!$number instanceof ILess_Node_Dimension) {
            return;
        }

        return $number->convertTo($units->value);
    }

    /**
     * Changes number units without converting it
     *
     * @param ILess_Node $number The dimension
     * @param ILess_Node $unit The unit
     */
    public function unit(ILess_Node $number, ILess_Node $unit = null)
    {
        if (!$number instanceof ILess_Node_Dimension) {
            throw new ILess_Exception_Compiler(sprintf('The first argument to unit must be a number%s',
                ($number instanceof ILess_Node_Operation ? '. Have you forgotten parenthesis?' : '.')));
        }

        return new ILess_Node_Dimension($number->value, $unit ? $unit->toCSS($this->env) : '');
    }

    /**
     * Converts string or escaped value into color
     *
     * @param string $string
     */
    public function color(ILess_Node $string)
    {
        if ($string instanceof ILess_Node_Quoted) {
            return new ILess_Node_Color(substr($string->value, 1));
        }
        throw new ILess_Exception_CompilerException('Argument must be a string');
    }

    /**
     * Converts to a color
     *
     * @param string $red The red component of a color
     * @param string $green The green component of a color
     * @param string $blue The blue component of a color
     */
    public function rgb($red, $green, $blue)
    {
        return $this->rgba($red, $green, $blue, 1);
    }

    /**
     * Converts to a color
     *
     * @param string $red The red component of a color
     * @param string $green The green component of a color
     * @param string $blue The blue component of a color
     * @param string $alpha The alpha channel
     * @return ILess_Node_Color
     */
    public function rgba($red, $green, $blue, $alpha)
    {
        $rgb = array_map(array($this, 'scaled'), array($red, $green, $blue));

        return new ILess_Node_Color($rgb, $this->number($alpha));
    }

    /**
     * Scales the number to percentage
     *
     * @param ILess_Node_Dimension $n The number
     * @param integer $size
     * @return float
     */
    public function scaled($n, $size = 255)
    {
        if ($n instanceof ILess_Node_Dimension && $n->unit->is('%')) {
            return ILess_Math::multiply($n->value, ILess_Math::divide($size, '100'));
        } else {
            return $this->number($n);
        }
    }

    /**
     * Converts the $number to "real" number
     *
     * @param ILess_Node_Dimension|integer|float $number
     * @return double
     * @throws InvalidArgumentException
     */
    public function number($number)
    {
        if ($number instanceof ILess_Node_Dimension) {
            return $number->unit->is('%') ? ILess_Math::clean(ILess_Math::divide($number->value, '100')) : $number->value;
        } elseif (is_numeric($number)) {
            return $number;
        } else {
            throw new InvalidArgumentException(sprintf('Color functions take numbers as parameters. "%s" given.', gettype($number)));
        }
    }

    /**
     * Creates a `#AARRGGBB`
     *
     * @param ILess_Color $color The color
     * @return ILess_Node_Anonymous
     */
    public function argb(ILess_Node $color)
    {
        if (!$color instanceof ILess_Node_Color) {
            return $color;
        }

        return new ILess_Node_Anonymous($color->toARGB());
    }

    /**
     * Creates a color
     *
     * @param ILess_Node_Dimension $hue The hue
     * @param ILess_Node_Dimension $saturation The saturation
     * @param ILess_Node_Dimension $lightness The lightness
     */
    public function hsl($hue, $saturation, $lightness)
    {
        return $this->hsla($hue, $saturation, $lightness, 1);
    }

    /**
     * Creates a color from hsla color namespace
     *
     * @param mixed $hue
     * @param mixed $saturation
     * @param mixed $lightness
     * @param mixed $alpha
     * @return ILess_Node_Color
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
            $alpha);
    }

    /**
     * Helper for hsla()
     *
     * @param float $h
     * @param float $m1
     * @param float $m2
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
     * Creates a color
     *
     * @param integer $hue The hue
     * @param integer $saturation The saturation
     * @param integer $value The value
     */
    public function hsv($hue, $saturation, $value)
    {
        return $this->hsva($hue, $saturation, $value, 1);
    }

    /**
     * Creates a color
     *
     * @param integer $hue The hue
     * @param integer $saturation The saturation
     * @param integer $value The value
     * @param integer $alpha The alpha channel
     */
    public function hsva($hue, $saturation, $value, $alpha)
    {
        $hue = (($this->number($hue) % 360) / 360) * 360;
        $saturation = $this->number($saturation);
        $value = $this->number($value);
        $alpha = $this->number($alpha);

        $i = floor(($hue / 60) % 6);
        $f = ($hue / 60) - $i;

        $vs = array($value,
            $value * (1 - $saturation),
            $value * (1 - $f * $saturation),
            $value * (1 - (1 - $f) * $saturation));

        $perm = array(
            array(0, 3, 1),
            array(2, 0, 1),
            array(1, 0, 3),
            array(1, 2, 0),
            array(3, 1, 0),
            array(0, 1, 2)
        );

        return $this->rgba($vs[$perm[$i][0]] * 255,
            $vs[$perm[$i][1]] * 255,
            $vs[$perm[$i][2]] * 255,
            $alpha);
    }

    /**
     * Returns the `hue` channel of the $color in the HSL space
     *
     * @param ILess_Node_Color $color
     * @return ILess_Node_Dimension
     */
    public function hue(ILess_Node_Color $color)
    {
        return $color->getHue();
    }

    /**
     * Returns the `saturation` channel of the $color in the HSL space
     *
     * @param ILess_Node_Color $color
     * @return ILess_Node_Dimension
     */
    public function saturation(ILess_Node_Color $color)
    {
        return $color->getSaturation();
    }

    /**
     * Returns the 'lightness' channel of @color in the HSL space
     *
     * @param ILess_Node_Color $color
     * @return ILess_Node_Dimension
     */
    public function lightness(ILess_Node_Color $color)
    {
        return $color->getLightness();
    }

    /**
     * Returns the `hue` channel of @color in the HSV space
     *
     * @param ILess_Node_Color $color
     * @return string
     */
    public function hsvhue(ILess_Node $color)
    {
        if (!$color instanceof ILess_Node_Color) {
            return $color;
        }
        $hsv = $color->toHSV();

        return new ILess_Node_Dimension(ILess_Math::round($hsv['h']));
    }

    /**
     * Returns the `saturation` channel of @color in the HSV space
     *
     * @param ILess_Node_Color $color
     * @return string
     */
    public function hsvsaturation(ILess_Node $color)
    {
        if (!$color instanceof ILess_Node_Color) {
            return $color;
        }
        $hsv = $color->toHSV();

        return new ILess_Node_Dimension(ILess_Math::round($hsv['s'] * 100), '%');
    }

    /**
     * Returns the 'value' channel of @color in the HSV space
     *
     * @param ILess_Node_Color $color
     * @return string
     */
    public function hsvvalue(ILess_Node $color)
    {
        if (!$color instanceof ILess_Node_Color) {
            return $color;
        }
        $hsv = $color->toHSV();

        return new ILess_Node_Dimension(ILess_Math::round($hsv['v'] * 100), '%');
    }

    /**
     * Returns the 'red' channel of @color
     *
     * @param ILess_Node_Color $color
     * @return string
     */
    public function red(ILess_Node_Color $color)
    {
        return $color->getRed();
    }

    /**
     * Returns the 'green' channel of @color
     *
     * @param ILess_Node_Color $color
     * @return string
     */
    public function green(ILess_Node_Color $color)
    {
        return $color->getGreen();
    }

    /**
     * Returns the 'blue' channel of @color
     *
     * @param ILess_Node_Color $color
     * @return ILess_Node_Dimension
     */
    public function blue(ILess_Node_Color $color)
    {
        return $color->getBlue();
    }

    /**
     * Returns the 'alpha' channel of the $color
     *
     * @param ILess_Node_Color $color The color
     * @return ILess_Node_Dimension
     */
    public function alpha(ILess_Node $color)
    {
        if (!$color instanceof ILess_Node_Color) {
            return $color;
        }

        return $color->getAlpha();
    }

    /**
     * Returns the 'luma' value (perceptual brightness) of the $color
     *
     * @param ILess_Node_Color $color
     * @return ILess_Node_Dimension
     */
    public function luma(ILess_Node_Color $color)
    {
        return $color->getLuma();
    }

    /**
     * Return a color 10% points *more* saturated
     *
     * @param ILess_Node_Color $color
     * @param ILess_Node The percentage
     * @return string
     */
    public function saturate(ILess_Node $color, ILess_Node $percentage = null)
    {
        if (!$color instanceof ILess_Node_Color) {
            if ($color instanceof ILess_Node_Dimension
                || !ILess_Node::methodExists($color, 'toColor')
            ) {
                return null;
            }
            $color = $color->toColor();
        }

        $percentage = $percentage ? $percentage->value : 10;
        $saturation = $this->clamp($color->getSaturation(true) + $percentage / 100);

        return $this->hsla($color->getHue(true), $saturation, $color->getLightness(true), $color->getAlpha());
    }

    /**
     * Return a color 10% points *less* saturated
     *
     * @param ILess_Node_Color $color
     * @return string
     */
    public function desaturate(ILess_Node_Color $color, ILess_Node $percentage = null)
    {
        $percentage = $percentage ? $percentage->value : 10;
        $saturation = $this->clamp($color->getSaturation(true) - $percentage / 100);

        return $this->hsla($color->getHue(true), $saturation, $color->getLightness(true), $color->getAlpha(true));
    }

    /**
     * Return a color 10% points *lighter*
     *
     * @param ILess_Node_Color|string $color
     * @param ILess_Node $percentage The percentage (Default to 10%)
     * @return string
     */
    public function lighten(ILess_Node $color, ILess_Node $percentage = null)
    {
        // this is a keyword
        if ($color instanceof ILess_Node_Keyword && ILess_Color::isNamedColor($color->value)) {
            $color = new ILess_Node_Color(ILess_Color::color($color->value));
        }

        if (!$color instanceof ILess_Node_Color) {
            throw new InvalidArgumentException('Cannot lighten the color');
        }

        $percentage = $percentage ? $percentage->value / 100 : 10;
        $lightness = $this->clamp($color->getLightness(true) + $percentage);

        return $this->hsla($color->getHue(true), $color->getSaturation(true), $lightness, $color->getAlpha());
    }

    /**
     * Return a color 10% points *darker*
     *
     * @param ILess_Node $color
     * @param ILess_Node
     * @return string
     * @throws InvalidArgumentException If the node is invalid
     */
    public function darken(ILess_Node $color, ILess_Node_Dimension $percentage = null)
    {
        // this is a keyword
        if ($color instanceof ILess_Node_Keyword && ILess_Color::isNamedColor($color->value)) {
            $color = new ILess_Node_Color(ILess_Color::color($color->value));
        }

        if (!$color instanceof ILess_Node_Color) {
            throw new InvalidArgumentException('Cannot darken the color. Invalid color given.');
        }

        $percentage = $percentage ? $percentage->value / 100 : 10;
        $lightness = $this->clamp($color->getLightness(true) - $percentage);

        return $this->hsla($color->getHue(true), $color->getSaturation(true), $lightness, $color->getAlpha(true));
    }

    /**
     * Return a color 10% points *less* transparent
     *
     * @param ILess_Node_Color $color
     * @return ILess_Node_Color
     */
    public function fadein(ILess_Node_Color $color, ILess_Node_Dimension $percentage = null)
    {
        $alpha = $color->getAlpha(true);
        if ($percentage && $percentage->unit->is('%')) {
            $alpha += $percentage->value / 100;
        } else {
            $alpha += $percentage ? $percentage->value : 10;
        }

        return $this->hsla($color->getHue(true), $color->getSaturation(true), $color->getLightness(true), $this->clamp($alpha));
    }

    /**
     * Return a color 10% points *more* transparent
     *
     * @param ILess_Node_Color $color
     * @param ILess_Node_Dimension $percentage
     * @return string
     */
    public function fadeout(ILess_Node_Color $color, ILess_Node_Dimension $percentage = null)
    {
        $alpha = $color->getAlpha(true);
        if ($percentage && $percentage->unit->is('%')) {
            $alpha -= $percentage->value / 100;
        } else {
            $alpha -= $percentage ? $percentage->value : 10;
        }

        return $this->hsla($color->getHue(true), $color->getSaturation(true), $color->getLightness(true), $this->clamp($alpha));
    }

    /**
     * Return $color with 50% transparency
     *
     * @param ILess_Node_Color $color
     * @param ILess_Node_Dimension $percentage
     * @return string
     */
    public function fade(ILess_Node_Color $color, ILess_Node_Dimension $percentage = null)
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
     * Return a color with a 10 degree larger in hue
     *
     * @param ILess_Node_Color $color
     * @param ILess_Node_Dimension $degrees
     * @return ILess_Node_Color
     */
    public function spin(ILess_Node_Color $color, ILess_Node_Dimension $degrees = null)
    {
        $degrees = $degrees ? $degrees->value : 10;
        $hue = (string)fmod($color->getHue(true) + $degrees, 360);
        $hue = $hue < 0 ? 360 + $hue : $hue;

        return $this->hsla($hue, $color->getSaturation(true), $color->getLightness(true), $color->getAlpha());
    }

    /**
     * Return a mix of $color1 and $color2 with given $weightPercentage (defaults to 50%)
     *
     * @param ILess_Node $color1
     * @param ILess_Node $color2
     * @param ILess_Node_Dimension $weightPercentage
     * @return ILess_Node_Color
     * @link http://sass-lang.com
     * @copyright 2006-2009 Hampton Catlin, Nathan Weizenbaum, and Chris Eppstein
     */
    public function mix(ILess_Node $color1, ILess_Node $color2, ILess_Node_Dimension $weightPercentage = null)
    {
        if (!$color1 instanceof ILess_Node_Color) {
            return $color1;
        } elseif (!$color2 instanceof ILess_Node_Color) {
            return $color1;
        }

        if (!$weightPercentage) {
            $weightPercentage = new ILess_Node_Dimension('50', '%');
        }

        $p = $weightPercentage->value / 100.0;
        $w = $p * 2 - 1;
        $a = $color1->getAlpha(true) - $color2->getAlpha(true);

        $w1 = (((($w * $a) == -1) ? $w : ($w + $a) / (1 + $w * $a)) + 1) / 2;
        $w2 = 1 - $w1;

        $color1Rgb = $color1->getRGB();
        $color2Rgb = $color2->getRGB();

        $rgb = array($color1Rgb[0] * $w1 + $color2Rgb[0] * $w2,
            $color1Rgb[1] * $w1 + $color2Rgb[1] * $w2,
            $color1Rgb[2] * $w1 + $color2Rgb[2] * $w2);

        $alpha = $color1->getAlpha(true) * $p + $color2->getAlpha(true) * (1 - $p);

        return new ILess_Node_Color($rgb, $alpha);
    }

    /**
     * Return a color mixed 10% with white
     *
     * @param ILess_Node $color
     * @return string
     */
    public function tint(ILess_Node $color, ILess_Node $percentage = null)
    {
        return $this->mix($this->rgb(255, 255, 255), $color, $percentage);
    }

    /**
     * Return a color mixed 10% with black
     *
     * @param ILess_Node $color
     * @param ILess_Node $percentage
     * @return string
     */
    public function shade(ILess_Node $color, ILess_Node $percentage = null)
    {
        return $this->mix($this->rgb(0, 0, 0), $color, $percentage);
    }

    /**
     * Returns a grey, 100% desaturated color
     *
     * @param ILess_Node_Color $color
     * @return string
     */
    public function greyscale(ILess_Node_Color $color)
    {
        return $this->desaturate($color, new ILess_Node_Dimension(100));
    }

    /**
     * Return @darkColor if @color is > 43% luma otherwise return @lightColor, see notes
     *
     * @param ILess_Node_Color $color
     * @return string
     */
    public function contrast(ILess_Node $color, ILess_Node_Color $darkColor = null,
                             ILess_Node_Color $lightColor = null, ILess_Node_Dimension $thresholdPercentage = null)
    {
        // ping pong back
        // filter: contrast(3.2);
        // should be kept as is, so check for color
        if (!$color instanceof ILess_Node_Color) {
            if ($color instanceof ILess_Node_Dimension ||
                !ILess_Node::methodExists($color, 'toColor')
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
     * Multiplies the $color1 with $color2
     *
     * @param ILess_Node_Color $color1 The first color
     * @param ILess_Node_Color $color2 The second color
     */
    public function multiply(ILess_Node_Color $color1, ILess_Node_Color $color2)
    {
        $r = $color1->getRed(true) * $color2->getRed(true) / 255;
        $g = $color1->getGreen(true) * $color2->getGreen(true) / 255;
        $b = $color1->getBlue(true) * $color2->getBlue(true) / 255;

        return $this->rgb($r, $g, $b);
    }

    /**
     *
     * @param ILess_Node_Color $color1 The first color
     * @param ILess_Node_Color $color2 The second color
     */
    public function screen(ILess_Node_Color $color1, ILess_Node_Color $color2)
    {
        /*
        $rs1 = ILess_Math::substract('255', $color1->getRed(true));
        $rs2 = ILess_Math::substract('255', $color2->getRed(true));
        $r = ILess_Math::substract('255', ILess_Math::divide(ILess_Math::multiply($rs1, $rs2), '255'));

        $rs1 = ILess_Math::substract('255', $color1->getGreen(true));
        $rs2 = ILess_Math::substract('255', $color2->getGreen(true));
        $g = ILess_Math::substract('255', ILess_Math::divide(ILess_Math::multiply($rs1, $rs2), '255'));

        $rs1 = ILess_Math::substract('255', $color1->getBlue(true));
        $rs2 = ILess_Math::substract('255', $color2->getBlue(true));
        $b = ILess_Math::substract('255', ILess_Math::divide(ILess_Math::multiply($rs1, $rs2), '255'));
        */

        // Formula: Result Color = 255 - [((255 - Top Color)*(255 - Bottom Color))/255]

        // $b = 255 - (255 - $color1->getBlue(true)) * (255 - $color2->getBlue(true)) / 255;
        $r = 255 - ((255 - $color1->getRed(true)) * (255 - $color2->getRed(true)) / 255);
        $g = 255 - (255 - $color1->getGreen(true)) * (255 - $color2->getGreen(true)) / 255;
        $b = 255 - (255 - $color1->getBlue(true)) * (255 - $color2->getBlue(true)) / 255;

        return $this->rgb($r, $g, $b);
    }

    /**
     *
     * @param ILess_Node_Color $color1 The first color
     * @param ILess_Node_Color $color2 The second color
     */
    public function overlay(ILess_Node_Color $color1, ILess_Node_Color $color2)
    {
        $color1Rgb = $color1->getRGB();
        $color2Rgb = $color2->getRGB();

        $r = $color1Rgb[0] < 128 ? 2 * $color1Rgb[0] * $color2Rgb[0] / 255 : 255 - 2 * (255 - $color1Rgb[0]) * (255 - $color2Rgb[0]) / 255;
        $g = $color1Rgb[1] < 128 ? 2 * $color1Rgb[1] * $color2Rgb[1] / 255 : 255 - 2 * (255 - $color1Rgb[1]) * (255 - $color2Rgb[1]) / 255;
        $b = $color1Rgb[2] < 128 ? 2 * $color1Rgb[2] * $color2Rgb[2] / 255 : 255 - 2 * (255 - $color1Rgb[2]) * (255 - $color2Rgb[2]) / 255;

        return $this->rgb($r, $g, $b);
    }

    /**
     *
     * @param ILess_Node_Color $color1 The first color
     * @param ILess_Node_Color $color2 The second color
     */
    public function softlight(ILess_Node_Color $color1, ILess_Node_Color $color2)
    {
        $color1Rgb = $color1->getRGB();
        $color2Rgb = $color2->getRGB();

        $t = $color2Rgb[0] * $color1Rgb[0] / 255;
        $r = $t + $color1Rgb[0] * (255 - (255 - $color1Rgb[0]) * (255 - $color2Rgb[0]) / 255 - $t) / 255;
        $t = $color2Rgb[1] * $color1Rgb[1] / 255;
        $g = $t + $color1Rgb[1] * (255 - (255 - $color1Rgb[1]) * (255 - $color2Rgb[1]) / 255 - $t) / 255;
        $t = $color2Rgb[2] * $color1Rgb[2] / 255;
        $b = $t + $color1Rgb[2] * (255 - (255 - $color1Rgb[2]) * (255 - $color2Rgb[2]) / 255 - $t) / 255;

        return $this->rgb($r, $g, $b);
    }

    /**
     * Hardlight filter
     *
     * @param ILess_Node_Color $color1 The first color
     * @param ILess_Node_Color $color2 The second color
     */
    public function hardlight(ILess_Node_Color $color1, ILess_Node_Color $color2)
    {
        $color1Rgb = $color1->getRGB();
        $color2Rgb = $color2->getRGB();

        $r = $color2Rgb[0] < 128 ? 2 * $color2Rgb[0] * $color1Rgb[0] / 255 : 255 - 2 * (255 - $color2Rgb[0]) * (255 - $color1Rgb[0]) / 255;
        $g = $color2Rgb[1] < 128 ? 2 * $color2Rgb[1] * $color1Rgb[1] / 255 : 255 - 2 * (255 - $color2Rgb[1]) * (255 - $color1Rgb[1]) / 255;
        $b = $color2Rgb[2] < 128 ? 2 * $color2Rgb[2] * $color1Rgb[2] / 255 : 255 - 2 * (255 - $color2Rgb[2]) * (255 - $color1Rgb[2]) / 255;

        return $this->rgb($r, $g, $b);
    }

    /**
     *
     * @param ILess_Node_Color $color1 The first color
     * @param ILess_Node_Color $color2 The second color
     */
    public function difference(ILess_Node_Color $color1, ILess_Node_Color $color2)
    {
        $color1Rgb = $color1->getRGB();
        $color2Rgb = $color2->getRGB();
        $r = abs($color1Rgb[0] - $color2Rgb[0]);
        $g = abs($color1Rgb[1] - $color2Rgb[1]);
        $b = abs($color1Rgb[2] - $color2Rgb[2]);

        return $this->rgb($r, $g, $b);
    }

    /**
     *
     * @param ILess_Node_Color $color1 The first color
     * @param ILess_Node_Color $color2 The second color
     */
    public function exclusion(ILess_Node_Color $color1, ILess_Node_Color $color2)
    {
        $color1Rgb = $color1->getRGB();
        $color2Rgb = $color2->getRGB();
        $r = $color1Rgb[0] + $color2Rgb[0] * (255 - $color1Rgb[0] - $color1Rgb[0]) / 255;
        $g = $color1Rgb[1] + $color2Rgb[1] * (255 - $color1Rgb[1] - $color1Rgb[1]) / 255;
        $b = $color1Rgb[2] + $color2Rgb[2] * (255 - $color1Rgb[2] - $color1Rgb[2]) / 255;

        return $this->rgb($r, $g, $b);
    }

    /**
     *
     * @param ILess_Node_Color $color1 The first color
     * @param ILess_Node_Color $color2 The second color
     */
    public function average(ILess_Node_Color $color1, ILess_Node_Color $color2)
    {
        $color1Rgb = $color1->getRGB();
        $color2Rgb = $color2->getRGB();
        $r = ($color1Rgb[0] + $color2Rgb[0]) / 2;
        $g = ($color1Rgb[1] + $color2Rgb[1]) / 2;
        $b = ($color1Rgb[2] + $color2Rgb[2]) / 2;

        return $this->rgb($r, $g, $b);
    }

    /**
     *
     * @param ILess_Node_Color $color1 The first color
     * @param ILess_Node_Color $color2 The second color
     */
    public function negation(ILess_Node_Color $color1, ILess_Node_Color $color2)
    {
        $color1Rgb = $color1->getRGB();
        $color2Rgb = $color2->getRGB();
        $r = 255 - abs(255 - $color2Rgb[0] - $color1Rgb[0]);
        $g = 255 - abs(255 - $color2Rgb[1] - $color1Rgb[1]);
        $b = 255 - abs(255 - $color2Rgb[2] - $color1Rgb[2]);

        return $this->rgb($r, $g, $b);
    }

    /**
     * Returns true if passed a color, including keyword colors
     *
     * @param ILess_Node $colorOrAnything
     * @return ILess_Node_Keyword
     */
    public function iscolor(ILess_Node $colorOrAnything)
    {
        return $this->isA($colorOrAnything, 'ILess_Node_Color');
    }

    /**
     * Returns true if a number of any unit
     *
     * @param ILess_Node $numberOrAnything
     * @return ILess_Node_Keyword
     */
    public function isnumber(ILess_Node $numberOrAnything)
    {
        return $this->isA($numberOrAnything, 'ILess_Node_Dimension');
    }

    /**
     * Returns true if it is passed a string
     *
     * @param ILess_Node $stringOrAnything
     * @return ILess_Node_Keyword
     */
    public function isstring(ILess_Node $stringOrAnything)
    {
        return $this->isA($stringOrAnything, 'ILess_Node_Quoted');
    }

    /**
     * Returns true if it is passed keyword
     *
     * @param ILess_Node $numberOrAnything
     * @return ILess_Node_Keyword
     */
    public function iskeyword(ILess_Node $keywordOrAnything)
    {
        return $this->isA($keywordOrAnything, 'ILess_Node_Keyword');
    }

    /**
     * Returns true if it is a string and a url
     *
     * @param mixed $urlOrAnything
     * @return boolean
     */
    public function isurl(ILess_Node $urlOrAnything)
    {
        return $this->isA($urlOrAnything, 'ILess_Node_Url');
    }

    /**
     * Returns true if it is a number and a px
     *
     * @param ILess_Node $urlOrAnything The node to check
     * @return ILess_Node_Keyword
     */
    public function ispixel(ILess_Node $pixelOrAnything)
    {
        if ($this->isA($pixelOrAnything, 'ILess_Node_Dimension') && $pixelOrAnything->unit->is('px')) {
            return new ILess_Node_Keyword('true');
        }

        return new ILess_Node_Keyword('false');
    }

    /**
     * Returns true if it is a number and a %
     *
     * @param ILess_Node $percentageOrAnything
     * @return ILess_Node_Keyword
     */
    public function ispercentage(ILess_Node $percentageOrAnything)
    {
        if ($this->isA($percentageOrAnything, 'ILess_Node_Dimension') && $percentageOrAnything->unit->is('%')) {
            return new ILess_Node_Keyword('true');
        }

        return new ILess_Node_Keyword('false');
    }

    /**
     * Returns true if it is a number and an em
     *
     * @param ILess_Node $emOrAnything
     * @return ILess_Node_Keyword
     */
    public function isem(ILess_Node $emOrAnything)
    {
        if ($this->isA($emOrAnything, 'ILess_Node_Dimension') && $emOrAnything->unit->is('em')) {
            return new ILess_Node_Keyword('true');
        }

        return new ILess_Node_Keyword('false');
    }

    /**
     * returns if a parameter is a number and is in a particular unit
     *
     * @param ILess_Node $node
     * @param ILess_Node $unit The unit to check
     * @return boolean
     */
    public function isunit(ILess_Node $node, ILess_Node $unit = null)
    {
        if ($this->isA($node, 'ILess_Node_Dimension')
            && $node->unit->is((property_exists($unit, 'value') ? $unit->value : $unit))
        ) {
            return new ILess_Node_Keyword('true');
        }

        return new ILess_Node_Keyword('false');
    }

    /**
     * Creates a SVG gradient
     *
     * @param ILess_Node $direction
     * @return ILess_Node_Url
     * @throws ILess_Exception_Compiler If the arguments are invalid
     */
    public function svggradient(ILess_Node $direction /*  $stop1, $stop2, ... */)
    {
        if (func_num_args() < 3) {
            throw new ILess_Exception_Compiler('svg-gradient expects direction, start_color [start_position], [color position,]..., end_color [end_position]');
        }

        $arguments = func_get_args();
        $stops = array_slice($arguments, 1);
        $gradientType = 'linear';
        $rectangleDimension = 'x="0" y="0" width="1" height="1"';
        $useBase64 = true;
        $renderEnv = new ILess_Environment();
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
                throw new ILess_Exception_Compiler("svg-gradient direction must be 'to bottom', 'to right', 'to bottom right', 'to top right' or 'ellipse at center'");
        }

        $returner = '<?xml version="1.0" ?>' .
            '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="100%" height="100%" viewBox="0 0 1 1" preserveAspectRatio="none">' .
            '<' . $gradientType . 'Gradient id="gradient" gradientUnits="userSpaceOnUse" ' . $gradientDirectionSvg . '>';

        for ($i = 0; $i < count($stops); $i++) {
            if (ILess_Node::propertyExists($stops[$i], 'value')
                && $stops[$i]->value
            ) {
                $color = $stops[$i]->value[0];
                $position = $stops[$i]->value[1];
            } else {
                $color = $stops[$i];
                $position = null;
            }

            if (!($color instanceof ILess_Node_Color)
                || (!(($i === 0 || $i + 1 === count($stops)) && $position === null)
                    && !($position instanceof ILess_Node_Dimension))
            ) {
                throw new ILess_Exception_Compiler('svg-gradient expects direction, start_color [start_position], [color position,]..., end_color [end_position]');
            }

            if ($position) {
                $positionValue = $position->toCSS($renderEnv);
            } elseif ($i === 0) {
                $positionValue = '0%';
            } else {
                $positionValue = '100%';
            }

            $alpha = $color->getAlpha(true);
            $returner .= '<stop offset="' . $positionValue . '" stop-color="' .
                $color->getColor()->toString($this->env->compress, false) . '"' .
                ($alpha < 1 ? ' stop-opacity="' . $alpha . '"' : '') . '/>';
        }

        $returner .= '</' . $gradientType . 'Gradient><rect ' . $rectangleDimension . ' fill="url(#gradient)" /></svg>';

        if ($useBase64) {
            $returner = base64_encode($returner);
        }

        $returner = "'data:image/svg+xml" . ($useBase64 ? ";base64" : "") . "," . $returner . "'";

        return new ILess_Node_Url(new ILess_Node_Anonymous($returner));
    }

    protected function doMinmax($isMin, $args)
    {
        switch (count($args)) {
            case 0:
                throw new ILess_Exception_Compiler('One or more arguments required.');
            case 1:
                return $args[0];
        }

        $order = array(); // elems only contains original argument values.
        $values = array(); // key is the unit.toString() for unified tree.Dimension values,
        // value is the index into the order array.

        for ($i = 0, $count = count($args); $i < $count; $i++) {
            $current = $args[$i];
            if (!($current instanceof ILess_Node_Dimension)) {
                $order[] = $current;
                continue;
            }
            $currentUnified = $current->unify();
            $unit = $currentUnified->unit->toString();
            if (!isset($values[$unit])) {
                $values[$unit] = count($order);
                $order[] = $current;
                continue;
            }

            $j = $values[$unit];
            $referenceUnified = $order[$j]->unify();
            if (($isMin && $currentUnified->value < $referenceUnified->value) ||
                (!$isMin && $currentUnified->value > $referenceUnified->value)
            ) {
                $order[$j] = $current;
            }
        }

        if (count($order) == 1) {
            return $order[0];
        }

        foreach ($order as $k => $a) {
            $order[$k] = $a->toCSS($this->env);
        }

        $args = implode(($this->env->compress ? ',' : ', '), $order);

        return new ILess_Node_Anonymous(($isMin ? 'min' : 'max') . '(' . $args . ')');
    }

    /**
     * Checks if the given object is of this class or has this class as one of its parents.
     *
     * @param ILess_Node $node
     * @param string $className The className to check
     * @return ILess_Node_Keyword
     */
    protected function isA(ILess_Node $node, $className)
    {
        if (is_a($node, $className)) {
            return new ILess_Node_Keyword('true');
        }

        return new ILess_Node_Keyword('false');
    }

    /**
     * Clamps the value
     *
     * @param integer $value
     * @return integer
     */
    protected function clamp($value)
    {
        return min(1, max(0, $value));
    }

}

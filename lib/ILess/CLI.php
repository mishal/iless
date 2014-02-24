<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * The CLI handler
 *
 * @package ILess
 * @subpackage CLI
 */
class ILess_CLI extends ILess_Configurable
{
    /**
     * Maximum line length
     *
     */
    const MAX_LINE_LENGTH = 78;

    /**
     * Parsed cli arguments
     *
     * @var array
     */
    protected $cliArguments = array();

    /**
     * Array of default options
     *
     * @var array
     */
    protected $defaultOptions = array(
        'silent' => false,
        'append' => false,
        'no_color' => false
    );

    /**
     * Array of valid options
     *
     * @var array
     */
    protected $validOptions = array(
        // option name => array(description, array of flags)
        'help' => array('Print help (this message) and exit.', array('h')),
        'silent' => array('Suppress output of error messages.', array('s')),
        'no_color' => array('Disable colorized output.', array()),
        'compress' => array('Compress output by removing the whitespace.', array('x')),
        'append' => array('Append the generated CSS to the target file?', array('a')),
        'no_ie_compat' => array('Disable IE compatibility checks.', array()),
        'silent' => array('Suppress output of error messages.', array('s')),
        'source_map' => array('Outputs an inline sourcemap to the generated CSS (or output to filename.map).', array()),
        'source_map_url' => array('The complete url and filename put in the less file.', array()),
        'source_map_base_path' => array('Sets sourcemap base path, defaults to current working directory.', array()),
        'source_map_url' => array('The complete URL and filename of the source map to put to the map.', array()),
        'version' => array('Print version number and exit.', array('v')),
        'dump_line_numbers' => array('Outputs filename and line numbers. TYPE can be either \'comments\', which will output the debug info within comments, \'mediaquery\' that will output the information within a fake media query which is compatible with the SASS format, and \'all\' which will do both.', array())
    );

    /**
     * Array of valid flags
     *
     * @var array
     */
    protected $validFlags = array();

    /**
     * Valid flag
     *
     * @var boolean
     */
    protected $isValid = false;

    /**
     * Current script name
     *
     * @var string
     */
    protected $scriptName;

    /**
     * Current directory
     *
     * @var string
     */
    protected $currentDir;

    /**
     * Stdin aliases
     *
     * @var array
     */
    private $stdAliases = array(
        '−', '–', '-'
    );

    /**
     * Constructor
     *
     * @param array $cliArguments Array of CLI arguments ($argv array)
     * @param string $currentDir Current directory
     */
    public function __construct(array $cliArguments, $currentDir = null)
    {
        $this->scriptName = basename(array_shift($cliArguments));
        $this->cliArguments = $this->parseArguments($cliArguments);
        $this->currentDir = $currentDir ? $currentDir : getcwd();
        parent::__construct($this->convertOptions($this->cliArguments['options']));
    }

    /**
     * Setups the CLI handler
     *
     * @return void
     * @throws InvalidArgumentException If there is an error in the arguments
     */
    protected function setup()
    {
        // convert flags to options
        if ($this->hasFlag('x')) {
            $this->setOption('compress', true);
        }

        if ($this->hasFlag('a')) {
            $this->setOption('append', true);
        }

        if ($this->hasFlag('s')) {
            $this->setOption('silent', true);
        }

        if ($this->hasFlag('v')) {
            $this->setOption('version', true);
        }

        if ($this->hasFlag('h')) {
            $this->setOption('help', true);
        }

        // the handler is valid when:
        // 1) version is requested: --version (option) or -v (flag) is set
        // 2) help is requested: --help or -h
        // 2) a file to be parsed is present
        $this->isValid = count($this->cliArguments['arguments']) || $this->getOption('help') || $this->getOption('version');
    }

    /**
     * Converts option names from dash to underscore. Also converts
     * less.js command options to ILess valid options.
     *
     * @param array $options
     * @return array
     */
    protected function convertOptions(array $options)
    {
        $converted = array();
        foreach ($options as $option => $value) {
            if (strpos($option, '-') !== false) {
                $option = str_replace('-', '_', $option);
            }

            switch ($option) {
                case 'line_numbers':
                    $option = 'dump_line_numbers';
                    break;
            }

            $converted[$option] = $value;
        }

        return $converted;
    }

    /**
     * Is valid?
     *
     * @return boolean
     */
    public function isValid()
    {
        return $this->isValid;
    }

    /**
     * Is flag set?
     *
     * @param string $flag The flag to check
     * @return boolean
     */
    public function hasFlag($flag)
    {
        return in_array($flag, $this->cliArguments['flags']);
    }

    /**
     * Returns the script name
     *
     * @return string
     */
    public function getScriptName()
    {
        return $this->scriptName;
    }

    /**
     * Returns the current directory
     *
     * @return string
     */
    public function getCurrentDir()
    {
        return $this->currentDir;
    }

    /**
     * Runs the task based on the arguments
     *
     * @return true|integer True on success, error code on failure
     */
    public function run()
    {
        if (!$this->isValid()) {
            echo $this->getUsage();
            // return error
            return 1;
        } elseif ($this->getOption('version')) {
            echo ILess_Parser::VERSION.PHP_EOL;

            return;
        } elseif ($this->getOption('help')) {
            echo $this->getUsage();

            return;
        }

        try {
            $parser = new ILess_Parser($this->prepareOptionsForTheParser());

            $toBeParsed = $this->cliArguments['arguments'][0];

            // read from stdin
            if (in_array($toBeParsed, $this->stdAliases)) {
                $content = file_get_contents('php://stdin');
                $parser->parseString($content);
            } else {
                if (!ILess_Util::isPathAbsolute($toBeParsed)) {
                    $toBeParsed = sprintf('%s/%s', $this->currentDir, $toBeParsed);
                }
                $parser->parseFile($toBeParsed);
            }

            $toBeSavedTo = null;
            if (isset($this->cliArguments['arguments'][1])) {
                $toBeSavedTo = $this->cliArguments['arguments'][1];
                if (!ILess_Util::isPathAbsolute($toBeSavedTo)) {
                    $toBeSavedTo = sprintf('%s/%s', $this->currentDir, $toBeSavedTo);
                }
            }

            $css = $parser->getCSS();

            // where to put the css?
            if ($toBeSavedTo) {
                // write the result
                $this->saveCSS($toBeSavedTo, $css, $this->getOption('append'));
            } else {
                echo $css;
            }
        } catch (Exception $e) {
            if (!$this->getOption('silent')) {
                $this->renderException($e);
            }

            return $e->getCode();
        }

        return true;
    }

    /**
     * Prepares options for the parser
     *
     * @return array
     */
    protected function prepareOptionsForTheParser()
    {
        $options = array();

        foreach ($this->getOptions() as $option => $value) {
            switch ($option) {
                case 'source_map':

                    $options['source_map'] = true;
                    $options['source_map_options'] = array();

                    if (is_string($value)) {
                        $options['source_map_options']['write_to'] = $value;
                    }

                    if ($basePath = $this->getOption('source_map_base_path')) {
                        $options['source_map_options']['base_path'] = $basePath;
                    } else {
                        $options['source_map_options']['base_path'] = $this->currentDir;
                    }

                    if ($url = $this->getOption('source_map_url')) {
                        $options['source_map_options']['url'] = $url;
                    } // same as write to
                    elseif (is_string($value)) {
                        $options['source_map_options']['url'] = basename($value);
                    }

                    break;

                // skip options which are processed above or invalid
                case 'source_map_base_path':
                case 'silent':
                case 'no_color':
                case 'append':
                    continue 2;

                case 'no_ie_compat':
                    $options['ie_compat'] = false;
                    continue 2;

                // less.js compatibility options
                case 'line_numbers':
                    $options['dump_line_numbers'] = $value;
                    break;

                default:
                    $options[$option] = $value;
                    break;
            }

            // all is passed, the environment checks if the option is valid
            $options[$option] = $value;
        }

        return $options;
    }

    /**
     * Saves the generated CSS to a given file
     *
     * @param string $targetFile The target file to write to
     * @param string $css The css
     * @param boolean $append Append the CSS?
     * @return boolean|integer The number of bytes that were written to the file, or false on failure.
     * @throws Exception If the file could not be saved
     */
    protected function saveCss($targetFile, $css, $append = false)
    {
        if (@file_put_contents($targetFile, $css, $append ? FILE_APPEND | LOCK_EX : LOCK_EX) === false) {
            throw new Exception(sprintf('Error while saving the data to "%s".', $targetFile));
        }
    }

    /**
     * Returns the CLI usage
     *
     * @return string
     */
    public function getUsage()
    {
        $options = array();
        $max = 0;
        foreach ($this->validOptions as $optionName => $properties) {
            $optionName = str_replace('_', '-', $optionName);
            list($help, $flags) = $properties;
            if ($flags) {
                $option = sprintf('  -%s, --%s', join(',-', $flags), $optionName);
            } else {
                $option = sprintf('  --%s', $optionName);
            }

            // find the largest line
            if ((strlen($option) + 2) > $max) {
                $max = strlen($option) + 2;
            }

            $options[] = array(
                $option,
                $help
            );
        }

        $optionsFormatted = array();
        foreach ($options as $option) {
            list($name, $help) = $option;
            // line will be too long
            if (strlen($name . $help) + 2 > self::MAX_LINE_LENGTH) {
                $help = wordwrap($help, self::MAX_LINE_LENGTH, PHP_EOL . str_repeat(' ', $max + 2));
            }
            $optionsFormatted[] = sprintf(' %-' . $max . 's %s', $name, $help);
        }

        return strtr('
{%signature}

usage: {%script_name} [option option=parameter ...] source [destination]

If source is set to `-` (dash or hyphen-minus), input is read from stdin.

options:
{%options}'.PHP_EOL, array(
            '{%signature}' => $this->getSignature(),
            '{%script_name}' => $this->scriptName,
            '{%options}' => join(PHP_EOL, $optionsFormatted)
        ));
    }

    /**
     * Returns the signature
     *
     * @return string
     * @link http://patorjk.com/software/taag/#p=display&f=Cyberlarge&t=iless
     */
    protected function getSignature()
    {
        return <<<SIGNATURE
 _____        _______ _______ _______
   |   |      |______ |______ |______
 __|__ |_____ |______ ______| ______|
SIGNATURE;

    }

    /**
     * Renders an exception
     *
     * @param Exception $e
     */
    protected function renderException(Exception $e)
    {
        $hasColors = $this->detectColors();

        // excerpt?
        if ($e instanceof ILess_Exception) {

            printf("%s: %s\n", $this->scriptName, $hasColors && !$this->getOption('no_color') ?
                ILess_ANSIColor::colorize($e->toString(false), 'red') : $e->toString(false));

            if ($excerpt = $e->getExcerpt()) {
                $hasColors ?
                    printf("%s\n", $excerpt->toTerminal()) :
                    printf("%s\n", $excerpt->toText());
            }

        } else {
            printf("%s: %s\n", $this->scriptName,
                    $hasColors && !$this->getOption('no_color') ? ILess_ANSIColor::colorize($e->getMessage(), 'red') : $e->getMessage());
        }
    }

    /**
     * Converts the string to plain text.
     *
     * @return string $string The string
     */
    protected function toText($string)
    {
        return strip_tags($string);
    }

    /**
     * Does the console support colors?
     *
     * @return boolean
     */
    protected function detectColors()
    {
        return (getenv('ConEmuANSI') === 'ON' || getenv('ANSICON') !== FALSE ||
               (defined('STDOUT') && function_exists('posix_isatty') && posix_isatty(STDOUT)));
    }

    /**
     * Is silence requested?
     *
     * @return boolean
     */
    protected function isSilent()
    {
        return $this->getOption('silent') || in_array('s', $this->cliArguments['flags']);
    }

    /**
     * Parses the $argv array to a more usefull array
     *
     * @param array $args The $argv array
     * @return array
     * @link http://php.net/manual/en/features.commandline.php#83843
     */
    protected function parseArguments($args)
    {
        $return = array(
            'arguments' => array(),
            'flags' => array(),
            'options' => array()
        );

        while ($arg = array_shift($args)) {
            if (in_array($arg, $this->stdAliases)) {
                $return['arguments'][] = $arg;
            } // Is it a command? (prefixed with --)
            elseif (substr($arg, 0, 2) === '--') {
                $value = '';
                $command = substr($arg, 2);
                // is it the syntax '--option=argument'?
                if (strpos($command, '=') !== false) {
                    list($command, $value) = explode('=', $command, 2);
                }
                $return['options'][$command] = !empty($value) ? $this->convertValue($value) : true;
            } // Is it a flag or a serial of flags? (prefixed with -)
            else if (substr($arg, 0, 1) === '-') {
                for ($i = 1; isset($arg[$i]); $i++) {
                    $return['flags'][] = $arg[$i];
                }
            } else {
                $return['arguments'][] = $arg;
            }
        }

        return $return;
    }

    /**
     * Converts the value. Parses strings like "false" to boolean false,
     * "true" to boolean true
     *
     * @param string $value
     * @return mixed
     */
    protected function convertValue($value)
    {
        switch (strtolower($value)) {
            case '0':
            case 'false':
            case 'no':
                $value = false;
                break;

            case '1':
            case 'true':
            case 'yes':
                $value = true;
                break;
        }

        return $value;
    }

}

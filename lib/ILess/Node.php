<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Base node
 *
 * @package ILess
 * @subpackage node
 */
abstract class ILess_Node
{
    /**
     * The value
     *
     * @var ILess_Node|string
     */
    public $value;

    /**
     * Debug information
     *
     * @var ILess_DebugInfo
     */
    public $debugInfo;

    /**
     * The node type. Each node should define the type
     *
     * @var string
     */
    protected $type;

    /**
     * Current file info
     *
     * @var ILess_FileInfo
     */
    public $currentFileInfo;

    /**
     * Constructor
     *
     * @param mixed $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Returns the node type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Checks if given method exists
     *
     * @param mixed $var The variable name
     * @param string $methodName The method name
     * @return boolean
     */
    public static function methodExists($var, $methodName)
    {
        return is_object($var) && method_exists($var, $methodName);
    }

    /**
     * Checks if given property exists
     *
     * @param mixed $var The variable to check
     * @param string $property The property name
     * @return boolean
     */
    public static function propertyExists($var, $property)
    {
        return is_object($var) && property_exists($var, $property);
    }

    /**
     * Returns debug information for the node
     *
     * @param ILess_Environment $env The environment
     * @param ILess_Node $context The context node
     * @param string $lineSeparator Line separator
     * @return string
     */
    public static function getDebugInfo(ILess_Environment $env, ILess_Node $context, $lineSeparator = '')
    {
        $result = '';
        if ($context->debugInfo && $env->dumpLineNumbers && !$env->compress) {
            switch ((string)$env->dumpLineNumbers) {
                case ILess_DebugInfo::FORMAT_COMMENT;
                    $result = $context->debugInfo->getAsComment();
                    break;

                case ILess_DebugInfo::FORMAT_MEDIA_QUERY;
                    $result = $context->debugInfo->getAsMediaQuery();
                    break;

                case ILess_DebugInfo::FORMAT_ALL;
                case '1':
                    $result = sprintf('%s%s%s',
                        $context->debugInfo->getAsComment(), $lineSeparator,
                        $context->debugInfo->getAsMediaQuery()
                    );
                    break;
            }
        }

        return $result;
    }

    /**
     * Outputs the ruleset rules
     *
     * @param ILess_Environment $env
     * @param ILess_Output $output
     * @param array $rules
     * @return void
     */
    public static function outputRuleset(ILess_Environment $env, ILess_Output $output, array $rules)
    {
        $env->tabLevel++;

        // compression
        if ($env->compress) {
            $output->add('{');
            foreach ($rules as $rule) {
                $rule->generateCSS($env, $output);
            }
            $output->add('}');
            $env->tabLevel--;

            return;
        }

        $tabSetStr = "\n" . str_repeat('  ', $env->tabLevel - 1);
        $tabRuleStr = $tabSetStr . '  ';

        // Non-compressed
        if (!count($rules)) {
            $output->add(' {' . $tabSetStr . '}');

            return;
        }

        $output->add(' {' . $tabRuleStr);
        $first = true;
        foreach ($rules as $rule) {
            if ($first) {
                $rule->generateCSS($env, $output);
                $first = false;
                continue;
            }

            $output->add($tabRuleStr);
            $rule->generateCSS($env, $output);
        }

        $output->add($tabSetStr . '}');
        $env->tabLevel--;
    }

    /**
     * Generate the CSS and put it in the output container
     *
     * @param ILess_Environment $env The environment
     * @param ILess_Output $output The output
     * @return void
     */
    abstract public function generateCSS(ILess_Environment $env, ILess_Output $output);

    /**
     * Compiles the node
     *
     * @param ILess_Environment $env
     * @param array $arguments Array of arguments
     * @param boolean $important Important flag
     * @return ILess_Node
     */
    abstract public function compile(ILess_Environment $env, $arguments = null, $important = null);

    /**
     * Convert to string
     *
     * @return string
     */
    public function toString()
    {
        return (string)$this->value;
    }

    /**
     * Convert to string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Compiles the node to CSS
     *
     * @param ILess_Environment $env
     * @return string
     */
    public function toCSS(ILess_Environment $env)
    {
        $output = new ILess_Output();
        $this->generateCSS($env, $output);

        return $output->toString();
    }

}

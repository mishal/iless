<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Node value
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_Quoted extends ILess_Node implements ILess_Node_ComparableInterface
{
    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'Quoted';

    /**
     * Escaped?
     *
     * @var boolean
     */
    protected $escaped = false;

    /**
     * The content
     *
     * @var string
     */
    public $content;

    /**
     * The quote
     *
     * @var string
     */
    public $quote;

    /**
     * Current index
     *
     * @var integer
     */
    public $index = 0;

    /**
     * Constructor
     *
     * @param string $quotedString Whole string with quotes
     * @param string $string The string without quotes
     * @param boolean $escaped Is the string escaped?
     * @param integer $index Current index
     * @param ILess_FileInfo $currentFileInfo The current file info
     */
    public function __construct($quotedString, $string, $escaped = false, $index = 0, ILess_FileInfo $currentFileInfo = null)
    {
        parent::__construct($string ? $string : '');
        $this->escaped = $escaped;
        $this->quote = $quotedString[0];
        $this->index = $index;
        $this->currentFileInfo = $currentFileInfo;
    }

    /**
     * @see ILess_Node::generateCSS
     */
    public function generateCSS(ILess_Environment $env, ILess_Output $output)
    {
        if (!$this->escaped) {
            $output->add($this->quote, $this->currentFileInfo, $this->index);
        }

        $output->add($this->value);

        if (!$this->escaped) {
            $output->add($this->quote);
        }
    }

    /**
     * Compiles the node
     *
     * @param ILess_Environment $env
     * @return ILess_Node_Quoted
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        $value = $this->value;
        // this is a javascript call, we are not in the browser!
        if (preg_match_all('/`([^`]+)`/', $this->value, $matches)) {
            foreach ($matches as $i => $match) {
                $js = new ILess_Node_Javascript($matches[1], $this->index, true);
                $js = $js->compile($env)->value;
                $value = str_replace($matches[0][$i], $js, $value);
            }
        }
        if (preg_match_all('/@\{([\w-]+)\}/', $value, $matches)) {
            foreach ($matches[1] as $i => $match) {
                $v = new ILess_Node_Variable('@' . $match, $this->index, $this->currentFileInfo);
                $canShorted = $env->canShortenColors;
                $env->canShortenColors = false;
                $v = $v->compile($env);
                $v = ($v instanceof ILess_Node_Quoted) ? $v->value : $v->toCSS($env);
                $env->canShortenColors = $canShorted;
                $value = str_replace($matches[0][$i], $v, $value);
            }
        }

        return new ILess_Node_Quoted($this->quote . $value . $this->quote, $value, $this->escaped, $this->index, $this->currentFileInfo);
    }

    /**
     * Compares with another node
     *
     * @param ILess_Node $other
     * @return integer
     */
    public function compare(ILess_Node $other)
    {
        if (!self::methodExists($other, 'toCSS')) {
            return -1;
        }

        $env = new ILess_Environment();
        $left = $this->toCSS($env);
        $right = $other->toCSS($env);

        if ($left === $right) {
            return 0;
        }

        return $left < $right ? -1 : 1;
    }

}

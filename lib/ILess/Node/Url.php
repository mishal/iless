<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Url node
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_Url extends ILess_Node implements ILess_Node_VisitableInterface
{
    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'Url';

    /**
     * Constructor
     *
     * @param ILess_Node $value
     * @param ILess_FileInfo $currentFileInfo
     */
    public function __construct(ILess_Node $value, ILess_FileInfo $currentFileInfo = null)
    {
        parent::__construct($value);
        $this->currentFileInfo = $currentFileInfo;
    }

    /**
     * Accepts a visit
     *
     * @param ILess_Visitor $visitor
     */
    public function accept(ILess_Visitor $visitor)
    {
        $this->value = $visitor->visit($this->value);
    }

    /**
     * @see ILess_Node
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        $value = $this->value->compile($env);
        $rootPath = isset($this->currentFileInfo) && $this->currentFileInfo->rootPath ? $this->currentFileInfo->rootPath : false;
        if ($rootPath && is_string($value->value) && ILess_Util::isPathRelative($value->value)) {
            $quoteExists = self::propertyExists($value, 'quote');
            if ($quoteExists && empty($value->quote) || !$quoteExists) {
                $rootPath = preg_replace_callback('/[\(\)\'"\s]/', array($this, 'rootPathReplaceCallback'), $rootPath);
            }
            $value->value = $rootPath . $value->value;
        }
        $value->value = ILess_Util::normalizePath($value->value, false);

        return new ILess_Node_Url($value, null);
    }

    /**
     * @see ILess_Node::generateCSS
     */
    public function generateCSS(ILess_Environment $env, ILess_Output $output)
    {
        $output->add('url(');
        $this->value->generateCSS($env, $output);
        $output->add(')');
    }

    /**
     * Replace callback
     *
     * @param $match
     * @return string
     */
    protected function rootPathReplaceCallback($match)
    {
        return '\\' . $match[0];
    }

}

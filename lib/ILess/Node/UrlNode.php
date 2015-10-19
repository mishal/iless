<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Node;

use ILess\Context;
use ILess\FileInfo;
use ILess\Node;
use ILess\Output\OutputInterface;
use ILess\Util;

/**
 * Url node.
 */
class UrlNode extends Node
{
    /**
     * Node type.
     *
     * @var string
     */
    protected $type = 'Url';

    /**
     * @var int
     */
    public $index = 0;

    /**
     * @var bool
     */
    public $isCompiled = false;

    /**
     * Constructor.
     *
     * @param Node $value
     * @param int $index
     * @param FileInfo $currentFileInfo
     * @param bool $isCompiled
     */
    public function __construct(
        Node $value,
        $index = 0,
        FileInfo $currentFileInfo = null,
        $isCompiled = false
    ) {
        parent::__construct($value);

        $this->index = $index;
        $this->currentFileInfo = $currentFileInfo;
        $this->isCompiled = $isCompiled;
    }

    /**
     * Compiles the node.
     *
     * @param Context $context The context
     * @param array|null $arguments Array of arguments
     * @param bool|null $important Important flag
     *
     * @return UrlNode
     */
    public function compile(Context $context, $arguments = null, $important = null)
    {
        $value = $this->value->compile($context);

        if (!$this->isCompiled) {
            $rootPath = isset($this->currentFileInfo) && $this->currentFileInfo->rootPath ? $this->currentFileInfo->rootPath : false;

            if ($rootPath && is_string($value->value) && Util::isPathRelative($value->value)) {
                $quoteExists = self::propertyExists($value, 'quote');
                if ($quoteExists && empty($value->quote) || !$quoteExists) {
                    $rootPath = preg_replace_callback('/[\(\)\'"\s]/', function ($match) {
                        return '\\' . $match[0];
                    }, $rootPath);
                }
                $value->value = $rootPath . $value->value;
            }

            $value->value = Util::normalizePath($value->value, false);

            if ($context->urlArgs) {
                if (!preg_match('/^\s*data:/', $value->value, $matches)) {
                    $delimiter = strpos($value->value, '?') === false ? '?' : '&';
                    $urlArgs = $delimiter . $context->urlArgs;
                    if (strpos($value->value, '#') !== false) {
                        $value->value = str_replace('#', $urlArgs . '#', $value->value);
                    } else {
                        $value->value .= $urlArgs;
                    }
                }
            }
        }

        return new self($value, $this->index, $this->currentFileInfo, true);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCSS(Context $context, OutputInterface $output)
    {
        $output->add('url(');
        $this->value->generateCSS($context, $output);
        $output->add(')');
    }
}

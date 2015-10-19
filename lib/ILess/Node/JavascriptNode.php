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

/**
 * Javascript.
 */
class JavascriptNode extends Node
{
    /**
     * Node type.
     *
     * @var string
     */
    protected $type = 'Javascript';

    /**
     * Escaped?
     *
     * @var bool
     */
    protected $escaped = false;

    /**
     * Current index.
     *
     * @var int
     */
    protected $index = 0;

    /**
     * The javascript expression.
     *
     * @var string
     */
    protected $expression;

    /**
     * Constructor.
     *
     * @param string $expression The javascript expression
     * @param bool $escaped Is the value escaped?
     * @param int $index The index
     * @param FileInfo $currentFileInfo Current file info
     */
    public function __construct($expression, $escaped = false, $index = 0, FileInfo $currentFileInfo = null)
    {
        $this->expression = $expression;
        $this->escaped = $escaped;
        $this->index = $index;
        $this->currentFileInfo = $currentFileInfo;
    }

    /**
     * {@inheritdoc}
     */
    public function generateCSS(Context $context, OutputInterface $output)
    {
        // we do not care about compression flag here, so the developer knows whats going on
        $output->add('/* Sorry, unable to do javascript evaluation in PHP... With men it is impossible, but not with God: for with God all things are possible. Mark 10:27 */');
    }
}

<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Comment
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_Comment extends ILess_Node implements ILess_Node_MarkableAsReferencedInterface
{
    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'Comment';

    /**
     * Current index
     *
     * @var integer
     */
    public $index = 0;

    /**
     * Silent flag
     *
     * @var boolean
     */
    protected $silent = false;

    /**
     * Reference flag
     *
     * @var boolean
     */
    protected $isReferenced = false;

    /**
     * Constructor
     *
     * @param string $value The comment value
     * @param boolean $silent
     */
    public function __construct($value, $silent = false, $index = 0, ILess_FileInfo $currentFileInfo = null)
    {
        parent::__construct($value);
        $this->silent = (boolean)$silent;
        $this->index = $index;
        $this->currentFileInfo = $currentFileInfo;
    }

    /**
     * @see ILess_Node
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        return $this;
    }

    /**
     * Is the comment silent?
     *
     * @param ILess_Environment $env
     * @return boolean
     */
    public function isSilent(ILess_Environment $env)
    {
        $isReference = $this->currentFileInfo && $this->currentFileInfo->reference && !$this->isReferenced;
        $isCompressed = $env->compress && !preg_match('/^\/\*!/', $this->value);

        return $this->silent || $isReference || $isCompressed;
    }

    /**
     * @see ILess_Node::generateCSS
     */
    public function generateCSS(ILess_Environment $env, ILess_Output $output)
    {
        if ($this->debugInfo) {
            $output->add(self::getDebugInfo($env, $this), $this->currentFileInfo, $this->index);
        }
        $output->add(trim($this->value));
    }

    /**
     * Mark the comment as referenced
     *
     * @return void
     */
    public function markReferenced()
    {
        $this->isReferenced = true;
    }

}

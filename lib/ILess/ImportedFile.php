<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Import
 *
 * @package ILess
 * @subpackage import
 */
class ILess_ImportedFile
{
    /**
     * The absolute path or URL
     *
     * @var string
     */
    protected $path;

    /**
     * The content of the file
     *
     * @var string
     */
    protected $content;

    /**
     * The ruleset
     *
     * @var ILess_Node_Ruleset
     */
    protected $ruleset;

    /**
     * Error exception
     *
     * @var Exception
     */
    protected $error;

    /**
     * Last modification of the file as Unix timestamp
     *
     * @var integer
     */
    protected $lastModified;

    /**
     * Constructor
     *
     * @param string $path The absolute path or URL
     * @param string $content The content of the local or remote file
     * @param integer $lastModified The last modification time
     */
    public function __construct($path, $content, $lastModified)
    {
        $this->path = $path;
        $this->content = ILess_Util::normalizeLineFeeds($content);
        $this->lastModified = $lastModified;
    }

    /**
     * Sets the ruleset
     *
     * @param string|ILess_Node_Ruleset|null $ruleset
     * @return ILess_ImportedFile
     */
    public function setRuleset($ruleset)
    {
        $this->ruleset = $ruleset;

        return $this;
    }

    /**
     * Returns the ruleset
     *
     * @return ILess_Node_Ruleset|string|null
     */
    public function getRuleset()
    {
        return $this->ruleset;
    }

    /**
     * Sets an error
     *
     * @param Exception $error
     * @return ILess_ImportedFile
     */
    public function setError(Exception $error)
    {
        $this->error = $error;

        return $this;
    }

    /**
     * Returns the error
     *
     * @return Exception
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Returns the path or URL
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Returns the content of the local or remote file
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Is virtual?
     *
     * @return boolean
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }

}

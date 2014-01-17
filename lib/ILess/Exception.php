<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Base exception
 *
 * @package ILess
 * @subpackage exception
 */
class ILess_Exception extends Exception
{
    /**
     * The current file
     *
     * @var ILess_ImportedFile|ILess_FileInfo
     */
    private $currentFile;

    /**
     * The current parser index
     *
     * @var integer
     */
    private $index;

    /**
     * File editor link. Allows variable holders:
     *
     *  * `%file` or `%f` - current file
     *  * `%line` or `%l` - current line
     *
     * @var string
     */
    protected static $fileEditUrlFormat = 'editor://open?file=%f&line=%l';

    /**
     * Constructor
     *
     * @param string $message
     * @param Exception $previous Previous exception
     * @param integer $index The current parser index
     * @param ILess_FileInfo|ILess_ImportedFile|string $currentFile The file
     * @param integer $code The exception code
     */
    public function __construct($message = null, Exception $previous = null, $index = null, $currentFile = null, $code = 0)
    {
        if (PHP_VERSION_ID < 50300) {
            $this->previous = $previous;
            parent::__construct($message, $code);
        } else {
            parent::__construct($message, $code, $previous);
        }

        $this->currentFile = $currentFile;
        $this->index = $index;
    }

    /**
     * Sets the editor url format
     *
     * @param string $format
     * @return void
     */
    public static function setFileEditorUrlFormat($format)
    {
        self::$fileEditUrlFormat = (string)$format;
    }

    /**
     * Returns the editor url format
     *
     * @return string
     */
    public static function getFileEditorUrlFormat()
    {
        return self::$fileEditUrlFormat;
    }

    /**
     * Returns the file
     *
     * @return ILess_ImportedFile|ILess_FileInfo|null
     */
    public function getCurrentFile()
    {
        return $this->currentFile;
    }

    /**
     * Sets the current file
     *
     * @param ILess_ImportedFile|ILess_FileInfo|string $file
     */
    public function setCurrentFile($file)
    {
        $this->currentFile = $file;
    }

    /**
     * Returns the current index
     *
     * @return integer
     */
    final public function getIndex()
    {
        return $this->index;
    }

    /**
     * Sets index
     *
     * @param integer $index
     */
    public function setIndex($index)
    {
        $this->index = $index;
    }

    /**
     * Returns current line from the file
     *
     * @return integer|false If the index is not present
     */
    private function getErrorLine()
    {
        $line = false;
        if ($this->index !== null && ($this->currentFile)) {
            $content = null;
            if ($this->currentFile instanceof ILess_FileInfo
                && $this->currentFile->importedFile
            ) {
                $content = $this->currentFile->importedFile->getContent();
            } elseif (is_string($this->currentFile) && ILess_Util::isPathAbsolute($this->currentFile)
                && is_readable($this->currentFile)
            ) {
                $content = file_get_contents($this->currentFile);
            }
            if ($content) {
                $line = ILess_Util::getLineNumber($content, $this->index);
            }
        }

        return $line;
    }

    /**
     * Returns file editor link. The link format can be customized.
     *
     * @param ILess_FileInfo $file The current file
     * @param integer $line
     * @return string|void
     * @see setFileEditorUrlFormat
     */
    protected function getFileEditorLink($file, $line = null)
    {
        if ($file instanceof ILess_FileInfo) {
            $path = $file->filename;
            if ($file->importedFile) {
                $path = $file->importedFile->getPath();
            }
            if (strpos($path, '__string_to_parse__') === 0) {
                $path = '[input string]';
            }
        } else {
            $path = $file;
        }

        // when in cli or not accessible via filesystem, don't generate links
        if (PHP_SAPI == 'cli' || !ILess_Util::isPathAbsolute($path)) {
            return $path;
        }

        return sprintf('<a href="%s" class="file-edit">%s</a>', htmlspecialchars(strtr(self::$fileEditUrlFormat, array(
            // allow more formats
            '%f' => $path,
            '%file' => $file,
            '%line' => $line,
            '%l' => $line
        ))), $path);
    }

    /**
     * Converts the exception to string
     *
     * @return string
     */
    public function __toString()
    {
        $string = $this->message;
        if ($this->currentFile) {
            // we have an line from the file
            if (($line = $this->getErrorLine()) !== false) {
                $string = sprintf('%s (%s, line: %s)', $this->message, $this->getFileEditorLink($this->currentFile, $line), $line);
            } else {
                $string = sprintf('%s (%s, line: ?)', $this->message, $this->getFileEditorLink($this->currentFile));
            }
        }

        $previous = null;
        // PHP 5.3
        if (method_exists($this, 'getPrevious')) {
            $previous = $this->getPrevious();
        } // PHP 5.2
        elseif (isset($this->previous)) {
            $previous = $this->previous;
        }

        if ($previous) {
            $string .= sprintf(", caused by %s, %s\n%s", get_class($previous), $previous->getMessage(), $previous->getTraceAsString());
        }

        return $string;
    }

}

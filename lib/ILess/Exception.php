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
     * @var ILess_ImportedFile|ILess_FileInfo|string
     */
    private $currentFile;

    /**
     * The current parser index
     *
     * @var integer
     */
    private $index;

    /**
     * Current line
     *
     * @var integer
     */
    private $currentLine;

    /**
     * Current column
     *
     * @var integer
     */
    private $currentColumn = 0;

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
        $message = $this->formatMessage($message, $previous);

        if (PHP_VERSION_ID < 50300) {
            $this->previous = $previous;
            parent::__construct($message, $code);
        } else {
            parent::__construct($message, $code, $previous);
        }

        $this->currentFile = $currentFile;
        $this->index = $index;

        if ($currentFile)
        {
            list($this->currentLine, $this->currentColumn) = $this->getLocation($currentFile);
        }

    }

    /**
     * Formats the message
     *
     * @param string $message
     * @param Exception $previous Previous exception
     * @return string
     */
    private function formatMessage($message, Exception $previous = null)
    {
        $messageFormatted = $message;
        if ($previous) {
            $messageFormatted .= sprintf(': %s', $previous->getMessage());
        }
        return $messageFormatted;
    }

    /**
     * Returns the current line and column
     *
     * @param ILess_FileInfo|ILess_ImportedFile|string $currentFile The file
     * @return array
     */
    private function getLocation($currentFile)
    {
        $line = null;
        $column = 0;

        return array(
            $line, $column
        );
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
     * @return integer|null If the index is not present
     */
    public function getErrorLine()
    {
        $line = null;
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
     * @param ILess_FileInfo|string $file The current file
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
            if (($line = $this->getErrorLine()) !== null) {
                $string = sprintf('%s in %s, line: %s', $this->message, $this->getFileEditorLink($this->currentFile, $line), $line);
            } else {
                $string = sprintf('%s in %s, line: ?', $this->message, $this->getFileEditorLink($this->currentFile));
            }
        }

        return $string;
    }

    public function prettyPrint()
    {
        return sprintf('<h2>%s</h2><pre>%s</pre>', get_class($this), $this->__toString());
    }

}

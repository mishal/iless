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
     * @var integer|null
     */
    private $errorLine;

    /**
     * Current column
     *
     * @var integer|null
     */
    private $errorColumn;

    /**
     * Excerpt from the string which contains error
     *
     * @var string
     */
    private $excerpt;

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
     * File excerpt line number
     *
     * @var integer|false
     */
    protected static $fileExcerptLineNumber = 3;

    /**
     * Constructor
     *
     * @param string $message The exception message
     * @param integer $index The current parser index
     * @param ILess_FileInfo|ILess_ImportedFile|string $currentFile The file
     * @param Exception $previous Previous exception
     * @param integer $code The exception code
     */
    public function __construct($message = null, $index = null, $currentFile = null, Exception $previous = null, $code = 0)
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

        if ($currentFile && $this->index !== null) {
            $this->updateFileErrorInformation();
        }
    }

    /**
     * Formats the message
     *
     * @param string $message The exception message
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
     * @param integer $index Current position index
     * @param boolean $excerpt Include the string excerpt?
     * @return array
     */
    protected function getLocation($currentFile, $index, $column = null, $excerpt = true)
    {
        $line = $column = $excerptContent = null;
        if ($index !== null && $currentFile) {
            $content = null;
            if ($currentFile instanceof ILess_FileInfo
                && $currentFile->importedFile
            ) {
                $content = $currentFile->importedFile->getContent();
            } elseif (is_string($currentFile) && ILess_Util::isPathAbsolute($currentFile)
                && is_readable($currentFile)
            ) {
                $content = file_get_contents($currentFile);
            }
            if ($content) {
                list($line, $column, $excerptContent) = ILess_Util::getLocation($content, $index, $column, $excerpt);
            }
        }

        return array(
            $line, $column, $excerptContent
        );
    }

    /**
     * Updates the line, column and excerpt
     *
     * @return void
     */
    protected function updateFileErrorInformation()
    {
        // recalculate the location
        list($this->errorLine, $this->errorColumn, $this->excerpt) =
                $this->getLocation($this->currentFile, $this->index, $this->errorColumn, self::getFileExcerptLineNumber());
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
     * Sets the number of lines to display in file excerpts when an exception is displayed
     *
     * @param integer|false $number
     */
    public static function setFileExcerptLineNumber($number)
    {
        self::$fileExcerptLineNumber = $number;
    }

    /**
     * Returns the number of lines to display in file excerpts
     *
     * @return integer|false
     */
    public static function getFileExcerptLineNumber()
    {
        return self::$fileExcerptLineNumber;
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
     * @param integer $index The current index
     */
    public function setCurrentFile($file, $index = null)
    {
        $this->currentFile = $file;
        if ($index !== null) {
            $this->index = $index;
        }
        $this->updateFileErrorInformation();
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
     * Returns the excerpt from the string which contains the error
     *
     * @return string
     */
    final public function getExcerpt()
    {
        return $this->excerpt;
    }

    /**
     * Sets index
     *
     * @param integer $index
     */
    final public function setIndex($index)
    {
        $this->index = $index;
        $this->updateFileErrorInformation();
    }

    /**
     * Returns current line from the file
     *
     * @return integer|null
     */
    final public function getErrorLine()
    {
        return $this->errorLine;
    }

    /**
     * Returns the error column
     *
     * @return integer|null
     */
    final public function getErrorColumn()
    {
        return $this->errorColumn;
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
        return $this->toString();
    }

    /**
     * Converts the exception to string
     *
     * @param boolean $includeExcerpt Include excerpt?
     * @param boolean $html Convert to HTML?
     * @return string
     */
    public function toString($includeExcerpt = true, $html = true)
    {
        $string = array();
        if ($this->currentFile) {
            // we have an line from the file
            if (($line = $this->getErrorLine()) !== null) {
                $string[] = sprintf('%s in %s on line: %s, column: %s', $this->message, $this->getFileEditorLink($this->currentFile, $line), $line, $this->errorColumn);
                if ($includeExcerpt && $this->excerpt)
                {
                    if ($html) {
                        $string[] = sprintf('<pre>%s</pre>', $this->excerpt->toHtml());
                    } else {
                        $string[] = $this->excerpt->toText();
                    }
                }
            } else {
                $string[] = sprintf('%s in %s on line: ?', $this->message, $this->getFileEditorLink($this->currentFile));
            }
        } else {
            $string[] = $this->message;
        }

        return join("\n", $string);
    }

    /**
     *
     * @return string
     */
    public function prettyPrint()
    {
        return sprintf('<h2>%s</h2>%s', get_class($this), $this->__toString());
    }

}

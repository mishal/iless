<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Exception;

use ILess\FileInfo;
use ILess\Util;
use ILess\ImportedFile;

/**
 * Base exception.
 */
class Exception extends \Exception
{
    /**
     * The current file.
     *
     * @var ImportedFile|FileInfo|string
     */
    private $currentFile;

    /**
     * The current parser index.
     *
     * @var int
     */
    private $index;

    /**
     * Current line.
     *
     * @var int|null
     */
    private $errorLine;

    /**
     * Current column.
     *
     * @var int|null
     */
    private $errorColumn;

    /**
     * Excerpt from the string which contains error.
     *
     * @var Util\StringExcerpt
     */
    private $excerpt;

    /**
     * File editor link. Allows variable holders:.
     *
     *  * `%file` or `%f` - current file
     *  * `%line` or `%l` - current line
     *
     * @var string
     */
    protected static $fileEditUrlFormat = 'editor://open?file=%f&line=%l';

    /**
     * File excerpt line number.
     *
     * @var int|false
     */
    protected static $fileExcerptLineNumber = 3;

    /**
     * Constructor.
     *
     * @param string $message The exception message
     * @param int $index The current parser index
     * @param FileInfo|ImportedFile|string $currentFile The file
     * @param \Exception $previous Previous exception
     * @param int $code The exception code
     */
    public function __construct(
        $message = null,
        $index = null,
        $currentFile = null,
        \Exception $previous = null,
        $code = 0
    ) {
        $message = $this->formatMessage($message, $previous);

        parent::__construct($message, $code, $previous);

        $this->currentFile = $currentFile;
        $this->index = $index;

        if ($currentFile && $this->index !== null) {
            $this->updateFileErrorInformation();
        }
    }

    /**
     * Formats the message.
     *
     * @param string $message The exception message
     * @param \Exception $previous Previous exception
     *
     * @return string
     */
    private function formatMessage($message, \Exception $previous = null)
    {
        $messageFormatted = $message;
        if ($previous && $previous->getMessage() !== $message) {
            $messageFormatted .= ': ' . $previous->getMessage();
        }

        return $messageFormatted;
    }

    /**
     * Returns the current line and column.
     *
     * @param FileInfo|ImportedFile|string $currentFile The file
     * @param int $index Current position index
     * @param bool $excerpt Include the string excerpt?
     *
     * @return array
     */
    protected function getLocation($currentFile, $index, $column = null, $excerpt = true)
    {
        $line = $col = $excerptContent = null;
        if ($index !== null && $currentFile) {
            $content = null;
            if ($currentFile instanceof FileInfo
                && $currentFile->importedFile
            ) {
                $content = $currentFile->importedFile->getContent();
            } elseif (is_string($currentFile) && Util::isPathAbsolute($currentFile)
                && is_readable($currentFile)
            ) {
                $content = file_get_contents($currentFile);
            }
            if ($content) {
                list($line, $col, $excerptContent) = Util::getLocation($content, $index, $column, $excerpt);
            }
        }

        return [
            $line,
            $col,
            $excerptContent,
        ];
    }

    /**
     * Updates the line, column and excerpt.
     */
    protected function updateFileErrorInformation()
    {
        // recalculate the location
        list($this->errorLine, $this->errorColumn, $this->excerpt) =
            $this->getLocation($this->currentFile, $this->index, $this->errorColumn, self::getFileExcerptLineNumber());
    }

    /**
     * Sets the editor url format.
     *
     * @param string $format
     */
    public static function setFileEditorUrlFormat($format)
    {
        self::$fileEditUrlFormat = (string) $format;
    }

    /**
     * Returns the editor url format.
     *
     * @return string
     */
    public static function getFileEditorUrlFormat()
    {
        return self::$fileEditUrlFormat;
    }

    /**
     * Sets the number of lines to display in file excerpts when an exception is displayed.
     *
     * @param int|false $number
     */
    public static function setFileExcerptLineNumber($number)
    {
        self::$fileExcerptLineNumber = $number;
    }

    /**
     * Returns the number of lines to display in file excerpts.
     *
     * @return int|false
     */
    public static function getFileExcerptLineNumber()
    {
        return self::$fileExcerptLineNumber;
    }

    /**
     * Returns the file.
     *
     * @return ImportedFile|FileInfo|null
     */
    public function getCurrentFile()
    {
        return $this->currentFile;
    }

    /**
     * Sets the current file.
     *
     * @param ImportedFile|FileInfo|string $file
     * @param int $index The current index
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
     * Returns the current index.
     *
     * @return int
     */
    final public function getIndex()
    {
        return $this->index;
    }

    /**
     * Returns the excerpt from the string which contains the error.
     *
     * @return Util\StringExcerpt|null
     */
    final public function getExcerpt()
    {
        return $this->excerpt;
    }

    /**
     * Sets index.
     *
     * @param int $index
     */
    final public function setIndex($index)
    {
        $this->index = $index;
        $this->updateFileErrorInformation();
    }

    /**
     * Returns current line from the file.
     *
     * @return int|null
     */
    final public function getErrorLine()
    {
        return $this->errorLine;
    }

    /**
     * Returns the error column.
     *
     * @return int|null
     */
    final public function getErrorColumn()
    {
        return $this->errorColumn;
    }

    /**
     * Returns file editor link. The link format can be customized.
     *
     * @param FileInfo|string $file The current file
     * @param int $line
     *
     * @return string|void
     *
     * @see setFileEditorUrlFormat
     */
    protected function getFileEditorLink($file, $line = null)
    {
        if ($file instanceof FileInfo) {
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
        if (PHP_SAPI == 'cli' || !Util::isPathAbsolute($path)) {
            return $path;
        }

        return sprintf('<a href="%s" class="file-edit">%s</a>', htmlspecialchars(strtr(self::$fileEditUrlFormat, [
            // allow more formats
            '%f' => $path,
            '%file' => $path,
            '%line' => $line,
            '%l' => $line,
        ])), $path);
    }

    /**
     * Converts the exception to string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString(true, php_sapi_name() !== 'cli');
    }

    /**
     * Converts the exception to string.
     *
     * @param bool $includeExcerpt Include excerpt?
     * @param bool $html Convert to HTML?
     *
     * @return string
     */
    public function toString($includeExcerpt = true, $html = true)
    {
        $string = [];
        if ($this->currentFile) {
            // we have an line from the file
            if (($line = $this->getErrorLine()) !== null) {
                $string[] = sprintf('%s in %s on line: %s, column: %s', $this->message,
                    $this->getFileEditorLink($this->currentFile, $line), $line, $this->errorColumn);
                if ($includeExcerpt && $this->excerpt) {
                    if ($html) {
                        $string[] = sprintf('<pre>%s</pre>', $this->excerpt->toHtml());
                    } else {
                        $string[] = $this->excerpt->toText();
                    }
                }
            } else {
                $string[] = sprintf('%s in %s on line: ?', $this->message,
                    $this->getFileEditorLink($this->currentFile));
            }
        } else {
            $string[] = $this->message;
        }

        return implode("\n", $string);
    }

    /**
     * @return string
     */
    public function prettyPrint($trace = false)
    {
        $error = sprintf('<h2>%s</h2>%s', get_class($this), $this->__toString());
        if ($trace) {
            $error .= sprintf('<h3>Trace</h3><pre class="exception-trace">%s</pre>', $this->getTraceAsString());
        }

        if ($previous = $this->getPrevious()) {
            $error .= '<h3>Caused by: ' . get_class($previous) . '</h3>';
            $error .= $previous->getMessage();
            $error .= '<pre class="exception-trace">' . $previous->getTraceAsString() . '</pre>';
        }

        return $error;
    }
}

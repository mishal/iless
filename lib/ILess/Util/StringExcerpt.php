<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Util;

use ILess\CLI\ANSIColor;

/**
 * String excerpt.
 */
final class StringExcerpt
{
    /**
     * Array of lines.
     *
     * @var array
     */
    protected $lines = [];

    /**
     * Current line.
     *
     * @var int
     */
    protected $currentLine;

    /**
     * Current column.
     *
     * @var int
     */
    protected $currentColumn;

    /**
     * The line numbers width.
     *
     * @var string
     */
    protected $lineWidth;

    /**
     * Constructor.
     *
     * @param array $lines The array of lines
     * @param int $currentLine The current line
     * @param int $currentColumn The current column
     */
    public function __construct(array $lines, $currentLine, $currentColumn = null)
    {
        $this->lines = $lines;
        $this->currentLine = $currentLine;
        $this->currentColumn = $currentColumn;
        $this->lineWidth = strlen((string) key($this->lines));
    }

    /**
     * Converts the exceprt to colorized string for terminal.
     *
     * @return string
     */
    public function toTerminal()
    {
        $output = '';
        foreach ($this->lines as $lineNumber => $lineContent) {
            if ($lineNumber + 1 == $this->currentLine) {
                // current column will be highlighted
                if ($this->currentColumn !== null) {
                    $output .= ANSIColor::colorize(sprintf("%{$this->lineWidth}s: ", $lineNumber + 1), 'grey+inverse');
                    $output .= ANSIColor::colorize(substr($lineContent, 0, $this->currentColumn - 1), 'grey');
                    $output .= ANSIColor::colorize(substr($lineContent, $this->currentColumn - 1, 1), 'red+bold');
                    $output .= ANSIColor::colorize(substr($lineContent, $this->currentColumn), 'red');
                    $output .= "\n";
                } else {
                    $output .= ANSIColor::colorize(sprintf("%{$this->lineWidth}s: %s\n",
                        $lineNumber + 1, $lineContent), 'red');
                }
            } else {
                $output .= ANSIColor::colorize(sprintf("%{$this->lineWidth}s: %s\n", $lineNumber + 1, $lineContent),
                    'grey');
            }
        }

        return $output;
    }

    /**
     * Converts the except to plain text.
     *
     * @return string
     */
    public function toText()
    {
        return strip_tags($this->toHtml());
    }

    /**
     * Coverts the lines to HTML.
     *
     * @return string The HTML
     */
    public function toHtml()
    {
        $html = '';
        foreach ($this->lines as $lineNumber => $lineContent) {
            if ($lineNumber + 1 == $this->currentLine) {
                // current column will be highlighted
                if ($this->currentColumn !== null) {
                    $html .= sprintf("<span class=\"iless-line iless-current-line\">%{$this->lineWidth}s: %s</span>\n",
                        $lineNumber + 1,
                        substr_replace($lineContent,
                            '<span class="iless-current-column">' . substr($lineContent, $this->currentColumn - 1,
                                1) . '</span>', $this->currentColumn - 1, 1));
                } else {
                    $html .= sprintf("<span class=\"iless-line iless-current-line\">%{$this->lineWidth}s: %s</span>\n",
                        $lineNumber + 1, $lineContent
                    );
                }
            } else {
                $html .= sprintf("<span class=\"iless-line\">%{$this->lineWidth}s: %s</span>\n",
                    $lineNumber + 1, $lineContent);
            }
        }

        return $html;
    }

    /**
     * Converts the object to string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toText();
    }
}

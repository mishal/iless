<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Parser;

use ILess\Exception\ParserException;
use stdClass;

/**
 * Parser input utility.
 */
final class ParserInput
{
    const CHARCODE_SPACE = 32;
    const CHARCODE_TAB = 9;
    const CHARCODE_LF = 10;
    const CHARCODE_CR = 13;
    const CHARCODE_PLUS = 43;
    const CHARCODE_COMMA = 44;
    const CHARCODE_FORWARD_SLASH = 47;
    const CHARCODE_9 = 57;

    /**
     * Current index.
     *
     * @var int
     */
    public $i = 0;

    /**
     * LeSS input string.
     *
     * @var string
     */
    private $input;

    /**
     * Current chunk.
     *
     * @var
     */
    private $j = 0;
    private $saveStack = [];
    private $furthest;
    private $furthestPossibleErrorMessage;
    private $chunks;
    private $current;
    private $currentPos;

    /**
     * @var bool
     */
    public $autoCommentAbsorb = true;

    /**
     * @var array
     */
    public $commentStore = [];

    /**
     * @var bool
     */
    public $finished = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    public function save()
    {
        $this->currentPos = $this->i;
        array_push($this->saveStack, (object) [
            'current' => $this->current,
            'i' => $this->i,
            'j' => $this->j,
        ]);
    }

    /**
     * @param null $possibleErrorMessage
     */
    public function restore($possibleErrorMessage = null)
    {
        if ($this->i > $this->furthest || ($this->i === $this->furthest && $possibleErrorMessage && !$this->furthestPossibleErrorMessage)) {
            $this->furthest = $this->i;
            $this->furthestPossibleErrorMessage = $possibleErrorMessage;
        }

        $state = array_pop($this->saveStack);
        $this->current = $state->current;
        $this->currentPos = $this->i = $state->i;
        $this->j = $state->i;
    }

    public function forget()
    {
        array_pop($this->saveStack);
    }

    /**
     * @param $offset
     *
     * @return bool
     */
    public function isWhiteSpace($offset = 0)
    {
        $pos = $this->i + ($offset);

        // return preg_match('/\s/', @$this->input[$pos]);
        return ctype_space(@$this->input[$pos]);
    }

    public function re($tok)
    {
        if ($this->i > $this->currentPos) {
            $this->current = substr($this->current, $this->i - $this->currentPos);
            $this->currentPos = $this->i;
        }

        $m = preg_match($tok, $this->current, $matches);

        if (preg_last_error() !== PREG_NO_ERROR) {
            throw new ParserException("Error in processing expression $tok!");
        }

        if (!$m) {
            return;
        }

        $this->skipWhitespace(strlen($matches[0]));

        return count($matches) === 1 ? $matches[0] : $matches;
    }

    public function char($tok)
    {
        if (@$this->input[$this->i] !== $tok) {
            return;
        }

        $this->skipWhitespace(1);

        return $tok;
    }

    public function str($tok)
    {
        $tokLength = strlen($tok);
        for ($i = 0; $i < $tokLength; ++$i) {
            if (@$this->input[$this->i + $i] !== $tok[$i]) {
                return;
            }
        }

        $this->skipWhitespace($tokLength);

        return $tok;
    }

    /**
     * @return null|string
     */
    public function quoted()
    {
        $startChar = $this->input[$this->i];

        if ($startChar !== "'" && $startChar !== '"') {
            return;
        }

        $length = strlen($this->input);
        $currentPosition = $this->i;
        for ($i = 1; $i + $currentPosition < $length; ++$i) {
            $nextChar = $this->input[$i + $currentPosition];
            switch ($nextChar) {
                case '\\':
                    $i++;
                    continue;
                case "\r":
                case "\n":
                    break;
                case $startChar:
                    $str = substr($this->input, $currentPosition, $i + 1);
                    $this->skipWhitespace($i + 1);

                    return $str;
            }
        }

        return;
    }

    private function skipWhitespace($length)
    {
        $oldi = $this->i;
        $oldj = $this->j;
        $curr = $this->i - $this->currentPos;
        $endIndex = $this->i + strlen($this->current) - $curr;
        $mem = ($this->i += $length);
        $inp = $this->input;

        for (; $this->i < $endIndex; ++$this->i) {
            $c = ord($inp[$this->i]);
            if ($this->autoCommentAbsorb && $c === self::CHARCODE_FORWARD_SLASH) {
                $nextChar = $inp[$this->i + 1];
                if ($nextChar === '/') {
                    $comment = [
                        'index' => $this->i,
                        'isLineComment' => true,
                    ];
                    $nextNewLine = strpos($inp, "\n", $this->i + 2);
                    if ($nextNewLine === false) {
                        $nextNewLine = $endIndex;
                    }
                    $this->i = $nextNewLine;
                    $comment['text'] = substr($inp, $comment['index'], $this->i - $comment['index']);
                    $this->commentStore[] = $comment;

                    continue;
                } elseif ($nextChar === '*') {
                    $nextStarSlash = strpos($inp, '*/', $this->i + 2);
                    if ($nextStarSlash >= 0) {
                        $comment = [
                            'index' => $this->i,
                            'text' => substr($inp, $this->i, $nextStarSlash + 2 - $this->i),
                        ];
                        $this->i += strlen($comment['text']) - 1;
                        $this->commentStore[] = $comment;
                        continue;
                    }
                }
                break;
            }

            if ($c !== self::CHARCODE_SPACE && ($c !== self::CHARCODE_LF) && ($c !== self::CHARCODE_TAB) && ($c !== self::CHARCODE_CR)) {
                break;
            }
        }

        $this->current = substr($this->current, $length + $this->i - $mem + $curr);
        $this->currentPos = $this->i;

        if (!$this->current) {
            if ($this->j < count($this->chunks) - 1) {
                $this->current = $this->chunks[++$this->j];
                $this->skipWhitespace(0);

                return true;
            }
            $this->finished = true;
        }

        return $oldi !== $this->i || $oldj !== $this->j;
    }

    /**
     * Original less.js function handles string and regexp here, I have to create
     * additional method for regexp, see `peekReg`.
     *
     * @param $tok
     *
     * @return bool
     */
    public function peek($tok)
    {
        $tokLength = strlen($tok);
        for ($i = 0; $i < $tokLength; ++$i) {
            if (@$this->input[$this->i + $i] !== $tok[$i]) {
                return false;
            }
        }

        return true;
    }

    public function peekReg($regexp)
    {
        if (preg_match($regexp, $this->current, $matches)) {
            return $matches;
        }
    }

    public function peekChar($tok)
    {
        return $this->input[$this->i] === $tok;
    }

    public function currentChar()
    {
        return @$this->input[$this->i];
    }

    public function getInput()
    {
        return $this->input;
    }

    public function peekNotNumeric()
    {
        $c = ord($this->input[$this->i]);

        // Is the first char of the dimension 0-9, '.', '+' or '-'
        return ($c > self::CHARCODE_9 || $c < self::CHARCODE_PLUS) || $c === self::CHARCODE_FORWARD_SLASH || $c === self::CHARCODE_COMMA;
    }

    /**
     * @param $string
     */
    public function start($string)
    {
        $this->input = $string;
        $this->i = $this->j = $this->currentPos = $this->furthest = 0;
        $this->chunks = [$string];
        $this->current = $this->chunks[0];
        $this->skipWhitespace(0);
    }

    /**
     * @return stdClass
     */
    public function end()
    {
        $isFinished = $this->i >= strlen($this->input);
        $message = null;
        if ($this->i < $this->furthest) {
            $message = $this->furthestPossibleErrorMessage;
            $this->i = $this->furthest;
        }

        return (object) [
            'isFinished' => $isFinished,
            'furthest' => $this->i,
            'furthestPossibleErrorMessage' => $message,
            'furthestReachedEnd' => $this->i >= strlen($this->input) - 1,
            'furthestChar' => @$this->input[$this->i],
        ];
    }
}

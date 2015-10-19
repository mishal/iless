<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess;

use ILess\Exception\Exception;
use InvalidArgumentException;

/**
 * Import sequencer imports files in order.
 */
final class ImportSequencer
{
    /**
     * @var callable
     */
    private $completeCallback;

    /**
     * @var array
     */
    private $imports = [];

    /**
     * @var array
     */
    private $variableImports = [];

    /**
     * @var int
     */
    private $currentDepth = 0;

    /**
     * Constructor.
     *
     * @param callable $completeCallback
     */
    public function __construct($completeCallback)
    {
        if (!is_callable($completeCallback, null, $callbackName)) {
            throw new InvalidArgumentException(sprintf('The callback %s is not valid callable.', $callbackName));
        }

        $this->completeCallback = $completeCallback;
    }

    /**
     * Tries to load files and when finished, runs the complete callback.
     */
    public function tryRun()
    {
        ++$this->currentDepth;

        try {
            while (true) {
                // normal imports
                while (count($this->imports) > 0) {
                    $importCallback = $this->imports[0];
                    $this->imports = array_slice($this->imports, 1);
                    call_user_func($importCallback);
                }

                if (count($this->variableImports) === 0) {
                    break;
                }

                $variableImportCallback = $this->variableImports[0];
                $this->variableImports = array_slice($this->variableImports, 1);

                call_user_func($variableImportCallback);
            }
        } catch (Exception $e) {
        }

        --$this->currentDepth;

        if ($this->currentDepth === 0) {
            call_user_func($this->completeCallback);
        }
    }

    /**
     * Adds an import which path contains some variables.
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function addVariableImport(callable $callback)
    {
        $this->variableImports[] = $callback;

        return $this;
    }

    /**
     * Adds an import.
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function addImport(callable $callback)
    {
        $this->imports[] = $callback;

        return $this;
    }
}

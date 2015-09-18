<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use ILess\Exception\Exception;
use ILess\Parser;

/**
 * Parsing tests
 *
 * @package ILess
 * @subpackage test
 */
class Test_Parser_ExceptionTest extends Test_TestCase
{
    protected $knownDiffs = [
        'color-invalid-hex-code.txt' => [
            'column' => 32 // we are using different regexp to "catch" color nodes
        ],
        'color-invalid-hex-code2.txt' => [
            'column' => 32 // we are using different regexp to "catch" color nodes
        ],
        'import-missing.txt' => [
            'message' => "'file-does-not-exist.less' wasn't found"
            // less.js uses paths to search in, we are using importers
        ],
    ];

    protected $skipTests = [
        'javascript-error.less', // we cannot handle javascript
        'javascript-undefined-var.less', // we cannot handle javascript
    ];

    protected function createParser($options = [])
    {
        return new Parser($options);
    }

    /**
     * @dataProvider getCompilationData
     */
    public function testCompilation($lessFile, $exception, array $options = [])
    {
        if (in_array(basename($lessFile), $this->skipTests)) {
            return;
        }

        list($exceptionClass, $message, $line, $column) = $this->getTestException($exception);

        echo "Testing exception ".basename($exception)."\n";

        $parser = $this->createParser($options);

        try {
            $parser->parseFile($lessFile);
            $parser->getCSS();
        } catch (Exception $e) {

            // we tolerate case diffs
            $messageThrown = strtolower(trim($e->getMessage(), '.,'));
            $messageExpected = strtolower(trim($message, '.,'));

            $this->assertEquals($messageExpected, $messageThrown, 'The exception message matches');
            // $this->assertEquals($exceptionClass, get_class($e), 'The exception class matches');
            $this->assertEquals($line, $e->getErrorLine(), 'The line matches');
            $this->assertEquals($column, $e->getErrorColumn(), 'The column matches');

            return;
        } catch (Exception $e) {
            $this->diag('Unhandled exception while parsing file: '.$lessFile);
            $this->diag(sprintf('%s: %s (file %s, line: %s)', get_class($e), $e->getMessage(), $e->getFile(),
                $e->getLine()));
            $this->fail('Invalid exception has been thrown.');
        }

        $this->fail('An expected exception has not been raised.');
    }

    protected function getTestException($exceptionFile)
    {
        $content = file_get_contents($exceptionFile);

        // less.js convert to iless
        $parts = explode("\n", $content, 1);
        preg_match('/^(SyntaxError|ParseError|ArgumentError|OperationError|FileError|NameError|RuntimeError): (.+)/',
            $parts[0], $match);

        $class = $match[1];
        $message = $match[2];

        // get line and column
        // on line null, column 0
        $line = null;
        $column = 0;

        preg_match('/on line ([\d|null]+)/', $message, $match);
        if (isset($match[1])) {
            $line = $match[1];
        }

        if ($line == 'null') {
            $line = null;
        }

        preg_match('/column (\d+):/', $message, $match);
        if (isset($match[1])) {
            $column = (integer)$match[1];
        }

        if (($pos = strpos($message, ' in {')) !== false) {
            $message = substr($message, 0, $pos);
        }

        $exceptionClass = 'Exception_'.$class;

        /*
        switch ($class) {
            case 'SyntaxError':
            case 'OperationError':
            case 'NameError':
            case 'RuntimeError':
                $exceptionClass = 'Exception_SyntaxError';
                break;
            case 'ArgumentError':
                $exceptionClass = 'ILess\ILess\Exception\Exception\FunctionException';
                break;
            case 'FileError':
                $exceptionClass = 'ILess\ILess\Exception\Exception\ImportException';
                break;
        }*/


        // handle known differences
        if (isset($this->knownDiffs[basename($exceptionFile)])) {
            $knownDiff = $this->knownDiffs[basename($exceptionFile)];
            if (array_key_exists('line', $knownDiff)) {
                $column = $knownDiff['line'];
            }
            if (array_key_exists('column', $knownDiff)) {
                $column = $knownDiff['column'];
            }
            if (array_key_exists('message', $knownDiff)) {
                $message = $knownDiff['message'];
            }
        }

        $message = str_replace([
            '{pathhref}',
            '{404status}',
        ], '', $message);

        return [
            $exceptionClass,
            $message,
            $line,
            $column,
        ];
    }

    public function getCompilationData()
    {
        $fixturesDir = dirname(__FILE__).'/_fixtures/less.js/less/errors';
        $data = array_map(null, glob($fixturesDir.'/*.less'), glob($fixturesDir.'/*.txt'));

        return $data;
    }

}

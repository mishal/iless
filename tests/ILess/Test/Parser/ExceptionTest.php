<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Parsing tests
 *
 * @package ILess
 * @subpackage test
 */
class ILess_Test_Parser_ExceptionTest extends ILess_Test_TestCase
{
    protected function createParser($options = array())
    {
        if (!count($options)) {
            $options = array(
                'strict_math' => true,
                'strict_units' => true
            );
        }

        $env = new ILess_Environment($options, new ILess_FunctionRegistry());
        $importer = new ILess_Importer($env, array(
            new ILess_Importer_FileSystem()
        ), new ILess_Cache_None());

        return new ILess_Parser_Core($env, $importer);
    }

    /**
     * @dataProvider getCompilationData
     */
    public function testCompilation($lessFile, $exception, array $options = array())
    {
        if(in_array(basename($lessFile), array(
             // FIXME: leave for now (problematic or not implemented)
            'css-guard-default-func.less', // not implemented by the parser
            'javascript-error.less', // leave this forever
            'javascript-undefined-var.less', // leave this forever
            'mixins-guards-default-func-1.less', // not implemented by the parser yet
            'mixins-guards-default-func-2.less', // not implemented by the parser yet
            'mixins-guards-default-func-3.less', // not implemented by the parser yet
            'multiple-guards-on-css-selectors2.less', // FIXME: PARSER DOES NOT THROW ANY EXCPETION!
            'property-interp-not-defined.less', // not implemented by the parser yet
        ))) {
            $this->diag('Skipped test: '. $lessFile);
            return;
        }

        list($exceptionClass, $message, $line, $column) = $this->getTestException($exception);

        $parser = $this->createParser();

        try {
            $parser->parseFile($lessFile);
            $parser->getCSS();
        }
        catch (ILess_Exception $e) {

            // we tolerate case diffs
            $messageThrown = strtolower(trim($e->getMessage(), '.'));
            $messageExpected = strtolower(trim($message, '.'));

            $this->assertEquals($messageExpected, $messageThrown, 'The exception message matches');
            $this->assertEquals($exceptionClass, get_class($e), 'The exception class matches');
            $this->assertEquals($line, $e->getErrorLine(), 'The line matches');
            $this->assertEquals($column, $e->getErrorColumn(), 'The column matches');

            return;
        }
        catch(Exception $e)
        {
            $this->diag('Unhandled exception while parsing file: ' . $lessFile);
            $this->diag(sprintf('%s: %s (file %s, line: %s)', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));
            $this->fail('Invalid exception has been thrown.');
        }

        $this->fail('An expected exception has not been raised.');
    }

    protected function getTestException($exceptionFile)
    {
        $content = file_get_contents($exceptionFile);

        // less.js convert to iless
        $parts = explode("\n", $content, 1);
        preg_match('/^(SyntaxError|ParseError|ArgumentError|OperationError|FileError|NameError|RuntimeError): (.+)/', $parts[0], $match);

        $class = $match[1];
        $message = $match[2];

        // get line and column
        // on line null, column 0
        $line = null;
        $column = 0;

        preg_match('/on line ([\d|null]+)/', $message, $match);
        if (isset($match[1]))
        {
            $line = $match[1];
        }

        if ($line == 'null') {
            $line = null;
        }

        preg_match('/column (\d+):/', $message, $match);
        if (isset($match[1]))
        {
            $column = (integer)$match[1];
        }

        if (($pos = strpos($message, ' in {')) !== false) {
            $message = substr($message, 0, $pos);
        }

        $exceptionClass = 'ILess_Exception_Parser';

        switch ($class) {
            case 'SyntaxError':
            case 'OperationError':
            case 'NameError':
            case 'RuntimeError':
                $exceptionClass = 'ILess_Exception_Compiler';
                break;
            case 'ArgumentError':
                $exceptionClass = 'ILess_Exception_Function';
                break;
            case 'FileError':
                $exceptionClass = 'ILess_Exception_Import';
                break;
        }

        //
        $message = str_replace(array(
            '{pathhref}', '{404status}'
        ), '', $message);

        return array(
            $exceptionClass, $message, $line, $column
        );
    }

    public function getCompilationData()
    {
        $fixturesDir = dirname(__FILE__) . '/_fixtures/less.js/less/errors';
        $data = array_map(null, glob($fixturesDir . '/*.less'), glob($fixturesDir . '/*.txt'));
        return $data;
    }

}

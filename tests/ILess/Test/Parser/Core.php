<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class ILess_Test_Parser_Core extends ILess_Parser_Core
{
    public function __construct(ILess_Environment $env, ILess_Importer $importer)
    {
        parent::__construct($env, $importer);
        ILess_Math::setup(16);
        ILess_UnitConversion::setup();
    }

    public function testParseDirective($string)
    {
        $this->setStringToBeParsed($string);

        return parent::parseDirective();
    }

    public function testParseComment($string)
    {
        $this->setStringToBeParsed($string);

        return parent::parseComment();
    }

    public function testParseEntitiesQuoted($string)
    {
        $this->setStringToBeParsed($string);

        return parent::parseEntitiesQuoted();
    }

    public function testParseEntitiesKeyword($string)
    {
        $this->setStringToBeParsed($string);

        return parent::parseEntitiesKeyword();
    }

    public function testParseEntity($string)
    {
        $this->setStringToBeParsed($string);

        return parent::parseEntity();
    }

    public function testParseMixinDefinition($string)
    {
        $this->setStringToBeParsed($string);

        return parent::parseMixinDefinition();
    }

    public function testParseSelector($string)
    {
        $this->setStringToBeParsed($string);

        return parent::parseSelector();
    }

    public function testParseElement($string)
    {
        $this->setStringToBeParsed($string);

        return parent::parseElement();
    }

    public function testParseColor($string)
    {
        $this->setStringToBeParsed($string);

        return parent::parseEntitiesColor();
    }

    public function testParseMedia($string)
    {
        $this->setStringToBeParsed($string);

        return parent::parseMedia();
    }

}
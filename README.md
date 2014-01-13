# ILess - the LESS processor for PHP

![ILess](logo.png)

The **dynamic** stylesheet language. For more info about the language see the offical website: <http://lesscss.org>

## What is This?

ILess is a **PHP port** of the official LESS processor written in Javascript. Most of the code structure remains the same, which should allow for fairly easy updates in the future.

## Build Status

[![Build Status](https://travis-ci.org/mishal/iless.png?branch=master)](https://travis-ci.org/mishal/iless)

## Getting Started

To use ILess in your project you can:

  - Install it using Composer ([more info on Packagist](https://packagist.org/packages/mishal/iless))
  - [Download the latest release](https://github.com/mishal/iless/archive/master.zip)
  - Clone the repository: `git clone git://github.com/mishal/iless.git`

## Requirements

To run ILess you need:

 * `PHP >= 5.2`  (yes, you read right)
 * `bcmath` extension installed

## Feature Highlights

 * Allows to register **custom file importers** (from database, ...)
 * Allows to setup **import directories** so search imports for
 * Allows to define **custom LESS functions** with PHP callbacks
 * Generates **source maps** (usefull for debugging the generated CSS)
 * Generates debugging information with SASS compatible information and/or simple comments
 * Supports output filters
 * Allows caching of the precompiled files
 * Is **unit tested** using PHPUnit
 * Has well documented API - see the generated [API docs](http://apigen.juzna.cz).

## Usage

For example usage check the `examples` folder in the source files.

    <?php
    // setup autoloading

    // 1) when installed with composer
    require 'vendor/autoload.php';

    // 2) when installed manually
    // require_once 'lib/ILess/Autoloader.php';
    // ILess_Autoloader::register();

    $parser = new ILess_Parser(array(
      // array of options
      // import dirs are search first
      'import_dirs' => array(
      // PHP 5.2
      // dirname(__FILE__) . '/less/import'
      __DIR__ . '/less/import'
      ))
	);

	// parses the file
    $parser->parseFile('screen.less');

    // parse string
    $parser->parseString('body { color: @color; }');

    // assing variables via the API
    $parser->setVariables(array(
      'color' => '#000000'
    ));
    
    // Add a custom function
    $parser->addFunction('superdarken', function(\ILess_FunctionRegistry $registry, \ILess_Node_Color $color) {
      return $registry->call('darken', [$color, new \Iless_Node_Dimension(80, '%')]);
    });

    $css = $parser->getCSS();

## Issues

Before opening any issue, please search for [existing issues](https://github.com/mishal/iless/issues). After that if you find a bug or would like to make feature request, please open a new issue. Please *always* create a unit test.

The `master` branch should contain only stable code, while the `develop` branch, as the name suggests, is for development.

 * [List of issues](https://github.com/mishal/iless/issues)

## Why Another Less PHP Processor?

There is a lack of **maintanable** PHP version of the LESS compiler which would compile the favourite LESS front-end frameworks like Twitter Bootstrap (version 3 and 2).

Why porting the JS version to PHP? Why not? The main reason is to follow new language features with less effort by simly porting the code to PHP.

## Disclaimer & About

iless = I less: **He must increase, but I must decrease.** [[John 3:30](https://www.bible.com/bible/37/jhn.3.30.ceb)]

I was born in non believers family and was raised as a atheist. When I was 30 years old my girlfriend came home and said that she is now a Christian and she believes in God! **What a shock for me**! I thought that she must be totally crazy!

I decided to do a heavy investigation on that topic a bring some proofs to her, **that there is no God**. I said to myself that I will search without any prejudices no matter what the result will be. In about 1 year I checked the topics which I thought **would bring any evidence of God's existence** - the science.

I was very suprised to see that there is a plenty of evidence of a design in things around me, even in me. The **DNA is a programming language**, but a bit complicated than only 1 and 0 that my computer uses. I know that no computer app can just appear or develop by chance even if I will have a rest of 1 billion years.

I came to a **revolutionary conclusion** for me. **God exists!** I was 30 year blind!

My girlfriend told me that God loves me and **wants a relationship with me**. That Jesus died for me and is waiting for my answer to his invitation. I said yes!

Now I'm God's adopted son saved for the eternity. God takes care of me. He freed me from drug addition and other ugly thinks.

I know that [God loves to you](http://bible.com/37/1jn.4.9-10.ceb) (is written in his Word) and [wants you to save you too](http://bible.com/37/act.2.21.ceb). Invite Jesus to your life!

**Note**: This is **not a religion!** But a relationship with living God.

### Upgrade your life

  * **Agree and accept the license** which God offers. There is no *accept* button, but you have to do it by faith. Accept that Jesus died for you and took the punishment instead of you.
  * **Repent from your sins**. Sin is everything that violates the law given by God (not loving God, stealing, cheating, lying... [See the full list](https://www.bible.com/bible/37/deu.30.15-16.ceb).
  * **Ask Jesus** for [forgiveness] and to become your personal lord and savior (http://bible.com/37/mrk.2.5-12.ceb).

If you did the steps above with your whole heart you are now a **[new creation](http://bible.com/37/2co.5.17.ceb)**. You belong to God's family and you have now an **eternal life**. You have been redeemed from the eternal punishment - from the outer darkness where is weeping and gnashing of teeth.

**Read the Bible**, and ask God to speak with you and to lead you to a (*true*) [Church](http://www.ibethel.org/). There is a lot of so called Churches around, but they to do not teach nor live the Bible. Note: I do not have any connections with Bethel Church.

## Credits

The work is based on the code by [Matt Agar](https://github.com/agar), [Martin Jantošovič](https://github.com/Mordred) and [Josh Schmidt](https://github.com/oyejorge). Source maps code based on [phpsourcemaps](https://github.com/bspot/phpsourcemaps) by [bspot](https://github.com/bspot).

[All contributors](https://github.com/mishal/iless/wiki/Contributors) are listed on separate [wiki page](https://github.com/mishal/iless/wiki/Contributors). 
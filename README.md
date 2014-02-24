# ILess - the LESS processor for PHP

![ILess](logo.png)

The **dynamic** stylesheet language. For more info about the language see the offical website: <http://lesscss.org>

## What is This?

ILess is a **PHP port** of the official LESS processor written in Javascript. Most of the code structure remains the same, which should allow for fairly easy updates in the future.

## Build Status

[![Build Status](https://travis-ci.org/mishal/iless.png?branch=master)](https://travis-ci.org/mishal/iless)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/mishal/iless/badges/quality-score.png?s=513af3953e6974968feac9c445db32489c62c35f)](https://scrutinizer-ci.com/g/mishal/iless/)

## Getting Started

To use ILess in your project you can:

  - Install it using Composer ([more info on Packagist](https://packagist.org/packages/mishal/iless))
  - [Download the latest release](https://github.com/mishal/iless/archive/master.zip)
  - Clone the repository: `git clone git://github.com/mishal/iless.git`

## Requirements

To run ILess you need:

 * `PHP >= 5.2`
 * `bcmath` extension installed

## Feature Highlights

 * Allows to register **custom file importers** (from database, ...)
 * Allows to setup **import directories** so search imports for
 * Allows to define **custom LESS functions** with PHP callbacks
 * Generates **source maps** (usefull for debugging the generated CSS)
 * Generates debugging information with SASS compatible information and/or simple comments
 * Supports output filters
 * Allows caching of the precompiled files and the generated CSS
 * Is **unit tested** using PHPUnit
 * Has developer friendly exception messages with location of the error and file excerpt (output is colorized when used by command line)
 * Has well documented API - see the generated [API docs](http://apigen.juzna.cz).

## Usage

### Basic usage

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

    // assign variables via the API
    $parser->setVariables(array(
      'color' => '#000000'
    ));

    // Add a custom function
    $parser->addFunction('superdarken', function(\ILess_FunctionRegistry $registry, \ILess_Node_Color $color) {
      return $registry->call('darken', [$color, new \Iless_Node_Dimension(80, '%')]);
    });

    $css = $parser->getCSS();

### Using the cache

    // setup the parser to use the cache
    $parser = new ILess_Parser(array(), new ILess_Cache_FileSystem(array(
      'cache_dir' => sys_get_temp_dir(),
      'ttl' => 86400 // the lifetime of cached files in seconds (1 day by default)
    ));

The parser will use the cache driver to save *serialized data* from parsed files and strings and to save *generated CSS*.
The `ttl` option allows to set the lifetime of the cached files. The **change of the imported files will regenerate the cache** for those files automatically.

The cache of the CSS will be different if you assign different variables through the API (See the example above how to do it)
and for different options like `compress`, ....

The generated CSS will be also cached for the `ttl` seconds. The change in the imported files (variables, and options) will cause the CSS regeneration.

**Note**: The generated cached files can be copied in the cloud, the modification time of the imported files does not depend on the modification time of the cache files.

### Custom cache driver

If you would like to cache the parsed data and generated CSS somewhere else (like `memcached`, `database`) simple create your own driver by
implementing `ILess_CacheInterface`. See the [lib/ILess/CacheInterface.php](./lib/ILess/CacheInterface.php).

For more examples check the [examples](./examples) folder in the source files.

## Command line usage

To compile the LESS files (or input from `stdin`) you can use the CLI script (located in `bin` directory).

## Usage from NetBeans IDE

To compile the LESS files from your NetBeans IDE (*version 7.4 is required*) you need to configure the path to the `iless` executable.
 [How to setup the compilation](http://wiki.netbeans.org/NetBeans_74_NewAndNoteworthy#Compilation_on_Save).

You have to configure the less path to point to `bin/iless`. On Windows use`bin/iless.cmd`.

## Usage from PhpStorm IDE

To compile the LESS files from your PhpStorm IDE you need to configure the `File watcher` for `.less` files. [See the manual](http://www.jetbrains.com/phpstorm/webhelp/transpiling-sass-less-and-scss-to-css.html#d151302e621) how to do it.
You have to configure the `program` option to point to `bin/iless`. On Windows use `bin/iless.cmd`.

**Note**: See additional command line options for the parser below.

## Examples

Parse the `my.less` and save it to `my.css` with compression enabled.

    $ php bin\iless my.less my.css --compress

Parse input from `stdin` and save it to a file `my.css`.

	$ php bin\iless - my.css

## Usage and available options

     _____        _______ _______ _______
       |   |      |______ |______ |______
     __|__ |_____ |______ ______| ______|

    usage: iless [option option=parameter ...] source [destination]

    If source is set to `-` (dash or hyphen-minus), input is read from stdin.

    options:
       -h, --help               Print help (this message) and exit.
       -s, --silent             Suppress output of error messages.
       --no-color               Disable colorized output.
       -x, --compress           Compress output by removing the whitespace.
       -a, --append             Append the generated CSS to the target file?
       --no-ie-compat           Disable IE compatibility checks.
       --source-map             Outputs an inline sourcemap to the generated CSS (or output to filename.map).
       --source-map-url         The complete URL and filename of the source map to put to the map.
       --source-map-base-path   Sets sourcemap base path, defaults to current working directory.
       -v, --version            Print version number and exit.
       --dump-line-numbers      Outputs filename and line numbers. TYPE can be either 'comments', which will
                                output the debug info within comments, 'mediaquery' that will output the
                                information within a fake media query which is compatible with the SASS
                                format, and 'all' which will do both.

## Issues

Before opening any issue, please search for [existing issues](https://github.com/mishal/iless/issues). After that if you find a bug or would like to make feature request, please open a new issue. Please *always* create a unit test.

The `master` branch should contain only stable code, while the `develop` branch, as the name suggests, is for development.

 * [List of issues](https://github.com/mishal/iless/issues)

## Contributing

Please read [contributing guide](./CONTRIBUTING.md).

## Why Another Less PHP Processor?

There is a lack of **maintanable** PHP version of the LESS compiler which would compile the favourite LESS front-end frameworks like Bootstrap (version 3 and 2).

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

# ILess - the LESS processor for PHP

![ILess](logo.png)

The **dynamic** stylesheet language. For more info about the language see the offical website: <http://lesscss.org>

## What is This?

ILess is a **PHP port** of the official LESS processor written in Javascript. Most of the code structure remains the same, which should allow for fairly easy updates in the future. 

## Build Status

[![Build Status](https://travis-ci.org/mishal/iless.png?branch=master)](https://travis-ci.org/mishal/iless)

## Getting Started

To use ILess in your project you can:

  - Install it using Composer
  - [Download the latest release](https://github.com/mishal/iless/archive/master.zip)
  - Clone the repository: `git clone git://github.com/mishal/iless.git`

## Requirements

To run ILess you need:

 * `PHP > 5.2`  (yes, you read right)
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

## Issues

Before opening any issue, please search for [existing issues](https://github.com/mishal/iless/issues). After that if you find a bug or would like to make feature request, please open a new issue. Please *always* create a unit test.

 * [List of issues](https://github.com/mishal/iless/issues)

## Why Another Less PHP Processor?

There is a lack of **maintanable** PHP version of the LESS compiler which would compile the favourite LESS front-end frameworks like Twitter Bootstrap (version 3 and 2).

Why porting the JS version to PHP? Why not? The main reason is to follow new language features with less effort by simly porting the code to PHP.

## Disclaimer & About

**He must increase, but I must decrease.** [[John 3:30](https://www.bible.com/bible/37/jhn.3.30.ceb)]

I was born in non believers family and was raised as a atheist. When I was 30 years old my girlfriend came home and said that she is now a Christian and she believes in God! **What a shock for me**! I thought that she must be totally crazy! 

I decided to do a heavy investigation on that topic a bring some proofs to her, **that there is no God**. I said to myself that I will search without any prejudices no matter what the result will be. In about 1 year I checked the topics which I thought **would bring any evidence of God's existence** - the science.

I was very suprised to see that there is a plenty of evidence of a design in things around me, even in me. The **DNA is a programming language**, but a bit complicated than only 1 and 0 that my computer uses. I know that no computer app can just appear or develop by chance even if I will have a rest of 1 billion years.

I came to a **revolutionary conclusion** for me. **God exists!** I was 30 year blind!

My girlfriend told me that God loves me and **wants a relationship with me**. That Jesus died for me and is waiting for my answer to his invitation. I said yes!

Now I'm God's adopted son saved for the eternity. God takes care of me. He freed me from drug addition and other ugly thinks.

I know that [God loves to you](http://bible.com/37/1jn.4.9-10.ceb) (is written in his Word) and [wants you to save you too](http://bible.com/37/act.2.21.ceb). Invite Jesus to your life! 

Note: This is **not a religion!** But a relationship with living God.

## Credits

The work is based on the code by Matt Agar, Martin Jantosovic and Josh Schmidt. [All contributors](https://github.com/mishal/iless/wiki/Contributors) are listed on separate [wiki page](https://github.com/mishal/iless/wiki/Contributors).

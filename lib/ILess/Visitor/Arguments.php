<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Visitor arguments
 *
 * @package ILess
 * @subpackage visitor
 */
class ILess_Visitor_Arguments
{
    /**
     * Visit deeper flag
     *
     * @var boolean
     */
    public $visitDeeper = true;

    /**
     * Constructor
     *
     * @param array $arguments
     */
    public function __construct($arguments = array())
    {
        foreach ($arguments as $argument => $value) {
            $this->$argument = $value;
        }
    }

}

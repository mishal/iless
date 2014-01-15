<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Dimension unit
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_DimensionUnit extends ILess_Node
{
    /**
     * Length regular expression
     *
     */
    const LENGTH_REGEXP = '/px|em|%|in|cm|mm|pc|pt|ex/';

    /**
     * Numerator
     *
     * @var array
     */
    public $numerator = array();

    /**
     * Denominator
     *
     * @var array
     */
    public $denominator = array();

    /**
     * The backup unit to use when unit is empty
     *
     * @var string
     */
    public $backupUnit;

    /**
     * Constructor
     *
     * @param ILess_Node|array $numerator
     * @param ILess_Node|array $denominator
     * @param string $backupUnit
     */
    public function __construct($numerator = array(), $denominator = array(), $backupUnit = null)
    {
        $this->numerator = is_array($numerator) ? $numerator : array($numerator);
        $this->denominator = is_array($denominator) ? $denominator : array($denominator);
        $this->backupUnit = $backupUnit;
    }

    /**
     * Converts to string
     *
     * @return string
     */
    public function toString()
    {
        $returnStr = implode('*', $this->numerator);
        foreach ($this->denominator as $d) {
            $returnStr .= '/' . $d;
        }

        return $returnStr;
    }

    /**
     * Compares two units
     *
     * @param ILess_Node_DimensionUnit $other
     * @return integer| 0 or -1
     */
    public function compare(ILess_Node_DimensionUnit $other)
    {
        return $this->is($other->toString()) ? 0 : -1;
    }

    /**
     * Is the unit equal to $unitString?
     *
     * @param string $unitString
     * @return boolean
     */
    public function is($unitString)
    {
        return $this->toString() === $unitString;
    }

    /**
     * Is the unit length unit?
     *
     * @return boolean
     */
    public function isLength()
    {
        $css = $this->toCSS(new ILess_Environment());

        return !!preg_match(self::LENGTH_REGEXP, $css);
    }

    /**
     * Is the unit angle unit?
     *
     * @return boolean
     */
    public function isAngle()
    {
        return isset(ILess_UnitConversion::$angle[$this->toCSS(new ILess_Environment())]);
    }

    /**
     * Is the unit empty?
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return count($this->numerator) === 0 && count($this->denominator) === 0;
    }

    /**
     * Is singular?
     *
     * @return boolean
     */
    public function isSingular()
    {
        return count($this->numerator) <= 1 && count($this->denominator) == 0;
    }

    public function usedUnits()
    {
        $result = array();
        foreach (ILess_UnitConversion::$groups as $groupName) {
            $group = ILess_UnitConversion::${$groupName};
            for ($i = 0; $i < count($this->numerator); $i++) {
                $atomicUnit = $this->numerator[$i];
                if (isset($group[$atomicUnit]) && !isset($result[$groupName])) {
                    $result[$groupName] = $atomicUnit;
                }
            }
            for ($i = 0; $i < count($this->denominator); $i++) {
                $atomicUnit = $this->denominator[$i];
                if (isset($group[$atomicUnit]) && !isset($result[$groupName])) {
                    $result[$groupName] = $atomicUnit;
                }
            }
        }

        return $result;
    }

    public function cancel()
    {
        $counter = array();
        $backup = null;

        for ($i = 0; $i < count($this->numerator); $i++) {
            $atomicUnit = $this->numerator[$i];
            if (!$backup) {
                $backup = $atomicUnit;
            }
            $counter[$atomicUnit] = (isset($counter[$atomicUnit]) ? $counter[$atomicUnit] : 0) + 1;
        }

        for ($i = 0; $i < count($this->denominator); $i++) {
            $atomicUnit = $this->denominator[$i];
            if (!$backup) {
                $backup = $atomicUnit;
            }
            $counter[$atomicUnit] = (isset($counter[$atomicUnit]) ? $counter[$atomicUnit] : 0) - 1;
        }

        $this->numerator = array();
        $this->denominator = array();

        foreach ($counter as $atomicUnit => $count) {
            if ($count > 0) {
                for ($i = 0; $i < $count; $i++) {
                    $this->numerator[] = $atomicUnit;
                }
            } elseif ($count < 0) {
                for ($i = 0; $i < -$count; $i++) {
                    $this->denominator[] = $atomicUnit;
                }
            }
        }

        if (count($this->numerator) === 0 && count($this->denominator) === 0 && $backup) {
            $this->backupUnit = $backup;
        }

        sort($this->numerator);
        sort($this->denominator);
    }

    /**
     * @see ILess_Node::generateCSS
     */
    public function generateCSS(ILess_Environment $env, ILess_Output $output)
    {
        if (count($this->numerator) >= 1) {
            $output->add($this->numerator[0]);
        } else {
            if (count($this->denominator) >= 1) {
                $output->add($this->denominator[0]);
            } else {
                if ((!$env->strictUnits) && $this->backupUnit) {
                    $output->add($this->backupUnit);
                }
            }
        }
    }

    /**
     * Compiles the node
     *
     * @param ILess_Environment $env
     * @return ILess_Node_DimensionUnit
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        return $this;
    }

    /**
     * Convert to string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    public function __clone()
    {
        return new ILess_Node_DimensionUnit($this->numerator, $this->denominator, $this->backupUnit);
    }

}

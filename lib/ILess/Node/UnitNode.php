<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Node;

use ILess\Context;
use ILess\Node;
use ILess\Output\OutputInterface;
use ILess\Util\UnitConversion;

/**
 * Dimension unit.
 */
class UnitNode extends Node implements ComparableInterface
{
    /**
     * Length regular expression.
     */
    const LENGTH_REGEXP = '/px|em|%|in|cm|mm|pc|pt|ex/';

    /**
     * Numerator.
     *
     * @var array
     */
    public $numerator = [];

    /**
     * Denominator.
     *
     * @var array
     */
    public $denominator = [];

    /**
     * The backup unit to use when unit is empty.
     *
     * @var string
     */
    public $backupUnit;

    /**
     * Constructor.
     *
     * @param array $numerator
     * @param array $denominator
     * @param string $backupUnit
     */
    public function __construct(array $numerator = [], array $denominator = [], $backupUnit = null)
    {
        $this->numerator = $numerator;
        $this->denominator = $denominator;

        sort($this->numerator);
        sort($this->denominator);

        if ($backupUnit) {
            $this->backupUnit = $backupUnit;
        } elseif ($numerator) {
            $this->backupUnit = $numerator[0];
        }
    }

    /**
     * Converts to string.
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
     * {@inheritdoc}
     */
    public function compare(Node $other)
    {
        // we can compare only units
        if ($other instanceof self) {
            return $this->is($other->toString()) ? 0 : null;
        }
    }

    /**
     * Take care about backup unit when cloning, see the constructor.
     */
    public function __clone()
    {
        if (null === $this->backupUnit && count($this->numerator)) {
            $this->backupUnit = $this->numerator[0];
        }
    }

    /**
     * Is the unit equal to $unitString?
     *
     * @param string $unitString
     *
     * @return bool
     */
    public function is($unitString)
    {
        return strtoupper($this->toString()) === strtoupper($unitString);
    }

    /**
     * Is the unit length unit?
     *
     * @return bool
     */
    public function isLength()
    {
        $css = $this->toCSS(new Context());

        return (bool) preg_match(self::LENGTH_REGEXP, $css);
    }

    /**
     * Is the unit angle unit?
     *
     * @return bool
     */
    public function isAngle()
    {
        $angle = UnitConversion::getGroup('angle');

        return isset($angle[$this->toCSS(new Context())]);
    }

    /**
     * Is the unit empty?
     *
     * @return bool
     */
    public function isEmpty()
    {
        return count($this->numerator) === 0 && count($this->denominator) === 0;
    }

    /**
     * Is singular?
     *
     * @return bool
     */
    public function isSingular()
    {
        return count($this->numerator) <= 1 && count($this->denominator) == 0;
    }

    public function usedUnits()
    {
        $result = [];
        foreach (UnitConversion::getGroups() as $groupName) {
            $group = UnitConversion::getGroup($groupName);
            for ($i = 0; $i < count($this->numerator); ++$i) {
                $atomicUnit = $this->numerator[$i];
                if (isset($group[$atomicUnit]) && !isset($result[$groupName])) {
                    $result[$groupName] = $atomicUnit;
                }
            }
            for ($i = 0; $i < count($this->denominator); ++$i) {
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
        $counter = [];

        for ($i = 0; $i < count($this->numerator); ++$i) {
            $atomicUnit = $this->numerator[$i];
            $counter[$atomicUnit] = (isset($counter[$atomicUnit]) ? $counter[$atomicUnit] : 0) + 1;
        }

        for ($i = 0; $i < count($this->denominator); ++$i) {
            $atomicUnit = $this->denominator[$i];
            $counter[$atomicUnit] = (isset($counter[$atomicUnit]) ? $counter[$atomicUnit] : 0) - 1;
        }

        $this->numerator = [];
        $this->denominator = [];

        foreach ($counter as $atomicUnit => $count) {
            if ($count > 0) {
                for ($i = 0; $i < $count; ++$i) {
                    $this->numerator[] = $atomicUnit;
                }
            } elseif ($count < 0) {
                for ($i = 0; $i < -$count; ++$i) {
                    $this->denominator[] = $atomicUnit;
                }
            }
        }

        sort($this->numerator);
        sort($this->denominator);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCSS(Context $context, OutputInterface $output)
    {
        if (count($this->numerator) === 1) {
            $output->add($this->numerator[0]);
        } elseif (!$context->strictUnits && $this->backupUnit) {
            $output->add($this->backupUnit);
        } elseif (!$context->strictUnits && count($this->denominator)) {
            $output->add($this->denominator[0]);
        }
    }

    /**
     * Convert to string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}

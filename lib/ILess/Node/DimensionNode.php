<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Node;

use ILess\Context;
use ILess\Exception\CompilerException;
use ILess\Math;
use ILess\Node;
use ILess\Output\OutputInterface;
use ILess\Util\UnitConversion;
use ILess\Util;
use ILess\Visitor\VisitorInterface;

/**
 * Dimension.
 */
class DimensionNode extends Node implements ComparableInterface, ToColorConvertibleInterface
{
    /**
     * Node type.
     *
     * @var string
     */
    protected $type = 'Dimension';

    /**
     * The unit.
     *
     * @var UnitNode
     */
    public $unit;

    /**
     * Constructor.
     *
     * @param string $value
     * @param UnitNode|string $unit
     */
    public function __construct($value, $unit = null)
    {
        parent::__construct(floatval($value));

        if (!$unit instanceof UnitNode) {
            $unit = $unit ? new UnitNode([$unit]) : new UnitNode();
        }

        $this->unit = $unit;
    }

    /**
     * {@inheritdoc}
     */
    public function accept(VisitorInterface $visitor)
    {
        $this->unit = $visitor->visit($this->unit);
    }

    /**
     * {@inheritdoc}
     */
    public function toColor()
    {
        return new ColorNode([$this->value, $this->value, $this->value]);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCSS(Context $context, OutputInterface $output)
    {
        if ($context->strictUnits && !$this->unit->isSingular()) {
            throw new CompilerException(
                sprintf(
                    'Multiple units in dimension. Correct the units or use the unit function. Bad unit: %s',
                    $this->unit->toString()
                )
            );
        }

        $value = Util::round($context, $this->value);
        $strValue = (string) $value;

        if ($value !== 0 && $value < 0.000001 && $value > -0.000001) {
            $strValue = Math::toFixed($value, 20);
        }

        // remove trailing zeros
        $strValue = Math::clean($strValue);

        // Zero values doesn't need a unit
        if ($context->compress) {
            if ($value == 0 && $this->unit->isLength()) {
                $output->add($strValue);

                return;
            }
            // Float values doesn't need a leading zero
            if ($value > 0 && $value < 1) {
                $strValue = substr($strValue, 1);
            }
        }

        $output->add($strValue);
        // pass to unit
        $this->unit->generateCSS($context, $output);
    }

    /**
     * Convert the value to string.
     *
     * @return string
     */
    public function toString()
    {
        return $this->toCSS(new Context());
    }

    /**
     * Operates with the dimension. In an operation between two dimensions,
     * we default to the first Dimension's unit,
     * so `1px + 2` will yield `3px`.
     *
     * @param Context $context
     * @param string $op
     * @param DimensionNode $other
     *
     * @return DimensionNode
     *
     * @throws CompilerException
     */
    public function operate(Context $context, $op, DimensionNode $other)
    {
        $value = Math::operate($op, $this->value, $other->value);
        $unit = clone $this->unit;

        if ($op === '+' || $op === '-') {
            if (!count($unit->numerator) && !count($unit->denominator)) {
                $unit = clone $other->unit;
                if ($this->unit->backupUnit) {
                    $unit->backupUnit = $this->unit->backupUnit;
                }
            } elseif (!count($other->unit->numerator) && !count($other->unit->denominator)) {
                // do nothing
            } else {
                $other = $other->convertTo($this->unit->usedUnits());
                if ($context->strictUnits && $other->unit->toString() !== $unit->toString()) {
                    throw new CompilerException(
                        sprintf(
                            'Incompatible units. Change the units or use the unit function. Bad units: \'%s\' and \'%s\'.',
                            $unit->toString(),
                            $other->unit->toString()
                        )
                    );
                }
                $value = Math::operate($op, $this->value, $other->value);
            }
        } elseif ($op === '*') {
            $unit->numerator = array_merge($unit->numerator, $other->unit->numerator);
            $unit->denominator = array_merge($unit->denominator, $other->unit->denominator);
            sort($unit->numerator);
            sort($unit->denominator);
            $unit->cancel();
        } elseif ($op === '/') {
            $unit->numerator = array_merge($unit->numerator, $other->unit->denominator);
            $unit->denominator = array_merge($unit->denominator, $other->unit->numerator);
            sort($unit->numerator);
            sort($unit->denominator);
            $unit->cancel();
        }

        return new self($value, $unit);
    }

    /**
     * Compares with another dimension.
     *
     * @param Node $other
     *
     * @return int
     */
    public function compare(Node $other)
    {
        if (!$other instanceof self) {
            return;
        }

        if ($this->unit->isEmpty() || $other->unit->isEmpty()) {
            $a = $this;
            $b = $other;
        } else {
            $a = $this->unify();
            $b = $other->unify();
            if ($a->unit->compare($b->unit) !== 0) {
                return;
            }
        }

        return Util::numericCompare($a->value, $b->value);
    }

    /**
     * Converts to the unified dimensions.
     *
     * @return DimensionNode
     */
    public function unify()
    {
        return $this->convertTo(
            [
                'length' => 'px',
                'duration' => 's',
                'angle' => 'rad',
            ]
        );
    }

    /**
     * Converts to another unit.
     *
     * @param array|string $conversions
     *
     * @return DimensionNode
     */
    public function convertTo($conversions)
    {
        $value = $this->value;
        $unit = clone $this->unit;

        if (is_string($conversions)) {
            $derivedConversions = [];
            foreach (UnitConversion::getGroups() as $i) {
                $group = UnitConversion::getGroup($i);
                if (isset($group[$conversions])) {
                    $derivedConversions = [$i => $conversions];
                }
            }
            $conversions = $derivedConversions;
        }

        foreach ($conversions as $groupName => $targetUnit) {
            $group = UnitConversion::getGroup($groupName);
            // numerator
            for ($i = 0, $count = count($unit->numerator); $i < $count; ++$i) {
                $atomicUnit = $unit->numerator[$i];
                if (is_object($atomicUnit)) {
                    continue;
                }
                if (!isset($group[$atomicUnit])) {
                    continue;
                }
                $value = $value * $group[$atomicUnit] / $group[$targetUnit];
                $unit->numerator[$i] = $targetUnit;
            }

            // denominator
            for ($i = 0, $count = count($unit->denominator); $i < $count; ++$i) {
                $atomicUnit = $unit->denominator[$i];
                if (!isset($group[$atomicUnit])) {
                    continue;
                }
                $value = $value / ($group[$atomicUnit] / $group[$targetUnit]);
                $unit->denominator[$i] = $targetUnit;
            }
        }

        $unit->cancel();

        return new self($value, $unit);
    }
}

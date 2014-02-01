<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Dimension
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_Dimension extends ILess_Node implements ILess_Node_VisitableInterface
{
    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'Dimension';

    /**
     * The unit
     *
     * @var ILess_Node_DimensionUnit
     */
    public $unit;

    /**
     * Constructor
     *
     * @param string $value
     * @param ILess_Node_DimensionUnit|string $unit
     */
    public function __construct($value, $unit = null)
    {
        $this->value = $this->toNumber($value);
        if (!$unit instanceof ILess_Node_DimensionUnit) {
            $unit = $unit ? new ILess_Node_DimensionUnit(array($unit)) : new ILess_Node_DimensionUnit();
        }
        $this->unit = $unit;
    }

    /**
     * Converts the value to float like number. We convert this to string to
     * be used with bcmath extension
     *
     * @param string $value
     * @return string
     */
    protected function toNumber($value)
    {
        $value = str_replace('+', '', $value);

        if ($value[0] == '.') {
            $value = '0' . $value;
        }

        // is number to low?
        if ($value != 0 && $value < 0.000001 && $value > -0.000001) {
            // would be output 1e-6 etc.
            $value = (string)preg_replace('/\.?0+$/', '', number_format(floatval($value), 20, '.', ''));
        }

        return $value;
    }

    /**
     * Accepts a visitor
     *
     * @param ILess_Visitor $visitor
     * @return void
     */
    public function accept(ILess_Visitor $visitor)
    {
        $this->unit = $visitor->visit($this->unit);
    }

    /**
     * Compiles the node
     *
     * @param ILess_Environment $env
     * @return ILess_Node_Dimension
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        return $this;
    }

    /**
     * Converts to color
     *
     * @return ILess_Node_Color
     */
    public function toColor()
    {
        return new ILess_Node_Color(array($this->value, $this->value, $this->value));
    }

    /**
     * @see ILess_Node::generateCSS
     */
    public function generateCSS(ILess_Environment $env, ILess_Output $output)
    {
        if ($env->strictUnits && !$this->unit->isSingular()) {
            throw new ILess_Exception_Compiler(sprintf('Multiple units in dimension. Correct the units or use the unit function. Bad unit: %s', $this->unit->toString()));
        }

        $value = $this->value;

        // Zero values doesn't need a unit
        if ($env->compress) {
            if ($value == 0 && $this->unit->isLength()) {
                $output->add($value);
                // no need to continue
                return;
            }
            // first digit is zero
            // Float values doesn't need a leading zero
            // elseif($value[0] == '0')
            elseif ($value > 0 && $value < 1 && $value[0] === '0') {
                $value = substr($value, 1);
            }
        }

        $output->add($value);
        // pass to unit
        $this->unit->generateCSS($env, $output);
    }

    /**
     * Convert the value to string
     *
     * @return string
     */
    public function toString()
    {
        return $this->toCSS(new ILess_Environment());
    }

    /**
     * Operates with the dimension. In an operation between two dimensions,
     * we default to the first Dimension's unit,
     * so `1px + 2` will yield `3px`.
     *
     * @param ILess_Environment $env
     * @param string $op
     * @param ILess_Node_Dimension $other
     * @return ILess_Node_Dimension
     * @throws ILess_CompilerException
     */
    public function operate(ILess_Environment $env, $op, ILess_Node_Dimension $other)
    {
        $value = ILess_Math::operate($op, $this->value, $other->value);
        $unit = clone $this->unit;
        if ($op === '+' || $op === '-') {
            if (!count($unit->numerator) && !count($unit->denominator)) {
                $unit->numerator = $other->unit->numerator;
                $unit->denominator = $other->unit->denominator;
            } elseif (!count($other->unit->numerator) && !count($other->unit->denominator)) {
                // do nothing
            } else {
                $other = $other->convertTo($this->unit->usedUnits());
                if ($env->strictUnits && $other->unit->toString() !== $unit->toString()) {
                    throw new ILess_Exception_Compiler(sprintf(
                        'Incompatible units. Change the units or use the unit function. Bad units: \'%s\' and \'%s\'.',
                        $unit->toString(),
                        $other->unit->toString()
                    ));
                }
                $value = ILess_Math::operate($op, $this->value, $other->value);
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

        return new ILess_Node_Dimension($value, $unit);
    }

    /**
     * Compares with another dimension
     *
     * @param ILess_Node $other
     * @return integer
     */
    public function compare(ILess_Node $other)
    {
        if (!$other instanceof ILess_Node_Dimension) {
            return -1;
        }

        $a = $this->unify();
        $b = $other->unify();

        if ($b->value > $a->value) {
            return -1;
        } elseif ($b->value < $a->value) {
            return 1;
        } else {
            if (!$b->unit->isEmpty() && $a->unit->compare($b->unit) !== 0) {
                return -1;
            }

            return 0;
        }
    }

    /**
     * Converts to the unified dimensions
     *
     * @return ILess_Node_Dimension
     */
    public function unify()
    {
        return $this->convertTo(array(
            'length' => 'm',
            'duration' => 's',
            'angle' => 'rad'
        ));
    }

    /**
     * Converts to another unit
     *
     * @param array|string $conversions
     * @return ILess_Node_Dimension
     */
    public function convertTo($conversions)
    {
        $value = $this->value;
        $unit = clone $this->unit;

        if (is_string($conversions)) {
            $derivedConversions = array();
            foreach (ILess_UnitConversion::$groups as $i) {
                if (isset(ILess_UnitConversion::${$i}[$conversions])) {
                    $derivedConversions = array($i => $conversions);
                }
            }
            $conversions = $derivedConversions;
        }

        foreach ($conversions as $groupName => $targetUnit) {
            $group = ILess_UnitConversion::${$groupName};
            // numerator
            for ($i = 0, $count = count($unit->numerator); $i < $count; $i++) {
                $atomicUnit = $unit->numerator[$i];
                if (is_object($atomicUnit)) {
                    continue;
                }
                if (!isset($group[$atomicUnit])) {
                    continue;
                }

                $value = ILess_Math::multiply($value, ILess_Math::divide($group[$atomicUnit], $group[$targetUnit]));
                $unit->numerator[$i] = $targetUnit;
            }

            // denominator
            for ($i = 0, $count = count($unit->denominator); $i < $count; $i++) {
                $atomicUnit = $unit->denominator[$i];
                if (!isset($group[$atomicUnit])) {
                    continue;
                }
                // $value = $value / ($group[$atomicUnit] / $group[$targetUnit]);
                $value = ILess_Math::divide($value, ILess_Math::divide($group[$atomicUnit], $group[$targetUnit]));
                $unit->denominator[$i] = $targetUnit;
            }
        }

        $unit->cancel();

        return new ILess_Node_Dimension($value, $unit);
    }

}

<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Color
 *
 * @package ILess
 * @subpackage node
 */
class ILess_Node_Color extends ILess_Node
{
    /**
     * Node type
     *
     * @var string
     */
    protected $type = 'Color';

    /**
     * The color
     *
     * @var ILess_Color
     */
    protected $color;

    /**
     * Constructor
     *
     * @param string|array $rgb The rgb value
     * @param integer $alpha Alpha channel
     * @throws InvalidArgumentException
     */
    public function __construct($rgb, $alpha = 1)
    {
        if (!$rgb instanceof ILess_Color) {
            $this->color = new ILess_Color($rgb, $alpha);
        } else {
            $this->color = $rgb;
        }
    }

    /**
     * Returns the color object
     *
     * @return ILess_Color
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @see ILess_Node::generateCSS
     */
    public function generateCSS(ILess_Environment $env, ILess_Output $output)
    {
        $output->add($this->toCSS($env));
    }

    /**
     * Compiles the color
     *
     * @param ILess_Environment $env
     * @return ILess_Node_Color
     */
    public function compile(ILess_Environment $env, $arguments = null, $important = null)
    {
        return $this;
    }

    /**
     * Returns the RGB channels
     *
     * @return array
     */
    public function getRGB()
    {
        return $this->color->rgb;
    }

    /**
     * Returns the HSV components of the color
     *
     * @return array
     */
    public function toHSV()
    {
        return $this->color->toHSV();
    }

    /**
     * Returns the red channel
     *
     * @param boolean $raw Return raw value?
     * @return mixed
     */
    public function getRed($raw = false)
    {
        return $raw ? $this->color->getRed() : new ILess_Node_Dimension($this->color->getRed());
    }

    /**
     * Returns the green channel
     *
     * @param boolean $raw Return raw value?
     * @return mixed
     */
    public function getGreen($raw = false)
    {
        return $raw ? $this->color->getGreen() : new ILess_Node_Dimension($this->color->getGreen());
    }

    /**
     * Returns the blue channel
     *
     * @param boolean $raw Return raw value?
     * @return mixed
     */
    public function getBlue($raw = false)
    {
        return $raw ? $this->color->getBlue() : new ILess_Node_Dimension($this->color->getBlue());
    }

    /**
     * Returns the alpha channel
     *
     * @param boolean $raw Return raw value?
     * @return mixed
     */
    public function getAlpha($raw = false)
    {
        return $raw ? $this->color->getAlpha() : new ILess_Node_Dimension($this->color->getAlpha());
    }

    /**
     * Returns the color saturation
     *
     * @param boolean $raw Return raw value?
     * @return mixed
     */
    public function getSaturation($raw = false)
    {
        return $raw ? $this->color->getSaturation() :
            new ILess_Node_Dimension(
                ILess_Math::round(
                    ILess_Math::multiply(
                        $this->color->getSaturation(), 100)
                )
                , '%');
    }

    /**
     * Returns the color hue
     *
     * @param boolean $raw Raw value?
     * @return mixed
     */
    public function getHue($raw = false)
    {
        return $raw ? $this->color->getHue() : new ILess_Node_Dimension(ILess_Math::round($this->color->getHue()));
    }

    /**
     * Returns the lightness
     *
     * @param boolean $raw Return raw value?
     * @return mixed ILess_Node_Dimension if $raw is false
     */
    public function getLightness($raw = false)
    {
        return $raw ? $this->color->getLightness() :
            new ILess_Node_Dimension(ILess_Math::round($this->color->getLightness() * 100), '%');
    }

    /**
     * Returns the luma
     *
     * @param boolean $raw Return raw value?
     * @return mixed ILess_Node_Dimension if $raw is false
     */
    public function getLuma($raw = false)
    {
        return $raw ? $this->color->getLuma() : new ILess_Node_Dimension(
            ILess_Math::clean(ILess_Math::multiply(
                ILess_Math::round(ILess_Math::multiply($this->color->getLuma(), $this->color->getAlpha()), 2)
                , 100)),
            '%');
    }

    /**
     * Converts the node to ARGB
     *
     * @return ILess_Node_Anonymous
     */
    public function toARGB()
    {
        return new ILess_Node_Anonymous($this->color->toARGB());
    }

    /**
     * Returns the HSL components of the color
     *
     * @return array
     */
    public function toHSL()
    {
        return $this->color->toHSL();
    }

    /**
     * Converts the node to string
     *
     * @param ILess_Environment $env
     * @return string
     */
    public function toCSS(ILess_Environment $env)
    {
        return $this->color->toString($env->compress, $env->compress && $env->canShortenColors);
    }

    /**
     * Operations have to be done per-channel, if not,
     * channels will spill onto each other. Once we have
     * our result, in the form of an integer triplet,
     * we create a new color node to hold the result.
     *
     * @param ILess_Environment $env
     * @param string $op
     * @param ILess_Node $other
     * @return ILess_Node_Color
     * @throws InvalidArgumentException
     */
    public function operate(ILess_Environment $env, $op, ILess_Node $other)
    {
        $result = array();

        if (!($other instanceof ILess_Node_Color)) {
            if (!self::methodExists($other, 'toColor')) {
                throw new InvalidArgumentException('The other node must implement toColor() method to operate');
            }

            $other = $other->toColor();

            if (!$other instanceof ILess_Node_Color) {
                throw new InvalidArgumentException('The toColor() method must return an instance of ILess_Node_Color');
            }
        }

        $t = $this->getRGB();
        $o = $other->getRGB();

        for ($c = 0; $c < 3; $c++) {
            $result[$c] = ILess_Math::operate($op, $t[$c], $o[$c]);
            if ($result[$c] > 255) {
                $result[$c] = 255;
            } elseif ($result < 0) {
                $result[$c] = 0;
            }
        }

        return new ILess_Node_Color($result, $this->color->getAlpha() + $other->color->getAlpha());
    }

    /**
     * Compares with another node
     *
     * @param ILess_Node $other
     * @return integer
     * @throws InvalidArgumentException
     */
    public function compare(ILess_Node $other)
    {
        if (!($other instanceof ILess_Node_Color)) {
            if (!self::methodExists($other, 'toColor')) {
                throw new InvalidArgumentException('The other node must implement toColor() method to operate');
            }
            $other = $other->toColor();
            if (!$other instanceof ILess_Node_Color) {
                throw new InvalidArgumentException('The toColor() method must return an instance of ILess_Node_Color');
            }
        }

        // cannot compare with another node
        if (!$other instanceof ILess_Node_Color) {
            return -1;
        }

        $color = $this->getColor();
        $other = $other->getColor();

        return ($color->rgb === $other->rgb && $color->alpha === $other->alpha) ? 0 : -1;
    }

}

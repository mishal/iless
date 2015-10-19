<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess;

use InvalidArgumentException;

/**
 * Color utility class.
 */
final class Color
{
    /**
     * HSL and HSV cache.
     *
     * @var array
     */
    protected $hsv, $hsl;

    /**
     * Luma cache.
     *
     * @var string
     */
    protected $luma;

    /**
     * Luminance cache.
     *
     * @var string
     */
    protected $luminance;

    /**
     * The rgb channels.
     *
     * @var array
     */
    public $rgb = [];

    /**
     * The alpha channel.
     *
     * @var int
     */
    public $alpha = 1;

    /**
     * Original format.
     *
     * @var bool
     */
    protected $short = false;

    /**
     * Created from keyword?
     *
     * @var bool
     */
    public $keyword = false;

    /**
     * @var string
     */
    protected $originalForm;

    /**
     * Transparent keyword?
     *
     * @var bool
     */
    public $isTransparentKeyword = false;

    /**
     * Constructor.
     *
     * @param array|string $rgb The RGB components as an array or string definition
     * @param int $alpha The alpha channel
     * @param string $originalForm
     */
    public function __construct($rgb = [255, 255, 255], $alpha = 1, $originalForm = null)
    {
        if (is_array($rgb)) {
            $this->rgb = $rgb;
        } // string
        else {

            // this is a named color
            if ($color = self::color($rgb)) {
                $this->keyword = $rgb;
                $rgb = $color;
            }

            // strip #
            $rgb = trim($rgb, '#');
            if (strlen($rgb) == 6) {
                foreach (str_split($rgb, 2) as $c) {
                    $this->rgb[] = hexdec($c);
                }
            } elseif (strlen($rgb) == 3) {
                $this->short = true;
                foreach (str_split($rgb, 1) as $c) {
                    $this->rgb[] = hexdec($c . $c);
                }
            } elseif (strtolower($rgb) == 'transparent') {
                $this->rgb = [255, 255, 255];
                $this->isTransparentKeyword = true;
                $alpha = 0;
            } else {
                throw new InvalidArgumentException('Argument must be a color keyword or 3/6 digit hex e.g. #FFF.');
            }
        }

        $this->originalForm = $originalForm;
        // limit alpha channel
        $this->alpha = is_numeric($alpha) ? $alpha : 1;
    }

    /**
     * Returns the fixed RGB components (fitted into 0 - 255 range).
     *
     * @return array Array of red, green and blue components
     */
    protected function getFixedRGB()
    {
        $components = [];
        foreach ($this->rgb as $i) {
            $i = Math::round($i);
            if ($i > 255) {
                $i = 255;
            } elseif ($i < 0) {
                $i = 0;
            }
            $components[] = $i;
        }

        return $components;
    }

    protected function clamp($value, $max)
    {
        return min(max($value, 0), $max);
    }

    /**
     * Creates new color from the keyword.
     *
     * @param string $keyword
     *
     * @return Color
     */
    public static function fromKeyword($keyword)
    {
        $color = null;
        // is this named color?
        if (self::isNamedColor($keyword)) {
            $color = new self(substr(self::color($keyword), 1));
            $color->keyword = $keyword;
        } elseif ($keyword === 'transparent') {
            $color = new self([255, 255, 255], 0);
            $color->isTransparentKeyword = true;
        }

        return $color;
    }

    /**
     * Returns the red channel.
     *
     * @return int
     */
    public function getRed()
    {
        return $this->rgb[0];
    }

    /**
     * Returns the green channel.
     *
     * @return int
     */
    public function getGreen()
    {
        return $this->rgb[1];
    }

    /**
     * Returns the blue channel.
     *
     * @return int
     */
    public function getBlue()
    {
        return $this->rgb[2];
    }

    /**
     * Returns the alpha channel.
     *
     * @return int
     */
    public function getAlpha()
    {
        return $this->alpha;
    }

    /**
     * Returns the color saturation.
     *
     * @return string
     */
    public function getSaturation()
    {
        $this->toHSL();

        return $this->hsl['s'];
    }

    /**
     * Returns the color hue.
     *
     * @param bool $round
     *
     * @return string
     */
    public function getHue()
    {
        $this->toHSL();

        return $this->hsl['h'];
    }

    /**
     * Returns the color lightness.
     *
     * @return string
     */
    public function getLightness()
    {
        $this->toHSL();

        return $this->hsl['l'];
    }

    /**
     * Returns the luma.
     *
     * @return int
     */
    public function getLuma()
    {
        if ($this->luma !== null) {
            return $this->luma;
        }

        $r = $this->rgb[0] / 255;
        $g = $this->rgb[1] / 255;
        $b = $this->rgb[2] / 255;

        $r = ($r <= 0.03928) ? $r / 12.92 : pow((($r + 0.055) / 1.055), 2.4);
        $g = ($g <= 0.03928) ? $g / 12.92 : pow((($g + 0.055) / 1.055), 2.4);
        $b = ($b <= 0.03928) ? $b / 12.92 : pow((($b + 0.055) / 1.055), 2.4);

        $this->luma = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;

        return $this->luma;
    }

    /**
     * @return string
     */
    public function getLuminance()
    {
        if ($this->luminance !== null) {
            return $this->luminance;
        }

        $this->luminance = (0.2126 * $this->rgb[0] / 255) +
            (0.7152 * $this->rgb[1] / 255) +
            (0.0722 * $this->rgb[2] / 255);

        return $this->luminance;
    }

    /**
     * Converts to HSL.
     *
     * @return array
     */
    public function toHSL()
    {
        if ($this->hsl) {
            return $this->hsl;
        }

        $r = $this->rgb[0] / 255;
        $g = $this->rgb[1] / 255;
        $b = $this->rgb[2] / 255;
        $a = $this->alpha;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        $d = $max - $min;

        if ($max === $min) {
            $h = $s = 0;
        } else {
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

            switch ($max) {
                case $r:
                    $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
                    break;
                case $g:
                    $h = ($b - $r) / $d + 2;
                    break;
                case $b:
                    $h = ($r - $g) / $d + 4;
                    break;
            }
            $h /= 6;
        }

        $this->hsl = ['h' => $h * 360, 's' => $s, 'l' => $l, 'a' => $a];

        return $this->hsl;
    }

    /**
     * Converts to HSV.
     *
     * @return array
     */
    public function toHSV()
    {
        if ($this->hsv) {
            return $this->hsv;
        }

        $r = $this->rgb[0] / 255;
        $g = $this->rgb[1] / 255;
        $b = $this->rgb[2] / 255;
        $a = $this->alpha;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);

        $v = $max;

        $d = $max - $min;
        if ($max === 0) {
            $s = 0;
        } else {
            $s = $d / $max;
        }

        if ($max === $min) {
            $h = 0;
        } else {
            switch ($max) {
                case $r:
                    $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
                    break;
                case $g:
                    $h = ($b - $r) / $d + 2;
                    break;
                case $b:
                    $h = ($r - $g) / $d + 4;
                    break;
            }
            $h /= 6;
        }

        return ['h' => $h * 360, 's' => $s, 'v' => $v, 'a' => $a];
    }

    /**
     * Returns the string representation in ARGB model.
     *
     * @return string
     */
    public function toARGB()
    {
        $argb = array_merge(
            [$this->alpha * 255],
            $this->rgb
        );

        $result = '';
        foreach ($argb as $i) {
            $i = dechex($this->clamp(Math::round($i), 255));
            $result .= str_pad($i, 2, '0', STR_PAD_LEFT);
        }

        return '#' . $result;
    }

    private function toHex($rgb)
    {
        $parts = array_map(
            function ($c) {
                $c = $this->clamp(round($c), 255);

                return ($c < 16 ? '0' : '') . dechex($c);
            },
            $rgb
        );

        return '#' . implode('', $parts);
    }

    public function toRGB()
    {
        return $this->toHex($this->rgb);
    }

    /**
     * Returns the color as HEX string (when transparency present, in RGBA model).
     *
     * @param bool $compress Compress the color?
     * @param bool $canShorten Can the color be shortened if possible?
     *
     * @return string
     */
    public function toString($compress = false, $canShorten = false)
    {
        if ($this->isTransparentKeyword) {
            return 'transparent';
        }

        if ($this->originalForm) {
            return $this->originalForm;
        }

        $alpha = Math::toFixed($this->alpha + 2e-16, 8);

        if ($alpha < 1) {
            $fixedRGB = $this->getFixedRGB();

            return sprintf(
                'rgba(%s)',
                implode(
                    $compress ? ',' : ', ',
                    [
                        $fixedRGB[0],
                        $fixedRGB[1],
                        $fixedRGB[2],
                        Math::clean($this->clamp($alpha, 1)),
                    ]
                )
            );
        }

        // prevent named colors
        if ($this->keyword) {
            return $this->keyword;
        }

        $color = [];
        foreach ($this->getFixedRgb() as $i) {
            $color[] = str_pad(dechex(Math::round($i)), 2, '0', STR_PAD_LEFT);
        }

        $color = implode('', $color);

        // convert color to short format
        if ($canShorten && $color[0] === $color[1] && $color[2] === $color[3] && $color[4] === $color[5]) {
            $color = $color[0] . $color[2] . $color[4];
        }

        $color = '#' . $color;

        return $color;
    }

    /**
     * Converts the color to string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Does the color exits?
     *
     * @param string $color The color name
     *
     * @return string
     */
    public static function isNamedColor($color)
    {
        return isset(NamedColors::$colors[$color]);
    }

    /**
     * Returns the color hex representation or false.
     *
     * @param string $color Color name
     *
     * @return string|false
     */
    public static function color($color)
    {
        return self::isNamedColor($color) ? NamedColors::$colors[$color] : false;
    }
}

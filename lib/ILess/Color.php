<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Color utility class
 *
 * @package ILess
 * @subpackage color
 */
class ILess_Color
{
    /**
     * Array of named colors
     *
     * @var array
     */
    public static $colors = array(
        'aliceblue' => '#f0f8ff',
        'antiquewhite' => '#faebd7',
        'aqua' => '#00ffff',
        'aquamarine' => '#7fffd4',
        'azure' => '#f0ffff',
        'beige' => '#f5f5dc',
        'bisque' => '#ffe4c4',
        'black' => '#000000',
        'blanchedalmond' => '#ffebcd',
        'blue' => '#0000ff',
        'blueviolet' => '#8a2be2',
        'brown' => '#a52a2a',
        'burlywood' => '#deb887',
        'cadetblue' => '#5f9ea0',
        'chartreuse' => '#7fff00',
        'chocolate' => '#d2691e',
        'coral' => '#ff7f50',
        'cornflowerblue' => '#6495ed',
        'cornsilk' => '#fff8dc',
        'crimson' => '#dc143c',
        'cyan' => '#00ffff',
        'darkblue' => '#00008b',
        'darkcyan' => '#008b8b',
        'darkgoldenrod' => '#b8860b',
        'darkgray' => '#a9a9a9',
        'darkgrey' => '#a9a9a9',
        'darkgreen' => '#006400',
        'darkkhaki' => '#bdb76b',
        'darkmagenta' => '#8b008b',
        'darkolivegreen' => '#556b2f',
        'darkorange' => '#ff8c00',
        'darkorchid' => '#9932cc',
        'darkred' => '#8b0000',
        'darksalmon' => '#e9967a',
        'darkseagreen' => '#8fbc8f',
        'darkslateblue' => '#483d8b',
        'darkslategray' => '#2f4f4f',
        'darkslategrey' => '#2f4f4f',
        'darkturquoise' => '#00ced1',
        'darkviolet' => '#9400d3',
        'deeppink' => '#ff1493',
        'deepskyblue' => '#00bfff',
        'dimgray' => '#696969',
        'dimgrey' => '#696969',
        'dodgerblue' => '#1e90ff',
        'firebrick' => '#b22222',
        'floralwhite' => '#fffaf0',
        'forestgreen' => '#228b22',
        'fuchsia' => '#ff00ff',
        'gainsboro' => '#dcdcdc',
        'ghostwhite' => '#f8f8ff',
        'gold' => '#ffd700',
        'goldenrod' => '#daa520',
        'gray' => '#808080',
        'grey' => '#808080',
        'green' => '#008000',
        'greenyellow' => '#adff2f',
        'honeydew' => '#f0fff0',
        'hotpink' => '#ff69b4',
        'indianred' => '#cd5c5c',
        'indigo' => '#4b0082',
        'ivory' => '#fffff0',
        'khaki' => '#f0e68c',
        'lavender' => '#e6e6fa',
        'lavenderblush' => '#fff0f5',
        'lawngreen' => '#7cfc00',
        'lemonchiffon' => '#fffacd',
        'lightblue' => '#add8e6',
        'lightcoral' => '#f08080',
        'lightcyan' => '#e0ffff',
        'lightgoldenrodyellow' => '#fafad2',
        'lightgray' => '#d3d3d3',
        'lightgrey' => '#d3d3d3',
        'lightgreen' => '#90ee90',
        'lightpink' => '#ffb6c1',
        'lightsalmon' => '#ffa07a',
        'lightseagreen' => '#20b2aa',
        'lightskyblue' => '#87cefa',
        'lightslategray' => '#778899',
        'lightslategrey' => '#778899',
        'lightsteelblue' => '#b0c4de',
        'lightyellow' => '#ffffe0',
        'lime' => '#00ff00',
        'limegreen' => '#32cd32',
        'linen' => '#faf0e6',
        'magenta' => '#ff00ff',
        'maroon' => '#800000',
        'mediumaquamarine' => '#66cdaa',
        'mediumblue' => '#0000cd',
        'mediumorchid' => '#ba55d3',
        'mediumpurple' => '#9370d8',
        'mediumseagreen' => '#3cb371',
        'mediumslateblue' => '#7b68ee',
        'mediumspringgreen' => '#00fa9a',
        'mediumturquoise' => '#48d1cc',
        'mediumvioletred' => '#c71585',
        'midnightblue' => '#191970',
        'mintcream' => '#f5fffa',
        'mistyrose' => '#ffe4e1',
        'moccasin' => '#ffe4b5',
        'navajowhite' => '#ffdead',
        'navy' => '#000080',
        'oldlace' => '#fdf5e6',
        'olive' => '#808000',
        'olivedrab' => '#6b8e23',
        'orange' => '#ffa500',
        'orangered' => '#ff4500',
        'orchid' => '#da70d6',
        'palegoldenrod' => '#eee8aa',
        'palegreen' => '#98fb98',
        'paleturquoise' => '#afeeee',
        'palevioletred' => '#d87093',
        'papayawhip' => '#ffefd5',
        'peachpuff' => '#ffdab9',
        'peru' => '#cd853f',
        'pink' => '#ffc0cb',
        'plum' => '#dda0dd',
        'powderblue' => '#b0e0e6',
        'purple' => '#800080',
        'red' => '#ff0000',
        'rosybrown' => '#bc8f8f',
        'royalblue' => '#4169e1',
        'saddlebrown' => '#8b4513',
        'salmon' => '#fa8072',
        'sandybrown' => '#f4a460',
        'seagreen' => '#2e8b57',
        'seashell' => '#fff5ee',
        'sienna' => '#a0522d',
        'silver' => '#c0c0c0',
        'skyblue' => '#87ceeb',
        'slateblue' => '#6a5acd',
        'slategray' => '#708090',
        'slategrey' => '#708090',
        'snow' => '#fffafa',
        'springgreen' => '#00ff7f',
        'steelblue' => '#4682b4',
        'tan' => '#d2b48c',
        'teal' => '#008080',
        'thistle' => '#d8bfd8',
        'tomato' => '#ff6347',
        'turquoise' => '#40e0d0',
        'violet' => '#ee82ee',
        'wheat' => '#f5deb3',
        'white' => '#ffffff',
        'whitesmoke' => '#f5f5f5',
        'yellow' => '#ffff00',
        'yellowgreen' => '#9acd32'
    );

    /**
     * HSL and HSV cache
     *
     * @var array
     */
    protected $hsv, $hsl;

    /**
     * Luma cache
     *
     * @var string
     */
    protected $luma;

    /**
     * The rgb channels
     *
     * @var array
     */
    public $rgb = array();

    /**
     * The alpha channel
     *
     * @var integer
     */
    public $alpha = 1;

    /**
     * Original format
     *
     * @var boolean
     */
    protected $short = false;

    /**
     * Created from keyword?
     *
     * @var boolean
     */
    protected $keyword = false;

    /**
     * Transparent keyword?
     *
     * @var boolean
     */
    public $isTransparentKeyword = false;

    /**
     * Constructor
     *
     * @param array|string $rgb The RGB components as an array or string definition
     * @param integer $alpha The alpha channel
     */
    public function __construct($rgb = array(255, 255, 255), $alpha = 1)
    {
        if (is_array($rgb)) {
            // clean the components
            foreach ($rgb as &$i) {
                $i = ILess_Math::clean($i);
            }
            $this->rgb = $rgb;
        } // string
        else {
            // this is a named color
            if (isset(self::$colors[$rgb])) {
                $this->keyword = $rgb;
                $rgb = self::$colors[$rgb];
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
            } else {
                throw new InvalidArgumentException('Argument must be a color keyword or 3/6 digit hex e.g. #FFF.');
            }
        }

        // limit alpha channel
        $this->alpha = is_numeric($alpha) ? ILess_Math::clean(min($alpha, 1)) : 1;
    }

    /**
     * Returns the fixed RGB components (fitted into 0 - 255 range)
     *
     * @return array Array of red, green and blue components
     */
    protected function getFixedRGB()
    {
        $components = array();
        foreach ($this->rgb as $i) {
            $i = ILess_Math::round($i);
            if ($i > 255) {
                $i = 255;
            } elseif ($i < 0) {
                $i = 0;
            }
            $components[] = $i;
        }

        return $components;
    }

    /**
     * Creates new color from the keyword
     *
     * @param string $keyword
     * @return ILess_Color
     */
    public static function fromKeyword($keyword)
    {
        $color = null;
        // is this named color?
        if (self::isNamedColor($keyword)) {
            $color = new ILess_Color(substr(ILess_Color::color($keyword), 1));
            $color->keyword = $keyword;
        } elseif ($keyword === 'transparent') {
            $color = new ILess_Color(array(255, 255, 255), 0);
            $color->isTransparentKeyword = true;
        }

        return $color;
    }

    /**
     * Returns the red channel
     *
     * @return integer
     */
    public function getRed()
    {
        return $this->rgb[0];
    }

    /**
     * Returns the green channel
     *
     * @return integer
     */
    public function getGreen()
    {
        return $this->rgb[1];
    }

    /**
     * Returns the blue channel
     *
     * @return integer
     */
    public function getBlue()
    {
        return $this->rgb[2];
    }

    /**
     * Returns the alpha channel
     *
     * @return integer
     */
    public function getAlpha()
    {
        return $this->alpha;
    }

    /**
     * Returns the color saturation
     *
     * @return string
     */
    public function getSaturation()
    {
        $this->toHSL();

        return $this->hsl['s'];
    }

    /**
     * Returns the color hue
     *
     * @param boolean $round
     * @return string
     */
    public function getHue()
    {
        $this->toHSL();

        return $this->hsl['h'];
    }

    /**
     * Returns the color lightness
     *
     * @return string
     */
    public function getLightness()
    {
        $this->toHSL();

        return $this->hsl['l'];
    }

    /**
     * Returns the luma
     *
     * @return integer
     */
    public function getLuma()
    {
        if ($this->luma !== null) {
            return $this->luma;
        }

        // Y = 0.2126 R + 0.7152 G + 0.0722 B
        $r = ILess_Math::multiply('0.2126', ILess_Math::divide($this->rgb[0], 255));
        $g = ILess_Math::multiply('0.7152', ILess_Math::divide($this->rgb[1], 255));
        $b = ILess_Math::multiply('0.0722', ILess_Math::divide($this->rgb[2], 255));

        $this->luma = ILess_Math::add(ILess_Math::add($r, $g), $b);

        return $this->luma;
    }

    /**
     * Converts to HSL
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

        $this->hsl = array('h' => $h * 360, 's' => $s, 'l' => $l);

        return $this->hsl;
    }

    /**
     * Converts to HSV
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

        return array('h' => $h * 360, 's' => $s, 'v' => $v, 'a' => $a);
    }

    /**
     * Returns the string representation in ARGB model
     *
     * @return string
     */
    public function toARGB()
    {
        $argb = array_merge(
            array(ILess_Math::clean(ILess_Math::round($this->alpha * 256))),
            $this->rgb);

        $result = '';
        foreach ($argb as $i) {
            $i = ILess_Math::round($i);
            $i = dechex($i > 255 ? 255 : ($i < 0 ? 0 : $i));
            $result .= str_pad($i, 2, '0', STR_PAD_LEFT);
        }

        return '#' . $result;
    }

    /**
     * Returns the color as HEX string (when transparency present, in RGBA model)
     *
     * @param boolean $compress Compress the color?
     * @param boolean $canShorten Can the color be shortened if possible?
     * @return string
     */
    public function toString($compress = false, $canShorten = false)
    {
        if ($this->isTransparentKeyword) {
            return 'transparent';
        }

        // no transparency
        if ($this->alpha == 1) {
            // FIXME: prevent keywords?
            // if($this->keyword)
            // {
            // return $this->keyword;
            // }
            $color = array();
            foreach ($this->getFixedRgb() as $i) {
                $color[] = str_pad(dechex($i), 2, '0', STR_PAD_LEFT);
            }
            $color = join('', $color);
            // convert color to short format
            if ($canShorten && $color[0] === $color[1] && $color[2] === $color[3] && $color[4] === $color[5]) {
                $color = $color[0] . $color[2] . $color[4];
            }

            $color = sprintf('#%s', $color);
        } else {
            $fixedRGB = $this->getFixedRGB();
            $color = sprintf('rgba(%s)', join($compress ? ',' : ', ', array(
                $fixedRGB[0], $fixedRGB[1], $fixedRGB[2], $this->alpha
            )));
        }

        return $color;
    }

    /**
     * Converts the color to string
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
     * @return string
     */
    public static function isNamedColor($color)
    {
        return isset(self::$colors[$color]);
    }

    /**
     * Returns the color hex representation
     *
     * @param $color Color name
     * @return mixed
     */
    public static function color($color)
    {
        return self::isNamedColor($color) ? self::$colors[$color] : false;
    }

}

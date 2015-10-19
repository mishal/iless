<?php

/*
 * This file is part of the Sift PHP framework.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess;

use ILess\Node\SelectorNode;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * Debug utility, only for porting js to php.
 */
class Debug
{
    /**
     * Assert that the node has valid rules.
     *
     * @param mixed $node
     *
     * @return bool
     *
     * @throws
     */
    public static function assertValidRules($node)
    {
        if (!property_exists($node, 'rules')) {
            throw new InvalidArgumentException('The node to check does not have rules property.');
        }

        $invalid = $indexes = [];
        foreach ($node->rules as $i => $rule) {
            if (!$rule instanceof Node) {
                $invalid[] = [
                    $i,
                    gettype($rule),
                    $rule,
                ];
            }
        }

        if ($invalid) {
            throw new UnexpectedValueException(sprintf(
                'Ruleset rule is not instance of ILess\Node. Invalid rules %s, Indexes %s',
                self::formatInvalid($invalid)
            ));
        }

        return true;
    }

    public static function assertValidPaths($node)
    {
        if (!property_exists($node, 'paths')) {
            throw new InvalidArgumentException('The node to check does not have paths property.');
        }

        $invalid = $indexes = [];
        foreach ($node->paths as $i => $path) {
            if (!is_array($path)) {
                $invalid[] = [
                    $i,
                    gettype($path),
                    $path,
                ];
                continue;
            }

            foreach ($path as $p => $sel) {
                if (!$sel instanceof SelectorNode) {
                    $invalid[] = [
                        $i,
                        gettype($path),
                        $path,
                    ];
                }
            }
        }

        if ($invalid) {
            throw new UnexpectedValueException(sprintf(
                    'ILess\Node path is not instance of Selector. Invalid paths %s, %s', count($invalid),
                    self::formatInvalid($invalid))
            );
        }

        return true;
    }

    private static function formatInvalid($invalid)
    {
        $out = [];
        foreach ($invalid as $i) {
            $out[] = 'index:' . $i[0] . ', type: ' . $i[1] . (is_object($i[2]) ? ('class: ' . get_class($i[2])) : '');
        }

        return implode('; ', $out);
    }
}

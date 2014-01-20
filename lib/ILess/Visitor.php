<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Visitor
 *
 * @package ILess
 * @subpackage visitor
 */
abstract class ILess_Visitor
{
    /**
     * Is the visitor replacing?
     *
     * @var boolean
     */
    protected $isReplacing = false;

    /**
     * Method cache
     *
     * @var array
     */
    private $methodCache = array();

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        // prepare the method cache to speed up the visitors
        foreach (get_class_methods($this) as $method) {
            if (strpos($method, 'visit') === 0) {
                $this->methodCache[$method] = true;
            }
        }
    }

    /**
     * Runs the visitor
     *
     * @param ILess_Node|array
     */
    abstract public function run($root);

    /**
     * Is the visitor replacing?
     *
     * @return boolean
     */
    public function isReplacing()
    {
        return $this->isReplacing;
    }

    /**
     * Visits a node or an array of nodes
     *
     * @param ILess_Node|array|string|null $node The node to visit
     * @return mixed The visited node
     */
    public function visit($node)
    {
        if (is_array($node)) {
            return $this->visitArray($node);
        }

        if (!is_object($node)) {
            return $node;
        }

        if (($type = $node->getType()) && ($funcName = sprintf('visit%s', $type)) &&
            isset($this->methodCache[$funcName])
        ) {
            $arguments = new ILess_Visitor_Arguments(array(
                'visitDeeper' => true
            ));

            $newNode = $this->$funcName($node, $arguments);

            if ($this->isReplacing()) {
                $node = $newNode;
            }

            if ($arguments->visitDeeper && $node instanceof ILess_Node_VisitableInterface) {
                $node->accept($this);
            }

            $funcName = sprintf('%sOut', $funcName);
            if (isset($this->methodCache[$funcName])) {
                $this->$funcName($node, isset($arguments) ? $arguments : new ILess_Visitor_Arguments());
            }
        } elseif ($node instanceof ILess_Node_VisitableInterface) {
            $node->accept($this);
        }

        return $node;
    }

    /**
     * Accepts a visit
     *
     * @param ILess_Node_VisitableInterface $node The node to visit
     */
    public function doAccept(ILess_Node_VisitableInterface $node)
    {
        return $node->accept($this);
    }

    /**
     * Visits an array of nodes
     *
     * @param array $nodes Array of nodes
     */
    public function visitArray(array $nodes)
    {
        if (!$this->isReplacing()) {
            array_map(array($this, 'visit'), $nodes);

            return $nodes;
        }
        $newNodes = array();
        foreach ($nodes as $node) {
            $evald = $this->visit($node);
            if (is_array($evald)) {
                self::flatten($evald, $newNodes);
            } else {
                $newNodes[] = $evald;
            }
        }

        return $newNodes;
    }

    /**
     * Flattens an array
     *
     * @param array $array The array to flatten
     * @param array $out The output array
     * @return void
     */
    protected static function flatten(array $array, array &$out)
    {
        foreach ($array as $item) {
            if (!is_array($item)) {
                $out[] = $item;
                continue;
            }
            foreach ($item as $nestedItem) {
                if (is_array($nestedItem)) {
                    self::flatten($nestedItem, $out);
                } else {
                    $out[] = $nestedItem;
                }
            }
        }
    }

}

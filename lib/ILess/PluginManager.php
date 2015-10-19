<?php

/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess;

use ILess\Plugin\PluginInterface;
use ILess\Plugin\PostProcessorInterface;
use ILess\Plugin\PreProcessorInterface;
use ILess\Visitor\VisitorInterface;

/**
 * PluginManager.
 */
final class PluginManager
{
    /**
     * Default priority.
     */
    const PRIORITY_DEFAULT = 1;

    /**
     * @var array
     */
    private $plugins = [];

    /**
     * @var array
     */
    private $visitors = [];

    /**
     * @var array
     */
    private $preProcessors = [];

    /**
     * @var array
     */
    private $postProcessors = [];

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @param Parser $parser
     */
    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Adds a plugin.
     *
     * @param PluginInterface $plugin
     *
     * @return $this
     */
    public function addPlugin(PluginInterface $plugin)
    {
        $this->plugins[] = $plugin;

        // let plugin do what it wants to
        $plugin->install($this->parser);

        return $this;
    }

    /**
     * Adds more plugins at once.
     *
     * @param array $plugins
     *
     * @return $this
     */
    public function addPlugins(array $plugins)
    {
        foreach ($plugins as $plugin) {
            $this->addPlugin($plugin);
        }

        return $this;
    }

    /**
     * Returns an array of attached plugins.
     *
     * @return array
     */
    public function getPlugins()
    {
        return $this->plugins;
    }

    /**
     * Adds a visitor.
     *
     * @param VisitorInterface $visitor
     *
     * @return $this
     */
    public function addVisitor(VisitorInterface $visitor)
    {
        $this->visitors[] = $visitor;

        return $this;
    }

    /**
     * Adds more visitors at once.
     *
     * @param array $visitors
     *
     * @return $this
     */
    public function addVisitors(array $visitors)
    {
        foreach ($visitors as $visitor) {
            $this->addVisitor($visitor);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getVisitors()
    {
        return $this->visitors;
    }

    /**
     * Returns post compile visitors.
     *
     * @return array
     */
    public function getPostCompileVisitors()
    {
        $visitors = [];
        foreach ($this->visitors as $visitor) {
            /* @var $visitor VisitorInterface */
            if ($visitor->getType() === VisitorInterface::TYPE_POST_COMPILE) {
                $visitors[] = $visitor;
            }
        }

        return $visitors;
    }

    /**
     * Returns pre compile visitors.
     *
     * @return array
     */
    public function getPreCompileVisitors()
    {
        $visitors = [];
        foreach ($this->visitors as $visitor) {
            /* @var $visitor VisitorInterface */
            if ($visitor->getType() === VisitorInterface::TYPE_PRE_COMPILE) {
                $visitors[] = $visitor;
            }
        }

        return $visitors;
    }

    /**
     * Adds a preprocessor.
     *
     * @param PreProcessorInterface $preProcessor
     * @param int $priority
     *
     * @return $this
     */
    public function addPreProcessor(
        PreProcessorInterface $preProcessor,
        $priority = self::PRIORITY_DEFAULT
    ) {
        $priority = (int) $priority;

        if (!isset($this->preProcessors[$priority])) {
            $this->preProcessors[$priority] = [];
        }

        $this->preProcessors[$priority][] = $preProcessor;

        return $this;
    }

    /**
     * Adds multiple pre preprocessors at once.
     *
     * @param array $preProcessors
     * @param int $priority
     *
     * @return $this
     */
    public function addPreProcessors(array $preProcessors, $priority = self::PRIORITY_DEFAULT)
    {
        foreach ($preProcessors as $preProcessor) {
            $this->addPreProcessor($preProcessor, $priority);
        }

        return $this;
    }

    /**
     * Returns the pre processors.
     *
     * @return array
     */
    public function getPreProcessors()
    {
        $preProcessors = $this->preProcessors;

        // sort by priority
        krsort($preProcessors);

        $result = [];
        foreach ($preProcessors as $priority => $p) {
            $result = array_merge($result, $p);
        }

        return $result;
    }

    /**
     * Adds a postprocessor.
     *
     * @param PostProcessorInterface $postProcessor
     * @param int $priority
     *
     * @return $this
     */
    public function addPostProcessor(
        PostProcessorInterface $postProcessor,
        $priority = self::PRIORITY_DEFAULT
    ) {
        $priority = (int) $priority;

        if (!isset($this->postProcessors[$priority])) {
            $this->postProcessors[$priority] = [];
        }

        $this->postProcessors[$priority][] = $postProcessor;

        return $this;
    }

    /**
     * Adds multiple post processors at once.
     *
     * @param array $postProcessors
     * @param int $priority
     *
     * @return $this
     */
    public function addPostProcessors(
        array $postProcessors,
        $priority = self::PRIORITY_DEFAULT
    ) {
        foreach ($postProcessors as $postProcessor) {
            $this->addPostProcessor($postProcessor, $priority);
        }

        return $this;
    }

    /**
     * Returns the post processors.
     *
     * @return array
     */
    public function getPostProcessors()
    {
        $postProcessors = $this->postProcessors;

        // sort by priority
        krsort($postProcessors);

        $result = [];
        foreach ($postProcessors as $priority => $p) {
            $result = array_merge($result, $p);
        }

        return $result;
    }

    /**
     * @return Parser
     */
    public function getParser()
    {
        return $this->parser;
    }
}

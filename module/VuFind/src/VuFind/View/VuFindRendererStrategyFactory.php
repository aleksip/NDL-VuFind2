<?php

namespace VuFind\View;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\View\Resolver\TemplatePathStack;
use Laminas\View\Strategy\PhpRendererStrategy;
use ZendTwig\Loader\StackLoader;
use ZendTwig\View\TwigStrategy;

class VuFindRendererStrategyFactory implements FactoryInterface
{
    /**
     * @param  ContainerInterface $container
     * @param  string             $name
     * @param  null|array         $options
     *
     * @return VuFindRendererStrategy
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        return new VuFindRendererStrategy(
            $container->get(TwigStrategy::class),
            $container->get(StackLoader::class),
            $container->get(PhpRendererStrategy::class),
            $container->get(TemplatePathStack::class)
        );
    }
}

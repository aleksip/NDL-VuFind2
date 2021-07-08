<?php

namespace VuFind\View;

use Exception;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\EventManager\ListenerAggregateTrait;
use Laminas\View\Renderer\RendererInterface;
use Laminas\View\Resolver\TemplatePathStack;
use Laminas\View\Strategy\PhpRendererStrategy;
use Laminas\View\ViewEvent;
use ZendTwig\Loader\StackLoader;
use ZendTwig\View\TwigStrategy;

class VuFindRendererStrategy implements ListenerAggregateInterface
{
    use ListenerAggregateTrait;

    protected $twigStrategy;

    protected $stackLoader;

    protected $phpStrategy;

    protected $templatePathStack;

    protected $twigRenderer;

    protected $phpRenderer;

    /**
     * VuFindRendererStrategy constructor.
     *
     * @param TwigStrategy        $twigStrategy Twig renderer strategy
     * @param PhpRendererStrategy $phpStrategy  PHP renderer strategy
     */
    public function __construct(TwigStrategy $twigStrategy, StackLoader $stackLoader,
        PhpRendererStrategy $phpStrategy, TemplatePathStack $templatePathStack
    ) {
        $this->twigStrategy = $twigStrategy;
        $this->stackLoader = $stackLoader;
        $this->phpStrategy = $phpStrategy;
        $this->templatePathStack = $templatePathStack;

        $this->twigStrategy->setForceRender(true);
        $this->twigRenderer = $this->twigStrategy->selectRender(new ViewEvent());
        $this->twigStrategy->setForceRender(false);
        $this->phpRenderer = $phpStrategy->getRenderer();
    }

    /**
     * Set the path stack to the paths provided
     *
     * @param array $paths Paths
     *
     * @return void
     */
    public function setPaths($paths)
    {
        $this->templatePathStack->setPaths($paths);
        $this->stackLoader->setPaths($paths);
    }

    /**
     * Attach one or more listeners
     *
     * @param EventManagerInterface $events   Event manager
     * @param int                   $priority Priority
     *
     * @return void
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(
            ViewEvent::EVENT_RENDERER, [$this, 'selectRender'], 1000
        );
        $this->listeners[] = $events->attach(
            ViewEvent::EVENT_RESPONSE, [$this, 'injectResponse'], 1000
        );
    }

    /**
     * Select the renderer
     *
     * @param ViewEvent $e View event
     *
     * @return RendererInterface|null
     */
    public function selectRender(ViewEvent $e)
    {
        try {
            $twig = $this->twigRenderer->canRender($e->getModel()->getTemplate());
        } catch (Exception $e) {
            $twig = false;
        }

        if ($twig) {
            return $this->twigRenderer;
        }
        return $this->phpRenderer;
    }

    /**
     * Populate the response object from the View
     *
     * @param ViewEvent $e View event
     *
     * @return void
     */
    public function injectResponse(ViewEvent $e)
    {
        if ($e->getRenderer() === $this->twigRenderer) {
            $this->twigStrategy->injectResponse($e);
        }
        $this->phpStrategy->injectResponse($e);
    }
}

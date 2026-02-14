<?php

declare(strict_types=1);

namespace CFXP\Core\View;

use CFXP\Core\Container\ContainerInterface;
use CFXP\Core\ServiceProviderInterface;
use CFXP\Core\Exceptions\NotFoundException;
use Psr\EventDispatcher\EventDispatcherInterface;
use CFXP\Core\Exceptions\ContainerResolutionException;

/**
 * View Service Provider
 *
 * Registers the view system with template engines and proper template lookup.
 * Each engine handles its own template resolution strategy.
 */
class ViewServiceProvider implements ServiceProviderInterface
{
    /**
     * @throws NotFoundException
     * @throws ContainerResolutionException
     */
    public function register(ContainerInterface $container): void
    {
        $viewPath = [$container->get('path.view')];
        $cacheDir = $container->get('path.storage') . DIRECTORY_SEPARATOR . 'cache/views';

        $viewEngine = new ViewEngine($viewPath);
        $viewEngine->enableCache($cacheDir);

        $viewEngine->addGlobals([
            'app_name' => $container->has('app.name') ? $container->get('app.name') : 'CFXPrimes',
            'app_url' => $container->has('app.url') ? $container->get('app.url') : 'http://localhost',
            'app_env' => $container->has('app.env') ? $container->get('app.env') : 'production',
            'app_debug' => (bool)($container->has('app.debug') ? $container->get('app.debug') : false),
        ]);

        $container->singleton(ViewEngine::class, fn() => $viewEngine);
        $container->alias('view', ViewEngine::class);
    }

    /**
     * @throws ContainerResolutionException
     */
    public function boot(ContainerInterface $container, ?EventDispatcherInterface $dispatcher = null): void
    {
        $viewEngine = $container->get(ViewEngine::class);
        $viewPath = $container->get('path.view');

        $viewEngine->addPath($viewPath . DIRECTORY_SEPARATOR . 'components', 'components');
        $viewEngine->addPath($viewPath . DIRECTORY_SEPARATOR . 'emails', 'emails');

        $this->registerCustomDirectives($viewEngine);
    }

    /**
     * Register custom directives
     */
    private function registerCustomDirectives(ViewEngine $viewEngine): void
    {
        // Money formatting directive
        $viewEngine->directive('money', function($expression) {
            return "<?= number_format({$expression}, 2) ?>";
        });

        // Asset helper directive
        $viewEngine->directive('asset', function($expression) {
            return "<?= asset({$expression}) ?>";
        });

        // URL helper directive
        $viewEngine->directive('url', function($expression) {
            return "<?= url({$expression}) ?>";
        });

        // Route helper directive
        $viewEngine->directive('route', function($expression) {
            return "<?= route({$expression}) ?>";
        });
    }
}

<?php

declare(strict_types=1);

namespace CFXP\Core\View\Engines;

use CFXP\Core\View\Contracts\ViewEngineInterface;
use CFXP\Core\View\Template;

/**
 * Simple Template Engine - Pure PHP templates with no magic directives.
 * 
 * Templates use:
 * - `$t->layout()` for layout inheritance
 * - `$t->insert()` for partials
 * - `$t->e()` for escaping
 * - `$t->section()` / `$t->start()` / `$t->stop()` for sections
 * 
 * All explicit, no Blade-style directives.
 */
class SimpleTemplateEngine implements ViewEngineInterface
{
    private Template $template;

    /**
     * @param array<int|string, string> $paths
     */
    public function __construct(array $paths = [])
    {
        $basePath = reset($paths) ?: '';
        $this->template = new Template($basePath);
        
        // Add additional paths as namespaces
        foreach ($paths as $namespace => $path) {
            if (is_string($namespace)) {
                $this->template->addPath($path, $namespace);
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = [], ?ViewEngineInterface $engine = null): string
    {
        // Convert dot notation to path (home.index -> home/index)
        $templatePath = str_replace('.', '/', $template);
        return $this->template->render($templatePath, $data);
    }

    public function exists(string $template): bool
    {
        $templatePath = str_replace('.', '/', $template);
        $basePath = $this->getBasePath();
        $file = $basePath . '/' . $templatePath . '.php';
        return is_file($file);
    }

    /**
     * Add a path with optional namespace.
     */
    public function addPath(string $path, ?string $namespace = null): void
    {
        $this->template->addPath($path, $namespace);
    }

    /**
     * Share data across all templates.
     * 
     * @param array<string, mixed> $data
     */
    public function share(array $data): void
    {
        $this->template->share($data);
    }

    /**
     * Register a custom function callable via $t->functionName().
     */
    public function registerFunction(string $name, callable $callback): void
    {
        $this->template->registerFunction($name, $callback);
    }

    /**
     * Set paths for the engine.
     * 
     * @param array<int|string, string> $paths
     */
    public function setPaths(array $paths): void
    {
        foreach ($paths as $namespace => $path) {
            if (is_int($namespace)) {
                // First non-namespaced path becomes base path
                $this->template = new Template((string) $path);
            } else {
                $this->template->addPath($path, $namespace);
            }
        }
    }

    private function getBasePath(): string
    {
        // Access the basePath from the Template - we need to make it accessible
        return '';  // Will use Template's internal resolution
    }
}

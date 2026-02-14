<?php

declare(strict_types=1);

namespace CFXP\Core\View;

use CFXP\Core\View\Contracts\ViewEngineInterface;
use CFXP\Core\View\Engines\PhpEngine;
use CFXP\Core\View\Engines\SimpleTemplateEngine;
use InvalidArgumentException;

class ViewEngine implements ViewEngineInterface
{
    /**
     * Base template paths for engines that need them.
     *
     * @var array<int|string, string>
     */
    private array $basePaths = [];

    /**
     * Global template data available to all engines.
     *
     * @var array<string, mixed>
     */
    private array $globalData = [];

    /**
     * Registered rendering engines.
     *
     * @var array<string, ViewEngineInterface>
     */
    private array $engines = [];

    /**
     * File extension to engine mapping.
     *
     * @var array<string, string>
     */
    private array $extensions = [
        'php' => 'template',    // Use template engine for .php files (supports directives)
        'tpl' => 'template',
        'phtml' => 'php'        // Use PHP engine for plain PHP files (.phtml)
    ];

    /**
     * Cache directory for engines that support it
     */
    private ?string $cacheDir = null;

    /**
     * @param array<string, mixed> $basePaths
     */
    public function __construct(array $basePaths = [])
    {
        $this->basePaths = $basePaths;
        $this->registerDefaultEngines();
    }

    /**
     * Render a template
      * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = [], ?ViewEngineInterface $engine = null): string
    {
        $renderEngine = $engine ?? $this->resolveEngine($template);
        $mergedData = array_merge($this->globalData, $data);
        
        return $renderEngine->render($template, $mergedData);
    }

    /**
     * Add a base path for engines that need filesystem access
     */
    public function addPath(string $path, ?string $namespace = null): void
    {
        if ($namespace) {
            $this->basePaths[$namespace] = $path;
        } else {
            $this->basePaths[] = $path;
        }

        foreach ($this->engines as $engine) {
            if (method_exists($engine, 'addPath')) {
                $engine->addPath($path, $namespace);
            }
        }
    }

    /**
     * Add global data available to all templates
     */
    public function addGlobal(string $key, mixed $value): void
    {
        $this->globalData[$key] = $value;
    }

    /**
     * Add multiple global variables
      * @param array<string, mixed> $data
     */
    public function addGlobals(array $data): void
    {
        $this->globalData = array_merge($this->globalData, $data);
    }

    /**
     * Register a rendering engine
     */
    public function engine(string $name, ViewEngineInterface $engine): void
    {
        if (method_exists($engine, 'setPaths')) {
            $engine->setPaths($this->basePaths);
        }
        
        if ($this->cacheDir && method_exists($engine, 'setCacheDir')) {
            $engine->setCacheDir($this->cacheDir);
        }

        $this->engines[$name] = $engine;
    }

    /**
     * Map file extension to engine
     */
    public function extension(string $extension, string $engineName): void
    {
        $this->extensions[$extension] = $engineName;
    }

    /**
     * Enable template caching
     */
    public function enableCache(string $cacheDir): void
    {
        $this->cacheDir = $cacheDir;

        foreach ($this->engines as $engine) {
            if (method_exists($engine, 'setCacheDir')) {
                $engine->setCacheDir($cacheDir);
            }
        }
    }

    /**
     * Add custom directive to template engine
     */
    public function directive(string $name, callable $handler): void
    {
        if (isset($this->engines['template'])) {
            $engine = $this->engines['template'];
            if (method_exists($engine, 'directive')) {
                $engine->directive($name, $handler);
            }
        }
    }

    /**
     * Resolve which engine to use for a template
     */
    private function resolveEngine(string $template): ViewEngineInterface
    {
        $actualFile = $this->findActualTemplateFile($template);
        
        if ($actualFile) {
            $extension = pathinfo($actualFile, PATHINFO_EXTENSION) ?: 'php';
        } else {
            $extension = pathinfo($template, PATHINFO_EXTENSION) ?: 'php';
        }
        
        $engineName = $this->extensions[$extension] ?? 'template';
        
        if (!isset($this->engines[$engineName])) {
            throw new InvalidArgumentException("Engine '{$engineName}' not registered");
        }

        return $this->engines[$engineName];
    }

    /**
     * Find the actual template file to determine real extension
     */
    private function findActualTemplateFile(string $template): ?string
    {
        $templatePath = str_replace('.', '/', $template);

        foreach ($this->basePaths as $basePath) {
            $possibilities = [
                $basePath . '/' . $templatePath,
                $basePath . '/' . $templatePath . '.php',
                $basePath . '/' . $templatePath . '.tpl',
            ];

            foreach ($possibilities as $path) {
                if (is_file($path)) {
                    return $path;
                }
            }
        }

        return null;
    }

    /**
     * Resolve template exists
     */
    public function exists(string $template): bool
    {
        try {
            $engine = $this->resolveEngine($template);
            return method_exists($engine, 'exists') ? $engine->exists($template) : true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Register default engines
     */
    private function registerDefaultEngines(): void
    {
        $this->engine('php', new PhpEngine($this->basePaths));
        $this->engine('template', new SimpleTemplateEngine($this->basePaths));
    }
}

<?php

declare(strict_types=1);

namespace CFXP\Core\View\Engines;

use CFXP\Core\View\Contracts\ViewEngineInterface;
use RuntimeException;

/**
 * PHP Template Engine
 * 
 * Renders PHP templates with template lookup and variable extraction.
 * Each engine is responsible for its own template resolution strategy.
 */
class PhpEngine implements ViewEngineInterface
{
    /**
     * Template search paths
     */
    /** @var array<string, mixed> */

    private array $paths = [];

    /**
     * Namespaced paths
     */
    /** @var array<string, mixed> */

    private array $namespacePaths = [];

    /**
     * @param array<string> $paths
     */
    public function __construct(array $paths = [])
    {
        $this->paths = $paths;
    }

    /**
     * Add a template path
     */
    public function addPath(string $path, ?string $namespace = null): void
    {
        if ($namespace) {
            $this->namespacePaths[$namespace] = $path;
        } else {
            $this->paths[] = $path;
        }
    }

    /**
     * Set multiple paths at once
      * @param array<string> $paths
     */
    public function setPaths(array $paths): void
    {
        $this->paths = array_merge($this->paths, $paths);
    }

    /**
     * Render a PHP template
      * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = [], ?ViewEngineInterface $engine = null): string
    {
        $templatePath = $this->findTemplate($template);
        
        if (!$templatePath) {
            throw new RuntimeException("Template '{$template}' not found in paths: " . implode(', ', $this->getAllPaths()));
        }

        return $this->renderFile($templatePath, $data);
    }

    /**
     * Check if template exists
     */
    public function exists(string $template): bool
    {
        return $this->findTemplate($template) !== null;
    }

    /**
     * Find a template in the search paths
     */
    private function findTemplate(string $template): ?string
    {
        // Handle namespaced templates (e.g., "emails::welcome")
        if (str_contains($template, '::')) {
            [$namespace, $templateName] = explode('::', $template, 2);
            
            if (isset($this->namespacePaths[$namespace])) {
                // Convert dot notation to path (e.g., "user.profile" -> "user/profile")
                $templatePath = str_replace('.', '/', $templateName);
                $path = $this->namespacePaths[$namespace] . '/' . $templatePath;
                if (!str_ends_with($path, '.php')) {
                    $path .= '.php';
                }
                
                if (is_file($path)) {
                    return $path;
                }
            }
            
            return null;
        }

        // Convert dot notation to path (e.g., "home.index" -> "home/index")
        $templatePath = str_replace('.', '/', $template);

        // Search in regular paths
        foreach ($this->getAllPaths() as $basePath) {
            $possibilities = [
                $basePath . '/' . $templatePath,
                $basePath . '/' . $templatePath . '.php',
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
     * Get all search paths (named + regular)
     */
    /**
     * @return array<string, mixed>
     */
private function getAllPaths(): array
    {
        return array_merge($this->paths, array_values($this->namespacePaths));
    }

    /**
     * Render a PHP file with variable extraction
      * @param array<string, mixed> $data
     */
    private function renderFile(string $templatePath, array $data): string
    {
        // Extract variables to make them available in the template
        extract($data, EXTR_SKIP);
        
        // Start output buffering
        ob_start();
        
        try {
            // Include the template file
            include $templatePath;
            
            // Get the rendered content
            return ob_get_contents() ?: '';
        } finally {
            // Always clean the buffer
            ob_end_clean();
        }
    }
}
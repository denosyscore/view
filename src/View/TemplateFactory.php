<?php

declare(strict_types=1);

namespace CFXP\Core\View;

/**
 * Factory for creating and configuring Template instances.
 * 
 * Use this in controllers to render templates.
 */
class TemplateFactory
{
    private Template $template;

    public function __construct(string $viewsPath)
    {
        $this->template = new Template($viewsPath);
    }

    /**
     * Render a template with data.
     * 
     * @param array<string, mixed> $data
     */
    public function render(string $name, array $data = []): string
    {
        return $this->template->render($name, $data);
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
     * Preassign data to a specific template.
     * 
     * @param array<string, mixed> $data
     */
    public function addData(array $data, string $templateName): void
    {
        $this->template->addData($data, $templateName);
    }

    /**
     * Register a custom function.
     */
    public function registerFunction(string $name, callable $callback): void
    {
        $this->template->registerFunction($name, $callback);
    }
}

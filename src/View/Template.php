<?php

declare(strict_types=1);

namespace CFXP\Core\View;

/**
 * Explicit PHP template engine.
 * 
 * Templates receive `$t` (this object) and `$data` (array with PHPDoc types).
 * No global helpers, no magic - just explicit method calls.
 */
class Template
{
    private ?string $layoutName = null;
    /** @var array<string, mixed> */
    private array $layoutData = [];
    /** @var array<string, string> */
    private array $sections = [];
    private ?string $currentSection = null;
    /** @var array<string, string> */
    private array $paths = [];
    /** @var array<string, mixed> */
    private array $sharedData = [];
    /** @var array<string, array<string, mixed>> */
    private array $templateData = [];
    /** @var array<string, callable> */
    private array $functions = [];

    public function __construct(
        private readonly string $basePath,
    ) {
        $this->paths[''] = $basePath;
    }

    /**
     * Set the layout for this template.
     * 
     * @param array<string, mixed> $data
     */
    public function layout(string $name, array $data = []): void
    {
        $this->layoutName = $name;
        $this->layoutData = $data;
    }

    /**
     * Insert another template inline.
     * 
     * @param array<string, mixed> $data
     */
    public function insert(string $name, array $data = []): void
    {
        echo $this->render($name, $data);
    }

    /**
     * Start capturing a named section.
     */
    public function start(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    /**
     * Stop capturing the current section.
     */
    public function stop(): void
    {
        if ($this->currentSection !== null) {
            $this->sections[$this->currentSection] = ob_get_clean() ?: '';
            $this->currentSection = null;
        }
    }

    /**
     * Get a captured section's content.
     */
    public function section(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    /**
     * Escape HTML entities.
     */
    public function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * URL encode a value.
     */
    public function eUrl(string $value): string
    {
        return urlencode($value);
    }

    /**
     * JSON encode for JavaScript.
     */
    public function eJs(mixed $value): string
    {
        return json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?: 'null';
    }

    /**
     * Add a path with optional namespace.
     */
    public function addPath(string $path, ?string $namespace = null): void
    {
        $this->paths[$namespace ?? ''] = $path;
    }

    /**
     * Share data across all templates.
     * 
     * @param array<string, mixed> $data
     */
    public function share(array $data): void
    {
        $this->sharedData = array_merge($this->sharedData, $data);
    }

    /**
     * Preassign data to a specific template.
     * 
     * @param array<string, mixed> $data
     */
    public function addData(array $data, string $templateName): void
    {
        if (!isset($this->templateData[$templateName])) {
            $this->templateData[$templateName] = [];
        }
        $this->templateData[$templateName] = array_merge($this->templateData[$templateName], $data);
    }

    /**
     * Register a custom function.
     */
    public function registerFunction(string $name, callable $callback): void
    {
        $this->functions[$name] = $callback;
    }

    /**
     * Call a registered function.
     * 
     * @param array<int, mixed> $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (isset($this->functions[$name])) {
            return ($this->functions[$name])(...$arguments);
        }
        throw new \BadMethodCallException("Function '{$name}' is not registered.");
    }

    /**
     * Render a template.
     * 
     * @param array<string, mixed> $data
     */
    public function render(string $name, array $data = []): string
    {
        $file = $this->resolveTemplate($name);
        
        // Merge data: shared -> template-specific -> passed
        $mergedData = array_merge(
            $this->sharedData,
            $this->templateData[$name] ?? [],
            $data
        );

        // Make $t and $data available
        $t = $this;
        $data = $mergedData;
        
        ob_start();
        include $file;
        $content = ob_get_clean() ?: '';

        // Handle layout
        if ($this->layoutName !== null) {
            $layoutName = $this->layoutName;
            $layoutData = array_merge($this->layoutData, ['content' => $content]);
            $this->layoutName = null;
            $this->layoutData = [];
            return $this->render($layoutName, $layoutData);
        }

        return $content;
    }

    /**
     * Resolve template path, handling namespaces.
     */
    private function resolveTemplate(string $name): string
    {
        // Check for namespace (emails::welcome)
        if (str_contains($name, '::')) {
            [$namespace, $template] = explode('::', $name, 2);
            $basePath = $this->paths[$namespace] ?? throw new \InvalidArgumentException(
                "Template namespace '{$namespace}' not registered."
            );
            $file = $basePath . '/' . $template . '.php';
        } else {
            $file = $this->paths[''] . '/' . $name . '.php';
        }

        if (!is_file($file)) {
            throw new \InvalidArgumentException("Template '{$name}' not found at '{$file}'.");
        }

        return $file;
    }
}

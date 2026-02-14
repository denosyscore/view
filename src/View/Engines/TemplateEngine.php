<?php

declare(strict_types=1);

namespace CFXP\Core\View\Engines;

use CFXP\Core\View\Contracts\ViewEngineInterface;
use CFXP\Core\View\Directives\DirectiveRegistry;
use CFXP\Core\View\Exceptions\TemplateCompilationException;
use CFXP\Core\View\Exceptions\TemplateRenderException;
use RuntimeException;

/**
 * Template Engine with Blade-like directives
 *
 * Handles template lookup, compilation, and caching.
 * Each engine manages its own template resolution.
 */
class TemplateEngine implements ViewEngineInterface
{
    /** @var array<string> Template search paths */
    private array $paths = [];

    /** @var array<string, string> Namespaced paths */
    private array $namespacePaths = [];

    /**
     * Cache directory for compiled templates
     */
    private ?string $cacheDir = null;

    /**
     * Directive registry
     */
    private DirectiveRegistry $directives;

    /**
     * Layout inheritance properties
     */
    private ?string $extendedLayout = null;
    /** @var array<string, string> */
    private array $layoutData = [];

    /** @var array<string, string> */
    private array $sections = [];

    /** @var array<int, string|null> */
    private array $sectionStack = [];

    private ?string $currentSection = null;

    /**
     * @param array<string> $paths
     */
    public function __construct(array $paths = [], ?string $cacheDir = null)
    {
        $this->paths = $paths;
        $this->cacheDir = $cacheDir;
        $this->directives = new DirectiveRegistry();
        // DirectiveRegistry already registers default directives in its constructor
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
     * Set cache directory
     */
    public function setCacheDir(?string $cacheDir): void
    {
        $this->cacheDir = $cacheDir;
    }

    /**
     * Add a custom directive
     */
    public function directive(string $name, callable $handler): void
    {
        $this->directives->register($name, $handler);
    }

    /**
     * Render a template
      * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = [], ?ViewEngineInterface $engine = null): string
    {
        return $this->renderWithInheritance($template, $data, true);
    }

    /**
     * Render a template without resetting section/layout state (for partials/includes)
      * @param array<string, mixed> $data
     */
    public function renderPartial(string $template, array $data = []): string
    {
        return $this->renderWithInheritance($template, $data, false);
    }

    /**
     * Internal render method that handles layout inheritance
      * @param array<string, mixed> $data
     */
    private function renderWithInheritance(string $template, array $data = [], bool $resetState = false): string
    {
        // Reset state only at the beginning of a new render cycle
        if ($resetState) {
            $this->extendedLayout = null;
            $this->layoutData = [];
            $this->sections = [];
            $this->sectionStack = [];
            $this->currentSection = null;
        }

        $templatePath = $this->findTemplate($template);

        if (!$templatePath) {
            throw new RuntimeException("Template '{$template}' not found in paths: " . implode(', ', $this->getAllPaths()));
        }

        $compiledPath = $this->getCompiledPath($templatePath);

        // Compile if needed
        if (!file_exists($compiledPath) || filemtime($templatePath) > filemtime($compiledPath)) {
            $this->compile($templatePath, $compiledPath);
        }

        // Render the template (this will populate sections and possibly set extendedLayout)
        $output = $this->renderCompiled($compiledPath, $data);

        // If this template extends a layout, render the layout instead
        if ($this->extendedLayout) {
            // Prepare data for the layout (merge with any layout-specific data)
            $layoutData = array_merge($data, $this->layoutData);

            // Get the layout template name
            $layoutTemplate = $this->extendedLayout;

            // Clear the extendedLayout to prevent infinite recursion
            $this->extendedLayout = null;
            $this->layoutData = [];

            // Render the layout template with sections already captured
            $layoutOutput = $this->renderWithInheritance($layoutTemplate, $layoutData, false);
            // Append child output to any layout yield if yield was not used
            return $layoutOutput !== '' ? $layoutOutput : $output;
        }

        return $output;
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
        // Handle namespaced templates
        if (str_contains($template, '::')) {
            [$namespace, $templateName] = explode('::', $template, 2);

            if (isset($this->namespacePaths[$namespace])) {
                // Convert dot notation to path (e.g., "user.profile" -> "user/profile")
                $templatePath = str_replace('.', '/', $templateName);
                $path = $this->namespacePaths[$namespace] . '/' . $templatePath;
                if (!str_ends_with($path, '.php') && !str_ends_with($path, '.tpl')) {
                    // Try both extensions
                    if (is_file($path . '.tpl')) {
                        return $path . '.tpl';
                    } elseif (is_file($path . '.php')) {
                        return $path . '.php';
                    }
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
                $basePath . '/' . $templatePath . '.tpl',
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
     * Get all search paths.
     *
     * @return array<string>
     */
    private function getAllPaths(): array
    {
        return array_merge($this->paths, array_values($this->namespacePaths));
    }

    /**
     * Compile template
     */
    private function compile(string $templatePath, string $compiledPath): void
    {
        $content = file_get_contents($templatePath);
        $compiled = $this->directives->compile($content);

        // Add source mapping comment at the beginning of compiled file
        // TODO: Source mapping should start from the root of the project
        $sourceMapping = "<?php /* SOURCE: {$templatePath} */ ?>\n";
        $compiled = $sourceMapping . $compiled;

        // Validate compiled template for matching directive pairs
        $this->validateCompiledTemplate($compiled, $templatePath);

        // Ensure cache directory exists
        if ($this->cacheDir && !is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }

        file_put_contents($compiledPath, $compiled);
    }

    /**
     * Get compiled template path
     */
    private function getCompiledPath(string $templatePath): string
    {
        if (!$this->cacheDir) {
            return $templatePath; // No caching
        }

        $hash = hash('md5', $templatePath);
        return $this->cacheDir . '/template_' . $hash . '.php';
    }

    /**
     * Render compiled template
      * @param array<string, mixed> $data
     */
    private function renderCompiled(string $compiledPath, array $data): string
    {
        extract($data, EXTR_SKIP);

        // Get the original template path from the compiled file
        $templatePath = $this->extractSourcePath($compiledPath);

        // Buffer output to capture any PHP errors
        $errorReporting = error_reporting();
        $displayErrors = ini_get('display_errors');

        try {
            // Disable direct error output to prevent premature output
            error_reporting(E_ALL);
            ini_set('display_errors', '0');

            // Use output buffering to catch any errors
            ob_start();
            $errorOccurred = false;

            // Set error handler to catch PHP errors during include
            set_error_handler(function($severity, $message, $file, $line) use (&$errorOccurred, $templatePath, $compiledPath) {
                $errorOccurred = true;
                // Simplify the error message
                $simpleMessage = $this->simplifyPhpErrorMessage($message);
                // Convert PHP errors to template exceptions
                throw new TemplateRenderException(
                    $simpleMessage,
                    $templatePath ?: $file,
                    $this->mapCompiledLineToTemplate($line, $compiledPath, $templatePath),
                    $compiledPath
                );
            });

            try {
                $result = include $compiledPath;

                // Check if include failed
                if ($result === false && $errorOccurred) {
                    throw new TemplateRenderException(
                        "Failed to render template",
                        $templatePath ?: $compiledPath,
                        1,
                        $compiledPath
                    );
                }

                $output = ob_get_contents();
                return $output !== false ? $output : '';
            } finally {
                restore_error_handler();
            }
        } catch (TemplateRenderException $e) {
            // Clean any partial output
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            // Re-throw template exceptions as-is
            throw $e;
        } catch (\Throwable $e) {
            // Clean any partial output
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            // Wrap other exceptions
            throw TemplateRenderException::fromCompiledError(
                $e,
                $templatePath ?: $compiledPath,
                $compiledPath
            );
        } finally {
            // Always clean up output buffer
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            // Restore original error settings
            error_reporting($errorReporting);
            ini_set('display_errors', $displayErrors);
        }
    }

    /**
     * Layout inheritance methods
     */

    /**
     * Extend a layout template
      * @param array<string, mixed> $data
     */
    public function extend(string $layout, array $data = []): void
    {
        $this->extendedLayout = $layout;
        $this->layoutData = array_merge($this->layoutData, $data);
    }

    /**
     * Start a section
     */
    public function startSection(string $name): void
    {
        $this->sectionStack[] = $this->currentSection;
        $this->currentSection = $name;
        ob_start();
    }

    /**
     * Set an inline section (doesn't require endSection)
     */
    public function setSection(string $name, string $value): void
    {
        $this->sections[$name] = $value;
    }

    /**
     * End the current section
     */
    public function endSection(): void
    {
        if ($this->currentSection === null) {
            throw new RuntimeException('No section started');
        }

        $content = ob_get_clean();
        $this->sections[$this->currentSection] = $content;
        $this->currentSection = array_pop($this->sectionStack);
    }

    /**
     * Yield a section's content
     */
    public function yieldSection(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    /**
     * Check if a section exists
     */
    public function hasSection(string $name): bool
    {
        return isset($this->sections[$name]);
    }

    /**
     * Get all sections.
     *
     * @return array<string, string>
     */
    public function getSections(): array
    {
        return $this->sections;
    }

    /**
     * Validate compiled template for matching directive pairs
     */
    private function validateCompiledTemplate(string $compiled, string $templatePath): void
    {
        // Track directive pairs that need matching
        $directivePairs = [
            'startSection' => 'endSection',
            'if' => 'endif',
            'foreach' => 'endforeach',
            'for' => 'endfor',
            'while' => 'endwhile',
            'isset' => 'endisset',
            'empty' => 'endempty',
            'auth' => 'endauth',
            'guest' => 'endguest',
        ];

        // Convert absolute path to relative path from views directory
        $relativePath = $this->getRelativeTemplatePath($templatePath);

        // Count directive occurrences in the compiled PHP code
        foreach ($directivePairs as $start => $end) {
            // For section directives, look for the actual PHP method calls
            if ($start === 'startSection') {
                // Only count startSection calls (not setSection which is for inline sections)
                $startPattern = '/\$this->startSection\s*\(/i';
                $endPattern = '/\$this->endSection\s*\(\)/i';
            } else {
                // For control structures, look for PHP syntax
                $startPattern = '/<\?php\s+' . preg_quote($start, '/') . '\s*\(/i';
                $endPattern = '/<\?php\s+' . preg_quote($end, '/') . '[;:\s]/i';
            }

            preg_match_all($startPattern, $compiled, $startMatches);
            preg_match_all($endPattern, $compiled, $endMatches);

            $startCount = count($startMatches[0]);
            $endCount = count($endMatches[0]);

            if ($startCount !== $endCount) {
                // Find the line number of the first unmatched directive
                $lineNumber = $this->findDirectiveLine($compiled, $start === 'startSection' ? 'startSection' : $start);

                if ($start === 'startSection') {
                    $message = $startCount > $endCount
                        ? "Unclosed @section - missing @endsection"
                        : "Extra @endsection without matching @section";
                    throw new TemplateCompilationException(
                        $message,
                        $templatePath,
                        $lineNumber
                    );
                } else {
                    $message = $startCount > $endCount
                        ? "Unclosed @{$start} - missing @{$end}"
                        : "Extra @{$end} without matching @{$start}";
                    throw new TemplateCompilationException(
                        $message,
                        $templatePath,
                        $lineNumber
                    );
                }
            }
        }
    }

    /**
     * Get relative template path from views directory
     */
    private function getRelativeTemplatePath(string $absolutePath): string
    {
        // Try to find the path relative to any of the registered view paths
        foreach ($this->getAllPaths() as $basePath) {
            if (str_starts_with($absolutePath, $basePath)) {
                $relativePath = substr($absolutePath, strlen($basePath));
                return 'views' . $relativePath;
            }
        }

        // If not in any registered path, try common view directories
        $patterns = [
            '/resources/views/',
            '/views/',
        ];

        foreach ($patterns as $pattern) {
            if (($pos = strpos($absolutePath, $pattern)) !== false) {
                return 'views/' . substr($absolutePath, $pos + strlen($pattern));
            }
        }

        // Fallback: just return the filename
        return basename($absolutePath);
    }

    /**
     * Get original template info from compiled path or cache mapping
     */
    private function getOriginalTemplatePath(string $compiledPath): string
    {
        // If it's not a cached file, it's the original template
        if (!str_contains($compiledPath, '/template_')) {
            return $this->getRelativeTemplatePath($compiledPath);
        }

        // For cached templates, we need to find the original
        // This is a simplified approach - in production you might want to maintain a mapping
        return 'compiled template';
    }

    /**
     * Extract source path from compiled template
     */
    private function extractSourcePath(string $compiledPath): ?string
    {
        if (!is_readable($compiledPath)) {
            return null;
        }

        // Read first few lines to find SOURCE comment
        $handle = fopen($compiledPath, 'r');
        if ($handle) {
            $firstLine = fgets($handle);
            fclose($handle);

            // Look for SOURCE: comment
            if (preg_match('/\/\*\s*SOURCE:\s*(.+?)\s*\*\//i', $firstLine, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Map compiled line number to template line (simplified)
     */
    private function mapCompiledLineToTemplate(int $compiledLine, string $compiledPath, ?string $templatePath): int
    {
        if (!$templatePath || !is_readable($templatePath) || !is_readable($compiledPath)) {
            return $compiledLine;
        }

        // This is a simplified mapping - could be made more sophisticated
        // by analyzing the structure of compiled output
        return max(1, $compiledLine - 1); // Subtract 1 for the source comment we added
    }

    /**
     * Find line number of a directive in compiled content
     */
    private function findDirectiveLine(string $compiled, string $directive): int
    {
        $lines = explode("\n", $compiled);
        $pattern = $directive === 'startSection' ? '/\$this->startSection\s*\(/i' : '/' . preg_quote($directive, '/') . '/i';

        foreach ($lines as $index => $line) {
            if (preg_match($pattern, $line)) {
                return $index + 1; // Line numbers are 1-based
            }
        }

        return 1; // Default to first line
    }

    /**
     * Simplify PHP error messages for templates
     */
    private function simplifyPhpErrorMessage(string $message): string
    {
        // Common patterns to simplify
        $patterns = [
            '/^Undefined variable:\s*(.+)$/i' => 'Undefined variable: $$$1',
            '/^Undefined index:\s*(.+)$/i' => 'Missing key: $1',
            '/^Undefined array key\s*"?([^"]+)"?$/i' => 'Missing key: $1',
            '/^Trying to access array offset on value of type null$/i' => 'Accessing null as array',
            '/^Call to undefined function\s*(.+)\(\)$/i' => 'Unknown function: $1()',
            '/^syntax error, unexpected (.+?)(?:,|\s|$)/i' => 'Syntax error: $1',
            '/^Call to a member function (.+?)\(\) on null$/i' => 'Calling $1() on null',
            '/^Cannot use object of type (.+?) as array$/i' => 'Using $1 as array',
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (preg_match($pattern, $message, $matches)) {
                return preg_replace($pattern, $replacement, $message);
            }
        }

        // Truncate very long messages
        if (strlen($message) > 80) {
            return substr($message, 0, 77) . '...';
        }

        return $message;
    }
}

<?php

declare(strict_types=1);

namespace CFXP\Core\View\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when template compilation fails
 *
 * This exception properly tracks the template file location
 * so that Whoops and other error handlers can display the
 * actual template file instead of the engine file.
 */
class TemplateCompilationException extends RuntimeException
{
    private string $templatePath;
    private int $templateLine;
    private ?string $compiledPath;

    public function __construct(
        string $message,
        string $templatePath,
        int $templateLine = 0,
        ?string $compiledPath = null,
        ?Throwable $previous = null
    ) {
        // Extract relative template name for the message
        $templateName = self::extractTemplateName($templatePath);
        $fullMessage = $message . " [{$templateName}]";

        parent::__construct($fullMessage, 0, $previous);

        $this->templatePath = $templatePath;
        $this->templateLine = $templateLine;
        $this->compiledPath = $compiledPath;

        // Override the file and line to point to the template
        $this->file = $templatePath;
        $this->line = $templateLine;
    }

    /**
     * Extract a clean template name from the full path
     */
    private static function extractTemplateName(string $path): string
    {
        // Look for common view directory patterns
        $patterns = [
            '/\/resources\/views\/(.+)$/',
            '/\/views\/(.+)$/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $path, $matches)) {
                // Keep the relative path with .php extension
                return $matches[1];
            }
        }

        // Fallback to basename
        return basename($path);
    }

    public function getTemplatePath(): string
    {
        return $this->templatePath;
    }

    public function getTemplateLine(): int
    {
        return $this->templateLine;
    }

    public function getCompiledPath(): ?string
    {
        return $this->compiledPath;
    }
}
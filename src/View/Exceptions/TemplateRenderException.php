<?php

declare(strict_types=1);

namespace CFXP\Core\View\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when template rendering fails
 *
 * This exception properly tracks the template file location
 * so that Whoops and other error handlers can display the
 * actual template file instead of the engine file.
 */
class TemplateRenderException extends RuntimeException
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

    /**
     * Create from a PHP error in compiled template
     */
    public static function fromCompiledError(
        Throwable $error,
        string $templatePath,
        string $compiledPath
    ): self {
        // Try to map the line number from compiled to source
        $templateLine = self::mapCompiledLineToTemplate($error, $compiledPath, $templatePath);

        // Simplify common PHP error messages
        $message = $error->getMessage();
        $message = self::simplifyErrorMessage($message);

        // Don't pass the simplified message to constructor as it will add template name
        return new self(
            $message,
            $templatePath,
            $templateLine,
            $compiledPath,
            $error
        );
    }

    /**
     * Simplify common PHP error messages for better readability
     */
    private static function simplifyErrorMessage(string $message): string
    {
        // Remove verbose PHP prefixes
        $patterns = [
            '/^Undefined variable:\s*/i' => 'Undefined variable: $',
            '/^Undefined index:\s*/i' => 'Missing array key: ',
            '/^Undefined offset:\s*/i' => 'Invalid array index: ',
            '/^Call to undefined function\s*/i' => 'Unknown function: ',
            '/^syntax error, unexpected\s*/i' => 'Syntax error: unexpected ',
            '/^Cannot access\s*/i' => 'Access denied: ',
            '/^Call to a member function (.+?) on\s+null/i' => 'Calling $1 on null',
            '/^Argument \d+ passed to .+ must be .+, .+ given/i' => 'Type error in function call',
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (preg_match($pattern, $message)) {
                $message = preg_replace($pattern, $replacement, $message);
                break;
            }
        }

        // Truncate very long messages
        if (strlen($message) > 100) {
            $message = substr($message, 0, 97) . '...';
        }

        return $message;
    }

    /**
     * Try to map compiled line number to template line
     */
    private static function mapCompiledLineToTemplate(
        Throwable $error,
        string $compiledPath,
        string $templatePath
    ): int {
        // If the error occurred in the compiled file
        if ($error->getFile() === $compiledPath) {
            // This is a simplified mapping - you could make this more sophisticated
            // by analyzing the compiled output structure
            $compiledLine = $error->getLine();

            // Read both files to try to map lines
            if (is_readable($compiledPath) && is_readable($templatePath)) {
                $compiledLines = file($compiledPath, FILE_IGNORE_NEW_LINES);
                $templateLines = file($templatePath, FILE_IGNORE_NEW_LINES);

                // Look for markers or patterns to map back to template
                // For now, return a rough estimate
                if ($compiledLine > 0 && count($templateLines) > 0) {
                    // Simple heuristic: compiled files usually have more lines
                    $ratio = count($templateLines) / max(count($compiledLines), 1);
                    return max(1, (int)($compiledLine * $ratio));
                }
            }
        }

        return 1; // Default to first line if we can't determine
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
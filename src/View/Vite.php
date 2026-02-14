<?php

declare(strict_types=1);

namespace Denosys\View;

/**
 * Vite asset helper for PHP.
 * 
 * Generates correct asset tags for both development (with HMR) and production.
 */
class Vite
{
    /** @var array<string, array<string, mixed>>|null */
    private static ?array $manifest = null;
    private static ?bool $devServerRunning = null;

    private string $buildDirectory = 'build';
    private string $hotFile = 'hot';
    private string $devServerUrl = 'http://localhost:5173';

    /**
     * Generate script/link tags for the given entrypoints.
     *
     * @param array<string> $entrypoints
     * @return string HTML tags
     */
    public function __invoke(array $entrypoints): string
    {
        if ($this->isDevServerRunning()) {
            return $this->generateDevTags($entrypoints);
        }

        return $this->generateProductionTags($entrypoints);
    }

    /**
     * Generate tags for development mode (HMR enabled).
     *
     * @param array<string> $entrypoints
     */
    private function generateDevTags(array $entrypoints): string
    {
        $tags = [];

        // Vite client for HMR
        $tags[] = sprintf(
            '<script type="module" src="%s/@vite/client"></script>',
            $this->devServerUrl
        );

        foreach ($entrypoints as $entrypoint) {
            $url = $this->devServerUrl . '/' . ltrim($entrypoint, '/');
            
            if ($this->isCssPath($entrypoint)) {
                // In dev mode, CSS is injected by Vite client
                $tags[] = sprintf('<link rel="stylesheet" href="%s">', $url);
            } else {
                $tags[] = sprintf('<script type="module" src="%s"></script>', $url);
            }
        }

        return implode("\n", $tags);
    }

    /**
     * Generate tags for production mode (uses manifest).
     *
     * @param array<string> $entrypoints
     */
    private function generateProductionTags(array $entrypoints): string
    {
        $manifest = $this->getManifest();
        $tags = [];
        $cssLoaded = [];

        foreach ($entrypoints as $entrypoint) {
            $chunk = $manifest[$entrypoint] ?? null;
            
            if ($chunk === null) {
                continue;
            }

            // Load CSS files for this chunk
            if (isset($chunk['css'])) {
                foreach ($chunk['css'] as $cssFile) {
                    if (!isset($cssLoaded[$cssFile])) {
                        $tags[] = sprintf(
                            '<link rel="stylesheet" href="/%s/%s">',
                            $this->buildDirectory,
                            $cssFile
                        );
                        $cssLoaded[$cssFile] = true;
                    }
                }
            }

            // Load the main file
            $file = $chunk['file'];
            
            if ($this->isCssPath($entrypoint)) {
                $tags[] = sprintf(
                    '<link rel="stylesheet" href="/%s/%s">',
                    $this->buildDirectory,
                    $file
                );
            } else {
                $tags[] = sprintf(
                    '<script type="module" src="/%s/%s"></script>',
                    $this->buildDirectory,
                    $file
                );
            }
        }

        return implode("\n", $tags);
    }

    /**
     * Check if the Vite dev server is running.
     */
    private function isDevServerRunning(): bool
    {
        if (self::$devServerRunning !== null) {
            return self::$devServerRunning;
        }

        // Check for hot file (created by `npm run dev`)
        $hotPath = public_path($this->hotFile);
        
        if (file_exists($hotPath)) {
            $content = file_get_contents($hotPath);
            if ($content !== false) {
                $url = trim($content);
                if ($url) {
                    $this->devServerUrl = $url;
                }
            }
            self::$devServerRunning = true;
            return true;
        }

        // In development, try to check if dev server responds
        // But don't throw errors if it fails - just assume production mode
        if ($this->isRunningInDevelopment()) {
            try {
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 0.1,
                        'ignore_errors' => true,
                    ]
                ]);
                
                // Use error suppression AND try-catch for maximum safety
                $result = @file_get_contents($this->devServerUrl . '/@vite/client', false, $context);
                self::$devServerRunning = $result !== false;
                return self::$devServerRunning;
            } catch (\Throwable $e) {
                // Dev server not running or can't be reached
                self::$devServerRunning = false;
                return false;
            }
        }

        self::$devServerRunning = false;
        return false;
    }

    /**
     * Get the manifest for production builds.
     *
     * @return array<string, array<string, mixed>>
     */
    private function getManifest(): array
    {
        if (self::$manifest !== null) {
            return self::$manifest;
        }

        $manifestPath = public_path($this->buildDirectory . '/.vite/manifest.json');
        
        if (!file_exists($manifestPath)) {
            // Try legacy manifest location
            $manifestPath = public_path($this->buildDirectory . '/manifest.json');
        }

        if (!file_exists($manifestPath)) {
            return self::$manifest = [];
        }

        $content = file_get_contents($manifestPath);
        
        if ($content === false) {
            return self::$manifest = [];
        }

        $decoded = json_decode($content, true);
        
        return self::$manifest = is_array($decoded) ? $decoded : [];
    }

    /**
     * Check if path is a CSS file.
     */
    private function isCssPath(string $path): bool
    {
        return str_ends_with($path, '.css') || str_ends_with($path, '.scss') || str_ends_with($path, '.sass');
    }

    /**
     * Check if running in development environment.
     */
    private function isRunningInDevelopment(): bool
    {
        $env = env('APP_ENV', 'production');
        return $env === 'local' || $env === 'development';
    }

    /**
     * Reset cached state (useful for testing).
     */
    public static function reset(): void
    {
        self::$manifest = null;
        self::$devServerRunning = null;
    }
}

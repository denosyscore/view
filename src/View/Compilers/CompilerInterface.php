<?php

declare(strict_types=1);

namespace CFXP\Core\View\Compilers;

/**
 * Contract for template compilers.
 * 
 * Compilers transform template source code into executable PHP code.
 * Different compilers can handle different aspects of template compilation.
 */
interface CompilerInterface
{
    /**
     * Compile the given template content.
     * 
     * @param string $content The raw template content
     * @return string The compiled PHP code
     */
    public function compile(string $content): string;

    /**
     * Get the compiler's priority (higher = runs first).
     */
    public function getPriority(): int;
}

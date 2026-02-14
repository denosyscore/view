<?php

declare(strict_types=1);

namespace CFXP\Core\View\Compilers;

use CFXP\Core\View\Directives\DirectiveRegistry;

/**
 * Compiles @directives using the DirectiveRegistry.
 */
class DirectiveCompiler implements CompilerInterface
{
    public function __construct(
        private DirectiveRegistry $registry
    ) {
    }

    public function compile(string $content): string
    {
        return $this->registry->compile($content);
    }

    public function getPriority(): int
    {
        return 50; // Run after echo compiler
    }
}

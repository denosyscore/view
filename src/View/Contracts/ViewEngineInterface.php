<?php

declare(strict_types=1);

namespace CFXP\Core\View\Contracts;

interface ViewEngineInterface
{
    /**
     * Render a template with the given data
      * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = [], ?ViewEngineInterface $engine = null): string;

    /**
     * Check if a template exists
     */
    public function exists(string $template): bool;
}

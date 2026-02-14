<?php

declare(strict_types=1);

namespace CFXP\Core\View\Contracts;

/**
 * Interface for template directives.
 */
interface DirectiveInterface
{
    /**
     * Get the directive name
     */
    public function getName(): string;

    /**
     * Compile the directive
     */
    public function compile(string $expression): string;

    /**
     * Get the directive pattern (optional, for complex directives)
     */
    public function getPattern(): ?string;
}
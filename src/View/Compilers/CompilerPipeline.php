<?php

declare(strict_types=1);

namespace CFXP\Core\View\Compilers;

/**
 * Registry and pipeline for template compilers.
 * 
 * Compilers are executed in priority order (highest first) to transform
 * template source code into executable PHP code.
 * 
 * @example
 * $pipeline = new CompilerPipeline();
 * $pipeline->add(new EchoCompiler());
 * $pipeline->add(new DirectiveCompiler($directives));
 * $compiled = $pipeline->compile($template);
 */
class CompilerPipeline
{
    /** @var CompilerInterface[] */
    /** @var array<string, mixed> */

    private array $compilers = [];

    /** @var bool Whether compilers are sorted */
    private bool $sorted = false;

    /**
     * Add a compiler to the pipeline.
     */
    public function add(CompilerInterface $compiler): self
    {
        $this->compilers[] = $compiler;
        $this->sorted = false;
        return $this;
    }

    /**
     * Compile template through all registered compilers.
     */
    public function compile(string $content): string
    {
        $this->sortCompilers();

        foreach ($this->compilers as $compiler) {
            $content = $compiler->compile($content);
        }

        return $content;
    }

    /**
     * Get all registered compilers.
     * 
     * @return CompilerInterface[]
     */
    /**
     * @return array<string, mixed>
     */
public function getCompilers(): array
    {
        $this->sortCompilers();
        return $this->compilers;
    }

    /**
     * Sort compilers by priority (highest first).
     */
    private function sortCompilers(): void
    {
        if ($this->sorted) {
            return;
        }

        usort($this->compilers, function (CompilerInterface $a, CompilerInterface $b) {
            return $b->getPriority() <=> $a->getPriority();
        });

        $this->sorted = true;
    }
}

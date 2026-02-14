<?php

declare(strict_types=1);

namespace Denosys\View\Compilers;

/**
 * Compiles echo statements: {{ }}, {!! !!}, {{-- --}}.
 */
class EchoCompiler implements CompilerInterface
{
    public function compile(string $content): string
    {
        // Compile comments first: {{-- comment --}}
        $content = preg_replace('/\{\{--(.+?)--\}\}/s', '<?php /* $1 */ ?>', $content);

        // Compile escaped output: {{ $variable }}
        $content = preg_replace(
            '/\{\{\s*(.+?)\s*\}\}/',
            '<?= htmlspecialchars(($1) ?? \'\', ENT_QUOTES, \'UTF-8\') ?>',
            $content
        );

        // Compile unescaped output: {!! $variable !!}
        $content = preg_replace('/\{!!\s*(.+?)\s*!!\}/', '<?= $1 ?>', $content);

        return $content;
    }

    public function getPriority(): int
    {
        return 100; // Run early to handle output syntax
    }
}

<?php

declare(strict_types=1);

namespace CFXP\Core\View\Directives;

use CFXP\Core\View\Contracts\DirectiveInterface;
use InvalidArgumentException;

/**
 * Registry for template directives.
 */
class DirectiveRegistry
{
    /**
     * Registered directives
     */
    /** @var array<string, mixed> */

    private array $directives = [];

    /**
     * Directive patterns cache
     */
    /** @var array<string, mixed> */

    private array $patterns = [];

    public function __construct()
    {
        $this->registerDefaultDirectives();
    }

    /**
     * Register a directive
     */
    public function register(string $name, callable $handler): self
    {
        $this->directives[$name] = $handler;
        $this->clearPatternCache();
        return $this;
    }

    /**
     * Register a directive object
     */
    public function registerDirective(DirectiveInterface $directive): self
    {
        $this->directives[$directive->getName()] = $directive;
        $this->clearPatternCache();
        return $this;
    }

    /**
     * Check if directive exists
     */
    public function has(string $name): bool
    {
        return isset($this->directives[$name]);
    }

    /**
     * Get a directive
     */
    public function get(string $name): callable|DirectiveInterface
    {
        if (!$this->has($name)) {
            throw new InvalidArgumentException("Directive not found: {$name}");
        }

        return $this->directives[$name];
    }

    /**
     * Get all directives
     */
    /**
     * @return array<string, mixed>
     */
public function all(): array
    {
        return $this->directives;
    }

    /**
     * Compile template with directives
     */
    public function compile(string $template): string
    {
        $compiled = $template;

        // First, process Blade-style output syntax
        $compiled = $this->compileBladeSyntax($compiled);

        // Process each directive
        foreach ($this->directives as $name => $directive) {
            if ($directive instanceof DirectiveInterface) {
                $pattern = $directive->getPattern() ?: $this->getDefaultPattern($name);
                $compiled = preg_replace_callback($pattern, function ($matches) use ($directive) {
                    $expression = $matches[1] ?? '';
                    return $directive->compile($expression);
                }, $compiled);
            } else {
                $pattern = $this->getDefaultPattern($name);
                $compiled = preg_replace_callback($pattern, function ($matches) use ($directive) {
                    $expression = $matches[1] ?? '';
                    return $directive($expression);
                }, $compiled);
            }
        }

        return $compiled;
    }

    /**
     * Compile Blade-style output syntax
     */
    private function compileBladeSyntax(string $template): string
    {
        // First, compile comments: {{-- comment --}} (must be done before other {{ }} processing)
        $template = preg_replace('/\{\{--(.+?)--\}\}/s', '<?php /* $1 */ ?>', $template);

        // Compile escaped output: {{ $variable }} - coalesce null to empty string
        $template = preg_replace('/\{\{\s*(.+?)\s*\}\}/', '<?= htmlspecialchars(($1) ?? \'\', ENT_QUOTES, \'UTF-8\') ?>', $template);

        // Compile unescaped output: {!! $variable !!}
        $template = preg_replace('/\{!!\s*(.+?)\s*!!\}/', '<?= $1 ?>', $template);

        return $template;
    }

    /**
     * Get default pattern for directive
     */
    private function getDefaultPattern(string $name): string
    {
        if (!isset($this->patterns[$name])) {
            // Pattern matches both @directive() and @directive (with or without parentheses)
            // (?![a-zA-Z0-9_]) is a negative lookahead to prevent matching @error in @errors
            // Uses PCRE recursive pattern for balanced parentheses matching:
            // (?:[^()]*+|\((?:[^()]*+|(?1))*\))* - matches nested parentheses correctly
            $balancedParens = '(?:[^()]*+|\((?:[^()]*+|(?1))*\))*';
            $this->patterns[$name] = '/@' . $name . '(?![a-zA-Z0-9_])(?:\s*\((' . $balancedParens . ')\))?/s';
        }

        return $this->patterns[$name];
    }

    /**
     * Clear pattern cache
     */
    private function clearPatternCache(): void
    {
        $this->patterns = [];
    }

    /**
     * Register default directives
     */
    private function registerDefaultDirectives(): void
    {
        // @if directive
        $this->register('if', function (string $expression): string {
            return "<?php if ({$expression}): ?>";
        });

        // @else directive
        $this->register('else', function (): string {
            return "<?php else: ?>";
        });

        // @elseif directive
        $this->register('elseif', function (string $expression): string {
            return "<?php elseif ({$expression}): ?>";
        });

        // @endif directive
        $this->register('endif', function (): string {
            return "<?php endif; ?>";
        });

        // @unless directive (inverse of @if)
        $this->register('unless', function (string $expression): string {
            return "<?php if (!({$expression})): ?>";
        });

        // @endunless directive
        $this->register('endunless', function (): string {
            return "<?php endif; ?>";
        });

        // @switch directive
        $this->register('switch', function (string $expression): string {
            return "<?php switch({$expression}): ?>";
        });

        // @case directive
        $this->register('case', function (string $expression): string {
            return "<?php case {$expression}: ?>";
        });

        // @default directive
        $this->register('default', function (): string {
            return "<?php default: ?>";
        });

        // @break directive
        $this->register('break', function (string $expression = ''): string {
            if ($expression) {
                return "<?php if({$expression}) break; ?>";
            }
            return "<?php break; ?>";
        });

        // @endswitch directive
        $this->register('endswitch', function (): string {
            return "<?php endswitch; ?>";
        });

        // @foreach directive
        $this->register('foreach', function (string $expression): string {
            return "<?php foreach ({$expression}): ?>";
        });

        // @endforeach directive
        $this->register('endforeach', function (): string {
            return "<?php endforeach; ?>";
        });

        // @for directive
        $this->register('for', function (string $expression): string {
            return "<?php for ({$expression}): ?>";
        });

        // @endfor directive
        $this->register('endfor', function (): string {
            return "<?php endfor; ?>";
        });

        // @while directive
        $this->register('while', function (string $expression): string {
            return "<?php while ({$expression}): ?>";
        });

        // @endwhile directive
        $this->register('endwhile', function (): string {
            return "<?php endwhile; ?>";
        });

        // @php directive
        $this->register('php', function (string $expression): string {
            return $expression ? "<?php {$expression}; ?>" : "<?php";
        });

        // @endphp directive
        $this->register('endphp', function (): string {
            return "?>";
        });

        // @echo directive
        $this->register('echo', function (string $expression): string {
            return "<?= htmlspecialchars({$expression}, ENT_QUOTES, 'UTF-8') ?>";
        });

        // @raw directive (unescaped output)
        $this->register('raw', function (string $expression): string {
            return "<?= {$expression} ?>";
        });

        // @include directive (use partial to preserve section state)
        $this->register('include', function (string $expression): string {
            return "<?php echo \$this->renderPartial({$expression}); ?>";
        });

        // @extends directive (layout inheritance)
        $this->register('extends', function (string $expression): string {
            return "<?php \$this->extend({$expression}); ?>";
        });

        // @section directive (handles both inline and block sections)
        $this->register('section', function (string $expression): string {
            // Check if this is an inline section (has a comma)
            if (strpos($expression, ',') !== false) {
                // Inline section: @section('name', 'value')
                return "<?php \$this->setSection({$expression}); ?>";
            } else {
                // Block section: @section('name')
                return "<?php \$this->startSection({$expression}); ?>";
            }
        });

        // @endsection directive
        $this->register('endsection', function (): string {
            return "<?php \$this->endSection(); ?>";
        });

        // @yield directive
        $this->register('yield', function (string $expression): string {
            return "<?= \$this->yieldSection({$expression}) ?>";
        });

        // @csrf directive
        $this->register('csrf', function (): string {
            return '<input type="hidden" name="_token" value="<?= csrf_token() ?>">';
        });

        // @method directive
        $this->register('method', function (string $expression): string {
            return "<input type=\"hidden\" name=\"_method\" value=\"<?= {$expression} ?>\">";
        });

        // @isset directive
        $this->register('isset', function (string $expression): string {
            return "<?php if (isset({$expression})): ?>";
        });

        // @endisset directive
        $this->register('endisset', function (): string {
            return "<?php endif; ?>";
        });

        // @empty directive
        $this->register('empty', function (string $expression): string {
            return "<?php if (empty({$expression})): ?>";
        });

        // @endempty directive
        $this->register('endempty', function (): string {
            return "<?php endif; ?>";
        });

        // @auth directive
        $this->register('auth', function (string $expression = ''): string {
            $guard = $expression ? ", {$expression}" : '';
            return "<?php if (auth()->check({$guard})): ?>";
        });

        // @endauth directive
        $this->register('endauth', function (): string {
            return "<?php endif; ?>";
        });

        // @guest directive
        $this->register('guest', function (string $expression = ''): string {
            $guard = $expression ? ", {$expression}" : '';
            return "<?php if (auth()->guest({$guard})): ?>";
        });

        // @endguest directive
        $this->register('endguest', function (): string {
            return "<?php endif; ?>";
        });
        
        // @config directive
        $this->register('config', function (string $expression): string {
            return "<?= config({$expression}) ?>";
        });

        // @route directive (for generating URLs)
        $this->register('route', function (string $expression): string {
            return "<?= route({$expression}) ?>";
        });

        // @vite directive (for Vite assets with HMR)
        $this->register('vite', function (string $expression): string {
            return "<?= (new \\CFXP\\Core\\View\\Vite())({$expression}) ?>";
        });

        // Validation error directives
        $this->registerValidationDirectives();
    }

    /**
     * Register validation-related directives
     */
    private function registerValidationDirectives(): void
    {
        // @error directive - check if field has error (uses errors() helper)
        $this->register('error', function (string $expression): string {
            return "<?php if(errors()->has({$expression})): ?>";
        });

        // @enderror directive
        $this->register('enderror', function (): string {
            return "<?php endif; ?>";
        });

        // @errors directive - check if any errors exist
        $this->register('errors', function (): string {
            return "<?php if(errors()->any()): ?>";
        });

        // @enderrors directive
        $this->register('enderrors', function (): string {
            return "<?php endif; ?>";
        });

        // @old directive - get old input value
        $this->register('old', function (string $expression): string {
            // Parse expression to get field and default value
            if (strpos($expression, ',') !== false) {
                return "<?= htmlspecialchars(old({$expression}), ENT_QUOTES, 'UTF-8') ?>";
            }
            return "<?= htmlspecialchars(old({$expression}, ''), ENT_QUOTES, 'UTF-8') ?>";
        });

        // @checked directive - for checkboxes
        $this->register('checked', function (string $expression): string {
            return "<?= {$expression} ? 'checked' : '' ?>";
        });

        // @selected directive - for select options
        $this->register('selected', function (string $expression): string {
            return "<?= {$expression} ? 'selected' : '' ?>";
        });

        // @disabled directive
        $this->register('disabled', function (string $expression): string {
            return "<?= {$expression} ? 'disabled' : '' ?>";
        });

        // @readonly directive
        $this->register('readonly', function (string $expression): string {
            return "<?= {$expression} ? 'readonly' : '' ?>";
        });

        // @class directive for conditional classes
        $this->register('class', function (string $expression): string {
            return "<?= class_names({$expression}) ?>";
        });
    }
}

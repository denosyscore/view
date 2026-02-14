<?php

declare(strict_types=1);

namespace CFXP\Core\Form;

use Stringable;

/**
 * Fluent builder for textarea fields.
 * 
 * Usage:
 * ```php
 * <?= Form::textarea('message')
 *     ->label('Your Message')
 *     ->rows(5)
 *     ->value($oldMessage)
 *     ->required() ?>
 * ```
 */
final class TextareaField implements Stringable
{
    private ?string $label = null;
    private ?string $value = null;
    private ?string $error = null;
    private ?string $placeholder = null;
    private ?string $id = null;
    private string $class = '';
    private int $rows = 3;
    private ?int $cols = null;
    private bool $required = false;
    private bool $disabled = false;
    private bool $readonly = false;
    /** @var array<string, string> */
    private array $attributes = [];

    public function __construct(
        private readonly string $name
    ) {}

    public function label(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function value(?string $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function error(?string $error): self
    {
        $this->error = $error;
        return $this;
    }

    public function placeholder(string $placeholder): self
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function id(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function class(string $class): self
    {
        $this->class = $class;
        return $this;
    }

    public function rows(int $rows): self
    {
        $this->rows = $rows;
        return $this;
    }

    public function cols(int $cols): self
    {
        $this->cols = $cols;
        return $this;
    }

    public function required(bool $required = true): self
    {
        $this->required = $required;
        return $this;
    }

    public function disabled(bool $disabled = true): self
    {
        $this->disabled = $disabled;
        return $this;
    }

    public function readonly(bool $readonly = true): self
    {
        $this->readonly = $readonly;
        return $this;
    }

    public function attr(string $name, string $value): self
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    public function __toString(): string
    {
        $html = '';
        $id = $this->id ?? $this->name;
        
        // Label
        if ($this->label !== null) {
            $html .= sprintf(
                '<label for="%s">%s</label>',
                $this->e($id),
                $this->e($this->label)
            );
        }
        
        // Build class string
        $classes = $this->class;
        if ($this->error !== null) {
            $classes .= ($classes !== '' ? ' ' : '') . 'is-invalid';
        }
        
        // Textarea
        $html .= sprintf(
            '<textarea name="%s" id="%s" rows="%d"',
            $this->e($this->name),
            $this->e($id),
            $this->rows
        );
        
        if ($this->cols !== null) {
            $html .= sprintf(' cols="%d"', $this->cols);
        }
        
        if ($classes !== '') {
            $html .= sprintf(' class="%s"', $this->e($classes));
        }
        
        if ($this->placeholder !== null) {
            $html .= sprintf(' placeholder="%s"', $this->e($this->placeholder));
        }
        
        if ($this->required) {
            $html .= ' required';
        }
        
        if ($this->disabled) {
            $html .= ' disabled';
        }
        
        if ($this->readonly) {
            $html .= ' readonly';
        }
        
        foreach ($this->attributes as $name => $value) {
            $html .= sprintf(' %s="%s"', $this->e($name), $this->e($value));
        }
        
        $html .= '>';
        $html .= $this->e($this->value ?? '');
        $html .= '</textarea>';
        
        // Error message
        if ($this->error !== null) {
            $html .= sprintf(
                '<div class="invalid-feedback">%s</div>',
                $this->e($this->error)
            );
        }
        
        return $html;
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

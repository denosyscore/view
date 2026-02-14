<?php

declare(strict_types=1);

namespace CFXP\Core\Form;

use Stringable;

/**
 * Fluent builder for checkbox fields.
 * 
 * Usage:
 * ```php
 * <?= Form::checkbox('agree')
 *     ->label('I agree to the terms')
 *     ->checked($wasChecked)
 *     ->required() ?>
 * ```
 */
final class CheckboxField implements Stringable
{
    private ?string $label = null;
    private ?string $error = null;
    private ?string $id = null;
    private string $class = '';
    private string $value = '1';
    private bool $checked = false;
    private bool $required = false;
    private bool $disabled = false;
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

    /**
     * Set the value submitted when checked (default: "1").
     */
    public function value(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function checked(bool $checked = true): self
    {
        $this->checked = $checked;
        return $this;
    }

    public function error(?string $error): self
    {
        $this->error = $error;
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

    public function attr(string $name, string $value): self
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    public function __toString(): string
    {
        $html = '';
        $id = $this->id ?? $this->name;
        
        // Build class string
        $classes = $this->class;
        if ($this->error !== null) {
            $classes .= ($classes !== '' ? ' ' : '') . 'is-invalid';
        }
        
        // Input
        $html .= sprintf(
            '<input type="checkbox" name="%s" id="%s" value="%s"',
            $this->e($this->name),
            $this->e($id),
            $this->e($this->value)
        );
        
        if ($classes !== '') {
            $html .= sprintf(' class="%s"', $this->e($classes));
        }
        
        if ($this->checked) {
            $html .= ' checked';
        }
        
        if ($this->required) {
            $html .= ' required';
        }
        
        if ($this->disabled) {
            $html .= ' disabled';
        }
        
        foreach ($this->attributes as $name => $value) {
            $html .= sprintf(' %s="%s"', $this->e($name), $this->e($value));
        }
        
        $html .= '>';
        
        // Label (inline for checkbox)
        if ($this->label !== null) {
            $html .= sprintf(
                '<label for="%s">%s</label>',
                $this->e($id),
                $this->e($this->label)
            );
        }
        
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

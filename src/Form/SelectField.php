<?php

declare(strict_types=1);

namespace Denosys\View\Form;

use Stringable;

/**
 * Fluent builder for select dropdowns.
 * 
 * Usage:
 * ```php
 * <?= Form::select('country')
 *     ->label('Country')
 *     ->options(['us' => 'United States', 'uk' => 'United Kingdom'])
 *     ->selected($currentCountry)
 *     ->required() ?>
 * ```
 */
final class SelectField implements Stringable
{
    private ?string $label = null;
    private ?string $selected = null;
    private ?string $error = null;
    private ?string $id = null;
    private ?string $placeholder = null;
    private string $class = '';
    private bool $required = false;
    private bool $disabled = false;
    /** @var array<string, string> */
    private array $options = [];
    /** @var array<string, string> */
    private array $attributes = [];

    public function __construct(
        private readonly string $name
    ) {}

    /**
     * Set the options for the select.
     * 
     * @param array<string, string> $options Key-value pairs (value => label)
     */
    public function options(array $options): self
    {
        $this->options = $options;
        return $this;
    }

    public function label(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function selected(?string $value): self
    {
        $this->selected = $value;
        return $this;
    }

    public function error(?string $error): self
    {
        $this->error = $error;
        return $this;
    }

    /**
     * Add a placeholder option (empty value at top).
     */
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
        
        // Select opening tag
        $html .= sprintf(
            '<select name="%s" id="%s"',
            $this->e($this->name),
            $this->e($id)
        );
        
        if ($classes !== '') {
            $html .= sprintf(' class="%s"', $this->e($classes));
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
        
        // Placeholder option
        if ($this->placeholder !== null) {
            $html .= sprintf(
                '<option value="">%s</option>',
                $this->e($this->placeholder)
            );
        }
        
        // Options
        foreach ($this->options as $value => $label) {
            $isSelected = $this->selected !== null && (string) $value === $this->selected;
            $html .= sprintf(
                '<option value="%s"%s>%s</option>',
                $this->e((string) $value),
                $isSelected ? ' selected' : '',
                $this->e($label)
            );
        }
        
        $html .= '</select>';
        
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

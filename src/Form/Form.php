<?php

declare(strict_types=1);

namespace CFXP\Core\Form;

/**
 * Static factory for creating form fields.
 * 
 * Usage in templates:
 * ```php
 * <?= Form::text('email')->label('Email')->required() ?>
 * <?= Form::password('password')->label('Password')->required() ?>
 * <?= Form::submit('Login') ?>
 * ```
 */
final class Form
{
    /**
     * Create a text input field.
     */
    public static function text(string $name): Field
    {
        return new Field('text', $name);
    }

    /**
     * Create an email input field.
     */
    public static function email(string $name): Field
    {
        return new Field('email', $name);
    }

    /**
     * Create a password input field.
     */
    public static function password(string $name): Field
    {
        return new Field('password', $name);
    }

    /**
     * Create a number input field.
     */
    public static function number(string $name): Field
    {
        return new Field('number', $name);
    }

    /**
     * Create a tel input field.
     */
    public static function tel(string $name): Field
    {
        return new Field('tel', $name);
    }

    /**
     * Create a URL input field.
     */
    public static function url(string $name): Field
    {
        return new Field('url', $name);
    }

    /**
     * Create a date input field.
     */
    public static function date(string $name): Field
    {
        return new Field('date', $name);
    }

    /**
     * Create a textarea field.
     */
    public static function textarea(string $name): TextareaField
    {
        return new TextareaField($name);
    }

    /**
     * Create a select dropdown.
     */
    public static function select(string $name): SelectField
    {
        return new SelectField($name);
    }

    /**
     * Create a checkbox field.
     */
    public static function checkbox(string $name): CheckboxField
    {
        return new CheckboxField($name);
    }

    /**
     * Create a hidden input.
     */
    public static function hidden(string $name, string $value): string
    {
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Create a submit button.
     */
    public static function submit(string $text, string $class = ''): string
    {
        $classAttr = $class !== '' ? sprintf(' class="%s"', htmlspecialchars($class, ENT_QUOTES, 'UTF-8')) : '';
        return sprintf(
            '<button type="submit"%s>%s</button>',
            $classAttr,
            htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Create a CSRF token hidden field.
     * 
     * @param string $token The CSRF token from the session
     */
    public static function csrf(string $token): string
    {
        return self::hidden('_token', $token);
    }

    /**
     * Create method spoofing field for PUT/PATCH/DELETE.
     */
    public static function method(string $method): string
    {
        return self::hidden('_method', strtoupper($method));
    }
}

<?php

declare(strict_types=1);

namespace Denosys\View\ValueObjects;

use InvalidArgumentException;
use Stringable;

/**
 * Immutable email value object.
 * 
 * Validates and normalizes email addresses on construction.
 */
final class Email implements Stringable
{
    private function __construct(
        public readonly string $value
    ) {}

    /**
     * Create an Email from a string.
     * 
     * @throws InvalidArgumentException If email is invalid
     */
    public static function from(string $email): self
    {
        $normalized = strtolower(trim($email));
        
        if ($normalized === '') {
            throw new InvalidArgumentException('Email cannot be empty');
        }
        
        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email format: {$email}");
        }
        
        return new self($normalized);
    }

    /**
     * Check if two emails are equal.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

<?php

declare(strict_types=1);

namespace Denosys\View\ValueObjects;

use InvalidArgumentException;

/**
 * Immutable hashed password value object.
 * 
 * Never stores plaintext passwords - only hashes.
 */
final class HashedPassword
{
    private function __construct(
        public readonly string $hash
    ) {}

    /**
     * Create a HashedPassword from plaintext.
     * 
     * @throws InvalidArgumentException If password is too short
     */
    public static function fromPlainText(string $password, int $minLength = 8): self
    {
        if (strlen($password) < $minLength) {
            throw new InvalidArgumentException(
                "Password must be at least {$minLength} characters"
            );
        }
        
        return new self(password_hash($password, PASSWORD_DEFAULT));
    }

    /**
     * Create a HashedPassword from an existing hash (e.g., from database).
     */
    public static function fromHash(string $hash): self
    {
        return new self($hash);
    }

    /**
     * Verify a plaintext password against this hash.
     */
    public function verify(string $plainText): bool
    {
        return password_verify($plainText, $this->hash);
    }

    /**
     * Check if the hash needs to be rehashed (algorithm upgrade).
     */
    public function needsRehash(): bool
    {
        return password_needs_rehash($this->hash, PASSWORD_DEFAULT);
    }
}

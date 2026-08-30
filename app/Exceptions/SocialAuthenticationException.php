<?php

namespace App\Exceptions;

use RuntimeException;

class SocialAuthenticationException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $replacements
     */
    public function __construct(
        public readonly string $translationKey,
        public readonly array $replacements = [],
    ) {
        parent::__construct(trans($translationKey, $replacements));
    }
}

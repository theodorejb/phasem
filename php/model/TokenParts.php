<?php

declare(strict_types=1);

namespace Phasem\model;

class TokenParts
{
    public function __construct(
        public string $selector,
        public string $verifier,
        public string $verifierHash,
    ) {}
}

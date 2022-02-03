<?php

declare(strict_types=1);

namespace PhpPg\PgServiceFile;

class Service
{
    /**
     * @param string $name
     * @param array<string, string> $settings
     */
    public function __construct(
        public string $name,
        public array $settings,
    ) {
    }
}

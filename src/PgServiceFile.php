<?php

declare(strict_types=1);

namespace PhpPg\PgServiceFile;

use Amp\ByteStream\ReadableResourceStream;
use InvalidArgumentException;

use function Amp\ByteStream\splitLines;
use function count;
use function error_clear_last;
use function error_get_last;
use function explode;
use function str_ends_with;
use function str_starts_with;
use function substr;
use function trim;

/**
 * @see https://www.postgresql.org/docs/current/libpq-pgservice.html
 */
final class PgServiceFile
{
    /**
     * @param array<string, Service> $services
     */
    public function __construct(
        private array $services = [],
    ) {
    }

    /**
     * @return array<Service>
     */
    public function getServices(): array
    {
        return $this->services;
    }

    /**
     * @param string $path
     * @return PgServiceFile
     *
     * @throws InvalidArgumentException
     */
    public static function open(string $path): PgServiceFile
    {
        $fp = @fopen($path, 'rb');
        if (false === $fp) {
            $lastErr = error_get_last()['message'] ?? 'Unknown error';
            error_clear_last();

            throw new InvalidArgumentException("Unable to open .pg_service file at path {$path}: {$lastErr}");
        }

        $stream = new ReadableResourceStream($fp);
        $services = [];
        $service = null;

        $lineNum = 0;
        foreach (splitLines($stream) as $line) {
            $lineNum++;
            $line = trim($line);

            // Skip comments
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            if (str_starts_with($line, '[') && str_ends_with($line, ']')) {
                $name = substr($line, 1, -1);
                $service = new Service($name, []);
                $services[$service->name] = $service;
            } else {
                $parts = explode('=', $line, 2);
                if (count($parts) !== 2) {
                    throw new InvalidArgumentException("Cannot parse .pg_service file {$path} at line {$lineNum}");
                }

                // No service section found
                if ($service === null) {
                    continue;
                }

                $parts[0] = trim($parts[0]);
                $parts[1] = trim($parts[1]);

                $service->settings[$parts[0]] = $parts[1];
            }
        }

        return new PgServiceFile($services);
    }
}

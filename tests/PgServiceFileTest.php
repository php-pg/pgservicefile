<?php

namespace PhpPg\PgServiceFile\Tests;

use PhpPg\PgServiceFile\PgServiceFile;
use PHPUnit\Framework\TestCase;

class PgServiceFileTest extends TestCase
{
    public function testOpen(): void
    {
        $data = <<<EOT
# A comment
[abc]
host=abc.example.com
port=9999
dbname=abcdb
user=abcuser
# Another comment

[def]
host = def.example.com
dbname = defdb
user = defuser
application_name = has space
EOT;

        \file_put_contents('test.pg_service.conf', $data);

        $pgServiceFile = PgServiceFile::open('test.pg_service.conf');
        $services = $pgServiceFile->getServices();

        self::assertCount(2, $services);
        self::assertSame('abc', $services['abc']->name);
        self::assertSame('def', $services['def']->name);

        self::assertCount(4, $services['abc']->settings);
        self::assertSame('abc.example.com', $services['abc']->settings['host']);
        self::assertSame('9999', $services['abc']->settings['port']);
        self::assertSame('abcdb', $services['abc']->settings['dbname']);
        self::assertSame('abcuser', $services['abc']->settings['user']);

        self::assertCount(4, $services['def']->settings);
        self::assertSame('def.example.com', $services['def']->settings['host']);
        self::assertSame('defdb', $services['def']->settings['dbname']);
        self::assertSame('defuser', $services['def']->settings['user']);
        self::assertSame('has space', $services['def']->settings['application_name']);
    }

    public function testOpenFails(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to open .pg_service file');

        PgServiceFile::open('unknownfile.txt');
    }

    public function testOpenInvalidSyntax(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot parse .pg_service file');

        \file_put_contents('test.pg_service.conf', 'Bad syntax');

        PgServiceFile::open('test.pg_service.conf');
    }
}

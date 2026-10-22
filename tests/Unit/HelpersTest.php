<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function test_format_bytes_is_human_readable(): void
    {
        $this->assertSame('0 KB', format_bytes(0));
        $this->assertSame('512 B', format_bytes(512));
        $this->assertSame('1.0 KB', format_bytes(1024));
        $this->assertSame('2.4 MB', format_bytes(2516582));
        $this->assertSame('30 MB', format_bytes(30 * 1024 * 1024));
    }
}

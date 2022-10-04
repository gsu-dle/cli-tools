<?php

declare(strict_types=1);

namespace GAState\Tools\CLI\Tests;

use GAState\Tools\CLI\StringUtils;

final class StringUtilsTest extends \PHPUnit\Framework\TestCase
{
    public function testGetElapsedTime(): void
    {
        $startTime = microtime(true);
        usleep(19501);
        $elapsed = StringUtils::getElapsedTime($startTime);

        self::assertContains($elapsed, ['0.020 sec', '0.021 sec']);
    }

    
    public function testFormatElapsedTime(): void
    {
        $elapsed = StringUtils::formatElapsedTime(3723.456);
        self::assertEquals('1 hr, 2 min, 3.456 sec', $elapsed);
    }
}
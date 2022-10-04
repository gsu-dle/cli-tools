<?php

declare(strict_types=1);

namespace GAState\Tools\CLI\Tests;

use GAState\Tools\CLI\CLICommand;

class MockCommand implements CLICommand
{
    /**
     * @param array<int, string> $args
     * @param array<string, string> $opts
     * @param array<string, string|null> $env
     * 
     * @return void
     */
    public function run(
        array $args = [],
        array $opts = [],
        array $env = []
    ): void {
        echo json_encode([
            'args' => $args, 
            'opts' => $opts, 
            'env' => $env
        ], JSON_PRETTY_PRINT) . "\n";
    }
}
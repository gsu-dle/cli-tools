<?php

declare(strict_types=1);

namespace GAState\Tools\CLI;

interface CLICommand
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
    ): void;
}

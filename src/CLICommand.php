<?php

declare(strict_types=1);

namespace GAState\Tools\CLI;

abstract class CLICommand
{
    /**
     * @param array<string> $args
     * @param array<string> $opts
     * 
     * @return void
     */
    public abstract function run(array $args = [], array $opts = []): void;
}
<?php

declare(strict_types=1);

namespace GAState\Tools\CLI;

abstract class CLICommand
{
    /**
     * @param array<string> $args
     * 
     * @return void
     */
    public abstract function run(array $args = []): void;
}
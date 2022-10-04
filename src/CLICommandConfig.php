<?php

declare(strict_types=1);

namespace GAState\Tools\CLI;

class CLICommandConfig
{
    /**
     * @param string $Name
     * @param string $Description
     * @param string $ClassName
     * @param array<CLIArgument> $Arguments
     * @param array<CLIOption> $Options
     */
    public function __construct(
        public string $Name,
        public string $Description,
        public string $ClassName,
        public array $Arguments = [],
        public array $Options = []
    ) {
    }
}

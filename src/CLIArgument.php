<?php

declare(strict_types=1);

namespace GAState\Tools\CLI;

class CLIArgument
{
    /**
     * @param string $Name
     * @param string $Description
     * @param bool $Required
     */
    public function __construct(
        public string $Name,
        public string $Description,
        public bool $Required
    ) {
    }
}

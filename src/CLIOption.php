<?php

declare(strict_types=1);

namespace GAState\Tools\CLI;

class CLIOption
{
    /**
     * @param string $Name
     * @param string $Description
     * @param string|null $ShortName
     * @param bool $ArgRequired
     */
    public function __construct(
        public string $Name,
        public string $Description,
        public string|null $ShortName,
        public bool $ArgRequired
    ) {
    }
}

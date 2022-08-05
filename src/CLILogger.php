<?php

declare(strict_types=1);

namespace GAState\App\BannerImport;

use DateTimeZone;
use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\StreamHandler;

class CLILogger extends Logger
{
    /**
     * @param string $name
     * @param string $cmd
     * @param string $logLevel
     * @param array<HandlerInterface> $handlers
     * @param array<callable> $processors
     * @param DateTimeZone|null $timezone
     */
    public function __construct(
        string $name,
        string $cmd,
        string $logLevel = 'info',
        array $handlers = [],
        array $processors = [],
        ?DateTimeZone $timezone = null
    ) {
        parent::__construct(
            name: $name,
            handlers: $handlers,
            processors: $processors,
            timezone: $timezone
        );

        $logLevelMap = [
            'debug' => Logger::DEBUG,
            'info' => Logger::INFO,
            'notice' => Logger::NOTICE,
            'success' => Logger::INFO,
            'warning' => Logger::WARNING,
            'error' => Logger::ERROR,
            'critical' => Logger::CRITICAL,
            'alert' => Logger::ALERT,
            'emergency' => Logger::EMERGENCY
        ];

        $handler = new StreamHandler('php://stdout', $logLevelMap[$logLevel] ?? Logger::INFO);
        $handler->setFormatter(new LineFormatter(
            format: "[%datetime%][%channel%][{$cmd}][%level_name%]: %message% - %context%\n"
        ));

        $this->pushHandler($handler);
    }
}

<?php

declare(strict_types=1);

namespace GAState\Tools\CLI;

use splitbrain\phpcli\PSR3CLI;
use Stringable;
use Exception;

/**
 * @codeCoverageIgnore Not considered for code coverage since this is just a facade for the CLI class from 
 * splitbrain\phpcli
 */
abstract class CLIBaseContainer extends PSR3CLI {
    /**
     * @return void
     */
    public final function run(): void
    {
        parent::run();
    }


    /**
     * @return void
     */
    protected final function registerDefaultOptions(): void
    {
        parent::registerDefaultOptions();
    }


    /**
     * @return void
     */
    protected final function handleDefaultOptions(): void
    {
        if ($this->options->getOpt('no-colors') === true) {
            $this->colors->disable();
        }
    }


    /**
     * @return void
     */
    protected final function setupLogging(): void
    {
        parent::setupLogging();
    }


    /**
     * @return void
     */
    protected final function parseOptions(): void
    {
        parent::parseOptions();
    }


    /**
     * @return void
     */
    protected final function checkArguments(): void
    {
        parent::checkArguments();
    }


    /**
     * @return void
     */
    protected final function execute(): void
    {
        parent::execute();
    }


    /**
     * @param Exception|string $error either an exception or an error message
     * @param array<mixed> $context
     *
     * @return void
     */
    public final function fatal($error, array $context = array()): void
    {
        parent::fatal($error, $context);
    }


    /**
     * @param string $string
     * @param array<mixed> $context
     *
     * @return void
     */
    public final function success($string, array $context = array()): void
    {
        parent::success($string, $context);
    }


    /**
     * @param string $level
     * @param string $message
     * @param array<mixed> $context
     *
     * @return void
     */
    protected final function logMessage($level, $message, array $context = array()): void
    {
        parent::logMessage($level, $message, $context);
    }


    /**
     * @param mixed $message
     * @param array<mixed> $context
     
     * @return string
     */
    protected final function interpolate($message, array $context = array()): string
    {
        return parent::interpolate($message, $context);
    }
    
    
    /**
     * @param string|Stringable $message
     * @param array<mixed> $context
     *
     * @return void
     */
    public function emergency($message, array $context = array()): void
    {
        parent::emergency(strval($message), $context);
    }


    /**
     * @param string|Stringable $message
     * @param array<mixed> $context
     *
     * @return void
     */
    public function alert($message, array $context = array())
    {
        parent::alert(strval($message), $context);
    }


    /**
     * @param string|Stringable $message
     * @param array<mixed> $context
     *
     * @return void
     */
    public function critical($message, array $context = array())
    {
        parent::critical(strval($message), $context);
    }


    /**
     * @param string|Stringable $message
     * @param array<mixed> $context
     *
     * @return void
     */
    public function error($message, array $context = array())
    {
        parent::error(strval($message), $context);
    }


    /**
     * @param string|Stringable $message
     * @param array<mixed> $context
     */
    public function warning($message, array $context = array())
    {
        parent::warning(strval($message), $context);
    }


    /**
     * @param string|Stringable $message
     * @param array<mixed> $context
     */
    public function notice($message, array $context = array())
    {
        parent::notice(strval($message), $context);
    }


    /**
     * @param string|Stringable $message
     * @param array<mixed> $context
     */
    public function info($message, array $context = array())
    {
        parent::info(strval($message), $context);
    }


    /**
     * @param string|Stringable $message
     * @param array<mixed> $context
     */
    public function debug($message, array $context = array())
    {
        parent::debug(strval($message), $context);
    }


    /**
     * @param mixed $level
     * @param string|Stringable $message
     * @param array<mixed> $context
     */
    public function log($level, $message, array $context = array())
    {
        parent::log(strval($level), strval($message), $context);
    }
}
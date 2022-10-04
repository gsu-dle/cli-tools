<?php

declare(strict_types=1);

namespace GAState\Tools\CLI;

use Auryn\Injector;
use Dotenv\Dotenv;
use Exception;
use splitbrain\phpcli\Options;

class CLIContainer extends CLIBaseContainer
{
    public const ENV_BASE_DIR = 'BASE_DIR';
    public const ENV_REQUIRED_ENVS = 'REQUIRED_ENVS';
    public const ENV_CMD_OPTS_FILE = 'CMD_OPTS_FILE';
    public const OPT_GLOBAL_CMD = '__global__';
    public const OPT_LOG_LEVEL = 'logLevel';


    /**
     * @var string|false $envConfigPath
     */
    protected string|false $envConfigPath = false;


    /**
     * @var array<string, string|null> $env
     */
    protected array $env = [];


    /**
     * @var string $cmdOptsConfigFile
     */
    protected string|false $cmdOptsConfigFile = false;


    /**
     * @var array<string, CLICommandConfig> $cmdOptsConfig
     */
    protected array $cmdOptsConfig = [];


    /**
     * @var Injector $injector
     */
    protected Injector $injector;


    /**
     * @param string|false $envConfigPath
     * @param bool $autocatch
     */
    public final function __construct(
        string|false $envConfigPath = false,
        string|false $cmdOptsConfigFile = false,
        bool $autocatch = false
    ) {
        $this->envConfigPath = $envConfigPath;
        $this->cmdOptsConfigFile = $cmdOptsConfigFile;
        $this->injector = new Injector();

        parent::__construct(autocatch: $autocatch);
    }


    /**
     * @param Options $options
     *
     * @return void
     */
    protected final function setup(Options $options): void
    {
        $this->env = $this->getEnvVars($options);
        $this->checkEnv($options);

        $this->cmdOptsConfig = $this->getCmdOpts($options);
        $this->loadCmdOpts($options);
    }


    /**
     * @param Options $options
     *
     * @return void
     */
    protected final function main(Options $options): void
    {
        if ($options->getOpt('help', false) === true) {
            echo $options->help();
            return;
        }

        $cmdName = $this->getCmdName($options);
        if ($cmdName === '') {
            throw new Exception('Invalid command name');
        }

        $cmdClass = $this->getCmdClass($cmdName, $options);
        if (!is_string($cmdClass) || !class_exists($cmdClass)) {
            throw new Exception('Invalid command class name');
        }

        $args = $this->getArgs($cmdName, $options);
        $opts = $this->getOpts($cmdName, $options);

        $this->boot($cmdName, $args, $opts, $options);

        $cmd = $this->injector->make($cmdClass);
        if (!$cmd instanceof CLICommand) {
            throw new Exception('Invalid command class type');
        }

        $cmd->run($args, $opts, $this->env);
    }


    /**
     * @param Options $options
     * 
     * @return array<string, string|null>
     */
    protected function getEnvVars(Options $options): array
    {
        $env = [];

        if ($this->envConfigPath !== false) {
            $_envConfigPath = realpath($this->envConfigPath);
            if ($_envConfigPath === false) {
                throw new Exception("Invalid environment config directory");
            }
            $envConfigPath = $_envConfigPath;

            $envConfigFile = realpath($envConfigPath . '/.env');
            if ($envConfigFile === false) {
                throw new Exception("Missing environment config file");
            }

            Dotenv::createImmutable($envConfigPath)->load();
            $env = $_ENV;

            if (!isset($env[static::ENV_BASE_DIR])) {
                $env[static::ENV_BASE_DIR] = $envConfigPath;
            }
        } else {
            foreach ($_ENV as $name => $value) {
                $env[strval($name)] = strval($value);
            }
        }

        /** @var array<string, string|null> */
        return $env;
    }


    /**
     * @param Options $options
     * 
     * @return void
     */
    protected function checkEnv(Options $options): void
    {
        $requiredEnv = $this->getRequiredEnvVars($options);
        foreach ($requiredEnv as $env) {
            if (!isset($this->env[$env])) {
                throw new Exception("Missing required environment variable: '{$env}'");
            }
        }
    }


    /**
     * @param Options $options
     * 
     * @return array<string>
     */
    protected function getRequiredEnvVars(Options $options): array
    {
        return isset($this->env[static::ENV_REQUIRED_ENVS]) ? explode(',', strval($this->env[static::ENV_REQUIRED_ENVS])) : [];
    }


    /**
     * @param Options $options
     * 
     * @return array<string, CLICommandConfig>
     */
    protected function getCmdOpts(Options $options): array
    {
        $cmdOptConfigFile = $this->getCmdOptsConfigFile($options);

        if (!is_string($cmdOptConfigFile) || !file_exists($cmdOptConfigFile)) {
            throw new Exception("Missing command options config file");
        }

        $cmdOptConfig = require $cmdOptConfigFile;
        if (!is_array($cmdOptConfig)) {
            throw new Exception("Invalid command options config file");
        }

        foreach ($cmdOptConfig as $cmdName => $cmd) {
            if (!is_string($cmdName) || !$cmd instanceof CLICommandConfig) {
                throw new Exception("Invalid command options config file");
            }
        }

        /** @var array<string, CLICommandConfig> */
        return $cmdOptConfig;
    }


    /**
     * @param Options $options
     * 
     * @return string|false
     */
    protected function getCmdOptsConfigFile(Options $options): string|false
    {
        return $this->env[static::ENV_CMD_OPTS_FILE] ?? $this->cmdOptsConfigFile;
    }


    /**
     * @param Options $options
     * 
     * @return void
     */
    protected function loadCmdOpts(Options $options): void
    {
        foreach ($this->cmdOptsConfig as $commandName => $command) {
            if ($commandName === static::OPT_GLOBAL_CMD) {
                $commandName = '';
            } else {
                $options->registerCommand(
                    $command->Name,
                    $command->Description
                );

                foreach ($command->Arguments as $cmdArgument) {
                    $options->registerArgument(
                        $cmdArgument->Name,
                        $cmdArgument->Description,
                        $cmdArgument->Required,
                        $commandName
                    );
                }
            }

            foreach ($command->Options as $cmdOption) {
                $options->registerOption(
                    $cmdOption->Name,
                    $cmdOption->Description,
                    $cmdOption->ShortName,
                    $cmdOption->ArgRequired,
                    $commandName
                );
            }
        }
    }


    /**
     * @param Options $options
     * 
     * @return string
     */
    protected function getCmdName(Options $options): string
    {
        return $options->getCmd();
    }


    /**
     * @param string $cmdName
     * @param Options $options
     * 
     * @return string|false
     */
    protected function getCmdClass(
        string $cmdName,
        Options $options
    ): string|false {
        $cmdClass = ($this->cmdOptsConfig[$cmdName] ?? null)?->ClassName ?? false;
        return is_string($cmdClass) && class_exists($cmdClass) ? $cmdClass : false;
    }


    /**
     * @param string $cmdName
     * @param Options $options
     * 
     * @return array<int,string>
     */
    protected function getArgs(
        string $cmdName,
        Options $options
    ): array {
        /** @var array<int,string> */
        return $options->getArgs();
    }


    /**
     * @param string $cmdName
     * @param Options $options
     * 
     * @return array<string,string>
     */
    protected function getOpts(
        string $cmdName,
        Options $options
    ): array {
        /** @var array<string,string> $opts */
        $opts = [];

        $_opts = $options->getOpt();
        if (is_array($_opts)) {
            foreach ($_opts as $name => $value) {
                $opts[strval($name)] = $value;
            }
        }

        return $opts;
    }


    /**
     * @param string $cmdName
     * @param array<int,string> $args
     * @param array<string,string> $opts
     * @param Options $options
     * 
     * @return void
     */
    protected function boot(
        string $cmdName,
        array $args,
        array $opts,
        Options $options
    ): void {
        // App logger
        $this->injector
            ->define("GAState\Tools\CLI\CLILogger", [
                ':name' => $options->getBin(),
                ':cmd' => $cmdName,
                ':logLevel' => $opts[static::OPT_LOG_LEVEL] ?? 'info',
                ':timezone' => null
            ])
            ->alias('Psr\Log\LoggerInterface', 'GAState\Tools\CLI\CLILogger');
    }
}

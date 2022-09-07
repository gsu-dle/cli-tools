<?php

declare(strict_types=1);

namespace GAState\Tools\CLI;

use Auryn\Injector;
use Dotenv\Dotenv;
use Exception;
use splitbrain\phpcli\PSR3CLI;
use splitbrain\phpcli\Options;

abstract class CLIContainer extends PSR3CLI
{
    /**
     * @var array<string, array<string, string|array<array<string,string>>>> $cmdOptsConfig
     */
    protected array $cmdOptsConfig = [];


    /**
     * @var Injector $injector
     */
    protected Injector $injector;


    /**
     * @param string|false $envConfigPath
     * @param string|null $envConfigName
     */
    public final function __construct(
        string|false $envConfigPath = __DIR__ . '/../',
        string|null $envConfigName = null
    ) {
        if ($envConfigPath !== false) {
            $_envConfigPath = $envConfigPath;
            $envConfigPath = explode(';', $envConfigPath);
            foreach ($envConfigPath as $idx => $path) {
                $path = realpath($path);
                if ($path === false) {
                    unset($envConfigPath[$idx]);
                }
            }
            if (count($envConfigPath) < 1) {
                throw new Exception("Invalid env config directory: {$_envConfigPath}");
            }
            if (is_string($envConfigName)) {
                $envConfigName = explode(';', $envConfigName);
            }
            Dotenv::createImmutable($envConfigPath, $envConfigName)->load();
        }

        $this->injector = new Injector();
        $this->injector->share($this->injector);

        $val = isset($_ENV['AUTOCATCH']) ? strtolower(strval($_ENV['AUTOCATCH'])) : '';
        $validVals = ['true', 'On', 'Yes', '1'];
        parent::__construct(autocatch: in_array($val, $validVals, true));
    }


    /**
     * @param Options $options
     *
     * @return void
     */
    protected final function setup(Options $options): void
    {
        $this->checkEnv($options);

        $this->cmdOptsConfig = $this->getCmdOpts($options);
        $this->loadCmdOpts($options);
    }


    /**
     * @param Options $options
     * 
     * @return void
     */
    protected function checkEnv(Options $options): void
    {
        $requiredEnvs = explode(',', isset($_ENV['REQUIRED_ENVS']) ? strtolower(strval($_ENV['REQUIRED_ENVS'])) : '');
        foreach ($requiredEnvs as $env) {
            $env = strtoupper(trim($env));
            if (!isset($_ENV[$env])) {
                throw new Exception("Missing required env var: {$env}");
            }
        }
    }


    /**
     * @param Options $options
     * 
     * @return array<string, array<string, string|array<array<string,string>>>>
     */
    protected function getCmdOpts(Options $options): array
    {
        $_cmdOptConfig = $cmdOptConfig = isset($_ENV['CMD_OPT_FILE']) ? realpath($_ENV['CMD_OPT_FILE']) : false;
        if (is_string($cmdOptConfig)) {
            $cmdOptConfig = include $_ENV['CMD_OPT_FILE'];
            if (!is_array($cmdOptConfig)) {
                throw new Exception("Invalid CMD_OPT_FILE: {$_cmdOptConfig}");
            }
        } else {
            $cmdOptConfig = [];
        }

        return $cmdOptConfig;
    }


    /**
     * @param Options $options
     * 
     * @return void
     */
    protected function loadCmdOpts(Options $options): void
    {
        foreach ($this->cmdOptsConfig  as $commandName => $command) {
            if ($commandName === '__global__') {
                $commandName = '';
            } else {
                $options->registerCommand(
                    command: $commandName,
                    help: is_string($command['description']) ? $command['description'] : ''
                );

                if (is_array($command['arguments'])) {
                    foreach ($command['arguments'] as $argument) {
                        $options->registerArgument(
                            arg: $argument['name'],
                            help: $argument['description'],
                            required: ($argument['required'] ?? 'true') === 'true',
                            command: $commandName
                        );
                    }
                }
            }

            if (is_array($command['options'])) {
                foreach ($command['options'] as $option) {
                    $options->registerOption(
                        long: $option['name'],
                        help: $option['description'],
                        short: $option['shortName'] ?? null,
                        needsarg: ($option['required'] ?? 'true') === 'true',
                        command: $commandName
                    );
                }
            }
        }
    }


    /**
     * @param Options $options
     *
     * @return void
     */
    protected final function main(Options $options): void
    {
        $cmdName = $options->getCmd();
        $args = $options->getArgs();

        if ($cmdName === '') {
            echo $options->help();
            return;
        }

        $this->boot($options);

        $cmdClass = $this->cmdOptsConfig[$cmdName]['class'] ?? '';
        if (!is_string($cmdClass) || !class_exists($cmdClass)) {
            throw new Exception(); // TODO:: add error message
        }
        $cmd = $this->injector->make($cmdClass);
        if (!$cmd instanceof CLICommand) {
            throw new Exception(); // TODO: add error message
        }

        $opts = [];
        $_opts = $options->getOpt();
        if (is_array($_opts)) {
            foreach($_opts as $name => $value) {
                $opts[strval($name)] = strval($value);
            }
        }

        $cmd->run(args: $args, opts: $opts);
    }


    /**
     * @param Options $options
     * 
     * @return void
     */
    protected abstract function boot(Options $options): void;
}
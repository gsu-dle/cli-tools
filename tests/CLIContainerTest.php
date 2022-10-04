<?php

declare(strict_types=1);

namespace GAState\Tools\CLI\Tests;

use Exception;
use GAState\Tools\CLI\CLIContainer;

final class CLIContainerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var string $envConfigPath
     */
    private string $envConfigPath;

    
    /**
     * @var string $envConfigFile
     */
    private string $envConfigFile;
    
    
    /**
     * @var string $cmdOptsConfigFile
     */
    private string $cmdOptsConfigFile;
    

    /**
     * @var array<string>
     */
    private array $argv;


    public function setUp(): void
    {
        global $argv;

        $this->envConfigPath = __DIR__;
        $this->envConfigFile = __DIR__ . '/.env';
        $this->cmdOptsConfigFile = __DIR__ . '/CmdOptsConfigFile.php';

        // Zero out any command-line arguments
        $argv = ['CLIContainerTest'];
        $this->argv = &$argv;

        // Just to get phpstan to shutup about $this->argv being write-only
        foreach($this->argv as $idx => $arg) {
            $this->argv[$idx] = trim($arg);
        }

        // Turn off output to the console
        $this->setOutputCallback(function () {
        });
    }


    public function tearDown(): void
    {
        if (file_exists($this->envConfigFile)) {
            unlink($this->envConfigFile);
        }

        if (file_exists($this->cmdOptsConfigFile)) {
            unlink($this->cmdOptsConfigFile);
        }
    }


    private function println(
        string $fileName,
        string $data = ''
    ): void {
        $env = fopen($fileName, 'a');
        if ($env === false) throw new Exception(); // TODO: add description
        fwrite($env, "$data\n");
        fclose($env);
    }


    public function testMissingCmdOptsFile(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing command options config file');

        (new CLIContainer(cmdOptsConfigFile: $this->cmdOptsConfigFile))->run();
    }


    public function testEmptyCmdOptsFile(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid command options config file');

        $this->println($this->cmdOptsConfigFile);

        (new CLIContainer(cmdOptsConfigFile: $this->cmdOptsConfigFile))->run();
    }


    public function testBadCmdOptsFile(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid command options config file');

        $this->println($this->cmdOptsConfigFile, "<?php return [1];");

        (new CLIContainer(cmdOptsConfigFile: $this->cmdOptsConfigFile))->run();
    }


    public function testCmdOptsFile(): void
    {
        $this->println($this->cmdOptsConfigFile, "<?php return [];");
        $this->argv[] = '--help';

        (new CLIContainer(cmdOptsConfigFile: $this->cmdOptsConfigFile))->run();

        self::assertTrue(true);
    }


    public function testBadEnvConfigPath(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid environment config directory');

        (new CLIContainer(envConfigPath: './not_a_dir'))->run();
    }


    public function testMissingEnvFile(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing environment config file');

        (new CLIContainer(envConfigPath: $this->envConfigPath))->run();
    }


    public function testMissingRequiredInEnvFile(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Missing required environment variable: 'CMD_OPTS_FILE'");

        $this->println($this->envConfigFile, 'REQUIRED_ENVS="CMD_OPTS_FILE"');

        (new CLIContainer(envConfigPath: $this->envConfigPath))->run();
    }


    public function testEnvFile(): void
    {
        $this->println($this->cmdOptsConfigFile, "<?php return [];");
        $this->println($this->envConfigFile, 'REQUIRED_ENVS="CMD_OPTS_FILE"');
        $this->println($this->envConfigFile, "CMD_OPTS_FILE=\"{$this->cmdOptsConfigFile}\"");
        $this->argv[] = '--help';

        (new CLIContainer(envConfigPath: $this->envConfigPath))->run();

        self::assertTrue(true);
    }


    public function testEnvGlobalVar(): void
    {
        global $_ENV;
        $_ENV['REQUIRED_ENVS'] = 'CMD_OPTS_FILE';
        $_ENV['CMD_OPTS_FILE'] = $this->cmdOptsConfigFile;

        $this->println($this->cmdOptsConfigFile, "<?php return [];");
        $this->argv[] = '--help';

        (new CLIContainer(cmdOptsConfigFile: $this->cmdOptsConfigFile))->run();

        self::assertTrue(true);
    }


    public function testMissingCommand(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invalid command name");

        $this->println($this->cmdOptsConfigFile, "<?php return [];");
        $this->println($this->envConfigFile, 'REQUIRED_ENVS="CMD_OPTS_FILE"');
        $this->println($this->envConfigFile, "CMD_OPTS_FILE=\"{$this->cmdOptsConfigFile}\"");

        (new CLIContainer(envConfigPath: $this->envConfigPath))->run();
    }


    public function testInvalidCommand(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invalid command name");

        $this->println($this->cmdOptsConfigFile, "<?php return [];");
        $this->println($this->envConfigFile, 'REQUIRED_ENVS="CMD_OPTS_FILE"');
        $this->println($this->envConfigFile, "CMD_OPTS_FILE=\"{$this->cmdOptsConfigFile}\"");
        $this->argv[] = 'not-a-command';

        (new CLIContainer(envConfigPath: $this->envConfigPath))->run();
    }


    public function testCommandBadClassName(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invalid command class name");

        $this->println($this->cmdOptsConfigFile, "<?php return [
            'mock-command' => new \\GAState\\Tools\\CLI\\CLICommandConfig(
                Name: 'mock-command',
                Description: 'PHP Unit mock command',
                ClassName: '\\GAState\\Tools\\CLI\\Tests\\NotACommand',
                Arguments: [],
                Options: []
            )
        ];");
        $this->println($this->envConfigFile, 'REQUIRED_ENVS="CMD_OPTS_FILE"');
        $this->println($this->envConfigFile, "CMD_OPTS_FILE=\"{$this->cmdOptsConfigFile}\"");
        $this->argv[] = 'mock-command';

        (new CLIContainer(envConfigPath: $this->envConfigPath))->run();
    }


    public function testCommandBadClassInstance(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invalid command class type");

        $this->println($this->cmdOptsConfigFile, "<?php return [
            'mock-command' => new \\GAState\\Tools\\CLI\\CLICommandConfig(
                Name: 'mock-command',
                Description: 'PHP Unit mock command',
                ClassName: '\\stdClass',
                Arguments: [],
                Options: []
            )
        ];");
        $this->println($this->envConfigFile, 'REQUIRED_ENVS="CMD_OPTS_FILE"');
        $this->println($this->envConfigFile, "CMD_OPTS_FILE=\"{$this->cmdOptsConfigFile}\"");
        $this->argv[] = 'mock-command';

        (new CLIContainer(envConfigPath: $this->envConfigPath))->run();
    }


    public function testCommandMissingRequiredArgument(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Not enough arguments");

        $this->println($this->cmdOptsConfigFile, "<?php return [
            'mock-command' => new \\GAState\\Tools\\CLI\\CLICommandConfig(
                Name: 'mock-command',
                Description: 'PHP Unit mock command',
                ClassName: '\\GAState\\Tools\\CLI\\Tests\\MockCommand',
                Arguments: [
                    new \\GAState\\Tools\\CLI\\CLIArgument(
                        Name: 'first_arg',
                        Description: 'First argument for this command',
                        Required: true
                    )
                ],
                Options: []
            )
        ];");
        $this->println($this->envConfigFile, 'REQUIRED_ENVS="CMD_OPTS_FILE"');
        $this->println($this->envConfigFile, "CMD_OPTS_FILE=\"{$this->cmdOptsConfigFile}\"");
        $this->argv[] = 'mock-command';

        (new CLIContainer(envConfigPath: $this->envConfigPath))->run();
    }


    public function testCommandMissingRequiredOptionArgument(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Option first_opt requires an argument");

        $this->println($this->cmdOptsConfigFile, "<?php return [
            'mock-command' => new \\GAState\\Tools\\CLI\\CLICommandConfig(
                Name: 'mock-command',
                Description: 'PHP Unit mock command',
                ClassName: '\\GAState\\Tools\\CLI\\Tests\\MockCommand',
                Arguments: [],
                Options: [
                    new \\GAState\\Tools\\CLI\\CLIOption(
                        Name: 'first_opt',
                        Description: 'First option for this command',
                        ShortName: null,
                        ArgRequired: true
                    )
                ]
            )
        ];");
        $this->println($this->envConfigFile, 'REQUIRED_ENVS="CMD_OPTS_FILE"');
        $this->println($this->envConfigFile, "CMD_OPTS_FILE=\"{$this->cmdOptsConfigFile}\"");
        $this->argv[] = 'mock-command';
        $this->argv[] = '--first_opt';

        (new CLIContainer(envConfigPath: $this->envConfigPath))->run();
    }


    public function testCommand(): void
    {
        $this->println($this->cmdOptsConfigFile, "<?php return [
            '__global__' => new \\GAState\\Tools\\CLI\\CLICommandConfig(
                Name: '__global__',
                Description: 'Global options',
                ClassName: '',
                Arguments: [],
                Options: []
            ),
            'mock-command' => new \\GAState\\Tools\\CLI\\CLICommandConfig(
                Name: 'mock-command',
                Description: 'PHP Unit mock command',
                ClassName: '\\GAState\\Tools\\CLI\\Tests\\MockCommand',
                Arguments: [],
                Options: [
                    new \\GAState\\Tools\\CLI\\CLIOption(
                        Name: 'first_opt',
                        Description: 'First option for this command',
                        ShortName: null,
                        ArgRequired: true
                    )
                ]
            )
        ];");
        $this->println($this->envConfigFile, 'REQUIRED_ENVS="CMD_OPTS_FILE"');
        $this->println($this->envConfigFile, "CMD_OPTS_FILE=\"{$this->cmdOptsConfigFile}\"");
        $this->argv[] = 'mock-command';
        $this->argv[] = '--first_opt';
        $this->argv[] = 'yes';
        $this->argv[] = '1';

        (new CLIContainer(envConfigPath: $this->envConfigPath))->run();

        self::assertTrue(true);
    }
}

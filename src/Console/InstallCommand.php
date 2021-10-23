<?php

namespace Coffie\Stack\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * Default .yml indent
     *
     * @const string
     */
    const YML_INDENT = '  ';

    /**
     * Available services
     *
     * @const array
     */
    const SERVICES = [
        'mysql',
        'pgsql',
        'mariadb',
        'redis',
        'mailhog',
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stack:install
                {--with= : The services that should be included in the installation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Stack\'s default Docker Compose file';

    /**
     * The default services.
     *
     * @var array
     */
    protected $services = [
        'mysql',
        'redis',
    ];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        switch ($this->option()) {
            case 'with':
                if ($this->option('with') == 'none') {
                    $this->services = [];
                } else {
                    $this->services = explode(',', $this->option('with'));
                }
                break;
            case 'no-interaction':
                break;
            default:
                $this->services = $this->gatherServicesWithMenu();
                break;
        }

        $this->buildDockerCompose();
        $this->replaceEnvVariables();

        $this->info('Stack scaffolding installed successfully.');
    }

    /**
     * Gather the desired Sail services using a Symfony menu.
     *
     */
    protected function gatherServicesWithMenu()
    {
        return $this->choice(
            'Which services would you like to install?',
            static::SERVICES,
            0,
            null,
            true
        );
    }

    /**
     * Build the Docker Compose file.
     *
     * @return void
     */
    protected function buildDockerCompose()
    {
        $compose = file_get_contents(__DIR__ . '/../../stubs/docker-compose.stub');

        $compose = str_replace('{{depends}}', $this->getDepends(), $compose);
        $compose = str_replace('{{services}}', $this->getStubs(), $compose);
        $compose = str_replace('{{volumes}}', $this->getVolumes(), $compose);

        // Remove empty lines...
        $compose = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $compose);

        file_put_contents($this->laravel->basePath('docker-compose.yml'), $compose);
    }

    /**
     * Replace the Host environment variables in the app's .env file.
     *
     * @return void
     */
    protected function replaceEnvVariables()
    {
        $environment = file_get_contents($this->laravel->basePath('.env'));

        if (in_array('pgsql', $this->services)) {
            $environment = str_replace('DB_CONNECTION=mysql', "DB_CONNECTION=pgsql", $environment);
            $environment = str_replace('DB_HOST=127.0.0.1', "DB_HOST=pgsql", $environment);
            $environment = str_replace('DB_PORT=3306', "DB_PORT=5432", $environment);
        } elseif (in_array('mariadb', $this->services)) {
            $environment = str_replace('DB_HOST=127.0.0.1', "DB_HOST=mariadb", $environment);
        } else {
            $environment = str_replace('DB_HOST=127.0.0.1', "DB_HOST=mysql", $environment);
        }

        $environment = str_replace('DB_USERNAME=root', "DB_USERNAME=stack", $environment);
        $environment = preg_replace("/DB_PASSWORD=(.*)/", "DB_PASSWORD=password", $environment);

        $environment = str_replace('MEMCACHED_HOST=127.0.0.1', 'MEMCACHED_HOST=memcached', $environment);
        $environment = str_replace('REDIS_HOST=127.0.0.1', 'REDIS_HOST=redis', $environment);

        file_put_contents($this->laravel->basePath('.env'), $environment);
        file_put_contents($this->laravel->basePath('.env.example'), $environment);
    }

    protected function getVolumes(): string
    {
        return collect($this->services)
            ->filter(function ($service) {
                return in_array($service, ['mysql', 'pgsql', 'mariadb', 'redis']);
            })
            ->map(function ($service) {
                return "  stack-{$service}:\n      driver: local";
            })
            ->whenNotEmpty(function ($collection) {
                return $collection->prepend('volumes:');
            })
            ->implode("\n");
    }

    protected function getDepends(): string
    {
        return collect($this->services)
            ->filter(function ($service) {
                return in_array($service, static::SERVICES);
            })
            ->prepend('php')
            ->map(function ($service) {
                return str_repeat(static::YML_INDENT, 3) . "- {$service}";
            })
            ->prepend('depends_on:')
            ->implode("\n");
    }

    protected function getStubs(): string
    {
        return rtrim(collect($this->services)
            ->add('cli-tools')
            ->map(function ($service) {
                return file_get_contents(__DIR__ . "/../../stubs/{$service}.stub");
            })
            ->implode("\n"));
    }
}
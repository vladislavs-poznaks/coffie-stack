<?php


namespace Tests;

use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Orchestra\Testbench\TestCase;

class InstallTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app->useEnvironmentPath(__DIR__ . '/..');
        $app->bootstrapWith([LoadEnvironmentVariables::class]);
    }

    protected function getPackageProviders($app)
    {
        return [
            'Coffie\\Stack\\StackServiceProvider'
        ];
    }

    /**
     * @test it_installs_docker_compose_without_interaction
     * @covers
     */
    public function it_installs_docker_compose()
    {
        $this->artisan('stack:install --no-interaction')
            ->expectsQuestion('Which services would you like to install?', 'mysql')
            ->expectsOutput('Stack scaffolding installed successfully.');
    }
}
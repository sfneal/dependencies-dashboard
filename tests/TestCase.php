<?php

namespace Sfneal\Dependencies\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Sfneal\Dependencies\Providers\DependenciesServiceProvider;
use Sfneal\Dependencies\Services\DependenciesService;
use Sfneal\Dependencies\Utils\DependencySvg;
use Sfneal\Dependencies\Utils\DependencyUrl;
use Sfneal\Helpers\Strings\StringHelpers;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Register package service providers.
     *
     * @param Application $app
     * @return array|string
     */
    protected function getPackageProviders($app)
    {
        return [
            DependenciesServiceProvider::class,
        ];
    }

    /**
     * Retrieve an array of packages.
     *
     * @return array
     */
    protected function packageProvider(): array
    {
        return [
            ['sfneal/laravel-helpers'],
            ['symfony/console'],
            ['spatie/laravel-view-models'],
        ];
    }

    /**
     * Retrieve the number of packages to expect.
     *
     * @return int
     */
    protected function expectedPackagesCount(): int
    {
        return count($this->packageProvider());
    }

    /**
     * Define environment setup.
     *
     * @param Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('dependencies.github_alias', ['stephenneal' => 'sfneal']);
    }

    /**
     * Execute `DependencyService` assertions.
     *
     * @param Collection $collection
     * @param int $expected
     */
    public function assertDependencyServiceCollection(Collection $collection, int $expected): void
    {
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertSame($expected, $collection->count());

        $collection->each(function (DependenciesService $service) {
            $this->assertTravisSvg($service->packageGitubName, $service->travis());
            $this->assertVersionSvg($service->package, $service->version());
            $this->assertLastCommitSvg($service->packageGitubName, $service->lastCommit());
            $this->assertGithubUrl($service->packageGitubName, $service->gitHub());
            $this->assertTravisUrl($service->packageGitubName, $service->travis());
            $this->assertVersionUrl($service->package, $service->version());
        });
    }

    /**
     * @param string $package
     * @param DependencySvg $generator
     */
    public function assertTravisSvg(string $package, DependencySvg $generator)
    {
        $url = $generator->svg();
        $response = Http::get($url);

        $this->assertInstanceOf(DependencyUrl::class, $generator);
        $this->assertInstanceOf(DependencySvg::class, $generator);
        $this->assertStringContainsString($package, $url);
        $this->assertStringContainsString('travis-ci.com', $url);
        $this->assertStringContainsString('.svg?branch=master', $url);
        $this->assertTrue($response->ok());
        $this->assertStringContainsString('build', $response->body());
    }

    /**
     * @param string $package
     * @param DependencySvg $generator
     */
    public function assertVersionSvg(string $package, DependencySvg $generator)
    {
        $url = $generator->svg();
        $response = Http::get($url);

        $this->assertInstanceOf(DependencyUrl::class, $generator);
        $this->assertInstanceOf(DependencySvg::class, $generator);
        $this->assertStringContainsString($package, $url);
        $this->assertStringContainsString('img.shields.io/', $url);
        $this->assertTrue($response->ok());

        $inString = (new StringHelpers($response->body()));
        $this->assertTrue(
            $inString->inString('<title>packagist: v') || $inString->inString('version')
        );
    }

    /**
     * @param string $package
     * @param DependencySvg $generator
     */
    public function assertLastCommitSvg(string $package, DependencySvg $generator)
    {
        $url = $generator->svg();
        $response = Http::get($url);

        $this->assertInstanceOf(DependencyUrl::class, $generator);
        $this->assertInstanceOf(DependencySvg::class, $generator);
        $this->assertStringContainsString($package, $url);
        $this->assertStringContainsString('img.shields.io/github/last-commit', $url);
        $this->assertTrue($response->ok());
        $this->assertStringContainsString('last commit', $response->body());
    }

    /**
     * @param string $package
     * @param DependencyUrl $generator
     */
    public function assertGithubUrl(string $package, DependencyUrl $generator)
    {
        $url = $generator->url();
        $response = Http::get($url);

        $this->assertInstanceOf(DependencyUrl::class, $generator);
        $this->assertStringContainsString($package, $url);
        $this->assertStringContainsString('github.com', $url);
        $this->assertTrue($response->ok());
    }

    /**
     * @param string $package
     * @param DependencyUrl $generator
     */
    public function assertTravisUrl(string $package, DependencyUrl $generator)
    {
        $url = $generator->url();
        $response = Http::get($url);

        $this->assertInstanceOf(DependencyUrl::class, $generator);
        $this->assertStringContainsString($package, $url);
        $this->assertStringContainsString('travis-ci.com', $url);
        $this->assertTrue($response->ok());
    }

    /**
     * @param string $package
     * @param DependencyUrl $generator
     */
    public function assertVersionUrl(string $package, DependencyUrl $generator)
    {
        $url = $generator->url();
        $response = Http::get($url);

        $this->assertInstanceOf(DependencyUrl::class, $generator);
        $this->assertTrue($response->ok());
        $this->assertStringContainsString($package, $url);
        $inString = new StringHelpers($url);
        $this->assertTrue(
            $inString->inString('packagist.org/packages') || $inString->inString('hub.docker.com/r/')
        );
    }
}

<?php

namespace Sfneal\Dependencies\Tests\Feature;

use Sfneal\Dependencies\Tests\TestCase;
use Sfneal\Dependencies\Utils\GithubUrl;

class GithubUrlTest extends TestCase
{
    /**
     * Retrieve an array of packages.
     *
     * @return array
     */
    public function packageProviderWithWorkflows(): array
    {
        return collect([
            ['sfneal/dependencies', 'composer'],
            ['sfneal/socials', 'composer'],
            ['sfneal/users', 'composer'],
            ['sfneal/models', 'composer'],
            ['sfneal/laravel-helpers', 'composer'],
            ['sfneal/datum', 'composer'],
            ['sfneal/tracking', 'composer'],
        ])
        ->map(function(array $dependency) {
            $dependency[] = ['Docker Builds', 'Test Suite'];
            return $dependency;
        })
        ->shuffle()
        ->toArray();
    }

    /**
     * @test
     * @dataProvider packageProviderWithWorkflows
     *
     * @param string $package
     * @param string $type
     * @param array $workflows
     */
    public function github_url_public_methods(string $package, string $type, array $workflows)
    {
        $github = new GithubUrl($package);

        $this->assertGithub($package, $github, true);
    }
}

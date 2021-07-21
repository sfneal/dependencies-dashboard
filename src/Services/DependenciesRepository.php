<?php

namespace Sfneal\Dependencies\Services;

use Illuminate\Support\Collection;
use Sfneal\Dependencies\Utils\ComposerDependencies;
use Sfneal\Helpers\Strings\StringHelpers;

class DependenciesRepository
{
    // todo: add caching support?
    // todo: add ability to pass an array of dependencies? (array, type) params

    /**
     * @var array Array of composer or Docker dependencies
     */
    private $dependencies;

    /**
     * @var bool Use composer.json dependencies as source
     */
    private $composerDependencies;

    /**
     * @var bool Include composer dev dependencies
     */
    private $devComposerDependencies;

    /**
     * Retrieve dependencies from the composer.json file & optionally include 'dev' dependencies.
     *
     * @param bool $devComposerDependencies
     * @return $this
     */
    public function fromComposer(bool $devComposerDependencies = false): self
    {
        $this->composerDependencies = true;
        $this->devComposerDependencies = $devComposerDependencies;

        return $this;
    }

    /**
     * Retrieve dependencies from the config file.
     *
     * @return $this
     */
    public function fromConfig(): self
    {
        $this->composerDependencies = false;
        $this->dependencies = config('dependencies.dependencies');

        return $this;
    }

    /**
     * Retrieve dependencies from an array.
     *
     * @param array $dependencies
     * @return $this
     */
    public function fromArray(array $dependencies): self
    {
        $this->composerDependencies = false;
        $this->dependencies = $dependencies;

        return $this;
    }

    /**
     * Retrieve a Collection of Dependencies with GitHub, Packagist version & Travis CI build status URLs.
     *
     * @return Collection
     */
    public function get(): Collection
    {
        return $this->getDependencies()->map(function (string $type, string $dependency) {
            return new DependenciesService($dependency, $type);
        });
    }

    /**
     * Retrieve a list of dependencies from the config file or by reading the composer.json 'requires' section.
     *
     * @return Collection
     */
    private function getDependencies(): Collection
    {
        if ($this->composerDependencies) {
            return $this->getComposerRequirements();
        }

        return $this->getArrayDependencies() ?? $this->getComposerRequirements();
    }

    /**
     * Retrieve a list of dependencies set in the 'dependencies' config.
     *
     * @return Collection
     */
    private function getArrayDependencies(): Collection
    {
        // Convert array of dependency type keys & array of dependency values
        // to a flat array of dependency keys and type values
        return collect($this->dependencies)
            ->mapWithKeys(function ($packages, $type) {
                return collect($packages)
                    ->mapWithKeys(function (string $package) use ($type) {
                        return [$package => $type];
                    });
            });
    }

    /**
     * Retrieve an array of composer packages that are required by the composer.json.
     *
     * @return Collection
     */
    private function getComposerRequirements(): Collection
    {
        // Retrieve 'require' array from composer.json with only package names (the keys
        // todo: remove keys?
        return collect(array_keys((new ComposerDependencies($this->devComposerDependencies))->get()))

            // Remove 'php' & php extensions from the packages array
            ->filter(function (string $dep) {
                return $dep != 'php' && ! (new StringHelpers($dep))->inString('ext');
            })

            // Map each dependencies to have a 'composer' value
            ->mapWithKeys(function (string $dep) {
                return [$dep => 'composer'];
            });
    }
}
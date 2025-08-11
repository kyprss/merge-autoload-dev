<?php

declare(strict_types=1);

namespace Kyprss\MergeAutoloadDev\Actions;

use Composer\Composer;
use Composer\IO\IOInterface;
use Kyprss\MergeAutoloadDev\PluginState;

final readonly class MergeAutoloadDevAction
{
    public function __construct(
        private Composer $composer,
        private IOInterface $io,
        private PluginState $state
    ) {}

    public function execute(): void
    {
        if (empty($this->state->getInclude())) {
            return;
        }

        $packageComposerFiles = [];
        foreach ($this->state->getInclude() as $include) {
            $packageComposerFiles = [...$packageComposerFiles, ...glob($include)];
        }

        if (empty($packageComposerFiles)) {
            return;
        }

        $packagesAutoloadDev = $this->collectPackagesAutoloadDev($packageComposerFiles);
        if (empty($packagesAutoloadDev)) {
            return;
        }

        $this->mergeIntoRootPackage($packagesAutoloadDev);

        if (isset($packagesAutoloadDev['psr-4'])) {
            $this->io->write(sprintf('<info>[merge-autoload-dev] - Merged %d PSR-4 namespaces</info>', count($packagesAutoloadDev['psr-4'])));
        }
        if (isset($packagesAutoloadDev['psr-0'])) {
            $this->io->write(sprintf('<info>[merge-autoload-dev] - Merged %d PSR-0 namespaces</info>', count($packagesAutoloadDev['psr-0'])));
        }
        if (isset($packagesAutoloadDev['classmap'])) {
            $this->io->write(sprintf('<info>[merge-autoload-dev] - Merged %d class maps</info>', count($packagesAutoloadDev['classmap'])));
        }
        if (isset($packagesAutoloadDev['files'])) {
            $this->io->write(sprintf('<info>[merge-autoload-dev] - Merged %d files</info>', count($packagesAutoloadDev['files'])));
        }
    }

    private function collectPackagesAutoloadDev(array $packageComposerFiles): array
    {
        $packagesAutoloadDev = [];

        foreach ($packageComposerFiles as $packageComposerFile) {
            $jsonData = json_decode(file_get_contents($packageComposerFile), true);
            if (! isset($jsonData['autoload-dev'])) {
                continue;
            }

            $packagePath = pathinfo($packageComposerFile, PATHINFO_DIRNAME);

            // PSR-4
            if (isset($jsonData['autoload-dev']['psr-4'])) {
                foreach ($jsonData['autoload-dev']['psr-4'] as $namespace => $path) {
                    if (is_array($path)) {
                        $packagesAutoloadDev['psr-4'][$namespace] = array_map(
                            fn (string $p): string => "{$packagePath}/".mb_ltrim($p, '/'),
                            $path
                        );
                    } else {
                        $packagesAutoloadDev['psr-4'][$namespace] = "{$packagePath}/".mb_ltrim($path, '/');
                    }
                }
            }

            // PSR-0
            if (isset($jsonData['autoload-dev']['psr-0'])) {
                foreach ($jsonData['autoload-dev']['psr-0'] as $namespace => $path) {
                    if (is_array($path)) {
                        $packagesAutoloadDev['psr-0'][$namespace] = array_map(
                            fn (string $p): string => "{$packagePath}/".mb_ltrim($p, '/'),
                            $path
                        );
                    } else {
                        $packagesAutoloadDev['psr-0'][$namespace] = "{$packagePath}/".mb_ltrim($path, '/');
                    }
                }
            }

            // classmap
            if (isset($jsonData['autoload-dev']['classmap'])) {
                foreach ($jsonData['autoload-dev']['classmap'] as $path) {
                    $packagesAutoloadDev['classmap'][] = "{$packagePath}/".mb_ltrim($path, '/');
                }
            }

            // files
            if (isset($jsonData['autoload-dev']['files'])) {
                foreach ($jsonData['autoload-dev']['files'] as $path) {
                    $packagesAutoloadDev['files'][] = "{$packagePath}/".mb_ltrim($path, '/');
                }
            }
        }

        return $packagesAutoloadDev;
    }

    private function mergeIntoRootPackage(array $packagesAutoloadDev): void
    {
        $rootPackage = $this->composer->getPackage();
        $rootAutoloadDev = $rootPackage->getDevAutoload();

        // PSR-4
        if (isset($packagesAutoloadDev['psr-4'])) {
            $rootAutoloadDev['psr-4'] = array_merge($rootAutoloadDev['psr-4'] ?? [], $packagesAutoloadDev['psr-4']);
        }

        // PSR-0
        if (isset($packagesAutoloadDev['psr-0'])) {
            $rootAutoloadDev['psr-0'] = array_merge($rootAutoloadDev['psr-0'] ?? [], $packagesAutoloadDev['psr-0']);
        }

        // classmap
        if (isset($packagesAutoloadDev['classmap'])) {
            $rootAutoloadDev['classmap'] = array_merge($rootAutoloadDev['classmap'] ?? [], $packagesAutoloadDev['classmap']);
        }

        // files
        if (isset($packagesAutoloadDev['files'])) {
            $rootAutoloadDev['files'] = array_merge($rootAutoloadDev['files'] ?? [], $packagesAutoloadDev['files']);
        }

        $rootPackage->setDevAutoload($rootAutoloadDev);
    }
}

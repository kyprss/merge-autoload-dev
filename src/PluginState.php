<?php

declare(strict_types=1);

namespace Kyprss\MergeAutoloadDev;

use Composer\Composer;

final class PluginState
{
    private Composer $composer;

    private array $settings = [];

    public function __construct(Composer $composer)
    {
        $this->composer = $composer;
    }

    public function includesDev(): bool
    {
        // Check if we're in dev mode (install/update with --dev)
        $devMode = true;

        if (method_exists($this->composer, 'getLocker')) {
            $locker = $this->composer->getLocker();
            if ($locker && method_exists($locker, 'isLocked')) {
                $devMode = ! $locker->isLocked() || $locker->getDevMode();
            }
        }

        return $devMode;
    }

    public function loadSettings(): void
    {
        $this->settings = $this->composer->getPackage()->getExtra()['merge-autoload-dev'] ??= [];
    }

    public function getInclude(): array
    {
        return $this->settings['include'] ?? [];
    }
}

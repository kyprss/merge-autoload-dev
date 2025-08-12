<?php

declare(strict_types=1);

namespace Kyprss\MergeAutoloadDev;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use Composer\Installer\PackageEvents;
use Kyprss\MergeAutoloadDev\Actions\MergeAutoloadDevAction;

final class Plugin implements EventSubscriberInterface, PluginInterface
{
    private const PRIORITY = 50000;

    private Composer $composer;

    private IOInterface $io;

    private PluginState $state;

    public static function getSubscribedEvents(): array
    {
        return [
            PluginEvents::INIT => ['onInit', self::PRIORITY],
            PackageEvents::POST_PACKAGE_INSTALL => ['mergeFiles', self::PRIORITY],
            ScriptEvents::POST_INSTALL_CMD => ['mergeFiles', self::PRIORITY],
            ScriptEvents::POST_UPDATE_CMD => ['mergeFiles', self::PRIORITY],
            ScriptEvents::PRE_AUTOLOAD_DUMP => ['mergeFiles', self::PRIORITY],
            ScriptEvents::PRE_INSTALL_CMD => ['mergeFiles', self::PRIORITY],
            ScriptEvents::PRE_UPDATE_CMD => ['mergeFiles', self::PRIORITY],
        ];
    }

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->state = new PluginState($this->composer);
    }

    public function deactivate(Composer $composer, IOInterface $io): void {}

    public function uninstall(Composer $composer, IOInterface $io): void {}

    public function onInit(): void
    {
        $this->state->loadSettings();
    }

    public function mergeFiles(): void
    {
        $action = new MergeAutoloadDevAction(
            $this->composer,
            $this->io,
            $this->state
        );

        $action->execute();
    }
}

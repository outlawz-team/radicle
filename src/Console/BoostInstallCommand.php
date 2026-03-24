<?php

namespace OutlawzTeam\Radicle\Console;

use Roots\Acorn\Console\Commands\Command;

class BoostInstallCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'outlawz:boost';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Outlawz Boost AI files into the project root';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $aiDir = __DIR__ . '/../../.ai';

        if (!is_dir($aiDir)) {
            $this->error('Source .ai directory not found in the package.');
            return;
        }

        $projectRoot = $this->laravel->basePath();

        foreach (new \DirectoryIterator($aiDir) as $item) {
            if ($item->isDot() || !$item->isDir()) {
                continue;
            }

            $this->copyDirectory($item->getPathname(), $projectRoot);
        }

        $this->info('Outlawz Boost AI files installed successfully.');
    }

    /**
     * Recursively copy a directory's contents into a destination directory.
     *
     * @param string $source
     * @param string $destination
     * @return void
     */
    protected function copyDirectory(string $source, string $destination): void
    {
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($items as $item) {
            $relativePath = substr($item->getPathname(), strlen($source) + 1);
            $target = $destination . DIRECTORY_SEPARATOR . $relativePath;

            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                copy($item->getPathname(), $target);
                $this->line("  <fg=green>copied</> {$relativePath}");
            }
        }
    }
}

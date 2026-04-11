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
     * Agent skill target paths relative to the project root.
     *
     * @var array<string, string>
     */
    protected array $agentSkillPaths = [
        'claude' => '.claude/skills',
        'codex'  => '.agents/skills',
    ];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->installAiFiles();
        $this->installSkills();
    }

    /**
     * Copy .ai directory contents into the project root.
     *
     * @return void
     */
    protected function installAiFiles(): void
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

        $this->info('AI files installed successfully.');
    }

    /**
     * Discover and install skills from all Composer packages that declare
     * an `extra.outlawz.skills` path in their composer.json.
     *
     * @return void
     */
    protected function installSkills(): void
    {
        $skills = $this->discoverSkills();

        if ($skills === []) {
            $this->warn('No skills found in installed packages.');
            return;
        }

        foreach ($skills as $name => $skillPath) {
            foreach ($this->agentSkillPaths as $agent => $agentPath) {
                $targetDir = $this->laravel->basePath($agentPath . DIRECTORY_SEPARATOR . $name);

                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                $this->copySkill($skillPath, $targetDir);
                $this->line("  <fg=green>installed</> skill [{$agent}]: {$name}");
            }
        }

        $this->info('Skills installed successfully.');
    }

    /**
     * Discover skills from installed Composer packages by reading
     * vendor/composer/installed.json and looking for extra.outlawz.skills.
     *
     * @return array<string, string> Skill name => absolute path to skill directory
     */
    protected function discoverSkills(): array
    {
        $installedJson = $this->laravel->basePath('vendor/composer/installed.json');

        if (!file_exists($installedJson)) {
            $this->warn('vendor/composer/installed.json not found.');
            return [];
        }

        $installed = json_decode(file_get_contents($installedJson), true);

        // Composer 2.x wraps packages under a "packages" key
        $packages = $installed['packages'] ?? $installed;

        $skills = [];

        foreach ($packages as $package) {
            $skillsDir = $package['extra']['outlawz']['skills'] ?? null;

            if ($skillsDir === null) {
                continue;
            }

            $packageVendorPath = $this->laravel->basePath('vendor' . DIRECTORY_SEPARATOR . $package['name']);
            $fullSkillsPath = $packageVendorPath . DIRECTORY_SEPARATOR . $skillsDir;

            if (!is_dir($fullSkillsPath)) {
                continue;
            }

            foreach (new \DirectoryIterator($fullSkillsPath) as $item) {
                if ($item->isDot() || !$item->isDir()) {
                    continue;
                }

                $skillFile = $item->getPathname() . DIRECTORY_SEPARATOR . 'SKILL.md';

                if (!file_exists($skillFile)) {
                    continue;
                }

                $skills[$item->getFilename()] = $item->getPathname();
            }
        }

        return $skills;
    }

    /**
     * Copy all files from a skill directory into the target directory.
     *
     * @param string $source
     * @param string $destination
     * @return void
     */
    protected function copySkill(string $source, string $destination): void
    {
        foreach (new \DirectoryIterator($source) as $item) {
            if ($item->isDot() || $item->isDir()) {
                continue;
            }

            copy($item->getPathname(), $destination . DIRECTORY_SEPARATOR . $item->getFilename());
        }
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

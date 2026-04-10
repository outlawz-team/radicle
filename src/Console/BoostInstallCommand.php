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
     * Skills to install, keyed by skill name with the raw GitHub base URL as value.
     *
     * @var array<string, string>
     */
    protected array $skills = [
        'acf'                  => 'https://raw.githubusercontent.com/outlawz-team/skills/main/skills',
        'tailwind-v4'          => 'https://raw.githubusercontent.com/outlawz-team/skills/main/skills',
        'web-design-guidelines' => 'https://raw.githubusercontent.com/vercel-labs/agent-skills/main/skills',
    ];

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
     * Download and install skills from outlawz-team/skills into each agent's skills directory.
     *
     * @return void
     */
    protected function installSkills(): void
    {
        $projectRoot = $this->laravel->basePath();

        foreach ($this->skills as $skill => $skillsBaseUrl) {
            $url = "{$skillsBaseUrl}/{$skill}/SKILL.md";
            $content = @file_get_contents($url);

            if ($content === false) {
                $this->warn("  Could not download skill: {$skill}");
                continue;
            }

            foreach ($this->agentSkillPaths as $agent => $path) {
                $targetDir = $projectRoot . DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR . $skill;
                $targetFile = $targetDir . DIRECTORY_SEPARATOR . 'SKILL.md';

                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                file_put_contents($targetFile, $content);
                $this->line("  <fg=green>installed</> skill [{$agent}]: {$skill}");
            }
        }

        $this->info('Skills installed successfully.');
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

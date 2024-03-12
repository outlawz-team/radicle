<?php

namespace OutlawzTeam\Radicle\Console;

use Roots\Acorn\Console\Commands\Command;

class AcfCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'acf';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'My custom Acorn command.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info('test');
    }
}

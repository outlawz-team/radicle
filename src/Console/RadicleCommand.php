<?php

namespace OutlawzTeam\Radicle\Console;

use Roots\Acorn\Console\Commands\Command;
use OutlawzTeam\Radicle\Facades\Radicle;

class RadicleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'radicle';

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
        $this->info(
            Radicle::getQuote()
        );
    }
}

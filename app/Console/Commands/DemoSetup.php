<?php

namespace App\Console\Commands;

use App\Support\DemoPool;
use Illuminate\Console\Command;

class DemoSetup extends Command
{
    protected $signature = 'demo:setup';

    protected $description = 'Create, migrate and seed the pool of demo databases (run once on the demo host).';

    public function handle(DemoPool $pool): int
    {
        if (! config('vendormap.demo.enabled')) {
            $this->warn('Demo mode is disabled in config.php (demo.enabled = false).');
            if (! $this->confirm('Set up the pool anyway?', true)) {
                return self::SUCCESS;
            }
        }

        $size = $pool->size();
        $this->info("Provisioning {$size} demo database(s) with prefix \""
            . config('vendormap.demo.db_prefix') . '"…');

        $bar = $this->output->createProgressBar($size);
        $bar->start();

        $pool->setup(function () use ($bar) {
            $bar->advance();
        });

        $bar->finish();
        $this->newLine(2);
        $this->info('Demo pool ready. Set demo.enabled = true in config.php to serve it.');

        return self::SUCCESS;
    }
}

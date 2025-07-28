<?php

declare(strict_types=1);

namespace Jordanpartridge\ConduitDocker\Commands;

use Illuminate\Console\Command;

class DockerinitCommand extends Command
{
    protected $signature = 'docker:init';

    protected $description = 'Sample command for docker component';

    public function handle(): int
    {
        $this->info('🚀 docker component is working!');
        $this->line('This is a sample command. Implement your logic here.');
        
        return 0;
    }
}
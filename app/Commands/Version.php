<?php
/**
 * Copyright © BoonWeb GmbH. All rights reserved.
 * See LICENSE for license details.
 */

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

use function Termwind\render;

class Version extends Command
{
    protected $signature = 'version';

    protected $description = 'Return current version';

    public function handle()
    {
        $version = config('app.version');

        render(
            <<<HTML
            <div class="py-1 ml-2">
                <div class="px-1 bg-orange-800 text-black">VERSION</div>
                <em class="ml-1">
                  Version: $version
                </em>
            </div>
HTML
        );
    }
}

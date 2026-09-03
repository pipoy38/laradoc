<?php
namespace App\Console\Commands;

use App\Support\AdminPrefix;
use App\Support\Settings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class WikiAdminPrefix extends Command
{
    protected $signature = 'wiki:admin-prefix {action=show : show|reset}';
    protected $description = 'Show or reset the admin URL prefix';

    public function handle(Settings $settings): int
    {
        if ($this->argument('action') === 'reset') {
            $settings->set('admin_prefix', AdminPrefix::DEFAULT);
            Artisan::call('route:clear');
            $this->info('Admin prefix reset to /'.AdminPrefix::DEFAULT);
            return self::SUCCESS;
        }
        $this->line('Admin URL prefix: /'.AdminPrefix::current());
        return self::SUCCESS;
    }
}

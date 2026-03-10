<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(EsiScopeSeeder::class);

        Artisan::call('sde:download', ['--no-interaction' => true]);
        Artisan::call('sde:seed', ['--no-interaction' => true]);
    }
}

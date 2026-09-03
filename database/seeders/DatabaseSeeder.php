<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database. `wiki:install` calls SampleContentSeeder directly and
     * never touches this class, so this only runs on an explicit `php artisan db:seed`.
     * SampleContentSeeder already tolerates running with no admin user present (it just leaves
     * created_by null), so this is harmless to run standalone too.
     */
    public function run(): void
    {
        $this->call(SampleContentSeeder::class);
    }
}

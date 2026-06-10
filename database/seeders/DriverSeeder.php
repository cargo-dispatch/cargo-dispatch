<?php

namespace Database\Seeders;

use App\Models\Drivers\Driver;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DriverSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       \App\Models\Drivers\Driver::factory()->count(100)->create();
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('attributes')->updateOrInsert(
            ['code' => 'color'],
            [
                'name' => 'Color',
                'data_type' => 'select',
                'is_active' => true,
            ]
        );

        DB::table('attributes')->updateOrInsert(
            ['code' => 'size'],
            [
                'name' => 'Size',
                'data_type' => 'select',
                'is_active' => true,
            ]
        );
    }
} 

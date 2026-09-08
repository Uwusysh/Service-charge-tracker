<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@setk.test'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMINISTRATOR,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'manager@setk.test'],
            [
                'name' => 'Block Manager',
                'password' => Hash::make('password'),
                'role' => User::ROLE_BLOCK_MANAGER,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'accountant@setk.test'],
            [
                'name' => 'Service Charge Accountant',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ACCOUNTANT,
            ]
        );

        $this->call([
            CostHeadingSeeder::class,
            SamplePortfolioSeeder::class,
        ]);
    }
}

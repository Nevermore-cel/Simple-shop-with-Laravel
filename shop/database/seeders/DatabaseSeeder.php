<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Создаем несколько категорий
        Category::factory(5)->create();

        // Создаем несколько продуктов, привязанных к категориям
        Product::factory(20)->create();

        // Создаем администратора
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'), 
            'role' => 'admin',
            'balance' => 10000,
        ]);

        // Создаем обычного пользователя
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'), 
            'role' => 'customer',
            'balance' => 500,
        ]);
    }
}
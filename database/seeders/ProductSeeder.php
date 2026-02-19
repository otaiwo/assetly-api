<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use App\Enums\ProductStatus;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Make sure we have a user
        $user = User::first();
        if (!$user) {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        // Make sure we have a category
        $category = Category::first();
        if (!$category) {
            $category = Category::create([
                'name' => 'UI Templates',
            ]);
        }

        // Create 5 products
        Product::create([
            'name' => 'UI Kit Pro',
            'description' => 'Modern UI kit for SaaS dashboards',
            'price' => 49.99,
            'category_id' => $category->id,
            'user_id' => $user->id,
            'status' => ProductStatus::APPROVED,
            'type' => 'pro',
            'credit_cost' => 10,
        ]);

        Product::create([
            'name' => 'Free Icon Pack',
            'description' => 'Minimal line icons for all projects',
            'price' => 0,
            'category_id' => $category->id,
            'user_id' => $user->id,
            'status' => ProductStatus::APPROVED,
            'type' => 'free',
            'credit_cost' => 0,
        ]);

        Product::create([
            'name' => 'Landing Page Template',
            'description' => 'High converting startup landing page',
            'price' => 29.99,
            'category_id' => $category->id,
            'user_id' => $user->id,
            'status' => ProductStatus::APPROVED,
            'type' => 'pro',
            'credit_cost' => 5,
        ]);

        Product::create([
            'name' => 'Mobile App UI Kit',
            'description' => 'Complete Figma mobile UI kit',
            'price' => 59.99,
            'category_id' => $category->id,
            'user_id' => $user->id,
            'status' => ProductStatus::APPROVED,
            'type' => 'pro',
            'credit_cost' => 15,
        ]);

        Product::create([
            'name' => 'Wireframe Pack',
            'description' => 'Low fidelity wireframe components',
            'price' => 0,
            'category_id' => $category->id,
            'user_id' => $user->id,
            'status' => ProductStatus::APPROVED,
            'type' => 'free',
            'credit_cost' => 0,
        ]);
    }
}

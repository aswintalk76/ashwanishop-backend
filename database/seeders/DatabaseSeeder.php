<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $admin = User::firstOrCreate(
            ['email' => 'admin@ashwanishop.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('Admin@12345'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $admin->assignRole($adminRole);

        $user = User::firstOrCreate(
            ['email' => 'user@demo.com'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('User@12345'),
                'email_verified_at' => now(),
                'is_active' => true,
                'phone' => '9876543210',
            ]
        );
        $user->assignRole($userRole);

        Setting::set('site_name', 'Ashwani Shop', 'general');
        Setting::set('site_tagline', 'Premium Shopping Experience', 'general');
        Setting::set('contact_email', 'support@ashwanishop.com', 'general');
        Setting::set('contact_phone', '+91 9876543210', 'general');
        Setting::set('upi_id', 'ashwanishop@upi', 'payment');

        $categories = [
            ['name' => 'Electronics', 'slug' => 'electronics'],
            ['name' => 'Fashion', 'slug' => 'fashion'],
            ['name' => 'Home & Living', 'slug' => 'home-living'],
        ];

        foreach ($categories as $i => $cat) {
            $category = Category::firstOrCreate(['slug' => $cat['slug']], [
                ...$cat,
                'description' => "Browse our {$cat['name']} collection",
                'is_active' => true,
                'sort_order' => $i,
            ]);

            for ($j = 1; $j <= 4; $j++) {
                $name = "{$cat['name']} Product {$j}";
                Product::firstOrCreate(
                    ['sku' => strtoupper($cat['slug'])."-{$j}"],
                    [
                        'category_id' => $category->id,
                        'name' => $name,
                        'slug' => Str::slug($name).'-'.Str::random(4),
                        'description' => "High quality {$name} with premium features and great value.",
                        'price' => rand(500, 5000),
                        'discount' => rand(0, 30),
                        'stock' => rand(10, 100),
                        'rating' => rand(35, 50) / 10,
                        'review_count' => rand(5, 50),
                        'images' => [],
                        'status' => 'active',
                    ]
                );
            }
        }
    }
}

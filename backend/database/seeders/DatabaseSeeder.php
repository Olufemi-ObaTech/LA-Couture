<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin account ────────────────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'admin@lacouture.com'],
            [
                'name'     => 'L.A. Couture Admin',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'LaCouture@Admin2026!')),
                'role'     => 'admin',
                'status'   => 'approved',
            ]
        );

        // ── Customer Service account ─────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'cs@lacouture.com'],
            [
                'name'     => 'L.A. Couture Customer Service',
                'password' => Hash::make(env('CS_PASSWORD', 'LaCouture@CS2026!')),
                'role'     => 'cs',
                'status'   => 'approved',
            ]
        );

        // ── Demo client account ──────────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'DemoClients@lacouture.com'],
            [
                'name'           => 'Demo Client',
                'brand_email'    => 'demo@lacouture.com',
                'personal_email' => 'DemoClients@lacouture.com',
                'password'       => Hash::make(env('DEMO_CLIENT_PASSWORD', 'LaCouture@Democlients2026!')),
                'role'           => 'client',
                'status'         => 'approved',
                'phone'          => '+234 900 000 0000',
                'interests'      => 'Luxury Suits, Agbada & Native',
                'approved_at'    => now(),
            ]
        );

        // ── Product catalog ──────────────────────────────────────────────────
        $products = [
            ['sku' => 'P001', 'name' => 'Abuja Power Suit',       'category' => 'Luxury Suits',    'price' => 200000, 'tag' => 'New',        'image' => 'img/black-breasted-suit.jpg'],
            ['sku' => 'P002', 'name' => 'Royal Agbada Set',        'category' => 'Agbada & Native', 'price' => 150000, 'tag' => 'Bestseller', 'image' => 'img/agbada-collection.jpg'],
            ['sku' => 'P003', 'name' => 'Bespoke Commission',      'category' => 'Bespoke',         'price' => 90000,  'tag' => 'Custom',     'image' => 'img/bespoke-design.jpg'],
            ['sku' => 'P004', 'name' => 'Lagos Street Luxe Set',   'category' => 'Smart Casual',    'price' => 150000, 'tag' => null,         'image' => 'img/kings-dashiki.jpg'],
            ['sku' => 'P005', 'name' => 'FCT Executive',           'category' => 'Formal Wear',     'price' => 80000,  'tag' => 'New',        'image' => 'img/fct-executive.jpg'],
            ['sku' => 'P006', 'name' => 'Gold Cufflink & Tie Set', 'category' => 'Accessories',     'price' => 35000,  'tag' => null,         'image' => 'img/gold-accessories.jpg'],
            ['sku' => 'P007', 'name' => 'Abuja Three-Piece',       'category' => 'Luxury Suits',    'price' => 155000, 'tag' => 'Limited',    'image' => 'img/luxury-suits.jpg'],
            ['sku' => 'P008', 'name' => 'Aso-Oke Senator Set',     'category' => 'Agbada & Native', 'price' => 120000, 'tag' => null,         'image' => 'img/senator-attire.jpg'],
            ['sku' => 'P009', 'name' => 'Weekend Linen Set',       'category' => 'Smart Casual',    'price' => 45000,  'tag' => null,         'image' => 'img/weekend-linen.jpg'],
        ];

        foreach ($products as $p) {
            Product::firstOrCreate(
                ['sku' => $p['sku']],
                array_merge($p, ['active' => true, 'stock' => 10])
            );
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'customer', 'label' => 'Customer'],
            ['name' => 'seller', 'label' => 'Seller'],
            ['name' => 'driver', 'label' => 'Driver'],
            ['name' => 'admin', 'label' => 'Administrator'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], ['label' => $role['label']]);
        }
    }
}

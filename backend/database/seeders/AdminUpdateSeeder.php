<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUpdateSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        if ($admin) {
            $admin->update([
                'phone' => 'admin@uzagromind.uz',
                'password' => Hash::make('uzagromind4321'),
                'plain_password' => 'uzagromind4321'
            ]);
        } else {
            User::create([
                'name' => 'Admin AgroMind',
                'phone' => 'admin@uzagromind.uz',
                'role' => 'admin',
                'password' => Hash::make('uzagromind4321'),
                'plain_password' => 'uzagromind4321'
            ]);
        }

        // Qoraqalpog'iston regionini aniqlaymiz yoki birinchisini olamiz
        $region = \App\Models\Region::where('name', 'like', '%Qoraqalpog%')->first() 
            ?? \App\Models\Region::first();

        if ($region) {
            // Amudaryo tumani nazoratchisi
            User::updateOrCreate(
                ['phone' => 'amudaryo_monitor'],
                [
                    'name' => 'Amudaryo Nazoratchisi',
                    'role' => 'monitor',
                    'region_id' => $region->id,
                    'district' => 'Amudaryo tumani',
                    'password' => Hash::make('secretpassword'),
                    'plain_password' => 'secretpassword'
                ]
            );

            // Shumanay tumani nazoratchisi
            User::updateOrCreate(
                ['phone' => 'shumanay_monitor'],
                [
                    'name' => 'Shumanay Nazoratchisi',
                    'role' => 'monitor',
                    'region_id' => $region->id,
                    'district' => 'Shumanay tumani',
                    'password' => Hash::make('secretpassword'),
                    'plain_password' => 'secretpassword'
                ]
            );

            // Mavjud fermalarni o'z egasining tumaniga biriktiramiz
            $farms = \App\Models\Farm::with('owner')->get();
            foreach ($farms as $farm) {
                if ($farm->owner) {
                    $farm->update([
                        'district' => $farm->owner->district ?? 'Amudaryo tumani',
                        'region_id' => $farm->owner->region_id ?? $region->id
                    ]);
                } else {
                    $farm->update([
                        'district' => 'Amudaryo tumani',
                        'region_id' => $region->id
                    ]);
                }
            }
        }
    }
}

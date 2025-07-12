<?php

namespace Database\Seeders;

use App\PermissionEnum;
use App\RoleEnum;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (PermissionEnum::cases() as $permission) {
            Permission::create(['name' => $permission->value]);
        }

        // الدور: Admin
        $admin = Role::create(['name' => RoleEnum::ADMIN]);
        $admin->givePermissionTo(Permission::all());

        // الدور: Moderator
        $mod = Role::create(['name' => RoleEnum::MODERATOR]);
        $mod->givePermissionTo([PermissionEnum::REPLY_TO_MESSAGES->value, PermissionEnum::VIEW_USERS->value]);

        // الدور: User
        Role::create(['name' => RoleEnum::USER]); // بدون صلاحيات خاصة

    }
}

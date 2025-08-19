<?php

namespace Database\Seeders;

use App\PermissionEnum;
use App\RoleEnum;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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

        DB::table('role_has_permissions')->delete();
        DB::table('model_has_permissions')->delete();
        DB::table('model_has_roles')->delete();
        DB::table('permissions')->delete();
        DB::table('roles')->delete();


        foreach (PermissionEnum::cases() as $permission) {
            Permission::create(['name' => $permission->value]);
        }

        // الدور: Admin
        $admin = Role::create(['name' => RoleEnum::ADMIN]);
        $admin->givePermissionTo(Permission::all());

        // الدور: Moderator
        $mod = Role::create(['name' => RoleEnum::MODERATOR]);
        $mod->givePermissionTo(Permission::where('name', '!=', 'manage users')->get());

        $support = Role::firstOrCreate(['name' => 'support']);
        $support->givePermissionTo(['reply tickets']);

        // الدور: User
        Role::create(['name' => RoleEnum::USER]); // بدون صلاحيات خاصة
        Role::create(['name' => RoleEnum::CUSTOM]); // بدون صلاحيات خاصة

    }
}

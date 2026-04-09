<?php

namespace Database\Seeders;

use App\Enums\RbacPermission;
use App\Enums\RbacRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = 'web';

        $permissions = [
            RbacPermission::ADMIN_ACCESS->value,
            RbacPermission::USERS_MANAGE->value,
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, $guard);
        }

        $admin = Role::findOrCreate(RbacRole::ADMIN->value, $guard);
        $student = Role::findOrCreate(RbacRole::STUDENT->value, $guard);

        $admin->syncPermissions($permissions);
        $student->syncPermissions([]);
    }
}


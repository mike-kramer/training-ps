<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const string PERMISSION = 'statistics.view';

    public function up(): void
    {
        $permission = Permission::create(['name' => self::PERMISSION]);
        $role = Role::where('name', 'admin')->first();
        if ($role !== null) {
            $role->permissions()->attach($permission);
        }
    }

    public function down(): void
    {
        Permission::where('name', self::PERMISSION)->delete();
    }
};

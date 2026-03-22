<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teams = config('permission.teams');

        if (!$teams) {
            return;
        }

        $teamKey = (string) ($columnNames['team_foreign_key'] ?? 'tenant_id');

        $alreadyTeamsReady =
            Schema::hasColumn($tableNames['roles'], $teamKey) &&
            Schema::hasColumn($tableNames['model_has_roles'], $teamKey) &&
            Schema::hasColumn($tableNames['model_has_permissions'], $teamKey);
        if ($alreadyTeamsReady) {
            DB::table($tableNames['roles'])->whereNull($teamKey)->update([$teamKey => 1]);
            return;
        }

        Schema::table($tableNames['roles'], function (Blueprint $table) use ($teamKey, $tableNames) {
            if (!Schema::hasColumn($tableNames['roles'], $teamKey)) {
                $table->unsignedBigInteger($teamKey)->nullable()->after('id');
                $table->index($teamKey, 'roles_team_foreign_key_index');
            }
        });

        DB::table($tableNames['roles'])->whereNull($teamKey)->update([$teamKey => 1]);

        Schema::table($tableNames['roles'], function (Blueprint $table) use ($teamKey) {
            $table->dropUnique(['name', 'guard_name']);
            $table->unique([$teamKey, 'name', 'guard_name']);
        });

        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($teamKey, $tableNames) {
            if (!Schema::hasColumn($tableNames['model_has_roles'], $teamKey)) {
                $table->unsignedBigInteger($teamKey)->default(1)->after(PermissionRegistrar::$pivotRole);
                $table->index($teamKey, 'model_has_roles_team_foreign_key_index');
            }
        });

        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($teamKey) {
            $table->dropPrimary('model_has_roles_role_model_type_primary');
            $table->primary([$teamKey, PermissionRegistrar::$pivotRole, config('permission.column_names.model_morph_key'), 'model_type'], 'model_has_roles_role_model_type_primary');
        });

        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($teamKey, $tableNames) {
            if (!Schema::hasColumn($tableNames['model_has_permissions'], $teamKey)) {
                $table->unsignedBigInteger($teamKey)->default(1)->after(PermissionRegistrar::$pivotPermission);
                $table->index($teamKey, 'model_has_permissions_team_foreign_key_index');
            }
        });

        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($teamKey) {
            $table->dropPrimary('model_has_permissions_permission_model_type_primary');
            $table->primary([$teamKey, PermissionRegistrar::$pivotPermission, config('permission.column_names.model_morph_key'), 'model_type'], 'model_has_permissions_permission_model_type_primary');
        });

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teamKey = (string) ($columnNames['team_foreign_key'] ?? 'tenant_id');

        if (!Schema::hasColumn($tableNames['roles'], $teamKey)) {
            return;
        }

        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($teamKey) {
            $table->dropPrimary('model_has_roles_role_model_type_primary');
            $table->primary([PermissionRegistrar::$pivotRole, config('permission.column_names.model_morph_key'), 'model_type'], 'model_has_roles_role_model_type_primary');
            $table->dropIndex('model_has_roles_team_foreign_key_index');
            $table->dropColumn($teamKey);
        });

        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($teamKey) {
            $table->dropPrimary('model_has_permissions_permission_model_type_primary');
            $table->primary([PermissionRegistrar::$pivotPermission, config('permission.column_names.model_morph_key'), 'model_type'], 'model_has_permissions_permission_model_type_primary');
            $table->dropIndex('model_has_permissions_team_foreign_key_index');
            $table->dropColumn($teamKey);
        });

        Schema::table($tableNames['roles'], function (Blueprint $table) use ($teamKey) {
            $table->dropUnique([$teamKey, 'name', 'guard_name']);
            $table->unique(['name', 'guard_name']);
            $table->dropIndex('roles_team_foreign_key_index');
            $table->dropColumn($teamKey);
        });
    }
};

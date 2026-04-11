<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $modelMorphKey = config('permission.column_names.model_morph_key', 'model_id');

        $roleAssignments = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', User::class)
            ->select("model_has_roles.{$modelMorphKey} as model_id", DB::raw('MIN(roles.name) as role_name'))
            ->groupBy("model_has_roles.{$modelMorphKey}")
            ->get();

        foreach ($roleAssignments as $assignment) {
            DB::table('users')
                ->where('id', $assignment->model_id)
                ->update(['role' => $assignment->role_name]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')
            ->whereNotNull('role')
            ->update(['role' => 'staff']);
    }
};

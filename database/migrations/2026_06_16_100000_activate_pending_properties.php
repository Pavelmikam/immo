<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Workflow change: admin approval removed, proprietaires publish directly.
        // All previously-pending properties (awaiting admin) become active now.
        DB::table('properties')
            ->where('status', 'pending')
            ->whereNull('deleted_at')
            ->update([
                'status'       => 'active',
                'published_at' => DB::raw('COALESCE(published_at, NOW())'),
                'updated_at'   => now(),
            ]);

        // Rejected properties are uneditable under the new policy (only draft allowed).
        // Move them back to draft so owners can re-publish.
        DB::table('properties')
            ->where('status', 'rejected')
            ->whereNull('deleted_at')
            ->update([
                'status'           => 'draft',
                'rejection_reason' => null,
                'updated_at'       => now(),
            ]);
    }

    public function down(): void
    {
        // Irreversible data change — no meaningful rollback
    }
};

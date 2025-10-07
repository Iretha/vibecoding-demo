<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_roles', function (Blueprint $table) {
            $table->integer('display_order')->nullable()->after('role_id');
            
            // Add composite index for efficient sorting
            $table->index(['user_id', 'display_order'], 'idx_user_roles_user_display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_roles', function (Blueprint $table) {
            $table->dropIndex('idx_user_roles_user_display_order');
            $table->dropColumn('display_order');
        });
    }
};

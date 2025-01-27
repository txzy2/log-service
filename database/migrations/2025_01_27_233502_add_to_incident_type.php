<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('incident_type', function (Blueprint $table) {
            $table->dropColumn('can_send');
            $table->enum('alias', ['manager', 'client'])->default('manager');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incident_type', function (Blueprint $table) {
            $table->dropColumn('alias');
            $table->string('can_send', 1)->enum('Y', 'N')->default('Y');
        });
    }
};

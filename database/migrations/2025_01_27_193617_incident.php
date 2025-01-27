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
        Schema::create('send_template', function (Blueprint $table) {
            $table->id();
            $table->string('to');
            $table->string('subject');
            $table->string('template');
        });

        Schema::create('incident_type', function (Blueprint $table) {
            $table->id();
            $table->string('type_name', 50);
            $table->string('can_send', 1)->default('N');
            $table->unsignedBigInteger('send_template_id');
            $table->string('code', 50);

            $table->foreign('send_template_id')->references('id')->on('send_template');
        });

        Schema::create('incident', function (Blueprint $table) {
            $table->id();
            $table->string('incident_object');
            $table->string('incident_text');
            $table->unsignedBigInteger('incident_type_id');
            $table->string('source');
            $table->date('date');
            $table->integer('count');

            $table->foreign('incident_type_id')->references('id')->on('incident_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incident');
        Schema::dropIfExists('incident_type');
        Schema::dropIfExists('send_template');
    }
};
<?php

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
        Schema::create('send_template', function (Blueprint $table) {
            $table->id();
            $table->string('to');
            $table->string('subject');
            $table->text('template');
        });

        Schema::create('incident_type', function (Blueprint $table) {
            $table->id();
            $table->string('type_name', 50);
            $table->enum('alias', ['email', 'push'])->nullable();
            $table->unsignedBigInteger('send_template_id')->nullable();
            $table->string('code', 50);

            $table->foreign('send_template_id')->references('id')->on('send_template');
        });

        Schema::create('incident', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->unsignedBigInteger('incident_type_id')->nullable();
            $table->string('level');
            $table->string('domain');
            $table->string('service');
            $table->string('message');
            $table->string('class');
            $table->string('function');
            $table->string('action');
            $table->string('file');
            $table->json('additionalFields')->nullable();
            $table->timestamp("date");
            $table->integer('count');
            $table->string('hash_sum')->nullable()->unique();
            $table->enum('demo', ['Y', 'N'])->default('N');
            $table->timestamps();

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

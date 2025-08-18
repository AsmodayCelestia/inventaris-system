<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('inventory_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventories')->onDelete('cascade');
            $table->date('inspection_date');
            $table->text('issue_found')->nullable();
            $table->text('solution_taken')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['planning', 'done'])->default('planning');

            $table->decimal('cost', 15, 0)->nullable();   // <-- TAMBAH INI

            $table->string('photo_1')->nullable();
            $table->string('photo_2')->nullable();
            $table->string('photo_3')->nullable();

            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('odometer_reading')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('inventory_maintenances');
    }
};
// done
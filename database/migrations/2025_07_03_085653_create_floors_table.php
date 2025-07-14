<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('floors', function (Blueprint $table) {
            $table->id();
            $table->string('number'); // Ini yang tadi error
            $table->foreignId('unit_id')->constrained('location_units')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('floors');
    }
};

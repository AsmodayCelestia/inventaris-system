<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->string('inventory_number')->unique();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->enum('acquisition_source', ['Beli', 'Hibah', 'Bantuan', '-'])->default('-');
            $table->date('procurement_date');
            $table->decimal('purchase_price', 15, 2);
            $table->decimal('estimated_depreciation', 15, 2)->nullable();
            $table->enum('status', ['Ada', 'Rusak', 'Perbaikan', 'Hilang', 'Dipinjam', '-'])->default('Ada');

            $table->foreignId('unit_id')->constrained('location_units')->onDelete('cascade');
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            
            $table->foreignId('floor_id')->nullable()->constrained('floors')->onDelete('set null');


            $table->date('expected_replacement')->nullable();
            $table->date('last_checked_at')->nullable();

            $table->foreignId('pj_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('inventories');
    }
};
// done
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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->string('inventory_number')->unique();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->enum('acquisition_source', ['Beli', 'Hibah', 'Bantuan', '-'])->default('-');
            $table->date('procurement_date');
            $table->decimal('purchase_price', 15, 2);
            $table->decimal('estimated_depreciation', 15, 2)->nullable();
            $table->enum('status', ['Ada', 'Rusak', 'Perbaikan', 'Hilang', 'Dipinjam', '-'])->default('Ada');

            // Tambahkan kolom image_path dan qr_code_path di sini
            $table->string('image_path')->nullable(); // Path gambar inventaris
            $table->string('qr_code_path')->nullable(); // Path QR Code inventaris

            // Lokasi
            $table->foreignId('unit_id')->constrained('location_units')->onDelete('cascade');
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            
            $table->date('expected_replacement')->nullable();
            $table->date('last_checked_at')->nullable();

            $table->foreignId('pj_id')->nullable()->constrained('users')->onDelete('set null');

            // Columns for Maintenance Scheduling
            $table->enum('maintenance_frequency_type', ['bulan', 'km', 'minggu', 'semester'])->nullable();
            $table->integer('maintenance_frequency_value')->nullable();
            $table->date('last_maintenance_at')->nullable();
            
            // Split next_due_at into two for flexibility
            $table->date('next_due_date')->nullable(); // For date-based maintenance
            $table->integer('next_due_km')->nullable(); // For KM-based maintenance

            $table->integer('last_odometer_reading')->nullable(); // To store the last odometer reading for KM-based items

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
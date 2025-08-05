<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InventoryMaintenance;
use App\Models\Inventory;

class ResetMaintenanceCommand extends Command
{
    protected $signature = 'maintenance:reset';
    protected $description = 'Menghapus semua data maintenance dan mereset status maintenance di tabel inventories.';

    public function handle()
    {
        // Konfirmasi sebelum melanjutkan
        if (!$this->confirm('Apakah Anda yakin ingin mereset semua data maintenance? Tindakan ini tidak bisa dibatalkan.')) {
            $this->info('Operasi dibatalkan.');
            return Command::SUCCESS;
        }

        $this->info('Memulai reset data maintenance...');

        // 1. Mengosongkan tabel inventory_maintenance
        $this->info('Mengosongkan tabel inventory_maintenance...');
        InventoryMaintenance::truncate();
        $this->info('Tabel inventory_maintenance berhasil dikosongkan.');

        // 2. Mereset kolom-kolom maintenance di tabel inventories
        $this->info('Mereset kolom maintenance di tabel inventories...');
        Inventory::query()->update([
            'maintenance_frequency_type' => null,
            'maintenance_frequency_value' => null,
            'last_maintenance_at' => null,
            'next_due_date' => null,
            'next_due_km' => null,
            'last_odometer_reading' => null,
        ]);
        $this->info('Kolom maintenance di tabel inventories berhasil direset.');

        $this->info('Reset data maintenance selesai. Anda bisa mulai melakukan testing lagi.');

        return Command::SUCCESS;
    }
}
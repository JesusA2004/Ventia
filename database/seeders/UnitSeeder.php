<?php

namespace Database\Seeders;

use App\Enums\Status;
use App\Enums\UnitType;
use App\Models\Unit;
use Illuminate\Database\Seeder;

/**
 * System-wide units of measure (company_id null), available to every
 * company alongside whatever custom units they create for themselves.
 */
class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Pieza', 'symbol' => 'PZA', 'type' => UnitType::Piece, 'decimal_places' => 0, 'allows_fraction' => false],
            ['name' => 'Kilogramo', 'symbol' => 'KG', 'type' => UnitType::Weight, 'decimal_places' => 3, 'allows_fraction' => true],
            ['name' => 'Gramo', 'symbol' => 'G', 'type' => UnitType::Weight, 'decimal_places' => 0, 'allows_fraction' => false],
            ['name' => 'Litro', 'symbol' => 'L', 'type' => UnitType::Volume, 'decimal_places' => 3, 'allows_fraction' => true],
            ['name' => 'Mililitro', 'symbol' => 'ML', 'type' => UnitType::Volume, 'decimal_places' => 0, 'allows_fraction' => false],
            ['name' => 'Metro', 'symbol' => 'M', 'type' => UnitType::Length, 'decimal_places' => 2, 'allows_fraction' => true],
            ['name' => 'Caja', 'symbol' => 'CAJA', 'type' => UnitType::Package, 'decimal_places' => 0, 'allows_fraction' => false],
            ['name' => 'Paquete', 'symbol' => 'PAQ', 'type' => UnitType::Package, 'decimal_places' => 0, 'allows_fraction' => false],
            ['name' => 'Servicio', 'symbol' => 'SERV', 'type' => UnitType::Service, 'decimal_places' => 0, 'allows_fraction' => false],
        ];

        foreach ($units as $unit) {
            Unit::withoutGlobalScopes()->firstOrCreate(
                ['company_id' => null, 'symbol' => $unit['symbol']],
                [...$unit, 'status' => Status::Active],
            );
        }
    }
}

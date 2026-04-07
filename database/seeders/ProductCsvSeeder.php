<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductCsvSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/products.csv');

        if (!file_exists($path)) {
            $this->command->error("No se encontró el archivo: {$path}");
            return;
        }

        $file = fopen($path, 'r');

        if ($file === false) {
            $this->command->error("No se pudo abrir el archivo CSV.");
            return;
        }

        $header = fgetcsv($file);

        if (!$header) {
            $this->command->error("El CSV está vacío o no tiene encabezados.");
            fclose($file);
            return;
        }

        while (($row = fgetcsv($file)) !== false) {
            if (count($row) !== count($header)) {
                continue;
            }

            $data = array_combine($header, $row);

            Product::updateOrCreate(
                ['code' => trim($data['code'])],
                [
                    'name' => trim($data['name']),
                    'price' => (float) $data['price'],
                    'send_to_kitchen' => (int) $data['send_to_kitchen'],
                ]
            );
        }

        fclose($file);

        $this->command->info('Importación completada correctamente.');
    }
}
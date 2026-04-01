<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use App\Models\RoadSignType;
use App\Models\RoadSign;

class RoadSignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $typesJsonPath = resource_path('tests/road-sign-types.json');
        $dataJsonPath = resource_path('tests/road-sign-data.json');

        if (File::exists($typesJsonPath)) {
            $typesJson = File::get($typesJsonPath);
            $typesData = json_decode($typesJson, true);

            if (isset($typesData['data']['data'])) {
                foreach ($typesData['data']['data'] as $type) {
                    RoadSignType::updateOrCreate(
                        ['id' => $type['id']],
                        [
                            'name' => $type['name'],
                            'code' => $type['code'],
                            'image_url' => $type['image_url'],
                        ]
                    );
                }
            }
        }

        if (File::exists($dataJsonPath)) {
            $dataJson = File::get($dataJsonPath);
            $this->importSigns(json_decode($dataJson, true));
        }

        $signsDirectory = resource_path('tests/road-signs');
        if (File::isDirectory($signsDirectory)) {
            $files = File::files($signsDirectory);
            foreach ($files as $file) {
                if ($file->getExtension() === 'json') {
                    $json = File::get($file->getPathname());
                    $this->importSigns(json_decode($json, true));
                }
            }
        }
    }

    /**
     * Import signs from data array.
     */
    private function importSigns(?array $signsData): void
    {
        if (isset($signsData['data']['data'])) {
            foreach ($signsData['data']['data'] as $sign) {
                RoadSign::updateOrCreate(
                    ['id' => $sign['id']],
                    [
                        'road_sign_type_id' => $sign['road_sign_type_id'],
                        'name' => $sign['name'],
                        'format' => $sign['format'],
                        'code' => $sign['code'],
                        'status' => $sign['status'],
                        'image' => $sign['image'],
                        'description' => $sign['description'],
                        'content' => $sign['content'],
                        'order_column' => $sign['order'] ?? 0,
                    ]
                );
            }
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use App\Models\TrafficSignCategory;
use App\Models\TrafficSign;

class TrafficSignsImportSeeder extends Seeder
{
    public function run(): void
    {
        $typesJsonPath = resource_path('tests/road-sign-types.json');
        $dataJsonPath = resource_path('tests/road-sign-data.json');

        if (!File::exists($typesJsonPath) || !File::exists($dataJsonPath)) {
            return;
        }

        $typesData = json_decode(File::get($typesJsonPath), true);
        $signsData = json_decode(File::get($dataJsonPath), true);

        $categoryMap = []; // old_type_id -> new_category_id

        if (isset($typesData['data']['data'])) {
            foreach ($typesData['data']['data'] as $type) {
                $name = is_array($type['name']) ? ($type['name']['uz'] ?? $type['name']['ru'] ?? 'Noma\'lum') : $type['name'];
                if (!$name) continue;
                
                \Illuminate\Support\Facades\DB::table('traffic_sign_categories')->updateOrInsert(
                    ['name' => $name],
                    ['created_at' => now(), 'updated_at' => now()]
                );
                
                $category = \Illuminate\Support\Facades\DB::table('traffic_sign_categories')->where('name', $name)->first();
                $categoryMap[$type['id']] = $category->id;
            }
        }

        if (isset($signsData['data']['data'])) {
            foreach ($signsData['data']['data'] as $sign) {
                if (isset($categoryMap[$sign['road_sign_type_id']])) {
                    $title = is_array($sign['name']) ? ($sign['name']['uz'] ?? $sign['name']['ru'] ?? 'Noma\'lum') : $sign['name'];
                    if (!$title) continue;

                    \Illuminate\Support\Facades\DB::table('traffic_signs')->updateOrInsert(
                        [
                            'title' => $title,
                            'traffic_sign_category_id' => $categoryMap[$sign['road_sign_type_id']]
                        ],
                        [
                            'description' => is_array($sign['description']) ? ($sign['description']['uz'] ?? '') : ($sign['description'] ?? ''),
                            'image' => $sign['image'] ?? '',
                            'created_at' => now(),
                            'updated_at' => now()
                        ]
                    );
                }
            }
        }
        
        // Also import from individual JSON files in road-signs directory
        $signsDirectory = resource_path('tests/road-signs');
        if (File::isDirectory($signsDirectory)) {
            $files = File::files($signsDirectory);
            foreach ($files as $file) {
                if ($file->getExtension() === 'json') {
                    $json = File::get($file->getPathname());
                    $data = json_decode($json, true);
                    if (isset($data['data']['data'])) {
                        foreach ($data['data']['data'] as $sign) {
                            if (isset($categoryMap[$sign['road_sign_type_id']])) {
                                $title = is_array($sign['name']) ? ($sign['name']['uz'] ?? $sign['name']['ru'] ?? 'Noma\'lum') : $sign['name'];
                                if (!$title) continue;

                                \Illuminate\Support\Facades\DB::table('traffic_signs')->updateOrInsert(
                                    [
                                        'title' => $title,
                                        'traffic_sign_category_id' => $categoryMap[$sign['road_sign_type_id']]
                                    ],
                                    [
                                        'description' => is_array($sign['description']) ? ($sign['description']['uz'] ?? '') : ($sign['description'] ?? ''),
                                        'image' => $sign['image'] ?? '',
                                        'created_at' => now(),
                                        'updated_at' => now()
                                    ]
                                );
                            }
                        }
                    }
                }
            }
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Transformation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class TransformationSeeder extends Seeder
{
    private const PROFILES = [
        ['كريم محمود', 28],
        ['أحمد عادل', 31],
        ['مصطفى حسن', 26],
        ['عمر طارق', 24],
        ['يوسف نبيل', 29],
        ['محمد سامي', 34],
        ['إسلام فتحي', 27],
        ['زياد هشام', 23],
        ['حسام الدين', 36],
        ['شريف علاء', 32],
        ['محمود رضا', 30],
        ['خالد أشرف', 35],
        ['طارق فوزي', 38],
        ['علي بدر', 25],
        ['مازن خالد', 23],
        ['سيف إيهاب', 29],
        ['رامي سليمان', 33],
        ['أدهم وائل', 27],
        ['مؤمن صلاح', 31],
        ['باسم شوقي', 34],
        ['حسن جمال', 28],
        ['وليد عاطف', 37],
        ['مروان مجدي', 24],
        ['هيثم إبراهيم', 33],
        ['نادر مصطفى', 29],
    ];

    public function run(): void
    {
        $assetsDir = database_path('seeders/assets/transformations');
        $files = glob($assetsDir.'/*.webp') ?: [];
        sort($files);

        $disk = Storage::disk('public');

        foreach (array_values($files) as $index => $file) {
            $filename = basename($file);
            $path = 'transformations/'.$filename;

            if (! $disk->exists($path)) {
                $disk->put($path, file_get_contents($file));
            }

            [$name, $age] = self::PROFILES[$index % count(self::PROFILES)];

            $item = Transformation::withTrashed()->firstOrCreate(
                ['image_path' => $path],
                [
                    'title' => $name,
                    'age' => $age,
                    'subtitle' => null,
                    'sort' => $index + 1,
                    'is_active' => true,
                ]
            );

            // Backfill temporary Egyptian name & age if the seeded row has none yet
            if ($item->title === null && $item->age === null) {
                $item->update(['title' => $name, 'age' => $age]);
            }
        }
    }
}

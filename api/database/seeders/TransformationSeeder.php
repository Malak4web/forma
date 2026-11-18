<?php

namespace Database\Seeders;

use App\Models\Transformation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class TransformationSeeder extends Seeder
{
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

            Transformation::withTrashed()->firstOrCreate(
                ['image_path' => $path],
                [
                    'title' => null,
                    'subtitle' => null,
                    'sort' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}

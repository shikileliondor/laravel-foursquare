<?php

namespace Database\Seeders;

use App\Models\Media;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MediaSeeder extends Seeder
{
    public function run(): void
    {
        $disk = Storage::disk((string) config('media.disk', 'public'));
        $folder = trim((string) config('media.folder', 'media'), '/').'/seed';

        foreach ([
            'a.jpg',
            'asok.jpg',
            'athudj.jpg',
            'athur.jpg',
            'autel.jpg',
            'convention.jpg',
            'conventiona.jpg',
            'morasha.jpg',
        ] as $filename) {
            $source = public_path('assets/images/'.$filename);
            $bytes = is_file($source) ? file_get_contents($source) : false;
            $image = $bytes !== false ? getimagesize($source) : false;

            if ($bytes === false || $image === false) {
                throw new RuntimeException("Image de démonstration introuvable ou invalide : {$filename}");
            }

            $path = $folder.'/'.$filename;

            if (! $disk->put($path, $bytes)) {
                throw new RuntimeException("Impossible d'enregistrer l'image de démonstration : {$filename}");
            }

            Media::updateOrCreate(['path' => $path], [
                'original_name' => $filename,
                'stored_name' => $filename,
                'mime_type' => $image['mime'],
                'file_size' => strlen($bytes),
                'width' => $image[0],
                'height' => $image[1],
            ]);
        }
    }
}

<?php

use App\Models\Media;

function storedImage(): Media
{
    return Media::create([
        'original_name' => 'affiche.png',
        'stored_name' => '67a8de79-919b-42f6-aed0-d24f176e0ecf.png',
        'path' => 'media/67a8de79-919b-42f6-aed0-d24f176e0ecf.png',
        'mime_type' => 'image/png',
        'file_size' => 70,
    ]);
}

it('serves images through PHP until the storage link is declared', function () {
    config(['media.direct_urls' => false]);

    expect(storedImage()->url())->toBe(route('v1.media.file', ['media' => Media::first()->id]));
});

it('hands out the static file URL once the storage link exists', function () {
    config([
        'media.direct_urls' => true,
        'filesystems.disks.public.url' => 'https://api.example.test/storage',
    ]);

    expect(storedImage()->url())
        ->toBe('https://api.example.test/storage/media/67a8de79-919b-42f6-aed0-d24f176e0ecf.png');
});

it('keeps the PHP route for disks Apache cannot serve', function () {
    config(['media.direct_urls' => true, 'media.disk' => 'local']);

    expect(storedImage()->url())->toContain('/api/v1/media/');
});

<?php

namespace Fachran\FileManager\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Fachran\FileManager\Models\File;

class FileFactory extends Factory
{
    protected $model = File::class;

    public function definition(): array
    {
        $extension = $this->faker->randomElement(['pdf', 'jpg', 'png', 'docx']);
        $mimeMap   = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'png'  => 'image/png',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        return [
            'folder_id'      => null,
            'owner_id'       => 1,
            'name'           => $this->faker->slug(3).'.'.$extension,
            'original_name'  => $this->faker->words(3, true).'.'.$extension,
            'storage_path'   => 'filemanager/2025/01/'.$this->faker->uuid().'.'.$extension,
            'disk'           => 'local',
            'mime_type'      => $mimeMap[$extension],
            'extension'      => $extension,
            'size'           => $this->faker->numberBetween(1024, 10485760),
            'checksum'       => $this->faker->sha256(),
            'thumbnail_path' => null,
            'metadata'       => null,
            'is_public'      => false,
            'download_count' => 0,
            'created_by'     => 1,
        ];
    }
}

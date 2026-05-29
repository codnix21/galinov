<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Копирует демо-фото из database/seeders/media/ в storage/app/public.
 * В БД — только относительные пути; у каждого объявления свой набор файлов.
 */
final class DemoImageFactory
{
    private const MEDIA_DIR = 'database/seeders/media';

    /** @var array<string, list<string>> Только фото своего типа — без пересечений */
    private const PROPERTY_FILES = [
        'apartment' => [
            'properties/apartment-01.jpg',
            'properties/apartment-02.jpg',
            'properties/apartment-03.jpg',
            'properties/apartment-04.jpg',
            'properties/apartment-05.jpg',
            'properties/apartment-06.jpg',
            'properties/apartment-07.jpg',
            'properties/apartment-08.jpg',
            'properties/apartment-09.jpg',
            'properties/apartment-10.jpg',
            'properties/interior-01.jpg',
            'properties/interior-02.jpg',
            'properties/interior-03.jpg',
            'properties/interior-04.jpg',
        ],
        'house' => [
            'properties/house-01.jpg',
            'properties/house-02.jpg',
            'properties/house-03.jpg',
            'properties/house-04.jpg',
            'properties/house-05.jpg',
            'properties/house-06.jpg',
            'properties/house-07.jpg',
            'properties/house-08.jpg',
            'properties/interior-03.jpg',
            'properties/interior-04.jpg',
        ],
        'commercial' => [
            'properties/commercial-01.jpg',
            'properties/commercial-02.jpg',
            'properties/commercial-03.jpg',
            'properties/commercial-04.jpg',
            'properties/commercial-05.jpg',
            'properties/commercial-06.jpg',
        ],
        'land' => [
            'properties/land-01.jpg',
            'properties/land-02.jpg',
            'properties/land-03.jpg',
            'properties/land-04.jpg',
            'properties/land-05.jpg',
            'properties/land-06.jpg',
        ],
    ];

    /** @var list<string> */
    private const AVATAR_FILES = [
        'avatars/01.jpg',
        'avatars/02.jpg',
        'avatars/03.jpg',
        'avatars/04.jpg',
        'avatars/05.jpg',
        'avatars/06.jpg',
        'avatars/07.jpg',
        'avatars/08.jpg',
    ];

    /**
     * Фото для объявления: разный источник и уникальный файл в storage на каждую запись.
     */
    public static function propertyPhoto(string $type, int $propertyId, int $order): string
    {
        $files = self::PROPERTY_FILES[$type] ?? self::PROPERTY_FILES['apartment'];
        $poolSize = count($files);
        $variant = self::variantIndex($propertyId, $order, $poolSize);

        return self::publishUnique($files[$variant], $propertyId, $order);
    }

    public static function avatar(int $variant): string
    {
        $relativeSource = self::AVATAR_FILES[$variant % count(self::AVATAR_FILES)];

        return self::publish($relativeSource, 'avatars');
    }

    /** Разброс индексов: у соседних объявлений разные кадры в галерее */
    private static function variantIndex(int $propertyId, int $order, int $poolSize): int
    {
        $hash = crc32("{$propertyId}:{$order}");

        return (int) ($hash % $poolSize);
    }

    /** @return list<string> */
    public static function requiredSources(): array
    {
        $paths = self::AVATAR_FILES;
        foreach (self::PROPERTY_FILES as $group) {
            foreach ($group as $file) {
                $paths[] = $file;
            }
        }

        return array_values(array_unique($paths));
    }

    public static function assertMediaPackInstalled(): void
    {
        $missing = [];
        foreach (self::requiredSources() as $rel) {
            if (!is_file(self::sourcePath($rel))) {
                $missing[] = $rel;
            }
        }

        if ($missing !== []) {
            throw new RuntimeException(
                'Не найдены демо-фото в ' . self::MEDIA_DIR . '. Убедитесь, что каталог database/seeders/media/ включён в поставку проекта.' . "\n"
                . 'Отсутствуют: ' . implode(', ', $missing)
            );
        }
    }

    public static function poolSize(string $type): int
    {
        return count(self::PROPERTY_FILES[$type] ?? self::PROPERTY_FILES['apartment']);
    }

    private static function publishUnique(string $relativeSource, int $propertyId, int $order): string
    {
        $source = self::sourcePath($relativeSource);
        if (!is_file($source)) {
            throw new RuntimeException("Демо-файл не найден: {$relativeSource}. Проверьте каталог database/seeders/media/.");
        }

        $stem = pathinfo($relativeSource, PATHINFO_FILENAME);
        $ext = pathinfo($relativeSource, PATHINFO_EXTENSION) ?: 'jpg';
        $destRelative = sprintf('properties/n%d_%d_%s.%s', $propertyId, $order, $stem, $ext);

        $disk = Storage::disk('public');
        $disk->makeDirectory('properties');

        File::copy($source, $disk->path($destRelative));

        return $destRelative;
    }

    private static function publish(string $relativeSource, string $targetFolder): string
    {
        $source = self::sourcePath($relativeSource);
        if (!is_file($source)) {
            throw new RuntimeException("Демо-файл не найден: {$relativeSource}. Проверьте каталог database/seeders/media/.");
        }

        $basename = pathinfo($relativeSource, PATHINFO_FILENAME);
        $ext = pathinfo($relativeSource, PATHINFO_EXTENSION) ?: 'jpg';
        $destRelative = $targetFolder . '/' . $basename . '.' . $ext;

        $disk = Storage::disk('public');
        $disk->makeDirectory($targetFolder);

        File::copy($source, $disk->path($destRelative));

        return $destRelative;
    }

    private static function sourcePath(string $relative): string
    {
        return base_path(self::MEDIA_DIR . '/' . str_replace('/', DIRECTORY_SEPARATOR, $relative));
    }
}

<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final class MaterialProcurementRequestFileStorage
{
    private const MAX_FILE_SIZE = 20 * 1024 * 1024;

    public function __construct(
        private readonly string $uploadDirectory,
    ) {
    }

    public function store(UploadedFile $file, int $requestId): array
    {
        if (!$file->isValid()) {
            throw new \RuntimeException('Не удалось загрузить файл.');
        }

        $size = $file->getSize();

        if (!is_int($size) || $size <= 0) {
            throw new \RuntimeException('Нельзя загрузить пустой файл.');
        }

        if ($size > self::MAX_FILE_SIZE) {
            throw new \RuntimeException('Размер одного файла не должен превышать 20 МБ.');
        }

        $originalName = trim(basename(str_replace('\\', '/', $file->getClientOriginalName())));

        if ($originalName === '' || mb_strlen($originalName) > 255) {
            throw new \RuntimeException('Некорректное имя файла.');
        }

        $extension = mb_strtolower($file->getClientOriginalExtension()) ?: 'bin';
        $relativeDirectory = (string) $requestId;
        $targetDirectory = rtrim($this->uploadDirectory, '/').'/'.$relativeDirectory;

        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0770, true) && !is_dir($targetDirectory)) {
            throw new \RuntimeException('Не удалось подготовить каталог для загрузки.');
        }

        $fileName = bin2hex(random_bytes(24)).'.'.$extension;
        $file->move($targetDirectory, $fileName);

        return [
            'originalName' => $originalName,
            'storedName' => $relativeDirectory.'/'.$fileName,
            'mimeType' => $file->getClientMimeType() ?: 'application/octet-stream',
            'size' => $size,
        ];
    }

    public function path(string $storedName): string
    {
        return rtrim($this->uploadDirectory, '/').'/'.ltrim($storedName, '/');
    }

    public function delete(string $storedName): void
    {
        $path = $this->path($storedName);

        if (is_file($path)) {
            @unlink($path);
        }
    }
}

<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
    public function __construct(
        private readonly string $targetDirectory,
        private readonly SluggerInterface $slugger,
        private readonly Filesystem $filesystem,
    ) {
    }

    public function upload(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), \PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename)->lower();
        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $fileName = sprintf('%s-%s.%s', $safeFilename, uniqid('', true), $extension);

        $this->filesystem->mkdir($this->targetDirectory);
        $file->move($this->targetDirectory, $fileName);

        return $fileName;
    }

    public function remove(?string $fileName): void
    {
        if (!$fileName) {
            return;
        }

        $this->filesystem->remove($this->targetDirectory.'\\'.$fileName);
    }
}

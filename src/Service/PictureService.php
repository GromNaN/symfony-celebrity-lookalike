<?php

declare(strict_types=1);

namespace App\Service;

use App\Document\File;
use App\Document\Picture;
use Doctrine\ODM\MongoDB\DocumentManager;

use function file_get_contents;
use function rand;
use function uniqid;

class PictureService
{
    public function __construct(private DocumentManager $dm)
    {
    }

    public function storePicture(string $filePath, string $originalName): Picture
    {
        $file = $this->dm->getRepository(File::class)->uploadFromFile($filePath, $originalName);

        // Assign a mock ID for testing purposes if not already set
        if (! $file->id) {
            $file->id = uniqid();
        }

        $this->dm->persist($file);

        // Generate description and embeddings
        $fileContent = file_get_contents($filePath);
        $imageData = $fileContent; // Placeholder for resizing logic
        [$description, $embeddings] = $this->generateDescriptionAndEmbeddings($imageData);

        // Create a new Picture document
        $picture = new Picture();
        $picture->file = $file;
        $picture->resizedImage = $imageData;
        $picture->description = $description;
        $picture->embeddings = $embeddings;

        $this->dm->persist($picture);
        $this->dm->flush();

        return $picture;
    }

    /**
     * @param string $imageData Image file
     *
     * @return array{0: string, 1: float[]}
     */
    public function generateDescriptionAndEmbeddings(string $imageData): array
    {
        // Placeholder logic for AI integration
        $description = 'Generated description';
        $embeddings = [0.1, 0.2, 0.3];

        return [$description, $embeddings];
    }

    /**
     * @param float[] $embeddings1
     * @param float[] $embeddings2
     */
    public function calculateSimilarity(array $embeddings1, array $embeddings2): float
    {
        // Placeholder logic for calculating similarity (e.g., cosine similarity)
        return rand(0, 100) / 100; // Random similarity for now
    }

    /** @return array{picture: Picture, similarity: float} */
    public function findSimilarPictures(Picture $picture, float $threshold = 0.8): array
    {
        $pictures = $this->dm->getRepository(Picture::class)->findAll();

        $matches = [];
        foreach ($pictures as $storedPicture) {
            $similarity = $this->calculateSimilarity($picture->embeddings, $storedPicture->embeddings);
            if ($similarity >= $threshold) {
                $matches[] = [
                    'picture' => $storedPicture,
                    'similarity' => $similarity,
                ];
            }
        }

        return $matches;
    }
}

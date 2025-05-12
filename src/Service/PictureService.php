<?php

namespace App\Service;

use App\Document\Picture;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\GridFS\Bucket;

class PictureService
{
    public function __construct(private Bucket $gridFsBucket, private DocumentManager $dm)
    {
    }

    public function storePicture(string $filePath, string $originalName): Picture
    {
        // Store the image in GridFS
        $stream = fopen($filePath, 'rb');
        $fileId = $this->gridFsBucket->uploadFromStream($originalName, $stream);
        fclose($stream);

        // Generate description and embeddings
        $imageData = file_get_contents($filePath); // Placeholder for resizing logic
        [$description, $embeddings] = $this->generateDescriptionAndEmbeddings($imageData);

        // Create a new Picture document
        $picture = new Picture();
        $picture->fileId = (string) $fileId;
        $picture->resizedImage = $imageData;
        $picture->description = $description;
        $picture->embeddings = $embeddings;

        $this->dm->persist($picture);
        $this->dm->flush();

        return $picture;
    }

    public function generateDescriptionAndEmbeddings(string $imageData): array
    {
        // Placeholder logic for AI integration
        $description = 'Generated description';
        $embeddings = [0.1, 0.2, 0.3];

        return [$description, $embeddings];
    }

    public function calculateSimilarity(array $embeddings1, array $embeddings2): float
    {
        // Placeholder logic for calculating similarity (e.g., cosine similarity)
        return rand(0, 100) / 100; // Random similarity for now
    }

    public function findSimilarPictures(Picture $picture, float $threshold = 0.8): array
    {
        $pictures = $this->dm->getRepository(Picture::class)->findAll();

        $matches = [];
        foreach ($pictures as $storedPicture) {
            $similarity = $this->calculateSimilarity($picture->embeddings, $storedPicture->embeddings);
            if ($similarity >= $threshold) {
                $matches[] = [
                    'id' => $storedPicture->id,
                    'description' => $storedPicture->description,
                    'similarity' => $similarity,
                ];
            }
        }

        return $matches;
    }
}
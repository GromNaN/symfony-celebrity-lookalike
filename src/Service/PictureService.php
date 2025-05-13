<?php

declare(strict_types=1);

namespace App\Service;

use App\Document\Face;
use App\Document\Picture;
use Doctrine\ODM\MongoDB\DocumentManager;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Format;

use function rand;
use function uniqid;

class PictureService
{
    public function __construct(private DocumentManager $dm)
    {
    }

    public function storePicture(string $filePath, string $originalFileName, string $name = ''): Face
    {
        $file = $this->dm->getRepository(Picture::class)->uploadFromFile($filePath, $originalFileName);
        assert($file instanceof Picture);

        // Assign a mock ID for testing purposes if not already set
        if (! $file->id) {
            $file->id = uniqid();
        }

        $this->dm->persist($file);

        [$description, $embeddings] = $this->generateDescriptionAndEmbeddings($filePath);

        // Create a new Face document
        $face = new Face();
        $face->name = $name;
        $face->file = $file;
        $face->resizedImage = $this->getResizedImage($filePath);
        $face->description = $description;
        $face->embeddings = $embeddings;

        $this->dm->persist($face);
        $this->dm->flush();

        return $face;
    }

    /**
     * @param string $imageData Image file
     *
     * @return array{0: string, 1: float[]}
     */
    public function generateDescriptionAndEmbeddings(string $filePath): array
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

    /** @return array{picture: Face, similarity: float} */
    public function findSimilarPictures(Face $picture, float $threshold = 0.8): array
    {
        $pictures = $this->dm->getRepository(Face::class)->findAll();

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

    private function getResizedImage(string $filePath): string
    {
        $image = new Imagine()->open($filePath);

        return $image->thumbnail(new Box(400, 400))->get(Format::ID_PNG);
    }
}

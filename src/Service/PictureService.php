<?php

declare(strict_types=1);

namespace App\Service;

use App\Document\Face;
use App\Document\Picture;
use App\Document\VectorSearchResult;
use Doctrine\ODM\MongoDB\DocumentManager;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Format;
use Imagine\Image\ImageInterface;

use function rand;
use function uniqid;

class PictureService
{
    public function __construct(
        private DocumentManager $dm,
        private VoyageAI $voyageAI,
    ) {
    }

    public function storePicture(string $filePath, string $originalFileName, string $name = ''): Face
    {
        $face = $this->checkForContributor($name);
        if ($face) {
            return $face;
        }

        $file = $this->dm->getRepository(Picture::class)->uploadFromFile($filePath, $originalFileName);
        assert($file instanceof Picture);

        // Assign a mock ID for testing purposes if not already set
        if (! $file->id) {
            $file->id = uniqid();
        }

        $this->dm->persist($file);

        $image = new Imagine()->open($filePath);

        // Create a new Face document
        $face = new Face();
        $face->name = $name;
        $face->file = $file;
        $face->resizedImage = $this->getResizedImage($image);
        $face->embeddings = $this->voyageAI->generateEmbeddings($image->get(Format::ID_PNG));
        $face->description = '';

        $this->dm->persist($face);
        $this->dm->flush();

        return $face;
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
    public function findSimilarPictures(Face $face, float $threshold = 0.8): array
    {
        $faces = $this->dm->getRepository(Face::class)
            ->createAggregationBuilder()
            ->hydrate(VectorSearchResult::class)
            ->sample(5)
            ->project()
                ->field('_id')->expression(0)
                ->field('face')->expression('$$ROOT')
                ->field('similarity')->literal(rand(0, 100) / 100)
            ->getAggregation()
            ->execute();

        return $faces->toArray();
    }

    private function checkForContributor(string $name): ?Face
    {
        return $this->dm->getRepository(Face::class)->findOneBy(['name' => $name]);
    }

    private function getResizedImage(ImageInterface $image): string
    {
        return $image
            ->thumbnail(new Box(400, 400))
            ->get(Format::ID_PNG);
    }
}

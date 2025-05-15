<?php

declare(strict_types=1);

namespace App\Service;

use App\Doctrine\ODM\MongoDB\Aggregation\VectorSearchStage;
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
        private OpenAI $openAI,
        private VoyageAI $voyageAI,
    ) {
    }

    /** @return array{0: string, 1: list<float>} */
    public function generateDescriptionAndEmbeddings(string $imageData): array
    {
        $description = $this->openAI->generateDescription($imageData);
        $embeddings = $this->voyageAI->generateTextEmbeddings($description);

        return [$description, $embeddings];
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
        [$face->description, $face->embeddings] = $this->generateDescriptionAndEmbeddings($image->get(Format::ID_PNG));

        $this->dm->persist($face);
        $this->dm->flush();

        return $face;
    }

    /** @return list<VectorSearchResult> */
    public function findSimilarPictures(Face $face, int $limit = 10, float $threshold = 0.8): array
    {
        $builder = $this->dm->getRepository(Face::class)
            ->createAggregationBuilder()
            ->hydrate(VectorSearchResult::class);

        $builder
            ->addStage(new VectorSearchStage($builder))
                ->index('faces')
                ->path('embeddings')
                ->numCandidates($limit * 20)
                ->queryVector($face->embeddings)
                ->limit($limit)
            ->project()
                ->field('_id')->expression(0)
                ->field('face')->expression('$$ROOT')
                ->field('score')->meta('vectorSearchScore');
        $faces = $builder
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

<?php

declare(strict_types=1);

namespace App\Command;

use App\Document\Face;
use App\Document\VectorSearchResult;
use App\Service\PictureService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Iterator\HydratingIterator;
use Imagine\Gd\Imagine;
use MongoDB\Builder\Expression;
use MongoDB\Builder\Pipeline;
use MongoDB\Builder\Stage;
use MongoDB\Driver\CursorInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:search',
    description: 'Search for a contributor based on a picture',
)]
class SearchCommand
{
    public function __construct(
        private PictureService $pictureService,
        private DocumentManager $dm,
    ) {
    }

    public function __invoke(
        #[Argument]
        string $pathToImage,
        OutputInterface $output,
        #[Argument]
        int $limit = 3,
    ): int {
        $output->writeln(sprintf('Searching for contributors similar to the file in: %s', $pathToImage));

        $image = new Imagine()->open($pathToImage);

        [$description, $embeddings] = $this->pictureService->generateDescriptionAndEmbeddings($image->get('png'));

        $output->writeln(sprintf('Generated description: %s', $description));

        $results = $this->dm->getDocumentCollection(Face::class)
            ->aggregate($this->getSearchPipeline($embeddings, $limit));

        foreach ($this->prepareIterator($results) as $result) {
            assert($result instanceof VectorSearchResult);

            $output->writeln(sprintf('Found contributor: %s; score: %.5f', $result->face->name, $result->score));
        }

        return Command::SUCCESS;
    }

    /** @param list<float> $embeddings */
    private function getSearchPipeline(array $embeddings, int $limit): Pipeline
    {
        return new Pipeline(
            Stage::vectorSearch(
                index: 'faces',
                limit: $limit,
                path: 'embeddings',
                queryVector: $embeddings,
                numCandidates: $limit * 20,
            ),
            Stage::addFields(
                face: Expression::variable('ROOT'),
                score: Expression::meta('vectorSearchScore'),
            ),
        );
    }

    private function prepareIterator(CursorInterface $cursor): HydratingIterator
    {
        return new HydratingIterator($cursor, $this->dm->getUnitOfWork(), $this->dm->getClassMetadata(VectorSearchResult::class));
    }
}

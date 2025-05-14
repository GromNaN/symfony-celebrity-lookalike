<?php

declare(strict_types=1);

namespace App\Command;

use App\Document\Face;
use App\Service\VoyageAI;
use App\VoyageAi\InputType;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Iterator\HydratingIterator;
use Imagine\Gd\Imagine;
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
        private VoyageAI $voyageAI,
        private DocumentManager $dm,
    ) {
    }

    public function __invoke(
        #[Argument]
        string $pathToImage,
        OutputInterface $output,
    ): int {
        $output->writeln(sprintf('Searching for contributors similar to the file in: %s', $pathToImage));

        $image = new Imagine()->open($pathToImage);

        $embeddings = $this->voyageAI->generateEmbeddings($image->get('png'), InputType::Query);

        $results = $this->dm->getDocumentCollection(Face::class)
            ->aggregate($this->getSearchPipeline($embeddings));

        foreach ($this->prepareIterator($results) as $face) {
            assert($face instanceof Face);

            $output->writeln(sprintf('Found contributor: %s', $face->name));
        }

        return Command::SUCCESS;
    }

    private function getSearchPipeline(array $embeddings): Pipeline
    {
        return new Pipeline(
            Stage::vectorSearch(
                index: 'faces',
                limit: 3,
                path: 'embeddings',
                queryVector: $embeddings,
                numCandidates: 3,
            ),
        );
    }

    private function prepareIterator(CursorInterface $cursor): HydratingIterator
    {
        return new HydratingIterator($cursor, $this->dm->getUnitOfWork(), $this->dm->getClassMetadata(Face::class));
    }
}

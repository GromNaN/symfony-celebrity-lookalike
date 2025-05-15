<?php

declare(strict_types=1);

namespace App\Command;

use App\Document\Face;
use App\Service\PictureService;
use Imagine\Gd\Imagine;
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
    public function __construct(private PictureService $pictureService)
    {
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

        $face = new Face();
        [$face->description, $face->embeddings] = $this->pictureService->generateDescriptionAndEmbeddings($image->get('png'));

        $output->writeln(sprintf('Generated description: %s', $face->description));

        $results = $this->pictureService->findSimilarPictures($face, $limit);

        foreach ($results as $result) {
            $output->writeln(sprintf('Found contributor: %s; score: %.5f', $result->face->name, $result->score));
        }

        return Command::SUCCESS;
    }
}

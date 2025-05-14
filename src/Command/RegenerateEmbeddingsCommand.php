<?php

declare(strict_types=1);

namespace App\Command;

use App\Document\Face;
use App\Document\Picture;
use App\Service\VoyageAI;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\Repository\GridFSRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

use function stream_get_contents;

#[AsCommand(
    name: 'app:regenerate-embeddings',
    description: 'Regenerate embeddings for all faces',
)]
readonly class RegenerateEmbeddingsCommand
{
    private DocumentRepository $faceRepository;
    private GridFSRepository $pictureRepository;

    public function __construct(
        private VoyageAI $voyageAI,
        private DocumentManager $dm,
    ) {
        $this->faceRepository = $this->dm->getRepository(Face::class);
        $this->pictureRepository = $this->dm->getRepository(Picture::class);
    }

    public function __invoke(OutputInterface $output): int
    {
        $output->writeln('Regenerating embeddings for all faces...');

        $count = $this->faceRepository->createQueryBuilder()
            ->count()
            ->getQuery()
            ->execute();

        $progressBar = new ProgressBar($output);
        $progressBar->setFormat(ProgressBar::FORMAT_VERY_VERBOSE);
        $progressBar->start($count);

        foreach ($this->faceRepository->findAll() as $face) {
            $pictureStream = $this->pictureRepository->openDownloadStream($face->file->id);

            try {
                $imageData = stream_get_contents($pictureStream);
            } finally {
                fclose($pictureStream);
            }

            $face->embeddings = $this->voyageAI->generateEmbeddings($imageData);
            $progressBar->advance();
        }

        $this->dm->flush();

        $progressBar->finish();

        return Command::SUCCESS;
    }
}

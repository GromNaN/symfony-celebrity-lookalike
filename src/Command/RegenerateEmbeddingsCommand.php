<?php

declare(strict_types=1);

namespace App\Command;

use App\Document\Face;
use App\Document\Picture;
use App\Service\PictureService;
use App\Service\VoyageAI;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\Repository\GridFSRepository;
use Imagine\Gd\Imagine;
use Imagine\Image\Format;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
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
        private PictureService $pictureService,
        private VoyageAI $voyageAI,
        private DocumentManager $dm,
    ) {
        $this->faceRepository = $this->dm->getRepository(Face::class);
        $this->pictureRepository = $this->dm->getRepository(Picture::class);
    }

    public function __invoke(
        OutputInterface $output,
        #[Argument(suggestedValues: ['image', 'description'])]
        string $field,
        #[Option]
        ?int $limit = null,
        #[Option]
        ?int $skip = null,
    ): int {
        $output->writeln('Regenerating embeddings for all faces...');

        $query = $this->faceRepository->createQueryBuilder();
        if ($limit !== null) {
            $query->limit($limit);
        }

        if ($skip !== null) {
            $query->limit($skip);
        }

        $query->field('descriptionEmbeddings')->exists(false);

        $count = (clone $query)->count()->getQuery()->execute();

        $progressBar = new ProgressBar($output);
        $progressBar->setFormat(ProgressBar::FORMAT_VERY_VERBOSE);
        $progressBar->start($count);

        try {
            foreach ($query->getQuery()->execute() as $face) {
                $pictureStream = $this->pictureRepository->openDownloadStream($face->file->id);

                try {
                    $imageData = stream_get_contents($pictureStream);
                } finally {
                    fclose($pictureStream);
                }

                $image = new Imagine()->load($imageData);

                switch ($field) {
                    case 'description':
                        [$face->description, $face->descriptionEmbeddings] = $this->pictureService->generateDescriptionAndEmbeddings($image->get(Format::ID_PNG));
                        break;
                    case 'image':
                        $face->imageEmbeddings = $this->voyageAI->generateImageEmbeddings($image->get(Format::ID_PNG));
                        break;
                    default:
                        $output->writeln(sprintf('Field name must be one of "image" or "description", "%s" given.', $field));
                }

                $progressBar->advance();
                $this->dm->flush();
            }
        } finally {
            $progressBar->finish();
        }

        return Command::SUCCESS;
    }
}

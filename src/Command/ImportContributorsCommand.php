<?php

declare(strict_types=1);

namespace App\Command;

use App\Document\Face;
use App\Service\PictureService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:import-contributors',
    description: 'Import contributors from a GitHub project and save them in the database.',
)]
class ImportContributorsCommand
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private DocumentManager $documentManager,
        private PictureService $pictureService
    ) {
    }

    public function __invoke(
        #[Argument]
        string $repository,
        OutputInterface $output,
    ): int {
        $output->writeln(sprintf('Fetching contributors for repository: %s', $repository));

        $url = sprintf('https://api.github.com/repos/%s/contributors', $repository);
        $response = $this->httpClient->request('GET', $url);
        $contributors = $response->toArray();

        foreach ($contributors as $contributor) {
            $output->writeln(sprintf('Processing contributor: %s', $contributor['login']));
            file_put_contents(
                $imageFile = tempnam(sys_get_temp_dir(), $contributor['login']),
                $this->httpClient->request('GET', $contributor['avatar_url'])->getContent(),
            );

            $this->pictureService->storePicture(
                $imageFile,
                $contributor['avatar_url'],
                $contributor['login'],
            );
        }

        $this->documentManager->flush();

        $output->writeln('Contributors imported successfully.');

        return Command::SUCCESS;
    }
}


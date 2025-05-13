<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\GitHub;
use App\Service\PictureService;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:import-contributors',
    description: 'Import contributors from a GitHub project and save them in the database.',
)]
class ImportContributorsCommand
{
    public function __construct(
        private GitHub $github,
        private HttpClientInterface $httpClient,
        private PictureService $pictureService,
    ) {
    }

    public function __invoke(
        #[Argument]
        string $repository,
        OutputInterface $output,
    ): int {
        $output->writeln(sprintf('Fetching contributors for repository: %s', $repository));

        ['count' => $count, 'iterator' => $iterator] = $this->github->getContributors($repository);

        $progressBar = new ProgressBar($output);
        $progressBar->start($count);
        $totalContributors = 0;

        foreach ($iterator as $contributors) {
            $totalContributors += count($contributors);
            $progressBar->advance(count($contributors));
            $this->importContributors($contributors, $output);
        }

        $progressBar->finish();

        $output->writeln(sprintf('%d contributors imported successfully.', $totalContributors));

        return Command::SUCCESS;
    }

    /** @param array<string, mixed> $contributors */
    private function importContributors(
        array $contributors,
        OutputInterface $output,
    ): void {
        // Create parallel HTTP requests to retrieve the avatars
        foreach ($contributors as &$contributor) {
            $contributor['http_request'] = $this->httpClient->request('GET', $contributor['avatar_url']);
        }

        unset($contributor);

        foreach ($contributors as $contributor) {
            file_put_contents(
                $imageFile = tempnam(sys_get_temp_dir(), $contributor['login']),
                $contributor['http_request']->getContent(),
            );

            $this->pictureService->storePicture(
                $imageFile,
                $contributor['avatar_url'],
                $contributor['login'],
            );
        }
    }
}

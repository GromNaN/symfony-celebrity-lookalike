<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\ImportContributorsCommand;
use App\Service\GitHub;
use App\Service\PictureService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class ImportContributorsCommandTest extends TestCase
{
    public function testInvokeImportsContributorsAndStoresPictures(): void
    {
        $contributors = [
            [
                'login' => 'alice',
                'avatar_url' => 'https://avatars.githubusercontent.com/u/1?v=4',
            ],
            [
                'login' => 'bob',
                'avatar_url' => 'https://avatars.githubusercontent.com/u/2?v=4',
            ],
        ];

        $github = $this->createMock(GitHub::class);
        $github->expects($this->once())
            ->method('getContributors')
            ->with('owner/repo')
            ->willReturn([
                'count' => 2,
                'iterator' => new \ArrayIterator([ $contributors ]),
            ]);

        $responses = [
            new MockResponse('alice_image_content'),
            new MockResponse('bob_image_content'),
        ];
        $httpClient = new MockHttpClient($responses);

        $pictureService = $this->createMock(PictureService::class);
        $pictureService->expects($this->exactly(2))
            ->method('storePicture')
            ->with(
                $this->isType('string'),
                $this->logicalOr(
                    $this->equalTo($contributors[0]['avatar_url']),
                    $this->equalTo($contributors[1]['avatar_url']),
                ),
                $this->logicalOr(
                    $this->equalTo($contributors[0]['login']),
                    $this->equalTo($contributors[1]['login']),
                ),
            );

        $command = new ImportContributorsCommand($github, $httpClient, $pictureService);

        $output = new BufferedOutput();
        $result = $command->__invoke('owner/repo', $output);

        $this->assertSame(0, $result);
        $display = $output->fetch();
        $this->assertStringContainsString('Fetching contributors for repository: owner/repo', $display);
        $this->assertStringContainsString('2 contributors imported successfully.', $display);
    }
}

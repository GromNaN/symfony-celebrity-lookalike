<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\GitHub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class GitHubTest extends TestCase
{
    public function testGetContributors(): void
    {
        $responses = [
            new MockResponse(json_encode([['login' => 'user1'], ['login' => 'user2']]), [
                'response_headers' => ['Link' => '<https://api.github.com/repos/owner/repo/contributors?per_page=10&page=2>; rel="next", <https://api.github.com/repos/owner/repo/contributors?per_page=10&page=3>; rel="last"'],
            ]),
            new MockResponse(json_encode([['login' => 'user3'], ['login' => 'user4']]), [
                'response_headers' => ['Link' => '<https://api.github.com/repos/owner/repo/contributors?per_page=10&page=3>; rel="last"'],
            ]),
        ];

        $httpClient = new MockHttpClient($responses);
        $github = new GitHub($httpClient, 'dummy_token');

        $result = $github->getContributors('owner/repo');

        $this->assertEquals(30, $result['count']); // 3 pages * 10 per page

        $contributors = iterator_to_array($result['iterator']);
        $this->assertCount(1, $contributors);
        $this->assertEquals([['login' => 'user3'], ['login' => 'user4']], $contributors[0]);

        // Verify the requested URLs
        $this->assertStringContainsString('/repos/owner/repo/contributors', $responses[0]->getRequestUrl());
        $this->assertStringContainsString('per_page=10', $responses[0]->getRequestUrl());
    }

    public function testParseLinkHeader(): void
    {
        $httpClient = new MockHttpClient();
        $github = new GitHub($httpClient, 'dummy_token');

        $method = new \ReflectionMethod(GitHub::class, 'parseLinkHeader');
        $method->setAccessible(true);

        $headers = ['<https://api.github.com/repos/owner/repo/contributors?page=2>; rel="next", <https://api.github.com/repos/owner/repo/contributors?page=3>; rel="last"'];

        $result = $method->invoke($github, $headers);

        $this->assertEquals([
            'next' => 'https://api.github.com/repos/owner/repo/contributors?page=2',
            'last' => 'https://api.github.com/repos/owner/repo/contributors?page=3',
        ], $result);
    }

    public function testGetContributorsWithNoMorePages(): void
    {
        $response = new MockResponse(json_encode([['login' => 'user1']]), [
            'response_headers' => [],
        ]);

        $httpClient = new MockHttpClient($response);
        $github = new GitHub($httpClient, 'dummy_token');

        $result = $github->getContributors('owner/repo');

        $this->assertGreaterThanOrEqual(1, $result['count']);
        $this->assertEquals([], iterator_to_array($result['iterator']));
    }
}

<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GitHub
{
    private readonly array $requestOptions;

    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire(env: 'GITHUB_TOKEN')]
        #[\SensitiveParameter]
        string $gitHubApiToken,
    ) {
        if (! $gitHubApiToken) {
            throw new \RuntimeException('GITHUB_TOKEN environment variable is required.');
        }

        $this->requestOptions = [
            // Set github token header
            'headers' => [
                'Authorization' => sprintf('Bearer %s', $gitHubApiToken),
                'X-GitHub-Api-Version' => '2022-11-28',
            ],
        ];
    }

    /** @return array{count: int, iterator: \Generator} */
    public function getContributors(string $repository, int $perPage = 10): array
    {
        // Get the first page of contributors to count total
        $url = sprintf('https://api.github.com/repos/%s/contributors?per_page=%d', $repository, $perPage);
        $response = $this->httpClient->request('GET', $url, $this->requestOptions);
        $links = $this->parseLinkHeader($response->getHeaders()['link'] ?? []);
        $nextLink = $links['next'] ?? null;
        $lastLink = $links['last'] ?? null;
        $count = 1;
        if ($lastLink) {
            parse_str(parse_url($lastLink, PHP_URL_QUERY), $query);
            $count = isset($query['page']) ? filter_var($query['page'], FILTER_VALIDATE_INT) : null;
        }

        return [
            'count' => $count * $perPage,
            'iterator' => (function () use ($nextLink) {
                while ($nextLink) {
                    $response = $this->httpClient->request('GET', $nextLink, $this->requestOptions);
                    $nextLink = $this->parseLinkHeader($response->getHeaders()['link'])['next'] ?? null;

                    yield $response->toArray();
                }
            })(),
        ];
    }

    /**
     * @param string[] $linkHeaders
     *
     * @return array<string, string>
     */
    private function parseLinkHeader(array $linkHeaders): array
    {
        $links = [];
        $parts = array_merge(...array_map(static fn ($linkHeader) => explode(',', $linkHeader), $linkHeaders));

        foreach ($parts as $part) {
            preg_match('/<([^>]+)>;\s*rel="([^"]+)"/', trim($part), $matches);
            if (count($matches) === 3) {
                $links[$matches[2]] = $matches[1];
            }
        }

        return $links;
    }
}

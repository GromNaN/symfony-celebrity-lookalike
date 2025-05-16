<?php

declare(strict_types=1);

namespace App\Service;

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function array_merge;

class OpenAI
{
    private const ANALYSE_PROMPT = <<<'PROMPT'
    Analyse the picture. It should contain a face or an abstract avatar.
    Describe the face in detail, ignoring everything aside from the face like a background.
    Focus on attributes that can help compare the face to other faces and determine whether the faces are similar.
    If the picture contains multiple faces, describe each face individually.
    Only return a list of individual attributes that can be used for comparison.
    PROMPT;

    private const FIND_MOST_SIMILAR_PROMPT = <<<'PROMPT'
    I am giving you a picture of a face, followed by a list of other faces. Number these images from 0.
    From that list of faces, find the face that is most similar to the first one I provided, then respond with only the number of the most similar image.
    PROMPT;

    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire(env: 'OPENAI_API_KEY')]
        #[\SensitiveParameter]
        private readonly string $apiKey,
    ) {
    }

    /**
     * @param string $imageData Image data as raw PNG data
     *
     * @return list<list<float>>
     */
    public function generateDescription(string $imageData): string
    {
        $request = [
            'model' => 'gpt-4.1-mini',
            'input' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => self::ANALYSE_PROMPT,
                        ],
                        $this->getImageDataPrompt($imageData),
                    ],
                ],
            ],
        ];

        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/responses', [
            'auth_bearer' => $this->apiKey,
            'json' => $request,
        ]);

        return $response->toArray()['output'][0]['content'][0]['text'];
    }

    /**
     * @param string       $imageData  Image data as raw PNG data
     * @param list<string> $candidates List of candidate images to compare against
     *
     * @return list<list<float>>
     */
    public function findMostSimilarFace(string $imageData, array $candidates): int
    {
        $content = [
            [
                'type' => 'input_text',
                'text' => self::FIND_MOST_SIMILAR_PROMPT,
            ],
            $this->getImageDataPrompt($imageData),
        ];

        $content = array_merge(
            $content,
            array_map(
                fn (string $imageData) => $this->getImageDataPrompt($imageData),
                $candidates,
            ),
        );

        $request = [
            'model' => 'gpt-4.1-mini',
            'input' => [
                [
                    'role' => 'user',
                    'content' => $content,
                ],
            ],
        ];

        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/responses', [
            'auth_bearer' => $this->apiKey,
            'json' => $request,
        ]);

        // Subtract 1 from the index as OpenAI also counts the original image
        $mostSimilarIndex = ((int) $response->toArray()['output'][0]['content'][0]['text']) - 1;

        return isset($candidates[$mostSimilarIndex])
            ? $mostSimilarIndex
            : throw new RuntimeException(sprintf('Invalid index %d returned from OpenAI', $mostSimilarIndex));
    }

    /** @return array{type: string, image_url: string} */
    private function getImageDataPrompt(string $imageData): array
    {
        return [
            'type' => 'input_image',
            'image_url' => 'data:image/png;base64,' . base64_encode($imageData),
        ];
    }
}

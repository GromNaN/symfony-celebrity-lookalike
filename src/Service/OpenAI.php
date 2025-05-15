<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function base64_encode;

class OpenAI
{
    private const PROMPT = <<<'PROMPT'
    Analyse the picture. It should contain a face or an abstract avatar.
    Describe the face in detail, ignoring everything aside from the face like a background.
    Focus on attributes that can help compare the face to other faces and determine whether the faces are similar.
    If the picture contains multiple faces, describe each face individually.
    Only return a list of individual attributes that can be used for comparison.
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
                            'text' => self::PROMPT,
                        ],
                        [
                            'type' => 'input_image',
                            'image_url' => 'data:image/png;base64,' . base64_encode($imageData),
                        ],
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
}

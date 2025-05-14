<?php

declare(strict_types=1);

namespace App\Service;

use App\VoyageAi\InputType;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

use function assert;
use function base64_encode;

class VoyageAI
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire(env: 'VOYAGE_API_KEY')]
        #[\SensitiveParameter]
        private readonly string $apiKey,
    ) {
    }

    /**
     * @param string $imageData Image data as raw PNG data
     *
     * @return list<list<float>>
     */
    public function generateEmbeddings(string $imageData, InputType $inputType = InputType::None): array
    {
        $request = [
            'model' => 'voyage-multimodal-3',
            'input' => [
                [
                    'content' => [
                        [
                            'type' => 'image_base64',
                            'image_base64' => 'data:image/png;base64,' . base64_encode($imageData),
                        ],
                    ],
                ],
            ],
        ];

        if ($inputType !== InputType::None) {
            $request['input_type'] = $inputType->value;
        }

        $response = $this->httpClient->request('POST', 'https://api.voyageai.com/v1/multimodalembeddings', [
            'auth_bearer' => $this->apiKey,
            'json' => $request,
        ]);

        return $this->extractEmbeddings($response);
    }

    /** @return list<float> */
    private function extractEmbeddings(ResponseInterface $response): array
    {
        $data = $response->toArray();

        assert(($data['object'] ?? null) === 'list');
        assert(isset($data['data']) && is_array($data['data']));

        return $data['data'][0]['embedding'];
    }
}

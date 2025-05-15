<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\VoyageAI;
use Imagine\Gd\Imagine;
use Imagine\Image\Format;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class VoyageAITest extends TestCase
{
    public function testGenerateImageEmbeddings(): void
    {
        $response = new MockResponse(<<<'JSON'
            {
              "object": "list",
              "data": [
                {
                  "object": "embedding",
                  "embedding": [
                    0.027587891,
                    -0.021240234,
                    0.018310547,
                    -0.021240234
                  ],
                  "index": 0
                }
              ],
              "model": "voyage-multimodal-3",
              "usage": {
                "text_tokens": 5,
                "image_pixels": 2000000,
                "total_tokens": 3576
              }
            }
        JSON);

        $httpClient = new MockHttpClient([$response]);
        $voyageAI = new VoyageAI($httpClient, 'secret-token');

        $imageData = new Imagine()->open(__DIR__ . '/../assets/face.png')->get(Format::ID_PNG);

        $embeddings = $voyageAI->generateImageEmbeddings($imageData);

        $this->assertEquals([0.027587891, -0.021240234, 0.018310547, -0.021240234], $embeddings);

        // Verify request details
        $this->assertEquals('https://api.voyageai.com/v1/multimodalembeddings', $response->getRequestUrl());
        $this->assertEquals('POST', $response->getRequestMethod());
        $this->assertJson($response->getRequestOptions()['body']);

        $requestJson = json_decode($response->getRequestOptions()['body'], true);
        $this->assertEquals('voyage-multimodal-3', $requestJson['model']);
        $this->assertEquals('image_base64', $requestJson['inputs'][0]['content'][0]['type']);
    }

    public function testGenerateTextEmbeddings(): void
    {
        $response = new MockResponse(<<<'JSON'
            {
              "object": "list",
              "data": [
                {
                  "object": "embedding",
                  "embedding": [
                    0.027587891,
                    -0.021240234,
                    0.018310547,
                    -0.021240234
                  ],
                  "index": 0
                }
              ],
              "model": "voyage-multimodal-3",
              "usage": {
                "text_tokens": 5,
                "image_pixels": 2000000,
                "total_tokens": 3576
              }
            }
        JSON);

        $httpClient = new MockHttpClient([$response]);
        $voyageAI = new VoyageAI($httpClient, 'secret-token');

        $embeddings = $voyageAI->generateTextEmbeddings('mock description');

        $this->assertEquals([0.027587891, -0.021240234, 0.018310547, -0.021240234], $embeddings);

        // Verify request details
        $this->assertEquals('https://api.voyageai.com/v1/embeddings', $response->getRequestUrl());
        $this->assertEquals('POST', $response->getRequestMethod());
        $this->assertJson($response->getRequestOptions()['body']);

        $requestJson = json_decode($response->getRequestOptions()['body'], true);
        $this->assertEquals('voyage-3.5', $requestJson['model']);
        $this->assertEquals('mock description', $requestJson['input'][0]);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\OpenAI;
use Imagine\Gd\Imagine;
use Imagine\Image\Format;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class OpenAITest extends TestCase
{
    public function testGenerateDescription(): void
    {
        $response = new MockResponse(<<<'JSON'
            {
              "output": [
                {
                  "content": [
                    {
                      "type": "output_text",
                      "text": "A person with a round face, short black hair, and brown eyes."
                    }
                  ]
                }
              ]
            }
        JSON);

        $httpClient = new MockHttpClient([$response]);
        $openAI = new OpenAI($httpClient, 'secret-token');

        $imageData = new Imagine()->open(__DIR__ . '/../assets/face.png')->get(Format::ID_PNG);

        $description = $openAI->generateDescription($imageData);

        $this->assertEquals('A person with a round face, short black hair, and brown eyes.', $description);

        // Verify request details
        $this->assertEquals('https://api.openai.com/v1/responses', $response->getRequestUrl());
        $this->assertEquals('POST', $response->getRequestMethod());
        $this->assertJson($response->getRequestOptions()['body']);

        $requestJson = json_decode($response->getRequestOptions()['body'], true);
        $this->assertEquals('gpt-4.1-mini', $requestJson['model']);
        $this->assertCount(2, $requestJson['input'][0]['content']);
        $this->assertSame('input_text', $requestJson['input'][0]['content'][0]['type']);
        $this->assertSame('input_image', $requestJson['input'][0]['content'][1]['type']);
    }
}

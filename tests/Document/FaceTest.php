<?php

declare(strict_types=1);

namespace App\Tests\Document;

use App\Document\Face;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FaceTest extends KernelTestCase
{
    public function testSearchIndex(): void
    {
        self::bootKernel();

        $classMetadata = $this->getContainer()
            ->get('doctrine_mongodb.odm.default_document_manager')
            ->getClassMetadata(Face::class);

        $this->assertEquals(
            [
                [
                    'name' => 'faces',
                    'type' => 'vectorSearch',
                    'definition' => [
                        'fields' => [
                            [
                                'numDimensions' => 1024,
                                'path' => 'embeddings',
                                'similarity' => 'euclidean',
                                'type' => 'vector',
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'descriptions',
                    'type' => 'vectorSearch',
                    'definition' => [
                        'fields' => [
                            [
                                'numDimensions' => 1024,
                                'path' => 'descriptionEmbeddings',
                                'similarity' => 'euclidean',
                                'type' => 'vector',
                            ],
                        ],
                    ],
                ],
            ],
            $classMetadata->searchIndexes,
        );
    }
}

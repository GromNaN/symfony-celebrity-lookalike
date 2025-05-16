<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'faces')]
#[ODM\SearchIndex(
    name: 'faces',
    fields: [
        [
            'numDimensions' => 1024,
            'path' => 'embeddings',
            'similarity' => 'euclidean',
            'type' => 'vector',
        ],
    ],
)]
#[ODM\SearchIndex(
    name: 'descriptions',
    fields: [
        [
            'numDimensions' => 1024,
            'path' => 'descriptionEmbeddings',
            'similarity' => 'euclidean',
            'type' => 'vector',
        ],
    ],
)]
class Face
{
    #[ODM\Id]
    public ?string $id = null;

    #[ODM\Field]
    #[ODM\Index(unique: true)]
    public ?string $name = null;

    #[ODM\Field(type: 'string')]
    public ?string $description = null;

    #[ODM\Field(type: 'bin')]
    public string $resizedImage;

    /** @var float[] Vector generated from the image file */
    #[ODM\Field(name: 'embeddings', type: 'collection')]
    public ?array $imageEmbeddings = null;

    /** @var float[] Vector generated from the textual description of the image */
    #[ODM\Field(type: 'collection')]
    public ?array $descriptionEmbeddings = null;

    #[ODM\ReferenceOne(targetDocument: Picture::class, cascade: ['persist', 'remove'])]
    public ?Picture $file = null;

    #[ODM\Field(type: 'date_immutable')]
    public ?\DateTimeImmutable $expiresAt = null;
}

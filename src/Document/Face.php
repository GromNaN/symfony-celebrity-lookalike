<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'faces')]
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

    /** @var float[] */
    #[ODM\Field(type: 'collection')]
    public ?array $embeddings = null;

    #[ODM\ReferenceOne(targetDocument: Picture::class, cascade: ['persist', 'remove'])]
    public ?Picture $file = null;
}

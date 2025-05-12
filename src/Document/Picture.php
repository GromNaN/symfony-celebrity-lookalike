<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'pictures')]
class Picture
{
    #[ODM\Id]
    public ?string $id = null;

    #[ODM\Field]
    public ?string $name = null;

    #[ODM\Field(type: 'string')]
    public ?string $description = null;

    /** @var float[] */
    #[ODM\Field(type: 'collection')]
    public ?array $embeddings = null;

    #[ODM\ReferenceOne(targetDocument: File::class, cascade: ['persist', 'remove'])]
    public ?File $file = null;
}

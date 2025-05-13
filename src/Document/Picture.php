<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\File(bucketName: 'pictures')]
class Picture
{
    #[ODM\Id]
    public ?string $id = null;

    #[ODM\Field(type: 'string')]
    public string $filename;

    #[ODM\Field(type: 'date')]
    public \DateTime $uploadDate;
}

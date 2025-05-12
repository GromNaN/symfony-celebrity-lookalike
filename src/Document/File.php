<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\File(bucketName: "pictures_bucket")]
class File
{
    #[ODM\Id]
    public string $id;

    #[ODM\Field(type: "string")]
    public string $filename;

    #[ODM\Field(type: "date")]
    public \DateTime $uploadDate;
}
<?php

declare(strict_types=1);

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\QueryResultDocument]
class VectorSearchResult
{
    #[ODM\EmbedOne(targetDocument: Face::class)]
    public Face $face;

    #[ODM\Field(type: 'float')]
    public float $score;
}

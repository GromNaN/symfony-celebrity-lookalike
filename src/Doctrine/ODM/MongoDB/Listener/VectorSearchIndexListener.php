<?php

declare(strict_types=1);

namespace App\Doctrine\ODM\MongoDB\Listener;

use App\Document\Face;
use Doctrine\Bundle\MongoDBBundle\Attribute\AsDocumentListener;
use Doctrine\ODM\MongoDB\Event\LoadClassMetadataEventArgs;
use Doctrine\ODM\MongoDB\Events;

use function array_map;

#[AsDocumentListener(Events::loadClassMetadata)]
class VectorSearchIndexListener
{
    public function loadClassMetadata(LoadClassMetadataEventArgs $args): void
    {
        $classMetadata = $args->getClassMetadata();

        if ($classMetadata->name !== Face::class) {
            return;
        }

        // Remove the original search index definition - ODM only supports Atlas Text Search
        $searchIndexes = array_map(
            static function (array $index) {
                if (($index['type'] ?? null) === 'vectorSearch') {
                    return $index;
                }

                $index['type'] = 'vectorSearch';
                $index['definition']['fields'] = $index['definition']['mappings']['fields'];
                unset($index['definition']['mappings']);

                return $index;
            },
            $classMetadata->searchIndexes,
        );

        // A big no-no: override the search indexes
        $classMetadata->searchIndexes = $searchIndexes;
    }
}

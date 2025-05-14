<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Document\Face;
use App\Document\Picture;
use App\Service\PictureService;
use App\Service\VoyageAI;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\GridFSRepository;
use PHPUnit\Framework\TestCase;
use function copy;
use function glob;
use function is_file;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

class PictureServiceTest extends TestCase
{
    private PictureService $pictureService;
    private DocumentManager $documentManagerMock;
    private VoyageAI $voyageAIMock;

    protected function setUp(): void
    {
        $this->documentManagerMock = $this->getMockBuilder(DocumentManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['persist', 'flush', 'getRepository'])
            ->getMock();

        $this->voyageAIMock = $this->getMockBuilder(VoyageAI::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['generateEmbeddings'])
            ->getMock();

        $this->pictureService = new PictureService(
            $this->documentManagerMock,
            $this->voyageAIMock,
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $tempFiles = glob(sys_get_temp_dir() . '/test_image*');
        foreach ($tempFiles as $file) {
            if (! is_file($file)) {
                continue;
            }

            unlink($file);
        }
    }

    public function testStorePicture(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'test_image.png');
        copy(__DIR__ . '/assets/face.png', $filePath);
        $originalName = 'image.jpg';

        $file = new Picture();
        $file->id = 'mockFileId';
        $file->filename = $originalName;
        $file->uploadDate = new \DateTime();

        $bucketMock = $this->createMock(GridFSRepository::class);

        $bucketMock
            ->expects($this->once())
            ->method('uploadFromFile')
            ->with($filePath, $originalName)
            ->willReturn($file);

        $this->documentManagerMock
            ->expects($this->once())
            ->method('getRepository')
            ->with(Picture::class)
            ->willReturn($bucketMock);

        $this->documentManagerMock
            ->expects($this->exactly(2))
            ->method('persist')
            ->with($this->callback(static function ($object) use ($originalName) {
                static $callCount = 0;
                $callCount++;

                if ($callCount === 1) {
                    return $object instanceof Picture && $object->filename === $originalName;
                }

                if ($callCount === 2) {
                    return $object instanceof Face;
                }

                return false;
            }));

        $this->documentManagerMock
            ->expects($this->once())
            ->method('flush');

        $this->voyageAIMock
            ->expects($this->once())
            ->method('generateEmbeddings')
            ->willReturn([-1, 0.5, 1]);

        $face = $this->pictureService->storePicture($filePath, $originalName, 'mockName');

        $this->assertInstanceOf(Face::class, $face);
        $this->assertEquals('mockName', $face->name);
        $this->assertEmpty($face->description);
        $this->assertEquals([-1, 0.5, 1], $face->embeddings);
        $this->assertNotEmpty($face->resizedImage);
    }

    public function testFindSimilarPictures(): void
    {
        $embeddings = [0.1, 0.2, 0.3];
        $threshold = 0.8;

        $pictureMock = $this->createMock(Face::class);
        $pictureMock->embeddings = [0.1, 0.2, 0.3];
        $pictureMock->id = 'mockId';
        $pictureMock->description = 'mockDescription';

        $repositoryMock = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
        $repositoryMock
            ->expects($this->once())
            ->method('findAll')
            ->willReturn([$pictureMock]);

        $this->pictureService = $this->getMockBuilder(PictureService::class)
            ->setConstructorArgs([$this->documentManagerMock, $this->voyageAIMock])
            ->onlyMethods(['calculateSimilarity'])
            ->getMock();

        $this->pictureService
            ->expects($this->once())
            ->method('calculateSimilarity')
            ->with($embeddings, $pictureMock->embeddings)
            ->willReturn(0.9);

        $this->documentManagerMock
            ->expects($this->once())
            ->method('getRepository')
            ->with(Face::class)
            ->willReturn($repositoryMock);

        $picture = new Face();
        $picture->embeddings = $embeddings;

        $matches = $this->pictureService->findSimilarPictures($picture, $threshold);

        $this->assertNotEmpty($matches);
        $this->assertEquals($pictureMock, $matches[0]['picture']);
    }
}

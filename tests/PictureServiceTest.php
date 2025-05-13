<?php

declare(strict_types=1);

namespace App\Tests;

use App\Document\Face;
use App\Document\Picture;
use App\Service\PictureService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\GridFSRepository;
use PHPUnit\Framework\TestCase;

use function file_put_contents;
use function glob;
use function is_file;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

class PictureServiceTest extends TestCase
{
    private PictureService $pictureService;
    private DocumentManager $documentManagerMock;

    protected function setUp(): void
    {
        $this->documentManagerMock = $this->getMockBuilder(DocumentManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['persist', 'flush', 'getRepository'])
            ->getMock();

        $this->pictureService = new PictureService(
            $this->documentManagerMock,
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
        $filePath = tempnam(sys_get_temp_dir(), 'test_image');
        file_put_contents($filePath, 'fake image content');
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

        $picture = $this->pictureService->storePicture($filePath, $originalName, 'mockName');

        $this->assertInstanceOf(Face::class, $picture);
        $this->assertEquals('mockName', $picture->name);
        $this->assertNotEmpty($picture->description);
        $this->assertNotEmpty($picture->embeddings);
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
            ->setConstructorArgs([$this->documentManagerMock])
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

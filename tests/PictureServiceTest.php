<?php

namespace App\Tests; // Fix namespace to align with PSR-4 standards

use App\Document\Picture;
use App\Service\PictureService;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\GridFS\Bucket;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject; // Import MockObject explicitly

class PictureServiceTest extends TestCase
{
    private PictureService $pictureService;
    private Bucket $gridFsBucketMock;
    private DocumentManager $documentManagerMock;

    protected function setUp(): void
    {
        $this->gridFsBucketMock = $this->createMock(Bucket::class);

        // Ensure proper mocking of methods
        $this->documentManagerMock = $this->getMockBuilder(DocumentManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['persist', 'flush', 'getRepository'])
            ->getMock();

        $this->pictureService = new PictureService(
            $this->gridFsBucketMock,
            $this->documentManagerMock
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up temporary files created during tests
        $tempFiles = glob(sys_get_temp_dir() . '/test_image*');
        foreach ($tempFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function testStorePicture(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'test_image');
        file_put_contents($filePath, 'fake image content');
        $originalName = 'image.jpg';
        $fileId = 'mockFileId';

        $this->gridFsBucketMock
            ->expects($this->once())
            ->method('uploadFromStream')
            ->with($originalName, $this->callback(function ($stream) {
                return is_resource($stream);
            })) // Use a callback to check for resource type
            ->willReturn($fileId);

        $this->documentManagerMock
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Picture::class));

        $this->documentManagerMock
            ->expects($this->once())
            ->method('flush');

        $picture = $this->pictureService->storePicture($filePath, $originalName);

        $this->assertInstanceOf(Picture::class, $picture);
        $this->assertEquals($fileId, $picture->fileId);
        $this->assertNotEmpty($picture->resizedImage);
        $this->assertNotEmpty($picture->description);
        $this->assertNotEmpty($picture->embeddings);
    }

    public function testFindSimilarPictures(): void
    {
        $embeddings = [0.1, 0.2, 0.3];
        $threshold = 0.8;

        $pictureMock = $this->createMock(Picture::class);
        $pictureMock->embeddings = [0.1, 0.2, 0.3];
        $pictureMock->id = 'mockId';
        $pictureMock->description = 'mockDescription';

        $repositoryMock = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
        $repositoryMock
            ->expects($this->once())
            ->method('findAll')
            ->willReturn([$pictureMock]);

        $this->pictureService = $this->getMockBuilder(PictureService::class)
            ->setConstructorArgs([$this->gridFsBucketMock, $this->documentManagerMock])
            ->onlyMethods(['calculateSimilarity'])
            ->getMock();

        $this->pictureService
            ->expects($this->once())
            ->method('calculateSimilarity')
            ->with($embeddings, $pictureMock->embeddings)
            ->willReturn(0.9); // Mock similarity above the threshold

        $this->documentManagerMock
            ->expects($this->once())
            ->method('getRepository')
            ->with(Picture::class)
            ->willReturn($repositoryMock);

        $picture = new Picture();
        $picture->embeddings = $embeddings;

        $matches = $this->pictureService->findSimilarPictures($picture, $threshold);

        $this->assertNotEmpty($matches);
        $this->assertEquals('mockId', $matches[0]['id']);
        $this->assertEquals('mockDescription', $matches[0]['description']);
    }
}
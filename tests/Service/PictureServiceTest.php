<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Document\Face;
use App\Document\Picture;
use App\Service\OpenAI;
use App\Service\PictureService;
use App\Service\VoyageAI;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Doctrine\ODM\MongoDB\Repository\GridFSRepository;
use Imagine\Gd\Imagine;
use Imagine\Image\Format;
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
    private OpenAI $openAIMock;

    protected function setUp(): void
    {
        $this->documentManagerMock = $this->getMockBuilder(DocumentManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['persist', 'flush', 'getRepository'])
            ->getMock();

        $this->voyageAIMock = $this->getMockBuilder(VoyageAI::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['generateTextEmbeddings'])
            ->getMock();

        $this->openAIMock = $this->getMockBuilder(OpenAI::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['generateDescription'])
            ->getMock();

        $this->pictureService = new PictureService(
            $this->documentManagerMock,
            $this->openAIMock,
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
        copy(__DIR__ . '/../assets/face.png', $filePath);
        $originalName = 'image.jpg';

        $file = new Picture();
        $file->id = 'mockFileId';
        $file->filename = $originalName;
        $file->uploadDate = new \DateTime();

        $bucketMock = $this->createMock(GridFSRepository::class);
        $repositoryMock = $this->createMock(DocumentRepository::class);

        $bucketMock
            ->expects($this->once())
            ->method('uploadFromFile')
            ->with($filePath, $originalName)
            ->willReturn($file);

        $repositoryMock
            ->expects($this->any(2))
            ->method('findOneBy')
            ->with(['name' => 'mockName'])
            ->willReturn(null);

        $this->documentManagerMock
            ->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturnMap(
                [
                    [Picture::class, $bucketMock],
                    [Face::class, $repositoryMock],
                ],
            );

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

        $this->openAIMock
            ->expects($this->once())
            ->method('generateDescription')
            ->willReturn('mock description');

        $this->voyageAIMock
            ->expects($this->once())
            ->method('generateTextEmbeddings')
            ->with('mock description')
            ->willReturn([-1, 0.5, 1]);

        $face = $this->pictureService->storePicture($filePath, $originalName, 'mockName');

        $this->assertInstanceOf(Face::class, $face);
        $this->assertEquals('mockName', $face->name);
        $this->assertSame('mock description', $face->description);
        $this->assertEquals([-1, 0.5, 1], $face->descriptionEmbeddings);
        $this->assertNotEmpty($face->resizedImage);
    }

    public function testGenerateDescriptionAndEmbeddings(): void
    {
        $image = new Imagine()->open(__DIR__ . '/../assets/face.png');

        $this->openAIMock
            ->expects($this->once())
            ->method('generateDescription')
            ->willReturn('mock description');

        $this->voyageAIMock
            ->expects($this->once())
            ->method('generateTextEmbeddings')
            ->with('mock description')
            ->willReturn([-1, 0.5, 1]);

        [$description, $embeddings] = $this->pictureService->generateDescriptionAndEmbeddings($image->get(Format::ID_PNG));

        $this->assertSame('mock description', $description);
        $this->assertEquals([-1, 0.5, 1], $embeddings);
    }
}

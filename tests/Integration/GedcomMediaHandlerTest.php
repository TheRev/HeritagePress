<?php
namespace HeritagePress\Tests;

use PHPUnit\Framework\TestCase;
use HeritagePress\GEDCOM\GedcomMediaHandler;

class GedcomMediaHandlerTest extends TestCase {
    private $mediaHandler;
    private $testDir;

    protected function setUp(): void {
        $this->testDir = dirname(__FILE__) . '/test_media';
        if (!file_exists($this->testDir)) {
            mkdir($this->testDir);
        }
        $this->mediaHandler = new GedcomMediaHandler($this->testDir);
    }

    protected function tearDown(): void {
        // Clean up test files
        if (file_exists($this->testDir)) {
            $files = glob($this->testDir . '/*');
            foreach ($files as $file) {
                unlink($file);
            }
            rmdir($this->testDir);
        }
    }

    public function testHandleSimpleMedia() {
        $media = [
            'id' => '@M1@',
            'data' => [
                [
                    'tag' => 'FILE',
                    'value' => 'photo.jpg',
                    'children' => [
                        ['tag' => 'FORM', 'value' => 'jpg']
                    ]
                ],
                ['tag' => 'TITL', 'value' => 'Family Photo']
            ]
        ];

        $result = $this->mediaHandler->handleMedia($media);

        $this->assertEquals('@M1@', $result['id']);
        $this->assertEquals('Family Photo', $result['title']);
        $this->assertCount(1, $result['files']);
        $this->assertEquals('jpg', $result['files'][0]['format']);
        $this->assertEquals('image', $result['files'][0]['type']);
    }

    public function testHandleMediaWithTranslations() {
        $media = [
            'id' => '@M1@',
            'data' => [
                [
                    'tag' => 'FILE',
                    'value' => 'document.pdf',
                    'children' => [
                        ['tag' => 'FORM', 'value' => 'pdf'],
                        [
                            'tag' => 'TRAN',
                            'value' => 'document_fr.pdf',
                            'children' => [
                                ['tag' => 'FORM', 'value' => 'pdf'],
                                ['tag' => 'LANG', 'value' => 'fr']
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->mediaHandler->handleMedia($media);

        $this->assertCount(1, $result['files']);
        $this->assertArrayHasKey('translations', $result['files'][0]);
        $this->assertCount(1, $result['files'][0]['translations']);
        $this->assertEquals('fr', $result['files'][0]['translations'][0]['language']);
    }

    public function testHandleMultipleFiles() {
        $media = [
            'id' => '@M1@',
            'data' => [
                [
                    'tag' => 'FILE',
                    'value' => 'photo1.jpg',
                    'children' => [['tag' => 'FORM', 'value' => 'jpg']]
                ],
                [
                    'tag' => 'FILE',
                    'value' => 'photo2.png',
                    'children' => [['tag' => 'FORM', 'value' => 'png']]
                ]
            ]
        ];

        $result = $this->mediaHandler->handleMedia($media);

        $this->assertCount(2, $result['files']);
        $this->assertEquals('jpg', $result['files'][0]['format']);
        $this->assertEquals('png', $result['files'][1]['format']);
    }

    public function testDetectMediaType() {
        $formats = [
            // Images
            'jpg' => 'image',
            'jpeg' => 'image',
            'png' => 'image',
            'gif' => 'image',
            'bmp' => 'image',
            // Documents
            'pdf' => 'document',
            'doc' => 'document',
            'docx' => 'document',
            'txt' => 'document',
            // Audio
            'mp3' => 'audio',
            'wav' => 'audio',
            'ogg' => 'audio',
            // Video
            'mp4' => 'video',
            'avi' => 'video',
            'mov' => 'video'
        ];

        foreach ($formats as $format => $expectedType) {
            $media = [
                'id' => '@M1@',
                'data' => [
                    [
                        'tag' => 'FILE',
                        'value' => "test.$format",
                        'children' => [['tag' => 'FORM', 'value' => $format]]
                    ]
                ]
            ];

            $result = $this->mediaHandler->handleMedia($media);
            $this->assertEquals($expectedType, $result['files'][0]['type'], "Failed for format: $format");
        }
    }

    public function testPathNormalization() {
        $paths = [
            'file://path/to/file.jpg' => $this->testDir . '/path/to/file.jpg',
            'C:\\path\\to\\file.jpg' => 'C:/path/to/file.jpg',
            '/absolute/path/file.jpg' => '/absolute/path/file.jpg',
            'relative/path/file.jpg' => $this->testDir . '/relative/path/file.jpg'
        ];

        foreach ($paths as $input => $expected) {
            $media = [
                'id' => '@M1@',
                'data' => [
                    [
                        'tag' => 'FILE',
                        'value' => $input,
                        'children' => [['tag' => 'FORM', 'value' => 'jpg']]
                    ]
                ]
            ];

            $result = $this->mediaHandler->handleMedia($media);
            $this->assertEquals($expected, $result['files'][0]['path'], "Failed normalizing: $input");
        }
    }

    public function testMediaValidation() {
        // Create a test file
        $validFile = $this->testDir . '/valid.jpg';
        file_put_contents($validFile, 'test content');

        $validationResults = [
            // Valid file
            $validFile => ['valid' => true],
            
            // Non-existent file
            $this->testDir . '/nonexistent.jpg' => [
                'valid' => false,
                'error' => 'File not found'
            ],
            
            // Empty file
            $this->testDir . '/empty.jpg' => [
                'valid' => false,
                'error' => 'Empty file'
            ],
            
            // Unsupported format
            $this->testDir . '/test.xyz' => [
                'valid' => false,
                'error' => 'Unsupported file type'
            ]
        ];

        // Create empty file
        touch($this->testDir . '/empty.jpg');

        foreach ($validationResults as $path => $expected) {
            $result = $this->mediaHandler->validateMediaFile($path);
            $this->assertEquals($expected, $result, "Validation failed for: $path");
        }
    }

    public function testHandleEmptyMedia() {
        $media = [
            'id' => '@M1@',
            'data' => []
        ];

        $result = $this->mediaHandler->handleMedia($media);

        $this->assertEquals('@M1@', $result['id']);
        $this->assertEmpty($result['files']);
    }

    public function testHandleInvalidMedia() {
        $media = [
            'id' => '',
            'data' => [
                [
                    'tag' => 'FILE',
                    'value' => '',
                    'children' => []
                ]
            ]
        ];

        $result = $this->mediaHandler->handleMedia($media);

        $this->assertNull($result);
    }

    public function testGedcomZipExtraction() {
        // Create a test ZIP file
        $zipPath = $this->testDir . '/test.gdz';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);
        $zip->addFromString('photo1.jpg', 'test content 1');
        $zip->addFromString('photo2.png', 'test content 2');
        $zip->addFromString('document.txt', 'test content 3');
        $zip->close();

        $extractPath = $this->testDir . '/extracted';
        $count = $this->mediaHandler->extractMediaFromGdz($zipPath, $extractPath);

        $this->assertEquals(3, $count);
        $this->assertFileExists($extractPath . '/photo1.jpg');
        $this->assertFileExists($extractPath . '/photo2.png');
        $this->assertFileExists($extractPath . '/document.txt');
    }
}

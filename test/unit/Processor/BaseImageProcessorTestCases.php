<?php
/**
 * @author    Craig Gosman <craig@ingenerator.com>
 */

namespace test\unit\Processor;

use Ingenerator\ImageProcessing\Processor\ImageProcessorInterface;
use Jcupitt\Vips\Size;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ingenerator\ImageProcessing\Processor\ImageCompare;
use Symfony\Component\Filesystem\Filesystem;
use function array_merge;
use function pathinfo;
use function sprintf;
use function uniqid;
use const PATHINFO_EXTENSION;
use const PATHINFO_FILENAME;

abstract class BaseImageProcessorTestCases extends TestCase
{
    private const RESOURCE_DIR = __DIR__.'/../../resources/';
    private const OUTPUT_DIR   = __DIR__.'/../../output/';

    abstract protected function newSubject(): ImageProcessorInterface;

    public static function providerImageSize(): array
    {
        return [
            [self::RESOURCE_DIR.'crop_400.jpg', FALSE, [400, 400]],
            [self::RESOURCE_DIR.'grey.jpg', FALSE, [800, 400]],
            [self::RESOURCE_DIR.'logo.png', FALSE, [500, 125]],
            [self::RESOURCE_DIR.'test_fonts.pdf', FALSE, [1240, 1754]],
            [self::RESOURCE_DIR.'left-mirrored.jpg', FALSE, [640, 480]],
            [self::RESOURCE_DIR.'left-mirrored.jpg', TRUE, [480, 640]],
        ];
    }

    #[DataProvider('providerImageSize')]
    public function test_get_image_size(string $source_path, bool $auto_rotate, array $expect): void
    {
        $subject = $this->newSubject();
        $this->assertSame($expect, $subject::getImageSize($source_path, $auto_rotate));
    }

    public static function providerCreatePlaceholder(): array
    {
        return [
            'JPEG placeholder' => [600, 400, self::RESOURCE_DIR.'placeholder.jpg'],
            'PNG placeholder'  => [200, 200, self::RESOURCE_DIR.'placeholder.png'],
        ];
    }

    #[DataProvider('providerCreatePlaceholder')]
    public function test_create_placeholder($width, $height, $path_expected_result): void
    {
        $subject = $this->newSubject();

        $output_file = sprintf(
            "%s%s.%s",
            self::OUTPUT_DIR,
            uniqid(pathinfo($path_expected_result, PATHINFO_FILENAME).'-', TRUE),
            pathinfo($path_expected_result, PATHINFO_EXTENSION)
        );
        $subject->createPlaceholder($width, $height, $output_file);
        $this->assertTrue(ImageCompare::isSame($path_expected_result, $output_file));
    }

    public static function providerThumbnailOperations(): array
    {
        return [
            'Scale down proportionately'                          => [
                self::RESOURCE_DIR.'porto_1024.jpg',
                ['scale' => ['width' => 640], 'save' => ['type' => 'jpg', 'quality' => 90]],
                self::RESOURCE_DIR.'scale_640.jpg',
            ],
            'Scale down to bounding box' => [
                self::RESOURCE_DIR.'porto_1024.jpg',
                ['scale' => ['width' => 640, 'height'=>200], 'save' => ['type' => 'jpg', 'quality' => 75]],
                self::RESOURCE_DIR.'scale_200.jpg',
            ],
            'Scale up to bounding box' => [
                self::RESOURCE_DIR.'porto_1024.jpg',
                ['scale' => ['width' => 2000, 'height'=>700], 'save' => ['type' => 'jpg', 'quality' => 75]],
                self::RESOURCE_DIR.'scale_700.jpg',
            ],
            'Crop center out of image'                            => [
                self::RESOURCE_DIR.'porto_1024.jpg',
                ['scale' => ['width' => 521], 'crop' => ['width' => 400, 'height' => 400, 'resize_method' => 'center']],
                self::RESOURCE_DIR.'crop_400.jpg',
            ],
            'Pad JPEG with transparent background'                => [
                self::RESOURCE_DIR.'porto_1024.jpg',
                [
                    'scale' => ['width' => 1024],
                    'pad'   => [
                        'width'         => 1048,
                        'height'        => 1048,
                        'resize_method' => 'fit',
                        'background'    => 'transparent',
                    ],
                    'save'  => ['type' => 'png'],
                ],
                self::RESOURCE_DIR.'fit_1048.png',
            ],
            'Convert to greyscale'                                => [
                self::RESOURCE_DIR.'porto_1024.jpg',
                [
                    'scale'  => ['width' => 521],
                    'pad'    => [
                        'width'         => 800,
                        'height'        => 400,
                        'resize_method' => 'fit-left',
                        'background'    => 'transparent',
                    ],
                    'filter' => 'greyscale',
                ],
                self::RESOURCE_DIR.'grey.jpg',
            ],
            'Convert PDF to PNG thumb'                            => [
                self::RESOURCE_DIR.'test_fonts.pdf',
                ['scale' => ['width' => 1000], 'save' => ['type' => 'png']],
                self::RESOURCE_DIR.'pdf.png',
            ],
            'Pad JPEG with colour'                                => [
                self::RESOURCE_DIR.'porto_1024.jpg',
                [
                    'scale' => ['width' => 150],
                    'pad'   => [
                        'width'         => 150,
                        'height'        => 150,
                        'resize_method' => 'fit',
                        'background'    => 'F90',
                    ],
                    'save'  => ['type' => 'jpg'],
                ],
                self::RESOURCE_DIR.'square_150_colour.jpg',
            ],
            'Read PNG with alpha'                                 => [
                self::RESOURCE_DIR.'logo.png',
                [
                    'scale' => ['width' => 550],
                    'pad'   => [
                        'width'         => 550,
                        'height'        => 550,
                        'resize_method' => 'fit',
                        'background'    => 'F90',
                    ],
                    'save'  => ['type' => 'png'],
                ],
                self::RESOURCE_DIR.'read_alpha.png',
            ],
            'Scale down only if src image greater than requested size, ie DO NOT SCALE UP' => [
                self::RESOURCE_DIR.'porto_1024.jpg',
                [
                    'scale' => ['width' => 1025, 'height' => 787, 'size' => Size::DOWN],
                    'save'  => ['type' => 'webp'],
                ],
                self::RESOURCE_DIR.'porto_1024.webp',
            ],
            'Scale up'                                            => [
                self::RESOURCE_DIR.'logo.png',
                [
                    'scale' => ['width' => 600],
                    'pad'   => [
                        'width'         => 600,
                        'height'        => 143,
                        'resize_method' => 'fit-left',
                        'background'    => '#06F',
                    ],
                    'save'  => ['type' => 'png'],
                ],
                self::RESOURCE_DIR.'scale_up.png',
            ],
            'Colour profiles converted to sRGB and then stripped' => [
                self::RESOURCE_DIR.'webkit_logo_p3.png',
                ['scale' => ['width' => 600]],
                self::RESOURCE_DIR.'webkit_logo_srgb.jpg',
            ],
            'Image is rotated correctly' => [
                self::RESOURCE_DIR.'left-mirrored.jpg',
                ['scale' => ['width' => 400]],
                self::RESOURCE_DIR.'oriented-correctly.jpg',
            ],
            'Demonstrate WebP support and compression'            => [
                self::RESOURCE_DIR.'porto_1024.jpg',
                ['scale' => ['width' => 1024], 'save' => ['type' => 'webp']],
                self::RESOURCE_DIR.'porto_1024.webp',
            ],
        ];
    }

    #[DataProvider('providerThumbnailOperations')]
    public function test_thumbnail(string $source_image, array $operations, string $path_expected_result): void
    {
        $operations = array_merge(
            ['save' => ['type' => 'jpg', 'quality' => 90]],
            $operations
        );

        $subject     = $this->newSubject();
        $output_file = sprintf(
            "%s%s.%s",
            self::OUTPUT_DIR,
            uniqid(pathinfo($path_expected_result, PATHINFO_FILENAME).'-', TRUE),
            pathinfo($path_expected_result, PATHINFO_EXTENSION)
        );
        $subject->thumbnail($source_image, $output_file, $operations);
        $this->assertTrue(
            ImageCompare::isSame($path_expected_result, $output_file),
            'Failed asserting that '.$output_file.' is same image as '.$path_expected_result
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        $filesystem = new Filesystem();
        $filesystem->mkdir(self::OUTPUT_DIR);
    }

}

<?php
/**
 * @author    Craig Gosman <craig@ingenerator.com>
 */

namespace test\unit\Ingenerator\ImageProcessing\Processor;

use Ingenerator\ImageProcessing\Processor\ImageProcessorInterface;
use Jcupitt\Vips\Image;
use Jcupitt\Vips\Interpretation;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use function array_merge;
use function pathinfo;
use function sprintf;
use function uniqid;
use const PATHINFO_EXTENSION;
use const PATHINFO_FILENAME;

abstract class BaseImageProcessorTest extends TestCase
{
    private const RESOURCE_DIR = __DIR__.'/../../resources/';
    private const OUTPUT_DIR   = __DIR__.'/../../output/';

    abstract protected function newSubject(): ImageProcessorInterface;

    public function providerImageSize(): array
    {
        return [
            [self::RESOURCE_DIR.'crop_400.jpg', [400, 400]],
            [self::RESOURCE_DIR.'fit_1048.png', [1048, 1048]],
            [self::RESOURCE_DIR.'grey.jpg', [800, 400]],
            [self::RESOURCE_DIR.'logo.png', [550, 131]],
            [self::RESOURCE_DIR.'pdf.png', [1000, 1415]],
            [self::RESOURCE_DIR.'porto_1024.jpg', [1024, 786]],
            [self::RESOURCE_DIR.'read_alpha.png', [550, 550]],
            [self::RESOURCE_DIR.'scale_640.jpg', [640, 491]],
            [self::RESOURCE_DIR.'scale_up.png', [600, 143]],
            [self::RESOURCE_DIR.'test_fonts.pdf', [1240, 1754]],
            [self::RESOURCE_DIR.'webkit_logo_p3.png', [1000, 1000]],
            [self::RESOURCE_DIR.'webkit_logo_srgb.jpg', [600, 600]],
        ];
    }

    /**
     * @dataProvider providerImageSize
     */
    public function test_get_image_size(string $source_path, array $expect): void
    {
        $subject = $this->newSubject();
        $this->assertSame($expect, $subject->getImageSize($source_path));
    }

    public function providerCreatePlaceholder(): array
    {
        return [
            'JPEG placeholder' => [600, 400, self::RESOURCE_DIR.'placeholder.jpg'],
            'PNG placeholder'  => [200, 200, self::RESOURCE_DIR.'placeholder.png'],
        ];
    }

    /**
     * @dataProvider providerCreatePlaceholder
     */
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
        $this->assertImageSame($path_expected_result, $output_file);
    }

    public function providerThumbnailOperations(): array
    {
        return [
            'Scale down proportionately'                          => [
                self::RESOURCE_DIR.'porto_1024.jpg',
                ['scale' => ['width' => 640], 'save' => ['type' => 'jpg', 'quality' => 90]],
                self::RESOURCE_DIR.'scale_640.jpg',
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
        ];
    }

    /**
     * @dataProvider providerThumbnailOperations
     */
    public function test_thumbnail(string $source_image, array $operations, string $path_expected_result): void
    {
        $operations = array_merge(
            ['filter' => NULL, 'save' => ['type' => 'jpg', 'quality' => 90]],
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
        $this->assertImageSame($path_expected_result, $output_file);
    }

    private function assertImageSame(
        string $path_to_expected,
        string $path_to_result,
    ): void {
        [$expected, $actual] = array_map(static function ($path) {
            $image = Image::newFromFile($path, ['access' => 'sequential'])
                ->colourspace(Interpretation::SRGB);

            if ( ! $image->hasAlpha()) {
                $image = $image->bandjoin_const(255);
            }

            return $image;
        }, [$path_to_expected, $path_to_result]);

        Assert::assertSame(
            $expected->width.'x'.$expected->height,
            $actual->width.'x'.$actual->height,
            "$path_to_result should have same dimensions as $path_to_expected"
        );

        // Equal will give 0 or 255 for false or true. Min will start a set of threads
        // to pull pixels through the equals operator, but is smart enough to know that
        // for uchar pixels (what you'll get for equal), 0 is the minimum possible value.
        //
        // Therefore, as soon as it sees a 0 (false, or not equal), it'll stop and return that
        $result = $actual->equal($expected)->min();

        Assert::assertSame(255.0, $result, "$path_to_result should be visually similar to $path_to_expected");
    }

    protected function setUp(): void
    {
        parent::setUp();
        $filesystem = new Filesystem();
        $filesystem->mkdir(self::OUTPUT_DIR);
    }

}

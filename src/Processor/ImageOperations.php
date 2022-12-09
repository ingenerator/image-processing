<?php
/**
 * @author    Craig Gosman <craig@ingenerator.com>
 */

namespace Ingenerator\ImageProcessing\Processor;


use Jcupitt\Vips\Access;
use Jcupitt\Vips\BandFormat;
use Jcupitt\Vips\BlendMode;
use Jcupitt\Vips\CompassDirection;
use Jcupitt\Vips\Extend;
use Jcupitt\Vips\Image;
use Jcupitt\Vips\Interpretation;
use JetBrains\PhpStorm\ArrayShape;

class ImageOperations implements ImageProcessorInterface
{
    public function createPlaceholder(int $width, int $height, string $output_path): void
    {
        Image::newFromBuffer(
            <<<SVG
            <svg width="$width" height="$height" >
                <rect width="$width" height="$height" stroke="black" stroke-width="5" fill="rgb(239, 239, 239)"/>
                <line x1="0" y1="0" x2="$width" y2="$height" stroke="black" stroke-width="3" />
                <line x1="$width" y1="0" x2="0" y2="$height" stroke="black" stroke-width="3" />
            </svg>
            SVG
        )->writeToFile($output_path);
    }

    #[ArrayShape([
        '0' => "int", // width
        '1' => "int", // height
    ])]
    public static function getImageSize(string $source_path, bool $auto_rotate = FALSE): array
    {
        // if image is PDF or SVG add options to specify DPI it should be rendered as
        $source_path .= match (Image::findLoad($source_path)) {
            'VipsForeignLoadPdfFile',
            'VipsForeignLoadSvgFile' => '[dpi=150]',
            default => ''
        };

        // read the image sequentially - we're only interested in the headers anyway
        $image = Image::newFromFile($source_path, ['access' => Access::SEQUENTIAL]);

        if ($auto_rotate) {
            $image = $image->autorot();
        }

        return [$image->width, $image->height];
    }

    public function thumbnail(string $source_path, string $output_path, array $operations): void
    {
        $image = Image::thumbnail(
            $source_path, $operations['scale']['width'],
            [
                'height'         => $operations['scale']['height'] ?? 10_000_000,
                'export-profile' => Interpretation::SRGB,
            ]
        );

        if (isset($operations['filter'])) {
            if ($operations['filter'] === 'greyscale') {
                $image = $image->colourspace(Interpretation::GREY16);
            }
        }
        if (isset($operations['crop'])) {
            $crop  = $operations['crop'];
            $image = $this->cropToSize($image, $crop['width'], $crop['height'], $crop['resize_method']);
        }
        if (isset($operations['pad'])) {
            $pad   = $operations['pad'];
            $image = $this->padInBox(
                $image,
                $pad['width'],
                $pad['height'],
                $pad['resize_method'],
                $pad['background']
            );
        }

        $options = match ($operations['save']['type']) {
            'jpg' => [
                // set JPEG quality factor
                'Q'               => $operations['save']['quality'] ?? 85,
                // generate custom coding table for image rather than using generic
                'optimize_coding' => TRUE,
                // strip tags and other metadata to reduce file size
                'strip'           => TRUE,
                // images with transparency are flattened with alpha channel becoming white
                'background'      => [255, 255, 255],
            ],
            'png' => [
                'Q'           => 85,
                'compression' => 4,
                // strip tags and other metadata to reduce file size
                'strip'       => TRUE,
                // enable quantization for 8bpp - these are thumbnails for web after all
                'palette'     => TRUE,
            ],
            default => [],
        };

        $image->writeToFile($output_path, $options);
    }

    private function cropToSize(Image $image, int $width, int $height, string $resize_method): Image
    {
        $top = match (TRUE) {
            str_contains($resize_method, 'top') => 0,
            str_contains($resize_method, 'bottom') => $image->height - $height,
            default => (int) round(($image->height - $height) / 2),
        };

        $left = match (TRUE) {
            str_contains($resize_method, 'left') => 0,
            str_contains($resize_method, 'right') => $image->width - $width,
            default => (int) round(($image->width - $width) / 2),
        };

        return $image->crop($left, $top, $width, $height);
    }

    private function padInBox(
        Image $image,
        int $width,
        int $height,
        string $resize_method,
        string $background
    ): Image {

        if ($image->bands < 3) {
            $image = $image->colourspace(Interpretation::SRGB);
        }

        if ( ! $image->hasAlpha() and $background === 'transparent') {
            $image = $image->bandjoin_const(255);
        }

        $new   = $this->create($width, $height, $background);
        $image = $image->gravity(
            $resize_method === 'fit-left' ? CompassDirection::WEST : CompassDirection::CENTRE,
            $width,
            $height
        );

        return $new->composite2($image, BlendMode::OVER);

//        return $image->gravity(
//            $resize_method === 'fit-left' ? CompassDirection::WEST : CompassDirection::CENTRE,
//            $width,
//            $height,
//            [
//                "extend"     => Extend::BACKGROUND,
//                'background' => ($background === 'transparent') ? [0, 0, 0, 0] : $this->hexToRgb($background),
//            ]
//        );
    }

    private function create(int $width, int $height, string $colour): Image
    {
        [$red, $green, $blue, $alpha] = ($colour === 'transparent') ? [0, 0, 0, 0] : $this->hexToRgb($colour);

        // Make a 1x1 pixel with the red channel and cast it to provided format.
        $pixel = Image::black(1, 1)->add($red)->cast(BandFormat::UCHAR);

        // Extend this 1x1 pixel to match the output dimensions.
        $image = $pixel->embed(0, 0, $width, $height, ['extend' => Extend::COPY]);

        // Ensure that the interpretation of the image is set.
        $image = $image->copy(['interpretation' => Interpretation::SRGB]);

        // Bandwise join the rest of the channels including the alpha channel.
        $image = $image->bandjoin([$green, $blue, $alpha]);

        return $image;
    }

    private function hexToRgb(string $hex): array
    {
        // remove preceding '#'
        $hex = ltrim($hex, '#');

        // turn short codes 'F90' into 'FF9900'
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        // split string every 2 chars and run them through hexdex
        $rgb   = array_map('hexdec', str_split($hex, 2));
        $rgb[] = 255;

        return $rgb;
    }
}

<?php
/**
 * @author    Craig Gosman <craig@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\ImageProcessing\Processor;

use Jcupitt\Vips\Image;
use Jcupitt\Vips\Interpretation;
use function array_map;

class ImageCompare
{
    public static function isSame(string $path_to_expected, string $path_to_actual): bool {
        [$expected, $actual] = array_map(static function ($path) {
            $image = Image::newFromFile($path, ['access' => 'sequential'])
                ->colourspace(Interpretation::SRGB);

            if ( ! $image->hasAlpha()) {
                $image = $image->bandjoin_const(255);
            }

            return $image;
        }, [$path_to_expected, $path_to_actual]);

        // assert images have same dimensions
        if ($expected->width.'x'.$expected->height !== $actual->width.'x'.$actual->height){
            return FALSE;
        }

        // Equal will give 0 or 255 for false or true. Min will start a set of threads
        // to pull pixels through the equals operator, but is smart enough to know that
        // for uchar pixels (what you'll get for equal), 0 is the minimum possible value.
        //
        // Therefore, as soon as it sees a 0 (false, or not equal), it'll stop and return that
        if ($actual->equal($expected)->min() !== 255.0){
            return FALSE;
        }

        return TRUE;
    }
}

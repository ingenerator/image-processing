<?php
/**
 * @author    Craig Gosman <craig@ingenerator.com>
 */

namespace test\unit\Ingenerator\ImageProcessing\Processor;

use Ingenerator\ImageProcessing\Processor\ImageOperations;

class ImageOperationsTest extends BaseImageProcessorTest
{
    protected function newSubject(): ImageOperations
    {
        return new ImageOperations();
    }
}

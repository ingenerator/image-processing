<?php
/**
 * @author    Craig Gosman <craig@ingenerator.com>
 */

namespace test\unit\Processor;

use Ingenerator\ImageProcessing\Processor\ImageOperations;

class ImageOperationsTest extends BaseImageProcessorTestCases
{
    protected function newSubject(): ImageOperations
    {
        return new ImageOperations();
    }
}

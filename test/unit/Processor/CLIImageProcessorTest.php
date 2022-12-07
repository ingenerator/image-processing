<?php
/**
 * @author    Craig Gosman <craig@ingenerator.com>
 */

namespace test\unit\Ingenerator\ImageProcessing\Processor;

use Ingenerator\ImageProcessing\Processor\CLIImageProcessor;

class CLIImageProcessorTest extends BaseImageProcessorTest
{
    protected function newSubject(): CLIImageProcessor
    {
        return new CLIImageProcessor();
    }
}

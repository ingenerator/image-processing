<?php
/**
 * @author    Craig Gosman <craig@ingenerator.com>
 */

namespace test\unit\Processor;

use Ingenerator\ImageProcessing\Processor\CLIImageProcessor;

class CLIImageProcessorTest extends BaseImageProcessorTestCases
{
    protected function newSubject(): CLIImageProcessor
    {
        return new CLIImageProcessor();
    }
}

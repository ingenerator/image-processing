<?php
/**
 * @author    Craig Gosman <craig@ingenerator.com>
 */

namespace Ingenerator\ImageProcessing\Processor;

use JetBrains\PhpStorm\ArrayShape;

interface ImageProcessorInterface
{
    public function createPlaceholder(int $width, int $height, string $output_path): void;

    #[ArrayShape([
        '0' => "int", // width
        '1' => "int", // height
    ])]
    public function getImageSize(string $source_path, bool $auto_rotate = FALSE): array;

    public function thumbnail(string $source_path, string $output_path, array $operations): void;
}

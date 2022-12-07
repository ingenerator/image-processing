<?php
/**
 * @author    Craig Gosman <craig@ingenerator.com>
 * @licence   proprietary
 */

require_once(__DIR__.'/../../vendor/autoload.php');

use Ingenerator\ImageProcessing\Processor\ImageOperations;
use Ingenerator\PHPUtils\StringEncoding\JSON;

$processor = new ImageOperations();
switch ($argv[1]) {
    case "getImageSize":
        [$script, $method, $source_path, $auto_rotate] = $argv;
        echo JSON::encode($processor->getImageSize($source_path,(bool) $auto_rotate));
        break;
    case "thumbnail":
        [$script, $method, $source_path, $output_path, $operations] = $argv;
        $processor->thumbnail($source_path, $output_path, JSON::decodeArray($operations));
        break;
    case "createPlaceholder":
        [$script, $method, $width, $height, $output_path] = $argv;
        $processor->createPlaceholder($width, $height, $output_path);
        break;
}

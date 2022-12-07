<?php
/**
 * @author    Craig Gosman <craig@ingenerator.com>
 */

if (is_file(__DIR__.'/../../vendor/autoload.php')) {
    require_once __DIR__.'/../../vendor/autoload.php';
} else {
    // when loaded as a library the autoloader will be 2 more dirs up
    require_once __DIR__.'/../../../../autoload.php';
}

use Ingenerator\ImageProcessing\Processor\ImageOperations;
use Ingenerator\PHPUtils\StringEncoding\JSON;

$processor = new ImageOperations();

// translate the CLI arguments to ImageOperations methods
switch ($argv[1]) {
    case "getImageSize":
        [$script, $method, $source_path, $auto_rotate] = $argv;
        echo JSON::encode($processor::getImageSize($source_path,(bool) $auto_rotate), FALSE).PHP_EOL;
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

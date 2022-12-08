<?php
/**
 * @author    Craig Gosman <craig@ingenerator.com>
 */

namespace Ingenerator\ImageProcessing\Processor;

use Ingenerator\PHPUtils\StringEncoding\JSON;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Process\Process;

class CLIImageProcessor implements ImageProcessorInterface
{
    public function createPlaceholder(int $width, int $height, string $output_path): void
    {
        self::runThruSymfonyProcess('createPlaceholder', $width, $height, $output_path);
    }

    public function thumbnail(string $source_path, string $output_path, array $operations): void
    {
        self::runThruSymfonyProcess('thumbnail', $source_path, $output_path, JSON::encode($operations, FALSE));
    }

    #[ArrayShape([
        '0' => "int", // width
        '1' => "int", // height
    ])]
    public static function getImageSize(string $source_path, bool $auto_rotate = FALSE): array
    {
        $process = self::runThruSymfonyProcess('getImageSize', $source_path, (string) $auto_rotate);

        return JSON::decodeArray($process->getOutput());
    }

    private static function runThruSymfonyProcess(string $method, ?string ...$args): Process
    {
        $process = new Process(command: ['php', __DIR__.'/process.php', $method, ...$args]);
        $process->mustRun();

        return $process;
    }
}

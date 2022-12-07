<?php
/**
 * @author    Craig Gosman <craig@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\ImageProcessing\Processor;

use Ingenerator\PHPUtils\StringEncoding\JSON;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Process\Process;

class CLIImageProcessor implements ImageProcessorInterface
{
    public function createPlaceholder(int $width, int $height, string $output_path): void
    {
        $this->runThruSymfonyProcess('createPlaceholder', $width, $height, $output_path);
    }

    public function thumbnail(string $source_path, string $output_path, array $operations): void
    {
        $this->runThruSymfonyProcess('thumbnail', $source_path, $output_path, JSON::encode($operations, FALSE));
    }

    #[ArrayShape([
        '0' => "int", // width
        '1' => "int", // height
    ])]
    public function getImageSize(string $source_path, bool $auto_rotate = FALSE): array
    {
        $process = $this->runThruSymfonyProcess('getImageSize', $source_path, (string) $auto_rotate);

        return JSON::decodeArray($process->getOutput());
    }

    private function runThruSymfonyProcess(string $method, ?string ...$args): Process
    {
        $process = new Process(command: ['php', __DIR__.'/process.php', $method, ...$args]);
        $process->mustRun();

        return $process;
    }
}

# Image Processing

## Install
```bash
composer require ingenerator/image-processing
```

## Quick-start
Operations can be run in three different ways:

### Interactively
```php
use Ingenerator\ImageProcessing\Processor\ImageOperations;
$processor = new ImageOperations;
['width', 'height'] = $processor->getImageSize('test/resources/porto_1024.jpg');
```

### As an independent PHP processes via symfony/process
```php
use Ingenerator\ImageProcessing\Processor\CLIImageProcessor;
$processor = new CLIImageProcessor;
['width', 'height'] = $processor->getImageSize('test/resources/porto_1024.jpg');
```

### Directly from the command line
```bash 
> php src/Processor/process.php getImageSize test/resources/porto_1024.jpg 0
[1024,786]
```

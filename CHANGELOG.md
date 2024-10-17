### Unreleased

### v2.2.0 (2024-10-17)

* Support `size` option on thumbnail() to control whether to upsize, downsize or both(default). See https://www.libvips.org/API/current/libvips-resample.html#vips-thumbnail

### v2.1.0 (2024-10-01)

* Support PHP 8.3

### v2.0.2 (2024-02-07)

* Support ingenerator/php-utils:^2.0
* Support symfony process ^7.0

### v2.0.1 (2023-08-17)

* Support symfony/process ^6 in addition to ^5

### v2.0.0 (2023-08-15)

* Support PHP8.2

### v1.0.2 (2023-04-19)

* Provide some sensible WebP compression settings and demonstrate webp conversion


### v1.0.1 (2023-03-16)

* Fix creating thumbnail from JPG with background colour option
  It was previously defaulting to black as we only add the alpha channel if the target background was 'transparent'. We should always add an alpha channel as we calculate the new background as RGBA and then flatten.

### v1.0.0 (2022-12-15)

* First version

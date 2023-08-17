### Unreleased

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

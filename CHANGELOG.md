### Unreleased

* Fix creating thumbnail from JPG with background colour option
  It was previously defaulting to black as we only add the alpha channel if the target background was 'transparent'. We should always add an alpha channel as we calculate the new background as RGBA and then flatten.

### v1.0.0 (2022-12-15)

* First version
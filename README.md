# ultimalib-php
PHP bindings for the ultima LTI validation service.

## Development
This project uses composer to manage its dependencies. After installing composer; the following commands can get you started:
* `php composer.phar install`
* `vendor/bin/phpunit`

## Usage
Composer has the ability to pull in dependencies from version control systems. This library can be included in composer with the following configuration; where `v1.0.0` is a valid release from this repository.

```json
# composer.json
"repositories": [
    {
        "url": "https://github.com/UQ-eLIPSE/ultimalib-php",
        "type": "vcs"
    }
],
"require": {
    "UQ-eLIPSE/ultimalib-php": "v1.0.0"
}
```

```php
# ./src/app.php
<?php
    require("../vendor/autoload.php");
    use Elipse\Ultima\LTIValidator;
    use Elipse\Ultima\LTIException;
    $validator = new LTIValidator("REMOTE_ENDPOINT", "APP_KEY");
    $result = $validator->validate("LAUNCH_URL", "LAUNCH_METHOD", "POST_REQUEST_ARRAY");
    var_dump($result);
?>
```

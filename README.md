# GoogleNewsPHP
Google News fetcher

## Install
```sh
composer require quiec/googlenewsphp
```

## Usage
```php
<?php
require __DIR__ . '/vendor/autoload.php';
$News = new Quiec\GoogleNews('tr', 'tr', 'BUSINESS', 100);
print_r($News->getNews());
```

## License
LGPL

## Author
[Yusuf Usta](https://github.com/quiec)
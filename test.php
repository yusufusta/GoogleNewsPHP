<?php
require __DIR__ . '/vendor/autoload.php';
$News = new Quiec\GoogleNews('tr', 'tr', 'BUSINESS', 100);
print_r($News->getNews());
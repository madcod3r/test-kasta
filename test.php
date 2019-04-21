#!/usr/bin/env php
<?php

use Curl\Curl;

require 'vendor/autoload.php';
require 'functions.php';


/**
 * Caching mechanism
 */
$categories = [];
$categoriesFile = __DIR__ . '/categories.data';

// get categories from cache
if (file_exists($categoriesFile)) {
    if ($categoriesSerialized = file_get_contents($categoriesFile)) {
        // get categories from cache
        $categories = unserialize($categoriesSerialized);
    }
}

// get categories from API if cache not exists
if (empty($categories)) {
    $curl = new Curl();
    $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
    $categories = getCategories($curl);

    // put categories in cache
    file_put_contents($categoriesFile, serialize($categories));
}

$countAllCategories = countOfCategories($categories);
$countSmallCategories = countOfSmallCategories($categories);

// print list of "small" categories
printCategories($categories);

// display percent of "small" categories
echo number_format(($countAllCategories / $countSmallCategories), 2) . "% ";
echo "(из $countAllCategories категорий)";
echo PHP_EOL;
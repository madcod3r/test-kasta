#!/usr/bin/env php
<?php

use Curl\Curl;

require 'vendor/autoload.php';

/**
 * Print Line
 *
 * @param $string
 */
function printLn($string = '') {
    echo $string . PHP_EOL;
}

/**
 * Print Error
 *
 * @param $errorMessage
 */
function printError($errorMessage) {
    printLn("Error: $errorMessage");
}

function error() {
    printError('Error in JSON');
    die;
}


/**
 * Here starts a test work
 */
$curl = new Curl();
$curl->get('https://modnakasta.ua/api/v2/market/menu/', [
    'v' => 1
]);

if ($curl->error) {
    printError("\n\tcode: $curl->error_code\n\tmessage: $curl->error_message");
    die;
}
else {
    $categories = json_decode($curl->response, true);

    if (count($categories) > 0) {

        $countOfCategories = count($categories['nodes']);

        printLn('count of root categories: ' . count($categories['nodes']));
        printLn();
        printLn('Root categories:');

        if ($countOfCategories > 0) {
            foreach ($categories['nodes'] as $i => $categoryNode) {
                printLn("category: {$categoryNode['q']} childs? {$categoryNode['has-children']}");
            }
        } else {
            error();
        }
    } else {
        error();
    }
}
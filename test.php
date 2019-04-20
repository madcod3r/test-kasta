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

function isCountOfProductsMoreThen($curl, $categoryQuery, $limit = 10)
{
    // send get request
    $curl->get("https://modnakasta.ua/api/v2/product-list$categoryQuery");

    $productList = json_decode($curl->response, true);

    if ((int)count($productList['product-ids']) > $limit) {
        return 1;
    }

    return 0;
}

function getCategories($curl, $parentId = null)
{
    $categories = [];

    $getParams = ['v' => 1];

    if ($parentId) {
        $getParams['parent-uuid'] = $parentId;
    }

    // send get request
    $curl->get('https://modnakasta.ua/api/v2/market/menu/', $getParams);

    // if curl throws an error
    if ($curl->error) {
        // display error
        printError("\n\tcode: $curl->error_code\n\tmessage: $curl->error_message");
        return [];
    } else {
        // if all is good
        $categoriesFromAPI = json_decode($curl->response, true);

        if (is_array($categoriesFromAPI) && count($categoriesFromAPI) > 0) {

            $countOfCategories = count($categoriesFromAPI['nodes']);

            if ($countOfCategories > 0) {

                foreach ($categoriesFromAPI['nodes'] as $i => $categoryFromAPINode) {
                    // create category array
                    $category = [
                        'uuid' => $categoryFromAPINode['uuid'],
                        'name' => $categoryFromAPINode['name'],
                        'url' => $categoryFromAPINode['url'],
                        'children' => []
                    ];

                    if ($parentId) {
                        $category['parent'] = $categoriesFromAPI;
                    }

                    // if a category has children - get them by recursion
                    if ((bool)$categoryFromAPINode['has-children'] === true) {
                        $category['children'] = getCategories($curl, $categoryFromAPINode['uuid']);
                    }
                    // if a category does not have children - get count of products
                    else {
                        $category['productsMoreThenTen'] = isCountOfProductsMoreThen($curl, $categoryFromAPINode['q']);
                    }
                    $categories[] = $category;
                }

            } else {
                error();
            }
        } else {
            error();
        }
    }
    return $categories;
}

/**
 * Print categories
 *
 * @param $categories
 * @param array $parentCategory
 * @param int $count
 * @return array
 */
function printCategories($categories, $parentCategory = [], &$count = 1)
{
    // create breadcrumbs for category
    $breadcrumbs = [];
    foreach ($categories as $category) {

        // if a category had children - get child categories
        if (isset($category['children']) && count($category['children']) > 0) {
            $breadcrumbs[$category['url']]['category'] = [
                'name' => $category['name'],
                'url' => $category['url'],
                'percent' => null,
            ];
            $breadcrumbs[$category['url']]['parent'] = $parentCategory;
            $breadcrumbs[$category['url']]['child'] =
                printCategories($category['children'], $category, $count);
        }
        // if no more children - print category
        else {
            // print categories which are only has less then 10 products
            if ($category['productsMoreThenTen'] === 0) {

                //echo $count++ . '. ';

                // display parent categories names if exists
                if (isset($category['parent'])) {
                    foreach ($category['parent']['breadcrumbs'] as $breadcrumb) {
                        echo $breadcrumb['name'] . ' > ';
                    }
                }

                // display category name
                echo "{$category['name']} | ";
                // display url
                echo "https://modnakasta.ua/market/{$category['url']}";
                // display end of a line
                echo PHP_EOL;
            }
        }
    }

    return $breadcrumbs;
}

function countOfCategories($categories) {
    $count = 0;
    $count += count($categories);

    foreach ($categories as $category) {
        if ($category['children']) {
            $count += countOfCategories($category['children']);
        }
    }

    return $count;
}

function countOfSmallCategories($categories) {
    $count = 0;
    foreach ($categories as $category) {

        if (count($category['children']) > 0) {
            $count += countOfSmallCategories($category['children']);
        } else {
            if ($category['productsMoreThenTen'] === 0) {
                $count++;
            }
        }
    }

    return $count;
}


/**
 * Here starts a test work
 */
$categories = [];
$categoriesFile = __DIR__ . '/categories.data';
if (file_exists($categoriesFile)) {
    if ($categoriesSerialized = file_get_contents($categoriesFile)) {
        // get categories from cache
        $categories = unserialize($categoriesSerialized);
    }
}

if (empty($categories)) {
    $curl = new Curl();
    $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
    $categories = getCategories($curl);

    // put categories in cache
    file_put_contents($categoriesFile, serialize($categories));
}

$countAllCategories = countOfCategories($categories);
$countSmallCategories = countOfSmallCategories($categories);

printCategories($categories);

// display percent of categories
echo number_format(($countAllCategories / $countSmallCategories), 2) . "% ";
echo "(из " . $countAllCategories . " категорий)";
echo PHP_EOL;
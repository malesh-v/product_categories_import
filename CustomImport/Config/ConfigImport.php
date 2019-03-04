<?php

namespace Malesh\CustomImport\Config;

class ConfigImport
{
    const CATEGORIES_CSV_FILENAME = 'categories.csv';
    const PRODUCTS_CSV_FILENAME = 'products.csv';
    const VALID_CATEGORIES_COLUMN_NAMES = ['name', 'active', 'parent'];
    const VALID_PRODUCTS_COLUMN_NAMES = [
        'name', 'sku', 'qty', 'visibility', 'price', 'attack_length', 'palm_size', 'is_extra',	'category'
    ];
}

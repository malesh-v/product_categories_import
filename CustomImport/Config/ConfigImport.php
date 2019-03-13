<?php

namespace Malesh\CustomImport\Config;

class ConfigImport
{
    //file names
    const CATEGORIES_CSV_FILENAME = 'categories.csv';
    const PRODUCTS_CSV_FILENAME = 'products.csv';

    //valid column names
    const VALID_CATEGORIES_COLUMN_NAMES = ['name', 'active', 'parent'];
    const VALID_PRODUCTS_COLUMN_NAMES = [
        'name', 'qty', 'visibility', 'price', 'attack_length', 'palm_size', 'is_extra', 'category'
    ];

    //attributes code
    const ATTACK_LENGTH_CODE = 'attack_length';
    const PALM_SIZE_CODE = 'palm_size';
    const EXTRA_CODE = 'is_extra';
}

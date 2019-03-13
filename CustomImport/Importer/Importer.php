<?php

namespace Malesh\CustomImport\Importer;

use Malesh\CustomImport\Config\ConfigImport;
use Malesh\CustomImport\Importer\Entity\CategoryImporterFactory;
use Malesh\CustomImport\Importer\Entity\ProductImporterFactory;

class Importer
{
    /** @var \Malesh\CustomImport\Importer\Entity\CategoryImporterFactory */
    private $categoryImporterFactory;

    /** @var \Malesh\CustomImport\Importer\Entity\ProductImporterFactory */
    private $productImporterFactory;

    /** @var \Malesh\CustomImport\Provider\CsvProvidersCreator */
    private $csvProviders;

    /** @var \Malesh\CustomImport\Importer\Entity\ProductImporter */
    private $productImporter;

    public function __construct(
        CategoryImporterFactory $categoryImporterFactory,
        ProductImporterFactory $productImporterFactory,
        $csvProviders
    )
    {
        $this->categoryImporterFactory = $categoryImporterFactory;
        $this->productImporterFactory = $productImporterFactory;
        $this->csvProviders = $csvProviders;
    }

    public function import()
    {
        $providers = $this->csvProviders->getProviders();

        foreach ($providers as $key => $provider) {
            switch ($key) {
                case ConfigImport::CATEGORIES_CSV_FILENAME:
                    $this->categoryImporterFactory->create()
                         ->createCategories($provider->getPreparedData());
                    break;
                case ConfigImport::PRODUCTS_CSV_FILENAME:
                    $this->productImporter = $this->productImporterFactory->create([
                        'productsData' => $provider->getPreparedData()
                    ]);
                    break;
            }
        }

        return $this;
    }

    public function getProductImporter()
    {
        return $this->productImporter;
    }
}

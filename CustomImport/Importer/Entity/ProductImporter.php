<?php

namespace Malesh\CustomImport\Importer\Entity;

use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\Product\Visibility;
use Malesh\CustomImport\Model\Attribute;
use Malesh\CustomImport\Config\ConfigImport;

class ProductImporter
{
    /** @var \Magento\Catalog\Api\Data\ProductInterfaceFactory */
    private $productFactory;

    /** @var /Magento\Catalog\Api\ProductRepositoryInterface */
    private $productRepository;

    /**  @var /Magento\Catalog\Model\Product */
    private $productModel;

    /** @var /Malesh\CustomImport\Importer\Entity\CategoryImporter */
    private $categoryImporter;

    /** @var /Malesh\CustomImport\Model\Attribute */
    private $attributeModel;

    /** @var array */
    private $defaultProductData;

    /** @var array */
    private $configurableProductData;

    public function __construct(
        ProductInterfaceFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        ProductModel $productModel,
        CategoryImporter $categoryImporter,
        Attribute $attributeModel,
        $productsData
    )
    {
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->productModel = $productModel;
        $this->categoryImporter = $categoryImporter;
        $this->attributeModel = $attributeModel;
        $this->setDefaultProductValues();

        $this->import($productsData);
    }

    public function getConfigurableProductData()
    {
        return $this->configurableProductData;
    }

    private function setDefaultProductValues()
    {
        $this->defaultProductData = [
            'type' => Type::TYPE_VIRTUAL,
            'status' => Status::STATUS_ENABLED,
            'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
            'attribute_set_id' => $this->productModel->getDefaultAttributeSetId(),
            'store_id' => 0,
        ];
    }

    private function import($productsData)
    {
        $preparedProductsData = $this->prepareData($productsData);

        $configurableData = [];

        foreach ($preparedProductsData as $name => $confItems) {

            foreach ($confItems as $item) {
                /** @var \Magento\Catalog\Api\Data\ProductInterface $product */
                $product = $this->productFactory->create();
                $product->setData($this->getPreparedProductData($item));

                $product->setCustomAttributes([
                    ConfigImport::ATTACK_LENGTH_CODE => $this->attributeModel->getAttributeLabel(
                        ConfigImport::ATTACK_LENGTH_CODE,
                        $item[ConfigImport::ATTACK_LENGTH_CODE]
                    ),
                    ConfigImport::PALM_SIZE_CODE => $this->attributeModel->getAttributeLabel(
                        ConfigImport::PALM_SIZE_CODE,
                        $item[ConfigImport::PALM_SIZE_CODE]
                    ),
                    ConfigImport::EXTRA_CODE => $item[ConfigImport::EXTRA_CODE]
                ]);

                $product = $this->productRepository->save($product);

                $configurableData[$name][] = $product->getId();
            }
        }

        $this->configurableProductData = $configurableData;
    }

    private function getPreparedProductData($item)
    {
        $categoryIds = $this->categoryImporter->getCategoriesByName($item['category'])->getAllIds();
        $categoryId = empty($categoryIds) ? null : $categoryIds[0];

        $item['category_ids'] = $categoryId;
        $item['sku'] = $this->getGeneratedSku($item);
        $item['stock_data'] = [
            'qty' => $item['qty'],
            'is_in_stock' => 1,
        ];

        return array_merge($this->defaultProductData, $item);
    }

    private function prepareData($productsData)
    {
        $data = [];

        foreach ($productsData as $item)
        {
            $data[$item['name']][] = $item;
        }

        return $data;
    }

    private function getGeneratedSku($item)
    {
        $sku = $item['name'];
        $sku .= '-attack_length-' . $item['attack_length'];
        $sku .= '-palm_size-' . $item['palm_size'];
        $sku .= '-is_extra-' . $item['is_extra'];

        return $sku;
    }
}

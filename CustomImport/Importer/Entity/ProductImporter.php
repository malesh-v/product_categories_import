<?php

namespace Malesh\CustomImport\Importer\Entity;

use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\Product\Visibility;
use Malesh\CustomImport\Model\Attribute;

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

    private function setDefaultProductValues()
    {
        $this->defaultProductData = [
            'type' => Type::TYPE_SIMPLE,
            'status' => Status::STATUS_ENABLED,
            'attribute_set_id' => $this->productModel->getDefaultAttributeSetId(),
            'visibility' => Visibility::VISIBILITY_NOT_VISIBLE,
        ];
    }

    private function import($productsData)
    {
        foreach ($productsData as $item) {
            /** @var \Magento\Catalog\Api\Data\ProductInterface $product */
            $product = $this->productFactory->create();
            $product->setData($this->getPreparedData($item));

            $product->setCustomAttributes([
                'attack_length' => $this->attributeModel->getAttributeLabel(
                    'attack_length', $item['attack_length']
                ),
                'palm_size' => $this->attributeModel->getAttributeLabel(
                    'palm_size', $item['palm_size']
                ),
                'is_extra'=> $item['is_extra']
            ]);

            $this->productRepository->save($product);
        }
    }

    private function getPreparedData($item)
    {
        $categoryIds = $this->categoryImporter->getCategoriesByName($item['category'])->getAllIds();
        $categoryId = count($categoryIds) > 0 ? $categoryIds[0] : null;

        $item['category_ids'] = $categoryId;

        $item['stock_data'] = [
            'qty' => $item['qty'],
            'is_in_stock' => 1,
        ];

        return array_merge($this->defaultProductData, $item);
    }
}

<?php

namespace Malesh\CustomImport\Model;

use Magento\Eav\Model\Config;
use Magento\Store\Model\StoreManagerInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Attribute as ConfigurableAttributeResource;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\Product as ProductModel;
use Psr\Log\LoggerInterface;

class ConfigurableProductBuilder
{
    /** @var \Magento\Catalog\Api\Data\ProductInterfaceFactory  */
    private $productFactory;

    /** @var \Magento\Catalog\Model\Product */
    private $productModel;

    /** @var \Magento\Catalog\Api\ProductRepositoryInterface */
    private $productRepository;

    /** @var \Magento\Eav\Model\Config */
    private $eavConfig;

    /** @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute */
    private $attributeModel;

    /** @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory */
    private $categoryCollectionFactory;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    private $storeManagerInterface;

    /** @var string */
    private $categoryName;

    /** @var string */
    private $info;

    /** @var \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Attribute */
    protected $optionResource;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var \Malesh\CustomImport\Importer\Entity\ProductImporter */
    private $productImporter;

    public function __construct(
        ProductInterfaceFactory $productFactory,
        ProductModel $productModel,
        ProductRepositoryInterface $productRepository,
        Config $eavConfig,
        Attribute $attributeModel,
        CategoryCollectionFactory $categoryCollectionFactory,
        StoreManagerInterface $storeManagerInterface,
        ConfigurableAttributeResource $optionResource,
        LoggerInterface $logger,
        $importer
    )
    {
        $this->productFactory = $productFactory;
        $this->productModel = $productModel;
        $this->productRepository = $productRepository;
        $this->eavConfig = $eavConfig;
        $this->attributeModel = $attributeModel;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->optionResource = $optionResource;
        $this->logger = $logger;
        $this->productImporter = $importer->getProductImporter();

        $this->create();
    }

    public function create()
    {
        $productsData = $this->productImporter->getConfigurableProductData();

        foreach ($productsData as $confProductName => $associatedProductIds) {
            $product = $this->createConfigurable($confProductName);
            $attributeModel = $this->attributeModel;
            $position = 0;

            foreach ($this->getConfigurableAttributes() as $attributeId) {

                $attributeModel = $attributeModel->setData([
                    'attribute_id' => $attributeId,
                    'product_id' => $product->getId(),
                    'position' => $position++,
                ]);

                try {
                    $this->optionResource->save($attributeModel);
                }
                catch (\Exception $e) {
                    $this->logger->warning($e->getMessage());
                }
            }

            $product->setTypeId(Configurable::TYPE_CODE);
            $product->setAssociatedProductIds($associatedProductIds);

            try {
                $product->save();
            } catch (\Exception $e) {
                $txt = 'Configurable product with ' . implode(',', $associatedProductIds) . ' product id\'s wasn\'t  created';
                $this->logger->warning($txt);
            }

            $this->generateMessage($product);
        }

        return $this;
    }

    public function getInfo()
    {
        return $this->info;
    }

    private function generateMessage($product)
    {
        $configurableName = $product->getName();
        $this->info .= 'Configurable product with name "' . $configurableName . '" was saved to "' . $this->categoryName . '". ';
    }

    private function createConfigurable($name)
    {
        $product = $this->productFactory->create();
        $categoryId = $this->getCategoryId();

        $product->setData([
            'name' => $name,
            'sku' => $name,
            'visibility' => Visibility::VISIBILITY_BOTH,
            'status' => Status::STATUS_ENABLED,
            'price' => '50',
            'attribute_set_id' => $this->productModel->getDefaultAttributeSetId(),
            'category_ids' => $categoryId,
            'stock_data' => [
                'qty' => '50',
                'is_in_stock' => 1,
            ]
        ]);

        try {
            $product = $this->productRepository->save($product);
        } catch (\Exception $e) {
            $this->logger->warning('Configurable product ' . $product->getName() . ' wasn\'t created');
        }

        return $product;
    }

    private function getConfigurableAttributes()
    {
        $prodAttributes = $this->eavConfig->getEntityAttributes(Product::ENTITY);

        return array(
            $prodAttributes['attack_length']->getId(),
            $prodAttributes['palm_size']->getId(),
            $prodAttributes['is_extra']->getId(),
        );
    }

    private function getCategoryId()
    {
        $parentId = $this->storeManagerInterface->getStore()->getRootCategoryId();

        $category = $this->categoryCollectionFactory
                ->create()
                ->addFieldToFilter(
                    'path', array('like' => "%/{$parentId}/%")
                )
                ->getLastItem();
        $this->categoryName = $category->getAttributeDefaultValue('name');

        return $category->getId();
    }
}

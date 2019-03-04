<?php

namespace Malesh\CustomImport\Model;

use Magento\Eav\Model\Config;
use Magento\Store\Model\StoreManagerInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\Product as ProductModel;

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

    /** @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory */
    private $productCollectionFactory;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    private $storeManagerInterface;

    /** @var string */
    private $categoryName;

    /** @var string */
    private $info;

    public function __construct(
        ProductInterfaceFactory $productFactory,
        ProductModel $productModel,
        ProductRepositoryInterface $productRepository,
        Config $eavConfig,
        Attribute $attributeModel,
        CategoryCollectionFactory $categoryCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        StoreManagerInterface $storeManagerInterface
    )
    {
        $this->productFactory = $productFactory;
        $this->productModel = $productModel;
        $this->productRepository = $productRepository;
        $this->eavConfig = $eavConfig;
        $this->attributeModel = $attributeModel;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManagerInterface = $storeManagerInterface;
    }

    public function create()
    {
        $product = $this->createConfigurable();
        $associatedProductIds = $this->getAvailableProductsIds();
        $attributeModel = $this->attributeModel;

        $position = 0;

        foreach ($this->getConfigurableAttributes() as $attributeId) {

            $attributeModel = $attributeModel->setData([
                'attribute_id' => $attributeId,
                'product_id' => $product->getId(),
                'position' => $position++,
            ]);

            try { $attributeModel->save(); }
            catch (\Exception $e) {}
        }

        $product->setTypeId(Configurable::TYPE_CODE);
        $product->setAssociatedProductIds($associatedProductIds);

        try {
            $product->save();
        } catch (\Exception $e) {}
        $this->generateMessage($product);

        return $this;
    }

    public function getInfo()
    {
        return $this->info;
    }

    private function generateMessage($product)
    {
        $configurableName = $product->getName();
        $this->info = 'Configurable product with name "' . $configurableName . '" was saved to "' . $this->categoryName . '"';
    }

    private function getAvailableProductsIds()
    {
        $twoRandomProducts = [];

        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToFilter('type_id', Type::TYPE_SIMPLE)
                   ->addAttributeToSelect(['attack_length', 'palm_size', 'is_extra'], '> 0');
        $productsIds = $collection->getAllIds();

        //generate two random product ids
        $rand_keys = array_rand($productsIds, 2);
        array_push($twoRandomProducts, $productsIds[$rand_keys[0]], $productsIds[$rand_keys[1]]);

        return $twoRandomProducts;
    }

    private function createConfigurable()
    {
        $product = $this->productFactory->create();
        $randName = 'configurable ' . mt_rand(2, 10);
        $categoryId = $this->getCategoryId();

        $product->setData([
            'name' => $randName,
            'sku' => $randName,
            'visibility' => Visibility::VISIBILITY_BOTH,
            'price' => '50',
            'attribute_set_id' => $this->productModel->getDefaultAttributeSetId(),
            'category_ids' => $categoryId,
            'status' => Status::STATUS_ENABLED,
            'stock_data' => [
                'qty' => '50',
                'is_in_stock' => 1,
            ]
        ]);

        return $this->productRepository->save($product);
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

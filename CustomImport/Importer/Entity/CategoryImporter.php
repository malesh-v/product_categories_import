<?php

namespace Malesh\CustomImport\Importer\Entity;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;

class CategoryImporter
{
    /** @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory */
    private $categoryCollectionFactory;

    /** @var \Magento\Catalog\Model\CategoryFactory */
    private $categoryFactory;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManagerInterface;

    /** @var \Magento\Catalog\Model\CategoryRepository */
    private $categoryRepository;

    public function __construct(
        CategoryFactory $categoryFactory,
        StoreManagerInterface $StoreManagerInterface,
        CategoryRepository $categoryRepository,
        CollectionFactory $collectionFactory
    )
    {
        $this->categoryRepository = $categoryRepository;
        $this->categoryFactory = $categoryFactory;
        $this->storeManagerInterface = $StoreManagerInterface;
        $this->categoryCollectionFactory = $collectionFactory;
    }

    public function createCategories($data, $parentCategoryRoot = true)
    {
        $subCategoriesArray = [];
        $parentId = $this->storeManagerInterface->getStore()->getRootCategoryId();

        foreach ($data as $value) {
            if ($this->getCategoriesByName($value['name'])->count() > 0) { continue; }

            if ($value['parent'] && $parentCategoryRoot) {
                $subCategoriesArray[] = $value;
                continue;
            }

            if ($value['parent']) {
                $idParent = $this->getCategoriesByName($value['parent'])->getAllIds();
                if (!count($idParent)) { continue; }
                $parentId = $idParent[0];
            }

            $categoryModel = $this->categoryFactory->create();
            $categoryModel->setData([
                'name' => $value['name'],
                'parent_id' => (int) $parentId,
                'is_active' => (bool) $value['active']
            ]);

            $this->categoryRepository->save($categoryModel);
        }

        if (count($subCategoriesArray) > 0 ) {
            $this->createCategories($subCategoriesArray, false);
        }
    }

    public function getCategoriesByName($name)
    {
        $collection = $this->categoryCollectionFactory->create()->addAttributeToFilter('name', $name);

        return $collection;
    }
}
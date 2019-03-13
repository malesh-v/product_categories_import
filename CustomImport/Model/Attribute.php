<?php

namespace Malesh\CustomImport\Model;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;

class Attribute
{
    /** @var \Magento\Eav\Model\Config  */
    private $eavConfig;

    /** @var \Magento\Eav\Api\AttributeOptionManagementInterface  */
    private $attributeOptionManagement;

    /** @var \Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory  */
    private $optionLabelFactory;

    /** @var \Magento\Eav\Api\Data\AttributeOptionInterfaceFactory  */
    private $optionFactory;

    public function __construct(
        Config $eavConfig,
        AttributeOptionManagementInterface $attributeOptionManagement,
        AttributeOptionLabelInterfaceFactory $optionLabelFactory,
        AttributeOptionInterfaceFactory $optionFactory
    ) {
        $this->eavConfig                    = $eavConfig;
        $this->attributeOptionManagement    = $attributeOptionManagement;
        $this->optionLabelFactory           = $optionLabelFactory;
        $this->optionFactory                = $optionFactory;
    }

    public function getAttributeLabel($attributeCode, $label)
    {
        //check label exist
        if (!$this->getLabel($attributeCode, $label)) {
            /** @var \Magento\Eav\Model\Entity\Attribute\OptionLabel $optionLabel */
            $optionLabel = $this->optionLabelFactory->create();
            $optionLabel->setStoreId(0);
            $optionLabel->setLabel($label);

            $option = $this->optionFactory->create();
            $option->setLabel($optionLabel);
            $option->setStoreLabels([$optionLabel]);
            $option->setIsDefault(false);

            $this->attributeOptionManagement->add(
                Product::ENTITY,
                $attributeCode,
                $option
            );
        }

        return $this->getLabel($attributeCode, $label);
    }

    private function getLabel($attributeCode, $label)
    {
        return $this->eavConfig
                    ->getAttribute(Product::ENTITY, $attributeCode)
                    ->getSource()
                    ->getOptionId($label);
    }
}

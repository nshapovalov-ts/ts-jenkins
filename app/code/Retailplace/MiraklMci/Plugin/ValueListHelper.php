<?php

namespace Retailplace\MiraklMci\Plugin;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttribute;
use Mirakl\Mci\Helper\ValueList;

class ValueListHelper
{
    /**
     * @param ValueList $subject
     * @param bool $result
     * @param EavAttribute $attribute
     * @return bool
     */
    public function afterIsAttributeExportable(
        ValueList $subject,
        $result,
        EavAttribute $attribute
    ) {
        if ($attribute->getAttributeCode() == 'item_variant') {
            return false;
        }
        return $result;
    }
}

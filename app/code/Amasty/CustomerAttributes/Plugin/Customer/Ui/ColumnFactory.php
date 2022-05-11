<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_CustomerAttributes
 */

namespace Amasty\CustomerAttributes\Plugin\Customer\Ui;

class ColumnFactory
{
    /**
     * set magento data model for checkxoxes and radios
     * @param $subject
     * @param $result
     * @return mixed
     */
    public function beforeCreate($subject, $attributeData, $columnName, $context, $config = []){
        switch ($attributeData['frontend_input']) {
            case 'selectimg':
            case 'selectgroup':
                $config['dataType'] = 'select';
                $config['component'] = 'Magento_Ui/js/grid/columns/select';
                $attributeData['frontend_input'] = 'select';
                break;
            case 'multiselectimg':
                $config['dataType'] = 'select';
                $config['component'] = 'Magento_Ui/js/grid/columns/select';
                $attributeData['frontend_input'] = 'multiselect';
                break;
        }

        return [$attributeData, $columnName, $context,  $config];
    }
}

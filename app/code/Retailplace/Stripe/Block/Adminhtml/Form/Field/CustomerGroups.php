<?php
/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Stripe\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\CatalogInventory\Block\Adminhtml\Form\Field\Customergroup;
use Magento\Framework\Exception\LocalizedException;

class CustomerGroups extends AbstractFieldArray
{
    /**
     * @var Customergroup
     */
    protected $_groupRenderer;

    /**
     * Retrieve group column renderer
     *
     * @return Customergroup
     * @throws LocalizedException
     */
    protected function _getGroupRenderer(): Customergroup
    {
        if (!$this->_groupRenderer) {
            $this->_groupRenderer = $this->getLayout()->createBlock(
                Customergroup::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
            $this->_groupRenderer->setClass('customer_group_select admin__control-select');
        }
        return $this->_groupRenderer;
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareToRender()
    {
        $this->addColumn('id', ['label' => __('ID'), 'renderer' => $this->_getGroupRenderer()]);
        $this->addColumn('value', [
            'label' => __('Max Credit Limit'),
            'class' => 'required-entry validate-number validate-greater-than-zero admin__control-text'
        ]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Max Credit Limit');
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @return void
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row)
    {
        $optionExtraAttr = [];
        $optionExtraAttr['option_' . $this->_getGroupRenderer()->calcOptionHash($row->getData('id'))] =
            'selected="selected"';
        $row->setData(
            'option_extra_attrs',
            $optionExtraAttr
        );
    }
}

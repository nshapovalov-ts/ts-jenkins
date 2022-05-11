<?php

/**
 * Retailplace_ChannelPricing
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\ChannelPricing\Block\Adminhtml\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\BlockInterface;

/**
 * Class AttributesVisibility
 */
class AttributesVisibility extends AbstractFieldArray
{
    /** @var \Magento\Framework\Serialize\Serializer\Json */
    private $serializer;

    /** @var \Magento\Framework\View\Element\BlockInterface */
    private $attributesField;

    /** @var \Magento\Framework\View\Element\BlockInterface */
    private $groupsField;

    /**
     * AttributesVisibility constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     * @param array $data
     */
    public function __construct(
        Context $context,
        Json $serializer,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->serializer = $serializer;
    }

    /**
     * Get Array of Mapped Values
     *
     * @param string|null $value
     * @return array
     */
    public function getValuesArray(?string $value): array
    {
        $dataArray = [];
        if ($value) {
            try {
                $data = $this->serializer->unserialize($value);
            } catch (\Exception $ex) {
                $data = [];
                $this->_logger->error($ex->getMessage());
            }

            foreach ($data as $row) {
                $dataArray[] = [
                    'attribute_code' => $row['attribute_code'],
                    'groups' => $row['groups']
                ];
            }
        }

        return $dataArray;
    }

    /**
     * Prepare Render
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareToRender()
    {
        $this->addColumn('attribute_code', [
            'label' => __('Attribute Name'),
            'renderer' => $this->getAttributesField()
        ]);

        $this->addColumn('groups', [
            'label' => __('Groups to show Attribute'),
            'renderer' => $this->getGroupsField(),
            'extra_params' => 'multiple="multiple"'
        ]);


        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Option');
    }

    /**
     * Get Attributes list Field
     *
     * @return \Magento\Framework\View\Element\BlockInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getAttributesField(): BlockInterface
    {
        if (!$this->attributesField) {
            $this->attributesField = $this->getLayout()->createBlock(
                AttributesField::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }

        return $this->attributesField;
    }

    /**
     * @return \Magento\Framework\View\Element\BlockInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getGroupsField(): BlockInterface
    {
        if (!$this->groupsField) {
            $this->groupsField = $this->getLayout()->createBlock(
                GroupsField::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }

        return $this->groupsField;
    }

    /**
     * Prepare Row
     *
     * @param \Magento\Framework\DataObject $row
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row)
    {
        $options = [];

        $options['option_' . $this->getAttributesField()->calcOptionHash(
            $row->getData('attribute_code')
        )] = 'selected="selected"';

        $groups = $row->getData('groups');
        if (count($groups)) {
            foreach ($groups as $group) {
                $options['option_' . $this->getGroupsField()->calcOptionHash($group)] = 'selected="selected"';
            }
        }

        $row->setData('option_extra_attrs', $options);
    }
}

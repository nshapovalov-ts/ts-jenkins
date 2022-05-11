<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Block\Widget;

use Amasty\CustomerAttributes\Helper\Image as ImageHelper;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Block\Widget\AbstractWidget;
use Magento\Customer\Helper\Address;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\View\Element\Template\Context;

class AbstractWidgetOption extends AbstractWidget
{
    /**
     * @var ImageHelper
     */
    private $imageHelper;

    /**
     * @var string|null
     */
    protected $attributeCode;

    /**
     * @var CurrentCustomer
     */
    protected $currentCustomer;

    /**
     * AccountAbstractWidget constructor.
     * @param Context $context
     * @param Address $addressHelper
     * @param CurrentCustomer $currentCustomer
     * @param ImageHelper $imageHelper
     * @param CustomerMetadataInterface $customerMetadata
     * @param array $data
     */
    public function __construct(
        Context $context,
        Address $addressHelper,
        CurrentCustomer $currentCustomer,
        ImageHelper $imageHelper,
        CustomerMetadataInterface $customerMetadata,
        array $data = []
    ) {
        $this->currentCustomer = $currentCustomer;
        $this->imageHelper = $imageHelper;
        parent::__construct($context, $addressHelper, $customerMetadata, $data);
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        $attribute = $this->getAttribute();
        $customerValues = $this->getCustomerValues();
        $options = $attribute->getOptions();
        $data = [];
        foreach ($options as $option) {
            if (empty($option->getValue())) {
                continue;
            }
            $selected = false;
            if (is_array($customerValues) && in_array($option->getValue(), $customerValues)) {
                $selected = true;
            }
            $data[] = [
                'label' => $option->getLabel(),
                'value' => $option->getValue(),
                'selected' => $selected,
                'icon' => $this->imageHelper->getIconUrl($option->getValue())
            ];
        }

        return $data;
    }

    /**
     * @return \Magento\Customer\Api\Data\AttributeMetadataInterface|null
     */
    public function getAttribute()
    {
        return $this->_getAttribute($this->attributeCode);
    }

    /**
     * @return false|string[]
     */
    public function getCustomerValues()
    {
        $attr = $this->currentCustomer->getCustomer()->getCustomAttribute($this->attributeCode);
        if ($attr) {
            return explode(',', $attr->getValue());
        }
        return false;
    }

    /**
     * @param $attributeCode
     */
    public function setAttributeCode($attributeCode)
    {
        $this->attributeCode = $attributeCode;
    }

    /**
     * Retrieve store attribute label
     *
     * @return string
     */
    public function getStoreLabel()
    {
        $attribute = $this->getAttribute();
        return $attribute ? __($attribute->getStoreLabel()) : '';
    }

    /**
     * Check if attribute marked as required
     *
     * @return bool
     */
    public function isRequired()
    {
        return $this->getAttribute() ? (bool)$this->getAttribute()->isRequired() : false;
    }
}

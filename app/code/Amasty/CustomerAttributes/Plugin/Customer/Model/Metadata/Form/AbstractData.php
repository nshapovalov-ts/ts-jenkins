<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_CustomerAttributes
 */


namespace Amasty\CustomerAttributes\Plugin\Customer\Model\Metadata\Form;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class AbstractData
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * AbstractData constructor.
     * @param TimezoneInterface $timezone
     */
    public function __construct(TimezoneInterface $timezone)
    {
        $this->timezone = $timezone;
    }

    /**
     * Check if attribute is a custom and value wasn't proceed from form (attribute is hidden)
     * then set old value to fix attribute value removing.
     * Change date attribute to fix bugs with different date formats
     *
     * @param \Magento\Customer\Model\Metadata\Form\AbstractData $subject
     * @param array|string $value

     * @return array|string
     */
    public function afterExtractValue(\Magento\Customer\Model\Metadata\Form\AbstractData $subject, $value)
    {
        /** @var \Magento\Customer\Model\Data\AttributeMetadata $attribute */
        $attribute = $subject->getAttribute();

        if ($attribute->isUserDefined()) {
            if (!$this->request->getParam($attribute->getAttributeCode())
                && !$value
                && $value !== ''
                && $value !== '0'
            ) {
                $value = $subject->outputValue();
            }

            if (empty($value) && !$attribute->isRequired()) {
                return $value;
            }

            if ($attribute->getBackendType() == Table::TYPE_DATETIME) {
                $value = $this->timezone->date($value)->getTimestamp();
            }
        }

        return $value;
    }

    /**
     * @param \Magento\Customer\Model\Metadata\Form\AbstractData $subject
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function beforeExtractValue(\Magento\Customer\Model\Metadata\Form\AbstractData $subject, $request)
    {
        $this->request = $request;
    }
}

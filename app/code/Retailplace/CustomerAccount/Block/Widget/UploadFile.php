<?php declare(strict_types=1);

namespace Retailplace\CustomerAccount\Block\Widget;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Block\Widget\AbstractWidget;
/**
 * Widget for uploading image.
 *
 * @method CustomerInterface getObject()
 *
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class UploadFile extends AbstractWidget
{
    /**
     * the attribute code
     */
    const ATTRIBUTE_CODE = 'upload_file';

    /**
     * @return void
     */
    public function _construct()
    {
        parent::_construct();

        // default template location
        $this->setTemplate('Retailplace_CustomerAccount::widget/uploadfile.phtml');
    }

    /**
     * Retrieve store attribute label
     *
     * @param string $attributeCode
     *
     * @return string
     */
    public function getStoreLabel($attributeCode)
    {
        $attribute = $this->_getAttribute($attributeCode);
        return $attribute ? __($attribute->getStoreLabel()) : '';
    }

    /**
     * @return \Magento\Customer\Api\Data\AttributeMetadataInterface|null
     */
    public function getAttribute()
    {
        return $this->_getAttribute(self::ATTRIBUTE_CODE);
    }

    /**
     * Check if attribute marked as required
     *
     * @return bool
     */
    public function isRequired()
    {
        return $this->_getAttribute(self::ATTRIBUTE_CODE) ? (bool)$this->_getAttribute('gender')->isRequired() : false;
    }

    /**
     * @return string
     */
    private function getMediaUrl()
    {
        return $this->getBaseUrl() . 'pub/media/';
    }

    /**
     * @param $filePath
     * @return string
     */
    private function getCustomerImageUrl($filePath)
    {
        return $this->getMediaUrl() . 'customer' . $filePath;
    }

    /**
     * @return string
     */
    public function getFileUrl()
    {
        if ($file = $this->getObject()->getCustomAttribute(self::ATTRIBUTE_CODE)) {
            return $this->getCustomerImageUrl($file->getValue());
        }
        return '';
    }
}

<?php
namespace Mirakl\Mci\Model\Product\Attribute;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Framework\DataObject;
use Mirakl\Mci\Helper\Config as MciConfig;

class AttributeFormatter
{
    const DATE_FORMAT       = 'yyyy-MM-dd';
    const DECIMAL_PRECISION = '4';
    const MAX_FILE_SIZE     = '15360';

    /**
     * @var MciConfig
     */
    protected $mciConfig;

    /**
     * @param   MciConfig   $mciConfig
     */
    public function __construct(MciConfig $mciConfig)
    {
        $this->mciConfig = $mciConfig;
    }

    /**
     * Returns true if specified attribute is for image import, false otherwise
     *
     * @param   DataObject  $attribute
     * @return  bool
     */
    protected function isAttributeImage(DataObject $attribute)
    {
        return \Mirakl\Mci\Helper\Data::isAttributeImage($attribute);
    }

    /**
     * Maps Magento attribute types with Mirakl attribute types
     *
     * @param   DataObject  $attribute
     * @return  string
     */
    public function getAttributeType(DataObject $attribute)
    {
        /** @var Attribute $attribute */
        switch ($attribute->getBackendType()) {
            case 'int':
                $type = 'INTEGER';
                break;

            case 'datetime':
                $type = 'DATE';
                break;

            case 'decimal':
                $type = 'DECIMAL';
                break;

            case 'text':
                $type = 'LONG_TEXT';
                break;

            default:
                $type = 'TEXT';
        }

        if ($attribute->getData('frontend_input') == 'select' ||
            $attribute->getSourceModel() === 'Magento\Eav\Model\Entity\Attribute\Source\Boolean')
        {
            $type = 'LIST';
        }

        if ($attribute->getData('frontend_input') == 'multiselect') {
            $type = 'LIST_MULTIPLE_VALUES';
        }

        if ($attribute->getData('frontend_input') == 'media_image' || $this->isAttributeImage($attribute)) {
            $type = 'MEDIA';
        }

        if ($attribute->getData('frontend_class') == 'validate-digits') {
            $type = 'INTEGER';
        }

        if ($attribute->getData('frontend_class') == 'validate-number') {
            $type = 'DECIMAL';
        }

        return $type;
    }

    /**
     * Returns attribute type parameter
     *
     * @param   DataObject  $attribute
     * @return  string
     */
    public function getAttributeTypeParameter(DataObject $attribute)
    {
        /** @var Attribute $attribute */
        $type = $this->getAttributeType($attribute);

        switch ($type) {
            case 'DATE':
                $param = self::DATE_FORMAT;
                break;

            case 'DECIMAL':
                $param = self::DECIMAL_PRECISION;
                break;

            case 'LIST':
            case 'LIST_MULTIPLE_VALUES':
                $param = $attribute->getAttributeCode(); // use this value list code for list values
                break;

            case 'MEDIA':
                if ($this->isAttributeImage($attribute)) {
                    $param = $this->mciConfig->getImageMaxSize();
                } else {
                    $param = self::MAX_FILE_SIZE;
                }
                break;

            default:
                $param = null;
        }

        return $param;
    }
}

<?php 
namespace Retailplace\MiraklSellerAdditionalField\Model\Option;


class RetailerTypeOptionsProvider extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\AttributeFactory
     */
    protected $industryExclusionsFactory;

    /**
     * @param \Magento\Eav\Model\ResourceModel\Entity\AttributeFactory $eavAttrEntity
     * @codeCoverageIgnore
     */
    public function __construct(
        \Retailplace\MiraklSellerAdditionalField\Model\IndustryExclusionsFactory $industryExclusionsFactory
    ) {
        $this->_industryExclusionsFactory = $industryExclusionsFactory;
    }

    /**
     * Retrieve all options array
     *
     * @return array
     */
    public function getAllOptions()
    {
    	
        if ($this->_options === null) {
            $this->_options = [];
            $industryExclusionsCollection = $this->_industryExclusionsFactory->create()->getCollection();
            $industryExclusionsCollection->addFieldToFilter('visible_for',['finset'=>[1]]);
            if($industryExclusionsCollection->getSize()){
                
                foreach ($industryExclusionsCollection as $industryExclusions) {
                     $this->_options[] = ['value' => $industryExclusions->getCode(), 'label' => __($industryExclusions->getLabel())];
                }
            }
        }
        
        return $this->_options;
    }

    /**
     * Retrieve option array
     *
     * @return array
     */
    public function getOptionArray()
    {
        $_options = [];
        foreach ($this->getAllOptions() as $option) {
            $_options[$option['value']] = $option['label'];
        }
        return $_options;
    }

    /**
     * Get a text for option value
     *
     * @param string|int $value
     * @return string|false
     */
    public function getOptionText($value)
    {
        $options = $this->getAllOptions();
        foreach ($options as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }
        return false;
    }
}
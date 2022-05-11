<?php 
namespace Retailplace\MiraklSellerAdditionalField\Model\Option;


class ChannelExclusionOptionsProvider extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\AttributeFactory
     */
    protected $channelExclusionsFactory;

    /**
     * @param \Magento\Eav\Model\ResourceModel\Entity\AttributeFactory $eavAttrEntity
     * @codeCoverageIgnore
     */
    public function __construct(
        \Retailplace\MiraklSellerAdditionalField\Model\ChannelExclusionsFactory $channelExclusionsFactory
    ) {
        $this->_channelExclusionsFactory = $channelExclusionsFactory;
    }

    /**
     * Retrieve all options array
     *
     * @return array
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $channelExclusionsCollection = $this->_channelExclusionsFactory->create()->getCollection();
            $channelExclusionsCollection->addFieldToFilter('status',['eq'=>1]);
            $channelExclusionsCollection->setOrder('sort','asc');
            $channelExclusionsCollection->load();
            //echo $channelExclusionsCollection->getSelect();die;
            if($channelExclusionsCollection->getSize()){
                $this->_options = [];
                foreach ($channelExclusionsCollection as $channelExclusions) {
                     $this->_options[] = ['value' => $channelExclusions->getCode(), 'label' => __($channelExclusions->getLabel())];
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
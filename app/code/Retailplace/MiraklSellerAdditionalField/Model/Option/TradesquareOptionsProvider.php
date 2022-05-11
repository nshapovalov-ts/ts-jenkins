<?php 
namespace Retailplace\MiraklSellerAdditionalField\Model\Option;

use Magento\Customer\Model\Customer;

class TradesquareOptionsProvider extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;


    /**
     * @param \Magento\Eav\Model\Config $eavConfig
     * @codeCoverageIgnore
     */
    public function __construct(
          \Magento\Eav\Model\Config $eavConfig

    ) {
        $this->eavConfig = $eavConfig;
    }

    /**
     * Retrieve all options array
     *
     * @return array
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $eavAttribute = $this->eavConfig->getAttribute(Customer::ENTITY,'tradesquare');

            $options = $eavAttribute->getSource()->getAllOptions();
            foreach($options as $option) {
                 $this->_options[] = ['value' => $option['value'], 'label' => $option['label']];
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
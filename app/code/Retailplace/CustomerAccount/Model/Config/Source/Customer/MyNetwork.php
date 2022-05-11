<?php
/**
 * Retailplace_CustomerAccount
 *
 * @copyright   Copyright © 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Satish Gumudavelly <satish@kipanga.com.au>
 */

declare(strict_types=1);

namespace Retailplace\CustomerAccount\Model\Config\Source\Customer;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Retailplace\ChannelPricing\Model\GroupProcessor\Nlna;

/**
 * Class MyNetwork
 */
class MyNetwork extends AbstractSource
{
    /**
     * {@inheritdoc}
     */
    public function getAllOptions()
    {
        if (null === $this->_options) {
            $this->_options = [
                ['label' => 'Australian Gift & Homeware Association ', 'value' => 'agha'],
                ['label' => Nlna::NETWORK_TYPE_NLNA_LABEL, 'value' => Nlna::NETWORK_TYPE_NLNA_CODE],
                ['label' => Nlna::NETWORK_TYPE_VAN_LABEL, 'value' => Nlna::NETWORK_TYPE_VAN_CODE],
                ['label' => 'Accommodation Association of Australia', 'value' => 'accommodation-association-of-australia'],
                ['label' => 'Other', 'value' => 'other']
            ];
        }
        return $this->_options;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionText($value)
    {
        foreach ($this->getAllOptions() as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }
        return false;
    }
}

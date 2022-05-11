<?php

namespace Magecomp\Smspro\Model\Config\Source;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class PaymentMethods
 * @package Magecomp\Smspro\Model\Config\Source
 */
class PaymentMethods implements ArrayInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * PaymentMethods constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $hash = [];
        foreach ($this->scopeConfig->getValue('payment') as $code => $config) {
            if (!empty($config['title'])) {
                $label = '';
                if (!empty($config['group'])) {
                    $label = ucfirst($config['group']) . ' - ';
                }
                $label .= $config['title'];
                if (!empty($config['allowspecific']) && !empty($config['specificcountry'])) {
                    $label .= ' in ' . $config['specificcountry'];
                }
                $hash[$code] = $label;
            }
        }
        asort($hash);

        $methods = [];
        foreach ($hash as $code => $label) {
            $methods[] = ['value' => $code, 'label' => $label];
        }

        return $methods;
    }
}

<?php
/**
 * EYEMAGINE - The leading Magento Solution Partner
 *
 * HubSpot Integration with Magento
 *
 * @author    EYEMAGINE <magento@eyemaginetech.com>
 * @copyright Copyright (c) 2016 EYEMAGINE Technology, LLC (http://www.eyemaginetech.com)
 * @license   http://www.eyemaginetech.com/license
 */
namespace Eyemagine\HubSpot\Block\Adminhtml;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Backend\Helper\Data as HelperBackend;
use Magento\Config\Block\System\Config\Form\Field;

/**
 * Class Regen
 *
 * @package Eyemagine\HubSpot\Block\Adminhtml
 */
class Regen extends Field
{

    /**
     *
     * @var string
     */
    protected $_buttonHtml = null;

    /**
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     *
     * @var \Magento\Backend\Helper\Data
     */
    protected $_helperBackend;

    /**
     *
     * @param \Magento\Store\Model\StoreManagerInterface
     * @param \Magento\Backend\Helper\Data $helperBackend
     */
    public function __construct(StoreManagerInterface $storeManager, HelperBackend $helperBackend)
    {
        $this->_helperBackend = $helperBackend;
        $this->_storeManager = $storeManager;
    }

    public function _getElementHtml(AbstractElement $element)
    {
        if ($this->_buttonHtml === null) {
            $this->_buttonHtml = $this->getLayout()
                ->createBlock('Magento\Backend\Block\Widget\Button')
                ->setId($element->getId())
                ->setType('button')
                ->setLabel('Regenerate')
                ->setOnClick('setLocation(\'' . $this->_helperBackend->getUrl("eyehubspot/index/index") . '\')')
                ->toHtml();
        }
        
        return $this->_buttonHtml;
    }
}

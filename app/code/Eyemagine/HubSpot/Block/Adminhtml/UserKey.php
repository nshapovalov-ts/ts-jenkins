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
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Store\Model\ScopeInterface;

/**
 * Class UserKey
 *
 * @package Eyemagine\HubSpot\Block\Adminhtml
 */
class UserKey extends Field
{
    const XML_PATH_EYEMAGINE_HUBSPOT_USER_KEY = 'eyehubspot/settings/userkey';

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig */
    protected $scopeConfig;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []

    ) {
        parent::__construct($context, $data);

        $this->scopeConfig = $context->getScopeConfig();
    }

    /**
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     *
     */
    public function render(AbstractElement $element)
    {
        return sprintf(
            '<tr class="system-fieldset-sub-head" id="row_%s">
                        <td class="label"><label for="%s">%s</label></td>
                        <td class="value">%s</td>
            			<td>&#160;</td>
            			<td>&#160;</td>            			
            </tr>',
            $element->getHtmlId(),
            $element->getHtmlId(),
            $element->getLabel(),
            $this->scopeConfig->getValue(self::XML_PATH_EYEMAGINE_HUBSPOT_USER_KEY, ScopeInterface::SCOPE_STORE)
        );
    }
}

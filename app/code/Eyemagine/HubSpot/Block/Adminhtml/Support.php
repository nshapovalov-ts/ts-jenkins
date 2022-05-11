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

/**
 * Class Support
 *
 * @package Eyemagine\HubSpot\Block\Adminhtml
 */
class Support extends Field
{

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
            '<a href="mailto:hubspot@eyemaginetech.com">hubspot@eyemaginetech.com</a>'
        );
    }
}

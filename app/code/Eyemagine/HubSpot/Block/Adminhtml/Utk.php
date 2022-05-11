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
 * Class Utk
 *
 * @package Eyemagine\HubSpot\Block\Adminhtml
 */
class Utk extends Field
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
            '<p>This extension supports the HubSpot UTK Cookie and will include
            the token value for orders and abandoned carts.</p>
            <p>Please note, you are responsible for adding the HubSpot tracking
            javascript to the site. The easiest method to add this javascript is
            to add it to the Store Configuration for <b>Stores</b>  &gt;
            <b>Configuration</b>  &gt; <b>General</b> &gt; <b>Design</b> &gt;
            <b>Footer</b> &gt; <b>Miscellaneous HTML</b>.</p>'
        );
    }
}

<?php
/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Stripe\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Template\Context;
use StripeIntegration\Payments\Model\Config;

/**
 * Class NetLabel
 */
class NetLabel extends Field
{
    /**
     * @var Config
     */
    private $paymentConfig;

    /**
     * @param Context $context
     * @param Config $paymentConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $paymentConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->paymentConfig = $paymentConfig;
    }

    /**
     * Render
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $this->updateLabel($element);
        return parent::render($element);
    }

    /**
     * Update text-label with days due
     *
     * @param $element
     */
    private function updateLabel($element)
    {
        $label = $element->getLabel();
        $label = str_replace(['%days%'], [$this->paymentConfig->getInvoicingDaysDue()], $label);
        $element->setLabel($label);
    }
}

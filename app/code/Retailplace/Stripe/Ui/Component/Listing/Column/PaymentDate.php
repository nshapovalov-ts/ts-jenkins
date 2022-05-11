<?php
/**
 * Retailplace_Stripe
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Stripe\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use StripeIntegration\Payments\Model\Config;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

class PaymentDate extends Column
{
    /**
     * @var Config
     */
    private $config;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Config $config
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Config $config,
        array $components = [],
        array $data = []
    ) {
        parent::__construct(
            $context,
            $uiComponentFactory,
            $components,
            $data
        );

        $this->config = $config;
    }

    /**
     * Prepare component configuration
     *
     * @return void
     */
    public function prepare()
    {
        $config = $this->getData('config');
        if ($config) {
            $config['label'] = $this->getPaymentDateLabel($config['label']);
            $this->setData('config', $config);
        }

        parent::prepare();
    }

    /**
     * Get Payment Date Label
     *
     * @param $label
     * @return string
     */
    public function getPaymentDateLabel($label): string
    {
        return __("NET%1 %2", $this->config->getInvoicingDaysDue(), $label)->render();
    }
}

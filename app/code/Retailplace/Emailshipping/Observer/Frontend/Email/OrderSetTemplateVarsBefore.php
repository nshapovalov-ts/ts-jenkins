<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Retailplace\Emailshipping\Observer\Frontend\Email;


class OrderSetTemplateVarsBefore implements \Magento\Framework\Event\ObserverInterface
{

	/**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) 
	{ 
        $transport = $observer->getTransport();
        $order = $transport->getData('order');
		
		$mixed = false;
		$itemCollections = $order->getItemsCollection();
		$orderData = $itemCollections->getData();
		
		$shipType = array_unique(array_column($orderData,'mirakl_shipping_type_label'));
		$shippingTypes = array_filter($shipType);
		$mixed = count($shippingTypes) > 1 ? true : false;
		if($shippingTypes):
			if($mixed):
				$shippingMethod = "Mixed (".implode(", ",$shippingTypes).")";
			else:
				$shippingMethod = $shippingTypes[0];
			endif;
			$transport['shipping_title'] = $shippingMethod;
		endif;
    }
}


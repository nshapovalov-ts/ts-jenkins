<?php
namespace Mirakl\Connector\Plugin\Model\Quote;

use Magento\Quote\Model\Quote\Address;

class AddressPlugin
{
    /**
     * Need to override this method in order to exclude some quote items (with Mirakl offer)
     * from being used in shipping method price calculation (by flagging them as free_shipping)
     *
     * @param   Address $subject
     * @param   array   $items
     * @return  array
     */
    public function afterGetAllItems(Address $subject, $items)
    {
        foreach ($items as $i => $item) {
            if ($item->getMiraklOfferId()) {
                $items[$i]->setFreeShipping(true);
            }
        }

        return $items;
    }
}

<?php

namespace StripeIntegration\Payments\Plugin\Sales\Model\Service;

class OrderService
{
    public function __construct(
        \StripeIntegration\Payments\Helper\Rollback $rollback,
        \StripeIntegration\Payments\Helper\GenericFactory $helperFactory
    ) {
        $this->rollback = $rollback;
        $this->helperFactory = $helperFactory;
    }

    public function aroundPlace($subject, \Closure $proceed, $order)
    {
        try
        {
            $this->rollback->reset();
            $returnValue = $proceed($order);
            $this->rollback->reset();

            return $returnValue;
        }
        catch (\Exception $e)
        {
            $helper = $this->helperFactory->create();

            if ($order->getId())
            {
                // The order has already been saved, so we don't want to run the rollback. The exception likely occurred in an order_save_after observer.
                $this->rollback->reset();
                $helper->dieWithError($e->getMessage(), $e);
            }
            else
            {
                $msg = $e->getMessage();
                if (!$this->isAuthenticationRequiredMessage($msg))
                    $this->rollback->run($e);
                else
                    $this->rollback->reset(); // In case some customization is trying to place multiple split-orders

                $helper->dieWithError($e->getMessage(), $e);
            }

            return null;
        }
    }

    // We can't use the helper method because of a circular dependency
    private function isAuthenticationRequiredMessage($message)
    {
        if (strpos($message, "Authentication Required: ") === 0)
            return true;

        return false;
    }
}

<?php

/**
 * Retailplace_AjaxCart
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Evgeniy Derevyanko <evgeniy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\AjaxCart\Controller\Cart;

use Exception;
use Magento\Checkout\Block\Cart\Grid;
use Magento\Checkout\Block\Cart\Item\Renderer;
use Magento\Checkout\Block\Onepage\Link;
use Magento\Checkout\Controller\Cart;
use Magento\Checkout\Model\Cart\RequestQuantityProcessor;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Retailplace\CheckoutOverride\Block\Cart\Item\Shipping;
use Retailplace\CheckoutOverride\ViewModel\CartItemRenderer;
use Mirakl\Connector\Model\Quote\Updater as QuoteUpdater;

/**
 * Post update shopping cart.
 */
class UpdatePostAjax extends Cart implements HttpGetActionInterface, HttpPostActionInterface
{
    /** @var \Magento\Checkout\Model\Cart\RequestQuantityProcessor|mixed */
    private $quantityProcessor;

    /** @var \Magento\Framework\Controller\Result\JsonFactory */
    private $jsonFactory;

    /** @var \Magento\Framework\Pricing\Helper\Data */
    private $priceHelper;

    /** @var \Retailplace\CheckoutOverride\ViewModel\CartItemRenderer */
    private $cartItemRenderer;

    /** @var \Mirakl\Connector\Model\Quote\Updater */
    private $quoteUpdater;

    /**
     * UpdatePostAjax constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Retailplace\CheckoutOverride\ViewModel\CartItemRenderer $cartItemRenderer
     * @param \Mirakl\Connector\Model\Quote\Updater $quoteUpdater
     * @param \Magento\Checkout\Model\Cart\RequestQuantityProcessor|null $quantityProcessor
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Session $checkoutSession,
        StoreManagerInterface $storeManager,
        Validator $formKeyValidator,
        \Magento\Checkout\Model\Cart $cart,
        JsonFactory $jsonFactory,
        PriceHelper $priceHelper,
        CartItemRenderer $cartItemRenderer,
        QuoteUpdater $quoteUpdater,
        RequestQuantityProcessor $quantityProcessor = null
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );

        $this->jsonFactory = $jsonFactory;
        $this->priceHelper = $priceHelper;
        $this->cartItemRenderer = $cartItemRenderer;
        $this->quoteUpdater = $quoteUpdater;
        $this->quantityProcessor = $quantityProcessor ?: $this->_objectManager->get(RequestQuantityProcessor::class);
    }

    /**
     * Empty customer's shopping cart
     *
     * @return void
     */
    protected function _emptyShoppingCart()
    {
        try {
            $this->cart->truncate()->save();
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (Exception $exception) {
            $this->messageManager->addExceptionMessage($exception, __('We can\'t update the shopping cart.'));
        }

        return $this->cart->getQuote();
    }

    /**
     * Update customer's shopping cart
     *
     * @return mixed
     */
    protected function _updateShoppingCart()
    {
        $quote = null;
        try {
            $cartData = $this->getRequest()->getParam('cart');
            if (is_array($cartData)) {
                if (!$this->cart->getCustomerSession()->getCustomerId() && $this->cart->getQuote()->getCustomerId()) {
                    $this->cart->getQuote()->setCustomerId(null);
                }
                $cartData = $this->quantityProcessor->process($cartData);
                $cartData = $this->cart->suggestItemsQty($cartData);
                $this->cart->updateItems($cartData)->save();
                $quote = $this->cart->getQuote();
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage(
                $this->_objectManager->get(Escaper::class)->escapeHtml($e->getMessage())
            );
        } catch (Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('We can\'t update the shopping cart.'));
            $this->_objectManager->get(LoggerInterface::class)->critical($e);
        }

        return $quote;
    }

    /**
     * Update shopping cart data action
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $result = [];
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $layout = $this->_view->getLayout();

        $updateAction = (string)$this->getRequest()->getParam('update_cart_action');

        switch ($updateAction) {
            case 'empty_cart':
                $quote = $this->_emptyShoppingCart();
                break;
            case 'update_qty':
                $quote = $this->_updateShoppingCart();
                break;
            default:
                $quote = $this->_updateShoppingCart();
        }

        if ($quote) {
            $result['checkout_allowed'] = 1;
            if (!$quote->validateMinimumAmount() || $quote->getHasError()) {
                $result['checkout_allowed'] = 0;
            }
            $result['items'] = $this->getItemsData($layout, $quote);
            $result['groups'] = $this->getShopData($layout, $quote);
            foreach ($result['groups'] as $group) {
                if (is_array($group) && isset($group['checkout_allowed']) && !$group['checkout_allowed']) {
                    $result['checkout_allowed'] = 0;
                }
            }
            $result['checkout_button_text'] = $this->getCheckoutButtonText($result['groups']['shops_count']);
            $result['postcode'] = $quote->getShippingAddress()->getPostcode();
        }

        return $this->jsonFactory->create()->setData($result);
    }

    /**
     * Get checkout button text
     *
     * @param int $count
     * @return \Magento\Framework\Phrase|string
     */
    private function getCheckoutButtonText($count)
    {
        if ($count == 1) {
            $buttonText = __('Checkout %1 supplier', $count);
        } elseif ($count > 1) {
            $buttonText = __('Checkout %1 suppliers', $count);
        } else {
            $buttonText = __('Checkout');
        }

        return $buttonText;
    }

    /**
     * Get shops totals
     *
     * @param \Magento\Framework\View\LayoutInterface $layout
     * @return mixed
     */
    private function getShopTotals($layout)
    {
        $block = $layout->createBlock(Link::class);
        $block->setData('hide_button', true);
        $block->setData('view_model', $this->cartItemRenderer);
        $block->setTemplate('Magento_Checkout::onepage/link.phtml');

        return $block->toHtml();
    }

    /**
     * Get shop data
     *
     * @param \Magento\Framework\View\LayoutInterface $layout
     * @param \Magento\Quote\Model\Quote $quote
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getShopData($layout, $quote)
    {
        $shopTotals = [];
        $shopTotals['totals_html'] = $this->getShopTotals($layout);

        /** @var \Magento\Checkout\Block\Cart\Grid $gridBlock */
        $gridBlock = $layout->createBlock(Grid::class);
        $gridBlock->getSellerGroupItems();

        /** @var \Magento\Checkout\Block\Cart\Item\Renderer $blockParent */
        $blockParent = $layout->createBlock(Renderer::class);
        $blockShippingLimitation = $layout->createBlock(Shipping::class);
        $blockParent->setChild('default.shipping', $blockShippingLimitation);
        $blockShippingLimitation->setData(['view_model' => $this->cartItemRenderer]);
        $blockShippingLimitation->setTemplate('Mirakl_FrontendDemo::checkout/cart/item/shipping_seller_limitaition.phtml');
        $blockShippingItem = $layout->createBlock(Shipping::class);
        $blockShippingItem->setTemplate('Mirakl_FrontendDemo::checkout/cart/item/shipping_item.phtml');
        $blockParent->setChild('shipping.item', $blockShippingItem);
        $blockShippingItem->setData(['view_model' => $this->cartItemRenderer]);
        $blockSellerCheckout = $layout->createBlock(Shipping::class);
        $blockParent->setChild('seller.checkout', $blockSellerCheckout);
        $shopCount = 0;
        $shops = [];
        foreach ($quote->getItems() as $quoteItem) {
            $miraklShopId = $quoteItem->getMiraklShopId();
            $groupId = $this->cartItemRenderer->getItemGroupId($quoteItem);
            $blockParent->setItem($quoteItem);
            $blockShippingLimitation->setItem($quoteItem);
            $blockParent->setShop($blockShippingLimitation->getShop());
            if (!isset($shopTotals[$groupId]['shipping_limitation'])) {
                $shopTotals[$groupId]['shipping_limitation'] = $blockShippingLimitation->toHtml();
                $shopTotals[$groupId]['shipping_types'] = $blockShippingItem->toHtml();
                $shopTotals[$groupId]['shop_id'] = $miraklShopId;

                if (
                    $this->cartItemRenderer->getCartTotalForShop() >= $this->cartItemRenderer->getMinimumOrderSum()
                    && !$quoteItem->getHasError()
                ) {
                    $shopTotals[$groupId]['seller_checkout'] = $blockSellerCheckout->setTemplate('Mirakl_FrontendDemo::checkout/cart/item/seller_checkout.phtml')->toHtml();
                } else {
                    $shopTotals[$groupId]['checkout_allowed'] = 0;
                    $shopTotals[$groupId]['seller_checkout'] = $blockSellerCheckout->setTemplate('Mirakl_FrontendDemo::checkout/cart/item/seller_checkout_disabled.phtml')->toHtml();
                }
            }
            if (!isset($shops[$miraklShopId])) {
                $shopCount++;
                $shops[$miraklShopId] = $miraklShopId;
            }
        }
        $shopTotals['shops_count'] = $shopCount;

        return $shopTotals;
    }

    /**
     * Get items data
     *
     * @param \Magento\Framework\View\LayoutInterface $layout
     * @param \Magento\Quote\Model\Quote $quote
     * @return array
     */
    private function getItemsData($layout, $quote)
    {
        $itemsData = [];
        $itemRendererBlock = $layout->createBlock(Renderer::class);
        $this->quoteUpdater->synchronize($quote);
        foreach ($quote->getItems() as $quoteItem) {
            $quoteItemId = $quoteItem->getId();
            $itemRendererBlock->setItem($quoteItem);
            $itemsData[$quoteItemId]['price'] = $this->priceHelper->currencyByStore($quoteItem->getPriceInclTax());
            $itemsData[$quoteItemId]['qty'] = $quoteItem->getQty();
            $itemsData[$quoteItemId]['row_total'] = $this->priceHelper->currencyByStore($quoteItem->getRowTotalInclTax());
            $itemsData[$quoteItemId]['shop_id'] = $quoteItem->getMiraklShopId();
            $itemsData[$quoteItemId]['messages'] = $itemRendererBlock->getMessages();
        }

        return $itemsData;
    }
}

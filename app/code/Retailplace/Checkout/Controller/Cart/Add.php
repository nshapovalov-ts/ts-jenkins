<?php

/**
 * Retailplace_Checkout
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Nikolay Shapovalov <nikolay@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\Checkout\Controller\Cart;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Escaper;
use Psr\Log\LoggerInterface;

/**
 * Controller for processing add to cart action.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class Add extends \Magento\Checkout\Controller\Cart\Add
{
    /** @var LoggerInterface */
    private $logger;

    /** @var Escaper */
    private $escaper;

    /** @var ResolverInterface */
    private $resolver;

    /** @var Json */
    private $serializer;

    /**
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param Validator $formKeyValidator
     * @param CustomerCart $cart
     * @param ProductRepositoryInterface $productRepository
     * @param Escaper $escaper
     * @param ResolverInterface $resolver
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Session $checkoutSession,
        StoreManagerInterface $storeManager,
        Validator $formKeyValidator,
        CustomerCart $cart,
        ProductRepositoryInterface $productRepository,
        Escaper $escaper,
        ResolverInterface $resolver,
        Json $serializer,
        LoggerInterface $logger
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart,
            $productRepository
        );
        $this->escaper = $escaper;
        $this->resolver = $resolver;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * @return Redirect|Add|void
     */
    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(
                __('Your session has expired')
            );
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $params = $this->getRequest()->getParams();

        try {
            if (isset($params['qty'])) {
                $filter = new \Zend_Filter_LocalizedToNormalized(
                    ['locale' => $this->resolver->getLocale()]
                );
                $params['qty'] = $filter->filter($params['qty']);
            }

            $product = $this->_initProduct();
            $related = $this->getRequest()->getParam('related_product');

            /**
             * Check product availability
             */
            if (!$product) {
                return $this->goBack();
            }

            $this->cart->addProduct($product, $params);
            if (!empty($related)) {
                $this->cart->addProductsByIds(explode(',', $related));
            }

            $this->cart->save();

            $this->_eventManager->dispatch(
                'checkout_cart_add_product_complete',
                ['product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
            );

            if (!$this->_checkoutSession->getNoCartRedirect(true)) {
                if (!$this->cart->getQuote()->getHasError()) {
                    if ($this->shouldRedirectToCart()) {
                        $message = __(
                            'You added %1 to your shopping cart.',
                            $product->getName()
                        );
                        $this->messageManager->addSuccessMessage($message);
                    } else {
                        $this->messageManager->addComplexSuccessMessage(
                            'addCartSuccessMessage',
                            [
                                'product_name' => $product->getName(),
                                'cart_url' => $this->getCartUrl(),
                            ]
                        );
                    }
                }

                return $this->goBack(null, $product);
            }
        } catch (LocalizedException $e) {
            if ($this->_checkoutSession->getUseNotice(true)) {
                $this->messageManager->addNoticeMessage(
                    $this->escaper->escapeHtml($e->getMessage())
                );
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->messageManager->addErrorMessage(
                        $this->escaper->escapeHtml($message)
                    );
                }
            }

            return $this->goBack();
        } catch (\Exception $exception) {
            $this->messageManager->addExceptionMessage(
                $exception,
                __('We can\'t add this item to your shopping cart right now.')
            );
            $this->logger->critical($exception);

            return $this->goBack();
        }
    }

    /**
     * @param $backUrl
     * @param $product
     * @return Redirect|void
     */
    protected function goBack($backUrl = null, $product = null)
    {
        if (!$this->getRequest()->isAjax()) {
            return parent::_goBack($backUrl);
        }

        $result = [];

        if ($backUrl || $backUrl = $this->getBackUrl()) {
            $result['backUrl'] = $backUrl;
        } else {
            if ($product) {
                $result['product'] = [
                    'sku' => $product->getSku()
                ];
            }
        }

        $this->getResponse()->representJson($this->serializer->serialize($result));
    }

    /**
     * Returns cart url
     *
     * @return string
     */
    private function getCartUrl(): string
    {
        return $this->_url->getUrl('checkout/cart', ['_secure' => true]);
    }

    /**
     * Is redirect should be performed after the product was added to cart.
     *
     * @return bool
     */
    private function shouldRedirectToCart(): bool
    {
        return $this->_scopeConfig->isSetFlag('checkout/cart/redirect_to_cart', ScopeInterface::SCOPE_STORE);
    }
}

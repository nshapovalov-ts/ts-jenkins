<?php

/**
 * Retailplace_MiraklQuote
 *
 * @copyright   Copyright (c) 2022 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Dmitriy Fionov <dmitriy@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklQuote\Controller\Actions;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Customer\Controller\AbstractAccount as CustomerController;
use Magento\Framework\Escaper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Mirakl\MMP\Common\Domain\UserType;
use Mirakl\MMP\Front\Domain\Quote\Message\CreatedQuoteRequestMessage;
use Retailplace\MiraklQuote\Model\MiraklQuoteManagement;

/**
 * Class NewMessagePost
 */
class NewMessagePost extends CustomerController implements HttpPostActionInterface
{
    /** @var \Retailplace\MiraklQuote\Model\MiraklQuoteManagement */
    private $miraklQuoteManagement;

    /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface */
    private $timezone;

    /** @var \Magento\Framework\Escaper */
    private $escaper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Retailplace\MiraklQuote\Model\MiraklQuoteManagement $miraklQuoteManagement
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Escaper $escaper
     */
    public function __construct(
        Context $context,
        MiraklQuoteManagement $miraklQuoteManagement,
        TimezoneInterface $timezone,
        Escaper $escaper
    ) {
        parent::__construct($context);

        $this->miraklQuoteManagement = $miraklQuoteManagement;
        $this->timezone = $timezone;
        $this->escaper = $escaper;
    }

    /**
     * Execute Controller
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $quoteRequestId = $this->getRequest()->getParam('quote_request_id');
        $message = $this->getRequest()->getParam('message');
        if (!$quoteRequestId || !$message) {
            $response = null;
        } else {
            $response = $this->miraklQuoteManagement->sendMessage($quoteRequestId, $message, UserType::SHOP);
        }
        $dateCreated = $this->timezone->date()->format('d.m.Y H:i');

        return $this->sendResponse($response, $dateCreated, $this->escaper->escapeHtml($message));
    }

    /**
     * Send Json Response
     *
     * @param \Mirakl\MMP\Front\Domain\Quote\Message\CreatedQuoteRequestMessage|null $quoteMessage
     * @param string $dateCreated
     * @param string $message
     * @return \Magento\Framework\Controller\ResultInterface
     */
    private function sendResponse(?CreatedQuoteRequestMessage $quoteMessage, string $dateCreated, string $message): ResultInterface
    {
        if ($quoteMessage) {
            $data = [
                'is_success' => true,
                'response' => [
                    'date_created_formatted' => $dateCreated,
                    'message' => $message
                ]
            ];
        } else {
            $data = [
                'is_success' => false,
                'response' => __('Unable to Send Message.')
            ];
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($data);

        return $resultJson;
    }
}

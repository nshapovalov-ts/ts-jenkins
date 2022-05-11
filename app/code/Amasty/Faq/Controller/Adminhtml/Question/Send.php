<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Faq
 */


namespace Amasty\Faq\Controller\Adminhtml\Question;

use Magento\Backend\App\Action\Context;
use Amasty\Faq\Api\QuestionRepositoryInterface;
use Amasty\Faq\Model\QuestionFactory;
use Amasty\Faq\Model\TagFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Amasty\Faq\Utils\Email;
use Amasty\Faq\Model\ConfigProvider;

class Send extends \Amasty\Faq\Controller\Adminhtml\AbstractQuestion
{
    /**
     * @var QuestionRepositoryInterface
     */
    private $repository;

    /**
     * @var Email
     */
    private $email;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    public function __construct(
        Context $context,
        QuestionRepositoryInterface $repository,
        Email $email,
        ConfigProvider $configProvider,
        ProductRepositoryInterface $productRepository
    ) {
        parent::__construct($context);
        $this->repository = $repository;
        $this->email = $email;
        $this->configProvider = $configProvider;
        $this->productRepository = $productRepository;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        if ($questionId = $this->getRequest()->getParam('id')) {
            try {
                $this->sendCustomerNotification($this->repository->getById($questionId));
                $this->messageManager->addSuccessMessage(
                    __('You saved the item. Answer sent to Customer\'s Email.')
                );
            } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
                $this->messageManager->addErrorMessage(__('This question no longer exists.'));
                return $this->_redirect('*/*/');
            }
        }

        return $this->_redirect('*/*/');
    }

    /**
     * @param \Amasty\Faq\Api\Data\QuestionInterface $question
     */
    private function sendCustomerNotification(\Amasty\Faq\Api\Data\QuestionInterface $question)
    {
        $productLink = '';
        $productName = '';
        $productIds = $question->getProductIds();

        if ($productIds) {
            $productIds = explode(',', $productIds);
            $productId = $productIds[0];

            try {
                $product = $this->productRepository->getById($productId);
                $productName = $product->getName();
                $productLink = $product->getProductUrl();
            } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
                null;
            }
        }

        $this->email->sendEmail(
            [
                'email' => $question->getEmail(),
                'name' => $question->getName()
            ],
            ConfigProvider::USER_NOTIFY_EMAIL_TEMPLATE,
            [
                'customer_name' => $question->getName() ? : __('Customer'),
                'question' => $question->getTitle(),
                'answer' => strip_tags($question->getAnswer()),
                'product_name' => $productName,
                'product_link' => $productLink,
                'asked_from_store' => $question->getAskedFromStore()
            ],
            \Magento\Framework\App\Area::AREA_FRONTEND,
            $this->configProvider->getNotifySender($question->getAskedFromStore())
        );
    }
}

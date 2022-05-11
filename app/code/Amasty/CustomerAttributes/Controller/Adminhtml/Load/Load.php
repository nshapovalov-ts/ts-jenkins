<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_CustomerAttributes
 */


namespace Amasty\CustomerAttributes\Controller\Adminhtml\Load;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory;

class Load extends Action
{
    /**
     * @var \Amasty\CustomerAttributes\Helper\Image
     */
    private $imageHelper;

    /**
     * @var Context
     */
    private $context;

    public function __construct(
        Context $context,
        \Amasty\CustomerAttributes\Helper\Image $imageHelper
    ) {
        parent::__construct($context);
        $this->imageHelper = $imageHelper;
        $this->context = $context;
    }

    public function execute()
    {
        $optionId = key($this->getRequest()->getFiles()->toArray()['amcustomerattr_icon']);
        $img = $this->imageHelper->uploadImage($optionId, $optionId);
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($img);

        return $resultJson;
    }
}

<?php
/**
 * Retailplace_MiraklMcm
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklMcm\Model\Queue\Publisher;

use Magento\Framework\MessageQueue\PublisherInterface;
use Retailplace\MiraklMcm\Api\Data\ProductImportMessageInterface;
use Retailplace\MiraklMcm\Model\Queue\ProductImportMessageFactory;

/**
 * Class Product
 */
class Product
{
    /**
     * product Import Topic Name
     */
    const TOPIC_NAME = 'retailplace.product.import';

    /**
     * @var PublisherInterface
     */
    private $publisher;
    /**
     * @var ProductImportMessageFactory
     */
    private $messageFactory;

    /**
     * Publisher constructor
     *
     * @param PublisherInterface $publisher
     * @param ProductImportMessageFactory $messageFactory
     */
    public function __construct(
        PublisherInterface $publisher,
        ProductImportMessageFactory $messageFactory
    ) {
        $this->publisher = $publisher;
        $this->messageFactory = $messageFactory;
    }

    /**
     * Add message to queue
     *
     * @param ProductImportMessageInterface $data
     */
    public function execute(ProductImportMessageInterface $data)
    {
        $this->publisher->publish(self::TOPIC_NAME, $data);
    }

    /**
     * Create Message
     *
     * @param array|null $data
     * @return ProductImportMessageInterface
     */
    public function createMessage(array $data = null)
    {
        $model = $this->messageFactory->create();
        if (!empty($data)) {
            $model->setData($data);
        }
        return $model;
    }
}

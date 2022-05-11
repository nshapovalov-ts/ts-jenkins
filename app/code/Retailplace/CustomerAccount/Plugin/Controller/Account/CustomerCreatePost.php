<?php

namespace Retailplace\CustomerAccount\Plugin\Controller\Account;

use Magento\Customer\Controller\Account\CreatePost;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Message\MessageInterface;

/**
 * Class CustomerCreatePost
 *
 * @package Mageplaza\CustomerApproval\Plugin
 */
class CustomerCreatePost
{
    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * CustomerCreatePost constructor.
     *
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        ManagerInterface $messageManager
    ) {
        $this->messageManager = $messageManager;
    }

    /**
     * @param CreatePost $createPost
     * @param $result
     *
     * @return mixed
     */
    public function afterExecute(CreatePost $createPost, $result)
    {
        foreach ($this->messageManager->getMessages()->getItems() as $message) {
            if ($message->getType() === MessageInterface::TYPE_SUCCESS) {
                $data = $message->getData();
                if (empty($data['view_type'])) {
                    $data['view_type'] = 'registering';
                }
                $message->setData($data);
            }
        }

        return $result;
    }
}

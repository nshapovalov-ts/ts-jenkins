<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\CmsDisplayRules\Controller\Block;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magefan\CmsDisplayRules\Model\BlockRepository;
use Magefan\CmsDisplayRules\Model\Config;

/**
 * Class Update return current page content
 * phpcs:ignoreFile
 */
class Get extends \Magento\Framework\App\Action\Action
{

    /**
     * @var JsonFactory
     */
    protected $jsonResultFactory;

    /**
     * @var BlockRepository
     */
    protected $blockRepository;

    /**
     * @var Config
     */
    protected $config;

    /**
     * Get constructor.
     * @param Context $context
     * @param JsonFactory $jsonResultFactory
     * @param BlockRepository $blockRepository
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonResultFactory,
        BlockRepository $blockRepository,
        Config $config
    ) {
        parent::__construct($context);
        $this->jsonResultFactory = $jsonResultFactory;
        $this->blockRepository = $blockRepository;
        $this->config = $config;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $result = $this->jsonResultFactory->create();

        if (!$this->config->isEnabled()) {
            $result->setData([
                'html' => [],
                'message' => __('You haven\'t permissions to see this block!')
            ]);
            return $result;
        }

        $ids = $this->getRequest()->getParam('block_id');
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $data = [
            'html' => [],
            'message' => '',
        ];

        foreach ($ids as $key => $secret) {
            if (!empty($key) && !empty($secret)) {
                try {
                    $block = $this->blockRepository->getById($key);
                    if (!empty($block->getSecret())) {
                        if ($secret != $block->getSecret()) {
                            throw new LocalizedException(__('You haven\'t permissions to see this block!'));
                        }
                        $data['html'][$key] = $this->_view->getLayout()->createBlock(\Magento\Cms\Block\Block::class)
                            ->setBlockId($key)
                            ->toHtml();
                    }
                } catch (NoSuchEntityException $e) {
                    $data['message'] .= ' ' . __('Block ID %1: %2.', $key, 'does not exist');
                } catch (LocalizedException $e) {
                    $data['message'] .= ' ' . __('Block ID %1: %2.', $key, $e->getMessage());
                } catch (\Exception $e) {
                    $data['message'] .= ' ' . __('Block ID %1: %2.', $key, 'Unexpected error');
                }
            }
        }
        $result->setData($data);
        return $result;
    }
}

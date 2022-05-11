<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\CmsDisplayRules\Plugin\Block;

use Magento\Framework\Registry;
use Magento\Framework\App\RequestInterface;
use Magefan\CmsDisplayRules\Model\ResourceModel\Block\Collection;
use Magento\Cms\Model\Block\DataProvider;
use Magefan\CmsDisplayRules\Model\BlockRepository;

/**
 * Class DataProviderPlugin plugin
 */
class DataProviderPlugin
{

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var BlockRepository
     */
    protected $blockRepository;

    /**
     * DataProviderPlugin constructor.
     * @param RequestInterface $request
     */
    public function __construct(
        RequestInterface $request,
        BlockRepository $blockRepository
    ) {
        $this->request = $request;
        $this->blockRepository = $blockRepository;
    }

    /**
     * @param DataProvider $subject
     * @param $proceed
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundGetData(DataProvider $subject, $proceed)
    {
        $conditions = [
            'group_id',
            'start_date',
            'finish_date',
            'time_from',
            'time_to',
            'days_of_week',
            'conditions_serialized',
            'another_cms'
        ];

        $result = $proceed();
        $blockId = $this->request->getParam('block_id');
        if ($blockId) {
            $blockRule = $this->blockRepository->getById($blockId);
            if ($blockRule) {
                $data[] = $blockRule->getData();
                foreach ($conditions as $condition) {
                    if (array_key_exists($condition, $data[0]) && ($data[0][$condition] || 0 === $data[0][$condition] || '0' === $data[0][$condition] )) {
                        if ($condition == 'group_id' || $condition == 'days_of_week') {
                            $result[$blockId]['magefan_cms_display_rules['. $condition . ']'] = explode(
                                ',',
                                $data[0][$condition]
                            );
                        } else {
                            $result[$blockId]['magefan_cms_display_rules['. $condition . ']'] = $data[0][$condition];
                        }
                    }
                }
            }
        }
        return $result;
    }
}

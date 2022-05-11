<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Faq
 */


namespace Amasty\Faq\Model\ResourceModel\Question;

use Amasty\Faq\Api\Data\QuestionInterface;
use Amasty\Faq\Model\ConfigProvider;
use Amasty\Faq\Setup\Operation\CreateQuestionStoreTable;
use Amasty\Faq\Setup\Operation\CreateQuestionTable;
use Magento\Framework\DataObject;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Store\Model\StoreManagerInterface;

class SitemapCollection extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        ConfigProvider $configProvider,
        $connectionName = null
    ) {
        $this->storeManager = $storeManager;
        $this->configProvider = $configProvider;
        parent::__construct($context, $connectionName);
    }

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(CreateQuestionTable::TABLE_NAME, QuestionInterface::QUESTION_ID);
    }

    /**
     * @param $storeId
     * @return array|bool
     */
    public function getCollection($storeId)
    {
        $questions = [];

        $store = $this->storeManager->getStore($storeId);

        if (!$store) {
            return false;
        }

        $urlKey = $this->configProvider->getUrlKey();

        $connection = $this->getConnection();

        $select = $connection->select()->from(
            ['e' => $this->getTable(CreateQuestionTable::TABLE_NAME)],
            [QuestionInterface::QUESTION_ID, QuestionInterface::URL_KEY, QuestionInterface::UPDATED_AT]
        )->joinLeft(
            ['st1' => $this->getTable(CreateQuestionStoreTable::TABLE_NAME)],
            'e.question_id = st1.question_id AND st1.store_id = 0',
            null
        )->joinLeft(
            ['st2' => $this->getTable(CreateQuestionStoreTable::TABLE_NAME)],
            'e.question_id = st2.question_id AND st2.store_id = ' . $storeId,
            null
        )->where('e.exclude_sitemap = 0 and e.' . QuestionInterface::IS_SHOW_FULL_ANSWER . ' = 0');

        $query = $connection->query($select);
        while ($row = $query->fetch()) {
            $question = $this->prepareQuestion($row, $urlKey);
            $questions[$question->getId()] = $question;
        }

        return $questions;
    }

    /**
     * @param array $questionRow
     * @param $urlKey
     * @return DataObject
     */
    protected function prepareQuestion(array $questionRow, $urlKey)
    {
        $question = new DataObject();
        $question->setId($questionRow[QuestionInterface::QUESTION_ID]);
        $questionUrl = $urlKey . '/' . $questionRow[QuestionInterface::URL_KEY] . '/';
        $question->setUrl($questionUrl);
        $question->setUpdatedAt($questionRow[QuestionInterface::UPDATED_AT]);

        return $question;
    }
}

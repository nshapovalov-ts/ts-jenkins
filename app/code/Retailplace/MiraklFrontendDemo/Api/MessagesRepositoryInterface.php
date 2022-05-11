<?php

/**
 * Retailplace_MiraklFrontendDemo
 *
 * @copyright   Copyright (c) 2021 TRADESQUARE PTY LTD (www.tradesquare.com.au)
 * @author      Alexander Korsun <aleksandr@tradesquare.com.au>
 */

declare(strict_types=1);

namespace Retailplace\MiraklFrontendDemo\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResults;
use Magento\Framework\Exception\CouldNotSaveException;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadDetails;
use Retailplace\MiraklFrontendDemo\Model\Messages;

/**
 * MessagesRepository Interface
 */
interface MessagesRepositoryInterface
{

    /**
     * get by id
     *
     * @param int $id
     * @return Messages
     */
    public function getById($id);
    /**
     * get by id
     *
     * @param int $id
     * @return Messages
     */
    public function save(Messages $subject);
    /**
     * get list
     *
     * @param SearchCriteriaInterface $criteria
     * @return SearchResults
     */
    public function getList(SearchCriteriaInterface $criteria);
    /**
     * delete
     *
     * @param Messages $subject
     * @return boolean
     */
    public function delete(Messages $subject);
    /**
     * delete by id
     *
     * @param int $id
     * @return boolean
     */
    public function deleteById($id);

    /**
     * @param ThreadDetails $threads
     * @return mixed|void
     * @throws CouldNotSaveException
     */
    public function updateMessages($threads);

}


<?php declare(strict_types=1);

namespace  Retailplace\CustomerAccount\Setup\Patch\Data;

use Magento\Cms\Model\BlockFactory;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Class ApprovalDocumentNote
 * @package  Retailplace\CustomerAccount\Setup\Patch\Data;
 */
class ApprovalDocumentNote implements DataPatchInterface
{
    const BLOCK_IDENTIFIER = 'approval-document-note';
    /**
     * @var BlockFactory
     */
    protected $blockFactory;

    /**
     * ApprovalDocumentNote constructor.
     * @param BlockFactory $blockFactory
     */
    public function __construct(
        BlockFactory $blockFactory
    ) {
        $this->blockFactory = $blockFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $data = [
            'title' => 'Approval Document Note',
            'identifier' => self::BLOCK_IDENTIFIER,
            'content' => "Don't have documents available right now?, Please email one of the above to <a href='mailto: support@tradesquare.com.au'>support@tradesquare.com.au</a> within the next 7 days to continue to buy on TradeSquare.",
            'stores' => [0],
            'is_active' => 1,
        ];
        $note = $this->blockFactory
            ->create()
            ->load($data['identifier'], 'identifier');

        /**
         * Create the block if it does not exists, otherwise update the content
         */
        if (!$note->getId()) {
            $note->setData($data)->save();
        } else {
            $note->setContent($data['content'])->save();
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        /**
         * No dependencies for this
         */
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        /**
         * Aliases are useful if we change the name of the patch until then we do not need any
         */
        return [];
    }
}

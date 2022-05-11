<?php
namespace Mirakl\MCM\FrontOperator\Domain\Product\Export;

use Mirakl\Core\Domain\MiraklObject;

/**
 * @method \DateTime getCreationDate()
 * @method $this     setCreationDate(\DateTime $creationDate)
 * @method \DateTime getDeletionDate()
 * @method $this     setDeletionDate(\DateTime $deletionDate)
 * @method string    getMiraklProductId()
 * @method $this     setMiraklProductId(string $miraklProductId)
 */
class AbstractProductDeleted extends MiraklObject
{}
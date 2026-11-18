<?php
/**
 * MageTips_Appliances Module
 *
 * Copyright © 2026 Muhammad Umar. All rights reserved.
 * Licensed under the Open Software License (OSL 3.0).
 *
 * @category  MageTips
 * @package   MageTips_Appliances
 * @author    Muhammad Umar <umarshiekh619@gmail.com>
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License 3.0
 * @link      https://github.com/UmarShaikh79/magento2-appliances
 */

declare(strict_types=1);

namespace MageTips\Appliances\Ui\DataProvider;

use MageTips\Appliances\Api\Data\EppInterface;
use MageTips\Appliances\Model\ResourceModel\Epp\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

/**
 * UI Form data provider for EPP
 *
 * Reads from DataPersistor first (set by Edit controller on every GET,
 * or by Save controller on validation/save failure), then falls back
 * to the collection for subsequent page loads.
 *
 * @since 1.0.0
 */
class EppFormDataProvider extends AbstractDataProvider
{
    /** @var DataPersistorInterface */
    private DataPersistorInterface $dataPersistor;

    /** @var array */
    private array $loadedData = [];

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection    = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Load EPP data from DataPersistor or fall back to collection.
     *
     * @return array
     */
    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        // Primary source: DataPersistor (set by Edit controller on every GET,
        // or by Save controller on validation/save failure).
        $persisted = $this->dataPersistor->get(EppInterface::TABLE_NAME);

        if (!empty($persisted)) {
            if ($persisted instanceof EppInterface) {
                // Edit controller stores the entity object
                $entityData = $persisted->getData();
                $entityId   = $persisted->getEntityId();
            } else {
                // Save error handler stores the raw POST array
                $entityData = (array)$persisted;
                $entityId   = $entityData[EppInterface::ENTITY_ID] ?? null;
            }

            // A new record has no entity_id yet; PHP 8.1+ deprecates using null
            // as an array key, so normalize to a string ('' for new records).
            $this->loadedData[(string)$entityId] = $entityData;
            $this->dataPersistor->clear(EppInterface::TABLE_NAME);

            return $this->loadedData;
        }

        // Fallback: collection (filtered by entity_id via AbstractDataProvider::addFilter)
        foreach ($this->collection->getItems() as $item) {
            $this->loadedData[$item->getId()] = $item->getData();
        }

        return $this->loadedData;
    }
}

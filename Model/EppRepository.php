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

namespace MageTips\Appliances\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use MageTips\Appliances\Api\EppRepositoryInterface;
use MageTips\Appliances\Api\Data\EppInterface;
use MageTips\Appliances\Model\ResourceModel\Epp as EppResourceModel;
use MageTips\Appliances\Model\ResourceModel\Epp\CollectionFactory;

class EppRepository implements EppRepositoryInterface
{
    /** @var EppFactory */
    private EppFactory $eppFactory;

    /** @var EppResourceModel */
    private EppResourceModel $eppResource;

    /** @var CollectionFactory */
    private CollectionFactory $collectionFactory;

    /**
     * @param EppFactory $eppFactory
     * @param EppResourceModel $eppResource
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        EppFactory $eppFactory,
        EppResourceModel $eppResource,
        CollectionFactory $collectionFactory
    ) {
        $this->eppFactory = $eppFactory;
        $this->eppResource = $eppResource;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $id): EppInterface
    {
        $epp = $this->eppFactory->create();
        $this->eppResource->load($epp, $id);

        if (!$epp->getId()) {
            throw new NoSuchEntityException(__('EPP with ID %1 not found', $id));
        }

        return $epp;
    }

    /**
     * @inheritdoc
     */
    public function getByCode(string $code): EppInterface
    {
        $epp = $this->eppFactory->create();
        $this->eppResource->load($epp, $code, 'code');

        if (!$epp->getId()) {
            throw new NoSuchEntityException(__('EPP with code %1 not found', $code));
        }

        return $epp;
    }

    /**
     * @inheritdoc
     */
    public function save(EppInterface $epp): EppInterface
    {
        try {
            $this->eppResource->save($epp);
        } catch (\Exception $e) {
            throw new LocalizedException(__('Unable to save EPP: %1', $e->getMessage()));
        }

        return $epp;
    }

    /**
     * @inheritdoc
     */
    public function delete(EppInterface $epp): bool
    {
        try {
            $this->eppResource->delete($epp);
        } catch (\Exception $e) {
            throw new LocalizedException(__('Unable to delete EPP: %1', $e->getMessage()));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getList(): array
    {
        return $this->collectionFactory->create()->getItems();
    }

    /**
     * @inheritdoc
     */
    public function getEnabledList(): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addEnabledFilter();

        return $collection->getItems();
    }

    /**
     * @inheritdoc
     */
    public function getApplicablePlansByPrice(float $productPrice): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addEnabledFilter();
        $collection->addPriceRangeFilter($productPrice);

        return $collection->getItems();
    }
}

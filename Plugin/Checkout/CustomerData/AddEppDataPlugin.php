<?php
/**
 * MageTips_Appliances Module
 *
 * Copyright © 2026 Muhammad Umar. All rights reserved.
 * Licensed under the Open Software License (OSL 3.0).
 */

declare(strict_types=1);

namespace MageTips\Appliances\Plugin\Checkout\CustomerData;

use MageTips\Appliances\Api\EppRepositoryInterface;
use MageTips\Appliances\Model\Quote\Total\EppCharge;
use Magento\Checkout\CustomerData\ItemPool;
use Magento\Quote\Model\Quote\Item;
use Psr\Log\LoggerInterface;

/**
 * Appends EPP plan name to each cart item's customer-section data so the
 * checkout summary can display it alongside the item.
 */
class AddEppDataPlugin
{
    /**
     * @param EppRepositoryInterface $eppRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected readonly EppRepositoryInterface $eppRepository,
        protected readonly LoggerInterface $logger
    ) {
    }

    /**
     * Append EPP plan name to cart item customer-section data.
     *
     * @param ItemPool $subject
     * @param array $result
     * @param Item $item
     * @return array
     */
    public function afterGetItemData(
        ItemPool $subject,
        array $result,
        Item $item
    ): array {
        $option = $item->getOptionByCode(EppCharge::EPP_ITEM_OPTION_CODE);

        if (!$option || !$option->getValue()) {
            return $result;
        }

        try {
            $plan  = $this->eppRepository->getByCode((string)$option->getValue());
            $years = (int)$plan->getYears();

            $result['epp_plan_name'] = sprintf(
                '%s - %d %s',
                (string)($plan->getName() ?? 'Protection Plan'),
                $years,
                $years === 1 ? 'YEAR' : 'YEARS'
            );
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }

        return $result;
    }
}

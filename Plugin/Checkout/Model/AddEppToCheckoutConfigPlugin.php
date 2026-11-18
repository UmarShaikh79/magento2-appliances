<?php
/**
 * MageTips_Appliances Module
 *
 * Copyright © 2026 Muhammad Umar. All rights reserved.
 * Licensed under the Open Software License (OSL 3.0).
 */

declare(strict_types=1);

namespace MageTips\Appliances\Plugin\Checkout\Model;

use MageTips\Appliances\Api\EppRepositoryInterface;
use MageTips\Appliances\Model\Delivery\Calculator;
use MageTips\Appliances\Model\Quote\Total\EppCharge;
use Magento\Checkout\Model\DefaultConfigProvider;
use Magento\Checkout\Model\Session as CheckoutSession;
use Psr\Log\LoggerInterface;

/**
 * Injects per-item EPP plan name and delivery date label into checkout config
 * so the sidebar KnockoutJS template can display them as badges on each item.
 */
class AddEppToCheckoutConfigPlugin
{
    /**
     * @param CheckoutSession $checkoutSession
     * @param EppRepositoryInterface $eppRepository
     * @param Calculator $calculator
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected readonly CheckoutSession $checkoutSession,
        protected readonly EppRepositoryInterface $eppRepository,
        protected readonly Calculator $calculator,
        protected readonly LoggerInterface $logger
    ) {
    }

    /**
     * Inject EPP plan names and delivery dates into checkout config.
     *
     * @param DefaultConfigProvider $subject
     * @param array $result
     * @return array
     */
    public function afterGetConfig(
        DefaultConfigProvider $subject,
        array $result
    ): array {
        try {
            $quote = $this->checkoutSession->getQuote();
        } catch (\Exception $e) {
            return $result;
        }

        $eppByItemId      = [];
        $deliveryByItemId = [];
        $deliveryLabel    = null;

        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            $itemId = (int)$quoteItem->getId();

            // EPP plan name
            $option = $quoteItem->getOptionByCode(EppCharge::EPP_ITEM_OPTION_CODE);
            if ($option && $option->getValue()) {
                try {
                    $plan  = $this->eppRepository->getByCode((string)$option->getValue());
                    $years = (int)$plan->getYears();
                    $eppByItemId[$itemId] = sprintf(
                        '%s - %d %s',
                        (string)($plan->getName() ?? 'Protection Plan'),
                        $years,
                        $years === 1 ? 'YEAR' : 'YEARS'
                    );
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage(), ['exception' => $e]);
                }
            }

            // Delivery date label
            $savedDate = (string)($quoteItem->getData('delivery_date') ?? '');
            if ($savedDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $savedDate)) {
                $dt = \DateTime::createFromFormat('Y-m-d', $savedDate);
                $deliveryByItemId[$itemId] = 'Ready for delivery by '
                    . ($dt ? $dt->format('F j') : $savedDate);
            } else {
                $product = $quoteItem->getProduct();
                if ($product && $product->getData('is_appliance')) {
                    if ($deliveryLabel === null) {
                        $deliveryLabel = 'Ready for delivery by '
                            . $this->calculator->getEarliestDate()->format('F j');
                    }
                    $deliveryByItemId[$itemId] = $deliveryLabel;
                }
            }
        }

        if (empty($eppByItemId) && empty($deliveryByItemId)) {
            return $result;
        }

        if (!empty($result['quoteItemData'])) {
            foreach ($result['quoteItemData'] as $index => $itemData) {
                $itemId = (int)($itemData['item_id'] ?? 0);
                if (isset($eppByItemId[$itemId])) {
                    $result['quoteItemData'][$index]['epp_plan_name'] = $eppByItemId[$itemId];
                }
                if (isset($deliveryByItemId[$itemId])) {
                    $result['quoteItemData'][$index]['delivery_date_label'] = $deliveryByItemId[$itemId];
                }
            }
        }

        if (!empty($result['totalsData']['items'])) {
            foreach ($result['totalsData']['items'] as $index => $itemData) {
                $itemId = (int)($itemData['item_id'] ?? 0);
                if (isset($eppByItemId[$itemId])) {
                    $result['totalsData']['items'][$index]['epp_plan_name'] = $eppByItemId[$itemId];
                }
                if (isset($deliveryByItemId[$itemId])) {
                    $result['totalsData']['items'][$index]['delivery_date_label'] = $deliveryByItemId[$itemId];
                }
            }
        }

        return $result;
    }
}

<?php
/**
 * MageTips_Appliances Module
 *
 * Copyright © 2026 Muhammad Umar. All rights reserved.
 * Licensed under the Open Software License (OSL 3.0).
 */

declare(strict_types=1);

namespace MageTips\Appliances\Model\Quote\Total;

use MageTips\Appliances\Api\ApplianceDetectorInterface;
use MageTips\Appliances\Api\EppRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Psr\Log\LoggerInterface;

/**
 * Sums EPP retail prices for all appliance items that have a plan selected.
 *
 * EPP selection is stored on each quote item as an option keyed by
 * EPP_ITEM_OPTION_CODE. The cart AJAX controller writes this option;
 * this collector reads it and adds the total to the quote.
 *
 * Price is multiplied by item qty (one plan per physical unit).
 */
class EppCharge extends AbstractTotal
{
    public const CODE = 'appliances_epp_charge';

    /** Quote item option key that holds the selected EPP code. */
    public const EPP_ITEM_OPTION_CODE = 'appliances_epp_code';

    /**
     * @param ApplianceDetectorInterface $detector
     * @param EppRepositoryInterface $eppRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected readonly ApplianceDetectorInterface $detector,
        protected readonly EppRepositoryInterface $eppRepository,
        protected readonly LoggerInterface $logger
    ) {
        $this->setCode(self::CODE);
    }

    /**
     * Sum EPP charges for all appliance items that have a plan selected.
     *
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return \MageTips\Appliances\Model\Quote\Total\EppCharge
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ): self {
        parent::collect($quote, $shippingAssignment, $total);

        $assignmentItems = $shippingAssignment->getItems();
        if (!$assignmentItems) {
            return $this;
        }

        $totalAmount = 0.0;

        foreach ($assignmentItems as $item) {
            if ($item->getParentItem() !== null) {
                continue;
            }

            if (!$this->detector->isApplianceItem($item)) {
                continue;
            }

            $option = $item->getOptionByCode(self::EPP_ITEM_OPTION_CODE);
            if (!$option || !$option->getValue()) {
                continue;
            }

            try {
                $epp = $this->eppRepository->getByCode($option->getValue());
                if (!$epp->isEnabled()) {
                    continue;
                }
                $itemPrice = (float)$item->getPrice();
                if ($itemPrice < (float)$epp->getMinPrice() || $itemPrice > (float)$epp->getMaxPrice()) {
                    continue;
                }
                $totalAmount += (float)$epp->getRetailPrice() * $item->getQty();
            } catch (LocalizedException $e) {
                $this->logger->warning(sprintf(
                    'EppCharge: EPP code "%s" on item %d not found — %s',
                    $option->getValue(),
                    $item->getId(),
                    $e->getMessage()
                ));
            }
        }

        if ($totalAmount <= 0.0) {
            return $this;
        }

        $total->addTotalAmount($this->getCode(), $totalAmount);
        $total->addBaseTotalAmount($this->getCode(), $totalAmount);

        return $this;
    }

    /**
     * Build the "Protection Plans" line for the checkout totals block.
     *
     * For multishipping we reuse the per-address amount collect() already stored;
     * summing all quote items there would show the whole-cart total on every
     * address. For single-address checkout we recompute from the quote items.
     *
     * @param Quote $quote
     * @param Total $total
     * @return array
     */
    public function fetch(Quote $quote, Total $total): array
    {
        if ($quote->getIsMultiShipping()) {
            // Here the Total is rebuilt from the address data, so getTotalAmount()
            // comes back empty. The per-address amount collect() saved is still in
            // the address data under "<code>_amount", so read that directly.
            $totalAmount = (float)$total->getData($this->getCode() . '_amount');
        } else {
            $totalAmount = $this->sumEppFromQuote($quote);
        }

        if ($totalAmount <= 0.0) {
            return [];
        }

        return [
            'code'  => $this->getCode(),
            'title' => __('Protection Plans'),
            'value' => $totalAmount,
        ];
    }

    /**
     * Sum EPP charges across all appliance items in the quote (single-address path).
     *
     * @param Quote $quote
     * @return float
     */
    private function sumEppFromQuote(Quote $quote): float
    {
        $totalAmount = 0.0;

        foreach ($this->detector->getApplianceItems($quote) as $item) {
            $option = $item->getOptionByCode(self::EPP_ITEM_OPTION_CODE);
            if (!$option || !$option->getValue()) {
                continue;
            }

            try {
                $epp = $this->eppRepository->getByCode($option->getValue());
                if (!$epp->isEnabled()) {
                    continue;
                }
                $itemPrice = (float)$item->getPrice();
                if ($itemPrice < (float)$epp->getMinPrice() || $itemPrice > (float)$epp->getMaxPrice()) {
                    continue;
                }
                $totalAmount += (float)$epp->getRetailPrice() * $item->getQty();
            } catch (LocalizedException $e) {
                $this->logger->warning(sprintf(
                    'EppCharge fetch: EPP code "%s" on item %d not found — %s',
                    $option->getValue(),
                    $item->getId(),
                    $e->getMessage()
                ));
            }
        }

        return $totalAmount;
    }
}

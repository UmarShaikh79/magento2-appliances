<?php
/**
 * MageTips_Appliances Module
 *
 * Copyright © 2026 Muhammad Umar. All rights reserved.
 * Licensed under the Open Software License (OSL 3.0).
 */

declare(strict_types=1);

namespace MageTips\Appliances\Block\Adminhtml\Order\Totals;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\AbstractBlock;

/**
 * Adds a "Protection Plans" total line to the admin order totals panel.
 *
 * Added as a child block of order_totals via layout XML. Magento calls
 * initTotals() on each child block before rendering the totals table.
 * We sum epp_plan_price * qty_ordered across all order items and inject
 * the amount between Subtotal and Grand Total for display only — the EPP
 * charge is already included in the order's stored grand_total.
 */
class EppTotal extends AbstractBlock
{
    /**
     * Inject EPP charge total into the order totals panel after subtotal.
     *
     * @return self
     */
    public function initTotals(): self
    {
        $parent = $this->getParentBlock();
        if (!$parent) {
            return $this;
        }

        $order = $parent->getOrder();
        if (!$order) {
            return $this;
        }

        $total = 0.0;
        foreach ($order->getAllVisibleItems() as $item) {
            $price = (float)$item->getData('epp_plan_price');
            if ($price > 0.0) {
                $total += $price * (float)$item->getQtyOrdered();
            }
        }

        if ($total <= 0.0) {
            return $this;
        }

        $parent->addTotal(
            new DataObject([
                'code'  => 'appliances_epp_charge',
                'label' => __('Protection Plans'),
                'value' => $total,
            ]),
            'subtotal'
        );

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function _toHtml(): string
    {
        return '';
    }
}

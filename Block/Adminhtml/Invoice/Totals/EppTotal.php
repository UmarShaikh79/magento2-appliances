<?php
/**
 * MageTips_Appliances Module
 *
 * Copyright © 2026 Muhammad Umar. All rights reserved.
 * Licensed under the Open Software License (OSL 3.0).
 */

declare(strict_types=1);

namespace MageTips\Appliances\Block\Adminhtml\Invoice\Totals;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\AbstractBlock;

/**
 * Adds a "Protection Plans" line to the Invoice Totals display panel.
 *
 * Added as a child of the invoice_totals block in layout XML. Reads
 * epp_plan_price from each invoice item's order item so it works for
 * both the New Invoice preview and the saved Invoice view page.
 */
class EppTotal extends AbstractBlock
{
    /**
     * Inject EPP charge total into the invoice totals panel after subtotal.
     *
     * @return self
     */
    public function initTotals(): self
    {
        $parent = $this->getParentBlock();
        if (!$parent) {
            return $this;
        }

        $invoice = $parent->getSource();
        if (!$invoice) {
            return $this;
        }

        $total = 0.0;
        foreach ($invoice->getAllItems() as $invoiceItem) {
            $orderItem = $invoiceItem->getOrderItem();
            if ($orderItem->isDummy()) {
                continue;
            }
            $price = (float)$orderItem->getData('epp_plan_price');
            if ($price > 0.0) {
                $total += $price * (float)$invoiceItem->getQty();
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

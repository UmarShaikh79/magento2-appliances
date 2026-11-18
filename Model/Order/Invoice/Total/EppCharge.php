<?php
/**
 * MageTips_Appliances Module
 *
 * Copyright © 2026 Muhammad Umar. All rights reserved.
 * Licensed under the Open Software License (OSL 3.0).
 */

declare(strict_types=1);

namespace MageTips\Appliances\Model\Order\Invoice\Total;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

/**
 * Adds the EPP protection plan charge to the invoice grand total.
 *
 * Registered in sales.xml section "order_invoice". Runs during invoice
 * collection (both on the New Invoice preview page and on Submit Invoice).
 * Reads epp_plan_price from each invoiced order item and multiplies by the
 * invoiced qty so partial invoices are charged proportionally.
 */
class EppCharge extends AbstractTotal
{
    /**
     * Collect EPP charges from invoiced order items and add to invoice grand total.
     *
     * @param Invoice $invoice
     * @return \MageTips\Appliances\Model\Order\Invoice\Total\EppCharge
     */
    public function collect(Invoice $invoice): self
    {
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

        $invoice->setGrandTotal($invoice->getGrandTotal() + $total);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $total);

        return $this;
    }
}

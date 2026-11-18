<?php
/**
 * MageTips_Appliances Module
 *
 * Copyright © 2026 Muhammad Umar. All rights reserved.
 * Licensed under the Open Software License (OSL 3.0).
 */

declare(strict_types=1);

namespace MageTips\Appliances\Model\Order\Creditmemo\Total;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

/**
 * Adds the EPP protection plan charge to the credit memo grand total.
 *
 * Registered in sales.xml section "order_creditmemo". Reads epp_plan_price
 * from each refunded order item and multiplies by the refunded qty so
 * partial credit memos are refunded proportionally.
 */
class EppCharge extends AbstractTotal
{
    /**
     * Collect EPP charges from refunded order items and add to credit memo grand total.
     *
     * @param Creditmemo $creditmemo
     * @return \MageTips\Appliances\Model\Order\Creditmemo\Total\EppCharge
     */
    public function collect(Creditmemo $creditmemo): self
    {
        $total = 0.0;

        foreach ($creditmemo->getAllItems() as $creditmemoItem) {
            $orderItem = $creditmemoItem->getOrderItem();
            if ($orderItem->isDummy()) {
                continue;
            }
            $price = (float)$orderItem->getData('epp_plan_price');
            if ($price > 0.0) {
                $total += $price * (float)$creditmemoItem->getQty();
            }
        }

        if ($total <= 0.0) {
            return $this;
        }

        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $total);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $total);

        return $this;
    }
}

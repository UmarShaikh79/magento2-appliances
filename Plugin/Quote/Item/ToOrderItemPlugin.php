<?php
/**
 * MageTips_Appliances Module
 *
 * Copyright © 2026 Muhammad Umar. All rights reserved.
 * Licensed under the Open Software License (OSL 3.0).
 */

declare(strict_types=1);

namespace MageTips\Appliances\Plugin\Quote\Item;

use Magento\Quote\Model\Quote\Address\Item as AddressItem;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Quote\Model\Quote\Item\ToOrderItem;
use Magento\Sales\Model\Order\Item as OrderItem;

/**
 * Copies the module's custom columns from the quote item to the order item
 * when an order is placed, so the plan and delivery data lands on the order.
 */
class ToOrderItemPlugin
{
    private const COLUMNS = [
        'delivery_date',
        'epp_plan_code',
        'epp_plan_name',
        'epp_plan_price',
        'epp_plan_years',
    ];

    /**
     * Copy the module's custom columns from the quote item to the order item.
     *
     * In multishipping $item is a Quote\Address\Item, and its importQuoteItem()
     * only copies the standard fields — custom columns like epp_plan_price never
     * make it across. In that case we read them from the underlying Quote\Item.
     *
     * @param ToOrderItem $subject
     * @param OrderItem $result
     * @param AbstractItem $item
     * @param array $data
     * @return OrderItem
     */
    public function afterConvert(
        ToOrderItem $subject,
        OrderItem $result,
        AbstractItem $item,
        array $data = []
    ): OrderItem {
        // For an address item, read the columns off the underlying quote item.
        $source = ($item instanceof AddressItem && $item->getQuoteItem())
            ? $item->getQuoteItem()
            : $item;

        foreach (self::COLUMNS as $column) {
            $value = $source->getData($column);
            if ($value !== null && $value !== '') {
                $result->setData($column, $value);
            }
        }

        return $result;
    }
}

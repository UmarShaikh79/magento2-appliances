<?php
/**
 * MageTips_Appliances Module
 *
 * Copyright © 2026 Muhammad Umar. All rights reserved.
 * Licensed under the Open Software License (OSL 3.0).
 */

declare(strict_types=1);

namespace MageTips\Appliances\Observer\Cart;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Captures the delivery_date POST param and writes it to the quote_item row.
 */
class AddDeliveryDateToItem implements ObserverInterface
{
    /**
     * @param RequestInterface $request
     */
    public function __construct(
        protected readonly RequestInterface $request
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        $deliveryDate = (string)$this->request->getParam('delivery_date', '');

        if (!$deliveryDate || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $deliveryDate)) {
            return;
        }

        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        $quoteItem = $observer->getEvent()->getQuoteItem();
        $item      = $quoteItem->getParentItem() ?: $quoteItem;

        $item->setData('delivery_date', $deliveryDate);
    }
}

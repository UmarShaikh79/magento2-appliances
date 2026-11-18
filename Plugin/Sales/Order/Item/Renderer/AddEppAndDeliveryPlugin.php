<?php
/**
 * MageTips_Appliances Module
 *
 * Copyright © 2026 Muhammad Umar. All rights reserved.
 * Licensed under the Open Software License (OSL 3.0).
 */

declare(strict_types=1);

namespace MageTips\Appliances\Plugin\Sales\Order\Item\Renderer;

use Magento\Sales\Block\Order\Item\Renderer\DefaultRenderer;

/**
 * Injects EPP plan and delivery date as item options on the frontend
 * order detail page (My Account → Orders → View → Items Ordered).
 *
 * EPP appears as an indented child-style option row:
 *   Protection Plan: 3-Year Plan — $399.00
 *
 * Delivery date appears as:
 *   Delivery Date: June 30, 2026
 */
class AddEppAndDeliveryPlugin
{
    /**
     * Append EPP plan and delivery date rows to the order item options list.
     *
     * @param DefaultRenderer $subject
     * @param array $result
     * @return array
     */
    public function afterGetItemOptions(DefaultRenderer $subject, array $result): array
    {
        $orderItem = $subject->getOrderItem();

        // EPP plan sub-row
        $eppName  = (string)($orderItem->getData('epp_plan_name') ?? '');
        $eppPrice = $orderItem->getData('epp_plan_price');
        $eppYears = (int)($orderItem->getData('epp_plan_years') ?? 0);

        if ($eppName !== '') {
            $label = $eppName;
            if ($eppYears > 0) {
                $label .= ' (' . $eppYears . ' ' . ($eppYears === 1 ? 'year' : 'years') . ')';
            }
            if ($eppPrice !== null) {
                $label .= ' — $' . number_format((float)$eppPrice, 2);
            }
            $result[] = [
                'label'    => (string)__('Protection Plan'),
                'value'    => $label,
                'custom'   => 'epp',
            ];
        }

        // Delivery date sub-row
        $deliveryDate = (string)($orderItem->getData('delivery_date') ?? '');
        if ($deliveryDate) {
            $dt        = \DateTime::createFromFormat('Y-m-d', $deliveryDate);
            $formatted = $dt ? $dt->format('F j, Y') : $deliveryDate;
            $result[]  = [
                'label'  => (string)__('Delivery Date'),
                'value'  => $formatted,
                'custom' => 'delivery',
            ];
        }

        return $result;
    }
}

<?php
/**
 * MageTips_Appliances Module
 *
 * Copyright © 2026 Muhammad Umar. All rights reserved.
 * Licensed under the Open Software License (OSL 3.0).
 */

declare(strict_types=1);

namespace MageTips\Appliances\Plugin\Sales\Adminhtml\Items\Column;

use Magento\Sales\Block\Adminhtml\Items\Column\DefaultColumn;

/**
 * Appends EPP plan and delivery date to the product name column in admin
 * Items Ordered tables (order, invoice, credit memo).
 *
 * The admin items template calls $block->getOrderOptions() on the Name column
 * block (which extends DefaultColumn), rendering each entry as <dt>label</dt>
 * <dd>value</dd> inside a .item-options <dl>.
 */
class AddEppAndDeliveryPlugin
{
    /**
     * Append EPP plan and delivery date rows to the admin items ordered column.
     *
     * @param DefaultColumn $subject
     * @param array $result
     * @return array
     */
    public function afterGetOrderOptions(DefaultColumn $subject, array $result): array
    {
        $item = $subject->getItem();
        if (!$item) {
            return $result;
        }

        // EPP plan sub-row
        $eppName  = (string)($item->getData('epp_plan_name') ?? '');
        $eppPrice = $item->getData('epp_plan_price');
        $eppYears = (int)($item->getData('epp_plan_years') ?? 0);

        if ($eppName !== '') {
            $label = $eppName;
            if ($eppYears > 0) {
                $label .= ' (' . $eppYears . ' ' . ($eppYears === 1 ? 'year' : 'years') . ')';
            }
            if ($eppPrice !== null) {
                $label .= ' — $' . number_format((float)$eppPrice, 2);
            }
            $result[] = [
                'label' => (string)__('Protection Plan'),
                'value' => $label,
            ];
        }

        // Delivery date sub-row
        $deliveryDate = (string)($item->getData('delivery_date') ?? '');
        if ($deliveryDate) {
            $dt        = \DateTime::createFromFormat('Y-m-d', $deliveryDate);
            $formatted = $dt ? $dt->format('F j, Y') : $deliveryDate;
            $result[]  = [
                'label' => (string)__('Delivery Date'),
                'value' => $formatted,
            ];
        }

        return $result;
    }
}

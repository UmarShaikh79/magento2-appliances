<?php
/**
 * MageTips_Appliances Module
 *
 * Copyright © 2026 Muhammad Umar. All rights reserved.
 * Licensed under the Open Software License (OSL 3.0).
 *
 * @category  MageTips
 * @package   MageTips_Appliances
 * @author    Muhammad Umar <umarshiekh619@gmail.com>
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License 3.0
 * @link      https://github.com/UmarShaikh79/magento2-appliances
 */

declare(strict_types=1);

namespace MageTips\Appliances\Plugin\Sales\Order\Email\Items\Order;

use Magento\Sales\Block\Order\Email\Items\Order\DefaultOrder;

/**
 * Appends EPP plan and delivery date to each item row in the order
 * confirmation email (uses DefaultOrder, a separate class from the
 * frontend DefaultRenderer — hence a dedicated plugin is required).
 */
class AddEppAndDeliveryPlugin
{
    /**
     * Append EPP plan and delivery date rows to the order email item options list.
     *
     * @param DefaultOrder $subject
     * @param array $result
     * @return array
     */
    public function afterGetItemOptions(DefaultOrder $subject, array $result): array
    {
        $item = $subject->getItem();
        if (!$item) {
            return $result;
        }

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

        $deliveryDate = (string)($item->getData('delivery_date') ?? '');
        if ($deliveryDate !== '') {
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

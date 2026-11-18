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

namespace MageTips\Appliances\Api;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item\AbstractItem;

/**
 * Identifies appliance products and quote items.
 *
 * The default implementation checks the is_appliance product attribute.
 * Merchants can substitute their own logic via a di.xml preference.
 *
 * @api
 * @since 1.0.0
 */
interface ApplianceDetectorInterface
{
    /**
     * Check whether a product (by ID) is an appliance.
     *
     * @param int $productId
     * @return bool
     */
    public function isApplianceProduct(int $productId): bool;

    /**
     * Check whether a single quote item represents an appliance.
     *
     * Accepts both Quote\Item (regular checkout) and Quote\Address\Item
     * (multishipping checkout) since both extend AbstractItem.
     *
     * @param AbstractItem $item
     * @return bool
     */
    public function isApplianceItem(AbstractItem $item): bool;

    /**
     * Check whether the quote contains at least one appliance item.
     *
     * @param Quote $quote
     * @return bool
     */
    public function hasAppliances(Quote $quote): bool;

    /**
     * Return all visible quote items that are appliances.
     *
     * @param Quote $quote
     * @return Item[]
     */
    public function getApplianceItems(Quote $quote): array;
}

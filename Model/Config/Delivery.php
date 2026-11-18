<?php
/**
 * MageTips_Appliances Module
 *
 * Copyright © 2026 Muhammad Umar. All rights reserved.
 * Licensed under the Open Software License (OSL 3.0).
 */

declare(strict_types=1);

namespace MageTips\Appliances\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Reads delivery date widget configuration from admin.
 */
class Delivery
{
    private const XML_PATH_PREFIX = 'appliances/delivery/';

    // Day-of-week numbers matching PHP date('N'): 1=Mon … 7=Sun
    private const DAY_MAP = [
        'monday'    => 1,
        'tuesday'   => 2,
        'wednesday' => 3,
        'thursday'  => 4,
        'friday'    => 5,
        'saturday'  => 6,
        'sunday'    => 7,
    ];

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        protected readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Check whether the delivery date widget is enabled for the store.
     *
     * @param string|null $storeCode
     * @return bool
     */
    public function isEnabled(?string $storeCode = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PREFIX . 'enabled',
            ScopeInterface::SCOPE_STORE,
            $storeCode
        );
    }

    /**
     * Minimum number of lead days before the earliest available delivery date.
     *
     * @param string|null $storeCode
     * @return int
     */
    public function getLeadDays(?string $storeCode = null): int
    {
        return max(1, (int)$this->scopeConfig->getValue(
            self::XML_PATH_PREFIX . 'lead_days',
            ScopeInterface::SCOPE_STORE,
            $storeCode
        ));
    }

    /**
     * Returns array of PHP date('N') integers for enabled delivery days.
     *
     * E.g. [1, 2, 3, 4, 5] for Mon–Fri only.
     *
     * @param string|null $storeCode
     * @return int[]
     */
    public function getEnabledDeliveryDays(?string $storeCode = null): array
    {
        $days = [];
        foreach (self::DAY_MAP as $key => $dayNumber) {
            if ($this->scopeConfig->isSetFlag(
                self::XML_PATH_PREFIX . $key,
                ScopeInterface::SCOPE_STORE,
                $storeCode
            )) {
                $days[] = $dayNumber;
            }
        }
        return $days;
    }

    /**
     * Returns holiday dates as an array of 'Y-m-d' strings.
     *
     * @param string|null $storeCode
     * @return string[]
     */
    public function getHolidays(?string $storeCode = null): array
    {
        $raw = (string)$this->scopeConfig->getValue(
            self::XML_PATH_PREFIX . 'holidays',
            ScopeInterface::SCOPE_STORE,
            $storeCode
        );

        if (trim($raw) === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode("\n", $raw)),
            static fn(string $d) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)
        ));
    }

    /**
     * Headline text displayed above the delivery date picker.
     *
     * @param string|null $storeCode
     * @return string
     */
    public function getHeadline(?string $storeCode = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_PREFIX . 'headline',
            ScopeInterface::SCOPE_STORE,
            $storeCode
        );
    }

    /**
     * Subtext displayed below the headline on the delivery date widget.
     *
     * @param string|null $storeCode
     * @return string
     */
    public function getSubtext(?string $storeCode = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_PREFIX . 'subtext',
            ScopeInterface::SCOPE_STORE,
            $storeCode
        );
    }
}

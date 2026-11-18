<?php
/**
 * MageTips_Appliances Module
 *
 * Copyright © 2026 Muhammad Umar. All rights reserved.
 * Licensed under the Open Software License (OSL 3.0).
 */

declare(strict_types=1);

namespace MageTips\Appliances\Model\Delivery;

use MageTips\Appliances\Model\Config\Delivery as DeliveryConfig;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Calculates the earliest available delivery date from today based on:
 *  - configured lead days
 *  - which days of the week count as delivery days
 *  - holiday blackout dates
 *
 * Example: leadDays=5, Mon–Fri only, holiday on day 3
 *   → skips weekends and that holiday → earliest date is day 6 from today.
 */
class Calculator
{
    /**
     * @param DeliveryConfig $config
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        protected readonly DeliveryConfig $config,
        protected readonly TimezoneInterface $timezone
    ) {
    }

    /**
     * Returns the earliest delivery date as a \DateTime in the store timezone.
     *
     * @return \DateTime
     * @throws \Exception
     */
    public function getEarliestDate(): \DateTime
    {
        $tz          = new \DateTimeZone($this->timezone->getConfigTimezone());
        $date        = new \DateTime('today', $tz);
        $leadDays    = $this->config->getLeadDays();
        $deliveryDays = $this->config->getEnabledDeliveryDays();
        $holidays    = $this->config->getHolidays();
        $counted     = 0;

        // Safety valve: never loop more than 365 days
        $maxIterations = 365;
        $iterations    = 0;

        while ($counted < $leadDays && $iterations < $maxIterations) {
            $date->modify('+1 day');
            $iterations++;

            // PHP date('N'): 1=Mon … 7=Sun
            if (!in_array((int)$date->format('N'), $deliveryDays, true)) {
                continue;
            }

            if (in_array($date->format('Y-m-d'), $holidays, true)) {
                continue;
            }

            $counted++;
        }

        return $date;
    }

    /**
     * Returns the headline string with {date} replaced by the calculated date.
     *
     * @return string
     * @throws \Exception
     */
    public function getHeadline(): string
    {
        $date     = $this->getEarliestDate();
        $headline = $this->config->getHeadline();
        $formatted = $date->format('l, F j'); // "Wednesday, June 7"

        return str_replace('{date}', $formatted, $headline);
    }

    /**
     * Returns data needed by the JS datepicker:
     *  - earliest: 'Y-m-d' string of the earliest selectable date
     *  - disabledDays: JS day-of-week numbers (0=Sun … 6=Sat) not available for delivery
     *  - holidays: array of 'Y-m-d' strings to disable in the calendar
     *
     * @return array
     * @throws \Exception
     */
    public function getPickerData(): array
    {
        $enabledDays = $this->config->getEnabledDeliveryDays(); // PHP N: 1=Mon … 7=Sun
        $allDays     = [1, 2, 3, 4, 5, 6, 7];

        // Convert to JS day-of-week (0=Sun, 1=Mon … 6=Sat)
        $phpToJs = [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 0];
        $disabledJs = [];
        foreach ($allDays as $phpDay) {
            if (!in_array($phpDay, $enabledDays, true)) {
                $disabledJs[] = $phpToJs[$phpDay];
            }
        }

        return [
            'earliest'     => $this->getEarliestDate()->format('Y-m-d'),
            'disabledDays' => $disabledJs,
            'holidays'     => $this->config->getHolidays(),
        ];
    }
}

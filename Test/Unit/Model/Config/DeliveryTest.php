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

namespace MageTips\Appliances\Test\Unit\Model\Config;

use MageTips\Appliances\Model\Config\Delivery;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeliveryTest extends TestCase
{
    private Delivery $config;

    /** @var ScopeConfigInterface&MockObject */
    private MockObject $scopeConfigMock;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->config = new Delivery($this->scopeConfigMock);
    }

    public function testIsEnabledReturnsTrue(): void
    {
        $this->scopeConfigMock
            ->method('isSetFlag')
            ->with('appliances/delivery/enabled', ScopeInterface::SCOPE_STORE, null)
            ->willReturn(true);

        $this->assertTrue($this->config->isEnabled());
    }

    public function testIsEnabledReturnsFalse(): void
    {
        $this->scopeConfigMock
            ->method('isSetFlag')
            ->willReturn(false);

        $this->assertFalse($this->config->isEnabled());
    }

    public function testGetLeadDaysReturnsConfiguredValue(): void
    {
        $this->scopeConfigMock
            ->method('getValue')
            ->with('appliances/delivery/lead_days', ScopeInterface::SCOPE_STORE, null)
            ->willReturn('5');

        $this->assertSame(5, $this->config->getLeadDays());
    }

    public function testGetLeadDaysReturnsMinOneWhenZero(): void
    {
        $this->scopeConfigMock
            ->method('getValue')
            ->willReturn('0');

        $this->assertSame(1, $this->config->getLeadDays());
    }

    public function testGetLeadDaysReturnsMinOneWhenNull(): void
    {
        $this->scopeConfigMock
            ->method('getValue')
            ->willReturn(null);

        $this->assertSame(1, $this->config->getLeadDays());
    }

    public function testGetEnabledDeliveryDaysReturnsMondayToFriday(): void
    {
        $this->scopeConfigMock
            ->method('isSetFlag')
            ->willReturnCallback(function (string $path) {
                $weekdays = [
                    'appliances/delivery/monday',
                    'appliances/delivery/tuesday',
                    'appliances/delivery/wednesday',
                    'appliances/delivery/thursday',
                    'appliances/delivery/friday',
                ];
                return in_array($path, $weekdays, true);
            });

        $days = $this->config->getEnabledDeliveryDays();

        $this->assertSame([1, 2, 3, 4, 5], $days);
    }

    public function testGetEnabledDeliveryDaysReturnsEmptyWhenNoneEnabled(): void
    {
        $this->scopeConfigMock
            ->method('isSetFlag')
            ->willReturn(false);

        $days = $this->config->getEnabledDeliveryDays();

        $this->assertSame([], $days);
    }

    public function testGetEnabledDeliveryDaysIncludesWeekend(): void
    {
        $this->scopeConfigMock
            ->method('isSetFlag')
            ->willReturnCallback(function (string $path) {
                return in_array($path, [
                    'appliances/delivery/saturday',
                    'appliances/delivery/sunday',
                ], true);
            });

        $days = $this->config->getEnabledDeliveryDays();

        $this->assertContains(6, $days); // Saturday
        $this->assertContains(7, $days); // Sunday
    }

    public function testGetHolidaysReturnsEmptyWhenBlank(): void
    {
        $this->scopeConfigMock
            ->method('getValue')
            ->willReturn('');

        $this->assertSame([], $this->config->getHolidays());
    }

    public function testGetHolidaysReturnsEmptyWhenWhitespace(): void
    {
        $this->scopeConfigMock
            ->method('getValue')
            ->willReturn('   ');

        $this->assertSame([], $this->config->getHolidays());
    }

    public function testGetHolidaysParsesValidDates(): void
    {
        $this->scopeConfigMock
            ->method('getValue')
            ->willReturn("2026-12-25\n2026-01-01\n2026-07-04");

        $holidays = $this->config->getHolidays();

        $this->assertSame(['2026-12-25', '2026-01-01', '2026-07-04'], $holidays);
    }

    public function testGetHolidaysFiltersInvalidLines(): void
    {
        $this->scopeConfigMock
            ->method('getValue')
            ->willReturn("2026-12-25\nnot-a-date\n\n2026-01-01");

        $holidays = $this->config->getHolidays();

        $this->assertSame(['2026-12-25', '2026-01-01'], $holidays);
    }

    public function testGetHeadlineReturnsConfiguredString(): void
    {
        $this->scopeConfigMock
            ->method('getValue')
            ->with('appliances/delivery/headline', ScopeInterface::SCOPE_STORE, null)
            ->willReturn('Estimated delivery: {date}');

        $this->assertSame('Estimated delivery: {date}', $this->config->getHeadline());
    }

    public function testGetSubtextReturnsConfiguredString(): void
    {
        $this->scopeConfigMock
            ->method('getValue')
            ->with('appliances/delivery/subtext', ScopeInterface::SCOPE_STORE, null)
            ->willReturn('Free delivery on appliances');

        $this->assertSame('Free delivery on appliances', $this->config->getSubtext());
    }

    public function testGetHeadlineReturnsEmptyStringWhenNotConfigured(): void
    {
        $this->scopeConfigMock
            ->method('getValue')
            ->willReturn(null);

        $this->assertSame('', $this->config->getHeadline());
    }
}

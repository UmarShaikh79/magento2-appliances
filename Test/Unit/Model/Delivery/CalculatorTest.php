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

namespace MageTips\Appliances\Test\Unit\Model\Delivery;

use MageTips\Appliances\Model\Config\Delivery as DeliveryConfig;
use MageTips\Appliances\Model\Delivery\Calculator;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CalculatorTest extends TestCase
{
    private Calculator $calculator;

    /** @var DeliveryConfig&MockObject */
    private MockObject $configMock;

    /** @var TimezoneInterface&MockObject */
    private MockObject $timezoneMock;

    protected function setUp(): void
    {
        $this->configMock   = $this->createMock(DeliveryConfig::class);
        $this->timezoneMock = $this->createMock(TimezoneInterface::class);

        $this->timezoneMock
            ->method('getConfigTimezone')
            ->willReturn('UTC');

        $this->calculator = new Calculator($this->configMock, $this->timezoneMock);
    }

    public function testGetEarliestDateSkipsWeekends(): void
    {
        // 3 lead days, Mon-Fri only, no holidays
        $this->configMock->method('getLeadDays')->willReturn(3);
        $this->configMock->method('getEnabledDeliveryDays')->willReturn([1, 2, 3, 4, 5]);
        $this->configMock->method('getHolidays')->willReturn([]);

        $result = $this->calculator->getEarliestDate();

        $this->assertInstanceOf(\DateTime::class, $result);
        // Result must be at least 3 days from today
        $today = new \DateTime('today', new \DateTimeZone('UTC'));
        $diff  = (int)$today->diff($result)->days;
        $this->assertGreaterThanOrEqual(3, $diff);
    }

    public function testGetEarliestDateSkipsHolidays(): void
    {
        // 1 lead day, all days enabled
        $this->configMock->method('getLeadDays')->willReturn(1);
        $this->configMock->method('getEnabledDeliveryDays')->willReturn([1, 2, 3, 4, 5, 6, 7]);

        // Make the next 10 days holidays so we skip all of them
        $holidays = [];
        $d = new \DateTime('today', new \DateTimeZone('UTC'));
        for ($i = 1; $i <= 10; $i++) {
            $d->modify('+1 day');
            $holidays[] = $d->format('Y-m-d');
        }
        $this->configMock->method('getHolidays')->willReturn($holidays);

        $result = $this->calculator->getEarliestDate();

        $this->assertInstanceOf(\DateTime::class, $result);
        // Result must be beyond the 10-holiday block
        $today = new \DateTime('today', new \DateTimeZone('UTC'));
        $diff  = (int)$today->diff($result)->days;
        $this->assertGreaterThan(10, $diff);
    }

    public function testGetEarliestDateWithAllDaysEnabled(): void
    {
        $this->configMock->method('getLeadDays')->willReturn(2);
        $this->configMock->method('getEnabledDeliveryDays')->willReturn([1, 2, 3, 4, 5, 6, 7]);
        $this->configMock->method('getHolidays')->willReturn([]);

        $result = $this->calculator->getEarliestDate();

        $today = new \DateTime('today', new \DateTimeZone('UTC'));
        $diff  = (int)$today->diff($result)->days;
        $this->assertSame(2, $diff);
    }

    public function testGetHeadlineReplacesDatePlaceholder(): void
    {
        $this->configMock->method('getLeadDays')->willReturn(1);
        $this->configMock->method('getEnabledDeliveryDays')->willReturn([1, 2, 3, 4, 5, 6, 7]);
        $this->configMock->method('getHolidays')->willReturn([]);
        $this->configMock->method('getHeadline')->willReturn('Get it by {date}');

        $headline = $this->calculator->getHeadline();

        $this->assertStringNotContainsString('{date}', $headline);
        $this->assertStringStartsWith('Get it by ', $headline);
    }

    public function testGetHeadlineWithNoPlaceholder(): void
    {
        $this->configMock->method('getLeadDays')->willReturn(1);
        $this->configMock->method('getEnabledDeliveryDays')->willReturn([1, 2, 3, 4, 5, 6, 7]);
        $this->configMock->method('getHolidays')->willReturn([]);
        $this->configMock->method('getHeadline')->willReturn('Delivery available');

        $headline = $this->calculator->getHeadline();

        $this->assertSame('Delivery available', $headline);
    }

    public function testGetPickerDataReturnsExpectedStructure(): void
    {
        $this->configMock->method('getLeadDays')->willReturn(1);
        $this->configMock->method('getEnabledDeliveryDays')->willReturn([1, 2, 3, 4, 5]); // Mon-Fri
        $this->configMock->method('getHolidays')->willReturn(['2026-12-25']);

        $data = $this->calculator->getPickerData();

        $this->assertArrayHasKey('earliest', $data);
        $this->assertArrayHasKey('disabledDays', $data);
        $this->assertArrayHasKey('holidays', $data);

        // Saturday (6→JS:6) and Sunday (7→JS:0) should be disabled
        $this->assertContains(6, $data['disabledDays']);
        $this->assertContains(0, $data['disabledDays']);

        // Monday–Friday should NOT be in disabledDays
        $this->assertNotContains(1, $data['disabledDays']);
        $this->assertNotContains(2, $data['disabledDays']);

        $this->assertContains('2026-12-25', $data['holidays']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $data['earliest']);
    }

    public function testGetPickerDataDisabledDaysWhenAllDaysEnabled(): void
    {
        $this->configMock->method('getLeadDays')->willReturn(1);
        $this->configMock->method('getEnabledDeliveryDays')->willReturn([1, 2, 3, 4, 5, 6, 7]);
        $this->configMock->method('getHolidays')->willReturn([]);

        $data = $this->calculator->getPickerData();

        $this->assertSame([], $data['disabledDays']);
    }
}

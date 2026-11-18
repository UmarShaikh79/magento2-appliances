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

namespace MageTips\Appliances\Test\Unit\Plugin\Quote\Item;

use MageTips\Appliances\Plugin\Quote\Item\ToOrderItemPlugin;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Quote\Model\Quote\Item\ToOrderItem;
use Magento\Sales\Model\Order\Item as OrderItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ToOrderItemPluginTest extends TestCase
{
    private ToOrderItemPlugin $plugin;

    /** @var ToOrderItem&MockObject */
    private MockObject $subjectMock;

    protected function setUp(): void
    {
        $this->plugin      = new ToOrderItemPlugin();
        $this->subjectMock = $this->createMock(ToOrderItem::class);
    }

    public function testAfterConvertCopiesAllCustomColumns(): void
    {
        $quoteItemMock = $this->createMock(AbstractItem::class);
        $quoteItemMock
            ->method('getData')
            ->willReturnCallback(fn($key) => match ($key) {
                'delivery_date'  => '2026-08-01',
                'epp_plan_code'  => 'EPP_3YR',
                'epp_plan_name'  => '3-Year Plan',
                'epp_plan_price' => 99.99,
                'epp_plan_years' => 3,
                default          => null,
            });

        $orderItemMock = $this->createMock(OrderItem::class);

        $setData = [];
        $orderItemMock
            ->method('setData')
            ->willReturnCallback(function ($key, $val) use (&$setData, $orderItemMock) {
                $setData[$key] = $val;
                return $orderItemMock;
            });

        $result = $this->plugin->afterConvert($this->subjectMock, $orderItemMock, $quoteItemMock);

        $this->assertSame($orderItemMock, $result);
        $this->assertSame('2026-08-01', $setData['delivery_date']);
        $this->assertSame('EPP_3YR', $setData['epp_plan_code']);
        $this->assertSame('3-Year Plan', $setData['epp_plan_name']);
        $this->assertSame(99.99, $setData['epp_plan_price']);
        $this->assertSame(3, $setData['epp_plan_years']);
    }

    public function testAfterConvertSkipsNullValues(): void
    {
        $quoteItemMock = $this->createMock(AbstractItem::class);
        $quoteItemMock->method('getData')->willReturn(null);

        $orderItemMock = $this->createMock(OrderItem::class);
        $orderItemMock->expects($this->never())->method('setData');

        $result = $this->plugin->afterConvert($this->subjectMock, $orderItemMock, $quoteItemMock);

        $this->assertSame($orderItemMock, $result);
    }

    public function testAfterConvertSkipsEmptyStringValues(): void
    {
        $quoteItemMock = $this->createMock(AbstractItem::class);
        $quoteItemMock->method('getData')->willReturn('');

        $orderItemMock = $this->createMock(OrderItem::class);
        $orderItemMock->expects($this->never())->method('setData');

        $result = $this->plugin->afterConvert($this->subjectMock, $orderItemMock, $quoteItemMock);

        $this->assertSame($orderItemMock, $result);
    }

    public function testAfterConvertCopiesOnlyPopulatedColumns(): void
    {
        $quoteItemMock = $this->createMock(AbstractItem::class);
        $quoteItemMock
            ->method('getData')
            ->willReturnCallback(fn($key) => match ($key) {
                'delivery_date' => '2026-09-15',
                default         => null, // EPP fields are null
            });

        $orderItemMock = $this->createMock(OrderItem::class);

        $setData = [];
        $orderItemMock
            ->method('setData')
            ->willReturnCallback(function ($key, $val) use (&$setData, $orderItemMock) {
                $setData[$key] = $val;
                return $orderItemMock;
            });

        $this->plugin->afterConvert($this->subjectMock, $orderItemMock, $quoteItemMock);

        $this->assertArrayHasKey('delivery_date', $setData);
        $this->assertArrayNotHasKey('epp_plan_code', $setData);
    }
}

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

namespace MageTips\Appliances\Test\Unit\Plugin\Sales\Order\Email\Items;

use MageTips\Appliances\Plugin\Sales\Order\Email\Items\AddEppAndDeliveryPlugin;
use Magento\Sales\Block\Order\Email\Items\DefaultItems;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;
use Magento\Sales\Model\Order\Item as OrderItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddEppAndDeliveryPluginTest extends TestCase
{
    private AddEppAndDeliveryPlugin $plugin;

    /** @var DefaultItems&MockObject */
    private MockObject $subjectMock;

    /** @var InvoiceItem&MockObject */
    private MockObject $invoiceItemMock;

    /** @var OrderItem&MockObject */
    private MockObject $orderItemMock;

    protected function setUp(): void
    {
        $this->plugin          = new AddEppAndDeliveryPlugin();
        $this->invoiceItemMock = $this->createMock(InvoiceItem::class);
        $this->orderItemMock   = $this->createMock(OrderItem::class);

        // getItem() is a magic __call method on DefaultItems; stub __call to intercept it.
        $this->subjectMock = $this->getMockBuilder(DefaultItems::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();
    }

    public function testAfterGetItemOptionsReturnsUnchangedWhenItemIsNull(): void
    {
        $this->subjectMock->method('__call')
            ->willReturnCallback(fn(string $m, array $a) => null);

        $result = $this->plugin->afterGetItemOptions($this->subjectMock, []);

        $this->assertSame([], $result);
    }

    public function testAfterGetItemOptionsReturnsUnchangedWhenOrderItemIsNull(): void
    {
        $this->subjectMock->method('__call')
            ->willReturnCallback(fn(string $m, array $a) => $m === 'getItem' ? $this->invoiceItemMock : null);
        $this->invoiceItemMock->method('getOrderItem')->willReturn(null);

        $result = $this->plugin->afterGetItemOptions($this->subjectMock, []);

        $this->assertSame([], $result);
    }

    public function testAfterGetItemOptionsReturnsUnchangedWhenNoData(): void
    {
        $this->subjectMock->method('__call')
            ->willReturnCallback(fn(string $m, array $a) => $m === 'getItem' ? $this->invoiceItemMock : null);
        $this->invoiceItemMock->method('getOrderItem')->willReturn($this->orderItemMock);
        $this->orderItemMock->method('getData')->willReturn(null);

        $result = $this->plugin->afterGetItemOptions($this->subjectMock, []);

        $this->assertSame([], $result);
    }

    public function testAfterGetItemOptionsAppendsEppPlanRow(): void
    {
        $this->subjectMock->method('__call')
            ->willReturnCallback(fn(string $m, array $a) => $m === 'getItem' ? $this->invoiceItemMock : null);
        $this->invoiceItemMock->method('getOrderItem')->willReturn($this->orderItemMock);
        $this->orderItemMock
            ->method('getData')
            ->willReturnCallback(fn($key) => match ($key) {
                'epp_plan_name'  => '3-Year Plan',
                'epp_plan_price' => 99.99,
                'epp_plan_years' => 3,
                'delivery_date'  => null,
                default          => null,
            });

        $result = $this->plugin->afterGetItemOptions($this->subjectMock, []);

        $this->assertCount(1, $result);
        $this->assertSame('Protection Plan', $result[0]['label']);
        $this->assertStringContainsString('3-Year Plan', $result[0]['value']);
        $this->assertStringContainsString('3 years', $result[0]['value']);
        $this->assertStringContainsString('99.99', $result[0]['value']);
    }

    public function testAfterGetItemOptionsAppendsDeliveryDateRow(): void
    {
        $this->subjectMock->method('__call')
            ->willReturnCallback(fn(string $m, array $a) => $m === 'getItem' ? $this->invoiceItemMock : null);
        $this->invoiceItemMock->method('getOrderItem')->willReturn($this->orderItemMock);
        $this->orderItemMock
            ->method('getData')
            ->willReturnCallback(fn($key) => match ($key) {
                'epp_plan_name' => '',
                'epp_plan_years' => 0,
                'delivery_date' => '2026-11-27',
                default         => null,
            });

        $result = $this->plugin->afterGetItemOptions($this->subjectMock, []);

        $this->assertCount(1, $result);
        $this->assertSame('Delivery Date', $result[0]['label']);
        $this->assertSame('November 27, 2026', $result[0]['value']);
    }

    public function testAfterGetItemOptionsAppendsBothRows(): void
    {
        $this->subjectMock->method('__call')
            ->willReturnCallback(fn(string $m, array $a) => $m === 'getItem' ? $this->invoiceItemMock : null);
        $this->invoiceItemMock->method('getOrderItem')->willReturn($this->orderItemMock);
        $this->orderItemMock
            ->method('getData')
            ->willReturnCallback(fn($key) => match ($key) {
                'epp_plan_name'  => '1-Year Plan',
                'epp_plan_price' => 29.99,
                'epp_plan_years' => 1,
                'delivery_date'  => '2026-10-01',
                default          => null,
            });

        $result = $this->plugin->afterGetItemOptions($this->subjectMock, []);

        $this->assertCount(2, $result);
        $this->assertSame('Protection Plan', $result[0]['label']);
        $this->assertStringContainsString('1 year)', $result[0]['value']);
        $this->assertSame('Delivery Date', $result[1]['label']);
        $this->assertSame('October 1, 2026', $result[1]['value']);
    }

    public function testAfterGetItemOptionsHandlesInvalidDeliveryDateFormat(): void
    {
        $this->subjectMock->method('__call')
            ->willReturnCallback(fn(string $m, array $a) => $m === 'getItem' ? $this->invoiceItemMock : null);
        $this->invoiceItemMock->method('getOrderItem')->willReturn($this->orderItemMock);
        $this->orderItemMock
            ->method('getData')
            ->willReturnCallback(fn($key) => match ($key) {
                'epp_plan_name' => '',
                'epp_plan_years' => 0,
                'delivery_date' => 'bad-date',
                default         => null,
            });

        $result = $this->plugin->afterGetItemOptions($this->subjectMock, []);

        $this->assertCount(1, $result);
        $this->assertSame('bad-date', $result[0]['value']);
    }
}

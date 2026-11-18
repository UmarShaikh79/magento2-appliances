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

namespace MageTips\Appliances\Test\Unit\Plugin\Sales\Adminhtml\Items\Column;

use MageTips\Appliances\Plugin\Sales\Adminhtml\Items\Column\AddEppAndDeliveryPlugin;
use Magento\Sales\Block\Adminhtml\Items\Column\DefaultColumn;
use Magento\Sales\Model\Order\Item as OrderItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddEppAndDeliveryPluginTest extends TestCase
{
    private AddEppAndDeliveryPlugin $plugin;

    /** @var DefaultColumn&MockObject */
    private MockObject $subjectMock;

    /** @var OrderItem&MockObject */
    private MockObject $orderItemMock;

    protected function setUp(): void
    {
        $this->plugin        = new AddEppAndDeliveryPlugin();
        $this->subjectMock   = $this->createMock(DefaultColumn::class);
        $this->orderItemMock = $this->createMock(OrderItem::class);
    }

    public function testAfterGetOrderOptionsReturnsUnchangedWhenNoItem(): void
    {
        $this->subjectMock->method('getItem')->willReturn(null);

        $result = $this->plugin->afterGetOrderOptions($this->subjectMock, []);

        $this->assertSame([], $result);
    }

    public function testAfterGetOrderOptionsReturnsUnchangedWhenNoData(): void
    {
        $this->subjectMock->method('getItem')->willReturn($this->orderItemMock);
        $this->orderItemMock->method('getData')->willReturn(null);

        $result = $this->plugin->afterGetOrderOptions($this->subjectMock, []);

        $this->assertSame([], $result);
    }

    public function testAfterGetOrderOptionsAppendsEppPlanRow(): void
    {
        $this->subjectMock->method('getItem')->willReturn($this->orderItemMock);
        $this->orderItemMock
            ->method('getData')
            ->willReturnCallback(fn($key) => match ($key) {
                'epp_plan_name'  => '3-Year Plan',
                'epp_plan_price' => 99.99,
                'epp_plan_years' => 3,
                'delivery_date'  => null,
                default          => null,
            });

        $result = $this->plugin->afterGetOrderOptions($this->subjectMock, []);

        $this->assertCount(1, $result);
        $this->assertSame('Protection Plan', $result[0]['label']);
        $this->assertStringContainsString('3 years', $result[0]['value']);
        $this->assertStringContainsString('99.99', $result[0]['value']);
    }

    public function testAfterGetOrderOptionsAppendsSingularYear(): void
    {
        $this->subjectMock->method('getItem')->willReturn($this->orderItemMock);
        $this->orderItemMock
            ->method('getData')
            ->willReturnCallback(fn($key) => match ($key) {
                'epp_plan_name'  => '1-Year Plan',
                'epp_plan_price' => null,
                'epp_plan_years' => 1,
                'delivery_date'  => null,
                default          => null,
            });

        $result = $this->plugin->afterGetOrderOptions($this->subjectMock, []);

        $this->assertStringContainsString('1 year)', $result[0]['value']);
    }

    public function testAfterGetOrderOptionsAppendsDeliveryDate(): void
    {
        $this->subjectMock->method('getItem')->willReturn($this->orderItemMock);
        $this->orderItemMock
            ->method('getData')
            ->willReturnCallback(fn($key) => match ($key) {
                'epp_plan_name'  => '',
                'epp_plan_years' => 0,
                'delivery_date'  => '2026-08-15',
                default          => null,
            });

        $result = $this->plugin->afterGetOrderOptions($this->subjectMock, []);

        $this->assertCount(1, $result);
        $this->assertSame('Delivery Date', $result[0]['label']);
        $this->assertSame('August 15, 2026', $result[0]['value']);
    }

    public function testAfterGetOrderOptionsAppendsBothRows(): void
    {
        $this->subjectMock->method('getItem')->willReturn($this->orderItemMock);
        $this->orderItemMock
            ->method('getData')
            ->willReturnCallback(fn($key) => match ($key) {
                'epp_plan_name'  => '2-Year Plan',
                'epp_plan_price' => 49.99,
                'epp_plan_years' => 2,
                'delivery_date'  => '2026-12-01',
                default          => null,
            });

        $result = $this->plugin->afterGetOrderOptions($this->subjectMock, []);

        $this->assertCount(2, $result);
    }

    public function testAfterGetOrderOptionsHandlesInvalidDate(): void
    {
        $this->subjectMock->method('getItem')->willReturn($this->orderItemMock);
        $this->orderItemMock
            ->method('getData')
            ->willReturnCallback(fn($key) => match ($key) {
                'epp_plan_name'  => '',
                'epp_plan_years' => 0,
                'delivery_date'  => 'not-a-date',
                default          => null,
            });

        $result = $this->plugin->afterGetOrderOptions($this->subjectMock, []);

        $this->assertSame('not-a-date', $result[0]['value']);
    }
}

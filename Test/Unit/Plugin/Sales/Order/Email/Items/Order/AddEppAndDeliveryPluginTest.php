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

namespace MageTips\Appliances\Test\Unit\Plugin\Sales\Order\Email\Items\Order;

use MageTips\Appliances\Plugin\Sales\Order\Email\Items\Order\AddEppAndDeliveryPlugin;
use Magento\Sales\Block\Order\Email\Items\Order\DefaultOrder;
use Magento\Sales\Model\Order\Item as OrderItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddEppAndDeliveryPluginTest extends TestCase
{
    private AddEppAndDeliveryPlugin $plugin;

    /** @var DefaultOrder&MockObject */
    private MockObject $subjectMock;

    /** @var OrderItem&MockObject */
    private MockObject $orderItemMock;

    protected function setUp(): void
    {
        $this->plugin        = new AddEppAndDeliveryPlugin();
        $this->orderItemMock = $this->createMock(OrderItem::class);

        // getItem() is a magic __call method on DefaultOrder; stub __call to intercept it.
        $this->subjectMock = $this->getMockBuilder(DefaultOrder::class)
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

    public function testAfterGetItemOptionsReturnsUnchangedWhenNoEppOrDelivery(): void
    {
        $this->subjectMock->method('__call')
            ->willReturnCallback(fn(string $m, array $a) => $m === 'getItem' ? $this->orderItemMock : null);
        $this->orderItemMock->method('getData')->willReturn(null);

        $result = $this->plugin->afterGetItemOptions($this->subjectMock, []);

        $this->assertSame([], $result);
    }

    public function testAfterGetItemOptionsAppendsEppWithPrice(): void
    {
        $this->subjectMock->method('__call')
            ->willReturnCallback(fn(string $m, array $a) => $m === 'getItem' ? $this->orderItemMock : null);
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

    public function testAfterGetItemOptionsAppendsEppWithoutPrice(): void
    {
        $this->subjectMock->method('__call')
            ->willReturnCallback(fn(string $m, array $a) => $m === 'getItem' ? $this->orderItemMock : null);
        $this->orderItemMock
            ->method('getData')
            ->willReturnCallback(fn($key) => match ($key) {
                'epp_plan_name'  => '5-Year Premium',
                'epp_plan_price' => null,
                'epp_plan_years' => 5,
                'delivery_date'  => null,
                default          => null,
            });

        $result = $this->plugin->afterGetItemOptions($this->subjectMock, []);

        $this->assertCount(1, $result);
        $this->assertStringNotContainsString('$', $result[0]['value']);
        $this->assertStringContainsString('5 years', $result[0]['value']);
    }

    public function testAfterGetItemOptionsAppendsDeliveryDate(): void
    {
        $this->subjectMock->method('__call')
            ->willReturnCallback(fn(string $m, array $a) => $m === 'getItem' ? $this->orderItemMock : null);
        $this->orderItemMock
            ->method('getData')
            ->willReturnCallback(fn($key) => match ($key) {
                'epp_plan_name' => '',
                'epp_plan_years' => 0,
                'delivery_date' => '2026-12-20',
                default         => null,
            });

        $result = $this->plugin->afterGetItemOptions($this->subjectMock, []);

        $this->assertCount(1, $result);
        $this->assertSame('Delivery Date', $result[0]['label']);
        $this->assertSame('December 20, 2026', $result[0]['value']);
    }

    public function testAfterGetItemOptionsAppendsBothRows(): void
    {
        $this->subjectMock->method('__call')
            ->willReturnCallback(fn(string $m, array $a) => $m === 'getItem' ? $this->orderItemMock : null);
        $this->orderItemMock
            ->method('getData')
            ->willReturnCallback(fn($key) => match ($key) {
                'epp_plan_name'  => '2-Year Plan',
                'epp_plan_price' => 49.99,
                'epp_plan_years' => 2,
                'delivery_date'  => '2026-07-04',
                default          => null,
            });

        $result = $this->plugin->afterGetItemOptions($this->subjectMock, []);

        $this->assertCount(2, $result);
        $this->assertSame('Protection Plan', $result[0]['label']);
        $this->assertSame('Delivery Date', $result[1]['label']);
        $this->assertSame('July 4, 2026', $result[1]['value']);
    }

    public function testAfterGetItemOptionsSingularYear(): void
    {
        $this->subjectMock->method('__call')
            ->willReturnCallback(fn(string $m, array $a) => $m === 'getItem' ? $this->orderItemMock : null);
        $this->orderItemMock
            ->method('getData')
            ->willReturnCallback(fn($key) => match ($key) {
                'epp_plan_name'  => '1-Year Plan',
                'epp_plan_price' => null,
                'epp_plan_years' => 1,
                'delivery_date'  => null,
                default          => null,
            });

        $result = $this->plugin->afterGetItemOptions($this->subjectMock, []);

        $this->assertStringContainsString('1 year)', $result[0]['value']);
    }
}

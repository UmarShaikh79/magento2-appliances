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

namespace MageTips\Appliances\Test\Unit\Plugin\Sales\Order\Item\Renderer;

use MageTips\Appliances\Plugin\Sales\Order\Item\Renderer\AddEppAndDeliveryPlugin;
use Magento\Sales\Block\Order\Item\Renderer\DefaultRenderer;
use Magento\Sales\Model\Order\Item as OrderItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddEppAndDeliveryPluginTest extends TestCase
{
    private AddEppAndDeliveryPlugin $plugin;

    /** @var DefaultRenderer&MockObject */
    private MockObject $subjectMock;

    /** @var OrderItem&MockObject */
    private MockObject $orderItemMock;

    protected function setUp(): void
    {
        $this->plugin        = new AddEppAndDeliveryPlugin();
        $this->subjectMock   = $this->createMock(DefaultRenderer::class);
        $this->orderItemMock = $this->createMock(OrderItem::class);

        $this->subjectMock->method('getOrderItem')->willReturn($this->orderItemMock);
    }

    public function testAfterGetItemOptionsReturnsUnchangedWhenNoEppOrDelivery(): void
    {
        $this->orderItemMock->method('getData')->willReturn(null);

        $result = $this->plugin->afterGetItemOptions($this->subjectMock, []);

        $this->assertSame([], $result);
    }

    public function testAfterGetItemOptionsAppendsEppPlanRow(): void
    {
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
        $this->orderItemMock
            ->method('getData')
            ->willReturnCallback(fn($key) => match ($key) {
                'epp_plan_name'  => '',
                'epp_plan_price' => null,
                'epp_plan_years' => 0,
                'delivery_date'  => '2026-08-15',
                default          => null,
            });

        $result = $this->plugin->afterGetItemOptions($this->subjectMock, []);

        $this->assertCount(1, $result);
        $this->assertSame('Delivery Date', $result[0]['label']);
        $this->assertSame('August 15, 2026', $result[0]['value']);
    }

    public function testAfterGetItemOptionsAppendsBothRows(): void
    {
        $this->orderItemMock
            ->method('getData')
            ->willReturnCallback(fn($key) => match ($key) {
                'epp_plan_name'  => '2-Year Plan',
                'epp_plan_price' => 49.99,
                'epp_plan_years' => 2,
                'delivery_date'  => '2026-09-01',
                default          => null,
            });

        $existing = [['label' => 'Color', 'value' => 'Blue']];
        $result   = $this->plugin->afterGetItemOptions($this->subjectMock, $existing);

        $this->assertCount(3, $result);
        $this->assertSame('Color', $result[0]['label']);
        $this->assertSame('Protection Plan', $result[1]['label']);
        $this->assertSame('Delivery Date', $result[2]['label']);
    }

    public function testAfterGetItemOptionsSingularYearLabel(): void
    {
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

        $this->assertCount(1, $result);
        $this->assertStringContainsString('1 year)', $result[0]['value']);
        $this->assertStringNotContainsString('years', $result[0]['value']);
    }

    public function testAfterGetItemOptionsHandlesInvalidDeliveryDate(): void
    {
        $this->orderItemMock
            ->method('getData')
            ->willReturnCallback(fn($key) => match ($key) {
                'epp_plan_name' => '',
                'epp_plan_years' => 0,
                'delivery_date' => 'not-a-valid-date',
                default         => null,
            });

        $result = $this->plugin->afterGetItemOptions($this->subjectMock, []);

        $this->assertCount(1, $result);
        $this->assertSame('not-a-valid-date', $result[0]['value']);
    }
}

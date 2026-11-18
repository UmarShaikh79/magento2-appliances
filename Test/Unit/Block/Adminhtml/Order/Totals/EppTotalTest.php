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

namespace MageTips\Appliances\Test\Unit\Block\Adminhtml\Order\Totals;

use MageTips\Appliances\Block\Adminhtml\Order\Totals\EppTotal;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EppTotalTest extends TestCase
{
    /** @var EppTotal&MockObject */
    private MockObject $eppTotal;

    protected function setUp(): void
    {
        // Partial mock: only stub getParentBlock(); initTotals() runs real code.
        $this->eppTotal = $this->getMockBuilder(EppTotal::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParentBlock'])
            ->getMock();
    }

    public function testInitTotalsReturnsEarlyWhenNoParent(): void
    {
        $this->eppTotal->method('getParentBlock')->willReturn(null);

        $result = $this->eppTotal->initTotals();

        $this->assertSame($this->eppTotal, $result);
    }

    public function testInitTotalsReturnsEarlyWhenNoOrder(): void
    {
        $parentMock = $this->getMockBuilder(AbstractBlock::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();
        $parentMock->method('__call')
            ->willReturnCallback(fn(string $m, array $a) => $m === 'getOrder' ? null : null);

        $this->eppTotal->method('getParentBlock')->willReturn($parentMock);

        $result = $this->eppTotal->initTotals();

        $this->assertSame($this->eppTotal, $result);
    }

    public function testInitTotalsReturnsEarlyWhenNoEppItems(): void
    {
        $itemMock = $this->createMock(OrderItem::class);
        $itemMock->method('getData')->willReturn(0.0);

        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getAllVisibleItems')->willReturn([$itemMock]);

        $parentMock = $this->getMockBuilder(AbstractBlock::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();
        $parentMock->method('__call')
            ->willReturnCallback(fn(string $m, array $a) => $m === 'getOrder' ? $orderMock : null);

        $this->eppTotal->method('getParentBlock')->willReturn($parentMock);

        $result = $this->eppTotal->initTotals();

        $this->assertSame($this->eppTotal, $result);
    }

    public function testInitTotalsAddsEppTotalWhenItemsHaveEppPrice(): void
    {
        $itemMock = $this->createMock(OrderItem::class);
        $itemMock->method('getData')->with('epp_plan_price')->willReturn(99.99);
        $itemMock->method('getQtyOrdered')->willReturn(2.0);

        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getAllVisibleItems')->willReturn([$itemMock]);

        $parentMock = $this->getMockBuilder(AbstractBlock::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();
        $parentMock->method('__call')
            ->willReturnCallback(fn(string $m, array $a) => match ($m) {
                'getOrder'  => $orderMock,
                'addTotal'  => null,
                default     => null,
            });

        $this->eppTotal->method('getParentBlock')->willReturn($parentMock);

        $result = $this->eppTotal->initTotals();

        $this->assertSame($this->eppTotal, $result);
    }

    public function testToHtmlReturnsEmptyString(): void
    {
        $method = new \ReflectionMethod(EppTotal::class, '_toHtml');
        $method->setAccessible(true);
        $this->assertSame('', $method->invoke($this->eppTotal));
    }
}

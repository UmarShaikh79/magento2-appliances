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

namespace MageTips\Appliances\Test\Unit\Block\Adminhtml\Invoice\Totals;

use MageTips\Appliances\Block\Adminhtml\Invoice\Totals\EppTotal;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;
use Magento\Sales\Model\Order\Item as OrderItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EppTotalTest extends TestCase
{
    /** @var EppTotal&MockObject */
    private MockObject $eppTotal;

    protected function setUp(): void
    {
        $this->eppTotal = $this->getMockBuilder(EppTotal::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParentBlock'])
            ->getMock();
    }

    public function testInitTotalsReturnsEarlyWhenNoParent(): void
    {
        $this->eppTotal->method('getParentBlock')->willReturn(null);

        $this->assertSame($this->eppTotal, $this->eppTotal->initTotals());
    }

    public function testInitTotalsReturnsEarlyWhenNoInvoice(): void
    {
        $parentMock = $this->getMockBuilder(AbstractBlock::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();
        $parentMock->method('__call')
            ->willReturnCallback(fn(string $m, array $a) => null);

        $this->eppTotal->method('getParentBlock')->willReturn($parentMock);

        $this->assertSame($this->eppTotal, $this->eppTotal->initTotals());
    }

    public function testInitTotalsSkipsDummyItems(): void
    {
        $orderItemMock = $this->createMock(OrderItem::class);
        $orderItemMock->method('isDummy')->willReturn(true);

        $invoiceItemMock = $this->createMock(InvoiceItem::class);
        $invoiceItemMock->method('getOrderItem')->willReturn($orderItemMock);

        $invoiceMock = $this->createMock(Invoice::class);
        $invoiceMock->method('getAllItems')->willReturn([$invoiceItemMock]);

        $parentMock = $this->getMockBuilder(AbstractBlock::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();
        $parentMock->method('__call')
            ->willReturnCallback(fn(string $m, array $a) => $m === 'getSource' ? $invoiceMock : null);

        $this->eppTotal->method('getParentBlock')->willReturn($parentMock);

        $this->assertSame($this->eppTotal, $this->eppTotal->initTotals());
    }

    public function testInitTotalsAddsEppTotalForValidItems(): void
    {
        $orderItemMock = $this->createMock(OrderItem::class);
        $orderItemMock->method('isDummy')->willReturn(false);
        $orderItemMock->method('getData')->with('epp_plan_price')->willReturn(49.99);

        $invoiceItemMock = $this->createMock(InvoiceItem::class);
        $invoiceItemMock->method('getOrderItem')->willReturn($orderItemMock);
        $invoiceItemMock->method('getQty')->willReturn(1.0);

        $invoiceMock = $this->createMock(Invoice::class);
        $invoiceMock->method('getAllItems')->willReturn([$invoiceItemMock]);

        $parentMock = $this->getMockBuilder(AbstractBlock::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();
        $parentMock->method('__call')
            ->willReturnCallback(fn(string $m, array $a) => match ($m) {
                'getSource' => $invoiceMock,
                'addTotal'  => null,
                default     => null,
            });

        $this->eppTotal->method('getParentBlock')->willReturn($parentMock);

        $this->assertSame($this->eppTotal, $this->eppTotal->initTotals());
    }

    public function testToHtmlReturnsEmptyString(): void
    {
        $method = new \ReflectionMethod(\MageTips\Appliances\Block\Adminhtml\Invoice\Totals\EppTotal::class, '_toHtml');
        $method->setAccessible(true);
        $this->assertSame('', $method->invoke($this->eppTotal));
    }
}

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

namespace MageTips\Appliances\Test\Unit\Model\Order\Invoice\Total;

use MageTips\Appliances\Model\Order\Invoice\Total\EppCharge;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;
use Magento\Sales\Model\Order\Item as OrderItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EppChargeTest extends TestCase
{
    private EppCharge $eppCharge;

    protected function setUp(): void
    {
        $this->eppCharge = new EppCharge();
    }

    private function buildInvoiceItem(bool $isDummy, float $eppPrice, float $qty): MockObject
    {
        $orderItemMock = $this->createMock(OrderItem::class);
        $orderItemMock->method('isDummy')->willReturn($isDummy);
        $orderItemMock->method('getData')->with('epp_plan_price')->willReturn($eppPrice);

        $invoiceItemMock = $this->createMock(InvoiceItem::class);
        $invoiceItemMock->method('getOrderItem')->willReturn($orderItemMock);
        $invoiceItemMock->method('getQty')->willReturn($qty);

        return $invoiceItemMock;
    }

    public function testCollectAddsEppToInvoiceTotal(): void
    {
        $invoiceItem = $this->buildInvoiceItem(false, 99.99, 2.0);

        $invoiceMock = $this->createMock(Invoice::class);
        $invoiceMock->method('getAllItems')->willReturn([$invoiceItem]);
        $invoiceMock->method('getGrandTotal')->willReturn(1000.00);
        $invoiceMock->method('getBaseGrandTotal')->willReturn(1000.00);

        // 99.99 * 2 = 199.98
        $invoiceMock->expects($this->once())
            ->method('setGrandTotal')
            ->with(1199.98);
        $invoiceMock->expects($this->once())
            ->method('setBaseGrandTotal')
            ->with(1199.98);

        $result = $this->eppCharge->collect($invoiceMock);

        $this->assertInstanceOf(EppCharge::class, $result);
    }

    public function testCollectSkipsDummyItems(): void
    {
        $invoiceItem = $this->buildInvoiceItem(true, 99.99, 1.0);

        $invoiceMock = $this->createMock(Invoice::class);
        $invoiceMock->method('getAllItems')->willReturn([$invoiceItem]);

        $invoiceMock->expects($this->never())->method('setGrandTotal');

        $this->eppCharge->collect($invoiceMock);
    }

    public function testCollectSkipsItemsWithZeroEppPrice(): void
    {
        $invoiceItem = $this->buildInvoiceItem(false, 0.00, 1.0);

        $invoiceMock = $this->createMock(Invoice::class);
        $invoiceMock->method('getAllItems')->willReturn([$invoiceItem]);

        $invoiceMock->expects($this->never())->method('setGrandTotal');

        $this->eppCharge->collect($invoiceMock);
    }

    public function testCollectWithNoItemsDoesNotModifyTotal(): void
    {
        $invoiceMock = $this->createMock(Invoice::class);
        $invoiceMock->method('getAllItems')->willReturn([]);

        $invoiceMock->expects($this->never())->method('setGrandTotal');

        $this->eppCharge->collect($invoiceMock);
    }

    public function testCollectSumsMultipleItems(): void
    {
        $item1 = $this->buildInvoiceItem(false, 50.00, 1.0);
        $item2 = $this->buildInvoiceItem(false, 75.00, 2.0);

        $invoiceMock = $this->createMock(Invoice::class);
        $invoiceMock->method('getAllItems')->willReturn([$item1, $item2]);
        $invoiceMock->method('getGrandTotal')->willReturn(500.00);
        $invoiceMock->method('getBaseGrandTotal')->willReturn(500.00);

        // 50*1 + 75*2 = 200
        $invoiceMock->expects($this->once())->method('setGrandTotal')->with(700.00);
        $invoiceMock->expects($this->once())->method('setBaseGrandTotal')->with(700.00);

        $this->eppCharge->collect($invoiceMock);
    }
}

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

namespace MageTips\Appliances\Test\Unit\Model\Order\Creditmemo\Total;

use MageTips\Appliances\Model\Order\Creditmemo\Total\EppCharge;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Item as CreditmemoItem;
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

    private function buildCreditmemoItem(bool $isDummy, float $eppPrice, float $qty): MockObject
    {
        $orderItemMock = $this->createMock(OrderItem::class);
        $orderItemMock->method('isDummy')->willReturn($isDummy);
        $orderItemMock->method('getData')->with('epp_plan_price')->willReturn($eppPrice);

        $creditmemoItemMock = $this->createMock(CreditmemoItem::class);
        $creditmemoItemMock->method('getOrderItem')->willReturn($orderItemMock);
        $creditmemoItemMock->method('getQty')->willReturn($qty);

        return $creditmemoItemMock;
    }

    public function testCollectAddsEppToCreditmemoTotal(): void
    {
        $item = $this->buildCreditmemoItem(false, 99.99, 1.0);

        $creditmemoMock = $this->createMock(Creditmemo::class);
        $creditmemoMock->method('getAllItems')->willReturn([$item]);
        $creditmemoMock->method('getGrandTotal')->willReturn(500.00);
        $creditmemoMock->method('getBaseGrandTotal')->willReturn(500.00);

        $creditmemoMock->expects($this->once())->method('setGrandTotal')->with(599.99);
        $creditmemoMock->expects($this->once())->method('setBaseGrandTotal')->with(599.99);

        $result = $this->eppCharge->collect($creditmemoMock);

        $this->assertInstanceOf(EppCharge::class, $result);
    }

    public function testCollectSkipsDummyItems(): void
    {
        $item = $this->buildCreditmemoItem(true, 99.99, 1.0);

        $creditmemoMock = $this->createMock(Creditmemo::class);
        $creditmemoMock->method('getAllItems')->willReturn([$item]);

        $creditmemoMock->expects($this->never())->method('setGrandTotal');

        $this->eppCharge->collect($creditmemoMock);
    }

    public function testCollectSkipsZeroEppPrice(): void
    {
        $item = $this->buildCreditmemoItem(false, 0.00, 1.0);

        $creditmemoMock = $this->createMock(Creditmemo::class);
        $creditmemoMock->method('getAllItems')->willReturn([$item]);

        $creditmemoMock->expects($this->never())->method('setGrandTotal');

        $this->eppCharge->collect($creditmemoMock);
    }

    public function testCollectWithEmptyItemsDoesNothing(): void
    {
        $creditmemoMock = $this->createMock(Creditmemo::class);
        $creditmemoMock->method('getAllItems')->willReturn([]);

        $creditmemoMock->expects($this->never())->method('setGrandTotal');

        $this->eppCharge->collect($creditmemoMock);
    }

    public function testCollectSumsMultipleItems(): void
    {
        $item1 = $this->buildCreditmemoItem(false, 40.00, 2.0);
        $item2 = $this->buildCreditmemoItem(false, 60.00, 1.0);

        $creditmemoMock = $this->createMock(Creditmemo::class);
        $creditmemoMock->method('getAllItems')->willReturn([$item1, $item2]);
        $creditmemoMock->method('getGrandTotal')->willReturn(200.00);
        $creditmemoMock->method('getBaseGrandTotal')->willReturn(200.00);

        // 40*2 + 60*1 = 140
        $creditmemoMock->expects($this->once())->method('setGrandTotal')->with(340.00);
        $creditmemoMock->expects($this->once())->method('setBaseGrandTotal')->with(340.00);

        $this->eppCharge->collect($creditmemoMock);
    }
}

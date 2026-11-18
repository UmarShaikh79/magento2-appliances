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

namespace MageTips\Appliances\Test\Unit\Model\Quote\Total;

use MageTips\Appliances\Api\ApplianceDetectorInterface;
use MageTips\Appliances\Api\EppRepositoryInterface;
use MageTips\Appliances\Model\Epp;
use MageTips\Appliances\Model\Quote\Total\EppCharge;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Api\Data\ShippingInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\Option;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class EppChargeTest extends TestCase
{
    private EppCharge $eppCharge;

    /** @var ApplianceDetectorInterface&MockObject */
    private MockObject $detectorMock;

    /** @var EppRepositoryInterface&MockObject */
    private MockObject $eppRepositoryMock;

    /** @var LoggerInterface&MockObject */
    private MockObject $loggerMock;

    /** @var Quote&MockObject */
    private MockObject $quoteMock;

    /** @var ShippingAssignmentInterface&MockObject */
    private MockObject $shippingAssignmentMock;

    /** @var Total&MockObject */
    private MockObject $totalMock;

    protected function setUp(): void
    {
        $this->detectorMock           = $this->createMock(ApplianceDetectorInterface::class);
        $this->eppRepositoryMock      = $this->createMock(EppRepositoryInterface::class);
        $this->loggerMock             = $this->createMock(LoggerInterface::class);
        $this->quoteMock              = $this->createMock(Quote::class);
        $this->shippingAssignmentMock = $this->createMock(ShippingAssignmentInterface::class);
        $this->totalMock              = $this->createMock(Total::class);

        // AbstractTotal::collect() calls $shippingAssignment->getShipping()->getAddress()
        $shippingMock = $this->createMock(ShippingInterface::class);
        $addressMock  = $this->createMock(Address::class);
        $shippingMock->method('getAddress')->willReturn($addressMock);
        $this->shippingAssignmentMock->method('getShipping')->willReturn($shippingMock);

        $this->eppCharge = new EppCharge(
            $this->detectorMock,
            $this->eppRepositoryMock,
            $this->loggerMock
        );
    }

    public function testCodeIsSet(): void
    {
        $this->assertSame(EppCharge::CODE, $this->eppCharge->getCode());
    }

    public function testCollectReturnsEarlyWhenNoItems(): void
    {
        $this->shippingAssignmentMock->method('getItems')->willReturn([]);

        $result = $this->eppCharge->collect(
            $this->quoteMock,
            $this->shippingAssignmentMock,
            $this->totalMock
        );

        $this->assertInstanceOf(EppCharge::class, $result);
        $this->totalMock->expects($this->never())->method('addTotalAmount');
    }

    public function testCollectSkipsChildItems(): void
    {
        $parentMock = $this->createMock(Item::class);

        $itemMock = $this->createMock(Item::class);
        $itemMock->method('getParentItem')->willReturn($parentMock); // non-null → child item

        $this->shippingAssignmentMock->method('getItems')->willReturn([$itemMock]);
        $this->detectorMock->expects($this->never())->method('isApplianceItem');
        $this->totalMock->expects($this->never())->method('addTotalAmount');

        $this->eppCharge->collect($this->quoteMock, $this->shippingAssignmentMock, $this->totalMock);
    }

    public function testCollectSkipsNonApplianceItems(): void
    {
        $itemMock = $this->createMock(Item::class);
        $itemMock->method('getParentItem')->willReturn(null);

        $this->shippingAssignmentMock->method('getItems')->willReturn([$itemMock]);
        $this->detectorMock->method('isApplianceItem')->with($itemMock)->willReturn(false);
        $this->totalMock->expects($this->never())->method('addTotalAmount');

        $this->eppCharge->collect($this->quoteMock, $this->shippingAssignmentMock, $this->totalMock);
    }

    public function testCollectSkipsItemsWithNoEppOption(): void
    {
        $itemMock = $this->createMock(Item::class);
        $itemMock->method('getParentItem')->willReturn(null);
        $itemMock->method('getOptionByCode')->with(EppCharge::EPP_ITEM_OPTION_CODE)->willReturn(null);

        $this->shippingAssignmentMock->method('getItems')->willReturn([$itemMock]);
        $this->detectorMock->method('isApplianceItem')->willReturn(true);
        $this->totalMock->expects($this->never())->method('addTotalAmount');

        $this->eppCharge->collect($this->quoteMock, $this->shippingAssignmentMock, $this->totalMock);
    }

    public function testCollectSkipsDisabledPlan(): void
    {
        $optionMock = $this->createMock(Option::class);
        $optionMock->method('getValue')->willReturn('EPP_3YR');

        $itemMock = $this->createMock(Item::class);
        $itemMock->method('getParentItem')->willReturn(null);
        $itemMock->method('getOptionByCode')->willReturn($optionMock);

        $this->shippingAssignmentMock->method('getItems')->willReturn([$itemMock]);
        $this->detectorMock->method('isApplianceItem')->willReturn(true);

        $eppMock = $this->createMock(Epp::class);
        $eppMock->method('isEnabled')->willReturn(false);
        $this->eppRepositoryMock->method('getByCode')->willReturn($eppMock);

        $this->totalMock->expects($this->never())->method('addTotalAmount');

        $this->eppCharge->collect($this->quoteMock, $this->shippingAssignmentMock, $this->totalMock);
    }

    public function testCollectSkipsItemOutsidePriceRange(): void
    {
        $optionMock = $this->createMock(Option::class);
        $optionMock->method('getValue')->willReturn('EPP_3YR');

        $itemMock = $this->createMock(Item::class);
        $itemMock->method('getParentItem')->willReturn(null);
        $itemMock->method('getOptionByCode')->willReturn($optionMock);
        $itemMock->method('getPrice')->willReturn(50.00);

        $this->shippingAssignmentMock->method('getItems')->willReturn([$itemMock]);
        $this->detectorMock->method('isApplianceItem')->willReturn(true);

        $eppMock = $this->createMock(Epp::class);
        $eppMock->method('isEnabled')->willReturn(true);
        $eppMock->method('getMinPrice')->willReturn(200.00);
        $eppMock->method('getMaxPrice')->willReturn(999.00);
        $this->eppRepositoryMock->method('getByCode')->willReturn($eppMock);

        $this->totalMock->expects($this->never())->method('addTotalAmount');

        $this->eppCharge->collect($this->quoteMock, $this->shippingAssignmentMock, $this->totalMock);
    }

    public function testCollectAddsEppChargeForValidItem(): void
    {
        $optionMock = $this->createMock(Option::class);
        $optionMock->method('getValue')->willReturn('EPP_3YR');

        $itemMock = $this->createMock(Item::class);
        $itemMock->method('getParentItem')->willReturn(null);
        $itemMock->method('getOptionByCode')->willReturn($optionMock);
        $itemMock->method('getPrice')->willReturn(500.00);
        $itemMock->method('getQty')->willReturn(2.0);

        $this->shippingAssignmentMock->method('getItems')->willReturn([$itemMock]);
        $this->detectorMock->method('isApplianceItem')->willReturn(true);

        $eppMock = $this->createMock(Epp::class);
        $eppMock->method('isEnabled')->willReturn(true);
        $eppMock->method('getMinPrice')->willReturn(200.00);
        $eppMock->method('getMaxPrice')->willReturn(999.00);
        $eppMock->method('getRetailPrice')->willReturn(99.99);
        $this->eppRepositoryMock->method('getByCode')->willReturn($eppMock);

        $this->totalMock->expects($this->once())->method('addTotalAmount')->with(EppCharge::CODE, 199.98);
        $this->totalMock->expects($this->once())->method('addBaseTotalAmount')->with(EppCharge::CODE, 199.98);

        $this->eppCharge->collect($this->quoteMock, $this->shippingAssignmentMock, $this->totalMock);
    }

    public function testCollectOnlyCountsAssignmentItemsNotFullQuote(): void
    {
        // Address 1 has only item A → should only charge for item A, not item B
        $optionMock = $this->createMock(Option::class);
        $optionMock->method('getValue')->willReturn('EPP_2YR');

        $itemA = $this->createMock(Item::class);
        $itemA->method('getParentItem')->willReturn(null);
        $itemA->method('getOptionByCode')->willReturn($optionMock);
        $itemA->method('getPrice')->willReturn(300.00);
        $itemA->method('getQty')->willReturn(1.0);

        // Only item A is in this shipping assignment
        $this->shippingAssignmentMock->method('getItems')->willReturn([$itemA]);
        $this->detectorMock->method('isApplianceItem')->willReturn(true);

        $eppMock = $this->createMock(Epp::class);
        $eppMock->method('isEnabled')->willReturn(true);
        $eppMock->method('getMinPrice')->willReturn(100.00);
        $eppMock->method('getMaxPrice')->willReturn(999.00);
        $eppMock->method('getRetailPrice')->willReturn(49.99);
        $this->eppRepositoryMock->method('getByCode')->willReturn($eppMock);

        $this->totalMock->expects($this->once())->method('addTotalAmount')->with(EppCharge::CODE, 49.99);

        $this->eppCharge->collect($this->quoteMock, $this->shippingAssignmentMock, $this->totalMock);
    }

    public function testCollectLogsWarningWhenEppCodeNotFound(): void
    {
        $optionMock = $this->createMock(Option::class);
        $optionMock->method('getValue')->willReturn('MISSING_CODE');

        $itemMock = $this->createMock(Item::class);
        $itemMock->method('getParentItem')->willReturn(null);
        $itemMock->method('getOptionByCode')->willReturn($optionMock);
        $itemMock->method('getId')->willReturn(7);

        $this->shippingAssignmentMock->method('getItems')->willReturn([$itemMock]);
        $this->detectorMock->method('isApplianceItem')->willReturn(true);

        $this->eppRepositoryMock->method('getByCode')
            ->willThrowException(new LocalizedException(__('Not found')));

        $this->loggerMock->expects($this->once())->method('warning')
            ->with($this->stringContains('MISSING_CODE'));

        $this->eppCharge->collect($this->quoteMock, $this->shippingAssignmentMock, $this->totalMock);
    }

    public function testFetchReturnsEmptyWhenNoEppItems(): void
    {
        $this->detectorMock->method('getApplianceItems')->willReturn([]);

        $result = $this->eppCharge->fetch($this->quoteMock, $this->totalMock);

        $this->assertSame([], $result);
    }

    public function testFetchReturnsArrayWithTotalForValidItems(): void
    {
        $optionMock = $this->createMock(Option::class);
        $optionMock->method('getValue')->willReturn('EPP_3YR');

        $itemMock = $this->createMock(Item::class);
        $itemMock->method('getOptionByCode')->willReturn($optionMock);
        $itemMock->method('getPrice')->willReturn(600.00);
        $itemMock->method('getQty')->willReturn(1.0);

        $this->detectorMock->method('getApplianceItems')->willReturn([$itemMock]);

        $eppMock = $this->createMock(Epp::class);
        $eppMock->method('isEnabled')->willReturn(true);
        $eppMock->method('getMinPrice')->willReturn(500.00);
        $eppMock->method('getMaxPrice')->willReturn(999.00);
        $eppMock->method('getRetailPrice')->willReturn(79.99);
        $this->eppRepositoryMock->method('getByCode')->willReturn($eppMock);

        $result = $this->eppCharge->fetch($this->quoteMock, $this->totalMock);

        $this->assertSame(EppCharge::CODE, $result['code']);
        $this->assertSame(79.99, $result['value']);
    }

    public function testFetchSkipsDisabledPlanAndReturnsEmpty(): void
    {
        $optionMock = $this->createMock(Option::class);
        $optionMock->method('getValue')->willReturn('EPP_3YR');

        $itemMock = $this->createMock(Item::class);
        $itemMock->method('getOptionByCode')->willReturn($optionMock);

        $this->detectorMock->method('getApplianceItems')->willReturn([$itemMock]);

        $eppMock = $this->createMock(Epp::class);
        $eppMock->method('isEnabled')->willReturn(false);
        $this->eppRepositoryMock->method('getByCode')->willReturn($eppMock);

        $result = $this->eppCharge->fetch($this->quoteMock, $this->totalMock);

        $this->assertSame([], $result);
    }

    public function testFetchLogsWarningWhenEppCodeNotFound(): void
    {
        $optionMock = $this->createMock(Option::class);
        $optionMock->method('getValue')->willReturn('BAD_CODE');

        $itemMock = $this->createMock(Item::class);
        $itemMock->method('getOptionByCode')->willReturn($optionMock);
        $itemMock->method('getId')->willReturn(3);

        $this->detectorMock->method('getApplianceItems')->willReturn([$itemMock]);

        $this->eppRepositoryMock
            ->method('getByCode')
            ->willThrowException(new LocalizedException(__('Not found')));

        $this->loggerMock
            ->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('BAD_CODE'));

        $result = $this->eppCharge->fetch($this->quoteMock, $this->totalMock);

        $this->assertSame([], $result);
    }

    public function testFetchSkipsPriceOutsideRange(): void
    {
        $optionMock = $this->createMock(Option::class);
        $optionMock->method('getValue')->willReturn('EPP_3YR');

        $itemMock = $this->createMock(Item::class);
        $itemMock->method('getOptionByCode')->willReturn($optionMock);
        $itemMock->method('getPrice')->willReturn(100.00); // below min

        $this->detectorMock->method('getApplianceItems')->willReturn([$itemMock]);

        $eppMock = $this->createMock(Epp::class);
        $eppMock->method('isEnabled')->willReturn(true);
        $eppMock->method('getMinPrice')->willReturn(200.00);
        $eppMock->method('getMaxPrice')->willReturn(999.00);
        $this->eppRepositoryMock->method('getByCode')->willReturn($eppMock);

        $result = $this->eppCharge->fetch($this->quoteMock, $this->totalMock);

        $this->assertSame([], $result);
    }
}

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

namespace MageTips\Appliances\Test\Unit\Model\Appliance;

use MageTips\Appliances\Model\Appliance\Detector;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DetectorTest extends TestCase
{
    private Detector $detector;

    /** @var ProductRepositoryInterface&MockObject */
    private MockObject $productRepositoryMock;

    /** @var LoggerInterface&MockObject */
    private MockObject $loggerMock;

    protected function setUp(): void
    {
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->loggerMock            = $this->createMock(LoggerInterface::class);

        $this->detector = new Detector($this->productRepositoryMock, $this->loggerMock);
    }

    /** Build an Item mock with a magic getProductId() via __call stub. */
    private function buildItemMock(int $productId): MockObject
    {
        $item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();
        $item->method('__call')
            ->willReturnCallback(fn(string $m, array $a) => $m === 'getProductId' ? $productId : null);
        return $item;
    }

    public function testIsApplianceProductReturnsTrueForAppliance(): void
    {
        $productMock = $this->createMock(Product::class);
        $productMock->method('getData')->with('is_appliance')->willReturn(1);

        $this->productRepositoryMock
            ->method('getById')
            ->with(10)
            ->willReturn($productMock);

        $this->assertTrue($this->detector->isApplianceProduct(10));
    }

    public function testIsApplianceProductReturnsFalseForNonAppliance(): void
    {
        $productMock = $this->createMock(Product::class);
        $productMock->method('getData')->with('is_appliance')->willReturn(0);

        $this->productRepositoryMock
            ->method('getById')
            ->with(20)
            ->willReturn($productMock);

        $this->assertFalse($this->detector->isApplianceProduct(20));
    }

    public function testIsApplianceProductReturnsFalseAndLogsWarningWhenProductNotFound(): void
    {
        $this->productRepositoryMock
            ->method('getById')
            ->willThrowException(new NoSuchEntityException(__('Product not found')));

        $this->loggerMock
            ->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('ApplianceDetector: product 99 not found'));

        $this->assertFalse($this->detector->isApplianceProduct(99));
    }

    public function testIsApplianceProductCachesResult(): void
    {
        $productMock = $this->createMock(Product::class);
        $productMock->method('getData')->willReturn(1);

        $this->productRepositoryMock
            ->expects($this->once())
            ->method('getById')
            ->willReturn($productMock);

        $this->detector->isApplianceProduct(5);
        $this->detector->isApplianceProduct(5);
    }

    public function testIsApplianceItemReturnsTrueForApplianceItem(): void
    {
        $productMock = $this->createMock(Product::class);
        $productMock->method('getData')->with('is_appliance')->willReturn(1);
        $this->productRepositoryMock->method('getById')->willReturn($productMock);

        $this->assertTrue($this->detector->isApplianceItem($this->buildItemMock(15)));
    }

    public function testIsApplianceItemReturnsFalseForNonApplianceItem(): void
    {
        $productMock = $this->createMock(Product::class);
        $productMock->method('getData')->willReturn(0);
        $this->productRepositoryMock->method('getById')->willReturn($productMock);

        $this->assertFalse($this->detector->isApplianceItem($this->buildItemMock(16)));
    }

    public function testHasAppliancesReturnsTrueWhenOneApplianceInQuote(): void
    {
        $productMock = $this->createMock(Product::class);
        $productMock->method('getData')->willReturn(1);
        $this->productRepositoryMock->method('getById')->willReturn($productMock);

        $quoteMock = $this->createMock(Quote::class);
        $quoteMock->method('getAllVisibleItems')->willReturn([$this->buildItemMock(10)]);

        $this->assertTrue($this->detector->hasAppliances($quoteMock));
    }

    public function testHasAppliancesReturnsFalseWhenNoAppliancesInQuote(): void
    {
        $productMock = $this->createMock(Product::class);
        $productMock->method('getData')->willReturn(0);
        $this->productRepositoryMock->method('getById')->willReturn($productMock);

        $quoteMock = $this->createMock(Quote::class);
        $quoteMock->method('getAllVisibleItems')->willReturn([$this->buildItemMock(20)]);

        $this->assertFalse($this->detector->hasAppliances($quoteMock));
    }

    public function testHasAppliancesReturnsFalseWhenQuoteIsEmpty(): void
    {
        $quoteMock = $this->createMock(Quote::class);
        $quoteMock->method('getAllVisibleItems')->willReturn([]);

        $this->assertFalse($this->detector->hasAppliances($quoteMock));
    }

    public function testGetApplianceItemsReturnsOnlyApplianceItems(): void
    {
        $applianceProductMock = $this->createMock(Product::class);
        $applianceProductMock->method('getData')->with('is_appliance')->willReturn(1);

        $nonApplianceProductMock = $this->createMock(Product::class);
        $nonApplianceProductMock->method('getData')->with('is_appliance')->willReturn(0);

        $this->productRepositoryMock
            ->method('getById')
            ->willReturnCallback(function (int $id) use ($applianceProductMock, $nonApplianceProductMock) {
                return $id === 10 ? $applianceProductMock : $nonApplianceProductMock;
            });

        $applianceItemMock    = $this->buildItemMock(10);
        $nonApplianceItemMock = $this->buildItemMock(20);

        $quoteMock = $this->createMock(Quote::class);
        $quoteMock->method('getAllVisibleItems')->willReturn([$applianceItemMock, $nonApplianceItemMock]);

        $result = $this->detector->getApplianceItems($quoteMock);

        $this->assertCount(1, $result);
        $this->assertSame($applianceItemMock, $result[0]);
    }

    public function testGetApplianceItemsReturnsEmptyWhenNoAppliances(): void
    {
        $productMock = $this->createMock(Product::class);
        $productMock->method('getData')->willReturn(0);
        $this->productRepositoryMock->method('getById')->willReturn($productMock);

        $quoteMock = $this->createMock(Quote::class);
        $quoteMock->method('getAllVisibleItems')->willReturn([$this->buildItemMock(30)]);

        $this->assertSame([], $this->detector->getApplianceItems($quoteMock));
    }
}

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

namespace MageTips\Appliances\Test\Unit\Observer\Cart;

use MageTips\Appliances\Observer\Cart\AddDeliveryDateToItem;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddDeliveryDateToItemTest extends TestCase
{
    private AddDeliveryDateToItem $observer;

    /** @var RequestInterface&MockObject */
    private MockObject $requestMock;

    protected function setUp(): void
    {
        $this->requestMock = $this->createMock(RequestInterface::class);
        $this->observer    = new AddDeliveryDateToItem($this->requestMock);
    }

    private function buildObserver(MockObject $quoteItemMock): Observer
    {
        // getQuoteItem() is magic (__call on DataObject); stub __call to intercept it.
        $eventMock = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();
        $eventMock->method('__call')
            ->willReturnCallback(fn(string $m, array $a) => $m === 'getQuoteItem' ? $quoteItemMock : null);

        $observerMock = $this->createMock(Observer::class);
        $observerMock->method('getEvent')->willReturn($eventMock);

        return $observerMock;
    }

    public function testExecuteSetsDeliveryDateOnQuoteItem(): void
    {
        $this->requestMock->method('getParam')
            ->with('delivery_date', '')
            ->willReturn('2026-09-01');

        $itemMock = $this->createMock(Item::class);
        $itemMock->method('getParentItem')->willReturn(null);
        $itemMock->expects($this->once())->method('setData')->with('delivery_date', '2026-09-01');

        $this->observer->execute($this->buildObserver($itemMock));
    }

    public function testExecuteSetsDeliveryDateOnParentItemWhenChildItem(): void
    {
        $this->requestMock->method('getParam')
            ->with('delivery_date', '')
            ->willReturn('2026-10-15');

        $parentItemMock = $this->createMock(Item::class);
        $parentItemMock->expects($this->once())->method('setData')->with('delivery_date', '2026-10-15');

        $itemMock = $this->createMock(Item::class);
        $itemMock->method('getParentItem')->willReturn($parentItemMock);
        $itemMock->expects($this->never())->method('setData');

        $this->observer->execute($this->buildObserver($itemMock));
    }

    public function testExecuteDoesNothingWhenDeliveryDateIsEmpty(): void
    {
        $this->requestMock->method('getParam')
            ->with('delivery_date', '')
            ->willReturn('');

        $itemMock = $this->createMock(Item::class);
        $itemMock->expects($this->never())->method('setData');

        $this->observer->execute($this->buildObserver($itemMock));
    }

    public function testExecuteDoesNothingWhenDeliveryDateInvalidFormat(): void
    {
        $this->requestMock->method('getParam')
            ->with('delivery_date', '')
            ->willReturn('not-a-date');

        $itemMock = $this->createMock(Item::class);
        $itemMock->expects($this->never())->method('setData');

        $this->observer->execute($this->buildObserver($itemMock));
    }

    public function testExecuteDoesNothingWhenDeliveryDateWrongPattern(): void
    {
        $this->requestMock->method('getParam')
            ->with('delivery_date', '')
            ->willReturn('2026/09/01'); // slashes instead of dashes

        $itemMock = $this->createMock(Item::class);
        $itemMock->expects($this->never())->method('setData');

        $this->observer->execute($this->buildObserver($itemMock));
    }
}

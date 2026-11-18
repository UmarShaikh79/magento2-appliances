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

namespace MageTips\Appliances\Test\Unit\Plugin\Checkout\Model;

use MageTips\Appliances\Api\EppRepositoryInterface;
use MageTips\Appliances\Model\Delivery\Calculator;
use MageTips\Appliances\Model\Epp;
use MageTips\Appliances\Plugin\Checkout\Model\AddEppToCheckoutConfigPlugin;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\DefaultConfigProvider;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Item\Option;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AddEppToCheckoutConfigPluginTest extends TestCase
{
    private AddEppToCheckoutConfigPlugin $plugin;

    /** @var CheckoutSession&MockObject */
    private MockObject $sessionMock;

    /** @var EppRepositoryInterface&MockObject */
    private MockObject $eppRepositoryMock;

    /** @var Calculator&MockObject */
    private MockObject $calculatorMock;

    /** @var DefaultConfigProvider&MockObject */
    private MockObject $subjectMock;

    /** @var Quote&MockObject */
    private MockObject $quoteMock;

    protected function setUp(): void
    {
        $this->sessionMock       = $this->createMock(CheckoutSession::class);
        $this->eppRepositoryMock = $this->createMock(EppRepositoryInterface::class);
        $this->calculatorMock    = $this->createMock(Calculator::class);
        $this->subjectMock       = $this->createMock(DefaultConfigProvider::class);
        $this->quoteMock         = $this->createMock(Quote::class);

        $this->plugin = new AddEppToCheckoutConfigPlugin(
            $this->sessionMock,
            $this->eppRepositoryMock,
            $this->calculatorMock,
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testAfterGetConfigReturnsUnchangedWhenSessionThrows(): void
    {
        $this->sessionMock
            ->method('getQuote')
            ->willThrowException(new \Exception('Session error'));

        $result = $this->plugin->afterGetConfig($this->subjectMock, ['key' => 'value']);

        $this->assertSame(['key' => 'value'], $result);
    }

    public function testAfterGetConfigReturnsUnchangedWhenNoItems(): void
    {
        $this->sessionMock->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->method('getAllVisibleItems')->willReturn([]);

        $result = $this->plugin->afterGetConfig($this->subjectMock, ['key' => 'value']);

        $this->assertSame(['key' => 'value'], $result);
    }

    public function testAfterGetConfigInjectsEppPlanNameIntoQuoteItemData(): void
    {
        $optionMock = $this->createMock(Option::class);
        $optionMock->method('getValue')->willReturn('EPP_3YR');

        $itemMock = $this->createMock(QuoteItem::class);
        $itemMock->method('getId')->willReturn(5);
        $itemMock->method('getOptionByCode')->willReturn($optionMock);
        $itemMock->method('getData')->willReturn(null); // no delivery_date

        $eppMock = $this->createMock(Epp::class);
        $eppMock->method('getName')->willReturn('3-Year Plan');
        $eppMock->method('getYears')->willReturn(3);
        $this->eppRepositoryMock->method('getByCode')->willReturn($eppMock);

        $this->sessionMock->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->method('getAllVisibleItems')->willReturn([$itemMock]);

        $result = $this->plugin->afterGetConfig($this->subjectMock, [
            'quoteItemData' => [['item_id' => 5]],
            'totalsData'    => ['items' => [['item_id' => 5]]],
        ]);

        $this->assertArrayHasKey('epp_plan_name', $result['quoteItemData'][0]);
        $this->assertStringContainsString('3-Year Plan', $result['quoteItemData'][0]['epp_plan_name']);
        $this->assertArrayHasKey('epp_plan_name', $result['totalsData']['items'][0]);
    }

    public function testAfterGetConfigHandlesRepositoryException(): void
    {
        $optionMock = $this->createMock(Option::class);
        $optionMock->method('getValue')->willReturn('BAD_CODE');

        $itemMock = $this->createMock(QuoteItem::class);
        $itemMock->method('getId')->willReturn(3);
        $itemMock->method('getOptionByCode')->willReturn($optionMock);
        $itemMock->method('getData')->willReturn(null);

        $this->eppRepositoryMock->method('getByCode')
            ->willThrowException(new \Exception('Not found'));

        $this->sessionMock->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->method('getAllVisibleItems')->willReturn([$itemMock]);

        $result = $this->plugin->afterGetConfig($this->subjectMock, ['x' => 1]);

        $this->assertSame(['x' => 1], $result);
    }

    public function testAfterGetConfigUsesCalculatorWhenProductIsApplianceAndNoSavedDate(): void
    {
        // Item has no EPP option and no saved delivery_date, but product is_appliance=true
        // → plugin uses Calculator::getEarliestDate() to build the delivery label
        $productMock = $this->createMock(Product::class);
        $productMock->method('getData')
            ->willReturnCallback(fn($k) => $k === 'is_appliance' ? 1 : null);

        $itemMock = $this->createMock(QuoteItem::class);
        $itemMock->method('getId')->willReturn(11);
        $itemMock->method('getOptionByCode')->willReturn(null);
        $itemMock->method('getData')->willReturn(null); // no delivery_date
        $itemMock->method('getProduct')->willReturn($productMock);

        $this->calculatorMock
            ->method('getEarliestDate')
            ->willReturn(new \DateTime('2026-10-01'));

        $this->sessionMock->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->method('getAllVisibleItems')->willReturn([$itemMock]);

        $result = $this->plugin->afterGetConfig($this->subjectMock, [
            'quoteItemData' => [['item_id' => 11]],
        ]);

        $this->assertArrayHasKey('delivery_date_label', $result['quoteItemData'][0]);
        $this->assertStringContainsString('October 1', $result['quoteItemData'][0]['delivery_date_label']);
    }

    public function testAfterGetConfigInjectsDeliveryDateForSavedDate(): void
    {
        $itemMock = $this->createMock(QuoteItem::class);
        $itemMock->method('getId')->willReturn(7);
        $itemMock->method('getOptionByCode')->willReturn(null);
        $itemMock->method('getData')
            ->willReturnCallback(fn($k) => $k === 'delivery_date' ? '2026-10-01' : null);

        $this->sessionMock->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->method('getAllVisibleItems')->willReturn([$itemMock]);

        $result = $this->plugin->afterGetConfig($this->subjectMock, [
            'quoteItemData' => [['item_id' => 7]],
        ]);

        $this->assertArrayHasKey('delivery_date_label', $result['quoteItemData'][0]);
        $this->assertStringContainsString('October 1', $result['quoteItemData'][0]['delivery_date_label']);
    }
}

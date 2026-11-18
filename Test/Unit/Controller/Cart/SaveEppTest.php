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

namespace MageTips\Appliances\Test\Unit\Controller\Cart;

use MageTips\Appliances\Api\EppRepositoryInterface;
use MageTips\Appliances\Controller\Cart\SaveEpp;
use MageTips\Appliances\Model\Epp;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\Option;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SaveEppTest extends TestCase
{
    private SaveEpp $controller;

    /** @var RequestInterface&MockObject */
    private MockObject $requestMock;

    /** @var JsonFactory&MockObject */
    private MockObject $jsonFactoryMock;

    /** @var CheckoutSession&MockObject */
    private MockObject $sessionMock;

    /** @var CartRepositoryInterface&MockObject */
    private MockObject $cartRepositoryMock;

    /** @var FormKeyValidator&MockObject */
    private MockObject $formKeyValidatorMock;

    /** @var EppRepositoryInterface&MockObject */
    private MockObject $eppRepositoryMock;

    /** @var LoggerInterface&MockObject */
    private MockObject $loggerMock;

    /** @var Json&MockObject */
    private MockObject $resultMock;

    /** @var Quote&MockObject */
    private MockObject $quoteMock;

    protected function setUp(): void
    {
        $this->requestMock          = $this->createMock(RequestInterface::class);
        $this->jsonFactoryMock      = $this->createMock(JsonFactory::class);
        $this->sessionMock          = $this->createMock(CheckoutSession::class);
        $this->cartRepositoryMock   = $this->createMock(CartRepositoryInterface::class);
        $this->formKeyValidatorMock = $this->createMock(FormKeyValidator::class);
        $this->eppRepositoryMock    = $this->createMock(EppRepositoryInterface::class);
        $this->loggerMock           = $this->createMock(LoggerInterface::class);
        $this->resultMock           = $this->createMock(Json::class);
        $this->quoteMock            = $this->createMock(Quote::class);

        $this->resultMock->method('setData')->willReturnSelf();
        $this->jsonFactoryMock->method('create')->willReturn($this->resultMock);
        $this->sessionMock->method('getQuote')->willReturn($this->quoteMock);

        $this->controller = new SaveEpp(
            $this->requestMock,
            $this->jsonFactoryMock,
            $this->sessionMock,
            $this->cartRepositoryMock,
            $this->formKeyValidatorMock,
            $this->eppRepositoryMock,
            $this->loggerMock
        );
    }

    /** Build an Item mock; magic getProductId() routed through __call stub. */
    private function buildItemMock(): MockObject
    {
        $item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call', 'getOptionByCode', 'getPrice', 'addOption', 'setData'])
            ->getMock();
        $item->method('__call')->willReturn(null);
        return $item;
    }

    /** Build an Option mock; magic getProductId()/setValue() routed through __call stub. */
    private function buildOptionMock(int $productId): MockObject
    {
        $option = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call', 'getValue'])
            ->getMock();
        $option->method('__call')
            ->willReturnCallback(fn(string $m, array $a) => $m === 'getProductId' ? $productId : null);
        return $option;
    }

    public function testExecuteReturnsErrorWhenFormKeyInvalid(): void
    {
        $this->formKeyValidatorMock->method('validate')->willReturn(false);

        $this->resultMock
            ->expects($this->once())
            ->method('setData')
            ->with($this->callback(fn($d) => isset($d['error']) && str_contains($d['error'], 'Invalid security token')))
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testExecuteReturnsErrorWhenItemIdIsZero(): void
    {
        $this->formKeyValidatorMock->method('validate')->willReturn(true);
        $this->requestMock->method('getParam')
            ->willReturnCallback(fn($key, $default = null) => match ($key) {
                'item_id'  => '0',
                'epp_code' => '',
                default    => $default,
            });

        $this->resultMock
            ->expects($this->once())
            ->method('setData')
            ->with($this->callback(fn($d) => isset($d['error']) && str_contains($d['error'], 'Invalid cart item')))
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testExecuteReturnsErrorWhenItemNotFound(): void
    {
        $this->formKeyValidatorMock->method('validate')->willReturn(true);
        $this->requestMock->method('getParam')
            ->willReturnCallback(fn($key, $default = null) => match ($key) {
                'item_id'  => '5',
                'epp_code' => 'EPP_3YR',
                default    => $default,
            });

        $this->quoteMock->method('getItemById')->with(5)->willReturn(null);

        $this->resultMock
            ->expects($this->once())
            ->method('setData')
            ->with($this->callback(fn($d) => isset($d['error']) && str_contains($d['error'], 'Cart item not found')))
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testExecuteRemovesPlanWhenEppCodeIsEmpty(): void
    {
        $this->formKeyValidatorMock->method('validate')->willReturn(true);
        $this->requestMock->method('getParam')
            ->willReturnCallback(fn($key, $default = null) => match ($key) {
                'item_id'  => '3',
                'epp_code' => '',
                default    => $default,
            });

        $itemMock = $this->buildItemMock();
        $itemMock->method('getOptionByCode')->willReturn(null);
        $itemMock->expects($this->atLeast(1))->method('setData');

        $this->quoteMock->method('getItemById')->with(3)->willReturn($itemMock);
        $this->quoteMock->method('collectTotals')->willReturnSelf();
        $this->cartRepositoryMock->expects($this->once())->method('save');

        $this->resultMock
            ->expects($this->once())
            ->method('setData')
            ->with(['success' => true])
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testExecuteReturnsErrorWhenPlanIsDisabled(): void
    {
        $this->formKeyValidatorMock->method('validate')->willReturn(true);
        $this->requestMock->method('getParam')
            ->willReturnCallback(fn($key, $default = null) => match ($key) {
                'item_id'  => '1',
                'epp_code' => 'EPP_3YR',
                default    => $default,
            });

        // Return non-zero productId so the inner if-branch is skipped
        $optionMock = $this->buildOptionMock(10);

        $itemMock = $this->buildItemMock();
        $itemMock->method('getOptionByCode')->willReturn($optionMock);
        $itemMock->method('getPrice')->willReturn(500.00);

        $this->quoteMock->method('getItemById')->with(1)->willReturn($itemMock);

        $eppMock = $this->createMock(Epp::class);
        $eppMock->method('isEnabled')->willReturn(false);
        $this->eppRepositoryMock->method('getByCode')->with('EPP_3YR')->willReturn($eppMock);

        $this->resultMock
            ->expects($this->once())
            ->method('setData')
            ->with($this->callback(fn($d) => isset($d['error']) && str_contains($d['error'], 'no longer available')))
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testExecuteReturnsErrorWhenPriceOutOfRange(): void
    {
        $this->formKeyValidatorMock->method('validate')->willReturn(true);
        $this->requestMock->method('getParam')
            ->willReturnCallback(fn($key, $default = null) => match ($key) {
                'item_id'  => '2',
                'epp_code' => 'EPP_3YR',
                default    => $default,
            });

        $optionMock = $this->buildOptionMock(10);

        $itemMock = $this->buildItemMock();
        $itemMock->method('getOptionByCode')->willReturn($optionMock);
        $itemMock->method('getPrice')->willReturn(50.00); // below min

        $this->quoteMock->method('getItemById')->with(2)->willReturn($itemMock);

        $eppMock = $this->createMock(Epp::class);
        $eppMock->method('isEnabled')->willReturn(true);
        $eppMock->method('getMinPrice')->willReturn(200.00);
        $eppMock->method('getMaxPrice')->willReturn(999.00);
        $this->eppRepositoryMock->method('getByCode')->willReturn($eppMock);

        $this->resultMock
            ->expects($this->once())
            ->method('setData')
            ->with($this->callback(fn($d) => isset($d['error']) && str_contains($d['error'], "not available for this product")))
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testExecuteSavesPlanSuccessfully(): void
    {
        $this->formKeyValidatorMock->method('validate')->willReturn(true);
        $this->requestMock->method('getParam')
            ->willReturnCallback(fn($key, $default = null) => match ($key) {
                'item_id'  => '4',
                'epp_code' => 'EPP_3YR',
                default    => $default,
            });

        $optionMock = $this->buildOptionMock(5);

        $itemMock = $this->buildItemMock();
        $itemMock->method('getOptionByCode')->willReturn($optionMock);
        $itemMock->method('getPrice')->willReturn(600.00);

        $this->quoteMock->method('getItemById')->with(4)->willReturn($itemMock);
        $this->quoteMock->method('collectTotals')->willReturnSelf();
        $this->cartRepositoryMock->expects($this->once())->method('save');

        $eppMock = $this->createMock(Epp::class);
        $eppMock->method('isEnabled')->willReturn(true);
        $eppMock->method('getMinPrice')->willReturn(500.00);
        $eppMock->method('getMaxPrice')->willReturn(999.00);
        $eppMock->method('getName')->willReturn('3-Year Plan');
        $eppMock->method('getRetailPrice')->willReturn(99.99);
        $eppMock->method('getYears')->willReturn(3);
        $this->eppRepositoryMock->method('getByCode')->willReturn($eppMock);

        $this->resultMock
            ->expects($this->once())
            ->method('setData')
            ->with(['success' => true])
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testExecuteExistingOptionWithZeroProductIdSetsProductId(): void
    {
        $this->formKeyValidatorMock->method('validate')->willReturn(true);
        $this->requestMock->method('getParam')
            ->willReturnCallback(fn($key, $default = null) => match ($key) {
                'item_id'  => '9',
                'epp_code' => 'EPP_3YR',
                default    => $default,
            });

        // getProductId() returns 0 → triggers the setData('product_id') branch (line 100)
        $optionMock = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call', 'getValue'])
            ->getMock();
        $optionMock->method('__call')
            ->willReturnCallback(fn(string $m, array $a) => $m === 'getProductId' ? 0 : null);
        $optionMock->method('getValue')->willReturn('OLD_CODE');

        $itemMock = $this->buildItemMock();
        $itemMock->method('getOptionByCode')->willReturn($optionMock);
        $itemMock->method('getPrice')->willReturn(600.00);

        $this->quoteMock->method('getItemById')->with(9)->willReturn($itemMock);
        $this->quoteMock->method('collectTotals')->willReturnSelf();
        $this->cartRepositoryMock->expects($this->once())->method('save');

        $eppMock = $this->createMock(Epp::class);
        $eppMock->method('isEnabled')->willReturn(true);
        $eppMock->method('getMinPrice')->willReturn(500.00);
        $eppMock->method('getMaxPrice')->willReturn(999.00);
        $eppMock->method('getName')->willReturn('3-Year Plan');
        $eppMock->method('getRetailPrice')->willReturn(99.99);
        $eppMock->method('getYears')->willReturn(3);
        $this->eppRepositoryMock->method('getByCode')->willReturn($eppMock);

        $this->resultMock
            ->expects($this->once())
            ->method('setData')
            ->with(['success' => true])
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testExecuteInnerGetByCodeExceptionLogsErrorAndSavesCart(): void
    {
        $this->formKeyValidatorMock->method('validate')->willReturn(true);
        $this->requestMock->method('getParam')
            ->willReturnCallback(fn($key, $default = null) => match ($key) {
                'item_id'  => '6',
                'epp_code' => 'EPP_BAD',
                default    => $default,
            });

        $optionMock = $this->buildOptionMock(5);

        $itemMock = $this->buildItemMock();
        $itemMock->method('getOptionByCode')->willReturn($optionMock);
        $itemMock->method('getPrice')->willReturn(600.00);

        $this->quoteMock->method('getItemById')->with(6)->willReturn($itemMock);
        $this->quoteMock->method('collectTotals')->willReturnSelf();
        $this->cartRepositoryMock->expects($this->once())->method('save');

        $this->eppRepositoryMock
            ->method('getByCode')
            ->willThrowException(new \Exception('EPP not found'));

        $this->loggerMock->expects($this->once())->method('error');

        $this->resultMock
            ->expects($this->once())
            ->method('setData')
            ->with(['success' => true])
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testExecuteOuterExceptionReturnsCannedError(): void
    {
        // Build a fresh controller whose session throws in the outer try
        $sessionMock = $this->createMock(\Magento\Checkout\Model\Session::class);
        $sessionMock->method('getQuote')->willThrowException(new \Exception('session fail'));

        $controller = new SaveEpp(
            $this->requestMock,
            $this->jsonFactoryMock,
            $sessionMock,
            $this->cartRepositoryMock,
            $this->formKeyValidatorMock,
            $this->eppRepositoryMock,
            $this->loggerMock
        );

        $this->formKeyValidatorMock->method('validate')->willReturn(true);
        $this->requestMock->method('getParam')
            ->willReturnCallback(fn($key, $default = null) => match ($key) {
                'item_id'  => '5',
                'epp_code' => 'EPP_3YR',
                default    => $default,
            });

        $this->resultMock
            ->expects($this->once())
            ->method('setData')
            ->with($this->callback(fn($d) => isset($d['error']) && str_contains($d['error'], 'Could not save')))
            ->willReturnSelf();

        $controller->execute();
    }

    public function testCreateCsrfValidationExceptionReturnsNull(): void
    {
        $this->assertNull($this->controller->createCsrfValidationException($this->requestMock));
    }

    public function testValidateForCsrfReturnsTrue(): void
    {
        $this->assertTrue($this->controller->validateForCsrf($this->requestMock));
    }
}

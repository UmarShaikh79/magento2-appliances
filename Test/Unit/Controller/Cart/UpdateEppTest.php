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

use MageTips\Appliances\Api\ApplianceDetectorInterface;
use MageTips\Appliances\Api\EppRepositoryInterface;
use MageTips\Appliances\Controller\Cart\UpdateEpp;
use MageTips\Appliances\Model\Epp;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UpdateEppTest extends TestCase
{
    private UpdateEpp $controller;

    /** @var RequestInterface&MockObject */
    private MockObject $requestMock;

    /** @var JsonFactory&MockObject */
    private MockObject $jsonFactoryMock;

    /** @var CheckoutSession&MockObject */
    private MockObject $sessionMock;

    /** @var CartRepositoryInterface&MockObject */
    private MockObject $cartRepositoryMock;

    /** @var ApplianceDetectorInterface&MockObject */
    private MockObject $detectorMock;

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
        $this->detectorMock         = $this->createMock(ApplianceDetectorInterface::class);
        $this->formKeyValidatorMock = $this->createMock(FormKeyValidator::class);
        $this->eppRepositoryMock    = $this->createMock(EppRepositoryInterface::class);
        $this->loggerMock           = $this->createMock(LoggerInterface::class);
        $this->resultMock           = $this->createMock(Json::class);
        $this->quoteMock            = $this->createMock(Quote::class);

        $this->resultMock->method('setData')->willReturnSelf();
        $this->jsonFactoryMock->method('create')->willReturn($this->resultMock);
        $this->sessionMock->method('getQuote')->willReturn($this->quoteMock);

        $this->controller = new UpdateEpp(
            $this->requestMock,
            $this->jsonFactoryMock,
            $this->sessionMock,
            $this->cartRepositoryMock,
            $this->detectorMock,
            $this->formKeyValidatorMock,
            $this->eppRepositoryMock,
            $this->loggerMock
        );
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

    public function testExecuteReturnsSuccessWhenEppCodeIsEmpty(): void
    {
        $this->formKeyValidatorMock->method('validate')->willReturn(true);
        $this->requestMock->method('getParam')
            ->willReturnCallback(fn($key, $default = null) => match ($key) {
                'item_ids' => [1, 2],
                'epp_code' => '',
                default    => $default,
            });

        $this->resultMock
            ->expects($this->once())
            ->method('setData')
            ->with(['success' => true])
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testExecuteReturnsSuccessWhenItemIdsIsEmpty(): void
    {
        $this->formKeyValidatorMock->method('validate')->willReturn(true);
        $this->requestMock->method('getParam')
            ->willReturnCallback(fn($key, $default = null) => match ($key) {
                'item_ids' => [],
                'epp_code' => 'EPP_3YR',
                default    => $default,
            });

        $this->resultMock
            ->expects($this->once())
            ->method('setData')
            ->with(['success' => true])
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testExecuteReturnsErrorWhenPlanNotFound(): void
    {
        $this->formKeyValidatorMock->method('validate')->willReturn(true);
        $this->requestMock->method('getParam')
            ->willReturnCallback(fn($key, $default = null) => match ($key) {
                'item_ids' => [1],
                'epp_code' => 'INVALID_PLAN',
                default    => $default,
            });

        $this->eppRepositoryMock
            ->method('getByCode')
            ->willThrowException(new NoSuchEntityException(__('Not found')));

        $this->resultMock
            ->expects($this->once())
            ->method('setData')
            ->with($this->callback(fn($d) => isset($d['error']) && str_contains($d['error'], 'Invalid protection plan')))
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testExecuteReturnsErrorWhenPlanDisabled(): void
    {
        $this->formKeyValidatorMock->method('validate')->willReturn(true);
        $this->requestMock->method('getParam')
            ->willReturnCallback(fn($key, $default = null) => match ($key) {
                'item_ids' => [1],
                'epp_code' => 'EPP_3YR',
                default    => $default,
            });

        $eppMock = $this->createMock(Epp::class);
        $eppMock->method('isEnabled')->willReturn(false);
        $this->eppRepositoryMock->method('getByCode')->willReturn($eppMock);

        $this->resultMock
            ->expects($this->once())
            ->method('setData')
            ->with($this->callback(fn($d) => isset($d['error']) && str_contains($d['error'], 'no longer available')))
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testExecuteSkipsItemsOutsidePriceRange(): void
    {
        $this->formKeyValidatorMock->method('validate')->willReturn(true);
        $this->requestMock->method('getParam')
            ->willReturnCallback(fn($key, $default = null) => match ($key) {
                'item_ids' => [5],
                'epp_code' => 'EPP_3YR',
                default    => $default,
            });

        $eppMock = $this->createMock(Epp::class);
        $eppMock->method('isEnabled')->willReturn(true);
        $eppMock->method('getMinPrice')->willReturn(500.00);
        $eppMock->method('getMaxPrice')->willReturn(999.00);
        $this->eppRepositoryMock->method('getByCode')->willReturn($eppMock);

        $itemMock = $this->createMock(Item::class);
        $itemMock->method('getId')->willReturn(5);
        $itemMock->method('getPrice')->willReturn(100.00); // below min
        $this->detectorMock->method('isApplianceItem')->with($itemMock)->willReturn(true);

        $this->quoteMock->method('getAllVisibleItems')->willReturn([$itemMock]);
        $this->quoteMock->method('collectTotals')->willReturnSelf();
        $this->cartRepositoryMock->expects($this->once())->method('save');

        // No option should be added because price is out of range
        $itemMock->expects($this->never())->method('addOption');

        $this->resultMock
            ->expects($this->once())
            ->method('setData')
            ->with(['success' => true])
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testExecuteAppliesPlanToValidItems(): void
    {
        $this->formKeyValidatorMock->method('validate')->willReturn(true);
        $this->requestMock->method('getParam')
            ->willReturnCallback(fn($key, $default = null) => match ($key) {
                'item_ids' => [10],
                'epp_code' => 'EPP_3YR',
                default    => $default,
            });

        $eppMock = $this->createMock(Epp::class);
        $eppMock->method('isEnabled')->willReturn(true);
        $eppMock->method('getMinPrice')->willReturn(200.00);
        $eppMock->method('getMaxPrice')->willReturn(999.00);
        $eppMock->method('getName')->willReturn('3-Year Plan');
        $eppMock->method('getRetailPrice')->willReturn(99.99);
        $eppMock->method('getYears')->willReturn(3);
        $this->eppRepositoryMock->method('getByCode')->willReturn($eppMock);

        $itemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call', 'getId', 'getOptionByCode', 'getPrice', 'addOption'])
            ->getMock();
        $itemMock->method('getId')->willReturn(10);
        $itemMock->method('getPrice')->willReturn(500.00);
        $itemMock->method('__call')
            ->willReturnCallback(fn(string $m, array $a) => $m === 'getProductId' ? 7 : null);
        $itemMock->method('getOptionByCode')->willReturn(null);
        $this->detectorMock->method('isApplianceItem')->with($itemMock)->willReturn(true);

        $this->quoteMock->method('getAllVisibleItems')->willReturn([$itemMock]);
        $this->quoteMock->method('collectTotals')->willReturnSelf();
        $this->cartRepositoryMock->expects($this->once())->method('save');

        $this->resultMock
            ->expects($this->once())
            ->method('setData')
            ->with(['success' => true])
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testExecuteSkipsNonApplianceItems(): void
    {
        $this->formKeyValidatorMock->method('validate')->willReturn(true);
        $this->requestMock->method('getParam')
            ->willReturnCallback(fn($key, $default = null) => match ($key) {
                'item_ids' => [20],
                'epp_code' => 'EPP_3YR',
                default    => $default,
            });

        $eppMock = $this->createMock(Epp::class);
        $eppMock->method('isEnabled')->willReturn(true);
        $eppMock->method('getMinPrice')->willReturn(100.00);
        $eppMock->method('getMaxPrice')->willReturn(999.00);
        $this->eppRepositoryMock->method('getByCode')->willReturn($eppMock);

        $itemMock = $this->createMock(Item::class);
        $itemMock->method('getId')->willReturn(20);
        $this->detectorMock->method('isApplianceItem')->with($itemMock)->willReturn(false);

        $this->quoteMock->method('getAllVisibleItems')->willReturn([$itemMock]);
        $this->quoteMock->method('collectTotals')->willReturnSelf();
        $this->cartRepositoryMock->expects($this->once())->method('save');

        // Item is not an appliance, so addOption should never be called
        $itemMock->expects($this->never())->method('addOption');

        $this->resultMock
            ->expects($this->once())
            ->method('setData')
            ->with(['success' => true])
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testExecuteSkipsItemWhenNotInRequestedIds(): void
    {
        // Item id=99 is NOT in itemIds=[10] → the "not in itemIds" continue branch is taken
        $this->formKeyValidatorMock->method('validate')->willReturn(true);
        $this->requestMock->method('getParam')
            ->willReturnCallback(fn($key, $default = null) => match ($key) {
                'item_ids' => [10],
                'epp_code' => 'EPP_3YR',
                default    => $default,
            });

        $eppMock = $this->createMock(Epp::class);
        $eppMock->method('isEnabled')->willReturn(true);
        $eppMock->method('getMinPrice')->willReturn(100.00);
        $eppMock->method('getMaxPrice')->willReturn(999.00);
        $this->eppRepositoryMock->method('getByCode')->willReturn($eppMock);

        $itemMock = $this->createMock(Item::class);
        $itemMock->method('getId')->willReturn(99); // NOT in [10]
        $this->detectorMock->method('isApplianceItem')->with($itemMock)->willReturn(true);

        $this->quoteMock->method('getAllVisibleItems')->willReturn([$itemMock]);
        $this->quoteMock->method('collectTotals')->willReturnSelf();
        $this->cartRepositoryMock->expects($this->once())->method('save');

        $itemMock->expects($this->never())->method('addOption');

        $this->resultMock
            ->expects($this->once())
            ->method('setData')
            ->with(['success' => true])
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testExecuteAppliesPlanToItemWithExistingOptionAndNonZeroProductId(): void
    {
        // When item already has an EPP option with productId>0, setValue is called (no addOption)
        $this->formKeyValidatorMock->method('validate')->willReturn(true);
        $this->requestMock->method('getParam')
            ->willReturnCallback(fn($key, $default = null) => match ($key) {
                'item_ids' => [15],
                'epp_code' => 'EPP_3YR',
                default    => $default,
            });

        $eppMock = $this->createMock(Epp::class);
        $eppMock->method('isEnabled')->willReturn(true);
        $eppMock->method('getMinPrice')->willReturn(200.00);
        $eppMock->method('getMaxPrice')->willReturn(999.00);
        $eppMock->method('getName')->willReturn('3-Year Plan');
        $eppMock->method('getRetailPrice')->willReturn(99.99);
        $eppMock->method('getYears')->willReturn(3);
        $this->eppRepositoryMock->method('getByCode')->willReturn($eppMock);

        // Existing option — productId=5 (non-zero, skips the inner setData branch)
        $existingOption = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item\Option::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();
        $existingOption->method('__call')
            ->willReturnCallback(fn(string $m, array $a) => $m === 'getProductId' ? 5 : null);

        $itemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call', 'getId', 'getOptionByCode', 'getPrice'])
            ->getMock();
        $itemMock->method('getId')->willReturn(15);
        $itemMock->method('getPrice')->willReturn(500.00);
        $itemMock->method('__call')->willReturn(null);
        $itemMock->method('getOptionByCode')->willReturn($existingOption);
        $this->detectorMock->method('isApplianceItem')->willReturn(true);

        $this->quoteMock->method('getAllVisibleItems')->willReturn([$itemMock]);
        $this->quoteMock->method('collectTotals')->willReturnSelf();
        $this->cartRepositoryMock->expects($this->once())->method('save');

        $this->resultMock
            ->expects($this->once())
            ->method('setData')
            ->with(['success' => true])
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testExecuteAppliesPlanToItemWithExistingOptionAndZeroProductId(): void
    {
        // When existing option has productId=0, setData('product_id',...) is called
        $this->formKeyValidatorMock->method('validate')->willReturn(true);
        $this->requestMock->method('getParam')
            ->willReturnCallback(fn($key, $default = null) => match ($key) {
                'item_ids' => [18],
                'epp_code' => 'EPP_3YR',
                default    => $default,
            });

        $eppMock = $this->createMock(Epp::class);
        $eppMock->method('isEnabled')->willReturn(true);
        $eppMock->method('getMinPrice')->willReturn(200.00);
        $eppMock->method('getMaxPrice')->willReturn(999.00);
        $eppMock->method('getName')->willReturn('3-Year Plan');
        $eppMock->method('getRetailPrice')->willReturn(99.99);
        $eppMock->method('getYears')->willReturn(3);
        $this->eppRepositoryMock->method('getByCode')->willReturn($eppMock);

        // productId=0 → triggers setData('product_id', ...)
        $existingOption = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item\Option::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();
        $existingOption->method('__call')
            ->willReturnCallback(fn(string $m, array $a) => $m === 'getProductId' ? 0 : null);

        $itemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call', 'getId', 'getOptionByCode', 'getPrice'])
            ->getMock();
        $itemMock->method('getId')->willReturn(18);
        $itemMock->method('getPrice')->willReturn(500.00);
        $itemMock->method('__call')->willReturn(null); // getProductId() → 0 for item too
        $itemMock->method('getOptionByCode')->willReturn($existingOption);
        $this->detectorMock->method('isApplianceItem')->willReturn(true);

        $this->quoteMock->method('getAllVisibleItems')->willReturn([$itemMock]);
        $this->quoteMock->method('collectTotals')->willReturnSelf();
        $this->cartRepositoryMock->expects($this->once())->method('save');

        $this->resultMock
            ->expects($this->once())
            ->method('setData')
            ->with(['success' => true])
            ->willReturnSelf();

        $this->controller->execute();
    }

    public function testExecuteOuterExceptionReturnsCannedError(): void
    {
        $sessionMock = $this->createMock(\Magento\Checkout\Model\Session::class);
        $sessionMock->method('getQuote')->willThrowException(new \Exception('session crash'));

        $controller = new UpdateEpp(
            $this->requestMock,
            $this->jsonFactoryMock,
            $sessionMock,
            $this->cartRepositoryMock,
            $this->detectorMock,
            $this->formKeyValidatorMock,
            $this->eppRepositoryMock,
            $this->loggerMock
        );

        $this->formKeyValidatorMock->method('validate')->willReturn(true);
        $this->requestMock->method('getParam')
            ->willReturnCallback(fn($key, $default = null) => match ($key) {
                'item_ids' => [1],
                'epp_code' => 'EPP_3YR',
                default    => $default,
            });
        $this->eppRepositoryMock->method('getByCode')->willReturn($this->createMock(Epp::class));

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

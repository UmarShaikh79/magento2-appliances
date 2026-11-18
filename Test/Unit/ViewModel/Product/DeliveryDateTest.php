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

namespace MageTips\Appliances\Test\Unit\ViewModel\Product;

use MageTips\Appliances\Model\Config\Delivery as DeliveryConfig;
use MageTips\Appliances\Model\Delivery\Calculator;
use MageTips\Appliances\ViewModel\Product\DeliveryDate;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeliveryDateTest extends TestCase
{
    private DeliveryDate $viewModel;

    /** @var ProductRepositoryInterface&MockObject */
    private MockObject $productRepositoryMock;

    /** @var RequestInterface&MockObject */
    private MockObject $requestMock;

    /** @var DeliveryConfig&MockObject */
    private MockObject $configMock;

    /** @var Calculator&MockObject */
    private MockObject $calculatorMock;

    /** @var Json&MockObject */
    private MockObject $jsonMock;

    protected function setUp(): void
    {
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->requestMock           = $this->createMock(RequestInterface::class);
        $this->configMock            = $this->createMock(DeliveryConfig::class);
        $this->calculatorMock        = $this->createMock(Calculator::class);
        $this->jsonMock              = $this->createMock(Json::class);

        $this->viewModel = new DeliveryDate(
            $this->productRepositoryMock,
            $this->requestMock,
            $this->configMock,
            $this->calculatorMock,
            $this->jsonMock
        );
    }

    public function testIsEnabledReturnsTrueWhenConfigEnabled(): void
    {
        $this->configMock->method('isEnabled')->willReturn(true);

        $this->assertTrue($this->viewModel->isEnabled());
    }

    public function testIsEnabledReturnsFalseWhenConfigDisabled(): void
    {
        $this->configMock->method('isEnabled')->willReturn(false);

        $this->assertFalse($this->viewModel->isEnabled());
    }

    public function testIsApplianceReturnsTrueWhenProductIsAppliance(): void
    {
        $productMock = $this->createMock(Product::class);
        $productMock->method('getData')->with('is_appliance')->willReturn(1);

        $this->requestMock->method('getParam')->with('id')->willReturn('42');
        $this->productRepositoryMock->method('getById')->with(42)->willReturn($productMock);

        $this->assertTrue($this->viewModel->isAppliance());
    }

    public function testIsApplianceReturnsFalseWhenProductIsNotAppliance(): void
    {
        $productMock = $this->createMock(Product::class);
        $productMock->method('getData')->with('is_appliance')->willReturn(0);

        $this->requestMock->method('getParam')->with('id')->willReturn('42');
        $this->productRepositoryMock->method('getById')->with(42)->willReturn($productMock);

        $this->assertFalse($this->viewModel->isAppliance());
    }

    public function testIsApplianceReturnsFalseWhenNoCurrentProduct(): void
    {
        $this->requestMock->method('getParam')->with('id')->willReturn(null);

        $this->assertFalse($this->viewModel->isAppliance());
    }

    public function testGetProductReturnsProductFromRepository(): void
    {
        $productMock = $this->createMock(Product::class);

        $this->requestMock->method('getParam')->with('id')->willReturn('42');
        $this->productRepositoryMock->method('getById')->with(42)->willReturn($productMock);

        $this->assertSame($productMock, $this->viewModel->getProduct());
    }

    public function testGetProductReturnsNullWhenNoIdParam(): void
    {
        $this->requestMock->method('getParam')->with('id')->willReturn(null);
        $this->productRepositoryMock->expects($this->never())->method('getById');

        $this->assertNull($this->viewModel->getProduct());
    }

    public function testGetProductReturnsNullWhenProductNotFound(): void
    {
        $this->requestMock->method('getParam')->with('id')->willReturn('999');
        $this->productRepositoryMock->method('getById')
            ->with(999)
            ->willThrowException(new NoSuchEntityException(__('Not found')));

        $this->assertNull($this->viewModel->getProduct());
    }

    public function testGetHeadlineDelegatesToCalculator(): void
    {
        $this->calculatorMock->method('getHeadline')->willReturn('Get it by Wednesday, June 7');

        $this->assertSame('Get it by Wednesday, June 7', $this->viewModel->getHeadline());
    }

    public function testGetSubtextDelegatesToConfig(): void
    {
        $this->configMock->method('getSubtext')->willReturn('Free delivery on all appliances');

        $this->assertSame('Free delivery on all appliances', $this->viewModel->getSubtext());
    }

    public function testGetPickerConfigJsonReturnsSerializedPickerData(): void
    {
        $pickerData = [
            'earliest'     => '2026-07-01',
            'disabledDays' => [0, 6],
            'holidays'     => ['2026-12-25'],
        ];

        $this->calculatorMock->method('getPickerData')->willReturn($pickerData);
        $this->jsonMock->method('serialize')->with($pickerData)->willReturn('{"earliest":"2026-07-01"}');

        $this->assertSame('{"earliest":"2026-07-01"}', $this->viewModel->getPickerConfigJson());
    }
}

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

namespace MageTips\Appliances\Test\Unit\Plugin\Checkout\CustomerData;

use MageTips\Appliances\Api\EppRepositoryInterface;
use MageTips\Appliances\Model\Epp;
use MageTips\Appliances\Plugin\Checkout\CustomerData\AddEppDataPlugin;
use Magento\Checkout\CustomerData\ItemPool;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\Option;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddEppDataPluginTest extends TestCase
{
    private AddEppDataPlugin $plugin;

    /** @var EppRepositoryInterface&MockObject */
    private MockObject $eppRepositoryMock;

    /** @var ItemPool&MockObject */
    private MockObject $subjectMock;

    /** @var Item&MockObject */
    private MockObject $itemMock;

    protected function setUp(): void
    {
        $this->eppRepositoryMock = $this->createMock(EppRepositoryInterface::class);
        $this->plugin            = new AddEppDataPlugin(
            $this->eppRepositoryMock,
            $this->createMock(\Psr\Log\LoggerInterface::class)
        );

        $this->subjectMock = $this->createMock(ItemPool::class);
        $this->itemMock    = $this->createMock(Item::class);
    }

    public function testAfterGetItemDataReturnsUnchangedWhenNoOption(): void
    {
        $this->itemMock->method('getOptionByCode')->willReturn(null);

        $result = $this->plugin->afterGetItemData($this->subjectMock, ['name' => 'Widget'], $this->itemMock);

        $this->assertSame(['name' => 'Widget'], $result);
    }

    public function testAfterGetItemDataReturnsUnchangedWhenOptionValueEmpty(): void
    {
        $optionMock = $this->createMock(Option::class);
        $optionMock->method('getValue')->willReturn('');
        $this->itemMock->method('getOptionByCode')->willReturn($optionMock);

        $result = $this->plugin->afterGetItemData($this->subjectMock, [], $this->itemMock);

        $this->assertSame([], $result);
    }

    public function testAfterGetItemDataAppendsPlanNameForMultipleYears(): void
    {
        $optionMock = $this->createMock(Option::class);
        $optionMock->method('getValue')->willReturn('EPP_3YR');
        $this->itemMock->method('getOptionByCode')->willReturn($optionMock);

        $eppMock = $this->createMock(Epp::class);
        $eppMock->method('getName')->willReturn('3-Year Plan');
        $eppMock->method('getYears')->willReturn(3);
        $this->eppRepositoryMock->method('getByCode')->with('EPP_3YR')->willReturn($eppMock);

        $result = $this->plugin->afterGetItemData($this->subjectMock, [], $this->itemMock);

        $this->assertArrayHasKey('epp_plan_name', $result);
        $this->assertStringContainsString('3-Year Plan', $result['epp_plan_name']);
        $this->assertStringContainsString('YEARS', $result['epp_plan_name']);
    }

    public function testAfterGetItemDataAppendsPlanNameForSingularYear(): void
    {
        $optionMock = $this->createMock(Option::class);
        $optionMock->method('getValue')->willReturn('EPP_1YR');
        $this->itemMock->method('getOptionByCode')->willReturn($optionMock);

        $eppMock = $this->createMock(Epp::class);
        $eppMock->method('getName')->willReturn('1-Year Plan');
        $eppMock->method('getYears')->willReturn(1);
        $this->eppRepositoryMock->method('getByCode')->willReturn($eppMock);

        $result = $this->plugin->afterGetItemData($this->subjectMock, [], $this->itemMock);

        $this->assertStringContainsString('YEAR', $result['epp_plan_name']);
        $this->assertStringNotContainsString('YEARS', $result['epp_plan_name']);
    }

    public function testAfterGetItemDataHandlesRepositoryException(): void
    {
        $optionMock = $this->createMock(Option::class);
        $optionMock->method('getValue')->willReturn('BAD_CODE');
        $this->itemMock->method('getOptionByCode')->willReturn($optionMock);

        $this->eppRepositoryMock
            ->method('getByCode')
            ->willThrowException(new \Exception('Not found'));

        $result = $this->plugin->afterGetItemData($this->subjectMock, ['x' => 1], $this->itemMock);

        // Returns original result unchanged
        $this->assertSame(['x' => 1], $result);
    }
}

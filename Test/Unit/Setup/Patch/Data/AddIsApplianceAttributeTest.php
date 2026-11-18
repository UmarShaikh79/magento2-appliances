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

namespace MageTips\Appliances\Test\Unit\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use MageTips\Appliances\Setup\Patch\Data\AddIsApplianceAttribute;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddIsApplianceAttributeTest extends TestCase
{
    /** @var ModuleDataSetupInterface&MockObject */
    private MockObject $moduleDataSetupMock;

    /** @var EavSetupFactory&MockObject */
    private MockObject $eavSetupFactoryMock;

    /** @var EavSetup&MockObject */
    private MockObject $eavSetupMock;

    /** @var AdapterInterface&MockObject */
    private MockObject $connectionMock;

    private AddIsApplianceAttribute $patch;

    protected function setUp(): void
    {
        $this->connectionMock       = $this->createMock(AdapterInterface::class);
        $this->moduleDataSetupMock  = $this->createMock(ModuleDataSetupInterface::class);
        $this->moduleDataSetupMock->method('getConnection')->willReturn($this->connectionMock);
        $this->eavSetupMock         = $this->createMock(EavSetup::class);
        $this->eavSetupFactoryMock  = $this->createMock(EavSetupFactory::class);
        $this->eavSetupFactoryMock->method('create')->willReturn($this->eavSetupMock);

        $this->patch = new AddIsApplianceAttribute(
            $this->moduleDataSetupMock,
            $this->eavSetupFactoryMock
        );
    }

    public function testApplyCallsStartAndEndSetupAndAddsAttribute(): void
    {
        $this->connectionMock->expects($this->once())->method('startSetup');
        $this->connectionMock->expects($this->once())->method('endSetup');
        $this->eavSetupMock
            ->expects($this->once())
            ->method('addAttribute')
            ->with(
                \Magento\Catalog\Model\Product::ENTITY,
                AddIsApplianceAttribute::ATTRIBUTE_CODE,
                $this->isArray()
            );

        $this->patch->apply();
    }

    public function testRevertCallsStartAndEndSetupAndRemovesAttribute(): void
    {
        $this->connectionMock->expects($this->once())->method('startSetup');
        $this->connectionMock->expects($this->once())->method('endSetup');
        $this->eavSetupMock
            ->expects($this->once())
            ->method('removeAttribute')
            ->with(\Magento\Catalog\Model\Product::ENTITY, AddIsApplianceAttribute::ATTRIBUTE_CODE);

        $this->patch->revert();
    }

    public function testGetDependenciesReturnsEmptyArray(): void
    {
        $this->assertSame([], AddIsApplianceAttribute::getDependencies());
    }

    public function testGetAliasesReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->patch->getAliases());
    }
}

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

use MageTips\Appliances\Model\Epp;
use MageTips\Appliances\Model\EppFactory;
use MageTips\Appliances\Model\ResourceModel\Epp as EppResourceModel;
use MageTips\Appliances\Setup\Patch\Data\CreateDefaultEppPlans;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateDefaultEppPlansTest extends TestCase
{
    /** @var EppFactory&MockObject */
    private MockObject $eppFactoryMock;

    /** @var EppResourceModel&MockObject */
    private MockObject $eppResourceMock;

    /** @var Epp&MockObject */
    private MockObject $eppMock;

    private CreateDefaultEppPlans $patch;

    protected function setUp(): void
    {
        $this->eppMock        = $this->getMockBuilder(Epp::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $this->eppFactoryMock = $this->createMock(EppFactory::class);
        $this->eppFactoryMock->method('create')->willReturn($this->eppMock);
        $this->eppResourceMock = $this->createMock(EppResourceModel::class);

        $this->patch = new CreateDefaultEppPlans($this->eppFactoryMock, $this->eppResourceMock);
    }

    public function testApplyCreatesNewPlansWhenNotExisting(): void
    {
        // getId() returns null/0 → plan does not exist → save is called
        $this->eppMock->method('getId')->willReturn(null);

        $this->eppResourceMock
            ->expects($this->exactly(2))
            ->method('load');

        $this->eppResourceMock
            ->expects($this->exactly(2))
            ->method('save');

        $this->patch->apply();
    }

    public function testApplySkipsExistingPlans(): void
    {
        // getId() returns an existing id → plan already exists → save is NOT called
        $this->eppMock->method('getId')->willReturn(42);

        $this->eppResourceMock->expects($this->never())->method('save');

        $this->patch->apply();
    }

    public function testGetDependenciesReturnsEmptyArray(): void
    {
        $this->assertSame([], CreateDefaultEppPlans::getDependencies());
    }

    public function testGetAliasesReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->patch->getAliases());
    }
}

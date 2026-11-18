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

namespace MageTips\Appliances\Test\Unit\Model;

use MageTips\Appliances\Model\Epp;
use PHPUnit\Framework\TestCase;

class EppTest extends TestCase
{
    private Epp $epp;

    protected function setUp(): void
    {
        // Bypass AbstractModel::_construct() which calls _getResource() → ObjectManager
        $this->epp = $this->getMockBuilder(Epp::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
    }

    public function testGetSetCode(): void
    {
        $this->epp->setCode('EPP_2YR');
        $this->assertSame('EPP_2YR', $this->epp->getCode());
    }

    public function testGetSetName(): void
    {
        $this->epp->setName('2-Year Standard');
        $this->assertSame('2-Year Standard', $this->epp->getName());
    }

    public function testGetSetDescription(): void
    {
        $this->epp->setDescription('Covers manufacturing defects');
        $this->assertSame('Covers manufacturing defects', $this->epp->getDescription());
    }

    public function testGetSetMinPrice(): void
    {
        $this->epp->setMinPrice(199.99);
        $this->assertSame(199.99, $this->epp->getMinPrice());
    }

    public function testGetSetMaxPrice(): void
    {
        $this->epp->setMaxPrice(999.99);
        $this->assertSame(999.99, $this->epp->getMaxPrice());
    }

    public function testGetSetRetailPrice(): void
    {
        $this->epp->setRetailPrice(49.99);
        $this->assertSame(49.99, $this->epp->getRetailPrice());
    }

    public function testGetSetYears(): void
    {
        $this->epp->setYears(3);
        $this->assertSame(3, $this->epp->getYears());
    }

    public function testGetSetDetailsUrl(): void
    {
        $this->epp->setDetailsUrl('https://example.com/plan');
        $this->assertSame('https://example.com/plan', $this->epp->getDetailsUrl());
    }

    public function testGetSetDetailsPdfPath(): void
    {
        $this->epp->setDetailsPdfPath('plans/epp-2yr.pdf');
        $this->assertSame('plans/epp-2yr.pdf', $this->epp->getDetailsPdfPath());
    }

    public function testIsEnabledTrue(): void
    {
        $this->epp->setEnabled(true);
        $this->assertTrue($this->epp->isEnabled());
    }

    public function testIsEnabledFalse(): void
    {
        $this->epp->setEnabled(false);
        $this->assertFalse($this->epp->isEnabled());
    }

    public function testGetEntityIdReturnsNullWhenNotSet(): void
    {
        $this->assertNull($this->epp->getEntityId());
    }

    public function testGetEntityIdReturnsInt(): void
    {
        $this->epp->setData('entity_id', '42');
        $this->assertSame(42, $this->epp->getEntityId());
    }

    public function testGetMinPriceReturnsNullWhenNotSet(): void
    {
        $this->assertNull($this->epp->getMinPrice());
    }

    public function testGetMaxPriceReturnsNullWhenNotSet(): void
    {
        $this->assertNull($this->epp->getMaxPrice());
    }

    public function testGetRetailPriceReturnsNullWhenNotSet(): void
    {
        $this->assertNull($this->epp->getRetailPrice());
    }

    public function testGetYearsReturnsNullWhenNotSet(): void
    {
        $this->assertNull($this->epp->getYears());
    }

    public function testGetCreatedAtReturnsNullWhenNotSet(): void
    {
        $this->assertNull($this->epp->getCreatedAt());
    }

    public function testGetUpdatedAtReturnsNullWhenNotSet(): void
    {
        $this->assertNull($this->epp->getUpdatedAt());
    }

    public function testCacheTagConstant(): void
    {
        $this->assertSame('magetips_appliances_epp', Epp::CACHE_TAG);
    }
}

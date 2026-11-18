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

namespace MageTips\Appliances\Test\Unit\Model\ResourceModel\Epp;

use MageTips\Appliances\Model\ResourceModel\Epp\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    /** @var Collection&MockObject */
    private MockObject $collection;

    protected function setUp(): void
    {
        $this->collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToFilter'])
            ->getMock();
    }

    public function testAddEnabledFilterAddsEnabledFlag(): void
    {
        $this->collection
            ->expects($this->once())
            ->method('addFieldToFilter')
            ->with('enabled', 1)
            ->willReturnSelf();

        $result = $this->collection->addEnabledFilter();

        $this->assertSame($this->collection, $result);
    }

    public function testAddPriceRangeFilterAddsTwoBoundFilters(): void
    {
        $this->collection
            ->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $result = $this->collection->addPriceRangeFilter(299.99);

        $this->assertSame($this->collection, $result);
    }

    public function testAddCodeFilterAddsCodeField(): void
    {
        $this->collection
            ->expects($this->once())
            ->method('addFieldToFilter')
            ->with('code', 'EPP_3YR')
            ->willReturnSelf();

        $result = $this->collection->addCodeFilter('EPP_3YR');

        $this->assertSame($this->collection, $result);
    }
}

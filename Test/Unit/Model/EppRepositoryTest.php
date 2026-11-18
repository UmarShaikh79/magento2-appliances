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
use MageTips\Appliances\Model\EppFactory;
use MageTips\Appliances\Model\EppRepository;
use MageTips\Appliances\Model\ResourceModel\Epp as EppResourceModel;
use MageTips\Appliances\Model\ResourceModel\Epp\Collection;
use MageTips\Appliances\Model\ResourceModel\Epp\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EppRepositoryTest extends TestCase
{
    private EppRepository $repository;

    /** @var EppFactory&MockObject */
    private MockObject $eppFactoryMock;

    /** @var EppResourceModel&MockObject */
    private MockObject $eppResourceMock;

    /** @var CollectionFactory&MockObject */
    private MockObject $collectionFactoryMock;

    /** @var Epp&MockObject */
    private MockObject $eppMock;

    /** @var Collection&MockObject */
    private MockObject $collectionMock;

    protected function setUp(): void
    {
        $this->eppFactoryMock        = $this->createMock(EppFactory::class);
        $this->eppResourceMock       = $this->createMock(EppResourceModel::class);
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->eppMock               = $this->createMock(Epp::class);
        $this->collectionMock        = $this->createMock(Collection::class);

        $this->repository = new EppRepository(
            $this->eppFactoryMock,
            $this->eppResourceMock,
            $this->collectionFactoryMock
        );
    }

    public function testGetByIdReturnsEpp(): void
    {
        $this->eppFactoryMock->method('create')->willReturn($this->eppMock);
        $this->eppMock->method('getId')->willReturn(1);

        $result = $this->repository->getById(1);

        $this->assertSame($this->eppMock, $result);
    }

    public function testGetByIdThrowsWhenNotFound(): void
    {
        $this->expectException(NoSuchEntityException::class);

        $this->eppFactoryMock->method('create')->willReturn($this->eppMock);
        $this->eppMock->method('getId')->willReturn(null);

        $this->repository->getById(999);
    }

    public function testGetByCodeReturnsEpp(): void
    {
        $this->eppFactoryMock->method('create')->willReturn($this->eppMock);
        $this->eppMock->method('getId')->willReturn(5);

        $result = $this->repository->getByCode('EPP_3YR');

        $this->assertSame($this->eppMock, $result);
    }

    public function testGetByCodeThrowsWhenNotFound(): void
    {
        $this->expectException(NoSuchEntityException::class);

        $this->eppFactoryMock->method('create')->willReturn($this->eppMock);
        $this->eppMock->method('getId')->willReturn(null);

        $this->repository->getByCode('MISSING_CODE');
    }

    public function testSaveReturnsEpp(): void
    {
        $result = $this->repository->save($this->eppMock);

        $this->assertSame($this->eppMock, $result);
    }

    public function testSaveThrowsOnException(): void
    {
        $this->expectException(LocalizedException::class);

        $this->eppResourceMock
            ->method('save')
            ->willThrowException(new \Exception('DB error'));

        $this->repository->save($this->eppMock);
    }

    public function testDeleteReturnsTrue(): void
    {
        $result = $this->repository->delete($this->eppMock);

        $this->assertTrue($result);
    }

    public function testDeleteThrowsOnException(): void
    {
        $this->expectException(LocalizedException::class);

        $this->eppResourceMock
            ->method('delete')
            ->willThrowException(new \Exception('DB error'));

        $this->repository->delete($this->eppMock);
    }

    public function testGetListReturnsItems(): void
    {
        $items = [$this->eppMock];
        $this->collectionFactoryMock->method('create')->willReturn($this->collectionMock);
        $this->collectionMock->method('getItems')->willReturn($items);

        $result = $this->repository->getList();

        $this->assertSame($items, $result);
    }

    public function testGetEnabledListReturnsFilteredItems(): void
    {
        $items = [$this->eppMock];
        $this->collectionFactoryMock->method('create')->willReturn($this->collectionMock);
        $this->collectionMock->method('addEnabledFilter')->willReturnSelf();
        $this->collectionMock->method('getItems')->willReturn($items);

        $result = $this->repository->getEnabledList();

        $this->assertSame($items, $result);
    }

    public function testGetApplicablePlansByPriceReturnsFilteredItems(): void
    {
        $items = [$this->eppMock];
        $this->collectionFactoryMock->method('create')->willReturn($this->collectionMock);
        $this->collectionMock->method('addEnabledFilter')->willReturnSelf();
        $this->collectionMock->method('addPriceRangeFilter')->with(499.99)->willReturnSelf();
        $this->collectionMock->method('getItems')->willReturn($items);

        $result = $this->repository->getApplicablePlansByPrice(499.99);

        $this->assertSame($items, $result);
    }

    public function testGetApplicablePlansByPriceReturnsEmptyWhenNoneMatch(): void
    {
        $this->collectionFactoryMock->method('create')->willReturn($this->collectionMock);
        $this->collectionMock->method('addEnabledFilter')->willReturnSelf();
        $this->collectionMock->method('addPriceRangeFilter')->willReturnSelf();
        $this->collectionMock->method('getItems')->willReturn([]);

        $result = $this->repository->getApplicablePlansByPrice(1.00);

        $this->assertSame([], $result);
    }
}

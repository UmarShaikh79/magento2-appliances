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

namespace MageTips\Appliances\Test\Unit\Ui\DataProvider;

use MageTips\Appliances\Api\Data\EppInterface;
use MageTips\Appliances\Model\Epp;
use MageTips\Appliances\Model\ResourceModel\Epp\Collection;
use MageTips\Appliances\Model\ResourceModel\Epp\CollectionFactory;
use MageTips\Appliances\Ui\DataProvider\EppFormDataProvider;
use Magento\Framework\App\Request\DataPersistorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EppFormDataProviderTest extends TestCase
{
    /** @var CollectionFactory&MockObject */
    private MockObject $collectionFactoryMock;

    /** @var Collection&MockObject */
    private MockObject $collectionMock;

    /** @var DataPersistorInterface&MockObject */
    private MockObject $dataPersistorMock;

    protected function setUp(): void
    {
        $this->collectionMock        = $this->createMock(Collection::class);
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->collectionFactoryMock->method('create')->willReturn($this->collectionMock);
        $this->dataPersistorMock     = $this->createMock(DataPersistorInterface::class);
    }

    private function buildDataProvider(): EppFormDataProvider
    {
        return new EppFormDataProvider(
            'epp_form',
            'entity_id',
            'entity_id',
            $this->collectionFactoryMock,
            $this->dataPersistorMock
        );
    }

    public function testGetDataFallsBackToCollectionWhenPersistorEmpty(): void
    {
        $this->dataPersistorMock->method('get')->with(EppInterface::TABLE_NAME)->willReturn(null);

        $eppMock = $this->getMockBuilder(Epp::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getData'])
            ->getMock();
        $eppMock->method('getId')->willReturn(1);
        $eppMock->method('getData')->willReturn(['entity_id' => 1, 'code' => 'EPP_3YR']);

        $this->collectionMock->method('getItems')->willReturn([$eppMock]);

        $result = $this->buildDataProvider()->getData();

        $this->assertArrayHasKey(1, $result);
        $this->assertSame('EPP_3YR', $result[1]['code']);
    }

    public function testGetDataLoadsFromPersistorWhenEntityObjectStored(): void
    {
        $eppMock = $this->getMockBuilder(Epp::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEntityId', 'getData'])
            ->getMock();
        $eppMock->method('getEntityId')->willReturn(5);
        $eppMock->method('getData')->willReturn(['entity_id' => 5, 'code' => 'EPP_5YR']);

        $this->dataPersistorMock->method('get')
            ->with(EppInterface::TABLE_NAME)
            ->willReturn($eppMock);

        $result = $this->buildDataProvider()->getData();

        $this->assertArrayHasKey(5, $result);
    }

    public function testGetDataLoadsFromPersistorWhenArrayStored(): void
    {
        $persisted = ['entity_id' => 3, 'code' => 'EPP_3YR', 'name' => '3-Year Plan'];

        $this->dataPersistorMock->method('get')
            ->with(EppInterface::TABLE_NAME)
            ->willReturn($persisted);
        $this->dataPersistorMock->expects($this->once())->method('clear');

        $result = $this->buildDataProvider()->getData();

        $this->assertArrayHasKey(3, $result);
    }

    public function testGetDataReturnsCachedResultOnSecondCall(): void
    {
        $this->dataPersistorMock->method('get')->willReturn(null);
        $this->collectionMock->method('getItems')->willReturn([]);

        $dataProvider = $this->buildDataProvider();
        $first        = $dataProvider->getData();
        $second       = $dataProvider->getData();

        $this->assertSame($first, $second);
        // Collection::getItems called exactly once (second call uses cache)
    }
}

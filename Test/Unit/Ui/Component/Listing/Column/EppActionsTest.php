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

namespace MageTips\Appliances\Test\Unit\Ui\Component\Listing\Column;

use MageTips\Appliances\Ui\Component\Listing\Column\EppActions;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EppActionsTest extends TestCase
{
    /** @var EppActions&MockObject */
    private MockObject $actionsColumn;

    /** @var UrlInterface&MockObject */
    private MockObject $urlBuilderMock;

    protected function setUp(): void
    {
        $this->urlBuilderMock = $this->createMock(UrlInterface::class);

        // Skip the complex parent constructor; inject via reflection.
        $this->actionsColumn = $this->getMockBuilder(EppActions::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        // Inject urlBuilder (private property) via reflection.
        $urlProp = new \ReflectionProperty(EppActions::class, 'urlBuilder');
        $urlProp->setAccessible(true);
        $urlProp->setValue($this->actionsColumn, $this->urlBuilderMock);

        // Inject _data so that getData('name') returns 'actions'.
        $dataProp = new \ReflectionProperty(\Magento\Framework\DataObject::class, '_data');
        $dataProp->setAccessible(true);
        $dataProp->setValue($this->actionsColumn, ['name' => 'actions']);
    }

    public function testPrepareDataSourceReturnsUnchangedWhenNoItems(): void
    {
        $dataSource = ['data' => []];

        $result = $this->actionsColumn->prepareDataSource($dataSource);

        $this->assertSame($dataSource, $result);
    }

    public function testPrepareDataSourceSkipsItemsWithoutEntityId(): void
    {
        $dataSource = ['data' => ['items' => [['name' => 'No ID']]]];

        $result = $this->actionsColumn->prepareDataSource($dataSource);

        $this->assertArrayNotHasKey('actions', $result['data']['items'][0]);
    }

    public function testPrepareDataSourceAddsEditAndDeleteUrls(): void
    {
        $this->urlBuilderMock
            ->method('getUrl')
            ->willReturnCallback(fn($path, $params) => "https://example.com/$path/" . $params['entity_id']);

        $dataSource = [
            'data' => [
                'items' => [
                    ['entity_id' => 5, 'code' => 'EPP_3YR'],
                ],
            ],
        ];

        $result = $this->actionsColumn->prepareDataSource($dataSource);

        $item = $result['data']['items'][0];
        $this->assertArrayHasKey('actions', $item);
        $this->assertArrayHasKey('edit', $item['actions']);
        $this->assertArrayHasKey('delete', $item['actions']);
        $this->assertStringContainsString('/5', $item['actions']['edit']['href']);
        $this->assertArrayHasKey('confirm', $item['actions']['delete']);
    }
}

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

namespace MageTips\Appliances\Test\Unit\Block\Adminhtml\Epp\Edit;

use MageTips\Appliances\Block\Adminhtml\Epp\Edit\DeleteButton;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteButtonTest extends TestCase
{
    /** @var RequestInterface&MockObject */
    private MockObject $requestMock;

    /** @var UrlInterface&MockObject */
    private MockObject $urlBuilderMock;

    private DeleteButton $button;

    protected function setUp(): void
    {
        $this->requestMock    = $this->createMock(RequestInterface::class);
        $this->urlBuilderMock = $this->createMock(UrlInterface::class);

        $contextMock = $this->createMock(Context::class);
        $contextMock->method('getRequest')->willReturn($this->requestMock);
        $contextMock->method('getUrlBuilder')->willReturn($this->urlBuilderMock);

        $this->button = new DeleteButton($contextMock);
    }

    public function testGetButtonDataReturnsEmptyArrayWhenNoId(): void
    {
        $this->requestMock->method('getParam')->with('entity_id')->willReturn('0');

        $this->assertSame([], $this->button->getButtonData());
    }

    public function testGetButtonDataReturnsDeleteButtonWhenIdSet(): void
    {
        $this->requestMock->method('getParam')->with('entity_id')->willReturn('7');
        $this->urlBuilderMock
            ->method('getUrl')
            ->willReturn('https://example.com/admin/appliances/epp/delete/entity_id/7');

        $data = $this->button->getButtonData();

        $this->assertSame('delete', $data['class']);
        $this->assertStringContainsString('deleteConfirm', $data['on_click']);
    }
}

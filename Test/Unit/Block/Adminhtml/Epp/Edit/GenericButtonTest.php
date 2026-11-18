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

use MageTips\Appliances\Block\Adminhtml\Epp\Edit\GenericButton;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GenericButtonTest extends TestCase
{
    private GenericButton $button;

    /** @var Context&MockObject */
    private MockObject $contextMock;

    /** @var RequestInterface&MockObject */
    private MockObject $requestMock;

    /** @var UrlInterface&MockObject */
    private MockObject $urlBuilderMock;

    protected function setUp(): void
    {
        $this->requestMock    = $this->createMock(RequestInterface::class);
        $this->urlBuilderMock = $this->createMock(UrlInterface::class);

        $this->contextMock = $this->createMock(Context::class);
        $this->contextMock->method('getRequest')->willReturn($this->requestMock);
        $this->contextMock->method('getUrlBuilder')->willReturn($this->urlBuilderMock);

        $this->button = new GenericButton($this->contextMock);
    }

    public function testGetEppIdReturnsNullWhenParamIsZero(): void
    {
        $this->requestMock->method('getParam')->with('entity_id')->willReturn('0');

        $this->assertNull($this->button->getEppId());
    }

    public function testGetEppIdReturnsIntegerWhenParamIsSet(): void
    {
        $this->requestMock->method('getParam')->with('entity_id')->willReturn('42');

        $this->assertSame(42, $this->button->getEppId());
    }

    public function testGetUrlDelegatesToUrlBuilder(): void
    {
        $this->urlBuilderMock
            ->method('getUrl')
            ->with('appliances/epp/index', ['_current' => true])
            ->willReturn('https://example.com/admin/appliances/epp/index');

        $result = $this->button->getUrl('appliances/epp/index', ['_current' => true]);

        $this->assertSame('https://example.com/admin/appliances/epp/index', $result);
    }
}

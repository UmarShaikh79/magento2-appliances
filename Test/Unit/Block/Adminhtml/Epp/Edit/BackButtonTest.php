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

use MageTips\Appliances\Block\Adminhtml\Epp\Edit\BackButton;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BackButtonTest extends TestCase
{
    private BackButton $button;

    protected function setUp(): void
    {
        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getParam')->willReturn('0');

        $urlBuilderMock = $this->createMock(UrlInterface::class);
        $urlBuilderMock->method('getUrl')->willReturn('https://example.com/admin/*/*/');

        $contextMock = $this->createMock(Context::class);
        $contextMock->method('getRequest')->willReturn($requestMock);
        $contextMock->method('getUrlBuilder')->willReturn($urlBuilderMock);

        $this->button = new BackButton($contextMock);
    }

    public function testGetButtonDataReturnsBackButtonStructure(): void
    {
        $data = $this->button->getButtonData();

        $this->assertArrayHasKey('label', $data);
        $this->assertArrayHasKey('on_click', $data);
        $this->assertArrayHasKey('class', $data);
        $this->assertSame('back', $data['class']);
        $this->assertStringContainsString('location.href', $data['on_click']);
    }
}

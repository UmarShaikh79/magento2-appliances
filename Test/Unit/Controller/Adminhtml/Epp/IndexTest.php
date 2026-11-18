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

namespace MageTips\Appliances\Test\Unit\Controller\Adminhtml\Epp;

use MageTips\Appliances\Controller\Adminhtml\Epp\Index;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\PageFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    /** @var Index&MockObject */
    private MockObject $controller;

    /** @var PageFactory&MockObject */
    private MockObject $pageFactoryMock;

    protected function setUp(): void
    {
        $this->pageFactoryMock = $this->createMock(PageFactory::class);

        $this->controller = $this->getMockBuilder(Index::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $prop = new \ReflectionProperty(Index::class, 'resultPageFactory');
        $prop->setAccessible(true);
        $prop->setValue($this->controller, $this->pageFactoryMock);
    }

    public function testExecuteReturnsPageWithTitle(): void
    {
        $titleMock = $this->createMock(Title::class);
        $titleMock->method('prepend')->willReturnSelf();

        $configMock = $this->createMock(PageConfig::class);
        $configMock->method('getTitle')->willReturn($titleMock);

        $pageMock = $this->createMock(Page::class);
        $pageMock->method('getConfig')->willReturn($configMock);

        $this->pageFactoryMock->method('create')->willReturn($pageMock);

        $result = $this->controller->execute();

        $this->assertSame($pageMock, $result);
    }

    public function testAdminResourceConstant(): void
    {
        $this->assertSame('MageTips_Appliances::appliances_epp', Index::ADMIN_RESOURCE);
    }
}

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

use MageTips\Appliances\Api\Data\EppInterface;
use MageTips\Appliances\Api\EppRepositoryInterface;
use MageTips\Appliances\Controller\Adminhtml\Epp\Delete;
use MageTips\Appliances\Model\Epp;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DeleteTest extends TestCase
{
    /** @var Delete&MockObject */
    private MockObject $controller;

    /** @var EppRepositoryInterface&MockObject */
    private MockObject $eppRepositoryMock;

    /** @var LoggerInterface&MockObject */
    private MockObject $loggerMock;

    /** @var ManagerInterface&MockObject */
    private MockObject $messageManagerMock;

    /** @var RedirectFactory&MockObject */
    private MockObject $redirectFactoryMock;

    /** @var Redirect&MockObject */
    private MockObject $redirectMock;

    /** @var RequestInterface&MockObject */
    private MockObject $requestMock;

    protected function setUp(): void
    {
        $this->eppRepositoryMock   = $this->createMock(EppRepositoryInterface::class);
        $this->loggerMock          = $this->createMock(LoggerInterface::class);
        $this->messageManagerMock  = $this->createMock(ManagerInterface::class);
        $this->redirectMock        = $this->createMock(Redirect::class);
        $this->redirectFactoryMock = $this->createMock(RedirectFactory::class);
        $this->requestMock         = $this->createMock(RequestInterface::class);

        $this->redirectMock->method('setPath')->willReturnSelf();
        $this->redirectFactoryMock->method('create')->willReturn($this->redirectMock);

        $this->controller = $this->getMockBuilder(Delete::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRequest'])
            ->getMock();
        $this->controller->method('getRequest')->willReturn($this->requestMock);

        $this->inject('eppRepository', $this->eppRepositoryMock);
        $this->inject('logger', $this->loggerMock);
        $this->injectParent('messageManager', $this->messageManagerMock);
        $this->injectParent('resultRedirectFactory', $this->redirectFactoryMock);
    }

    private function inject(string $property, object $value): void
    {
        $prop = new \ReflectionProperty(Delete::class, $property);
        $prop->setAccessible(true);
        $prop->setValue($this->controller, $value);
    }

    private function injectParent(string $property, object $value): void
    {
        // Walk up the class hierarchy to find where the property is declared.
        $class = new \ReflectionClass($this->controller);
        while ($class) {
            if ($class->hasProperty($property)) {
                $prop = $class->getProperty($property);
                $prop->setAccessible(true);
                $prop->setValue($this->controller, $value);
                return;
            }
            $class = $class->getParentClass();
        }
    }

    public function testExecuteDeletesEppAndRedirects(): void
    {
        $this->requestMock
            ->method('getParam')
            ->with(EppInterface::ENTITY_ID)
            ->willReturn(7);

        $eppMock = $this->createMock(Epp::class);
        $this->eppRepositoryMock->method('getById')->with(7)->willReturn($eppMock);
        $this->eppRepositoryMock->method('delete')->with($eppMock);

        $this->messageManagerMock->expects($this->once())->method('addSuccessMessage');

        $result = $this->controller->execute();

        $this->assertSame($this->redirectMock, $result);
    }

    public function testExecuteRedirectsWhenEppNotFound(): void
    {
        $this->requestMock->method('getParam')->willReturn(99);

        $this->eppRepositoryMock
            ->method('getById')
            ->willThrowException(new LocalizedException(__('Not found')));

        $this->loggerMock->expects($this->once())->method('error');
        $this->messageManagerMock->expects($this->once())->method('addErrorMessage');

        $result = $this->controller->execute();

        $this->assertSame($this->redirectMock, $result);
    }

    public function testExecuteRedirectsWhenDeleteFails(): void
    {
        $this->requestMock->method('getParam')->willReturn(5);

        $eppMock = $this->createMock(Epp::class);
        $this->eppRepositoryMock->method('getById')->with(5)->willReturn($eppMock);
        $this->eppRepositoryMock
            ->method('delete')
            ->willThrowException(new LocalizedException(__('Cannot delete')));

        $this->loggerMock->expects($this->once())->method('error');
        $this->messageManagerMock->expects($this->once())->method('addErrorMessage');

        $result = $this->controller->execute();

        $this->assertSame($this->redirectMock, $result);
    }
}

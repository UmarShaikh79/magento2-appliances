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

namespace MageTips\Appliances\Test\Unit\Plugin\Cart\Item;

use MageTips\Appliances\Api\ApplianceDetectorInterface;
use MageTips\Appliances\Api\EppRepositoryInterface;
use MageTips\Appliances\Block\Cart\EppItemPlan;
use MageTips\Appliances\Plugin\Cart\Item\RendererPlugin;
use Magento\Checkout\Block\Cart\Item\Renderer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\View\LayoutInterface;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RendererPluginTest extends TestCase
{
    /** @var RendererPlugin */
    private RendererPlugin $plugin;

    /** @var ApplianceDetectorInterface&MockObject */
    private MockObject $detectorMock;

    /** @var EppRepositoryInterface&MockObject */
    private MockObject $eppRepositoryMock;

    /** @var ScopeConfigInterface&MockObject */
    private MockObject $scopeConfigMock;

    /** @var HttpRequest&MockObject */
    private MockObject $requestMock;

    /** @var Renderer&MockObject */
    private MockObject $subjectMock;

    protected function setUp(): void
    {
        $this->detectorMock      = $this->createMock(ApplianceDetectorInterface::class);
        $this->eppRepositoryMock = $this->createMock(EppRepositoryInterface::class);
        $this->scopeConfigMock   = $this->createMock(ScopeConfigInterface::class);
        $this->requestMock       = $this->createMock(HttpRequest::class);
        $this->subjectMock       = $this->createMock(Renderer::class);

        $this->plugin = new RendererPlugin(
            $this->detectorMock,
            $this->eppRepositoryMock,
            $this->scopeConfigMock,
            $this->requestMock
        );
    }

    public function testAfterToHtmlReturnsEarlyWhenDisabled(): void
    {
        $this->scopeConfigMock->method('isSetFlag')->willReturn(false);

        $result = $this->plugin->afterToHtml($this->subjectMock, '<div>item</div>');

        $this->assertSame('<div>item</div>', $result);
    }

    public function testAfterToHtmlReturnsEarlyWhenNoItem(): void
    {
        $this->scopeConfigMock->method('isSetFlag')->willReturn(true);
        $this->subjectMock->method('getItem')->willReturn(null);

        $result = $this->plugin->afterToHtml($this->subjectMock, '<div>item</div>');

        $this->assertSame('<div>item</div>', $result);
    }

    public function testAfterToHtmlReturnsEarlyWhenNotAppliance(): void
    {
        $this->scopeConfigMock->method('isSetFlag')->willReturn(true);

        $itemMock = $this->createMock(Item::class);
        $this->subjectMock->method('getItem')->willReturn($itemMock);
        $this->detectorMock->method('isApplianceItem')->with($itemMock)->willReturn(false);

        $result = $this->plugin->afterToHtml($this->subjectMock, '<div>item</div>');

        $this->assertSame('<div>item</div>', $result);
    }

    public function testAfterToHtmlReturnsEarlyWhenNoPlans(): void
    {
        $this->scopeConfigMock->method('isSetFlag')->willReturn(true);

        $itemMock = $this->createMock(Item::class);
        $itemMock->method('getPrice')->willReturn(100.00);
        $this->subjectMock->method('getItem')->willReturn($itemMock);
        $this->detectorMock->method('isApplianceItem')->willReturn(true);
        $this->eppRepositoryMock->method('getApplicablePlansByPrice')->willReturn([]);

        $result = $this->plugin->afterToHtml($this->subjectMock, '<div>item</div>');

        $this->assertSame('<div>item</div>', $result);
    }

    public function testAfterToHtmlAppendsEppBlockWhenAllConditionsMet(): void
    {
        $this->scopeConfigMock->method('isSetFlag')->willReturn(true);
        $this->requestMock->method('getRouteName')->willReturn('checkout');

        $itemMock = $this->createMock(Item::class);
        $itemMock->method('getPrice')->willReturn(500.00);
        $this->subjectMock->method('getItem')->willReturn($itemMock);
        $this->detectorMock->method('isApplianceItem')->willReturn(true);
        $this->eppRepositoryMock->method('getApplicablePlansByPrice')->willReturn([new \stdClass()]);

        $planBlockMock = $this->getMockBuilder(EppItemPlan::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setTemplate', 'toHtml'])
            ->getMock();
        $planBlockMock->expects($this->once())->method('setTemplate')
            ->with('MageTips_Appliances::cart/epp_item_plan.phtml')
            ->willReturnSelf();
        $planBlockMock->method('toHtml')->willReturn('<div class="epp-plan">Plan</div>');

        $layoutMock = $this->createMock(LayoutInterface::class);
        $layoutMock->method('createBlock')->willReturn($planBlockMock);

        $this->subjectMock->method('getLayout')->willReturn($layoutMock);

        $result = $this->plugin->afterToHtml($this->subjectMock, '<div>item</div>');

        $this->assertStringContainsString('<div>item</div>', $result);
        $this->assertStringContainsString('epp-plan', $result);
    }

    public function testAfterToHtmlUsesCompactTemplateInMultishippingWhenPlanSelected(): void
    {
        $this->scopeConfigMock->method('isSetFlag')->willReturn(true);
        $this->requestMock->method('getRouteName')->willReturn('multishipping');

        $itemMock = $this->createMock(Item::class);
        $itemMock->method('getPrice')->willReturn(500.00);
        $this->subjectMock->method('getItem')->willReturn($itemMock);
        $this->detectorMock->method('isApplianceItem')->willReturn(true);
        $this->eppRepositoryMock->method('getApplicablePlansByPrice')->willReturn([new \stdClass()]);

        $planBlockMock = $this->getMockBuilder(EppItemPlan::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hasPlanSelected', 'setTemplate', 'toHtml'])
            ->getMock();
        $planBlockMock->method('hasPlanSelected')->willReturn(true);
        $planBlockMock->expects($this->once())->method('setTemplate')
            ->with('MageTips_Appliances::cart/epp_item_plan_compact.phtml')
            ->willReturnSelf();
        $planBlockMock->method('toHtml')->willReturn('<div class="appliances-epp-compact">Plan</div>');

        $layoutMock = $this->createMock(LayoutInterface::class);
        $layoutMock->method('createBlock')->willReturn($planBlockMock);
        $this->subjectMock->method('getLayout')->willReturn($layoutMock);

        $result = $this->plugin->afterToHtml($this->subjectMock, '<div>item</div>');

        $this->assertStringContainsString('appliances-epp-compact', $result);
    }

    public function testAfterToHtmlSkipsUnselectedItemInMultishipping(): void
    {
        $this->scopeConfigMock->method('isSetFlag')->willReturn(true);
        $this->requestMock->method('getRouteName')->willReturn('multishipping');

        $itemMock = $this->createMock(Item::class);
        $itemMock->method('getPrice')->willReturn(500.00);
        $this->subjectMock->method('getItem')->willReturn($itemMock);
        $this->detectorMock->method('isApplianceItem')->willReturn(true);
        $this->eppRepositoryMock->method('getApplicablePlansByPrice')->willReturn([new \stdClass()]);

        $planBlockMock = $this->getMockBuilder(EppItemPlan::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hasPlanSelected', 'setTemplate', 'toHtml'])
            ->getMock();
        $planBlockMock->method('hasPlanSelected')->willReturn(false);
        $planBlockMock->expects($this->never())->method('setTemplate');
        $planBlockMock->expects($this->never())->method('toHtml');

        $layoutMock = $this->createMock(LayoutInterface::class);
        $layoutMock->method('createBlock')->willReturn($planBlockMock);
        $this->subjectMock->method('getLayout')->willReturn($layoutMock);

        $result = $this->plugin->afterToHtml($this->subjectMock, '<div>item</div>');

        $this->assertSame('<div>item</div>', $result);
    }
}

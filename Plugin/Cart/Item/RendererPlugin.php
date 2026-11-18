<?php
/**
 * MageTips_Appliances Module
 *
 * Copyright © 2026 Muhammad Umar. All rights reserved.
 * Licensed under the Open Software License (OSL 3.0).
 */

declare(strict_types=1);

namespace MageTips\Appliances\Plugin\Cart\Item;

use MageTips\Appliances\Api\ApplianceDetectorInterface;
use MageTips\Appliances\Api\EppRepositoryInterface;
use MageTips\Appliances\Block\Cart\EppItemPlan;
use Magento\Checkout\Block\Cart\Item\Renderer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Store\Model\ScopeInterface;

/**
 * Injects a per-item protection-plan box after each appliance cart row.
 *
 * The same cart item renderer is reused by the multishipping "Ship to Multiple
 * Addresses" page (via the checkout_cart_item_renderers handle). There the table
 * is compact and read-only, so instead of the full interactive selector we render
 * a single read-only line — and only for items that already have a plan selected.
 */
class RendererPlugin
{
    private const XML_PATH_ENABLED = 'appliances/general/enabled';

    /** Route name of every multishipping checkout page. */
    private const MULTISHIPPING_ROUTE = 'multishipping';

    /**
     * @param ApplianceDetectorInterface $detector
     * @param EppRepositoryInterface $eppRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param HttpRequest $request
     */
    public function __construct(
        protected readonly ApplianceDetectorInterface $detector,
        protected readonly EppRepositoryInterface $eppRepository,
        protected readonly ScopeConfigInterface $scopeConfig,
        protected readonly HttpRequest $request
    ) {
    }

    /**
     * Append the EPP item plan block HTML after each appliance cart row.
     *
     * @param Renderer $subject
     * @param string $result
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterToHtml(Renderer $subject, string $result): string
    {
        if (!$this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE)) {
            return $result;
        }

        $item = $subject->getItem();
        if (!$item instanceof QuoteItem) {
            return $result;
        }

        if (!$this->detector->isApplianceItem($item)) {
            return $result;
        }

        $plans = $this->eppRepository->getApplicablePlansByPrice((float)$item->getPrice());
        if (empty($plans)) {
            return $result;
        }

        $isMultishipping = $this->request->getRouteName() === self::MULTISHIPPING_ROUTE;

        $planBlock = $subject->getLayout()->createBlock(
            EppItemPlan::class,
            '',
            ['data' => ['item' => $item]]
        );

        if ($isMultishipping) {
            // Read-only context: show a compact line only when a plan is selected.
            if (!$planBlock->hasPlanSelected()) {
                return $result;
            }
            $planBlock->setTemplate('MageTips_Appliances::cart/epp_item_plan_compact.phtml');
        } else {
            $planBlock->setTemplate('MageTips_Appliances::cart/epp_item_plan.phtml');
        }

        return $result . $planBlock->toHtml();
    }
}

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

namespace MageTips\Appliances\Block\Cart;

use MageTips\Appliances\Api\Data\EppInterface;
use MageTips\Appliances\Api\EppRepositoryInterface;
use MageTips\Appliances\Model\Quote\Total\EppCharge;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Store\Model\ScopeInterface;

/**
 * Per-cart-item protection plan box.
 *
 * One instance is created per appliance item by RendererPlugin::afterToHtml().
 * The item is injected via setItem() and all logic runs lazily on first access.
 *
 * @since 1.0.0
 */
class EppItemPlan extends Template
{
    private const XML_PATH_BOX_TITLE       = 'appliances/epp/protection_plan_box_title';
    private const XML_PATH_PROTECTION_ICON = 'appliances/epp/protection_icon';

    /** @var array|null */
    private ?array $plansCache = null;

    /**
     * @param Context $context
     * @param EppRepositoryInterface $eppRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        Context $context,
        protected readonly EppRepositoryInterface $eppRepository,
        protected readonly ScopeConfigInterface $scopeConfig,
        protected readonly PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    // ── Item access ─────────────────────────────────────────────────────────

    /**
     * Quote item assigned to this plan box.
     *
     * @return QuoteItem
     */
    public function getItem(): QuoteItem
    {
        return $this->getData('item');
    }

    // ── Plan data ────────────────────────────────────────────────────────────

    /**
     * Plans applicable to this item's price, sorted cheapest first.
     *
     * @return EppInterface[]
     */
    public function getPlans(): array
    {
        if ($this->plansCache !== null) {
            return $this->plansCache;
        }
        $plans = $this->eppRepository->getApplicablePlansByPrice((float)$this->getItem()->getPrice());
        usort($plans, static fn($a, $b) => $a->getRetailPrice() <=> $b->getRetailPrice());
        $this->plansCache = $plans;
        return $this->plansCache;
    }

    /**
     * Cheapest (minimum-years) plan — used as the auto-selected plan on ADD.
     *
     * @return EppInterface|null
     */
    public function getDefaultPlan(): ?EppInterface
    {
        return $this->getPlans()[0] ?? null;
    }

    /**
     * Plan code of the cheapest applicable plan, or empty string.
     *
     * @return string
     */
    public function getDefaultPlanCode(): string
    {
        return (string)($this->getDefaultPlan()?->getCode() ?? '');
    }

    /**
     * Current EPP plan code saved on this quote item, or '' if none.
     *
     * @return string
     */
    public function getCurrentCode(): string
    {
        $option = $this->getItem()->getOptionByCode(EppCharge::EPP_ITEM_OPTION_CODE);
        return $option ? (string)$option->getValue() : '';
    }

    /**
     * Full plan object for the currently selected plan, or null.
     *
     * @return EppInterface|null
     */
    public function getCurrentPlan(): ?EppInterface
    {
        $code = $this->getCurrentCode();
        if ($code === '') {
            return null;
        }

        foreach ($this->getPlans() as $plan) {
            if ($plan->getCode() === $code) {
                return $plan;
            }
        }

        return null;
    }

    /**
     * Check if this item currently has an EPP plan selected.
     *
     * @return bool
     */
    public function hasPlanSelected(): bool
    {
        return $this->getCurrentCode() !== '';
    }

    // ── Display helpers ──────────────────────────────────────────────────────

    /**
     * Configurable header title shown in the green bar above the plan box.
     *
     * Falls back to "Protection Plan and Installation Service".
     *
     * @return string
     */
    public function getBoxTitle(): string
    {
        $title = $this->scopeConfig->getValue(self::XML_PATH_BOX_TITLE, ScopeInterface::SCOPE_STORE);
        return $title ? (string)$title : (string)__('Protection Plan and Installation Service');
    }

    /**
     * "More Details" URL from the cheapest plan; empty string if not configured.
     *
     * @return string
     */
    public function getDetailsUrl(): string
    {
        foreach ($this->getPlans() as $plan) {
            $url = (string)($plan->getDetailsUrl() ?? '');
            if ($url !== '') {
                return $url;
            }
        }
        return '';
    }

    /**
     * URL for the saveEpp cart controller.
     *
     * @return string
     */
    public function getSaveUrl(): string
    {
        return $this->getUrl('appliances/cart/saveEpp');
    }

    /**
     * Absolute URL to the configured protection plan icon, or empty string.
     *
     * @return string
     */
    public function getProtectionIconUrl(): string
    {
        $file = $this->scopeConfig->getValue(self::XML_PATH_PROTECTION_ICON, ScopeInterface::SCOPE_STORE);
        if (!$file) {
            return '';
        }
        $mediaUrl = $this->_urlBuilder->getBaseUrl(['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA]);

        return $mediaUrl . 'appliances/epp/icon/' . ltrim((string)$file, '/');
    }

    /**
     * Format a price for display in the current store currency.
     *
     * @param float $price
     * @return string
     */
    public function formatPrice(float $price): string
    {
        return $this->priceCurrency->format($price, false);
    }
}

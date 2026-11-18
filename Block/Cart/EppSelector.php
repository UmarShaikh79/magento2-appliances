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

use MageTips\Appliances\Api\ApplianceDetectorInterface;
use MageTips\Appliances\Api\EppRepositoryInterface;
use MageTips\Appliances\Model\Quote\Total\EppCharge;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;

/**
 * Cart-page protection-plan summary bar + MANAGE modal data.
 *
 * Renders a compact bar above the cart items table with a MANAGE/ADD button
 * that opens the Kent.com-style modal for multi-item plan management.
 *
 * @since 1.0.0
 */
class EppSelector extends Template
{
    private const XML_PATH_MODAL_TITLE     = 'appliances/epp/modal_title';
    private const XML_PATH_PROTECTION_ICON = 'appliances/epp/protection_icon';

    /** @var array|null */
    private ?array $applianceItemsCache = null;

    /** @var array|null */
    private ?array $allPlansCache       = null;

    /**
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param ApplianceDetectorInterface $detector
     * @param EppRepositoryInterface $eppRepository
     * @param PriceCurrencyInterface $priceCurrency
     * @param ScopeConfigInterface $scopeConfig
     * @param ImageHelper $imageHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        protected readonly CheckoutSession $checkoutSession,
        protected readonly ApplianceDetectorInterface $detector,
        protected readonly EppRepositoryInterface $eppRepository,
        protected readonly PriceCurrencyInterface $priceCurrency,
        protected readonly ScopeConfigInterface $scopeConfig,
        protected readonly ImageHelper $imageHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    // ─── Quote helpers ───────────────────────────────────────────────────────

    /**
     * All visible appliance items that have at least one applicable EPP plan.
     *
     * @return list<array{item_id,name,sku,price,qty,current_epp_code,image_url}>
     */
    public function getApplianceItems(): array
    {
        if ($this->applianceItemsCache !== null) {
            return $this->applianceItemsCache;
        }

        $this->applianceItemsCache = [];
        $quote = $this->checkoutSession->getQuote();

        foreach ($quote->getAllVisibleItems() as $item) {
            if (!$this->detector->isApplianceItem($item)) {
                continue;
            }

            $itemPrice = (float)$item->getPrice();
            $plans     = $this->eppRepository->getApplicablePlansByPrice($itemPrice);

            if (empty($plans)) {
                continue;
            }

            $currentCode = '';
            $option = $item->getOptionByCode(EppCharge::EPP_ITEM_OPTION_CODE);
            if ($option && $option->getValue()) {
                $currentCode = (string)$option->getValue();
            }

            // Product thumbnail for modal item rows
            $imageUrl = '';
            try {
                $product  = $item->getProduct();
                $imageUrl = $this->imageHelper
                    ->init($product, 'cart_page_product_thumbnail')
                    ->getUrl();
            } catch (\Exception $e) {
                $imageUrl = '';
            }

            $this->applianceItemsCache[] = [
                'item_id'          => (int)$item->getId(),
                'name'             => (string)$item->getName(),
                'sku'              => (string)$item->getSku(),
                'price'            => $itemPrice,
                'qty'              => (float)$item->getQty(),
                'current_epp_code' => $currentCode,
                'image_url'        => $imageUrl,
            ];
        }

        return $this->applianceItemsCache;
    }

    /**
     * Appliance items that have an EPP plan code set.
     *
     * @return array
     */
    public function getCoveredItems(): array
    {
        return array_values(array_filter(
            $this->getApplianceItems(),
            static fn($i) => $i['current_epp_code'] !== ''
        ));
    }

    /**
     * Check if the cart contains any appliance items with applicable plans.
     *
     * @return bool
     */
    public function hasApplianceItems(): bool
    {
        return !empty($this->getApplianceItems());
    }

    /**
     * Check if any appliance item has an EPP plan selected.
     *
     * @return bool
     */
    public function hasPlanSelected(): bool
    {
        return !empty($this->getCoveredItems());
    }

    // ─── Plan helpers ────────────────────────────────────────────────────────

    /**
     * Union of applicable plans across all appliance items, cheapest first.
     *
     * @return list<array{code,name,retail_price,years,details_url}>
     */
    public function getAllPlans(): array
    {
        if ($this->allPlansCache !== null) {
            return $this->allPlansCache;
        }

        $seen  = [];
        $plans = [];

        foreach ($this->getApplianceItems() as $item) {
            foreach ($this->eppRepository->getApplicablePlansByPrice($item['price']) as $epp) {
                $code = (string)$epp->getCode();
                if (!isset($seen[$code])) {
                    $seen[$code] = true;
                    $plans[]     = [
                        'code'         => $code,
                        'name'         => (string)$epp->getName(),
                        'retail_price' => (float)$epp->getRetailPrice(),
                        'years'        => (int)$epp->getYears(),
                        'details_url'  => (string)($epp->getDetailsUrl() ?? ''),
                    ];
                }
            }
        }

        usort($plans, static fn($a, $b) => $a['retail_price'] <=> $b['retail_price']);

        $this->allPlansCache = $plans;
        return $this->allPlansCache;
    }

    /**
     * Plan code of the first covered item, or empty string if none.
     *
     * @return string
     */
    public function getSelectedPlanCode(): string
    {
        foreach ($this->getCoveredItems() as $item) {
            return $item['current_epp_code'];
        }
        return '';
    }

    /**
     * Plan data array for the currently selected plan, or null if none.
     *
     * @return array|null
     */
    public function getSelectedPlan(): ?array
    {
        $code = $this->getSelectedPlanCode();
        if ($code === '') {
            return null;
        }
        foreach ($this->getAllPlans() as $plan) {
            if ($plan['code'] === $code) {
                return $plan;
            }
        }
        return null;
    }

    /**
     * Total EPP cost across all covered items.
     *
     * @return float
     */
    public function getTotalEppCost(): float
    {
        $plan = $this->getSelectedPlan();
        if ($plan === null) {
            return 0.0;
        }
        return $plan['retail_price'] * count($this->getCoveredItems());
    }

    /**
     * Applicable plans for a single item price, sorted cheapest first.
     *
     * @param float $price
     * @return list<array{code,name,retail_price,years}>
     */
    public function getItemPlans(float $price): array
    {
        $plans = $this->eppRepository->getApplicablePlansByPrice($price);
        usort($plans, static fn($a, $b) => $a->getRetailPrice() <=> $b->getRetailPrice());
        return array_map(static fn($epp) => [
            'code'         => (string)$epp->getCode(),
            'name'         => (string)$epp->getName(),
            'retail_price' => (float)$epp->getRetailPrice(),
            'years'        => (int)$epp->getYears(),
        ], $plans);
    }

    /**
     * Details URL from the first plan that has one configured.
     *
     * @return string
     */
    public function getDetailsUrl(): string
    {
        foreach ($this->getAllPlans() as $plan) {
            if ($plan['details_url'] !== '') {
                return $plan['details_url'];
            }
        }
        return '';
    }

    // ─── Config / URL helpers ─────────────────────────────────────────────

    /**
     * Title shown in the mage/modal header popup.
     *
     * @return string
     */
    public function getModalTitle(): string
    {
        $title = $this->scopeConfig->getValue(self::XML_PATH_MODAL_TITLE, ScopeInterface::SCOPE_STORE);
        return $title ? (string)$title : (string)__('Protection Plan');
    }

    /**
     * Current store currency symbol.
     *
     * @return string
     */
    public function getCurrencySymbol(): string
    {
        return $this->priceCurrency->getCurrencySymbol() ?: '$';
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
     * URL for the updateEpp cart controller.
     *
     * @return string
     */
    public function getUpdateUrl(): string
    {
        return $this->getUrl('appliances/cart/updateEpp');
    }
}

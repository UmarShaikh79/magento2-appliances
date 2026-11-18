<?php
/**
 * MageTips_Appliances Module
 *
 * Copyright © 2026 Muhammad Umar. All rights reserved.
 * Licensed under the Open Software License (OSL 3.0).
 */

declare(strict_types=1);

namespace MageTips\Appliances\ViewModel\Product;

use MageTips\Appliances\Model\Config\Delivery as DeliveryConfig;
use MageTips\Appliances\Model\Delivery\Calculator;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * View model for the delivery date widget on the product page.
 *
 * @since 1.0.0
 */
class DeliveryDate implements ArgumentInterface
{
    /**
     * @param ProductRepositoryInterface $productRepository
     * @param RequestInterface $request
     * @param DeliveryConfig $config
     * @param Calculator $calculator
     * @param Json $json
     */
    public function __construct(
        protected readonly ProductRepositoryInterface $productRepository,
        protected readonly RequestInterface $request,
        protected readonly DeliveryConfig $config,
        protected readonly Calculator $calculator,
        protected readonly Json $json
    ) {
    }

    /**
     * Check whether the delivery date widget is enabled in config.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    /**
     * Check whether the current product is marked as an appliance.
     *
     * @return bool
     */
    public function isAppliance(): bool
    {
        $product = $this->getProduct();
        return $product !== null && (bool)$product->getData('is_appliance');
    }

    /**
     * Return the current product from the request, or null if not resolvable.
     *
     * On a product view page the product is already in the repository's
     * per-request identity map, so this does not trigger an extra DB query.
     *
     * @return ProductInterface|null
     */
    public function getProduct(): ?ProductInterface
    {
        $productId = (int)$this->request->getParam('id');
        if ($productId <= 0) {
            return null;
        }

        try {
            return $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Return the headline string with the calculated earliest delivery date.
     *
     * @return string
     */
    public function getHeadline(): string
    {
        return $this->calculator->getHeadline();
    }

    /**
     * Return the configured subtext displayed below the delivery date headline.
     *
     * @return string
     */
    public function getSubtext(): string
    {
        return $this->config->getSubtext();
    }

    /**
     * Return the datepicker configuration as a JSON string for use in JS.
     *
     * @return string
     */
    public function getPickerConfigJson(): string
    {
        return $this->json->serialize($this->calculator->getPickerData());
    }
}

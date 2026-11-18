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

namespace MageTips\Appliances\Model\Appliance;

use MageTips\Appliances\Api\ApplianceDetectorInterface;
use MageTips\Appliances\Setup\Patch\Data\AddIsApplianceAttribute;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Psr\Log\LoggerInterface;

/**
 * Default appliance detector — reads the is_appliance product attribute.
 *
 * Results are cached per product ID for the lifetime of the request so
 * repeated calls (totals, shipping, payment) pay only one DB lookup per product.
 *
 * @since 1.0.0
 */
class Detector implements ApplianceDetectorInterface
{
    /** @var ProductRepositoryInterface */
    private ProductRepositoryInterface $productRepository;

    /** @var LoggerInterface */
    private LoggerInterface $logger;

    /** @var array<int, bool> */
    private array $cache = [];

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->logger            = $logger;
    }

    /**
     * @inheritdoc
     */
    public function isApplianceProduct(int $productId): bool
    {
        if (!isset($this->cache[$productId])) {
            try {
                $product = $this->productRepository->getById($productId);
                $this->cache[$productId] = (bool)$product->getData(
                    AddIsApplianceAttribute::ATTRIBUTE_CODE
                );
            } catch (NoSuchEntityException $e) {
                $this->logger->warning(
                    sprintf('ApplianceDetector: product %d not found — %s', $productId, $e->getMessage())
                );
                $this->cache[$productId] = false;
            }
        }

        return $this->cache[$productId];
    }

    /**
     * @inheritdoc
     */
    public function isApplianceItem(AbstractItem $item): bool
    {
        return $this->isApplianceProduct((int)$item->getProductId());
    }

    /**
     * @inheritdoc
     */
    public function hasAppliances(Quote $quote): bool
    {
        foreach ($quote->getAllVisibleItems() as $item) {
            if ($this->isApplianceItem($item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function getApplianceItems(Quote $quote): array
    {
        $applianceItems = [];

        foreach ($quote->getAllVisibleItems() as $item) {
            if ($this->isApplianceItem($item)) {
                $applianceItems[] = $item;
            }
        }

        return $applianceItems;
    }
}

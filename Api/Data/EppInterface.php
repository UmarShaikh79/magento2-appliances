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

namespace MageTips\Appliances\Api\Data;

/**
 * EPP Data Interface
 *
 * @api
 * @since 1.0.0
 */
interface EppInterface
{
    public const TABLE_NAME      = 'appliances_epp';
    public const ENTITY_ID       = 'entity_id';
    public const CODE            = 'code';
    public const NAME            = 'name';
    public const DESCRIPTION     = 'description';
    public const MIN_PRICE       = 'min_price';
    public const MAX_PRICE       = 'max_price';
    public const RETAIL_PRICE    = 'retail_price';
    public const YEARS           = 'years';
    public const DETAILS_URL     = 'details_url';
    public const DETAILS_PDF_PATH = 'details_pdf_path';
    public const ENABLED         = 'enabled';
    public const CREATED_AT      = 'created_at';
    public const UPDATED_AT      = 'updated_at';

    /**
     * Get entity ID
     *
     * @return int|null
     */
    public function getEntityId(): ?int;

    /**
     * Get unique EPP code (e.g. EPP_2YR_STD)
     *
     * @return string|null
     */
    public function getCode(): ?string;

    /**
     * Set unique EPP code
     *
     * @param string $code
     * @return self
     */
    public function setCode(string $code): self;

    /**
     * Get display name
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Set display name
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name): self;

    /**
     * Get plan description
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Set plan description
     *
     * @param string $description
     * @return self
     */
    public function setDescription(string $description): self;

    /**
     * Get minimum product price this plan applies to
     *
     * @return float|null
     */
    public function getMinPrice(): ?float;

    /**
     * Set minimum product price
     *
     * @param float $minPrice
     * @return self
     */
    public function setMinPrice(float $minPrice): self;

    /**
     * Get maximum product price this plan applies to
     *
     * @return float|null
     */
    public function getMaxPrice(): ?float;

    /**
     * Set maximum product price
     *
     * @param float $maxPrice
     * @return self
     */
    public function setMaxPrice(float $maxPrice): self;

    /**
     * Get retail price charged to the customer for this plan
     *
     * @return float|null
     */
    public function getRetailPrice(): ?float;

    /**
     * Set retail price
     *
     * @param float $retailPrice
     * @return self
     */
    public function setRetailPrice(float $retailPrice): self;

    /**
     * Get coverage duration in years
     *
     * @return int|null
     */
    public function getYears(): ?int;

    /**
     * Set coverage duration in years
     *
     * @param int $years
     * @return self
     */
    public function setYears(int $years): self;

    /**
     * Get URL to the plan details page
     *
     * @return string|null
     */
    public function getDetailsUrl(): ?string;

    /**
     * Set URL to the plan details page
     *
     * @param string $detailsUrl
     * @return self
     */
    public function setDetailsUrl(string $detailsUrl): self;

    /**
     * Get path to the plan details PDF (relative to media directory)
     *
     * @return string|null
     */
    public function getDetailsPdfPath(): ?string;

    /**
     * Set path to the plan details PDF
     *
     * @param string $path
     * @return self
     */
    public function setDetailsPdfPath(string $path): self;

    /**
     * Check whether this plan is active
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * Enable or disable this plan
     *
     * @param bool $enabled
     * @return self
     */
    public function setEnabled(bool $enabled): self;

    /**
     * Get record creation timestamp
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Get record last-updated timestamp
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;
}

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

namespace MageTips\Appliances\Model;

use MageTips\Appliances\Api\Data\EppInterface;
use MageTips\Appliances\Model\ResourceModel\Epp as EppResourceModel;
use Magento\Framework\Model\AbstractModel;

/**
 * EPP Model
 *
 * @api
 * @since 1.0.0
 */
class Epp extends AbstractModel implements EppInterface
{
    public const CACHE_TAG = 'magetips_appliances_epp';

    /** @var string */
    protected $_cacheTag    = self::CACHE_TAG;

    /** @var string */
    protected $_eventPrefix = 'magetips_appliances_epp';

    /**
     * Initialize resource model.
     */
    protected function _construct(): void
    {
        $this->_init(EppResourceModel::class);
    }

    /**
     * @inheritdoc
     */
    public function getEntityId(): ?int
    {
        $id = $this->getData(EppInterface::ENTITY_ID);
        return $id !== null ? (int)$id : null;
    }

    /**
     * @inheritdoc
     */
    public function getCode(): ?string
    {
        return $this->getData(EppInterface::CODE);
    }

    /**
     * @inheritdoc
     */
    public function setCode(string $code): self
    {
        return $this->setData(EppInterface::CODE, $code);
    }

    /**
     * @inheritdoc
     */
    public function getName(): ?string
    {
        return $this->getData(EppInterface::NAME);
    }

    /**
     * @inheritdoc
     */
    public function setName(string $name): self
    {
        return $this->setData(EppInterface::NAME, $name);
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): ?string
    {
        return $this->getData(EppInterface::DESCRIPTION);
    }

    /**
     * @inheritdoc
     */
    public function setDescription(string $description): self
    {
        return $this->setData(EppInterface::DESCRIPTION, $description);
    }

    /**
     * @inheritdoc
     */
    public function getMinPrice(): ?float
    {
        $val = $this->getData(EppInterface::MIN_PRICE);
        return $val !== null ? (float)$val : null;
    }

    /**
     * @inheritdoc
     */
    public function setMinPrice(float $minPrice): self
    {
        return $this->setData(EppInterface::MIN_PRICE, $minPrice);
    }

    /**
     * @inheritdoc
     */
    public function getMaxPrice(): ?float
    {
        $val = $this->getData(EppInterface::MAX_PRICE);
        return $val !== null ? (float)$val : null;
    }

    /**
     * @inheritdoc
     */
    public function setMaxPrice(float $maxPrice): self
    {
        return $this->setData(EppInterface::MAX_PRICE, $maxPrice);
    }

    /**
     * @inheritdoc
     */
    public function getRetailPrice(): ?float
    {
        $val = $this->getData(EppInterface::RETAIL_PRICE);
        return $val !== null ? (float)$val : null;
    }

    /**
     * @inheritdoc
     */
    public function setRetailPrice(float $retailPrice): self
    {
        return $this->setData(EppInterface::RETAIL_PRICE, $retailPrice);
    }

    /**
     * @inheritdoc
     */
    public function getYears(): ?int
    {
        $val = $this->getData(EppInterface::YEARS);
        return $val !== null ? (int)$val : null;
    }

    /**
     * @inheritdoc
     */
    public function setYears(int $years): self
    {
        return $this->setData(EppInterface::YEARS, $years);
    }

    /**
     * @inheritdoc
     */
    public function getDetailsUrl(): ?string
    {
        return $this->getData(EppInterface::DETAILS_URL);
    }

    /**
     * @inheritdoc
     */
    public function setDetailsUrl(string $detailsUrl): self
    {
        return $this->setData(EppInterface::DETAILS_URL, $detailsUrl);
    }

    /**
     * @inheritdoc
     */
    public function getDetailsPdfPath(): ?string
    {
        return $this->getData(EppInterface::DETAILS_PDF_PATH);
    }

    /**
     * @inheritdoc
     */
    public function setDetailsPdfPath(string $path): self
    {
        return $this->setData(EppInterface::DETAILS_PDF_PATH, $path);
    }

    /**
     * @inheritdoc
     */
    public function isEnabled(): bool
    {
        return (bool)$this->getData(EppInterface::ENABLED);
    }

    /**
     * @inheritdoc
     */
    public function setEnabled(bool $enabled): self
    {
        return $this->setData(EppInterface::ENABLED, (int)$enabled);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(EppInterface::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData(EppInterface::UPDATED_AT);
    }
}

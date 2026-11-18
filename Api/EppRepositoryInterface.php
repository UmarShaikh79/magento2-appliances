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

namespace MageTips\Appliances\Api;

use MageTips\Appliances\Api\Data\EppInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface EppRepositoryInterface
{
    /**
     * Get EPP by ID
     *
     * @param int $id
     * @return EppInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $id): EppInterface;

    /**
     * Get EPP by code
     *
     * @param string $code
     * @return EppInterface
     * @throws NoSuchEntityException
     */
    public function getByCode(string $code): EppInterface;

    /**
     * Save EPP
     *
     * @param EppInterface $epp
     * @return EppInterface
     * @throws LocalizedException
     */
    public function save(EppInterface $epp): EppInterface;

    /**
     * Delete EPP
     *
     * @param EppInterface $epp
     * @return bool
     * @throws LocalizedException
     */
    public function delete(EppInterface $epp): bool;

    /**
     * Get all EPP records
     *
     * @return EppInterface[]
     */
    public function getList(): array;

    /**
     * Get enabled EPP records
     *
     * @return EppInterface[]
     */
    public function getEnabledList(): array;

    /**
     * Get applicable plans by price
     *
     * @param float $productPrice
     * @return EppInterface[]
     */
    public function getApplicablePlansByPrice(float $productPrice): array;
}

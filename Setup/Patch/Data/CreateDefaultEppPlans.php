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

namespace MageTips\Appliances\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use MageTips\Appliances\Model\EppFactory;
use MageTips\Appliances\Model\ResourceModel\Epp as EppResourceModel;

/**
 * Create Default EPP Plans Patch
 *
 * @since 1.0.0
 */
class CreateDefaultEppPlans implements DataPatchInterface
{
    /** @var EppFactory */
    private EppFactory $eppFactory;

    /** @var EppResourceModel */
    private EppResourceModel $eppResource;

    /**
     * @param EppFactory $eppFactory
     * @param EppResourceModel $eppResource
     */
    public function __construct(
        EppFactory $eppFactory,
        EppResourceModel $eppResource
    ) {
        $this->eppFactory = $eppFactory;
        $this->eppResource = $eppResource;
    }

    /**
     * @inheritdoc
     */
    public function apply(): void
    {
        $defaultPlans = [
            [
                'code' => 'EPP_2YR_STD',
                'name' => '2-Year Standard Protection',
                'description' => 'Covers manufacturing defects for 2 years',
                'min_price' => 100,
                'max_price' => 5000,
                'retail_price' => 199,
                'years' => 2,
                'enabled' => 1,
            ],
            [
                'code' => 'EPP_3YR_PLU',
                'name' => '3-Year Plus Protection',
                'description' => 'Covers manufacturing defects and accidental damage',
                'min_price' => 100,
                'max_price' => 10000,
                'retail_price' => 399,
                'years' => 3,
                'enabled' => 1,
            ],
        ];

        foreach ($defaultPlans as $planData) {
            $epp = $this->eppFactory->create();
            $this->eppResource->load($epp, $planData['code'], 'code');

            if (!$epp->getId()) {
                $epp->setData($planData);
                $this->eppResource->save($epp);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}

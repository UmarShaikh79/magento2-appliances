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

namespace MageTips\Appliances\Model\ResourceModel\Epp;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use MageTips\Appliances\Model\Epp;
use MageTips\Appliances\Model\ResourceModel\Epp as EppResourceModel;

/**
 * EPP Collection
 *
 * @since 1.0.0
 */
class Collection extends AbstractCollection
{
    /** @var string */
    protected $_idFieldName = 'entity_id';

    /** @var string */
    protected $_eventPrefix = 'magetips_appliances_epp_collection';

    /** @var string */
    protected $_eventObject = 'epp_collection';

    /**
     * Initialize collection model.
     */
    protected function _construct()
    {
        $this->_init(Epp::class, EppResourceModel::class);
    }

    /**
     * Filter collection to enabled EPP plans only.
     *
     * @return self
     */
    public function addEnabledFilter(): self
    {
        $this->addFieldToFilter('enabled', 1);
        return $this;
    }

    /**
     * Filter collection to plans whose price range covers the given product price.
     *
     * @param float $productPrice
     * @return self
     */
    public function addPriceRangeFilter(float $productPrice): self
    {
        $this->addFieldToFilter('min_price', ['lteq' => $productPrice]);
        $this->addFieldToFilter('max_price', ['gteq' => $productPrice]);
        return $this;
    }

    /**
     * Filter collection to the plan with the given code.
     *
     * @param string $code
     * @return self
     */
    public function addCodeFilter(string $code): self
    {
        $this->addFieldToFilter('code', $code);
        return $this;
    }
}

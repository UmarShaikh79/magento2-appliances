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

namespace MageTips\Appliances\Block\Adminhtml\Epp\Edit;

use Magento\Backend\Block\Widget\Context;

/**
 * Base button class for EPP form toolbar buttons
 *
 * @since 1.0.0
 */
class GenericButton
{
    /**
     * @param Context $context
     */
    public function __construct(
        protected Context $context
    ) {
    }

    /**
     * Return the current EPP entity ID from the request, or null for new records.
     *
     * @return int|null
     */
    public function getEppId(): ?int
    {
        $id = (int)$this->context->getRequest()->getParam('entity_id');
        return $id ?: null;
    }

    /**
     * Generate a backend URL for the given route and parameters.
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}

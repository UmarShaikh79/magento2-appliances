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

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Back button for EPP form
 *
 * @since 1.0.0
 */
class BackButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * Return the Back button configuration array.
     *
     * @return array
     */
    public function getButtonData(): array
    {
        return [
            'label'    => __('Back'),
            'on_click' => sprintf("location.href = '%s';", $this->getUrl('*/*/')),
            'class'    => 'back',
            'sort_order' => 10,
        ];
    }
}

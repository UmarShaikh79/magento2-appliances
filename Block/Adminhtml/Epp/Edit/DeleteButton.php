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
 * Delete button for EPP form (only shown on edit)
 *
 * @since 1.0.0
 */
class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * Return the Delete button configuration array.
     *
     * @return array
     */
    public function getButtonData(): array
    {
        $eppId = $this->getEppId();
        if (!$eppId) {
            return [];
        }

        $deleteUrl = $this->getUrl('*/*/delete', ['entity_id' => $eppId]);

        return [
            'label'    => __('Delete'),
            'class'    => 'delete',
            'on_click' => "deleteConfirm('" . __('Are you sure you want to delete this EPP?') . "', '$deleteUrl')",
            'sort_order' => 20,
        ];
    }
}

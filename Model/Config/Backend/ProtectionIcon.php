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

namespace MageTips\Appliances\Model\Config\Backend;

use Magento\Config\Model\Config\Backend\Image;

/**
 * Backend model for the Protection Plan icon upload field.
 * Extends the standard Image backend to also allow SVG files.
 */
class ProtectionIcon extends Image
{
    /**
     * @inheritdoc
     */
    protected function _getAllowedExtensions(): array
    {
        return ['jpg', 'jpeg', 'gif', 'png', 'svg', 'webp'];
    }
}

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

namespace MageTips\Appliances\Test\Unit\Model\Config\Backend;

use MageTips\Appliances\Model\Config\Backend\ProtectionIcon;
use PHPUnit\Framework\TestCase;

class ProtectionIconTest extends TestCase
{
    public function testGetAllowedExtensionsIncludesSvg(): void
    {
        $model = $this->getMockBuilder(ProtectionIcon::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $reflection = new \ReflectionMethod($model, '_getAllowedExtensions');
        $reflection->setAccessible(true);
        $extensions = $reflection->invoke($model);

        $this->assertContains('svg', $extensions);
        $this->assertContains('png', $extensions);
        $this->assertContains('jpg', $extensions);
        $this->assertContains('webp', $extensions);
    }
}

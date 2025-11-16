<?php
/**
 * Product Configurator - Add Gold Color Attributes
 *
 * Creates product attributes for managing gold color variants:
 * - gold_color: The specific gold color (white, yellow, rose, black)
 * - gold_variant_group: Group ID to link color variants together
 *
 * @category  ElielWeb
 * @package   ElielWeb_ProductConfigurator
 * @author    Elie <elie@redline.paris>
 * @copyright Copyright (c) 2025 RedLine
 */

declare(strict_types=1);

namespace ElielWeb\ProductConfigurator\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddGoldColorAttributes implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        // 1. Create 'gold_color' attribute (dropdown with specific colors)
        $eavSetup->addAttribute(
            Product::ENTITY,
            'gold_color',
            [
                'type' => 'varchar',
                'label' => 'Gold Color',
                'input' => 'select',
                'source' => \Magento\Eav\Model\Entity\Attribute\Source\Table::class,
                'required' => false,
                'sort_order' => 100,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'user_defined' => true,
                'searchable' => true,
                'filterable' => true,
                'comparable' => true,
                'visible_on_front' => true,
                'used_in_product_listing' => true,
                'unique' => false,
                'apply_to' => 'simple',
                'group' => 'Product Details',
                'option' => [
                    'values' => [
                        'Or Blanc',
                        'Or Jaune',
                        'Or Rose',
                        'Or Noir'
                    ]
                ]
            ]
        );

        // 2. Create 'gold_variant_group' attribute (text field for grouping variants)
        $eavSetup->addAttribute(
            Product::ENTITY,
            'gold_variant_group',
            [
                'type' => 'varchar',
                'label' => 'Gold Variant Group',
                'input' => 'text',
                'required' => false,
                'sort_order' => 101,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'user_defined' => true,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'used_in_product_listing' => true,
                'unique' => false,
                'apply_to' => 'simple',
                'group' => 'Product Details',
                'note' => 'Common identifier to link gold color variants (e.g., PURE-FIL, MISS-FIL)'
            ]
        );

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}

<?php
/**
 * Product Configurator - Gold Color Selector ViewModel
 *
 * Manages gold color variants selection for jewelry products
 *
 * @category  ElielWeb
 * @package   ElielWeb_ProductConfigurator
 * @author    Elie <elie@redline.paris>
 * @copyright Copyright (c) 2025 RedLine
 */

declare(strict_types=1);

namespace ElielWeb\ProductConfigurator\ViewModel;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;

class GoldColorSelector implements ArgumentInterface
{
    /**
     * Gold color hex codes mapping
     */
    private const GOLD_COLORS = [
        'Or Blanc' => [
            'hex' => '#E8E8E8',
            'code' => 'white',
            'label_fr' => 'Or Blanc',
            'label_en' => 'White Gold'
        ],
        'Or Jaune' => [
            'hex' => '#FFD700',
            'code' => 'yellow',
            'label_fr' => 'Or Jaune',
            'label_en' => 'Yellow Gold'
        ],
        'Or Rose' => [
            'hex' => '#ECC5C0',
            'code' => 'rose',
            'label_fr' => 'Or Rose',
            'label_en' => 'Rose Gold'
        ],
        'Or Noir' => [
            'hex' => '#2C2C2C',
            'code' => 'black',
            'label_fr' => 'Or Noir',
            'label_en' => 'Black Gold'
        ]
    ];

    public function __construct(
        private readonly CollectionFactory $productCollectionFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Get all gold color variants for a given product
     *
     * @param ProductInterface $product
     * @return array
     */
    public function getGoldVariants(ProductInterface $product): array
    {
        $variantGroup = $product->getData('gold_variant_group');

        if (empty($variantGroup)) {
            return [];
        }

        try {
            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToSelect(['gold_color', 'gold_variant_group', 'name', 'url_key'])
                ->addAttributeToFilter('gold_variant_group', $variantGroup)
                ->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
                ->addStoreFilter($this->storeManager->getStore()->getId());

            $variants = [];
            foreach ($collection as $variant) {
                $goldColor = $variant->getAttributeText('gold_color') ?: $variant->getData('gold_color');

                if (!empty($goldColor) && isset(self::GOLD_COLORS[$goldColor])) {
                    $variants[] = [
                        'product_id' => $variant->getId(),
                        'sku' => $variant->getSku(),
                        'name' => $variant->getName(),
                        'gold_color' => $goldColor,
                        'gold_color_code' => self::GOLD_COLORS[$goldColor]['code'],
                        'hex_code' => self::GOLD_COLORS[$goldColor]['hex'],
                        'label_fr' => self::GOLD_COLORS[$goldColor]['label_fr'],
                        'label_en' => self::GOLD_COLORS[$goldColor]['label_en'],
                        'url' => $variant->getProductUrl(),
                        'is_current' => $variant->getId() == $product->getId()
                    ];
                }
            }

            // Sort by gold color order (white, yellow, rose, black)
            $colorOrder = ['Or Blanc', 'Or Jaune', 'Or Rose', 'Or Noir'];
            usort($variants, function ($a, $b) use ($colorOrder) {
                $posA = array_search($a['gold_color'], $colorOrder);
                $posB = array_search($b['gold_color'], $colorOrder);
                return $posA <=> $posB;
            });

            return $variants;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check if product has gold color variants
     *
     * @param ProductInterface $product
     * @return bool
     */
    public function hasGoldVariants(ProductInterface $product): bool
    {
        return !empty($product->getData('gold_variant_group'));
    }

    /**
     * Get current product gold color
     *
     * @param ProductInterface $product
     * @return string|null
     */
    public function getCurrentGoldColor(ProductInterface $product): ?string
    {
        return $product->getAttributeText('gold_color') ?: $product->getData('gold_color');
    }

    /**
     * Get gold color data by name
     *
     * @param string $colorName
     * @return array|null
     */
    public function getGoldColorData(string $colorName): ?array
    {
        return self::GOLD_COLORS[$colorName] ?? null;
    }

    /**
     * Get all available gold colors
     *
     * @return array
     */
    public function getAllGoldColors(): array
    {
        return self::GOLD_COLORS;
    }

    /**
     * Get variant by gold color
     *
     * @param ProductInterface $product
     * @param string $goldColor
     * @return array|null
     */
    public function getVariantByColor(ProductInterface $product, string $goldColor): ?array
    {
        $variants = $this->getGoldVariants($product);

        foreach ($variants as $variant) {
            if ($variant['gold_color'] === $goldColor) {
                return $variant;
            }
        }

        return null;
    }
}

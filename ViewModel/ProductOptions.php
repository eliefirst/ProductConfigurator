<?php
/**
 * Product Configurator - Product Options ViewModel for Hyva
 *
 * Provides optimized data access for product custom options in Hyva theme
 *
 * @category  ElielWeb
 * @package   ElielWeb_ProductConfigurator
 * @author    Elie <elie@redline.paris>
 * @copyright Copyright (c) 2025 RedLine
 */

declare(strict_types=1);

namespace ElielWeb\ProductConfigurator\ViewModel;

use ElielWeb\ProductConfigurator\Model\OptionMapper;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Option;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;

class ProductOptions implements ArgumentInterface
{
    public function __construct(
        private readonly OptionMapper $optionMapper,
        private readonly PriceHelper $priceHelper,
        private readonly SerializerInterface $serializer,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Get formatted product options for Alpine.js
     *
     * @param ProductInterface $product
     * @return array
     */
    public function getOptionsData(ProductInterface $product): array
    {
        if (!$product->getHasOptions()) {
            return [];
        }

        $options = $product->getOptions() ?? [];
        $formattedOptions = [];

        foreach ($options as $option) {
            $formattedOption = $this->formatOption($option, $product);
            if ($formattedOption) {
                $formattedOptions[] = $formattedOption;
            }
        }

        return $formattedOptions;
    }

    /**
     * Format single option for frontend
     *
     * @param Option $option
     * @param ProductInterface $product
     * @return array|null
     */
    private function formatOption(Option $option, ProductInterface $product): ?array
    {
        $additionalData = $this->parseAdditionalData($option->getAdditionalData());

        $formattedOption = [
            'id' => $option->getOptionId(),
            'title' => $option->getTitle(),
            'type' => $option->getType(),
            'is_require' => (bool)$option->getIsRequire(),
            'sort_order' => $option->getSortOrder(),
            'price' => $option->getPrice(),
            'price_type' => $option->getPriceType(),
        ];

        // Add Aitoc metadata if available
        if (!empty($additionalData)) {
            $formattedOption['aitoc_flags'] = $this->optionMapper->extractAitocFlags(
                $option->getAdditionalData()
            );
            $formattedOption['migrated_from_aitoc'] = $additionalData['aitoc_migrated'] ?? false;
        }

        // Add values for select-type options
        if ($this->isSelectType($option->getType())) {
            $formattedOption['values'] = $this->formatOptionValues($option);
        }

        // Add specific config for different types
        $formattedOption = array_merge(
            $formattedOption,
            $this->getTypeSpecificConfig($option)
        );

        return $formattedOption;
    }

    /**
     * Format option values
     *
     * @param Option $option
     * @return array
     */
    private function formatOptionValues(Option $option): array
    {
        $values = $option->getValues() ?? [];
        $formattedValues = [];

        foreach ($values as $value) {
            $formattedValues[] = [
                'id' => $value->getOptionTypeId(),
                'title' => $value->getTitle(),
                'price' => (float)$value->getPrice(),
                'price_type' => $value->getPriceType(),
                'sku' => $value->getSku(),
                'sort_order' => $value->getSortOrder(),
                'formatted_price' => $this->formatPrice($value->getPrice(), $value->getPriceType()),
            ];
        }

        return $formattedValues;
    }

    /**
     * Get type-specific configuration
     *
     * @param Option $option
     * @return array
     */
    private function getTypeSpecificConfig(Option $option): array
    {
        $config = [];

        switch ($option->getType()) {
            case Option::OPTION_TYPE_FILE:
                $config['file_extension'] = $option->getFileExtension();
                $config['image_size_x'] = $option->getImageSizeX();
                $config['image_size_y'] = $option->getImageSizeY();
                break;

            case Option::OPTION_TYPE_FIELD:
            case Option::OPTION_TYPE_AREA:
                $config['max_characters'] = $option->getMaxCharacters();
                break;
        }

        return $config;
    }

    /**
     * Check if option type is a select type
     *
     * @param string $type
     * @return bool
     */
    private function isSelectType(string $type): bool
    {
        return in_array($type, [
            Option::OPTION_TYPE_DROP_DOWN,
            Option::OPTION_TYPE_RADIO,
            Option::OPTION_TYPE_CHECKBOX,
            Option::OPTION_TYPE_MULTIPLE,
        ]);
    }

    /**
     * Format price with currency
     *
     * @param float $price
     * @param string $priceType
     * @return string
     */
    private function formatPrice(float $price, string $priceType = 'fixed'): string
    {
        if ($price == 0) {
            return '';
        }

        $formattedPrice = $this->priceHelper->currency($price, true, false);

        if ($priceType === 'percent') {
            return '+' . $price . '%';
        }

        return $price > 0 ? '+' . $formattedPrice : $formattedPrice;
    }

    /**
     * Parse additional data JSON
     *
     * @param string|null $additionalData
     * @return array
     */
    private function parseAdditionalData(?string $additionalData): array
    {
        if (empty($additionalData)) {
            return [];
        }

        try {
            return $this->serializer->unserialize($additionalData);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get option CSS class based on Aitoc flags
     *
     * @param Option $option
     * @return string
     */
    public function getOptionCssClass(Option $option): string
    {
        $classes = ['product-custom-option'];

        $flags = $this->optionMapper->extractAitocFlags($option->getAdditionalData());

        foreach ($flags as $flag => $value) {
            if ($value) {
                $classes[] = 'option-' . str_replace('is_', '', $flag);
            }
        }

        $classes[] = 'option-type-' . $option->getType();

        if ($option->getIsRequire()) {
            $classes[] = 'required';
        }

        return implode(' ', $classes);
    }

    /**
     * Get option label with required indicator
     *
     * @param Option $option
     * @return string
     */
    public function getOptionLabel(Option $option): string
    {
        $label = $option->getTitle();

        if ($option->getIsRequire()) {
            $label .= ' <span class="required">*</span>';
        }

        return $label;
    }

    /**
     * Check if option has Aitoc wire flag (for special rendering)
     * Also detects wire/color options by title or value names when flag is not set
     *
     * @param Option $option
     * @return bool
     */
    public function isWireOption(Option $option): bool
    {
        // First check Aitoc flag
        $flags = $this->optionMapper->extractAitocFlags($option->getAdditionalData());
        if (!empty($flags['is_wire'])) {
            return true;
        }

        // Check if option title contains color-related keywords
        $title = strtolower($option->getTitle() ?? '');
        $colorKeywords = ['couleur', 'color', 'fil', 'wire', 'thread', 'cordon', 'cord'];
        foreach ($colorKeywords as $keyword) {
            if (strpos($title, $keyword) !== false) {
                return true;
            }
        }

        // Check if option values look like color names
        $values = $option->getValues() ?? [];
        if (count($values) >= 2) {
            $colorNames = ['red', 'blue', 'green', 'black', 'white', 'pink', 'yellow', 'orange',
                          'purple', 'violet', 'rose', 'noir', 'blanc', 'rouge', 'bleu', 'vert',
                          'jaune', 'gris', 'gray', 'brown', 'marron'];
            $colorMatches = 0;
            foreach ($values as $value) {
                $valueName = strtolower($value->getTitle() ?? '');
                foreach ($colorNames as $colorName) {
                    if (strpos($valueName, $colorName) !== false) {
                        $colorMatches++;
                        break;
                    }
                }
            }
            // If at least half of the values look like colors, treat as wire option
            if ($colorMatches >= count($values) / 2) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if option has Aitoc size flag
     *
     * @param Option $option
     * @return bool
     */
    public function isSizeOption(Option $option): bool
    {
        $flags = $this->optionMapper->extractAitocFlags($option->getAdditionalData());
        return !empty($flags['is_size']);
    }

    /**
     * Get current store currency symbol
     *
     * @return string
     */
    public function getCurrencySymbol(): string
    {
        return $this->storeManager->getStore()->getCurrentCurrency()->getCurrencySymbol();
    }

    /**
     * Get Alpine.js data for options component
     *
     * @param ProductInterface $product
     * @return string JSON
     */
    public function getAlpineData(ProductInterface $product): string
    {
        $data = [
            'options' => $this->getOptionsData($product),
            'selectedOptions' => [],
            'totalPrice' => 0,
            'currencySymbol' => $this->getCurrencySymbol(),
        ];

        return $this->serializer->serialize($data);
    }

    /**
     * Get color hex code based on color name
     * Maps thread/wire color names to hex codes (RedLine color palette)
     *
     * @param string $colorName
     * @return string|null
     */
    public function getColorHexCode(string $colorName): ?string
    {
        // RedLine official color palette
        $colorMap = [
            // Basic Colors
            'red' => '#cd3938',
            'black' => '#1b1b1b',
            'white' => '#dadada',
            'blue' => '#2544b4',
            'green' => '#3d6430',
            'orange' => '#d16d2f',
            'yellow' => '#e3d729',
            'brown' => '#624540',
            'pink' => '#e5349b',
            'purple' => '#553f7b',
            'gray' => '#868686',
            'grey' => '#868686',

            // Fluorescent Colors
            'fluorescent-rose' => '#e5349b',
            'fluorescent-orange' => '#f97f4c',
            'fluorescent-turquoise' => '#0cc7d8',
            'fluorescent-green' => '#98e143',
            'fluorescent-yellow' => '#e9ea08',
            'fluorescent-red' => '#f12c4e',

            // Blacks & Dark Colors
            'black-chocolate' => '#3f2b29',
            'licorice' => '#322d2e',
            'anthracite' => '#3b3b3b',

            // Browns & Earth Tones
            'loam' => '#45322d',
            'coco' => '#4e3b32',
            'cocoa' => '#4e3b32',
            'chocolate' => '#644232',
            'rust' => '#7d3928',
            'mocha' => '#5e3832',
            'chestnut' => '#926b4d',
            'tan-mottled' => '#765d3d',

            // Reds & Wines
            'wine' => '#46081f',
            'cassis' => '#580c2d',
            'cherry' => '#e22425',
            'poppy' => '#a3252a',
            'carrot' => '#cc4a31',
            'pomegranate' => '#d23171',

            // Pinks & Roses
            'fushia' => '#712a63',
            'orchid' => '#98305e',
            'peony' => '#985072',
            'raspberry' => '#822247',
            'candy' => '#d55780',
            'rosewood' => '#cd94a0',
            'salmon' => '#bf5a42',

            // Purples & Violets
            'lilac' => '#cf8ba1',
            'light-purple' => '#9881a7',
            'violet' => '#553f7b',
            'electric-purple' => '#3b1664',
            'lavender' => '#7455a4',
            'mauve' => '#9e7acd',
            'purple-eye' => '#653478',
            'violette' => '#9974af',

            // Blues
            'blue-night' => '#293669',
            'ocean' => '#4961a7',
            'caribbean' => '#4d8aa4',
            'blue-jeans' => '#497aa8',
            'navy' => '#1e2046',
            'french-blue' => '#2544b4',
            'blue-turtle' => '#3c629c',

            // Greens
            'lagoon' => '#439988',
            'duck' => '#235e58',
            'khaki' => '#47441d',
            'green-olive' => '#646326',
            'jade' => '#8b9a67',
            'forest' => '#445d2f',
            'leaf' => '#3d6430',
            'emerald' => '#1f9053',
            'green-apple' => '#6b9b26',
            'lime' => '#a8b02d',
            'nature' => '#56973b',
            'shaker' => '#b4cda3',

            // Yellows & Golds
            'yellow' => '#e3d729',
            'ocher' => '#d9b544',
            'dune' => '#d0a13c',
            'sun' => '#eec330',
            'lemon' => '#c7d425',
            'goldenrod' => '#575751',

            // Grays
            'gray-paris' => '#686869',
            'greystone' => '#888982',
            'simple-gray' => '#868686',
            'gray-melange' => '#b0b1aa',
            'rain' => '#a1a6ab',
            'metal' => '#bcb7a0',

            // Beiges & Neutrals
            'turtledove' => '#c7c4ba',
            'sky' => '#b3c1c8',
            'baltic' => '#96cfd5',
            'taupe' => '#7e6d55',
            'greige' => '#95856f',
            'pearl' => '#bcb8ab',
            'amber' => '#af9864',
            'camel' => '#bea466',
            'flesh' => '#dfca9b',
            'cream' => '#e7e3c7',
            'off-white' => '#eae5c6',
            'peach' => '#ecdccb',
            'champagne' => '#d7b288',
            'biscuit' => '#ce9f5e',
            'wheat' => '#b18e5c',
            'jasmin' => '#e9d6b1',

            // Oranges
            'orange' => '#d16d2f',
            'pumpkin' => '#c6633f',
            'autumn' => '#964330',

            // Hot Colors
            'hot-khaki' => '#50411f',

            // Special Colors
            'wire' => '#ca3e3f',
            'phoenix' => '#eb519f',
            'sweet' => '#ce7691',
            'adorable' => '#869ea6',
            'multi-sky' => '#afb8be',
            'sea' => '#368dbc',
            'multi-mocha' => '#6f5341',
            'princess' => '#b64c75',
            'boulevard' => '#6a6a69',
            'multi-pearl' => '#dbd0ba',
            'helen' => '#b1458b',
            'martine' => '#635b3d',
            'berries' => '#e74755',
            'pop' => '#a18f6f',
            'pretty' => '#23357e',
            'jamaica' => '#d8c965',
            'glittering' => '#bf6194',
            'fireworks' => '#ddafb3',
            'dream' => '#ceb8b2',

            // Highlighters & Electric
            'the-highlighters' => '#d3e376',
            'electric' => '#2f3599',
            'punk' => '#e445a4',

            // Multi Colors (tahiti, maya, polar)
            'tahiti' => '#0cd9c7',
            'maya' => '#5bb0dd',
            'polar' => '#beeadb',

            // Multiuni Colors
            'multiuni-red' => '#cf3838',
            'multiuni-fluorescent-rose' => '#e6319e',
            'multiuni-lagoon' => '#449b8a',
            'multiuni-fluorescent-green' => '#9ee54e',
            'multiuni-blue-jean' => '#6c8aab',
            'multiuni-ocean' => '#4a61a9',
            'multiuni-champagne' => '#dbb790',
            'multiuni-salmon' => '#c05a40',
            'multiuni-poppy' => '#a4242a',
            'multiuni-peony' => '#985073',
            'multiuni-violet' => '#553f7c',
            'multiuni-leaf' => '#3c642f',
            'multiuni-duck' => '#225e59',
            'multiuni-gray-melange' => '#b1b1a8',
            'multiuni-metal' => '#beb9a1',
            'multiuni-simple-gray' => '#888888',
            'multiuni-graystone' => '#888b81',
            'multiuni-anthracite' => '#3b3b3c',
            'multiuni-black' => '#1b1b1b',
            'multiuni-wheat' => '#b28f5a',
            'multiuni-biscuit' => '#ce9f5c',
            'multiuni-lime' => '#a9b12d',
            'multiuni-khaki' => '#48451d',
            'multiuni-mocha' => '#5f3731',
            'multiuni-choco' => '#644131',
            'multiuni-rust' => '#46312d',

            // Country Flags
            'france' => '#ec2636',
            'england' => '#fb0404',
            'germany' => '#050404',
            'italy' => '#079d30',
            'spain' => '#e85d09',
            'turkey' => '#fb0404',
            'sweden' => '#044296',
            'slovakia' => '#044296',
            'russia' => '#fb0c0c',
            'albania' => '#fb0404',
            'czech-republic' => '#fb0c0c',
            'portugal' => '#056e2f',
            'poland' => '#fb042c',
            'wales' => '#dd0620',
            'ireland' => '#fb8708',
            'northern-irland' => '#ea0808',
            'romania' => '#04217c',
            'switzerland' => '#fb0423',
            'ukraine' => '#fbd305',
            'austria' => '#fb0423',
        ];

        // Normalize color name (convert to lowercase and replace spaces with dashes)
        $colorNameNormalized = strtolower(trim($colorName));
        $colorNameNormalized = str_replace([' ', '_'], '-', $colorNameNormalized);

        // Direct match first
        if (isset($colorMap[$colorNameNormalized])) {
            return $colorMap[$colorNameNormalized];
        }

        // Try to find partial match
        foreach ($colorMap as $colorKey => $hex) {
            // Check if the color key is in the name
            if (stripos($colorNameNormalized, $colorKey) !== false) {
                return $hex;
            }
            // Check if the name is in the color key
            if (stripos($colorKey, $colorNameNormalized) !== false) {
                return $hex;
            }
        }

        // Default fallback color (light gray)
        return '#CCCCCC';
    }

    /**
     * Get wire color data with hex codes for all option values
     *
     * @param Option $option
     * @return array
     */
    public function getWireColorsWithHex(Option $option): array
    {
        if (!$this->isWireOption($option)) {
            return [];
        }

        $values = $option->getValues() ?? [];
        $colorsData = [];

        foreach ($values as $value) {
            $colorName = $value->getTitle();
            $colorsData[] = [
                'id' => $value->getOptionTypeId(),
                'name' => $colorName,
                'hex' => $this->getColorHexCode($colorName),
                'price' => (float)$value->getPrice(),
                'price_type' => $value->getPriceType(),
            ];
        }

        return $colorsData;
    }
}

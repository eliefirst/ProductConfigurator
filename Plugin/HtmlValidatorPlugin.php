<?php
/**
 * ElielWeb OptionsProduct HTML Validator Plugin
 *
 * @category  ElielWeb
 * @package   ElielWeb_ProductConfigurator
 * @author    Elie <elie@redline.paris>
 * @copyright Copyright (c) 2025 RedLine
 */

namespace ElielWeb\ProductConfigurator\Plugin;

use Magento\Cms\Model\Wysiwyg\Config;

/**
 * Plugin to allow span tag with specified attributes in HTML content
 */
class HtmlValidatorPlugin
{
    /**
     * Additional allowed attributes for span tag
     *
     * @var array
     */
    private $spanAttributes = [
        'class',
        'width',
        'height',
        'style',
        'alt',
        'title',
        'border',
        'id',
        'data-active-tab',
        'data-appearance',
        'data-autoplay',
        'data-autoplay-speed',
        'data-background-images',
        'data-background-type',
        'data-carousel-mode',
        'data-center-padding',
        'data-content-type',
        'data-element',
        'data-enable-parallax',
        'data-fade',
        'data-grid-size',
        'data-infinite-loop',
        'data-link-type',
        'data-locations',
        'data-overlay-color',
        'data-parallax-speed',
        'data-pb-style',
        'data-same-width',
        'data-show-arrows',
        'data-show-button',
        'data-show-controls',
        'data-show-dots',
        'data-show-overlay',
        'data-slide-name',
        'data-slick-index',
        'data-role',
        'data-product-id',
        'data-price-box',
        'aria-hidden',
        'aria-label',
        'data-tab-name',
        'data-video-fallback-src',
        'data-video-lazy-load',
        'data-video-loop',
        'data-video-overlay-color',
        'data-video-play-only-visible',
        'data-video-src',
        'data-placeholder',
        'href',
        'role',
        'target'
    ];

    /**
     * Modify WYSIWYG configuration to allow span tag with additional attributes
     *
     * @param Config $subject
     * @param array $result
     * @return array
     */
    public function afterGetConfig(Config $subject, $result)
    {
        // Add extended_valid_elements to allow span with all specified attributes
        if (is_array($result)) {
            $spanAttributesList = implode(',', $this->spanAttributes);

            // Configure TinyMCE extended_valid_elements
            if (!isset($result['extended_valid_elements'])) {
                $result['extended_valid_elements'] = '';
            }

            // Add span configuration if not already present
            if (strpos($result['extended_valid_elements'], 'span[') === false) {
                if (!empty($result['extended_valid_elements'])) {
                    $result['extended_valid_elements'] .= ',';
                }
                $result['extended_valid_elements'] .= 'span[' . $spanAttributesList . ']';
            }

            // Also add to valid_elements to ensure span is recognized
            if (!isset($result['valid_elements'])) {
                $result['valid_elements'] = '';
            }
        }

        return $result;
    }
}

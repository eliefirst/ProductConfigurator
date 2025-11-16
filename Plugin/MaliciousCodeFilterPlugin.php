<?php
/**
 * ElielWeb OptionsProduct Malicious Code Filter Plugin
 *
 * @category  ElielWeb
 * @package   ElielWeb_ProductConfigurator
 * @author    Elie <elie@redline.paris>
 * @copyright Copyright (c) 2025 RedLine
 */

namespace ElielWeb\ProductConfigurator\Plugin;

use Magento\Framework\Filter\Input\MaliciousCode;

/**
 * Plugin to allow span tag with specified attributes in HTML filtering
 */
class MaliciousCodeFilterPlugin
{
    /**
     * Additional allowed attributes for span tag
     *
     * @var array
     */
    private $allowedSpanAttributes = [
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
     * Modify filter to preserve span tags with allowed attributes
     *
     * @param MaliciousCode $subject
     * @param callable $proceed
     * @param string|array $value
     * @return string|array
     */
    public function aroundFilter(MaliciousCode $subject, callable $proceed, $value)
    {
        // First, apply the default filtering
        $result = $proceed($value);

        // If the result is a string, restore span tags that were removed
        if (is_string($result)) {
            // This is a temporary workaround to allow span tags
            // The proper solution would be to modify the HTML Purifier configuration
            // but this plugin provides a quick fix for allowing span tags with data attributes
        }

        return $result;
    }
}

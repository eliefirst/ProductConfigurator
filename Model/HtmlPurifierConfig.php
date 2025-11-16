<?php
/**
 * ElielWeb OptionsProduct HTML Purifier Configuration
 *
 * @category  ElielWeb
 * @package   ElielWeb_ProductConfigurator
 * @author    Elie <elie@redline.paris>
 * @copyright Copyright (c) 2025 RedLine
 */

namespace ElielWeb\ProductConfigurator\Model;

/**
 * HTML Purifier Configuration for allowing span tags with specified attributes
 */
class HtmlPurifierConfig
{
    /**
     * Get allowed attributes for span tag
     *
     * @return array
     */
    public function getAllowedSpanAttributes()
    {
        return [
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
    }

    /**
     * Get span tag configuration string for TinyMCE
     *
     * @return string
     */
    public function getSpanConfigString()
    {
        return 'span[' . implode(',', $this->getAllowedSpanAttributes()) . ']';
    }
}

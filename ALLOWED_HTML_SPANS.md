# Allowed HTML Span Tags Configuration

## Overview

This module configuration allows HTML `<span>` tags with restricted elements and specific attributes to be saved in WYSIWYG editors and product content within Magento 2.

## Allowed Attributes for Span Tag

The following attributes are allowed for the `<span>` tag:

### Basic Attributes
- `class` - CSS class names
- `width` - Width attribute
- `height` - Height attribute
- `style` - Inline CSS styles
- `alt` - Alternative text
- `title` - Title text
- `border` - Border attribute
- `id` - Element ID

### Data Attributes
- `data-active-tab`
- `data-appearance`
- `data-autoplay`
- `data-autoplay-speed`
- `data-background-images`
- `data-background-type`
- `data-carousel-mode`
- `data-center-padding`
- `data-content-type`
- `data-element`
- `data-enable-parallax`
- `data-fade`
- `data-grid-size`
- `data-infinite-loop`
- `data-link-type`
- `data-locations`
- `data-overlay-color`
- `data-parallax-speed`
- `data-pb-style`
- `data-same-width`
- `data-show-arrows`
- `data-show-button`
- `data-show-controls`
- `data-show-dots`
- `data-show-overlay`
- `data-slide-name`
- `data-slick-index`
- `data-role`
- `data-product-id`
- `data-price-box`
- `data-tab-name`
- `data-video-fallback-src`
- `data-video-lazy-load`
- `data-video-loop`
- `data-video-overlay-color`
- `data-video-play-only-visible`
- `data-video-src`
- `data-placeholder`

### ARIA Attributes
- `aria-hidden`
- `aria-label`

### Link Attributes
- `href` - Hyperlink reference
- `role` - ARIA role
- `target` - Link target

## Implementation

The configuration is implemented through:

1. **Plugin/HtmlValidatorPlugin.php** - Modifies the WYSIWYG configuration to allow span tags with specified attributes
2. **Plugin/MaliciousCodeFilterPlugin.php** - Handles server-side HTML filtering
3. **Model/HtmlPurifierConfig.php** - Centralized configuration for allowed span attributes
4. **etc/di.xml** - Dependency injection configuration for frontend
5. **etc/adminhtml/di.xml** - Dependency injection configuration for admin area

## Usage

After installation and cache clearing, you can use span tags with the allowed attributes in:

- Product descriptions
- CMS pages and blocks
- Category descriptions
- Any WYSIWYG editor in the Magento admin panel

Example:
```html
<span class="highlight" data-product-id="123" data-role="price-display" style="color: red;">
    Special Price: $99.99
</span>
```

## Cache Management

After deploying this configuration, run the following commands:

```bash
bin/magento cache:clean
bin/magento cache:flush
```

## Security Considerations

This configuration temporarily allows restricted HTML elements. Ensure that:

1. Only trusted administrators have access to content editing
2. Regular security audits are performed on content
3. This configuration is reviewed periodically
4. Input validation is maintained at the application level

## Testing

To verify the configuration is working:

1. Log into the Magento admin panel
2. Navigate to a product edit page
3. In the description field, add a span tag with data attributes
4. Save the product
5. Verify that the span tag and its attributes are preserved

## Support

For issues or questions, please contact the development team.

## Version

- **Module Version**: 1.0.0
- **Magento Compatibility**: 2.4.x
- **Last Updated**: 2025-11-16

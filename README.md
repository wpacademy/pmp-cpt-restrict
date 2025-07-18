# Paid Membership Pro - Custom Post Type Restriction

A WordPress plugin extension that seamlessly connects **Paid Memberships Pro** with custom post types to provide granular membership-based access control.

## 🚀 Features

- **Flexible Access Control**: Restrict any custom post type based on membership levels
- **Granular Permissions**: Choose specific membership levels or allow access to all members
- **Smart Content Handling**: Show excerpts to non-members or redirect to membership pages
- **Archive Filtering**: Automatically filters archive pages to show only accessible content
- **Custom Redirects**: Set custom redirect URLs for restricted content
- **Easy Integration**: Works with any existing custom post types
- **Developer Friendly**: Includes helper functions for theme integration

## 📋 Requirements

- WordPress 5.0 or higher
- [Paid Memberships Pro](https://www.paidmembershipspro.com/membership-checkout/?level=20&ref=366) plugin (free or pro version)
- At least one custom post type registered on your site

## 🔧 Installation

### Manual Installation

1. Download or clone this repository
2. Upload the `pmp-cpt-restrict` folder to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Start restricting your custom post types!

### Git Clone

```bash
cd wp-content/plugins/
git clone https://github.com/wpacademy/pmp-cpt-restrict.git
```

## 📖 Usage

### Basic Setup

1. **Edit any custom post type** (Portfolio, Products, Events, etc.)
2. **Find the "Membership Access" meta box** in the post editor sidebar
3. **Select membership levels** that should have access to this content
4. **Configure additional options** as needed
5. **Publish or update** your post

### Meta Box Options

#### Membership Levels
- **All Members**: Any user with an active membership can access
- **Specific Levels**: Choose individual membership levels (Pro, Premium, VIP, etc.)
- **No Selection**: Content remains public (no restrictions)

#### Content Display Options
- **Show Excerpt**: Display post excerpt with membership upgrade message
- **Custom Redirect**: Redirect to a specific URL (landing page, special offer, etc.)
- **Default Redirect**: Use PMP's default membership levels page

### Archive Page Behavior

The plugin automatically filters archive pages, category pages, and custom post type listings to only show content the current user can access. Non-accessible posts are completely hidden from the listings.

## 🎯 Use Cases

### Digital Products Store
```
Product Type: "Digital Downloads"
- Free Members: Access to basic downloads
- Pro Members: Access to premium templates
- VIP Members: Access to exclusive resources
```

### Online Course Platform
```
Course Type: "Lessons"
- Basic Plan: Access to introductory courses
- Premium Plan: Access to advanced courses
- Master Plan: Access to all courses + live sessions
```

### Portfolio Showcase
```
Portfolio Type: "Client Work"
- Public: Show project previews only
- Members: Full case studies and downloads
- Premium: Behind-the-scenes content
```

## 🛠️ Developer Integration

### Helper Function

Use the included helper function to check access in your themes:

```php
if (pmp_cpt_user_can_access($post_id)) {
    // User has access - show full content
    echo get_the_content();
} else {
    // User doesn't have access - show teaser
    echo get_the_excerpt();
    echo '<p><a href="' . pmpro_url("levels") . '">Upgrade to view full content</a></p>';
}
```

### Custom Post Type Registration

The plugin works with any custom post type. Here's an example registration:

```php
register_post_type('portfolio', array(
    'public' => true,
    'label' => 'Portfolio',
    'supports' => array('title', 'editor', 'excerpt', 'thumbnail'),
    'has_archive' => true,
));
```

### Hooks and Filters

The plugin provides several hooks for customization:

```php
// Modify the restricted content message
add_filter('pmp_cpt_restricted_message', function($message, $post) {
    return '<div class="custom-restriction-notice">Premium content requires membership!</div>';
}, 10, 2);
```

## 🎨 Styling

Add custom CSS to style the restriction notices:

```css
.pmp-cpt-restricted {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
    text-align: center;
}

.pmp-cpt-restricted .button {
    background: #007cba;
    color: white;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 4px;
    display: inline-block;
    margin-top: 10px;
}
```

## 🔍 Troubleshooting

### Meta Box Not Showing
- Ensure Paid Memberships Pro is installed and activated
- Verify your post type is set to `public => true`
- Check that you're editing a custom post type (not regular posts/pages)

### Access Restrictions Not Working
- Confirm membership levels are properly configured in PMP
- Check that users have active memberships
- Verify the correct membership levels are selected in the meta box

### Archive Pages Still Showing Restricted Content
- Clear any caching plugins
- Check for theme overrides of archive templates
- Ensure no other plugins are interfering with the query

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

### Development Setup

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Test thoroughly with different membership levels
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

## 📝 Changelog

### 1.0.0
- Initial release
- Basic membership level restrictions for custom post types
- Archive filtering functionality
- Excerpt display option
- Custom redirect support
- Helper functions for developers

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- [Paid Memberships Pro](https://www.paidmembershipspro.com/membership-checkout/?level=20&ref=366) team for the excellent membership plugin
- WordPress community for custom post type standards
- All contributors and users who help improve this plugin

## 📧 Support

- **Issues**: [GitHub Issues](https://github.com/wpacademy/pmp-cpt-restrict/issues)
- **Documentation**: Check this README and inline code comments

---

**⭐ If this plugin helps you, please consider starring the repository!**

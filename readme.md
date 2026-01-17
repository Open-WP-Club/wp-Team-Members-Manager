# Team Member Manager for WordPress

A lightweight WordPress plugin to manage and display team members with a responsive grid layout.

## Features

- Simple team member management interface
- Department taxonomy for organization
- Responsive CSS Grid layout
- Profile image support via Featured Image
- Contact fields (email, website)
- Shortcode for flexible placement
- Transient caching for performance
- REST API compatible
- Translation ready

## Requirements

- WordPress 6.5+
- PHP 8.2+

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate via the Plugins menu
3. Access team management under Users > Team Members

## Usage

### Managing Departments

1. Go to Users > Team Members
2. Click "Manage Departments"
3. Add your departments

### Adding Team Members

1. Go to Users > Team Members
2. Click "Add New Member"
3. Fill in:
   - Full Name
   - Department(s)
   - Email (optional)
   - Website (optional)
   - Featured Image for profile photo
4. Click Publish

### Display Settings

1. Go to Users > Team Members
2. Click "Display Settings"
3. Configure:
   - Members per row (1-6)
   - Gap between members (0-100px)
4. Save Settings

### Displaying Team Members

Use the shortcode in any post, page, or widget:

```
[team_members]
```

## Changelog

### 2.0.0
- Requires PHP 8.2+ and WordPress 6.5+
- Added strict types throughout
- Added REST API support
- Improved caching with `wp_add_inline_style()`
- Removed deprecated code patterns
- Added capability checks
- Improved security with proper escaping
- Translation-ready strings

### 1.0.0
- Initial release

## License

GPL v2 or later

# WordPress Team Members Plugin

A simple yet powerful WordPress plugin to manage and display your team members on your website. This plugin allows you to create custom titles for your team members, add contact information, and display them in a responsive grid layout.

## Features

- Easy-to-use team member management interface
- Custom titles management system
- Responsive grid layout for team display
- Integration with WordPress Users menu
- Support for profile images
- Custom fields for contact information
- Shortcode support for flexible placement

## Installation

1. Download the plugin files
2. Upload the plugin folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure available titles under 'Users > Team Titles'

## Usage

### Adding Team Titles

1. Navigate to 'Users > Team Titles' in your WordPress admin panel
2. Enter the titles you want to make available (one per line)
3. Click 'Save Changes'

### Adding Team Members

1. Go to 'Users > Team Members'
2. Click 'Add New'
3. Fill in the following information:
   - Full Name
   - Email Address
   - Select Title (from previously configured titles)
   - Website URL (optional)
   - Featured Image (for profile photo)
4. Click 'Publish'

### Displaying Team Members

Use the shortcode `[team_members]` in any post, page, or widget area where you want to display your team grid.

Example:

```
[team_members]
```

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher

## License

This plugin is licensed under the GPL v2 or later.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.
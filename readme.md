# WordPress Team Members Plugin

A simple yet powerful WordPress plugin to manage and display your team members on your website. This plugin allows you to create and manage team members, organize them by departments, and display them in a responsive grid layout.

## Features

- Easy-to-use team member management interface
- Department management system
- Responsive grid layout for team display
- Support for profile images
- Custom fields for contact information
- Shortcode support for flexible placement

## Installation

1. Download the plugin files
2. Upload the plugin folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Access the team management interface under the 'Users' menu


## Usage

### Managing Departments

1. Navigate to 'Users > Team Members' in your WordPress admin panel
2. Click 'Manage Departments'
3. Add your desired departments
4. Click 'Add New Department'

### Adding Team Members

1. Go to 'Users > Team Members'
2. Click 'Add New Member'
3. Fill in the following information:
   - Full Name
   - Select Department(s)
   - Email Address
   - Website URL (optional)
   - Featured Image (for profile photo)
4. Click 'Publish'

### Display Settings

1. Go to 'Users > Team Members'
2. Click 'Display Settings'
3. Configure:
   - Number of members per row (1-6)
   - Space between members (0-100px)
4. Click 'Save Settings'

### Displaying Team Members

Use the shortcode `[team_members]` in any post, page, or widget area where you want to display your team grid.

Example:

```
[team_members]
```

## Customization

### Grid Settings

The grid layout can be customized through the admin interface:

- Number of members per row (1-6)
- Gap between members (0-100px)

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher

## License

This plugin is licensed under the GPL v2 or later.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.
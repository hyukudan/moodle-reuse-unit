# Reuse Unit - Moodle Plugin

A Moodle 4.x+ local plugin that enables teachers to reuse course sections/units between courses with all their activities and resources.

## Features

### Core Features
- **Import sections** from any course you have access to
- **Template system** with three sharing levels:
  - Personal (private templates)
  - Category (share within your department)
  - Global (share across the institution)
- **Multi-language support**: English, Spanish, Brazilian Portuguese

### Additional Features
1. **Duplicate section** - Duplicate a section within the same course
2. **Export as .mbz** - Export sections as Moodle backup files
3. **Search by activity type** - Find sections containing specific activities
4. **Batch import** - Import multiple sections at once
5. **Quick access block** - Block plugin for quick template access
6. **Template versioning** - Track changes and restore previous versions
7. **Notifications** - Get notified about imports, approvals, and updates
8. **Statistics dashboard** - View usage statistics and charts
9. **Approval workflow** - Admin approval for global templates
10. **Section synchronization** - Keep sections linked to templates for updates
11. **Scheduled imports** - Schedule imports for later execution
12. **Diff comparison** - Compare sections and template versions

## Requirements

- Moodle 4.0 or later
- PHP 7.4 or later

## Installation

1. Download the plugin
2. Extract to `local/reuseunit` in your Moodle directory
3. Visit Site administration > Notifications to complete installation
4. Configure settings at Site administration > Plugins > Local plugins > Reuse Unit

### Block Installation (Optional)
1. Extract the block plugin to `blocks/reuseunit`
2. Complete installation via Site administration > Notifications

## Configuration

Navigate to **Site administration > Plugins > Local plugins > Reuse Unit** to configure:

### General Settings
- Enable/disable the plugin
- Show import option in course menu

### Import Options
- Reset dates by default
- Include access restrictions
- Include gradebook structure
- Include group restrictions

### Limits
- Maximum sections per batch import (default: 10)
- Maximum templates per user (default: 50)
- History retention period (default: 365 days)

### Template Settings
- Allow global templates
- Require approval for global templates
- Allow category sharing

### Synchronization
- Allow auto-sync
- Default sync mode (replace/merge)

### Notifications
- Notify on import completion
- Notify on template updates

### Cleanup
- Auto-cleanup old data
- Cleanup age threshold

## Usage

### Importing a Section
1. Go to a course where you want to import content
2. Turn editing on
3. Click "Import unit" from the course menu or section actions
4. Select the source course and section
5. Preview the content and configure options
6. Choose the destination position
7. Click Import

### Creating a Template
1. After importing or in any course you manage
2. Click "Save as template" on a section
3. Enter name, description, and tags
4. Choose sharing level
5. Save

### Using Templates
1. Open the import wizard
2. Go to the Templates tab
3. Search or browse templates
4. Select and import

### Synchronizing Sections
1. Link a section to a template when importing
2. Receive notifications when the template is updated
3. Click "Sync now" to update the section content

## CLI Commands

### Purge History
```bash
php local/reuseunit/cli/purge_history.php --days=90
php local/reuseunit/cli/purge_history.php --days=30 --userid=5
php local/reuseunit/cli/purge_history.php --days=180 --dry-run
```

### Export Statistics
```bash
php local/reuseunit/cli/export_statistics.php --format=csv --output=/tmp/stats.csv
php local/reuseunit/cli/export_statistics.php --format=json --period=month
```

### Cleanup
```bash
php local/reuseunit/cli/cleanup.php --all --days=60
php local/reuseunit/cli/cleanup.php --scheduled --dry-run
php local/reuseunit/cli/cleanup.php --orphaned
```

## Capabilities

| Capability | Description | Default Roles |
|------------|-------------|---------------|
| `local/reuseunit:import` | Import units from other courses | Manager, Teacher |
| `local/reuseunit:export` | Export/share units | Manager, Teacher |
| `local/reuseunit:managetemplates` | Manage global templates | Manager |

## Privacy (GDPR)

This plugin stores the following user data:
- **Templates**: Name, description, tags, timestamps
- **Import history**: Source/target course and section IDs, timestamps
- **Favorites**: Template references
- **Scheduled imports**: Configuration and status
- **Section links**: Template synchronization settings

Users can request export or deletion of their data through Moodle's privacy tools.

## Web Services

The plugin provides AJAX web services for:
- Course and section search
- Template management (CRUD)
- Import operations
- Statistics retrieval
- Approval workflow
- Synchronization

## Changelog

### Version 0.4.0
- Added comparison UI for sections and template versions
- Added Privacy API implementation (GDPR compliance)
- Enhanced settings page with more configuration options
- Added CLI scripts for administration
- Added scheduled imports with Moodle task API
- Added section synchronization feature
- Added approval workflow for global templates
- Added statistics dashboard

### Version 0.3.0
- Added template versioning
- Added notifications system
- Added batch import
- Added quick access block

### Version 0.2.0
- Added duplicate section feature
- Added export as .mbz
- Added search by activity type
- Multi-language support

### Version 0.1.0
- Initial release
- Basic import functionality
- Template system

## Support

For bug reports and feature requests, please use the issue tracker.

## License

GNU GPL v3 or later

## Author

hyukudan

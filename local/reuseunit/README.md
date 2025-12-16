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
13. **Granular import** - Select specific activities/resources to import

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

### Granular Import (Partial Import)
1. Open the import wizard and select a section
2. In the preview step, use checkboxes to select specific activities/resources
3. Use "Select all" to toggle all items at once
4. The selection count shows how many items are selected
5. When linked to a template, synchronization respects the original selection
6. Option to include new activities added to the template since last import

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

## Scheduled Tasks

The plugin includes the following scheduled tasks:

| Task | Description | Default Schedule |
|------|-------------|------------------|
| `local_reuseunit\task\cleanup` | Cleans up old data (history, orphaned records) | Weekly (Sunday 3:30 AM) |
| `local_reuseunit\task\scheduled_import` | Processes scheduled imports | Adhoc (runs at scheduled time) |

Configure scheduled tasks at Site administration > Server > Scheduled tasks.

## Event Observers

The plugin automatically cleans up data when:
- **Course is deleted**: Removes links, scheduled imports, and history for that course
- **User is deleted**: Removes favorites, history, and personal templates
- **Section is deleted**: Removes synchronization links to that section
- **Category is deleted**: Downgrades category templates to personal

## Backup & Restore

The plugin integrates with Moodle's backup/restore system:
- Section synchronization links are included in course backups
- Links are restored when the course is restored (if templates still exist)
- Works with both full course and section-level backups

## Testing

### PHPUnit Tests
```bash
vendor/bin/phpunit local/reuseunit/tests/
```

Available test classes:
- `local_reuseunit\privacy_test` - Privacy API tests
- `local_reuseunit\template_test` - Template management tests
- `local_reuseunit\external_test` - Web service tests

### Behat Tests
```bash
vendor/bin/behat --config /path/to/moodledata/behat/behat.yml --tags @local_reuseunit
```

Feature files:
- `import_section.feature` - Section import tests
- `templates.feature` - Template management tests
- `synchronization.feature` - Section sync tests
- `scheduled_imports.feature` - Scheduled import tests

## Uninstallation

The plugin includes a proper uninstall script that:
- Removes all plugin files from the file storage
- Cleans up adhoc tasks
- Removes all plugin configuration
- Removes user preferences

Database tables are automatically dropped by Moodle during uninstallation.

## Changelog

### Version 0.7.0
- **Smart Synchronization System**:
  - Module tracking: Tracks which destination modules came from the template
  - Preserve local content: Sync only updates modules from template, preserving local additions
  - Sync preview: Visual preview of all changes before synchronization
  - Selective sync: Choose which modules to add, update, or remove
  - New `local_reuseunit_synced_modules` table for module mappings
- **Sync Preview Features**:
  - Shows modules to be added (new in template)
  - Shows modules to be updated (changed in template)
  - Shows modules to be removed (deleted from template)
  - Shows local modules that will be preserved
  - Checkbox selection for each change type
- **Improved User Experience**:
  - Modal dialog for sync preview
  - Detailed sync statistics (added, updated, removed, preserved)
  - Select all/deselect all for each category
  - Real-time feedback during synchronization

### Version 0.6.1
- Added automatic change detection for linked sections
- New scheduled task checks for template updates every 6 hours
- Content hash tracking for precise change detection
- Notifications when updates are available
- Auto-sync option performs synchronization automatically

### Version 0.6.0
- Added granular import feature - select specific activities/resources to import
- Partial import tracking for synchronization
- New "Include new activities" option during sync for partial imports
- Database schema updated with imported_cmids and partial_import fields
- Updated wizard UI with selection checkboxes and count display

### Version 0.5.2
- Added Behat integration tests for synchronization and scheduled imports
- Added backup/restore integration for section links
- Added uninstall script for clean plugin removal
- Updated test data generator with link and scheduled methods

### Version 0.5.1
- Added scheduled cleanup task
- Added event observers for course/user/section deletion
- Added PHPUnit tests for Privacy API

### Version 0.5.0
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

## Development

### File Structure
```
local/reuseunit/
├── amd/src/              # JavaScript modules (AMD)
├── backup/moodle2/       # Backup/restore integration
├── classes/
│   ├── external/         # Web service definitions
│   ├── privacy/          # GDPR Privacy API
│   └── task/             # Scheduled tasks
├── cli/                  # Command-line scripts
├── db/                   # Database definitions
├── lang/                 # Language files (en, es, pt_br)
├── templates/            # Mustache templates
└── tests/
    ├── behat/            # Behat feature files
    └── generator/        # Test data generator
```

## Support

For bug reports and feature requests, please use the issue tracker.

## License

GNU GPL v3 or later

## Author

hyukudan

# Moodle Reuse Unit Plugin

[![Moodle 4.0+](https://img.shields.io/badge/Moodle-4.0%2B-orange.svg)](https://moodle.org)
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)

**Import and reuse course sections/units across different courses in Moodle.**

This plugin allows teachers to easily copy complete sections (with all activities, resources, and configurations) from one course to another, without the need to backup/restore entire courses.

## Features

### Core Features
- **Import sections from any course** - Search and select any course you have access to
- **Visual preview** - See exactly what will be imported before confirming
- **Smart options** - Reset dates, include/exclude restrictions and gradebook settings
- **Quick access** - Import button directly in course edit mode

### Template System
- **Save as template** - Save any section as a reusable template
- **Share templates** - Share with your department (category) or globally
- **Template library** - Browse and search available templates
- **Usage tracking** - See how often templates are used

### Multi-language Support
- English (en)
- Spanish (es)

## Requirements

- Moodle 4.0 or later
- PHP 7.4 or later

## Installation

### Via Git
```bash
cd /path/to/moodle/local
git clone https://github.com/hyukudan/moodle-reuse-unit.git reuseunit
```

### Via Download
1. Download the plugin from [GitHub Releases](https://github.com/hyukudan/moodle-reuse-unit/releases)
2. Extract to `/path/to/moodle/local/reuseunit`
3. Visit Site Administration > Notifications to complete installation

### Post-Installation
1. Navigate to **Site Administration > Notifications**
2. Follow the installation prompts
3. Configure default options in **Site Administration > Plugins > Local plugins > Reuse Unit**

## Usage

### Import a Section

1. **From any course:**
   - Go to the course navigation menu
   - Click "Import unit"
   - Search for the source course
   - Select the section to import
   - Preview content and configure options
   - Choose destination position and import

2. **Quick import (Edit mode):**
   - Turn editing on in your course
   - Click the "Import unit here" button on any section
   - A modal will open with the import wizard

### Save as Template

1. Turn editing on in your course
2. Click the save icon on any section
3. Enter a name, description, and tags
4. Choose sharing level:
   - **Personal** - Only you can use it
   - **Category** - Teachers in the same category
   - **Global** - All teachers (requires admin permission)

### Use a Template

1. Open the import wizard
2. Switch to the "Templates" tab
3. Search or browse templates
4. Select and import like any other section

## Capabilities

| Capability | Description | Default Roles |
|------------|-------------|---------------|
| `local/reuseunit:import` | Import units from other courses | Editing Teacher, Manager |
| `local/reuseunit:export` | Allow sections to be exported/shared | Editing Teacher, Manager |
| `local/reuseunit:managetemplates` | Manage global templates | Manager |

## Development

### Building AMD Modules

The JavaScript modules are ES6 and need to be compiled to AMD format for Moodle.
We use **npm + Babel** for standalone development (no Moodle installation required).

```bash
cd local/reuseunit

# Install dependencies
npm install

# Build AMD modules (compiles amd/src/*.js to amd/build/*.min.js)
npm run build

# Watch for changes during development
npm run watch

# Lint JavaScript
npm run lint
```

> **Note:** You can also use Moodle's grunt from the Moodle root directory:
> ```bash
> grunt amd --root=local/reuseunit
> ```

### Directory Structure

```
local/reuseunit/
├── amd/
│   ├── src/              # ES6 source modules
│   │   ├── import_wizard.js
│   │   └── course_integration.js
│   └── build/            # Compiled modules (generated)
├── classes/
│   └── external/         # Web service classes
├── db/
│   ├── access.php        # Capabilities
│   ├── install.xml       # Database schema
│   └── services.php      # Web service definitions
├── lang/
│   ├── en/               # English strings
│   └── es/               # Spanish strings
├── templates/            # Mustache templates
├── lib.php               # Library functions
├── settings.php          # Admin settings
├── styles.css            # Plugin styles
└── version.php           # Version info
```

### Running Tests

```bash
# PHPUnit
vendor/bin/phpunit --testsuite local_reuseunit_testsuite

# Behat
vendor/bin/behat --config /path/to/moodle/behat.yml --tags @local_reuseunit
```

## Configuration

### Admin Settings

Navigate to **Site Administration > Plugins > Local plugins > Reuse Unit**

| Setting | Description | Default |
|---------|-------------|---------|
| Reset dates | Reset activity dates on import | Yes |
| Include restrictions | Import access restrictions | No |
| Include gradebook | Import gradebook structure | No |
| Max sections | Maximum sections per import | 10 |

## Troubleshooting

### Import fails with permission error
- Ensure you have the `local/reuseunit:export` capability in the source course
- Ensure you have the `local/reuseunit:import` capability in the destination course

### Templates not showing
- Check that the template's share level allows your access
- Personal templates are only visible to the creator
- Category templates require you to have a role in that category

### JavaScript not working
- Clear Moodle caches: Site Administration > Development > Purge caches
- Check browser console for errors
- Ensure AMD modules are compiled: `npm run build`

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## License

This plugin is licensed under the [GNU GPL v3](https://www.gnu.org/licenses/gpl-3.0.en.html).

## Author

**hyukudan**

- GitHub: [@hyukudan](https://github.com/hyukudan)

## Changelog

### v0.1.0 (Initial Release)
- Basic section import functionality
- Template system with sharing levels
- Multi-language support (EN/ES)
- Course edit mode integration

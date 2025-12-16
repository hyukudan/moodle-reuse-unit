# Changelog

All notable changes to the Moodle Reuse Unit plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.3.0] - 2024-12-16

### Added
- **Batch Import**: Import multiple sections at once
- Import queue panel with add/remove functionality
- Batch mode toggle in advanced search
- Progress tracking for batch operations
- Maximum sections limit (configurable)

### Changed
- Import wizard now supports both single and batch modes
- Section search results can be added to queue in batch mode

## [0.2.0] - 2024-12-16

### Added
- **Duplicate Section**: Duplicate sections within the same course with position options
- **Export Section**: Export individual sections as .mbz backup files for download
- **Advanced Search**: Search sections across all courses by name and activity type
- Activity type filters: Quiz, Assignment, Forum, Page, File, URL, Lesson, H5P, SCORM
- New UI buttons in course edit mode: Duplicate, Export

### Changed
- Enhanced import wizard with advanced search panel
- Improved section selection with activity type badges

## [0.1.0] - 2024-12-16

### Added
- Initial release of the Reuse Unit plugin
- Import sections from any accessible course
- Visual preview of section contents before import
- Import options:
  - Reset activity dates
  - Include/exclude access restrictions
  - Include/exclude gradebook structure
- Quick import button in course edit mode
- Template system:
  - Save sections as reusable templates
  - Three sharing levels: Personal, Category, Global
  - Search and browse templates
  - Usage tracking
- Import history tracking
- Favorites system for frequently used sections
- Multi-language support:
  - English (en)
  - Spanish (es)
  - Brazilian Portuguese (pt_br)
- PHPUnit and Behat tests
- AMD JavaScript modules with ES6
- Responsive UI with dark mode support

### Technical
- Compatible with Moodle 4.0+
- Uses Moodle's backup/restore API internally
- Web services for AJAX operations
- Three database tables: history, favorites, templates
- Three capabilities for access control

[Unreleased]: https://github.com/hyukudan/moodle-reuse-unit/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/hyukudan/moodle-reuse-unit/releases/tag/v0.1.0

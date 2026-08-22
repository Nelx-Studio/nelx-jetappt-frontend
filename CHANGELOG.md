# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.9] - 2026-08-20

### Added
- Native appointment listings with table and grid display formats
- Interactive calendar view widget for appointment browsing
- Staff Appointments widget (table and grid views)
- Client Appointments widget (table and grid views)
- Appointments Calendar widget for monthly booking overview
- Enhanced appointment info modal with flexible field configuration
- Payment status management system with REST API endpoint
- New shortcodes: `[nelx_staff_appointments]`, `[nelx_client_appointments]`, `[nelx_staff_appointments_grid]`, `[nelx_client_appointments_grid]`
- Cron job testing and diagnostics tools for setup verification
- Manual cron confirmation option for shared hosting environments
- Improved file checking, CLI testing, and crontab detection for cron validation

### Changed
- Refined Elementor widget system with improved performance
- Enhanced timezone handling for client-local time display throughout the plugin
- Improved settings sanitization and data validation across all configuration categories
- Better Google Meet integration with reduced debug logging for cleaner operation
- Enhanced JSON handling for complex nested appointment data structures
- Improved REST API endpoints for better programmatic access to appointment data

### Fixed
- Better cron job detection for improved reliability
- Enhanced error handling in Google Meet integration
- More accurate timezone conversion for appointment times
- Improved email notification system robustness
- Enhanced widget rendering performance

## [1.0.0] - 2026-06-23

### Added
- Initial release of Jet Appointments Frontend Manager
- Schedule editor with custom working hours, days off, and custom schedules
- Provider and client action buttons for appointment management
- Google Meet integration for online appointments
- Email notification system with automated emails for appointment events
- In-app notification system for real-time appointment updates
- Elementor widgets for drag-and-drop page building
- Timezone support for accurate client and provider scheduling
- Full internationalization (i18n) support for multi-language compatibility

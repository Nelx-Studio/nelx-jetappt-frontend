# Nelx JetAppointments Frontend Manager

A powerful WordPress plugin that provides a complete frontend management experience for the [JetAppointments](https://crocoblock.com/plugins/jetappointment/) booking system. Manage appointments, schedules, and client interactions seamlessly from your WordPress frontend.

**Contributors:** Astariko  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

## Features

### Appointment Management
- **Appointment Listings** – Display appointments in table and grid formats for both staff and clients
- **Calendar View** – Browse appointments with an interactive monthly calendar widget
- **Schedule Editor** - Providers can manage working hours, days off, and custom schedules from the frontend
- **Provider Action Buttons** - Confirm, reject, reschedule, and view appointment details
- **Client Action Buttons** - Reschedule, cancel, and view appointment information
- **Enhanced Info Modal** - Flexible field configuration with customizable appointment details and payment status management

### Communication & Notifications
- **Google Meet Integration** - Automatically generate Google Meet links for online appointments
- **Email Notifications** - Automated emails for new appointments, confirmations, cancellations, rescheduling, and reminders
- **In-app Notifications** - Real-time notifications for appointment events
- **Timezone Support** - Handles client and provider timezones for accurate scheduling and local time display

### Frontend Customization
- **Elementor Widgets** - Drag-and-drop widgets including:
  - Staff Appointments (Table & Grid)
  - Client Appointments (Table & Grid)
  - Calendar View
  - Schedule Editor
  - Provider & Client Action Buttons
  - Google Meet Settings
- **Shortcodes** - Add appointment management components anywhere on your site

### Administrative Features
- **Payment Status Management** - Update and track appointment payment status via admin interface
- **Cron Job Testing** - Enhanced diagnostics for cron job detection and manual confirmation
- **Settings Management** - Comprehensive settings page for configuration and customization
- **REST API Endpoints** - Programmatic access to appointment data and payment status updates

## Requirements

- WordPress 6.2 or higher
- PHP 7.4 or higher
- JetAppointments Booking plugin installed and activated

## Installation

1. Upload the `nelx-jetappt-frontend` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Navigate to **Nelx Appointments** in the WordPress admin menu to configure settings
4. Ensure JetAppointments plugin is installed and activated
5. Add shortcodes or Elementor widgets to your pages

## Quick Start

### Using Shortcodes

Add the following shortcodes to any page or post:

```
[nelx_schedule_editor]
[nelx_provider_action_buttons] -legacy
[nelx_client_action_buttons] - legacy
[nelx_google_meet_settings]
[nelx_staff_appointments]
[nelx_client_appointments]
[nelx_staff_appointments_grid]
[nelx_client_appointments_grid]
```

### Using Elementor Widgets

1. Edit a page with Elementor
2. Search for **Nelx** in the widget finder
3. Drag and drop any of these widgets:
   - Schedule Editor
   - Staff Appointments (Table or Grid view)
   - Client Appointments (Table or Grid view)
   - Provider Action Buttons
   - Client Action Buttons
   - Google Meet Settings

### Configuring Google Meet Integration

1. Go to **Nelx Appointments** → **Google Meet Settings** in the WordPress admin
2. Enter your Google API Client ID and Client Secret
3. Providers can then connect their Google accounts from the frontend
4. Google Meet links will automatically generate for online appointments

### Managing Appointment Details

The enhanced Info Modal allows customizable display of appointment information:
- View client/provider details
- Update payment status (providers)
- Manage appointment actions (reschedule, cancel, confirm)
- Display timezone-aware appointment times

## FAQ

**Do I need JetAppointments to use this plugin?**

Yes, this plugin is an extension for JetAppointments and requires it to be installed and activated.

**How do email notifications work?**

The plugin sends automated emails for:
- New appointments (to providers)
- Appointment confirmations (to clients)
- Appointment cancellations (to both parties)
- Appointment rescheduling (to both parties)
- Appointment reminders (to clients)

You can customize all email templates from the settings page.

**Does this work with Elementor?**

Yes, the plugin includes multiple Elementor widgets that can be dragged and dropped into any Elementor page for complete appointment management.

**How do I display appointments for clients and staff?**

Use the **Staff Appointments** or **Client Appointments** widgets/shortcodes. Choose between table and grid display formats based on your design preferences.

**How does the calendar view work?**

The **Appointments Calendar** widget displays a monthly calendar. Users can click on dates to view appointments scheduled for that day, providing an intuitive overview of the booking schedule.

**Can I customize the appointment information displayed?**

Yes, the Info Modal is fully customizable. Configure which fields appear and how they're displayed from the plugin settings.

## Troubleshooting

**"JetAppointments plugin is not activated"**
- Ensure the JetAppointments Booking plugin is installed and activated in WordPress admin

**Google Meet links not generating**
- Verify your Google API credentials are correctly configured
- Check that providers have authorized their Google accounts
- Ensure the provider's Google account has Google Meet access

**Emails not being sent**
- Check your WordPress mail configuration
- Verify email templates are not disabled in settings
- Check your hosting provider's email sending limits

**Cron jobs not running**
- Navigate to **Nelx Appointments** → **Diagnostics**
- Use the cron testing tools to verify your setup
- For shared hosting, use the manual cron confirmation option
- Check your hosting provider's cron job configuration

**Appointments not displaying**
- Verify the correct shortcode or widget is being used
- Confirm JetAppointments plugin is activated and has appointments
- Check user permissions and filter settings
- Ensure timezone settings are correctly configured

## Changelog

### [1.1.9] - 2026-08-20

**Major Features**

- Native appointment listings with table and grid display formats
- Interactive calendar view for appointment browsing
- Enhanced appointment info modal with flexible field configuration
- Payment status management system with REST API endpoint
- Improved cron job diagnostics and testing tools

**Improvements**

- Refined Elementor widget system with new appointment listing widgets
- Enhanced timezone handling for client-local time display
- Improved settings sanitization and data validation
- Better Google Meet integration with reduced debug logging
- Enhanced cron handler with file checking, CLI testing, and crontab detection
- Improved JSON handling for complex nested appointment data

### [1.0.0] - 2026-06-23

**Initial Release**

- Schedule editor with custom working hours and days off
- Provider and client action buttons
- Google Meet integration
- Email notification system
- In-app notification system
- Elementor widgets
- Timezone support
- Full i18n (internationalization) support

See [CHANGELOG.md](CHANGELOG.md) for detailed version history.

## Versioning

This project adheres to [Semantic Versioning](https://semver.org/). For version history, see [CHANGELOG.md](CHANGELOG.md) and [Releases](https://github.com/Nelx-Studio/nelx-jetappt-frontend/releases).

## License

This plugin is licensed under the GPLv2 or later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html) for details.

## Support

For issues, questions, or feedback, please open an issue on [GitHub](https://github.com/Nelx-Studio/nelx-jetappt-frontend/issues).

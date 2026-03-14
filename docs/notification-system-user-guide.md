# Notification System - User Guide

## Overview

The Notification System allows you to receive and manage notifications through multiple channels (Email, In-App, SMS). You can customize which notifications you receive and how you receive them.

## Table of Contents

1. [Accessing Notification Settings](#accessing-notification-settings)
2. [Notification Channels](#notification-channels)
3. [Notification Types](#notification-types)
4. [Managing Preferences](#managing-preferences)
5. [Do Not Disturb](#do-not-disturb)
6. [Notification Inbox](#notification-inbox)
7. [Troubleshooting](#troubleshooting)

## Accessing Notification Settings

1. Click on your profile picture in the top-right corner
2. Select "Profile" from the dropdown menu
3. Click on the "Notification Settings" tab

Alternatively, you can access notification settings directly at: `/notification-settings`

## Notification Channels

The system supports three notification channels:

### 1. Email Notifications
- Notifications sent to your registered email address
- Includes rich formatting and attachments
- Can be replied to (for certain notification types)

### 2. In-App Notifications
- Notifications displayed within the application
- Appear in the notification bell icon (top navigation)
- Can be marked as read/unread
- Support actions (click to view details)

### 3. SMS Notifications
- Text messages sent to your mobile phone
- Limited to 160 characters
- Requires mobile number verification

## Notification Types

### Attendance Notifications
- **Attendance Check-In**: Sent when you check in for work
- **Attendance Check-Out**: Sent when you check out from work
- **Late Check-In**: Sent when you check in after your scheduled time
- **Early Departure**: Sent when you check out before your scheduled time

### Report Notifications
- **Report Generated**: Sent when a report you requested is ready
- **Report Generation Failed**: Sent when report generation fails
- **Scheduled Report**: Sent for regularly scheduled reports

### Appointment Notifications
- **Appointment Reminder**: Sent before scheduled appointments
- **Appointment Confirmation**: Sent when appointments are confirmed
- **Appointment Cancellation**: Sent when appointments are cancelled

### System Notifications
- **System Announcement**: Important system updates and announcements
- **Welcome Message**: Sent when you first create an account
- **Password Reset**: Sent when you request a password reset
- **Security Alert**: Sent for security-related events

## Managing Preferences

### Enabling/Disabling Notifications

1. Go to Notification Settings
2. For each notification type, you'll see toggle switches for each channel
3. Toggle the switch to enable or disable notifications for that channel

### Channel Preferences

Each channel has additional preferences:

#### Email Preferences
- **Priority**: High, Normal, Low (affects email delivery speed)
- **Format**: HTML or Plain Text
- **Attachments**: Include or exclude attachments

#### In-App Preferences
- **Sound**: Choose notification sound
- **Vibration**: Enable/disable vibration
- **Display Duration**: How long notifications stay visible
- **Auto-dismiss**: Automatically dismiss after reading

#### SMS Preferences
- **Delivery Time**: Immediate or Scheduled
- **Character Limit**: Truncate long messages

### Setting Preferences

1. Click on the "Preferences" button next to any notification type
2. Configure your preferred settings for each channel
3. Click "Save" to apply changes

## Do Not Disturb

The Do Not Disturb feature allows you to silence notifications during specific times.

### Setting Up Do Not Disturb

1. Go to Notification Settings
2. Scroll to the "Do Not Disturb" section
3. Enable Do Not Disturb
4. Configure:
   - **Start Time**: When Do Not Disturb begins
   - **End Time**: When Do Not Disturb ends
   - **Days**: Which days to apply Do Not Disturb
   - **Exceptions**: Specific dates to override Do Not Disturb

### How It Works

- Notifications received during Do Not Disturb hours are queued
- Queued notifications are delivered after Do Not Disturb ends
- Emergency notifications (security alerts) may bypass Do Not Disturb
- You'll receive a summary of missed notifications when Do Not Disturb ends

## Notification Inbox

### Accessing Your Inbox

1. Click the bell icon in the top navigation bar
2. This opens your notification inbox
3. You can also access it at: `/notifications/inbox`

### Managing Notifications

#### Reading Notifications
- Click on any notification to view details
- Notifications are marked as read when viewed
- You can mark as unread if needed

#### Filtering Notifications
- **All**: Show all notifications
- **Unread**: Show only unread notifications
- **By Type**: Filter by notification type
- **By Date**: Filter by date range

#### Actions
- **Mark as Read**: Mark selected notifications as read
- **Mark All as Read**: Mark all notifications as read
- **Delete**: Remove notifications from your inbox
- **Clear All**: Remove all notifications

#### Notification Actions
Some notifications include actions:
- **View Report**: Opens generated reports
- **Confirm Appointment**: Confirms appointment requests
- **Acknowledge**: Acknowledges receipt of notification
- **Snooze**: Remind me later

## Bulk Operations

### Marking Multiple Notifications
1. Check the boxes next to notifications
2. Use the bulk action buttons at the top
3. Choose "Mark as Read", "Delete", or other actions

### Exporting Notifications
1. Go to Notification Settings
2. Click "Export Notifications"
3. Choose format (CSV, PDF)
4. Select date range
5. Download the file

## Mobile Access

### Mobile App
- Notifications are available in the mobile app
- Push notifications for urgent messages
- Same settings sync across devices

### Mobile Browser
- Responsive design for mobile browsers
- Touch-friendly interface
- Offline access to recent notifications

## Troubleshooting

### Common Issues

#### Not Receiving Notifications
1. Check your notification settings are enabled
2. Verify your email address is correct
3. Check spam/junk folders for email notifications
4. Ensure Do Not Disturb is not active
5. Verify mobile number for SMS notifications

#### Too Many Notifications
1. Adjust notification frequency in settings
2. Disable less important notification types
3. Use Do Not Disturb during busy hours
4. Set up notification filters

#### Notifications Marked as Spam
1. Add system email to your contacts
2. Whitelist the domain in your email client
3. Check email authentication settings

#### Mobile Notifications Not Working
1. Enable push notifications in app settings
2. Check battery optimization settings
3. Ensure app has notification permissions
4. Update to latest app version

### Getting Help

If you continue to experience issues:

1. **Contact Support**: Email support@example.com
2. **System Status**: Check `/status` for system notifications
3. **Documentation**: Visit `/docs` for more guides
4. **Feedback**: Use the feedback form in the app

## Best Practices

### For Optimal Experience
1. **Enable Multiple Channels**: Use both email and in-app for important notifications
2. **Set Priorities**: Mark critical notifications as high priority
3. **Regular Review**: Review notification settings monthly
4. **Use Do Not Disturb**: Set quiet hours for better focus
5. **Clean Inbox**: Regularly clear old notifications

### Security Tips
1. **Verify Notifications**: Be cautious of unexpected notifications
2. **Report Suspicious Activity**: Report any suspicious notifications
3. **Keep Contact Info Updated**: Ensure email and phone are current
4. **Use Strong Passwords**: Protect your account access

## Frequently Asked Questions

### Q: Can I receive notifications on multiple devices?
A: Yes, notifications sync across all devices where you're logged in.

### Q: How long are notifications stored?
A: Notifications are stored for 90 days. Important notifications should be saved externally.

### Q: Can I customize notification sounds?
A: Yes, in the In-App notification preferences.

### Q: Are there notification limits?
A: Yes, to prevent spam. Contact support if you need higher limits.

### Q: Can I schedule notifications?
A: Some notification types can be scheduled. Check individual notification settings.

### Q: How do I unsubscribe from all notifications?
A: Go to Notification Settings and click "Disable All Notifications". Note: some critical system notifications cannot be disabled.

## Updates and Changes

The notification system is regularly updated. You'll receive notifications about:
- New notification types
- System maintenance
- Feature updates
- Policy changes

Check the System Announcements section regularly for updates.

---

*Last Updated: December 2024*  
*Version: 2.0*  
*For more help, contact: support@example.com*
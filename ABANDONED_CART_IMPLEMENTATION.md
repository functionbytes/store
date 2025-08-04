# Abandoned Cart Email System Implementation

## Overview

A comprehensive abandoned cart email recovery system has been implemented for the Botble CMS e-commerce platform. This system automatically detects when customers abandon their shopping carts and sends targeted recovery emails to encourage them to complete their purchases.

## 🛠️ Components Implemented

### 1. Email Templates

#### Modern Abandoned Cart Template (`abandoned_cart.tpl`)
- **Location**: `platform/plugins/ecommerce/resources/email-templates/abandoned_cart.tpl`
- **Features**:
  - Eye-catching design with emojis and modern styling
  - Personalized customer greeting
  - Urgency messaging with limited-time offers
  - Product list display
  - Call-to-action buttons
  - Free shipping incentive
  - Customer support information

#### Enhanced Classic Template (`order_recover.tpl`)
- **Location**: `platform/plugins/ecommerce/resources/email-templates/order_recover.tpl`
- **Improvements**:
  - Better messaging and personalization
  - Enhanced visual appeal
  - Added incentive sections

### 2. Command Line Interface

#### Enhanced SendAbandonedCartsEmailCommand
- **Location**: `platform/plugins/ecommerce/src/Commands/SendAbandonedCartsEmailCommand.php`
- **Features**:
  - Configurable time windows (minimum and maximum hours)
  - Batch processing limits
  - Template selection
  - Dry-run mode for testing
  - Progress bar for visual feedback
  - Detailed logging and error handling
  - Prevention of duplicate emails

**Usage Examples**:
```bash
# Basic usage (default settings)
php artisan cms:abandoned-carts:email

# Custom settings
php artisan cms:abandoned-carts:email --hours=2 --max-hours=72 --limit=100

# Dry run to preview
php artisan cms:abandoned-carts:email --dry-run

# Use different template
php artisan cms:abandoned-carts:email --template=order_recover
```

### 3. Queue Job System

#### SendAbandonedCartEmailJob
- **Location**: `platform/plugins/ecommerce/src/Jobs/SendAbandonedCartEmailJob.php`
- **Features**:
  - Queued email processing for better performance
  - Retry mechanism (3 attempts)
  - Comprehensive error handling
  - Order history tracking
  - Email validation

### 4. Admin Configuration System

#### Settings Form
- **Location**: `platform/plugins/ecommerce/src/Forms/Settings/AbandonedCartSettingForm.php`
- **Settings Available**:
  - Enable/disable abandoned cart emails
  - Time delays and windows
  - Email templates selection
  - Batch limits
  - Email frequency controls
  - Free shipping offers
  - Category exclusions

#### Settings Controller
- **Location**: `platform/plugins/ecommerce/src/Http/Controllers/Settings/AbandonedCartSettingController.php`
- **Features**:
  - Settings management
  - Test email functionality
  - Bulk email sending
  - Dry-run preview
  - AJAX-powered interface

#### Admin Interface
- **Location**: `platform/plugins/ecommerce/resources/views/settings/abandoned-cart.blade.php`
- **Features**:
  - User-friendly settings panel
  - Real-time settings toggle
  - Test email functionality
  - Preview abandoned carts
  - Manual email triggering

### 5. Validation and Requests

#### AbandonedCartSettingRequest
- **Location**: `platform/plugins/ecommerce/src/Http/Requests/Settings/AbandonedCartSettingRequest.php`
- **Validation Rules**:
  - Time boundaries (1-72 hours for delays, 24-720 hours for max age)
  - Email limits (1-500 per batch)
  - Template validation
  - Email format validation

### 6. Localization

#### Language Support
- **Location**: `platform/plugins/ecommerce/resources/lang/en/setting.php`
- **Added Translations**:
  - All abandoned cart settings labels
  - Help texts and descriptions
  - Validation messages
  - Success/error messages

## 🔧 Configuration Options

### Basic Settings
- **Enable/Disable**: Toggle abandoned cart emails on/off
- **Delay Hours**: Wait time before sending first email (1-72 hours)
- **Maximum Age**: Don't email carts older than this (24-720 hours)
- **Email Limit**: Maximum emails per batch run (1-500)

### Email Settings
- **Template Selection**: Choose between modern or classic templates
- **Subject Line**: Customizable email subject
- **Maximum Emails**: Limit emails per cart (1-10)
- **Email Interval**: Time between reminder emails (1-168 hours)
 
### Advanced Options
- **Free Shipping Offer**: Include free shipping incentive
- **Category Exclusions**: Skip certain product categories
- **Test Email**: Send test emails to verify setup

## 🚀 Usage Instructions

### 1. Enable the System
1. Go to Admin → E-commerce → Settings → Abandoned Cart
2. Toggle "Enable abandoned cart emails" to ON
3. Configure your preferred settings
4. Save the settings

### 2. Set Up Automation
Add to your cron job scheduler:
```bash
# Run every hour
0 * * * * php /path/to/your/project/artisan cms:abandoned-carts:email
```

### 3. Test the System
1. Use the test email feature in admin settings
2. Run dry-run mode: `php artisan cms:abandoned-carts:email --dry-run`
3. Preview abandoned carts in the admin interface

### 4. Monitor Performance
- Check Laravel logs for email activity
- Review order histories for email tracking
- Monitor email delivery rates
        
## 📊 Email Logic

### Detection Criteria
An order is considered "abandoned" if:
- ✅ `is_finished` = 0 (incomplete order)
- ✅ Has products in the cart
- ✅ Has customer email address
- ✅ Created within the time window (between min and max hours ago)
- ✅ Hasn't received an abandoned cart email recently

### Email Scheduling
- **First Email**: Sent after the configured delay (default: 1 hour)
- **Follow-up Emails**: Sent at configured intervals (default: 24 hours)
- **Maximum Emails**: Configurable limit per cart (default: 3)
- **Exclusions**: Orders that were completed or already received recent emails

## 🎯 Features & Benefits

### For Customers
- **Personalized Experience**: Emails address customers by name
- **Visual Appeal**: Modern, responsive email templates
- **Clear CTAs**: Easy-to-find "Complete Purchase" buttons
- **Incentives**: Free shipping and time-limited offers
- **Product Reminders**: Show exactly what they left behind

### for Merchants
- **Easy Configuration**: User-friendly admin interface
- **Flexible Scheduling**: Customizable timing and frequency
- **Performance Monitoring**: Built-in logging and tracking
- **Template Options**: Choose between different email styles
- **Batch Processing**: Efficient handling of large volumes
- **Queue Integration**: Non-blocking email sending

### Technical Benefits
- **Scalable**: Uses Laravel queues for performance
- **Reliable**: Retry mechanisms and error handling
- **Secure**: Proper validation and CSRF protection
- **Maintainable**: Clean, documented code following Laravel conventions
- **Testable**: Dry-run modes and test email functionality

## 🔍 Integration Points

### Database Tables Used
- `ec_orders`: Main order data
- `ec_order_histories`: Email tracking and logging
- `ec_customers`: Customer information
- `ec_order_addresses`: Shipping/billing addresses
- `ec_order_products`: Cart contents

### Email System Integration
- Uses Botble's EmailHandler system
- Integrates with existing email templates
- Follows the same variable system as other order emails

### Settings Integration
- Integrates with Botble's settings system
- Uses the same form patterns as other e-commerce settings
- Follows the plugin's localization system

## 🛡️ Security Considerations

- All inputs are validated and sanitized
- CSRF protection on all forms
- Email addresses are validated before sending
- Rate limiting through batch size controls
- Secure token-based cart recovery links

## 📈 Performance Optimizations

- Queue-based email sending prevents blocking
- Configurable batch sizes for memory management
- Database query optimization with proper indexes
- Prevents duplicate email sending
- Efficient date-based filtering

## 🔮 Future Enhancements

Potential improvements that could be added:
- A/B testing for email templates
- Advanced segmentation rules
- Recovery analytics dashboard
- SMS integration
- Dynamic discount codes
- Machine learning for optimal send times
- Multi-language template support

---

## 🚧 Final Setup Steps

After implementing all components, follow these steps to activate the system:

### 1. Clear Laravel Cache
```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

### 2. Access Admin Panel
1. Navigate to **Admin Panel → Settings → E-commerce → Abandoned Cart**
2. You should see the new "Abandoned Cart" option in the settings menu

### 3. Configure Settings
- Enable abandoned cart emails
- Set delay times (recommended: 1-2 hours initial delay)
- Choose email template (modern template recommended)
- Configure limits and intervals
- Test with the built-in test email feature

### 4. Set Up Automation
Add to your server's cron job:
```bash
0 * * * * cd /path/to/your/project && php artisan cms:abandoned-carts:email >> /dev/null 2>&1
```

### 5. Monitor Performance
- Check Laravel logs for email activity
- Use the admin interface to send test emails
- Monitor email delivery rates and cart recovery metrics

---

The abandoned cart email system is now fully functional and ready for production use. Regular monitoring and optimization based on open rates and conversion metrics is recommended for best results.

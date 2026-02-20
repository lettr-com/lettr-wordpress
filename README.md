# Lettr – Email API Plugin for WordPress

Send transactional and marketing emails from your WordPress site using the [Lettr](https://lettr.com) email API. Lettr replaces the default WordPress email system with a reliable, developer-friendly API that delivers emails to inboxes — not spam folders.

## Why Lettr?

- **Reliable email delivery** — Built on battle-tested infrastructure with SPF, DKIM, and DMARC authentication out of the box.
- **Simple setup** — Connect your WordPress site with a single API key. No SMTP configuration needed.
- **Transactional email at scale** — Send password resets, order confirmations, notifications, and more through the [Lettr email API](https://lettr.com).
- **Real-time tracking** — Monitor opens, clicks, bounces, and deliverability from the [Lettr dashboard](https://lettr.com).
- **Templates & personalization** — Use the Lettr drag-and-drop editor and merge tags for dynamic content.
- **Developer-first** — RESTful API, detailed [documentation](https://docs.lettr.com), and SDKs for PHP, Node.js, Python, Go, Rust, Java, and Laravel.

## Requirements

- WordPress 5.8 or higher
- PHP 7.2 or higher
- A [Lettr](https://lettr.com) account and API key

## Install

**Option A: Upload via WordPress Admin Panel**

1. Download the plugin as a ZIP.
2. In your WordPress admin panel, go to **Plugins → Add Plugin → Upload Plugin**, upload the ZIP, press **Install**, and activate the plugin once installed.

**Option B: Manual install**

1. Clone or extract the plugin into `/wp-content/plugins/lettr`.
2. Activate the plugin via the `Plugins` page.

## Usage

1. Once the plugin is activated, you are automatically redirected to the plugin's setup page.
2. Follow the step-by-step guide on the page to connect [Lettr](https://lettr.com) to your site.
3. Enter your API key from the [Lettr dashboard](https://lettr.com) and configure your sender name and email address.
4. Send a test email to verify everything is working.

All outgoing WordPress emails (`wp_mail`) will now be sent through the Lettr API automatically — including emails from WooCommerce, contact form plugins, and any other plugin that uses the standard WordPress mail function.

## Documentation

- [Lettr Documentation](https://docs.lettr.com)
- [API Reference](https://docs.lettr.com/api-reference/introduction)
- [Send Email API](https://docs.lettr.com/api-reference/emails/send-email)
- [Domain Setup Guide](https://docs.lettr.com/learn/domains/introduction)

## Support

For questions, bug reports, or feature requests, visit [lettr.com](https://lettr.com).

## License

GPL-2.0-or-later

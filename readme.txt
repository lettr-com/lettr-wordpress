=== Lettr - Email API ===
Contributors: vojtechj
Tested up to: 6.9
Stable tag:   1.0.0
License:      GPL-2.0-or-later
Tags:         email, smtp, transactional email, email api, lettr

Send transactional emails from your site through the Lettr email API.

== Description ==

Send transactional and marketing emails from your WordPress site using the [Lettr](https://lettr.com) email API. Lettr replaces the default WordPress email system with a reliable, developer-friendly API that delivers emails to inboxes — not spam folders.

= Why Lettr? =

* **Reliable email delivery** — Built on battle-tested infrastructure with SPF, DKIM, and DMARC authentication out of the box.
* **Simple setup** — Connect your WordPress site with a single API key. No SMTP configuration needed.
* **Transactional email at scale** — Send password resets, order confirmations, notifications, and more through the Lettr email API.
* **Real-time tracking** — Monitor opens, clicks, bounces, and deliverability from the Lettr dashboard.
* **Templates & personalization** — Use the Lettr drag-and-drop editor and merge tags for dynamic content.
* **Developer-first** — RESTful API, detailed documentation, and SDKs for PHP, Node.js, Python, Go, Rust, Java, and Laravel.

= How it works =

This plugin overrides the default WordPress `wp_mail()` function and routes all outgoing emails through the Lettr API. This means every email your site sends — including emails from WooCommerce, contact form plugins, and any other plugin — is delivered through Lettr automatically.

= Links =

* [Lettr Website](https://lettr.com)
* [Documentation](https://docs.lettr.com)
* [API Reference](https://docs.lettr.com/api-reference/introduction)

== Installation ==

1. Upload the plugin to `/wp-content/plugins/lettr` or install via the WordPress plugin installer.
2. Activate the plugin through the **Plugins** menu.
3. Go to **Settings → Lettr** and enter your API key from the [Lettr dashboard](https://lettr.com).
4. Configure your sender name and email address.
5. Send a test email to verify everything is working.

== External services ==

This plugin connects to the Lettr API at `app.lettr.com` to deliver email on behalf of your WordPress site. Each time `wp_mail()` is invoked, the plugin sends the following data to Lettr over HTTPS so that Lettr can process and deliver the message:

* The configured sender email address and name
* Recipient, CC, BCC, and Reply-To addresses
* The email subject, HTML and/or plain text body, and any attachments
* Any custom email headers, tags, or metadata supplied by the calling code
* Your Lettr API key (sent as a Bearer token in the `Authorization` header for authentication)

The plugin also calls `app.lettr.com/api/auth/check` to validate the API key when you save it on the settings screen.

This service is provided by Lettr. By using this plugin you agree to Lettr's terms and acknowledge their privacy practices:

* Terms of Service: [https://lettr.com/terms](https://lettr.com/terms)
* Privacy Policy: [https://lettr.com/privacy-policy](https://lettr.com/privacy-policy)

== Frequently Asked Questions ==

= Do I need a Lettr account? =

Yes. Sign up for free at [lettr.com](https://lettr.com) to get your API key.

= Does this work with WooCommerce? =

Yes. Lettr handles all emails sent via the standard WordPress `wp_mail()` function, which includes WooCommerce order confirmations, shipping notifications, and customer emails.

= Do I need to configure SMTP? =

No. Lettr uses a REST API instead of SMTP, which means there are no ports to open or SMTP credentials to manage.

= Where can I find my API key? =

Log in to your account at [lettr.com](https://lettr.com) and navigate to the API Keys section.

== Changelog ==

= 1.0.0 =
* Initial release.

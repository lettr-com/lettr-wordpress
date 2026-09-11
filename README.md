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

## For developers

The plugin bundles `Lettr_Api` (`class-lettr-api.php`), a self-contained PHP client that mirrors the full [Lettr API](https://docs.lettr.com/api-reference/introduction) — every operation in the OpenAPI spec maps to exactly one public method. The plugin itself only uses email sending and auth-check; the remaining surfaces (domains, webhooks, templates, projects, campaigns, and the `audience/*` lists, contacts, topics, properties, and segments) are intentional SDK surface for your own integrations, not dead code. They are bundled because WordPress plugins can't rely on Composer at runtime.

```php
$lettr = new Lettr_Api(); // uses the API key saved in plugin settings

// Subscribe a new commenter / customer to an audience list:
$lettr->create_audience_contact( array(
    'email'   => $user->user_email,
    'list_id' => 'lst_123',
) );

$result = $lettr->list_audience_contacts( array( 'per_page' => 50 ) );
```

Every method returns the decoded JSON array on success, `true` on `204 No Content`, or a `WP_Error` on failure.

### Retrying a send safely

`wp_mail()` gives you no way to tell a timeout from a failure — the first attempt may well have been delivered and only the response was lost. Return a stable key from the `lettr_idempotency_key` filter and the retry replays the original result instead of sending a second email:

```php
add_filter( 'lettr_idempotency_key', function ( $key, $body, $atts ) {
    return 'order-' . get_the_ID() . '-receipt';
}, 10, 3 );
```

This is opt-in on purpose. Deriving a key automatically would mean hashing the payload, and two legitimately identical notifications — the same alert fired twice an hour apart — would collapse into one silently dropped email. Only your code knows whether a repeat is a retry or a genuine second message.

Derive the key from what the send is *about*, not from a timestamp or `wp_generate_uuid4()` — those differ on the retry and defeat the mechanism entirely. Keys are kept 24 hours and scoped per team and API key.

`Lettr_Api::send_email()` takes the key as a second argument if you are calling the client directly.

### Templates, folders and purpose

A template is either **transactional** (the default — receipts, password resets, alerts) or **campaign** (marketing sent to an audience list). A campaign can only send a template whose purpose is `campaign`, and **the purpose cannot be changed after creation**. Pass it when creating one:

```php
$lettr->create_template( array(
    'name'    => 'March newsletter',
    'html'    => $html,
    'purpose' => 'campaign',
) );
```

`list_folders()` lists the folders templates are filed into, with each folder's purpose and template count. It is the only source of a folder id — nothing else in the API returns one. A folder's purpose is independent of its templates': filing a template in a campaign folder does not make the template a campaign template.

`list_templates()` accepts `purpose` and `folder_id` filters. A folder outside the resolved project is an error rather than an empty list, so a wrong id cannot be mistaken for an empty folder.

Template responses carry `preparation_status` (`pending`, `ready`, `failed`). Imported templates render asynchronously, so a template can exist before it is sendable. After an *update* the previous render keeps serving until the new one settles — a pending template still sends, just not yet the new content.

**Note:** the plugin's own admin UI does not create templates; it sends mail and checks auth. These are client methods for your own integrations.

## Documentation

- [Lettr Documentation](https://docs.lettr.com)
- [API Reference](https://docs.lettr.com/api-reference/introduction)
- [Send Email API](https://docs.lettr.com/api-reference/emails/send-email)
- [Domain Setup Guide](https://docs.lettr.com/learn/domains/introduction)

## Support

For questions, bug reports, or feature requests, visit [lettr.com](https://lettr.com).

## License

GPL-2.0-or-later

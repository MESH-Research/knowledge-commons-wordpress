# Reply By Email (BuddyPress group discussions)

Knowledge Commons lets members reply to BuddyPress group activity and
discussion notifications directly from their email client. This is provided by
the bundled **BP Reply By Email** (RBE) plugin in
`plugins/bp-reply-by-email/`, using [SparkPost](https://www.sparkpost.com/) as
the inbound-email provider.

This document explains how the feature works end to end, how SparkPost is
configured, what has to be set up for a given network/domain, and how to
diagnose the common failure mode where "nothing fires."

> ## ⚠️ The dev environment cannot test reply-by-email
>
> Reply-by-email depends on SparkPost making an **inbound HTTP POST into the
> site** (the relay webhook — hop 3 below). **The dev environment is not
> externally accessible**, so SparkPost has no route to reach it and the webhook
> can never be delivered. This is a hard limitation of the architecture, not a
> configuration bug:
>
> - You can configure everything correctly — inbound domain, MX records, relay
>   webhook, RBE settings — and replies will **still** never post on dev, because
>   the final hop physically cannot complete.
> - Therefore **do not expect to verify reply-by-email end to end on dev.**
>   The feature can only be exercised on an environment SparkPost can reach over
>   the public internet (staging or production).
> - Dev is still useful for verifying the *outbound* half (that notifications go
>   out with correctly-encoded reply-to addresses) and the SparkPost-side
>   configuration, but the round trip cannot be completed there.

## Operating mode: Inbound, not IMAP

RBE supports two modes:

- **IMAP** — the site connects to an IMAP mailbox and polls it for replies.
- **Inbound** — replies are routed through a third-party inbound-email provider
  that parses the message and POSTs it to the site as a webhook.

**Knowledge Commons uses Inbound mode with SparkPost.** The only RBE
configuration that lives in this repository is the SparkPost webhook token,
defined in `site/config/application.php`:

```php
Config::define( 'BP_RBE_SPARKPOST_WEBHOOK_TOKEN', getenv( 'BP_RBE_SPARKPOST_WEBHOOK_TOKEN' ) );
Config::define( 'BP_RBE_DEBUG', getenv( 'BP_RBE_DEBUG' ) );
```

IMAP-mode settings (mail server, port, keep-alive, etc.) on the admin page are
**not** used on KC and can be ignored.

## How it works, end to end

Reply-by-email is a three-hop chain. A break in any hop means replies silently
fail to post.

### 1. Outbound — the encoded reply-to address

When BuddyPress sends a group notification, RBE rewrites the reply-to address to
an *encoded address at the network's inbound domain*. See
`bp_rbe_inject_qs_in_email()` in `includes/bp-rbe-functions.php`:

```
THEQUERYSTRING@<inbound-domain>      // e.g. a1b2c3...@post.hcommons-dev.org
```

`THEQUERYSTRING` is an encrypted token (`bp_rbe_encode()`) that encodes the
replying user, the target group, and the item being replied to. It is signed
with the **Key** set in the RBE admin settings — a reply encoded under one key
cannot be decoded under another.

### 2. Inbound mail → SparkPost

The member replies; their mail client sends to `…@<inbound-domain>`. For that
mail to reach SparkPost rather than bouncing:

- The inbound domain's **MX records must point at SparkPost's inbound relay
  hosts** (`rx1.sparkpostmail.com`, `rx2.sparkpostmail.com`).
- The domain must be registered as an **Inbound Domain** in SparkPost. Unlike
  *sending* domains, inbound domains have **no DKIM/ownership challenge to
  "verify"** — an inbound domain is simply created via the API, and it becomes
  functional once its MX records resolve to SparkPost and a relay webhook is
  bound to it.

### 3. SparkPost → the site (relay webhook)

SparkPost parses the inbound message and POSTs it as JSON to a **Relay Webhook**
URL on the site. On the WordPress side, RBE catches the request on `wp_loaded`
at priority `0` (`includes/bp-rbe-hooks.php`):

```php
add_action( 'wp_loaded', 'bp_rbe_inbound_catch_callback', 0 );
```

The callback runs on *every* request, but the SparkPost parser
(`includes/classes/bp-reply-by-email-inbound-provider-sparkpost.php`) only acts
when **both** of the following are true:

1. `Content-Type: application/json`, and
2. the request carries the `X-MessageSystems-Webhook-Token` header.

It then compares that header against `BP_RBE_SPARKPOST_WEBHOOK_TOKEN`. **If the
token does not match, the request is logged and `die()`d before anything is
parsed.** On success it looks up the WordPress user by the message's `from`
address, decodes the querystring to resolve the group/item, and posts the reply.

Because the catch runs on `wp_loaded`, the relay webhook can target essentially
any URL on the site (the site home URL is fine) — there is no dedicated endpoint
path.

> This third hop is exactly why **dev cannot test the feature**: SparkPost must
> open a connection *to* the site from the public internet, and dev is not
> publicly reachable. See the warning at the top of this document.

## What must be configured for a domain/network

Reply-by-email is **not** self-provisioning. Nothing in the plugin registers a
domain automatically. Each network/domain needs all of the following.

> **Multi-network caveat.** The RBE admin page is *not* network-aware (see the
> `@todo` in `includes/bp-rbe-admin.php` and the `bp_is_root_blog()` guard in
> `setup_admin()`). Settings are stored per root blog via
> `bp_get_option( 'bp-rbe' )`. KC runs several networks (hcommons, hastac,
> hcommons-dev, …), each with its own root blog, so **each network needs its own
> RBE settings filled in** — they are not inherited from production.

### DNS (for the reply domain)

- MX records pointing at SparkPost's inbound relay hosts:

  ```
  <reply-domain>.  MX  10  rx1.sparkpostmail.com.
  <reply-domain>.  MX  10  rx2.sparkpostmail.com.
  ```

### SparkPost

- Register the reply domain as an **Inbound Domain**.
- Create a **Relay Webhook** that matches that domain and POSTs to the site's
  URL, carrying an **auth token**.

See [Configuring SparkPost](#configuring-sparkpost) below for the exact API
calls.

### WordPress / environment (on the target network's root blog)

- Define `BP_RBE_SPARKPOST_WEBHOOK_TOKEN` (read via `getenv()` in
  `application.php`) and make it **identical** to the token configured on the
  SparkPost relay webhook.
- In the BP Reply By Email admin settings:
  - **Mode** = Inbound
  - **Provider** = SparkPost
  - **Inbound Domain** = the reply domain (must exactly match the SparkPost
    inbound domain and the MX records)
  - Note the **Key** — replies are only decodable under the key they were
    encoded with.

## Configuring SparkPost

Inbound domains and relay webhooks are managed through the **SparkPost API**, not
the web dashboard. All calls below assume `$SPARKPOST_API_KEY` is exported and an
API key with the *Inbound Domains* and *Relay Webhooks* permissions. EU accounts
use `https://api.eu.sparkpost.com` instead of `https://api.sparkpost.com`.

### Naming convention and the dev reply domain

Production/staging networks use a `reply.<network>` label (e.g.
`reply.hcommons-staging.org`). For **dev**, the `reply.hcommons-dev.org` label is
**unavailable** — it collides with another SparkPost resource of the same name
(see [Domain namespace conflicts](#domain-namespace-conflicts-error-1602)
below). The dev reply domain is therefore **`post.hcommons-dev.org`**.

### Shared auth token

All KC relay webhooks share a single `auth_token`. Whatever value the target
environment has in `BP_RBE_SPARKPOST_WEBHOOK_TOKEN` must be the value used in its
relay webhook — they have to match exactly or the parser rejects the POST.

### Create the inbound domain

```bash
# confirm the label is free (expect a 404)
curl -s https://api.sparkpost.com/api/v1/inbound-domains/post.hcommons-dev.org \
  -H "Authorization: $SPARKPOST_API_KEY" | jq

# create it
curl -sX POST https://api.sparkpost.com/api/v1/inbound-domains \
  -H "Authorization: $SPARKPOST_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"domain": "post.hcommons-dev.org"}'
```

The inbound domain can be created before DNS is in place; SparkPost will not
relay mail until the MX records resolve, but the registration itself succeeds.

### Create the relay webhook

```bash
curl -sX POST https://api.sparkpost.com/api/v1/relay-webhooks \
  -H "Authorization: $SPARKPOST_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
        "name": "HCommons Dev Replies Webhook",
        "target": "https://hcommons-dev.org",
        "auth_token": "<value of BP_RBE_SPARKPOST_WEBHOOK_TOKEN in target env>",
        "match": { "protocol": "SMTP", "domain": "post.hcommons-dev.org" }
      }'
```

### Listing / inspecting existing configuration

```bash
# inbound domains (account-global)
curl -s https://api.sparkpost.com/api/v1/inbound-domains \
  -H "Authorization: $SPARKPOST_API_KEY" | jq

# relay webhooks (master scope; pass X-MSYS-SUBACCOUNT: <id> for a subaccount)
curl -s https://api.sparkpost.com/api/v1/relay-webhooks \
  -H "Authorization: $SPARKPOST_API_KEY" | jq

# sending domains — useful when diagnosing namespace conflicts
curl -s https://api.sparkpost.com/api/v1/sending-domains/<domain> \
  -H "Authorization: $SPARKPOST_API_KEY" | jq
```

## Troubleshooting "nothing fires"

> Remember the overriding constraint: **on dev, "nothing fires" is expected** —
> SparkPost cannot reach a non-public host, so the relay webhook never arrives.
> The steps below apply to publicly-reachable environments (staging/production).

Walk the three hops in order; each has a distinct symptom.

### 1. Turn on debug logging

`BP_RBE_DEBUG` is already wired in `application.php`. With it enabled, a
successful webhook hit logs:

```
- SparkPost webhook received -
- Webhook parsing completed -
```

A token mismatch logs `SparkPost token verification failed.` and then stops.
**No log line at all** on a reply attempt means SparkPost never reached the site
— the problem is upstream (hop 2, the relay webhook, or — on dev — the host not
being publicly reachable), not in WordPress.

### 2. Trace each hop

- **Hop 2 (mail → SparkPost):** Check SparkPost's inbound/event logs. Did the
  reply arrive at SparkPost at all? If not, the MX/inbound-domain registration is
  the issue even if the MX record *exists* — confirm the domain is present as an
  **Inbound Domain** in SparkPost and that MX resolves to the relay hosts.
- **Hop 3 (SparkPost → site):** Check SparkPost's **relay webhook delivery log**.
  Is a relay webhook configured for this domain, pointed at the correct site URL,
  and is the site **publicly reachable**? A missing/misaddressed webhook — or an
  unreachable host — stops everything here. Confirm the webhook's `auth_token`
  matches `BP_RBE_SPARKPOST_WEBHOOK_TOKEN` in that environment; a mismatch shows
  as `SparkPost token verification failed.` in the site log.
- **WordPress settings:** Confirm the network's root blog has Mode = Inbound,
  Provider = SparkPost, and **Inbound Domain** set to the reply domain. If
  outbound notifications carry a malformed or empty reply-to address, this
  setting is missing — replies would be undeliverable regardless of the relay
  configuration.

### Domain namespace conflicts (error 1602)

When creating an inbound domain, SparkPost may reject it with:

```json
{ "errors": [ { "message": "resource conflict",
  "description": "An inbound domain with similar attributes already exists.",
  "code": "1602" } ] }
```

This means the label is already claimed **somewhere in the account by a resource
of any type** — an inbound domain, a CNAME/MX-verified sending or bounce domain,
a tracking domain, or a relay-webhook match. Crucially, the conflicting resource
may be **invisible to your `GET /inbound-domains` listing**: that list is
master-scoped, and the clash can be with a *different resource type* that shares
the name.

A domain (and its subdomains) can only be claimed once across an account and its
subaccounts, so a `GET` for the exact inbound domain can return 404 while the
create still conflicts. To diagnose, check each resource type for the label:

```bash
for kind in inbound-domains sending-domains tracking-domains; do
  echo "== $kind =="
  curl -s https://api.sparkpost.com/api/v1/$kind/<label> \
    -H "Authorization: $SPARKPOST_API_KEY" | jq '.results // .errors'
done
# and check whether any relay webhook already matches the label:
curl -s https://api.sparkpost.com/api/v1/relay-webhooks \
  -H "Authorization: $SPARKPOST_API_KEY" | jq '.results[].match'
```

**Resolution:** either remove the conflicting resource (only if it is unused —
e.g. a stray tracking domain on a dev host), or **choose a different reply
label**. The latter is why dev uses `post.hcommons-dev.org` rather than
`reply.hcommons-dev.org`: the `reply.` label was already claimed and the
low-risk fix was to pick a fresh, unclaimed label. Any label works as long as
its MX points at SparkPost, an inbound domain + relay webhook exist for it, and
RBE's **Inbound Domain** setting matches it exactly.

## Key files

| Path | Purpose |
| --- | --- |
| `plugins/bp-reply-by-email/includes/bp-rbe-admin.php` | Admin settings page (mode, provider, inbound domain, key). |
| `plugins/bp-reply-by-email/includes/bp-rbe-hooks.php` | Registers `bp_rbe_inbound_catch_callback` on `wp_loaded`. |
| `plugins/bp-reply-by-email/includes/bp-rbe-functions.php` | Address encoding (`bp_rbe_inject_qs_in_email`, `bp_rbe_encode`) and webhook dispatch. |
| `plugins/bp-reply-by-email/includes/classes/bp-reply-by-email-inbound-provider-sparkpost.php` | SparkPost webhook parser and token verification. |
| `site/config/application.php` | Defines `BP_RBE_SPARKPOST_WEBHOOK_TOKEN` and `BP_RBE_DEBUG`. |

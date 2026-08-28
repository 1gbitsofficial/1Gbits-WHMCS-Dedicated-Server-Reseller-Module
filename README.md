# 1Gbits Dedicated Server — WHMCS Reseller Provisioning Module

The official WHMCS provisioning module for reselling **1Gbits dedicated servers**. It
connects WHMCS to the 1Gbits reseller API so servers are ordered, suspended,
unsuspended, upgraded and terminated straight from the WHMCS service page, with live
server details shown to both staff and clients.

- **Version:** 1.0.0
- **Requires:** WHMCS 8.x, PHP 7.4 – 8.3, the `curl` and `json` PHP extensions
- **Dependencies:** none at runtime
- **License:** [MIT](LICENSE)

---

## Contents

```
whmcs.json                              # Marketplace manifest
logo.png                                # 200x200 listing icon
modules/
  servers/
    onegbits/
      onegbits.php                      # WHMCS module commands
      README.md                         # Short in-install reference
      lib/
        ApiClient.php                   # cURL client for the 1Gbits API
        Helper.php                      # Config, persistence and normalisation
      templates/
        clientarea.tpl                  # Client area overview panel
tests/                                  # PHPUnit suite for the helper layer
```

---

## Installation

1. Copy `modules/servers/onegbits/` into your WHMCS installation, so the module file
   sits at `modules/servers/onegbits/onegbits.php`.

2. **Add the API server.** Go to **System Settings → Servers → Add New Server** and set:

   | Field | Value |
   | --- | --- |
   | Name | `1Gbits API` |
   | Hostname | `api.1gbits.com` |
   | Server Type | `1Gbits Dedicated Server (Reseller)` |
   | Username | your **API key** |
   | Access Hash | your **API secret** |
   | Secure (SSL) | enabled |

   Click **Test Connection** to confirm connectivity before saving.

   > The hostname also accepts a path (`api.1gbits.com/v2`) or a full URL
   > (`https://api.1gbits.com/v1`). With a bare host, `/v1` is appended.

3. **Create the product.** Go to **System Settings → Products/Services → Create a New
   Product**, choose product type *Dedicated/VPS Server*, then on the **Module
   Settings** tab select module `1Gbits Dedicated Server (Reseller)`, assign the server
   group containing the server from step 2, and configure:

   - **Plan SKU** — the 1Gbits plan identifier, e.g. `DEDI-INTEL-E3`
   - **Location** — datacenter code (`ams`, `lon`, `nyc`, `ist`, `tyo`)
   - **OS Template** — installed at provisioning time
   - **Auto Assign IP** — let the API allocate a primary IP

4. **Optional order fields.** Add product custom fields if you want customers or staff
   to influence the order:

   - **RAID Level** — dropdown, e.g. `None,RAID1,RAID10`
   - **Additional IPs** — text or dropdown, numeric
   - **Order Notes** — text area

   These are read at provisioning time and are entirely optional.

### Upgrading from a pre-1.0.0 install

Config option slots were re-ordered in 1.0.0 (the first three used to hold the API
base URL, key and secret). After copying the new files in, open each existing product's
**Module Settings** tab, clear any stale values, re-enter **Plan SKU**, **Location**,
**OS Template** and **Auto Assign IP**, and move the API credentials onto the server
record as described above. Existing services keep working: the module falls back to a
**Server ID** custom field when no service property is present.

> **Where are the API credentials?** On the *server*, not the product — so a single set
> of credentials is shared by every product and **Test Connection** works. WHMCS calls
> `TestConnection` from the server configuration page, where product config options do
> not exist, which is why credentials cannot live there.

---

## How the server ID is stored

`CreateAccount` reads the server ID from the API response and saves it against the
service using WHMCS **service properties**. Every later command (suspend, unsuspend,
terminate, change package, admin tab, client area) reads it back from there. No manual
custom field is required.

For services created before 1.0.0, the module falls back to reading a service custom
field named **Server ID**, so existing installs keep working.

---

## Features

| Command | Behaviour |
| --- | --- |
| Create | `POST /reseller/orders`, then persists the returned server ID |
| Suspend | `POST /reseller/servers/{id}/suspend` |
| Unsuspend | `POST /reseller/servers/{id}/unsuspend` |
| Terminate | `DELETE /reseller/servers/{id}` |
| Change Package | `POST /reseller/servers/{id}/change-plan` |
| Admin Services tab | `GET /reseller/servers/{id}` — status, hostname, IP, plan, OS |
| Client area | `GET /reseller/servers/{id}` rendered via `templates/clientarea.tpl` |
| Test Connection | `GET /reseller/ping` |

---

## API authentication

Every request carries:

- `X-API-KEY` — the server Username
- `X-SIGNATURE` — `hash_hmac('sha256', "METHOD\nPATH\nBODY", <server Access Hash>)`

If your 1Gbits account uses a different scheme, adjust `ApiClient::headers()` and
`ApiClient::sign()` in `modules/servers/onegbits/lib/ApiClient.php`.

---

## Logging and security

- Every API exchange is written to **Utilities → Logs → Module Log** via
  `logModuleCall()`, with the API key, password and access hash passed as replacement
  variables so they are **masked** in the log.
- Client area errors are deliberately generic; API diagnostics stay in the module log.
- TLS peer and host verification are always on, and redirects are not followed.
- Restrict your API key to the WHMCS server's IP address and rotate credentials
  periodically.

---

## Development

```bash
composer install
composer test          # PHPUnit suite for the helper layer
php -l modules/servers/onegbits/onegbits.php
```

The helper layer is deliberately free of WHMCS globals so it can be unit tested outside
a WHMCS installation.

---

## Troubleshooting

| Symptom | Cause |
| --- | --- |
| *API credentials are not configured* | Username / Access Hash missing on the server record |
| *Test Connection* fails | Firewall, wrong hostname, or the API key is not allowed from this IP |
| *No 1Gbits server ID is recorded* | Create never completed successfully — check the module log |
| Provisioning succeeds but no server ID returned | The API response shape differs; check `Helper::extractServerId()` |
| Client area panel is unstyled | Template belongs at `modules/servers/onegbits/templates/clientarea.tpl` |

---

## Extending

Add further module commands (reboot, OS reinstall, reverse DNS, rescue mode) by adding
`onegbits_<Command>()` functions plus `onegbits_AdminCustomButtonArray()` and
`onegbits_ClientAreaCustomButtonArray()` entries.

---

## Support

- Documentation and issues: <https://github.com/1gbitsofficial/1Gbits-WHMCS-Dedicated-Server-Reseller-Module>
- 1Gbits support: <https://my.1gbits.com/submitticket.php>

See [CHANGELOG.md](CHANGELOG.md) for release history.

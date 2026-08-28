# 1Gbits Dedicated Server (Reseller) — WHMCS Module

Version 1.0.0 · MIT licensed · requires WHMCS 8.x and PHP 7.4+

## Quick setup

1. **System Settings → Servers → Add New Server**
   - Hostname: `api.1gbits.com`
   - Server Type: `1Gbits Dedicated Server (Reseller)`
   - Username: your **API key**
   - Access Hash: your **API secret**
   - Secure (SSL): enabled
   - Click **Test Connection**.

2. **System Settings → Products/Services** — create a *Dedicated/VPS Server* product,
   set the module to `1Gbits Dedicated Server (Reseller)`, assign the server group, and
   configure **Plan SKU**, **Location**, **OS Template** and **Auto Assign IP**.

3. *(Optional)* Add product custom fields **RAID Level**, **Additional IPs** and
   **Order Notes** to pass extra order options through to the API.

API credentials live on the server record, not the product, so they are shared by every
product and **Test Connection** works.

## Server ID

`CreateAccount` persists the server ID returned by the API as a WHMCS service property.
All other commands read it back from there — no manual custom field needed. Services
created before 1.0.0 fall back to a **Server ID** custom field.

## Files

```
onegbits.php              WHMCS module commands
lib/ApiClient.php         cURL client for the 1Gbits reseller API
lib/Helper.php            Config, persistence and response normalisation
templates/clientarea.tpl  Client area overview panel
```

## Logs

API exchanges are recorded in **Utilities → Logs → Module Log**. Credentials are masked.

Full documentation, changelog and support links:
<https://github.com/1gbitsofficial/1Gbits-WHMCS-Dedicated-Server-Reseller-Module>

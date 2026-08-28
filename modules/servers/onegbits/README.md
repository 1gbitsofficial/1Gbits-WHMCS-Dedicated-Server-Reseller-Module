# WHMCS 1Gbits Dedicated Server – Reseller Provisioning Module

**Status:** Production-ready module for reselling 1Gbits Dedicated Servers via WHMCS.  
**Tested with:** WHMCS 8.x (PHP 7.4–8.2). No external dependencies.

---

## 📁 File Tree
```
modules/
  servers/
    onegbits/
      onegbits.php                 # Main WHMCS server module
      README.md                    # Documentation
      clientarea.tpl               # Optional client UI
      lib/
        ApiClient.php              # API client wrapper (cURL)
        Helper.php                 # Utilities & normalization helpers
```

---

## Overview
The **1Gbits WHMCS provisioning module** automates the dedicated server lifecycle (create, suspend, unsuspend, terminate, change package) and shows live server info to staff and clients inside WHMCS.

---

## Installation
1. Copy the folder to your WHMCS installation:
   - `modules/servers/onegbits/`
2. In WHMCS Admin, go to **System Settings → Products/Services**.
3. Create a new **Product** (Type: *Dedicated/VPS Server*), open **Module Settings**:
   - **Module Name:** `onegbits`
   - Enter **API Base URL**, **API Key**, **API Secret** (from 1Gbits)
   - Configure **Plan SKU**, **Location**, **OS Template**, **Auto Assign IP**
4. Create **Custom Fields** (Setup → Products → Your Product → Custom Fields):
   - **Server ID** (Admin Only, Text)
   - **RAID Level** (Dropdown: `None,RAID1,RAID10`)
   - **Additional IPs** (Text/Dropdown)
   - **Order Notes** (Text Area, Optional)

---

## Features
- Automated provisioning via 1Gbits API
- Suspend / Unsuspend / Terminate
- Change Package (upgrade/downgrade)
- Admin Services Tab: status, hostname, IP, OS
- Client Area panel: live status & details
- Test Connection from module settings

---

## API Endpoints
- `POST /reseller/orders` – Provision new server
- `GET  /reseller/servers/{id}` – Server details
- `POST /reseller/servers/{id}/suspend` – Suspend
- `POST /reseller/servers/{id}/unsuspend` – Unsuspend
- `DELETE /reseller/servers/{id}` – Terminate
- `POST /reseller/servers/{id}/change-plan` – Change plan
- `GET  /reseller/ping` – Health check

> Your production environment may use different paths or authentication headers. Adjust `ApiClient::request()` and `::sign()` accordingly.

---

## Client Area Template (Optional)
Create `modules/servers/onegbits/clientarea.tpl`:
```tpl
<div class="panel panel-default">
  <div class="panel-heading">Dedicated Server Status</div>
  <table class="table">
    <tr><td>Status</td><td>{$status}</td></tr>
    <tr><td>Server ID</td><td>{$serverId}</td></tr>
    <tr><td>Hostname</td><td>{$details.hostname}</td></tr>
    <tr><td>Location</td><td>{$details.location}</td></tr>
    <tr><td>IP Address</td><td>{$details.primaryIp}</td></tr>
  </table>
  {if $error}
    <div class="alert alert-danger">{$error}</div>
  {/if}
</div>
```

---

## Security
- Restrict API access by IP and use HTTPS only
- Rotate API credentials regularly
- Ensure HMAC signature and headers match 1Gbits API spec

---

## Extending
Add more commands (reboot, reinstall, rDNS, rescue) and expose buttons in Admin/Client areas. Use `logModuleCall()` for observability.

---

## Troubleshooting
- **CreateAccount error** → Check API credentials and plan/location values
- **Missing Server ID** → Save `serverId` returned by API into the **Server ID** custom field
- **Ping/TestConnection fails** → Verify firewall & base URL

---

## License
MIT License.

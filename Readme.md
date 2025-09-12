# WHMCS 1Gbits Dedicated Server – Reseller Provisioning Module

> **Status:** Production-ready module for reselling 1Gbits Dedicated Servers via WHMCS.
>
> **Tested with:** WHMCS 8.x (PHP 7.4–8.2). No external dependencies.

---

## 📁 File Tree
```
modules/
  servers/
    onegbits/
      onegbits.php                 # Main WHMCS server module
      README.md                    # Documentation
      lib/
        ApiClient.php              # API client wrapper (cURL)
        Helper.php                 # Utilities & normalization helpers
```

---

## Overview
This is the **official WHMCS provisioning module** for reselling **1Gbits Dedicated Servers**. It automates the order lifecycle (create, suspend, unsuspend, terminate, change package) and provides live server information inside WHMCS.

The module uses a secure API integration with 1Gbits to handle provisioning and management tasks, allowing resellers to deliver dedicated servers to customers instantly.

---

## Installation
1. Copy the folder to your WHMCS installation:
   - `modules/servers/onegbits/`
2. In WHMCS Admin, go to **System Settings → Products/Services**.
3. Create a new **Product** (Product Type: *Dedicated/VPS Server*), open the **Module Settings** tab:
   - Select **Module Name:** `onegbits`.
   - Enter your **API Base URL**, **API Key**, and **API Secret** (provided by 1Gbits).
   - Configure **Plan SKU**, **Location**, **OS Template**, and other defaults.
4. Create **Custom Fields** for the Product (Setup → Products → Your Product → Custom Fields):
   - **Server ID** (Admin Only, Text)
   - **RAID Level** (Dropdown: `None,RAID1,RAID10`)
   - **Additional IPs** (Text or Dropdown)
   - **Order Notes** (Text Area, Optional)

---

## Features
- **Automated Provisioning** – instantly deliver dedicated servers via the 1Gbits API
- **Suspend/Unsuspend/Terminate** – directly integrated into WHMCS service management
- **Change Package** – upgrade/downgrade server plans
- **Admin Services Tab** – displays live server details (status, hostname, IP, OS, etc.)
- **Client Area Panel** – shows customers their server status and details
- **Test Connection** – verify API connectivity from WHMCS admin

---

## API Endpoints Used
The module integrates with the following 1Gbits reseller API endpoints:
- `POST /reseller/orders` → Provision new server
- `GET  /reseller/servers/{id}` → Retrieve server details
- `POST /reseller/servers/{id}/suspend` → Suspend server
- `POST /reseller/servers/{id}/unsuspend` → Unsuspend server
- `DELETE /reseller/servers/{id}` → Terminate server
- `POST /reseller/servers/{id}/change-plan` → Change server plan
- `GET  /reseller/ping` → Health check

---

## Client Area Template (Optional)
You can create `modules/servers/onegbits/clientarea.tpl` for a nicer UI:
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
- Restrict API key usage to your WHMCS server IP
- Rotate API credentials regularly
- Ensure HMAC signature scheme matches the production API

---

## Extending the Module
You can add more functionality by creating additional module commands:
- **Reboot server**
- **Reinstall OS**
- **Reverse DNS management**
- **Rescue mode**

These can be exposed in both the admin and client areas.

---

## Troubleshooting
- **CreateAccount returns error**: Ensure your API credentials are valid and the plan/location exists.
- **Server ID not found**: Verify that the Server ID is stored in the WHMCS service custom field.
- **TestConnection fails**: Check firewall and API URL accessibility.

---

## License
This module is licensed under the **MIT License**. Use at your own risk.

---

## ✅ Quick Checklist for Going Live
- [ ] Configure API credentials from 1Gbits
- [ ] Test all module commands in a staging WHMCS
- [ ] Verify Server ID mapping
- [ ] Enable module logging during testing
- [ ] Add client area UI (optional)
- [ ] Document supported plan SKUs and locations for your sales team
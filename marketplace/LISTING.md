# 1Gbits Dedicated Server (Reseller)

**Automatically provision and manage 1Gbits dedicated servers directly from WHMCS.**

The official 1Gbits provisioning module for WHMCS resellers. It connects WHMCS to the 1Gbits reseller API so dedicated servers are ordered, suspended, unsuspended, upgraded and terminated straight from the WHMCS service page — with live server details shown to both your staff and your clients.

## Key Features

* **Automated provisioning** — a new 1Gbits dedicated server is ordered the moment an order is accepted, with the plan, datacenter location, OS template and IP allocation taken from your product settings.
* **Full lifecycle management** — *Suspend*, *Unsuspend*, *Terminate* and *Change Package* work directly from the WHMCS admin service page.
* **Live admin overview** — status, hostname, primary IP, plan and operating system are displayed on the admin **Services** tab.
* **Client area panel** — customers see their server details in a clean panel that fits both the **Six** and **Twenty-One** themes.
* **Optional order fields** — let customers choose a RAID level, additional IPs or leave order notes via product custom fields.
* **Test Connection** — verify your API credentials from **System Settings → Servers** before you sell.
* **Secure logging** — every API exchange is written to the WHMCS Module Log with the API key and secret **masked**.

## How It Works

1. Add `api.1gbits.com` as a server in **System Settings → Servers** using your 1Gbits **API key** and **API secret**.
2. Create a *Dedicated/VPS Server* product, select the **1Gbits Dedicated Server (Reseller)** module and choose a **Plan SKU**, **Location** and **OS Template**.
3. Start selling — WHMCS handles ordering, billing, suspension and termination automatically.

Credentials are stored once on the server record and shared by every product, so there is nothing to re-enter when you add new plans.

## Requirements

* WHMCS **8.x**
* PHP **7.4 – 8.3** with the `curl` and `json` extensions
* An active [1Gbits reseller account](https://www.1gbits.com/) with API access

## Supported Actions

| WHMCS action | Result |
| --- | --- |
| Create | Orders a new dedicated server and stores its ID against the service |
| Suspend / Unsuspend | Suspends or restores the server |
| Terminate | Cancels the server |
| Change Package | Upgrades or downgrades the plan |
| Admin Services tab | Live status, hostname, IP, plan and OS |
| Client area | Server details panel for the customer |
| Test Connection | Confirms API connectivity |

## Installation

Upload the `modules/servers/onegbits/` directory to your WHMCS installation, add the 1Gbits API server, and assign the module to your product. Full step-by-step instructions are in the [documentation](https://github.com/1gbitsofficial/1Gbits-WHMCS-Dedicated-Server-Reseller-Module#readme).

## Pricing & License

* **Free** — released under the MIT license.
* Source code is available on [GitHub](https://github.com/1gbitsofficial/1Gbits-WHMCS-Dedicated-Server-Reseller-Module).

## Support

* Documentation & issue tracker: [GitHub](https://github.com/1gbitsofficial/1Gbits-WHMCS-Dedicated-Server-Reseller-Module/issues)
* 1Gbits support desk: [my.1gbits.com](https://my.1gbits.com/submitticket.php)
* Website: [www.1gbits.com](https://www.1gbits.com/)

# Changelog

All notable changes to this module are documented in this file. This project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-08-28

First Marketplace release.

### Added
- `whmcs.json` module manifest, 200x200 `logo.png`, MIT `LICENSE` and this changelog.
- The provisioned server ID is now persisted against the service via WHMCS
  service properties, so Suspend, Unsuspend, Terminate, Change Package, the
  admin Services tab and the client area work after ordering.
- `templates/clientarea.tpl` rewritten for the Twenty-One theme with Bootstrap
  card markup, per-value HTML escaping and empty-value placeholders.
- PHPUnit test suite covering the helper layer.

### Changed
- API credentials moved from per-product config options to the WHMCS server
  record (Username = API key, Access Hash = API secret). This makes
  **Test Connection** work, which previously always failed because product
  config options are not available on the server configuration page.
- The API base URL is derived from the server hostname, SSL flag and port. The
  hostname accepts a bare host, a host with a path, or a full URL.
- Product config options converted to the named form with `FriendlyName`, so
  reordering them no longer shifts `configoptionN` values.
- `ApiClient` now sets a connect timeout, a versioned user agent and explicit
  TLS verification, and returns a normalised result with a readable error
  message parsed from the API response.
- Client area errors are now generic; API diagnostics stay in the module log.
- Server IDs are URL-encoded before being interpolated into request paths.
- Documentation consolidated into the repository README.

### Upgrade notes
- Config option slots were re-ordered. Re-enter each existing product's Module
  Settings values and move the API credentials to the server record.
- Services provisioned before 1.0.0 continue to work via the **Server ID**
  custom field fallback.

### Security
- API key, password and access hash are passed to `logModuleCall()` as
  replacement variables so they are masked in the WHMCS module log.

### Removed
- `onegbits_ChangePassword()`, which only returned an error. WHMCS hides the
  feature when the function is absent.

[1.0.0]: https://github.com/1gbitsofficial/1Gbits-WHMCS-Dedicated-Server-Reseller-Module/releases/tag/v1.0.0

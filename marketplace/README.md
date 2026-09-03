# WHMCS Marketplace listing assets

Everything needed to (re)submit the listing at <https://marketplace.whmcs.com/>.

| File | Use it for |
| --- | --- |
| `LISTING.md` | Paste into the listing **Description** field. It only uses formatting from the [Marketplace Markdown guide](https://marketplace.whmcs.com/help/marketplace/markdown-guide): headings, bold, italics, lists, tables and links. |
| `icon-512.png` | Upload as the listing **icon**. 512 × 512, rendered from the vector source in `src/icon.svg`, so it is sharp at any size. |
| `screenshots/01-client-area-twenty-one.png` | Screenshot 1 — the module's client area panel in the **Twenty-One** theme (1440 × 960). |
| `screenshots/02-client-area-six.png` | Screenshot 2 — the same panel in the **Six** theme (1440 × 960). |

The repository-root `logo.png` (200 × 200, referenced by `whmcs.json`) is rendered
from the same `src/icon.svg`.

## Regenerating

The screenshots are the module's `templates/clientarea.tpl` output wrapped in a
Twenty-One / Six page shell with sample data (`src/twentyone.html`, `src/six.html`).
To re-render everything after a change:

```bash
cd marketplace/src
NODE_PATH=$(npm root -g) node render.js
```

Requires Node.js with the `playwright` package and a Chromium build available to it.

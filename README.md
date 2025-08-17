
![Logo](https://avatars.githubusercontent.com/u/93708739?v=4)


# LOOKING GLASS

Modern Looking Glass for PHP 7–8.1 with auto-detected server details, dynamic theme handling, and an improved UI.

## Authors

- [@maven_htx](https://instagram.com/noxhostingio)


## Installation
Download Project as Zip Unzip and UPLOAD TO HOST

[DOWNLOAD ZIP FROM GITHUB]() 

Install Script Run command below


```bash


chmod 777 ./configure.sh

then run ./configure.sh

```
Follow Prompts to install required packages and Test Files    
 
Note: do not commit real database credentials. `db_config.php` is now a placeholder and will be written by `install.php` during setup on the target server.
## Demo

[Looking Glass](https://lg.denver.kalixhosting.com) 

![Looking Glass](https://kalixhosting.com/img/lookingglass.png)

## Features and Improvements

- Theme handling
  - Automatic application of the site theme with safe user overrides.
  - Removed theme selection from `admin.php` to prevent confusion and ensure consistency.
  - Frontend honors a stored user choice but resets it when the admin default changes.
  - Theme switching supported via `theme-switcher.js` with `data-theme` on `<html>`.

- Server details auto-detection
  - Public IPv4 of the host is detected automatically (via `api.ipify.org` with IPv4-only resolution; fallbacks to `ipv4.icanhazip.com`, `$_SERVER['SERVER_ADDR']`, and hostname resolution).
  - Location is fetched from `ipinfo.io/json` to populate city/region/country and map query.
  - Detected values override configured defaults at runtime so the UI shows real data.

- Admin panel changes
  - Theme selection removed in `admin.php`.
  - Configuration saving streamlined; location list and speedtest fields kept.

- UI/UX enhancements
  - Subtle borders and spacing around buttons so adjacent controls are visually distinct.
  - Smooth theme transitions; accessible focus styles; optional theme toggle.

## How it works

- `config.php` reads settings from MySQL via `db_manager.php`.
- `bootstrap.php` assembles `templateData` and performs:
  - IPv4 auto-detection and location lookup.
  - Safe fallbacks when external services are unreachable.
- `index.php` sets `data-theme` early, reconciling user overrides with the admin default.
- `theme-switcher.js` prefers the server-provided theme, stores user changes in `localStorage`, and exposes `window.LGTheme` helpers.
- `themes.css` defines light/dark variables and the refined button appearance.

## Configuration Notes

- Database: this project uses MySQL for configuration storage.
- External calls: outbound HTTPS is needed to `api.ipify.org`, `ipv4.icanhazip.com`, and `ipinfo.io` for auto-detection. If outbound is blocked, UI falls back to configured defaults.

## Development

- Frontend assets: `themes.css`, `theme-switcher.js`.
- PHP entrypoints: `index.php`, `bootstrap.php`, `admin.php`.
- Database helpers: `db_manager.php`.

## Security

- CSRF token protection on form submissions.
- Prepared statements for DB updates/reads.

## Troubleshooting

- If IPv4 shows 127.0.0.1, your host may lack outbound connectivity or only expose private networking. Ensure the server can reach `api.ipify.org` over IPv4.
- If location is empty, confirm connectivity to `https://ipinfo.io/json`.
- If theme looks stuck, clear the `localStorage` keys `lg-theme` and `lg-theme-default` in your browser and reload.
### Credits
This project is inspired by the [LookingGlass project](https://github.com/telephone/LookingGlass) of @telephone and uses his procExecute() function, although slightly modified.

And to [@Hybula](https://github.com/hybula/lookingglass) for Bootstrap Styled

### License
Mozilla Public License Version 2.0
## 🔗 Links
KALIXHOSTING https://kalixhosting.com/

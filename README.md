# RFID Vehicle Tracking & Student Identification

Campus gate entry/exit tracking system: an ESP32 with an RFID reader logs a scanned card to a PHP/MySQL backend, which identifies the student and their registered vehicle and shows a live dashboard at the security post.

Write-ups: [`docs/Draft Paper.pdf`](docs/Draft%20Paper.pdf), [`docs/EL 5Th SEm.pdf`](docs/EL%205Th%20SEm.pdf), [`docs/Evara Phase 1.pdf`](docs/Evara%20Phase%201.pdf). `Resources/` has the literature-review papers on RFID and IoT in supply chain / access control that the design draws on.

## How it works

```
RFID card tap → ESP32 (MFRC522 reader) → WiFi → HTTP POST to verify.php
                                                        │
                                                  MySQL lookup (uid → user + vehicle)
                                                        │
                                    toggle entry/exit based on last logged access
                                                        │
                                          response: GRANTED|name|type|vehicle|number
                                                        │
                                        ESP32 displays result (LCD/OLED variant)
                                        Security dashboard shows live access log
```

- **Firmware** (`.ino` files, Arduino/ESP32 + `MFRC522` + `WiFi`/`HTTPClient` libraries): reads the card UID, POSTs it to `rfid_system/verify.php`, and prints the response over serial (or to an LCD/OLED, depending on the sketch). Several sketch variants exist for different hardware setups on the bench:
  - `final_code/final_code.ino` — baseline, serial output only
  - `everthing_plus_lcd/` and `everything_plus_oled/` — same flow with a physical display attached
  - `final_code_mobile_hotspot/` — same flow, configured for a phone hotspot instead of a fixed router
  - `read_and_website_no_oled_led/` — reads the card and hits the server without display/LED feedback
  - `get_rfid/get_rfid.ino` — minimal sketch that just prints a scanned card's UID, used to register new cards into the database
- **Backend** (`rfid_system/`, PHP + MySQL, built for XAMPP):
  - `verify.php` — looks up the UID, toggles entry/exit based on the vehicle's last logged access, inserts an `access_logs` row, returns a pipe-delimited response to the ESP32
  - `admin/` — login-gated panel to add/edit/delete registered users and vehicles, and view the access log (`add_user.php`, `edit_user.php`, `delete_user.php`, `view_users.php`, `view_logs.php`)
  - `security/index.php` — live dashboard for the security post: today's entry/exit counts, current occupancy (entries minus exits), and the most recent scan with the cardholder's photo
  - `security/get_live_data.php` — polling endpoint the dashboard calls for live updates
- **`Website/`** — a separate marketing-style landing page/pitch deck (`index.html`, `database.html`) describing the system as a "Smart Campus Solution," not the working admin app itself.

## Setup

1. Import the MySQL schema (`users`, `access_logs` tables referenced by the PHP files — no `.sql` dump is checked in, create the tables to match the columns used in `config.php`/`verify.php`/`admin/*.php` before first run).
2. Serve `rfid_system/` with XAMPP (Apache + MySQL), default DB config in `rfid_system/config.php` is `localhost` / `root` / no password / `rfid_system` — change this for anything beyond a local bench setup.
3. Flash one of the `.ino` sketches to an ESP32, wired to an MFRC522 RFID reader on SPI (SS on pin 5, RST on pin 4). Set `ssid`/`password` for your network and `serverName` to your PC's LAN IP + `/rfid_system/verify.php`.
4. Use `get_rfid/get_rfid.ino` first to read UIDs off new cards, then register each UID through the admin panel with the cardholder's name, vehicle, and photo.
5. Tap a registered card at the reader; the security dashboard at `rfid_system/security/index.php` updates live.

## Known limitations

- No SQL migration/schema file in the repo, tables have to be reverse-engineered from the column names used in the PHP.
- Default XAMPP DB credentials (`root`, no password) are checked into `config.php`, fine for a local lab demo, not fine to run as-is anywhere reachable from outside the LAN.
- WiFi SSID/password are hardcoded in the `.ino` files, replace before flashing to your own network.
- `verify.php` escapes the UID before querying but doesn't use parameterized queries elsewhere in `rfid_system/`, worth hardening if this ever goes past a bench demo.
- No automated tests; this is bench-tested hardware + a working local web app, not a hardened production access-control system.
- **`rfid_system/uploads/photos/` has real cardholder photos checked into git, filed under actual student USNs** (e.g. `1RV23AS004.jpeg`). That's personally identifiable data sitting in what looks like a public repo. Worth removing from version control (and scrubbing git history, a simple `git rm` doesn't purge it from old commits) before sharing this repo further, this README does not embed or reproduce any of those photos.

## Note on the portfolio site

This project has no dedicated write-up on [aboutkvs.vercel.app](https://aboutkvs.vercel.app), it's a standalone IoT/hardware build outside the aerospace project set on that site.

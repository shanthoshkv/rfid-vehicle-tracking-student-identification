# Contributing to RFID Vehicle Tracking & Student Identification

Thanks for helping. Small, focused changes are easiest to review.

## Getting set up

```bash
git clone https://github.com/shanthoshkv/rfid-vehicle-tracking-student-identification
cd rfid-vehicle-tracking-student-identification
# backend: serve rfid_system/ with XAMPP (Apache + MySQL), see README "Setup"
# firmware: open a .ino sketch in the Arduino IDE (ESP32 board package)
```

## Check your change

There are no automated tests. CI lints every PHP file and checks that key files exist. Locally, if PHP is installed:

```bash
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

## Pull requests

1. Open an issue first for anything bigger than a bug fix or doc tweak.
2. Branch from `main`, keep the diff small, one concern per PR.
3. Make sure the check above passes and CI is green.
4. Describe what changed and why. Add a line to `CHANGELOG.md` under **Unreleased** for user-visible changes.
5. Never commit real WiFi credentials, passwords or student photos/data.

## Good first issues

Look for the [`good first issue`](https://github.com/shanthoshkv/rfid-vehicle-tracking-student-identification/labels/good%20first%20issue) label. Comment on one to claim it.

## Questions

Use [Discussions](https://github.com/shanthoshkv/rfid-vehicle-tracking-student-identification/discussions) for questions and ideas, Issues for bugs and concrete requests.

By contributing you agree your work is released under this repository's license.

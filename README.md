# Lal Mejhe Adda

Booking site for the Coca-Cola "Lal Mejhe" Pujo campaign (Kolkata). Plain PHP and JavaScript, MySQL, Infobip SMS OTP login.
This is a copy of the code deployed on the Hostinger server (pulled 10 Oct 2026). `public_html/` is the web root.

## Configuration
No secrets are stored in the code. Set these as server environment variables:

| Variable | Purpose |
|---|---|
| `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` | MySQL connection |
| `INFOBIP_API_KEY` | Infobip SMS key |
| `INFOBIP_SMS_ENDPOINT` | Infobip SMS endpoint (optional override) |
| `APP_OTP_SECRET` | Long random secret used to hash OTPs |
| `ADMIN_PASSWORD_HASH` | bcrypt hash of the admin password (optional; otherwise `admin/.admin_password`) |

`public_html/config.sample.php` shows the layout for a shared-hosting setup.

## Security
Scan results are in `security-report-v1/`. See `SECURITY.md` to report a vulnerability.

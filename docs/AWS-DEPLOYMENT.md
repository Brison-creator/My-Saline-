# Deploying to AWS (mysaline.net)

`mysaline.net` is the temporary development site. The live `mysaline.com` is
never touched until you've signed off here.

**Status at time of writing:** the domain is registered but has no A record — it
resolves as a name with no address behind it, so nothing is serving yet.

---

## The one thing that will bite you

**AWS blocks outbound port 25 on new accounts, and EC2/Lightsail IP addresses
have poor sending reputation.** The Favorites ballot only counts a vote after the
voter clicks an emailed confirmation link. If mail doesn't deliver, votes
silently don't count and nobody tells you.

So email is not a finishing touch on this deployment — it is a prerequisite.
Section 5 covers it. Do not open voting until you have sent yourself a real test
ballot and received the link.

---

## 1. Stand up the server

For a WordPress site this size, **Lightsail** is the right AWS product. EC2 is
more machine to manage for no benefit; Amplify and S3 can't run PHP at all.

1. AWS console → **Lightsail** → Create instance
2. Platform **Linux/Unix** → Blueprint **WordPress** (this is the Bitnami image)
3. Size: **$10/month** (2 GB RAM). The $5 plan works but is tight once you import
   real content and run image resizing.
4. Region: **us-east-1 (N. Virginia)** or **us-east-2 (Ohio)** — closest to
   Arkansas of the cheap regions.
5. Name it `mysaline-dev`, create, wait ~2 minutes.
6. **Networking → Create static IP** and attach it. Do this before touching DNS;
   without it the address changes on reboot and the site disappears.

Retrieve the WordPress admin password:

```bash
# Lightsail → your instance → Connect using SSH, then:
cat /home/bitnami/bitnami_application_password
```

Log in at `http://<static-ip>/wp-login.php` as `user`.

## 2. Point the domain

In **Route 53** (or wherever `mysaline.net` is registered):

| Type | Name | Value |
| --- | --- | --- |
| A | `mysaline.net` | your Lightsail static IP |
| A | `www.mysaline.net` | your Lightsail static IP |

If the domain was bought through Route 53 the hosted zone already exists — just
add the records. If it was bought elsewhere, either point that registrar's
nameservers at the Route 53 zone, or add the A records at the registrar directly.

Give it 5–30 minutes, then confirm:

```bash
dig +short mysaline.net          # should return your static IP
```

## 3. HTTPS

On the Bitnami image this is one command:

```bash
sudo /opt/bitnami/bncert-tool
```

Answer with `mysaline.net` and `www.mysaline.net`, accept the HTTP→HTTPS
redirect and the www redirect. It provisions Let's Encrypt and sets up renewal.

Then set both URLs in WordPress (Settings → General), or from the CLI:

```bash
sudo wp option update home 'https://mysaline.net' --allow-root --path=/opt/bitnami/wordpress
sudo wp option update siteurl 'https://mysaline.net' --allow-root --path=/opt/bitnami/wordpress
```

## 4. Install the theme

Build the package locally:

```bash
./build.sh          # → dist/mysaline.zip
```

Then **Appearance → Themes → Add New → Upload Theme**, choose the ZIP, install,
**Activate**.

Immediately afterwards:

1. **Settings → Permalinks → Post name → Save.** This also flushes the rewrite
   rules the custom post types need. Skipping it gives 404s on
   `/events/`, `/obituaries/` and `/businesses/`.
2. Confirm the ballot's tables were created — theme activation runs `dbDelta`:

   ```bash
   sudo wp db query "SHOW TABLES LIKE '%mysaline_fav%'" --allow-root --path=/opt/bitnami/wordpress
   ```

   Expect `wp_mysaline_fav_votes` and `wp_mysaline_fav_pending`.
3. Seed or import your content (see section 6).

### Bitnami file permissions

The Bitnami image runs PHP as `daemon`. If uploads fail:

```bash
sudo chown -R bitnami:daemon /opt/bitnami/wordpress/wp-content
sudo chmod -R g+w /opt/bitnami/wordpress/wp-content
```

## 5. Email — do this before voting opens

### Set up SES

1. AWS console → **SES** → same region as the instance
2. **Verified identities** → verify `mysaline.net` (add the DKIM CNAME records to
   Route 53; SES offers to do it automatically if the zone is there)
3. **Request production access.** This is the step people miss: a new SES account
   is in **sandbox mode and can only send to addresses you have verified**. In
   sandbox, confirmation emails to actual voters go nowhere. Approval usually
   takes a day, so request it early.
4. **SMTP settings → Create SMTP credentials.** These are *not* your AWS access
   keys — SES issues a separate username and password.

### Wire WordPress to it

Install **WP Mail SMTP** (or Post SMTP) and enter:

| Field | Value |
| --- | --- |
| Host | `email-smtp.<region>.amazonaws.com` |
| Encryption | TLS |
| Port | **587** — not 25, which AWS blocks |
| Username / Password | the SES SMTP credentials |
| From address | something `@mysaline.net` that SES has verified |

### Prove it works

Send a test from the plugin, then run a **real ballot end to end**: submit a few
picks, wait for the email, click the link, and check the vote appears under
**Favorites → Results**. Also check it doesn't land in spam in Gmail and Yahoo —
between them that's most of your audience.

## 6. Content

**For a design review**, the site can stand on demo content — but the seeder in
`dev/` is written for the local environment and is not part of the theme ZIP.
Either create a handful of posts by hand, or copy `dev/seed-demo.php` up and run
it with WP-CLI.

**For a real compatibility test**, import a copy of the live site's content:

```bash
# On mysaline.com: Tools → Export → All content  (or a WXR export from the host)
# On mysaline.net: Tools → Import → WordPress, install the importer, upload the file
```

This is the step that actually proves the theme handles 19 years of posts,
categories, authors and media. Everything else about compatibility is inference
until this runs. Watch for:

- Do old permalinks still resolve?
- Do category and author archives look right at real volume?
- Are featured images present, and do they need
  **Regenerate Thumbnails** for the theme's image sizes?

## 7. Keep it out of Google

A staging copy of a real news site will get indexed and compete with the live
one. Before you add any real content:

**Settings → Reading → Discourage search engines from indexing this site.**

Consider also putting HTTP basic auth in front of the whole thing while you work.

## 8. Going live on mysaline.com later

Nothing here migrates the live site. When you're ready, the path is:

1. Back up the live database and `wp-content`.
2. Upload the same `dist/mysaline.zip` to `mysaline.com` — **installing does not
   change anything**; the current theme keeps running until you activate.
3. Pick a quiet window, activate, then **Settings → Permalinks → Save**.
4. Work through the post-deploy checklist in `DEPLOYMENT.md`.
5. If anything looks wrong, reactivate the old theme. The theme never modifies
   posts, pages, categories, media or URLs, so reverting is instant and lossless.

`mysaline.net` can stay afterwards as a permanent staging site — try changes
there first, always.

---

## Rough monthly cost

| | |
| --- | --- |
| Lightsail 2 GB | ~$10 |
| Static IP (attached) | free |
| Route 53 hosted zone | $0.50 |
| SES | $0.10 per 1,000 emails — pennies at your list size |
| **Total** | **~$11/month** |

A snapshot backup of the instance is about $1/month more and worth it.

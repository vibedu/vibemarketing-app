# Vibe Marketing — putting it into use

Files in this folder: `index.html`, `manifest.json`, `icon.svg`. Keep them together.

## 1. Get the link live (about 3 minutes, free)

1. On a laptop, open **app.netlify.com/drop**
2. Drag this whole folder onto the page
3. You get an https link straight away. **Create the free account when it offers**,
   otherwise the link expires in a day. Then rename it to something like
   `vibemarketing.netlify.app` in Site settings.

Cloudflare Pages, Vercel and GitHub Pages work the same way. It must be **https** —
the camera and GPS are blocked on plain http and on files opened off the phone.

## 2. First run — the office

Open the link on your phone → **Office login** → it asks you to create your account.
Name, and a PIN you choose. You become **Director** with full access.

Then, in Settings (gear icon):
- **Business details** — company name, your GSTIN, invoice prefix
- **Office users** — add the others. Roles:
  - **Director** — everything
  - **Finance** — vendors, campaigns, invoices, payments. No fleet editing.
  - **Field manager** — campaigns, fleet, drivers, photos, renewals. **No money at all.**
- **Manager contact** — the WhatsApp number and email that field photos and renewal
  reminders get sent to

The app starts **empty**. That is deliberate — it is your business, not a demo.

## 3. Load your real data

In this order, or nothing links up properly:

1. **Fleet → Drivers → Add driver.** Name and a 10-digit phone are required — the phone
   is how he signs in. Photo and KYC can follow later.
2. **Fleet → Vehicles → Add vehicle.** Registration, FC due and insurance due are
   required. Add PUC and permit if you track them. Assign the driver.
3. **Vendors → Add vendor.** WhatsApp number and email matter — that is where invoices go.
4. **Orders → New campaign.** Raise the proforma; it offers to send immediately.
5. Open the campaign and **tick which vehicles** are carrying it. Drivers only see a
   campaign if their vehicle is ticked.

## 4. The drivers

Send them the same link. On their phone: open it → menu → **Add to Home Screen**.

They tap **I am a driver**, switch to **தமிழ்**, pick their vehicle number, and type
their PIN — the last 4 digits of their phone, unless you set a different one on their
record. Then **பணி தொடங்கு**.

The phone will ask once for **camera** and **location**. They must allow both.

Signing in *is* their attendance. **பணி முடி** at the end of the day stamps the
out-time. If they forget, the app closes the shift at 8pm of that day automatically.

## 5. Back up — do not skip this

Everything lives on the phone's browser. Clear the browser data, lose the phone, or
let a "cleaner" app run, and it is gone. There is no server holding a copy.

**Every Friday:** Settings → **Back up everything** → the file lands in Downloads →
send it to yourself on WhatsApp or email. To bring it back on any phone:
Settings → **Restore from a backup file**.

Photos are capped at the most recent 60 per phone so storage does not fill up. Older
ones drop off the phone — they stay in whatever WhatsApp thread you shared them to.

## What this app does not do yet

Each phone holds its own data. The office phone does not automatically see what the
drivers record — the driver's photos and attendance are on his phone. What crosses
over is WhatsApp: after each photo he taps **Share photo + details** and it goes to
your group with the GPS stamp burned into the image.

If you want the office to see driver activity live, and one set of books instead of
one per phone, that needs a server behind the app. Run it this way for a few weeks
first — you will know exactly what you need by then.

## Known limits, plainly

- Phone notifications only fire when the app is opened. The bell inside the app and
  the WhatsApp reminders are reliable; background push is not, without a server.
- WhatsApp and email open with the message pre-filled — you still tap send. Fully
  automatic sending needs the WhatsApp Business API and a server.
- PINs keep honest people honest. Anyone who can clear the browser data can reset
  the app. Treat it as a lock on a drawer, not a safe.

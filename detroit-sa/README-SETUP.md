# Detroit S.A website + admin panel

## Run it on your computer or phone
Open a terminal in this folder and run:

    php -S localhost:8000

Then open http://localhost:8000 (website) and http://localhost:8000/admin/ (admin panel).
The site needs PHP 7.0 or newer. Live Server and `python -m http.server` cannot run PHP,
so use `php -S` (or upload the folder to a PHP host).

## Admin login (temporary)
- Email:    vmabanga0@gmail.com
- Password: Vuyani2026

Sign in at /admin/ and open "Account & login" to change both. The new details are stored
(hashed) in data/admin.json.php. If the password is ever forgotten, delete that file and the
temporary login above works again.

## Users
The temporary login above is the Full admin. Under Admin > Users the Full admin can add a Viewer (can read booking requests and see what needs attention, but cannot edit) or another Full admin.

## What the admin can manage
Notices (top bar), hero slider, Home page text and hero photo, Services, Parts page, banks/insurers/warranty, Gallery,
About & FAQ, Testimonials, page headings, business details (phone, hours, social links, logo),
booking requests inbox, page-view stats and login details.

## Uploading to a host
1. Upload the whole folder (keep the structure).
2. Make sure PHP can write to the data/ and uploads/ folders (permissions 755 or 775).
3. Use HTTPS on the live site (most hosts offer free SSL).
4. Booking emails use PHP mail(). Every request is also saved in Admin > Booking requests.

## Folders
- *.php       public pages (they read everything from data/content.json.php)
- admin/      admin panel (login.php, index.php, api.php)
- includes/   shared PHP code
- data/       saved content, login, messages, stats (back this up)
- uploads/    images uploaded in the admin panel (back this up)
- images/     original site images and the company-profile photos

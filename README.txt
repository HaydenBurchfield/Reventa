ReVènta — XAMPP Setup Guide
============================

1. PLACE FILES
   Copy the entire "reventa" folder into your XAMPP htdocs directory:
   C:/xampp/htdocs/reventa/   (Windows)
   /Applications/XAMPP/htdocs/reventa/  (Mac)

2. DATABASE
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Create a database named: reventa
   - Import your SQL schema file

3. DB CREDENTIALS
   Edit: php/Utils/DatabaseConnection.php
   Set your host, username, password (default XAMPP: root / no password)

4. UPLOAD PERMISSIONS
   Ensure these folders are writable:
   uploads/listings/
   uploads/avatars/

5. ACCESS
   http://localhost/reventa/

FILE STRUCTURE
--------------
index.php                    — Homepage
pages/
  login.php                  — Login
  signup.php                 — Sign Up
  explore.php                — Browse all listings (with filters)
  listing.php                — Single product page
  sell.php                   — Create a listing
  profile.php                — User profile + listings
  messages.php               — Chat / messaging
  likes.php                  — Liked items
  settings.php               — Change password etc.
  mens.php / womens.php / kids.php  — Category pages
php/
  objects/                   — OOP classes (User, Listing, Chat, etc.)
  Utils/                     — DatabaseConnection, Logout
  api/                       — AJAX endpoints (like, messages, etc.)
assets/css/                  — Stylesheets
uploads/                     — User-uploaded images (listings, avatars)

# BRAGS.co.uk WordPress Project

A modern multi-vendor e-commerce platform built with WordPress, WooCommerce, and Dokan.

---

## 🛠 Tech Stack & Environment
- **Core Platform**: WordPress
- **Server Environment**: Local XAMPP (Apache & MySQL/MariaDB)
- **Database Name**: `wp_jp0jg`
- **Main Theme**: Woodmart (with `woodmart-child` child theme for custom modifications)

---

## 📂 Key Theme & Plugins

### Theme
- **Woodmart Theme**: A premium responsive WooCommerce theme designed for multipurpose e-commerce websites.

### Key Plugins
- **E-Commerce & Vendor System**:
  - **WooCommerce**: Core online store engine.
  - **Dokan Lite & Dokan Pro**: Multi-vendor marketplace capabilities.
- **Page Builder**:
  - **Elementor**: Drag-and-drop page builder.
- **Marketing & Affiliates**:
  - **Indeed Affiliate Pro**: Premium affiliate system management.
  - **Google Listings and Ads** & **Pinterest for WooCommerce**
- **Security & SEO**:
  - **Wordfence**: Firewall and malware scanning.
  - **Yoast SEO (wordpress-seo)**: Search engine optimization.
- **Database & Administration**:
  - **WP Data Access**: Local database table manager.
  - **WP-Lister for Amazon**: Amazon listing integration.

---

## 🚀 Setup & Local Installation

### 1. Database Setup
1. Open your **XAMPP Control Panel** and ensure **Apache** and **MySQL** are running.
2. Open **phpMyAdmin** (usually `http://localhost/phpmyadmin`) and create a database named `wp_jp0jg`.
3. Import the database dump `wp_jp0jg.sql` located in the root of this project.
   - *Note*: If you run into import timeout issues, ensure your `max_allowed_packet` size in `my.ini` is set to `1024M`.

### 2. Configuration
Ensure your local `wp-config.php` has the correct database credentials:
```php
define( 'DB_NAME', 'wp_jp0jg' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' ); // Default XAMPP password is empty
define( 'DB_HOST', 'localhost:3306' );
```

---

## 📝 General Administration
- Default local admin URL: `http://localhost/brags.co.uk/wp-admin`

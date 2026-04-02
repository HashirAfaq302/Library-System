# Library System (PHP + MySQL + Twig)

## Requirements
- PHP 8.x
- MySQL / MariaDB
- Apache (XAMPP on Windows or mi-linux student server)

## Setup (XAMPP)
1. Create the database/tables:
   - If you already made `library_db` with different columns, import `database/reset.sql` (this deletes old tables).
   - Otherwise import `database/schema.sql`.
2. Ensure Apache + MySQL are running.
3. Open: `/library-system/public/books`

Default DB config uses:
- DB: `library_db`
- User: `root`
- Pass: empty

To override (mi-linux): set env vars `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, and optional `APP_BASE_URL`.

## Debugging (local only)
- Set `APP_DEBUG=1` to show the real error message on the 500 page (helps when columns/table names don’t match).

## Features checklist (rubric)
- CRUD: books (Create/Read/Update/Delete)
- Search: multiple criteria (title/author/genre/year)
- Ownership: `books.user_id` links books to users; Browse is public, My Books is owner-only
- Security: input filtering, output escaping (Twig), sessions (auth-protected pages), captcha (math), password hashing
- Ajax: autocomplete for title/author/genre
- Template engine: Twig used site-wide




## OR ## 

## Simplified Instructions ## 

Library System Project

Requirements:

* XAMPP installed
* PHP 8+
* MySQL

Steps to Run:

1. Copy the "library-system" folder into:
   C:\xampp\htdocs\

2. Start Apache and MySQL in XAMPP.

3. Open phpMyAdmin:
   http://localhost/phpmyadmin

4. Create a database:
   library_db

5. Import the file:
   database.sql

6. Open the website in browser:
   http://localhost/library-system/public

Features:

* Browse Books (public)
* User Login/Register
* Add Books
* Edit Books
* Delete Books
* My Books section

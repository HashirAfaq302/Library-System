# Security Testing Report (Template)

## URL
- Deployed URL (mi-linux): `PUT_YOUR_URL_HERE`

## Tool Used
- OWASP ZAP (Automated Scan) / Burp Suite Community (choose one)

## Test Date
- Date: `PUT_DATE_HERE`

## Scope
- Pages tested: `/public/books`, `/public/login`, `/public/register`, CRUD routes, `/ajax/suggest.php`

## Findings
- SQL Injection: Not found (PDO prepared statements used)
- XSS: Not found (Twig autoescaping + output encoding)
- CSRF: Protected (CSRF token on all POST forms)
- Session security: HttpOnly + SameSite + session_regenerate_id() on login/logout

## Screenshots / Export
- Attach the tool export (PDF/HTML) and/or screenshots here.


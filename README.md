# Fan Hub Plus

Fan Hub Plus is a PHP 8 + MySQL/MariaDB pop-culture catalog website. This package includes a clean, isolated database baseline.

## Requirements

- PHP 8.1+ with `pdo_mysql` and `mbstring` enabled.
- MySQL 8+ or MariaDB 10.5+.
- A PHP-capable web server, or PHP’s built-in server for local testing.

## Fresh database setup

The setup below creates a **new database named `fan_hub_plus`** and loads the site schema and curated catalog. It does not drop or empty any existing database. Do not change `fan_hub_plus` to your old database name unless you intentionally want to install into that database.

1. Back up your database server before making changes.
2. Create the isolated database and base schema with a MySQL setup account:

   ```sh
   mysql -u root -p < database.sql
   ```

   `database.sql` is repeatable: it uses `CREATE TABLE IF NOT EXISTS` and `INSERT IGNORE`; it has no `DROP` or table truncation. It seeds the fixed local demo administrator below and will not migrate old tables.

3. Create a restricted app user (replace the password with a unique, long random value):

   ```sql
   CREATE USER 'fan_hub_app'@'127.0.0.1' IDENTIFIED BY 'USE_A_LONG_RANDOM_PASSWORD';
   GRANT SELECT, INSERT, UPDATE, DELETE ON fan_hub_plus.* TO 'fan_hub_app'@'127.0.0.1';
   FLUSH PRIVILEGES;
   ```

   The website only needs CRUD access. Database creation and schema changes are performed by your administrator during setup, not by the public-facing app account.

4. Configure the website. Either set `FAN_HUB_DB_HOST`, `FAN_HUB_DB_PORT`, `FAN_HUB_DB_NAME`, `FAN_HUB_DB_USER`, and `FAN_HUB_DB_PASS` in the PHP process environment, or copy `config.local.example.php` to `config.local.php` and enter private values. Keep that file outside public downloads/version control and restrict its file permissions. `config.local.php` has priority over environment values. **If you are reusing an existing project folder, inspect its private `config.local.php` and change `name` to `fan_hub_plus` and the user/password to the new app account; that private file can otherwise keep the website connected to the old database.** The default database is `fan_hub_plus`; the default app user is `fan_hub_app` (the password must be configured).

5. Sign in to the seeded administrator account at `auth.php`:

   - Email: `ghazanfarmehmood131@gmail.com`
   - Password: `8cc3716ad22f9dfa7a3e46c4`

   The password is stored as a hash in the SQL file. Every fresh copy uses the same credentials, so anyone receiving the ZIP can access its admin account. Do not distribute the ZIP publicly with these credentials unchanged.

## Anime and movie video playlists

- Sign in to `admin.php`, open **Items**, then choose **Playlist** beside an anime or movie. Upload MP4, WebM, or OGV files and give each episode or movie a label.
- XAMPP Apache permits files up to 1 GB per upload. Other PHP servers must set `upload_max_filesize`, `post_max_size`, and request time limits accordingly.
- Visitors can browse titles and trailers without an account. They must sign up or sign in before a full uploaded video can play; after signup, the requested video opens automatically.
- Upload only videos you own, are licensed to host and stream, or that are in the public domain. Files are stored under `storage/videos` and served through the authenticated `video.php` endpoint, not direct file URLs.
- For an existing database, run `mysql -u root -p < database.sql` again to create the playlist table. The schema script uses `CREATE TABLE IF NOT EXISTS` and does not drop existing tables.

## Existing installation / migration

The supplied archive does not contain a live database or database-server credentials, so each recipient must import the schema and configure a local MySQL user. The former `database.sql` included `DROP TABLE` statements and the old admin helper reset a seeded admin password; those destructive reset behaviors have been removed. This package now intentionally seeds the fixed local demo administrator documented above. Keep a backup before migrating or deleting any existing database.

To migrate data, first take a verified backup, then plan and test a separate migration against a copy. Do not run destructive SQL against the old database as part of this fresh install.

## Run locally

From the project directory:

```sh
php -S 0.0.0.0:8080
```

Open `http://localhost:8080/`.

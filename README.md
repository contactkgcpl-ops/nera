# Salvin India - Contract Packaging & Manufacturing Website

This repository contains the code for the Salvin India contract packaging website.

## Automated Deployment Setup (GitHub to Hostinger)

This repository is configured with a GitHub Actions workflow that automatically deploys files to Hostinger via FTP whenever you push to the `main` branch.

### Step-by-Step Configuration

#### 1. Set Up GitHub Action Secrets
In order for GitHub to securely connect to your Hostinger FTP account, you must add the following **Repository Secrets** in GitHub:

1. On GitHub, go to your repository page.
2. Navigate to **Settings** > **Secrets and variables** > **Actions**.
3. Click on **New repository secret** and add the following three secrets:
   * **`FTP_SERVER`**: Your Hostinger FTP Host (e.g., `ftp.salvinindia.com` or your Hostinger IP address).
   * **`FTP_USERNAME`**: Your FTP username (e.g., `u123456789.ftp` or similar).
   * **`FTP_PASSWORD`**: Your FTP account password.

#### 2. Configure Database for Production
The [db.php](file:///c:/xampp/htdocs/Contract_Packaging/db.php) file is designed to dynamically detect if it's running locally (on localhost) or in production on Hostinger.

Open [db.php](file:///c:/xampp/htdocs/Contract_Packaging/db.php) and update the production configuration block (in the `else` section) with your Hostinger database details:

```php
} else {
    // Production (Hostinger) configuration
    define('DB_HOST', 'localhost'); // Hostinger MySQL server (usually 'localhost' or an IP)
    define('DB_PORT', '3306');      // Standard port
    define('DB_USER', 'your_hostinger_db_user'); // Your Hostinger DB User
    define('DB_PASS', 'your_hostinger_db_password'); // Your Hostinger DB Password
    define('DB_NAME', 'your_hostinger_db_name'); // Your Hostinger DB Name
    define('IS_DEV', false);
}
```

*Note: You only need to create a blank MySQL database in Hostinger hPanel. The website will automatically create the `categories` and `products` tables on its first run.*

---

## File Structure & Uploads Protection
- The automated deployment workflow is configured to **exclude** the `uploads/` folder. This ensures that any product or category images uploaded by the admin on the live site are not overwritten or deleted when you push new code from GitHub.
- Editor config files, log files, and database backups (`.sql`) are also excluded via `.gitignore` and deployment rules to ensure repository cleanliness and security.

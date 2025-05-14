# Deploying to InfinityFree

This guide will help you deploy the Cherish Orthopaedic Centre website to InfinityFree hosting.

## Prerequisites

1. An InfinityFree account (sign up at infinityfree.com)
2. Your domain name
3. FileZilla or any FTP client
4. Access to phpMyAdmin (provided by InfinityFree)

## Deployment Steps

### 1. Create Hosting Account

1. Log in to your InfinityFree account
2. Click "Create Account" under "Free Hosting"
3. Choose your subdomain or connect your domain
4. Note down the following details:
   - FTP hostname
   - FTP username
   - FTP password
   - MySQL hostname
   - MySQL database name
   - MySQL username
   - MySQL password

### 2. Configure Database

1. Log in to phpMyAdmin using your MySQL credentials
2. Create a new database (if not already created)
3. Import the `database.sql` file to create tables and initial data
4. Update `config.php` with your database credentials:
   ```php
   $db_host = 'YOUR_MYSQL_HOST';
   $db_name = 'YOUR_DB_NAME';
   $db_user = 'YOUR_DB_USER';
   $db_pass = 'YOUR_DB_PASSWORD';
   ```

### 3. Upload Files

1. Open FileZilla and connect using your FTP credentials
2. Navigate to the `htdocs` directory
3. Upload the following files and directories:
   ```
   /api
   /assets
   /pages
   .htaccess
   config.php
   index.html
   ```

### 4. File Permissions

Set the following permissions:
- Directories: 755 (`drwxr-xr-x`)
- Files: 644 (`-rw-r--r--`)
- PHP files: 644 (`-rw-r--r--`)

### 5. Update Frontend API URLs

Update all API URLs in your frontend JavaScript files to use your domain:

1. Open `frontend/assets/js/main.js`
2. Replace all API URLs with your domain:
   ```javascript
   // Before:
   fetch('/api/products')
   
   // After:
   fetch('https://your-domain.com/api/products')
   ```

### 6. Configure Domain

If using a custom domain:

1. Go to your domain registrar's dashboard
2. Update nameservers to:
   - ns1.infinityfree.com
   - ns2.infinityfree.com
3. Wait for DNS propagation (up to 48 hours)

### 7. Test Deployment

1. Visit your website URL
2. Test all functionality:
   - User registration/login
   - Product browsing
   - Shopping cart
   - Checkout process
   - Appointment booking

### 8. Security Checklist

- [ ] Verify SSL certificate is working
- [ ] Confirm sensitive files are protected
- [ ] Test error handling
- [ ] Check database connection
- [ ] Validate form submissions
- [ ] Test payment integration

### Common Issues

1. **Database Connection Failed**
   - Double-check database credentials in `config.php`
   - Verify database server is accessible

2. **500 Internal Server Error**
   - Check PHP error logs
   - Verify file permissions
   - Validate .htaccess syntax

3. **API Endpoints Not Working**
   - Confirm mod_rewrite is enabled
   - Check .htaccess rules
   - Verify API file permissions

4. **Images Not Loading**
   - Check file paths
   - Verify image file permissions
   - Confirm image files were uploaded

### Support

For hosting-related issues:
- Visit InfinityFree support forums
- Check InfinityFree documentation
- Contact InfinityFree support

## Maintenance

Regular maintenance tasks:
1. Backup database regularly
2. Monitor error logs
3. Update SSL certificates
4. Check disk space usage
5. Monitor bandwidth usage

Remember to keep your credentials secure and never commit them to version control.

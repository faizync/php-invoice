# PHP Invoice Generator

A clean, no-database PHP invoice generator. Fill in the form, generate a professional invoice, and print or save as PDF.

## Features
- Dynamic line items (add/remove)
- Auto subtotal, tax, and total calculation
- Print / Save as PDF support
- Input validation
- No database required — pure PHP

## Project Structure
```
php-invoice/
├── public/
│   └── index.php       ← entry point (Nginx/Apache root points here)
├── src/
│   └── Invoice.php     ← Invoice class (validation + calculations)
└── README.md
```

## Deployment on EC2 (Nginx)

### Step 1 — Clone the repo
```bash
cd /var/www
git clone <YOUR_REPO_URL> php-invoice
```

### Step 2 — Install PHP
```bash
sudo apt update
sudo apt install -y php php-fpm php-cli php-common
```

### Step 3 — Configure Nginx
```bash
sudo nano /etc/nginx/sites-enabled/php-invoice
```

```nginx
server {
    listen 80;
    server_name _;

    root /var/www/php-invoice/public;
    index index.php;

    access_log /var/log/nginx/php-invoice-access.log;
    error_log  /var/log/nginx/php-invoice-error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        try_files $uri =404;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
```

```bash
sudo nginx -t
sudo systemctl restart nginx
```

## Deployment on EC2 (Apache2)

### Configure Apache2
```bash
sudo nano /etc/apache2/sites-enabled/php-invoice.conf
```

```apache
<VirtualHost *:80>
    ServerName _
    DocumentRoot /var/www/php-invoice/public

    <Directory /var/www/php-invoice/public>
        Require all granted
        AllowOverride All
        Options -Indexes
    </Directory>

    <Directory /var/www/php-invoice/src>
        Require all denied
    </Directory>

    ErrorLog  ${APACHE_LOG_DIR}/php-invoice-error.log
    CustomLog ${APACHE_LOG_DIR}/php-invoice-access.log combined
</VirtualHost>
```

```bash
sudo systemctl stop nginx
sudo systemctl start apache2
```

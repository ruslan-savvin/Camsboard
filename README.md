# Camsboard

Вебзастосунок (PHP + JS) із модулем комп'ютерного зору на базі Python та YOLOv8.  
Web application (PHP + JS) with a computer vision module powered by Python and YOLOv8.

---

## Системні вимоги / System Requirements (Ubuntu 24.04+)

- **Apache** 2.4+ (`mod_rewrite`)
- **PHP** 8.3+ та **Composer** / **PHP** 8.3+ and **Composer**
- **MySQL** 8.0+
- **Python** 3.12+ (`python3-venv`, `python3-pip`)
- Системні бібліотеки для OpenCV / System libraries for OpenCV (`libgl1`, `libglib2.0-0`)

---

## Розгортання на чистому сервері / Deployment on a Clean Server

### 1. Встановлення системних пакетів / Install System Packages

```bash
# Оновити список пакетів та встановити Apache, MySQL, PHP, Composer і Python
# Update package list and install Apache, MySQL, PHP, Composer, and Python
sudo apt update
sudo apt install -y git curl unzip apache2 libapache2-mod-php mysql-server php-cli php-mysql php-mbstring php-xml php-curl composer python3-pip python3-venv libgl1 libglib2.0-0
sudo a2enmod rewrite
sudo systemctl enable --now apache2 mysql
```

### 2. Клонування репозиторію / Clone the Repository

```bash
# Перейти у робочу директорію вебсервера та клонувати проєкт
# Navigate to the web server directory and clone the project
cd /var/www
git clone https://github.com/ruslan-savvin/camsboard
cd /var/www/camsboard
```

### 3. Налаштування бази даних MySQL / MySQL Database Setup

```bash
# Створити базу даних, користувача та імпортувати структуру і початкові дані з deploy/schema.sql
# Create the database, user, and import schema and initial data from deploy/schema.sql
sudo mysql -e "CREATE DATABASE camsboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'camsboard_user'@'localhost' IDENTIFIED BY 'your_password_here';"
sudo mysql -e "GRANT ALL PRIVILEGES ON camsboard.* TO 'camsboard_user'@'localhost'; FLUSH PRIVILEGES;"
sudo mysql camsboard < /var/www/camsboard/deploy/schema.sql
```

> **Дані для першого входу / Default First Login:**
>
> - **Login:** `1234567891`
> - **Динамічний пароль / Dynamic Code:** `1111` _(працює, коли в `.env` встановлено `AUTH_2FA_ENABLED=false` / works when `AUTH_2FA_ENABLED=false` is set in `.env`)_.
> - Після першого входу змініть дані в профілі, налаштуйте сповіщення (Email / Telegram) та увімкніть `AUTH_2FA_ENABLED=true` у файлі `.env`.
> - After the first login, update your profile details, configure notifications (Email / Telegram), and set `AUTH_2FA_ENABLED=true` in `.env`.

### 4. Налаштування конфігурації / Configuration Setup

```bash
# Скопіювати шаблон конфігурації та заповнити власні налаштування (БД, reCAPTCHA)
# Copy the configuration template and fill in your credentials (DB, reCAPTCHA)
cp .env.example .env
nano .env
```

### 5. Встановлення PHP-залежностей / Install PHP Dependencies (Composer)

```bash
# Встановити продакшн-пакети PHP з оптимізацією автозавантаження
# Install production PHP packages with autoloader optimization
composer install --no-dev --optimize-autoloader
```

### 6. Налаштування Python-середовища / Python Environment Setup

```bash
# Створити ізольоване віртуальне середовище
# Create an isolated virtual environment
python3 -m venv venv

# Оновити менеджер пакетів pip та встановити бібліотеки з requirements.txt
# Upgrade pip package manager and install libraries from requirements.txt
venv/bin/pip install --upgrade pip
venv/bin/pip install -r requirements.txt
```

### 7. Завантаження моделі YOLOv8n / Download YOLOv8n Model

```bash
# Завантажити стандартну модель, експортувати в формат ONNX та перемістити в src/
# Download the standard model, export to ONNX format, and move to src/
venv/bin/yolo export model=yolov8n.pt format=onnx
mv yolov8n.onnx src/
rm -f yolov8n.pt
```

### 8. Налаштування прав доступу / Set File Permissions

```bash
# Надати вебсерверу (www-data) права власника на папку проєкту
# Grant the web server user (www-data) ownership of the project directory
sudo chown -R www-data:www-data /var/www/camsboard
```

### 9. Налаштування прав sudo / Configure Sudo Permissions

```bash
# Скопіювати правила sudoers для керування камерами та таймерами systemd
# Copy sudoers rules for managing cameras and systemd timers
sudo cp /var/www/camsboard/deploy/sudoers.camsboard /etc/sudoers.d/camsboard
sudo chmod 0440 /etc/sudoers.d/camsboard
sudo visudo -c
```

### 10. Встановлення FFmpeg / Install FFmpeg

```bash
# Встановити FFmpeg для роботи з відеопотоками
# Install FFmpeg for video stream processing
sudo apt install -y ffmpeg
ffmpeg -version
```

### 11. Налаштування вебсервера Apache / Configure Apache Web Server

```bash
# Створити та відкрити файл конфігурації віртуального хоста
# Create and open the virtual host configuration file
sudo nano /etc/apache2/sites-available/camsboard.conf
```

```apache
# Вставити конфігурацію віртуального хоста у файл
# Paste the virtual host configuration into the file
<VirtualHost *:80>
    ServerName _
    DocumentRoot /var/www/camsboard

    <Directory /var/www/camsboard>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/camsboard_error.log
    CustomLog ${APACHE_LOG_DIR}/camsboard_access.log combined
</VirtualHost>
```

```bash
# Увімкнути сайт, вимкнути стандартну заглушку та перезапустити Apache
# Enable the site, disable the default page, and restart Apache
sudo a2dissite 000-default.conf
sudo a2ensite camsboard.conf
sudo systemctl restart apache2
```

### 12. Налаштування SSL-сертифіката (HTTPS) / Configure SSL Certificate (HTTPS)

```bash
# Варіант А: Швидкий самопідписаний сертифікат (для роботи по IP-адресі)
# Примітка: Браузер покаже попередження безпеки — натисніть "Додатково" -> "Прийняти ризик і продовжити"
# Option A: Quick self-signed certificate (for IP address access)
# Note: Browser will show a security warning — click "Advanced" -> "Accept the Risk and Continue"
sudo a2enmod ssl
sudo a2ensite default-ssl
sudo sed -i 's|/var/www/html|/var/www/camsboard|g' /etc/apache2/sites-available/default-ssl.conf
sudo systemctl restart apache2

# Варіант Б: Безкоштовний SSL-сертифікат Let's Encrypt (якщо є домен)
# Option B: Free Let's Encrypt SSL certificate (if a domain name is used)
sudo apt install -y certbot python3-certbot-apache
sudo certbot --apache -d your-domain.com
```

### 13. Встановлення MediaMTX / Install MediaMTX

```bash
sudo mkdir -p /opt/mediamtx && cd /opt/mediamtx
domain="github.com"
author="bluenviron"
project="mediamtx"
version="v1.21.3"
file="mediamtx_v1.21.3_linux_amd64.tar.gz"
sudo wget "https://$domain/$author/$project/releases/download/$version/$file"
sudo tar -xzf "$file" && sudo rm -f "$file"


# Створити та відкрити файл системної служби systemd
# Create and open the systemd service file
sudo nano /etc/systemd/system/mediamtx.service
```

````ini
[Unit]
Description=MediaMTX Media Server
After=network.target

[Service]
Type=simple
User=root
ExecStart=/opt/mediamtx/mediamtx /opt/mediamtx/mediamtx.yml
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target


nano /opt/mediamtx/mediamtx.yml
```yaml
# Варіант А: Для тестового сервера без домену (самопідписаний сертифікат)
# Примітка: Перейдіть у браузері на https://IP_СЕРВЕРА:8889 та підтвердіть сертифікат
# Option A: For test server without domain (self-signed certificate)
# Note: Open https://SERVER_IP:8889 in browser and accept the certificate warning
webrtcEncryption: yes
webrtcServerKey: /etc/ssl/private/ssl-cert-snakeoil.key
webrtcServerCert: /etc/ssl/certs/ssl-cert-snakeoil.pem


# Варіант Б: Для продакшен-сервера з доменом Let's Encrypt
# Option B: For production server with Let's Encrypt domain
# webrtcEncryption: yes
# webrtcServerKey: /etc/letsencrypt/live/dvr.greatapart.com/privkey.pem
# webrtcServerCert: /etc/letsencrypt/live/dvr.greatapart.com/fullchain.pem
````

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now mediamtx
sudo systemctl status mediamtx

```

### 14. Налаштування фаєрволу (UFW) / Configure Firewall (UFW)

```bash
# Дозволити SSH, вебсервер (HTTP/HTTPS) та медіасервер MediaMTX (RTMP, RTSP, WebRTC)
# Allow SSH, web server (HTTP/HTTPS), and MediaMTX media server (RTMP, RTSP, WebRTC)
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 1935/tcp
sudo ufw allow 8554/tcp
sudo ufw allow 8889/tcp
sudo ufw allow 8189/udp

# Увімкнути фаєрвол та перевірити активні правила
# Enable firewall and check active rules
sudo ufw --force enable
sudo ufw status
```

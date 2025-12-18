# 🚀 Guide de Déploiement - PharmaMobile

## 📋 Options de Déploiement

### 1. **Déploiement Local/Serveur VPS** (Recommandé)
- ✅ Contrôle total
- ✅ Moins cher
- ✅ Idéal pour soutenance

### 2. **Déploiement Cloud** 
- AWS, DigitalOcean, Heroku
- Plus complexe mais scalable

## 🛠️ Préparation du Projet

### Étape 1: Optimisation Laravel
```bash
# Dans le dossier pharma-app
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Étape 2: Configuration Production
```bash
# Créer .env.production
cp .env .env.production
```

Modifier `.env.production` :
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://votre-domaine.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pharma_production
DB_USERNAME=pharma_user
DB_PASSWORD=mot_de_passe_securise

# Firebase
FCM_SERVER_KEY=votre_server_key_firebase
```

### Étape 3: Sécurisation
```bash
# Générer nouvelle clé APP_KEY
php artisan key:generate --env=production
```

## 🌐 Déploiement sur VPS/Serveur

### Prérequis Serveur
- Ubuntu 20.04+ ou CentOS 8+
- PHP 8.1+
- MySQL 8.0+
- Nginx ou Apache
- Composer
- Node.js (pour Angular)

### Installation Serveur
```bash
# Mise à jour système
sudo apt update && sudo apt upgrade -y

# Installation PHP 8.1
sudo apt install php8.1 php8.1-fpm php8.1-mysql php8.1-xml php8.1-curl php8.1-zip php8.1-mbstring -y

# Installation MySQL
sudo apt install mysql-server -y

# Installation Nginx
sudo apt install nginx -y

# Installation Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Installation Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install nodejs -y
```

### Déploiement Laravel
```bash
# Cloner le projet
git clone votre-repo pharma-app
cd pharma-app

# Installation dépendances
composer install --optimize-autoloader --no-dev

# Configuration
cp .env.production .env
php artisan key:generate

# Base de données
php artisan migrate --force
php artisan db:seed --force

# Permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Configuration Nginx
```nginx
# /etc/nginx/sites-available/pharma-app
server {
    listen 80;
    server_name votre-domaine.com;
    root /var/www/pharma-app/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fmp.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

```bash
# Activer le site
sudo ln -s /etc/nginx/sites-available/pharma-app /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## 📱 Déploiement Angular (Frontend)

### Build Production
```bash
# Dans le dossier frontend Angular
npm install
ng build --configuration production
```

### Servir avec Nginx
```nginx
# Ajouter dans la config Nginx
location /app {
    alias /var/www/pharma-app-frontend/dist;
    try_files $uri $uri/ /index.html;
}
```

## 🔒 SSL/HTTPS avec Let's Encrypt
```bash
# Installation Certbot
sudo apt install certbot python3-certbot-nginx -y

# Obtenir certificat SSL
sudo certbot --nginx -d votre-domaine.com

# Auto-renouvellement
sudo crontab -e
# Ajouter: 0 12 * * * /usr/bin/certbot renew --quiet
```

## 🐳 Déploiement avec Docker (Alternative)

### Dockerfile Laravel
```dockerfile
FROM php:8.1-fpm

# Installation dépendances
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip

# Installation extensions PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Installation Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copie du projet
WORKDIR /var/www
COPY . .

# Installation dépendances
RUN composer install --optimize-autoloader --no-dev

# Permissions
RUN chown -R www-data:www-data /var/www
```

### docker-compose.yml
```yaml
version: '3.8'
services:
  app:
    build: .
    ports:
      - "8000:8000"
    environment:
      - APP_ENV=production
    volumes:
      - ./storage:/var/www/storage
    depends_on:
      - db

  db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: pharma_production
      MYSQL_USER: pharma_user
      MYSQL_PASSWORD: password
      MYSQL_ROOT_PASSWORD: rootpassword
    volumes:
      - mysql_data:/var/lib/mysql

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
    depends_on:
      - app

volumes:
  mysql_data:
```

## 🚀 Déploiement Rapide (Heroku)

### Préparation Heroku
```bash
# Installation Heroku CLI
# Puis:
heroku create pharma-app-votre-nom
heroku addons:create cleardb:ignite
heroku config:set APP_KEY=$(php artisan --no-ansi key:generate --show)
```

### Procfile
```
web: vendor/bin/heroku-php-apache2 public/
```

### Déploiement
```bash
git add .
git commit -m "Deploy to production"
git push heroku main
heroku run php artisan migrate --force
```

## ✅ Checklist Déploiement

- [ ] Base de données configurée
- [ ] Variables d'environnement définies
- [ ] SSL/HTTPS activé
- [ ] Permissions fichiers correctes
- [ ] Cache Laravel optimisé
- [ ] Logs configurés
- [ ] Sauvegardes automatiques
- [ ] Monitoring activé
- [ ] Tests de charge effectués

## 🔧 Maintenance Post-Déploiement

### Monitoring
```bash
# Logs Laravel
tail -f storage/logs/laravel.log

# Logs Nginx
tail -f /var/log/nginx/error.log
```

### Sauvegardes
```bash
# Script de sauvegarde DB
#!/bin/bash
mysqldump -u pharma_user -p pharma_production > backup_$(date +%Y%m%d).sql
```

### Mises à jour
```bash
# Mise à jour sécurisée
git pull origin main
composer install --no-dev
php artisan migrate --force
php artisan config:cache
```

## 🎯 Recommandation pour Soutenance

**Option la plus simple :**
1. VPS chez OVH/DigitalOcean (5€/mois)
2. Domaine gratuit (.tk, .ml) ou sous-domaine
3. Déploiement manuel avec Nginx
4. Base de données MySQL locale

**Temps estimé :** 2-3 heures pour un déploiement complet.
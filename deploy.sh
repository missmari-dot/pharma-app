#!/bin/bash

# 🚀 Script de déploiement automatique - PharmaMobile
# Usage: ./deploy.sh [production|staging]

ENV=${1:-production}

echo "🚀 Déploiement PharmaMobile - Environnement: $ENV"

# Vérifications préalables
if [ ! -f ".env.$ENV" ]; then
    echo "❌ Fichier .env.$ENV manquant"
    exit 1
fi

# Sauvegarde
echo "📦 Sauvegarde de la base de données..."
php artisan backup:run --only-db

# Mise en mode maintenance
echo "🔧 Activation du mode maintenance..."
php artisan down

# Mise à jour du code
echo "📥 Mise à jour du code..."
git pull origin main

# Installation des dépendances
echo "📦 Installation des dépendances..."
composer install --optimize-autoloader --no-dev

# Configuration
echo "⚙️ Configuration de l'environnement..."
cp .env.$ENV .env

# Migrations
echo "🗄️ Exécution des migrations..."
php artisan migrate --force

# Optimisations
echo "⚡ Optimisation Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Permissions
echo "🔐 Configuration des permissions..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Redémarrage des services
echo "🔄 Redémarrage des services..."
sudo systemctl reload nginx
sudo systemctl reload php8.1-fpm

# Sortie du mode maintenance
echo "✅ Désactivation du mode maintenance..."
php artisan up

echo "🎉 Déploiement terminé avec succès!"
echo "🌐 Site accessible sur: $(php artisan route:list | grep '/' | head -1 | awk '{print $4}')"
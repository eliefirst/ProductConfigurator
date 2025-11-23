#!/bin/bash
#
# Script de recompilation Magento - Correction problème de performance
#
# Ce script nettoie et recompile Magento après suppression du plugin HTML validator
#

set -e  # Arrêter en cas d'erreur

echo "======================================"
echo "RECOMPILATION MAGENTO - FIX PERFORMANCE"
echo "======================================"
echo ""

# Vérifier qu'on est dans le bon répertoire
if [ ! -f "bin/magento" ]; then
    echo "ERREUR: bin/magento introuvable !"
    echo "Ce script doit être exécuté depuis la racine de Magento"
    echo "Exemple: bash app/code/ElielWeb/ProductConfigurator/recompile.sh"
    exit 1
fi

echo "1/6 - Mise en mode maintenance..."
php bin/magento maintenance:enable

echo "2/6 - Nettoyage des caches..."
php bin/magento cache:clean
php bin/magento cache:flush

echo "3/6 - Suppression des fichiers générés..."
rm -rf generated/code/*
rm -rf generated/metadata/*
rm -rf var/cache/*
rm -rf var/page_cache/*
rm -rf var/view_preprocessed/*
echo "   ✓ Fichiers générés supprimés"

echo "4/6 - Recompilation du code DI..."
php bin/magento setup:di:compile

echo "5/6 - Nettoyage final des caches..."
php bin/magento cache:flush

echo "6/6 - Désactivation du mode maintenance..."
php bin/magento maintenance:disable

echo ""
echo "======================================"
echo "✓ RECOMPILATION TERMINÉE AVEC SUCCÈS"
echo "======================================"
echo ""
echo "Le site devrait maintenant être rapide à nouveau."
echo ""
echo "Si le site est toujours lent, vérifiez :"
echo "  - Les logs : var/log/system.log et var/log/exception.log"
echo "  - Le mode de déploiement : bin/magento deploy:mode:show"
echo "  - Les processus en cours : top ou htop"
echo ""

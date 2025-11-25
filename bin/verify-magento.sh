#!/bin/bash

# Script de vérification de l'état de Magento après restauration

MAGE_DIR="/data/www/magento2-preprod"
DB_NAME="magento_preprod"
DB_USER="magento_preprod_user"
DB_PASS="Ut4h0us123"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${GREEN}=== Vérification de l'installation Magento ===${NC}\n"

# Fonction pour afficher le statut
check_status() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓${NC} $2"
    else
        echo -e "${RED}✗${NC} $2"
    fi
}

# 1. Vérifier que le répertoire Magento existe
if [ -d "$MAGE_DIR" ]; then
    check_status 0 "Répertoire Magento: $MAGE_DIR"
else
    check_status 1 "Répertoire Magento: $MAGE_DIR"
    exit 1
fi

cd "$MAGE_DIR" || exit 1

# 2. Vérifier les fichiers critiques
echo -e "\n${YELLOW}Fichiers critiques:${NC}"
[ -f "bin/magento" ] && check_status 0 "bin/magento" || check_status 1 "bin/magento"
[ -f "app/etc/env.php" ] && check_status 0 "app/etc/env.php" || check_status 1 "app/etc/env.php"
[ -f "app/etc/config.php" ] && check_status 0 "app/etc/config.php" || check_status 1 "app/etc/config.php"

# 3. Vérifier la connexion à la base de données
echo -e "\n${YELLOW}Base de données:${NC}"
mariadb -u"$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME; SELECT 1;" &>/dev/null
check_status $? "Connexion à la base de données: $DB_NAME"

# Compter les tables
if [ $? -eq 0 ]; then
    table_count=$(mariadb -u"$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME; SHOW TABLES;" 2>/dev/null | wc -l)
    table_count=$((table_count - 1))
    echo -e "  ${BLUE}→${NC} Nombre de tables: $table_count"
fi

# 4. Vérifier le mode Magento
echo -e "\n${YELLOW}Configuration Magento:${NC}"
if [ -f "bin/magento" ]; then
    mode=$(php bin/magento deploy:mode:show 2>/dev/null | grep -o 'developer\|production\|default')
    if [ -n "$mode" ]; then
        check_status 0 "Mode: $mode"
    else
        check_status 1 "Mode: Impossible à déterminer"
    fi

    # Statut de maintenance
    if [ -f "var/.maintenance.flag" ]; then
        check_status 1 "Mode maintenance: ACTIVÉ (désactiver avec: php bin/magento maintenance:disable)"
    else
        check_status 0 "Mode maintenance: désactivé"
    fi
fi

# 5. Vérifier les permissions
echo -e "\n${YELLOW}Permissions:${NC}"
[ -w "var" ] && check_status 0 "var/ accessible en écriture" || check_status 1 "var/ accessible en écriture"
[ -w "pub/media" ] && check_status 0 "pub/media/ accessible en écriture" || check_status 1 "pub/media/ accessible en écriture"
[ -w "pub/static" ] && check_status 0 "pub/static/ accessible en écriture" || check_status 1 "pub/static/ accessible en écriture"

# 6. Vérifier les caches
echo -e "\n${YELLOW}Caches:${NC}"
cache_size=$(du -sh var/cache 2>/dev/null | cut -f1)
echo -e "  ${BLUE}→${NC} Taille du cache: $cache_size"

page_cache_size=$(du -sh var/page_cache 2>/dev/null | cut -f1)
echo -e "  ${BLUE}→${NC} Taille du page_cache: $page_cache_size"

# 7. Vérifier les logs récents
echo -e "\n${YELLOW}Logs récents:${NC}"
if [ -f "var/log/system.log" ]; then
    recent_errors=$(tail -100 var/log/system.log 2>/dev/null | grep -i "error\|exception\|critical" | wc -l)
    if [ $recent_errors -gt 0 ]; then
        check_status 1 "Erreurs dans system.log: $recent_errors (100 dernières lignes)"
        echo -e "  ${YELLOW}→ Voir: tail -50 var/log/system.log${NC}"
    else
        check_status 0 "Pas d'erreur dans system.log (100 dernières lignes)"
    fi
else
    echo -e "  ${BLUE}→${NC} Fichier system.log non trouvé"
fi

if [ -f "var/log/exception.log" ]; then
    exception_size=$(wc -l < var/log/exception.log 2>/dev/null)
    recent_exceptions=$(tail -100 var/log/exception.log 2>/dev/null | wc -l)
    if [ $recent_exceptions -gt 0 ]; then
        check_status 1 "Exceptions récentes: $recent_exceptions"
        echo -e "  ${YELLOW}→ Voir: tail -20 var/log/exception.log${NC}"
    else
        check_status 0 "Pas d'exception récente"
    fi
fi

# 8. Vérifier les modules
echo -e "\n${YELLOW}Modules Magento:${NC}"
if [ -f "bin/magento" ]; then
    enabled_modules=$(php bin/magento module:status 2>/dev/null | grep -A 1000 "List of enabled modules" | grep "Eliefirst_ProductConfigurator" || echo "")
    if [ -n "$enabled_modules" ]; then
        check_status 0 "Module Eliefirst_ProductConfigurator: activé"
    else
        check_status 1 "Module Eliefirst_ProductConfigurator: non trouvé ou désactivé"
    fi
fi

# 9. Vérifier l'espace disque
echo -e "\n${YELLOW}Espace disque:${NC}"
disk_usage=$(df -h "$MAGE_DIR" | tail -1 | awk '{print $5}' | sed 's/%//')
disk_info=$(df -h "$MAGE_DIR" | tail -1 | awk '{print "  Utilisé: " $3 " / " $2 " (" $5 ")"}')
echo "$disk_info"

if [ "$disk_usage" -gt 90 ]; then
    check_status 1 "Espace disque critique: ${disk_usage}%"
elif [ "$disk_usage" -gt 80 ]; then
    echo -e "  ${YELLOW}⚠${NC} Attention: espace disque à ${disk_usage}%"
else
    check_status 0 "Espace disque OK: ${disk_usage}%"
fi

# 10. Tester la page d'accueil (si URL fournie)
echo -e "\n${YELLOW}Test HTTP (optionnel):${NC}"
echo "Pour tester le site, exécuter:"
echo "  curl -I https://votre-site-preprod.com"
echo "  curl -s https://votre-site-preprod.com | grep -i 'error\|exception'"

# Résumé
echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}Vérification terminée${NC}"
echo -e "${GREEN}========================================${NC}\n"

echo -e "${BLUE}Commandes utiles:${NC}"
echo "  # Nettoyer les caches"
echo "  php bin/magento cache:flush"
echo ""
echo "  # Reindexer"
echo "  php bin/magento indexer:reindex"
echo ""
echo "  # Voir les logs en temps réel"
echo "  tail -f var/log/system.log"
echo "  tail -f var/log/exception.log"
echo ""
echo "  # Vérifier les modules"
echo "  php bin/magento module:status"
echo ""
echo "  # Mode maintenance"
echo "  php bin/magento maintenance:enable"
echo "  php bin/magento maintenance:disable"
echo ""

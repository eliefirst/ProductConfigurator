#!/bin/bash

# Script de restauration rapide - restaure automatiquement le backup le plus récent du 23/11

# --- CONFIGURATION ---
BACKUP_DIR="/data/backups/preprod2"
MAGE_DIR="/data/www/magento2-preprod"
DB_NAME="magento_preprod"
DB_USER="magento_preprod_user"
DB_PASS="Ut4h0us123"
MYSQL_CMD="mariadb"
# ---------------------

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}=== Quick Restore - Backup du 23/11 ===${NC}\n"

# Trouver le backup du 23/11 (chercher les backups commençant par 20251123)
echo "Recherche des backups du 23/11..."
BACKUP_2311=$(find "$BACKUP_DIR" -maxdepth 1 -type d -name "20251123_*" | sort -r | head -1)

if [ -z "$BACKUP_2311" ]; then
    echo -e "${RED}Aucun backup trouvé pour le 23/11/2025${NC}"
    echo "Backups disponibles:"
    ls -la "$BACKUP_DIR" | grep "^d" | grep -v "^\." | tail -10
    exit 1
fi

BACKUP_NAME=$(basename "$BACKUP_2311")
echo -e "${GREEN}Backup trouvé: $BACKUP_NAME${NC}"
echo "Chemin: $BACKUP_2311"

# Vérifier les fichiers
DB_BACKUP="$BACKUP_2311/db.sql.gz"
FILES_BACKUP="$BACKUP_2311/files.tar.gz"

echo -e "\nVérification des fichiers..."
if [ ! -f "$DB_BACKUP" ]; then
    echo -e "${RED}ERROR: Fichier DB manquant: $DB_BACKUP${NC}"
    exit 1
fi
echo -e "${GREEN}✓${NC} Base de données: $(du -h "$DB_BACKUP" | cut -f1)"

if [ ! -f "$FILES_BACKUP" ]; then
    echo -e "${RED}ERROR: Fichier files manquant: $FILES_BACKUP${NC}"
    exit 1
fi
echo -e "${GREEN}✓${NC} Fichiers: $(du -h "$FILES_BACKUP" | cut -f1)"

# Confirmation
echo -e "\n${YELLOW}ATTENTION: Cette opération va:${NC}"
echo "  1. Créer un backup de sécurité de l'état actuel"
echo "  2. Restaurer la base de données du $BACKUP_NAME"
echo "  3. Restaurer les fichiers (app/etc, pub/media, var/)"
echo "  4. Nettoyer tous les caches"
echo "  5. Recompiler Magento"
echo ""
read -p "Continuer? [yes/NO]: " confirm

if [ "$confirm" != "yes" ]; then
    echo "Restauration annulée."
    exit 0
fi

# Appeler le script de restauration complet
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [ -f "$SCRIPT_DIR/restore.sh" ]; then
    echo -e "\n${GREEN}Lancement de la restauration...${NC}\n"
    bash "$SCRIPT_DIR/restore.sh" "$BACKUP_NAME"
else
    echo -e "${RED}ERROR: Script restore.sh non trouvé dans $SCRIPT_DIR${NC}"
    echo "Veuillez utiliser la restauration manuelle:"
    echo ""
    echo "cd $MAGE_DIR"
    echo "php bin/magento maintenance:enable"
    echo "gunzip < $DB_BACKUP | $MYSQL_CMD -u$DB_USER -p$DB_PASS $DB_NAME"
    echo "tar xzf $FILES_BACKUP -C $MAGE_DIR"
    echo "rm -rf var/cache/* var/page_cache/* generated/*"
    echo "php bin/magento setup:upgrade"
    echo "php bin/magento setup:di:compile"
    echo "php bin/magento cache:flush"
    echo "php bin/magento maintenance:disable"
    exit 1
fi

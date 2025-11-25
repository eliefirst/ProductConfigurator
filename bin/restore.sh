#!/bin/bash

# --- CONFIGURATION ---
BACKUP_DIR="/data/backups/preprod2"
MAGE_DIR="/data/www/magento2-preprod"
DB_NAME="magento_preprod"
DB_USER="magento_preprod_user"
DB_PASS="Ut4h0us123" # Attention: mot de passe sensible en clair.
MYSQL_CMD="mariadb" # Mettre à jour si nécessaire (ex: mysql)
# ---------------------

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== Magento Preprod Restore Script ===${NC}\n"

# Vérifier que le script est exécuté en tant que root ou avec les bonnes permissions
if [ ! -w "$MAGE_DIR" ]; then
    echo -e "${RED}ERROR: No write permission to $MAGE_DIR${NC}"
    echo "Please run with appropriate permissions."
    exit 1
fi

# Liste des backups disponibles
echo -e "${YELLOW}Available backups:${NC}"
if [ ! -d "$BACKUP_DIR" ]; then
    echo -e "${RED}ERROR: Backup directory $BACKUP_DIR does not exist.${NC}"
    exit 1
fi

# Afficher les backups disponibles avec leur taille
backups=($(find "$BACKUP_DIR" -maxdepth 1 -type d -name "*_*" | sort -r))

if [ ${#backups[@]} -eq 0 ]; then
    echo -e "${RED}No backups found in $BACKUP_DIR${NC}"
    exit 1
fi

echo ""
for i in "${!backups[@]}"; do
    backup_name=$(basename "${backups[$i]}")
    backup_size=$(du -sh "${backups[$i]}" | cut -f1)
    echo "[$i] $backup_name (Size: $backup_size)"
done

# Sélection du backup à restaurer
echo ""
if [ -n "$1" ]; then
    # Si un argument est passé, l'utiliser comme index ou nom de backup
    if [[ "$1" =~ ^[0-9]+$ ]] && [ "$1" -lt "${#backups[@]}" ]; then
        SELECTED_BACKUP="${backups[$1]}"
    else
        # Chercher par nom/date
        SELECTED_BACKUP="$BACKUP_DIR/$1"
        if [ ! -d "$SELECTED_BACKUP" ]; then
            echo -e "${RED}ERROR: Backup '$1' not found.${NC}"
            exit 1
        fi
    fi
else
    read -p "Select backup to restore [0-$((${#backups[@]}-1))]: " selection
    if [[ ! "$selection" =~ ^[0-9]+$ ]] || [ "$selection" -ge "${#backups[@]}" ]; then
        echo -e "${RED}Invalid selection.${NC}"
        exit 1
    fi
    SELECTED_BACKUP="${backups[$selection]}"
fi

BACKUP_NAME=$(basename "$SELECTED_BACKUP")
echo -e "\n${GREEN}Selected backup: $BACKUP_NAME${NC}"

# Vérifier que les fichiers de backup existent
DB_BACKUP="$SELECTED_BACKUP/db.sql.gz"
FILES_BACKUP="$SELECTED_BACKUP/files.tar.gz"

if [ ! -f "$DB_BACKUP" ]; then
    echo -e "${RED}ERROR: Database backup not found: $DB_BACKUP${NC}"
    exit 1
fi

if [ ! -f "$FILES_BACKUP" ]; then
    echo -e "${RED}ERROR: Files backup not found: $FILES_BACKUP${NC}"
    exit 1
fi

# Confirmation avant restauration
echo -e "\n${YELLOW}WARNING: This will overwrite the current database and files!${NC}"
echo "Database: $DB_NAME"
echo "Directory: $MAGE_DIR"
echo ""
read -p "Are you sure you want to continue? [yes/NO]: " confirm

if [ "$confirm" != "yes" ]; then
    echo "Restore cancelled."
    exit 0
fi

# Créer un backup de sécurité avant restauration
SAFETY_BACKUP="$BACKUP_DIR/before-restore-$(date +%Y%m%d_%H%M%S)"
echo -e "\n${YELLOW}Creating safety backup before restore...${NC}"
mkdir -p "$SAFETY_BACKUP"

echo "Backing up current database..."
$MYSQL_CMD-dump --quick -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$SAFETY_BACKUP/db.sql.gz"

echo "Backing up current files..."
tar czf "$SAFETY_BACKUP/files.tar.gz" -C "$MAGE_DIR" \
    app/etc \
    pub/media \
    var/.setup_cronjob_status \
    var/.update_cronjob_status 2>/dev/null

echo -e "${GREEN}Safety backup created: $SAFETY_BACKUP${NC}"

# Mettre Magento en mode maintenance
echo -e "\n${YELLOW}Enabling maintenance mode...${NC}"
cd "$MAGE_DIR" || exit 1
php bin/magento maintenance:enable 2>/dev/null

# Restauration de la base de données
echo -e "\n${YELLOW}Restoring database...${NC}"
gunzip < "$DB_BACKUP" | $MYSQL_CMD -u"$DB_USER" -p"$DB_PASS" "$DB_NAME"

if [ $? -ne 0 ]; then
    echo -e "${RED}ERROR: Database restore failed!${NC}"
    echo "You can restore the safety backup from: $SAFETY_BACKUP"
    exit 1
fi

echo -e "${GREEN}Database restored successfully.${NC}"

# Restauration des fichiers
echo -e "\n${YELLOW}Restoring files...${NC}"
tar xzf "$FILES_BACKUP" -C "$MAGE_DIR"

if [ $? -ne 0 ]; then
    echo -e "${RED}ERROR: Files restore failed!${NC}"
    exit 1
fi

echo -e "${GREEN}Files restored successfully.${NC}"

# Nettoyage des caches
echo -e "\n${YELLOW}Cleaning Magento caches...${NC}"
rm -rf "$MAGE_DIR/var/cache"/* 2>/dev/null
rm -rf "$MAGE_DIR/var/page_cache"/* 2>/dev/null
rm -rf "$MAGE_DIR/var/view_preprocessed"/* 2>/dev/null
rm -rf "$MAGE_DIR/generated/code"/* 2>/dev/null
rm -rf "$MAGE_DIR/generated/metadata"/* 2>/dev/null

# Réinitialiser les permissions (adapter selon vos besoins)
echo -e "${YELLOW}Setting permissions...${NC}"
cd "$MAGE_DIR" || exit 1
find var generated vendor pub/static pub/media app/etc -type f -exec chmod g+w {} + 2>/dev/null
find var generated vendor pub/static pub/media app/etc -type d -exec chmod g+ws {} + 2>/dev/null

# Lancer les commandes Magento post-restauration
echo -e "\n${YELLOW}Running Magento commands...${NC}"
php bin/magento cache:flush
php bin/magento cache:clean
php bin/magento setup:upgrade --keep-generated 2>/dev/null || php bin/magento setup:upgrade
php bin/magento setup:di:compile 2>/dev/null
php bin/magento setup:static-content:deploy -f 2>/dev/null
php bin/magento indexer:reindex

# Désactiver le mode maintenance
echo -e "\n${YELLOW}Disabling maintenance mode...${NC}"
php bin/magento maintenance:disable

echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}Restore completed successfully!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo "Restored from: $BACKUP_NAME"
echo "Safety backup: $SAFETY_BACKUP"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Test your website"
echo "2. Check logs: $MAGE_DIR/var/log/"
echo "3. If issues occur, you can restore the safety backup"
echo ""

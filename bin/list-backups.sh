#!/bin/bash

# Script pour lister tous les backups disponibles avec détails

BACKUP_DIR="/data/backups/preprod2"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${GREEN}=== Backups Magento Preprod ===${NC}\n"

if [ ! -d "$BACKUP_DIR" ]; then
    echo -e "${RED}ERROR: Répertoire de backup non trouvé: $BACKUP_DIR${NC}"
    exit 1
fi

# Trouver tous les backups
backups=($(find "$BACKUP_DIR" -maxdepth 1 -type d -name "*_*" | sort -r))

if [ ${#backups[@]} -eq 0 ]; then
    echo -e "${RED}Aucun backup trouvé dans $BACKUP_DIR${NC}"
    exit 1
fi

echo -e "Répertoire: ${BLUE}$BACKUP_DIR${NC}\n"
echo "----------------------------------------"

for backup in "${backups[@]}"; do
    backup_name=$(basename "$backup")

    # Extraire la date du nom du backup
    backup_date=$(echo "$backup_name" | cut -d'_' -f1)
    backup_time=$(echo "$backup_name" | cut -d'_' -f2)

    # Formater la date pour affichage
    if [ ${#backup_date} -eq 8 ]; then
        year=${backup_date:0:4}
        month=${backup_date:4:2}
        day=${backup_date:6:2}
        formatted_date="$day/$month/$year"
    else
        formatted_date="$backup_date"
    fi

    # Formater l'heure
    if [ ${#backup_time} -eq 6 ]; then
        hour=${backup_time:0:2}
        min=${backup_time:2:2}
        sec=${backup_time:4:2}
        formatted_time="${hour}:${min}:${sec}"
    else
        formatted_time="$backup_time"
    fi

    # Taille totale du backup
    total_size=$(du -sh "$backup" 2>/dev/null | cut -f1)

    # Vérifier les fichiers présents
    db_file="$backup/db.sql.gz"
    files_file="$backup/files.tar.gz"

    db_status="${RED}✗ Manquant${NC}"
    db_size=""
    if [ -f "$db_file" ]; then
        db_status="${GREEN}✓${NC}"
        db_size=$(du -h "$db_file" | cut -f1)
    fi

    files_status="${RED}✗ Manquant${NC}"
    files_size=""
    if [ -f "$files_file" ]; then
        files_status="${GREEN}✓${NC}"
        files_size=$(du -h "$files_file" | cut -f1)
    fi

    # Âge du backup
    backup_timestamp=$(stat -c %Y "$backup" 2>/dev/null)
    current_timestamp=$(date +%s)
    age_seconds=$((current_timestamp - backup_timestamp))
    age_days=$((age_seconds / 86400))

    # Affichage
    echo -e "${YELLOW}$backup_name${NC}"
    echo -e "  📅 Date: $formatted_date à $formatted_time"
    echo -e "  ⏱️  Âge: $age_days jours"
    echo -e "  💾 Taille totale: $total_size"
    echo -e "  🗄️  Base de données: $db_status $db_size"
    echo -e "  📁 Fichiers: $files_status $files_size"
    echo ""
done

echo "----------------------------------------"
echo -e "Total: ${GREEN}${#backups[@]}${NC} backup(s)"

# Afficher l'espace disque
echo ""
echo -e "${YELLOW}Espace disque:${NC}"
df -h "$BACKUP_DIR" | tail -1 | awk '{print "  Utilisé: " $3 " / " $2 " (" $5 ")"}'

# Highlight des backups du 23/11
echo ""
echo -e "${YELLOW}Backups du 23/11/2025:${NC}"
nov23_backups=$(find "$BACKUP_DIR" -maxdepth 1 -type d -name "20251123_*" | sort -r)
if [ -z "$nov23_backups" ]; then
    echo -e "  ${RED}Aucun backup trouvé pour le 23/11${NC}"
else
    echo "$nov23_backups" | while read b; do
        bname=$(basename "$b")
        bsize=$(du -sh "$b" | cut -f1)
        btime=$(echo "$bname" | cut -d'_' -f2 | sed 's/\(..\)\(..\)\(..\)/\1:\2:\3/')
        echo -e "  ${GREEN}✓${NC} $bname (${btime}) - $bsize"
    done
fi

echo ""
echo -e "${BLUE}Pour restaurer un backup:${NC}"
echo "  ./restore.sh              # Mode interactif"
echo "  ./quick-restore.sh        # Restaurer le backup du 23/11"
echo "  ./restore.sh 20251123_HHMMSS  # Restaurer un backup spécifique"
echo ""

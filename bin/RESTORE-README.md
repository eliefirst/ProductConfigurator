# Scripts de Restauration Magento Preprod

## 📋 Vue d'ensemble

Deux scripts sont disponibles pour restaurer les backups Magento:

1. **restore.sh** - Script complet avec sélection interactive
2. **quick-restore.sh** - Restauration rapide du backup du 23/11

## 🚀 Utilisation

### Option 1: Restauration rapide du backup du 23/11

```bash
cd /home/user/ProductConfigurator/bin
./quick-restore.sh
```

Ce script:
- ✅ Trouve automatiquement le dernier backup du 23/11/2025
- ✅ Crée un backup de sécurité avant restauration
- ✅ Restaure la base de données ET les fichiers
- ✅ Nettoie tous les caches
- ✅ Recompile Magento

### Option 2: Restauration interactive (choisir un backup)

```bash
cd /home/user/ProductConfigurator/bin
./restore.sh
```

Le script affichera la liste des backups disponibles:
```
Available backups:

[0] 20251123_235959 (Size: 1.2G)
[1] 20251123_180000 (Size: 1.1G)
[2] 20251122_235959 (Size: 1.2G)

Select backup to restore [0-2]:
```

### Option 3: Restauration directe par date

```bash
./restore.sh 20251123_235959
# ou
./restore.sh 0  # utiliser l'index
```

## ⚠️ Important

### Le script effectue automatiquement:

1. **Backup de sécurité** - Un backup complet de l'état actuel avant restauration
2. **Mode maintenance** - Active le mode maintenance pendant la restauration
3. **Restauration DB** - Restaure la base de données
4. **Restauration fichiers** - Restaure app/etc, pub/media, var/
5. **Nettoyage caches** - Supprime var/cache, var/page_cache, generated/
6. **Commandes Magento**:
   - `setup:upgrade`
   - `setup:di:compile`
   - `setup:static-content:deploy`
   - `indexer:reindex`
   - `cache:flush`
7. **Désactive maintenance** - Remet le site en ligne

### Chemins de backup

- **Répertoire backups**: `/data/backups/preprod2/`
- **Magento**: `/data/www/magento2-preprod/`
- **Base de données**: `magento_preprod`

### Structure d'un backup

```
/data/backups/preprod2/20251123_235959/
├── db.sql.gz           # Dump de la base de données
└── files.tar.gz        # app/etc, pub/media, var/
```

## 🔧 Restauration manuelle (si nécessaire)

Si les scripts ne fonctionnent pas, restauration manuelle:

```bash
# 1. Variables
BACKUP_DATE="20251123_235959"
BACKUP_DIR="/data/backups/preprod2/$BACKUP_DATE"
MAGE_DIR="/data/www/magento2-preprod"

# 2. Mode maintenance
cd $MAGE_DIR
php bin/magento maintenance:enable

# 3. Restaurer la base de données
gunzip < $BACKUP_DIR/db.sql.gz | mariadb -umagento_preprod_user -pUt4h0us123 magento_preprod

# 4. Restaurer les fichiers
tar xzf $BACKUP_DIR/files.tar.gz -C $MAGE_DIR

# 5. Nettoyer les caches
rm -rf var/cache/* var/page_cache/* var/view_preprocessed/*
rm -rf generated/code/* generated/metadata/*

# 6. Commandes Magento
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
php bin/magento indexer:reindex
php bin/magento cache:flush

# 7. Désactiver maintenance
php bin/magento maintenance:disable
```

## 📊 Vérification après restauration

```bash
# Vérifier les logs
tail -f /data/www/magento2-preprod/var/log/system.log
tail -f /data/www/magento2-preprod/var/log/exception.log

# Vérifier le statut Magento
cd /data/www/magento2-preprod
php bin/magento deploy:mode:show
php bin/magento module:status

# Tester le site
curl -I https://votre-site-preprod.com
```

## 🆘 En cas de problème

Si la restauration échoue, un backup de sécurité est créé automatiquement:

```
/data/backups/preprod2/before-restore-YYYYMMDD_HHMMSS/
```

Pour restaurer ce backup de sécurité:
```bash
./restore.sh before-restore-YYYYMMDD_HHMMSS
```

## 🔒 Sécurité

⚠️ **ATTENTION**: Les mots de passe sont en clair dans les scripts.

Pour améliorer la sécurité, créer un fichier `.my.cnf`:

```bash
# Dans /root/.my.cnf
[client]
user=magento_preprod_user
password=Ut4h0us123

# Puis dans les scripts, remplacer:
mariadb -umagento_preprod_user -pUt4h0us123
# par:
mariadb
```

## 📝 Logs

Les logs de restauration sont affichés en temps réel. En cas d'erreur, vérifier:

- Permissions sur `/data/www/magento2-preprod`
- Connexion à la base de données
- Espace disque disponible
- Logs Magento dans `var/log/`

# Instructions de Recompilation Magento - URGENT

## Problème
Le plugin HTML validator cause des problèmes de performance sévères.
Il a été **DÉSACTIVÉ** dans etc/adminhtml/di.xml.

## Commandes à exécuter IMMÉDIATEMENT

Exécutez ces commandes dans l'ordre depuis le répertoire racine de Magento :

```bash
# 1. Nettoyer tous les caches
bin/magento cache:clean
bin/magento cache:flush

# 2. Supprimer les fichiers générés
rm -rf generated/code/*
rm -rf generated/metadata/*
rm -rf var/cache/*
rm -rf var/page_cache/*
rm -rf var/view_preprocessed/*

# 3. Recompiler le code
bin/magento setup:di:compile

# 4. Déployer les assets statiques (si nécessaire)
# bin/magento setup:static-content:deploy -f

# 5. Vérifier que le cache est bien nettoyé
bin/magento cache:flush
```

## Mode Production

Si vous êtes en mode production, exécutez aussi :

```bash
bin/magento deploy:mode:set production
```

## Mode Developer

Si vous êtes en mode développeur :

```bash
bin/magento deploy:mode:set developer
bin/magento cache:clean
```

## Vérification

Après avoir exécuté ces commandes :
1. Testez la vitesse du site
2. Si le site est redevenu rapide, le problème venait bien du plugin
3. Le plugin est maintenant désactivé - les balises span avec attributs ne seront plus préservées

## Solution Alternative

Pour permettre les balises span sans impact sur les performances, il faudra :
1. Utiliser une configuration au niveau de la base de données Magento
2. Ou modifier directement la configuration HTML Purifier
3. Ou utiliser un module tiers optimisé

## Status Actuel

- ✅ Plugin désactivé dans etc/adminhtml/di.xml
- ❌ Les balises span avec attributs personnalisés seront filtrées
- ⚠️  Nécessite une recompilation complète de Magento

**IMPORTANT** : Exécutez les commandes ci-dessus IMMÉDIATEMENT pour restaurer les performances.

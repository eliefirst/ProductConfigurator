# Guide de Déploiement - Gold Color Selector

## 🎯 Objectif

Déployer le module `ElielWeb_ProductConfigurator` avec le sélecteur de couleurs d'or sur le serveur de production, en cohabitation avec le module Aitoc Options Management.

---

## 🔧 Prérequis

- Accès SSH au serveur production
- Accès au backoffice Magento
- Le module Aitoc Options Management est installé et activé

---

## 📦 Étape 1 : Déployer le code du module

### Option A : Via Git (Recommandé)

```bash
# Connexion SSH au serveur
ssh root@production3

# Aller dans le répertoire Magento
cd /data/www/magento2

# Si le module n'existe pas encore, le cloner
cd app/code/ElielWeb/
git clone https://github.com/eliefirst/ProductConfigurator.git

# Ou mettre à jour si déjà présent
cd ProductConfigurator
git fetch origin
git checkout claude/gold-color-variants-011caXNF54KPMZvZpTafHkzJ
git pull origin claude/gold-color-variants-011caXNF54KPMZvZpTafHkzJ
```

### Option B : Via FTP/SCP

Copiez tout le contenu du module vers :
```
/data/www/magento2/app/code/ElielWeb/ProductConfigurator/
```

---

## ⚙️ Étape 2 : Installation du module

```bash
cd /data/www/magento2

# 1. Activer le module
bin/magento module:enable ElielWeb_ProductConfigurator

# 2. Exécuter le setup (crée les attributs gold_color et gold_variant_group)
bin/magento setup:upgrade

# 3. Compiler le code
bin/magento setup:di:compile

# 4. Déployer le contenu statique
bin/magento setup:static-content:deploy -f fr_FR en_US

# 5. Vider tous les caches
bin/magento cache:flush

# 6. Réindexer les attributs
bin/magento indexer:reindex catalog_product_attribute
bin/magento indexer:reindex catalogsearch_fulltext
```

---

## 🔍 Étape 3 : Vérification de l'installation

### 3.1 Vérifier que le module est activé

```bash
bin/magento module:status ElielWeb_ProductConfigurator
# Résultat attendu : "Module is enabled"
```

### 3.2 Vérifier que le module se charge APRÈS Aitoc

```bash
bin/magento module:status | grep -E "(Aitoc|ElielWeb)"
# Vérifier que les deux modules sont activés
```

### 3.3 Vérifier que les attributs sont créés

```bash
mariadb -u magento_user -p magento -e "
SELECT
    attribute_code,
    attribute_id,
    frontend_label,
    backend_type
FROM eav_attribute
WHERE attribute_code IN ('gold_color', 'gold_variant_group')
AND entity_type_id = (SELECT entity_type_id FROM eav_entity_type WHERE entity_type_code = 'catalog_product');
"
```

**Résultat attendu :**
```
+---------------------+--------------+----------------+--------------+
| attribute_code      | attribute_id | frontend_label | backend_type |
+---------------------+--------------+----------------+--------------+
| gold_color          | XXX          | Gold Color     | varchar      |
| gold_variant_group  | XXX          | Gold Variant...| varchar      |
+---------------------+--------------+----------------+--------------+
```

---

## 🏷️ Étape 4 : Configuration des produits

Pour que le sélecteur de couleurs d'or s'affiche, vous devez configurer **AU MOINS 2 produits** avec les mêmes attributs.

### 4.1 Via le Backoffice Magento (Recommandé)

1. Connectez-vous au backoffice Magento
2. Allez dans **Catalog > Products**
3. Pour le **Bracelet Pure Or Jaune (SKU: 1J)** :
   - Éditez le produit
   - Descendez à la section **Product Details**
   - **Gold Color** : Sélectionnez `Or Jaune`
   - **Gold Variant Group** : Entrez `PURE-FIL` (en majuscules)
   - Sauvegardez

4. Pour le **Bracelet Pure Or Blanc (SKU: 1B)** :
   - **Gold Color** : Sélectionnez `Or Blanc`
   - **Gold Variant Group** : Entrez `PURE-FIL` (EXACTEMENT le même)
   - Sauvegardez

5. Pour le **Bracelet Pure Or Rose (SKU: 1R)** :
   - **Gold Color** : Sélectionnez `Or Rose`
   - **Gold Variant Group** : Entrez `PURE-FIL`
   - Sauvegardez

6. Pour le **Bracelet Pure Or Noir (SKU: 1N)** :
   - **Gold Color** : Sélectionnez `Or Noir`
   - **Gold Variant Group** : Entrez `PURE-FIL`
   - Sauvegardez

### 4.2 Vérification SQL

Vérifiez que les attributs sont bien configurés :

```bash
mariadb -u magento_user -p magento -e "
SELECT
    p.sku,
    p.entity_id,
    COALESCE(eav_color.value, eav_color_int.value) as gold_color,
    COALESCE(eav_group.value, eav_group_text.value) as gold_variant_group
FROM catalog_product_entity p
LEFT JOIN catalog_product_entity_varchar eav_color
    ON p.entity_id = eav_color.entity_id
    AND eav_color.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'gold_color')
LEFT JOIN catalog_product_entity_int eav_color_int
    ON p.entity_id = eav_color_int.entity_id
    AND eav_color_int.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'gold_color')
LEFT JOIN catalog_product_entity_varchar eav_group
    ON p.entity_id = eav_group.entity_id
    AND eav_group.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'gold_variant_group')
LEFT JOIN catalog_product_entity_text eav_group_text
    ON p.entity_id = eav_group_text.entity_id
    AND eav_group_text.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'gold_variant_group')
WHERE p.sku IN ('1J', '1B', '1R', '1N')
ORDER BY p.sku;
"
```

**Résultat attendu :**
```
+-----+-----------+------------+---------------------+
| sku | entity_id | gold_color | gold_variant_group  |
+-----+-----------+------------+---------------------+
| 1B  | XXX       | Or Blanc   | PURE-FIL            |
| 1J  | XXX       | Or Jaune   | PURE-FIL            |
| 1N  | XXX       | Or Noir    | PURE-FIL            |
| 1R  | XXX       | Or Rose    | PURE-FIL            |
+-----+-----------+------------+---------------------+
```

---

## 🧹 Étape 5 : Nettoyage final

```bash
cd /data/www/magento2

# Vider tous les caches
bin/magento cache:flush

# Réindexer
bin/magento indexer:reindex

# Redémarrer PHP-FPM (si nécessaire)
systemctl restart php8.4-fpm

# Redémarrer Nginx/Apache (si nécessaire)
systemctl restart nginx
# ou
systemctl restart apache2
```

---

## ✅ Étape 6 : Test final

1. Ouvrez votre navigateur en mode navigation privée (pour éviter le cache)
2. Allez sur : `https://www.elielweb.com/bracelet-pure-fil-or-jaune.html`
3. Vous devriez voir **avant les options personnalisées** :
   - Un titre **"COULEUR D'OR"**
   - 4 pastilles de couleurs (blanc, jaune, rose, noir/gris)
   - La pastille jaune avec une bordure bleue et une coche (produit actuel)
   - Les autres pastilles cliquables

4. Cliquez sur la pastille "Or Blanc" → Vous devriez être redirigé vers le produit SKU 1B

---

## 🐛 Troubleshooting

### Le sélecteur ne s'affiche pas

**Causes possibles :**

1. **Le module n'est pas activé**
   ```bash
   bin/magento module:status ElielWeb_ProductConfigurator
   ```

2. **Les attributs ne sont pas créés**
   ```bash
   # Relancer le setup
   bin/magento setup:upgrade
   bin/magento cache:flush
   ```

3. **Les produits n'ont pas le même `gold_variant_group`**
   - Vérifiez avec la requête SQL ci-dessus
   - Les 4 produits doivent avoir EXACTEMENT le même groupe

4. **Il n'y a qu'un seul produit configuré**
   - Le sélecteur ne s'affiche que s'il y a AU MOINS 2 variantes

5. **Le cache n'est pas vidé**
   ```bash
   bin/magento cache:flush
   bin/magento indexer:reindex
   ```

6. **Conflit de layout avec Aitoc**
   - Vérifiez que `etc/module.xml` contient bien `<module name="Aitoc_OptionsManagement"/>` dans la séquence

### Les couleurs ne correspondent pas

Vérifiez que l'attribut `gold_color` est bien renseigné avec les valeurs exactes :
- `Or Blanc` (avec majuscules et espace)
- `Or Jaune`
- `Or Rose`
- `Or Noir`

### Erreur "Invalid Form Key"

Cette erreur a été corrigée dans les commits récents. Assurez-vous d'avoir la dernière version du module.

---

## 📊 Requêtes SQL utiles

### Lister tous les produits avec leurs couleurs d'or

```sql
SELECT
    p.sku,
    p.entity_id,
    COALESCE(eav_color_text.value, eav_color_int.value) as gold_color,
    eav_group.value as gold_variant_group,
    ps.value as status
FROM catalog_product_entity p
LEFT JOIN catalog_product_entity_varchar eav_color_text
    ON p.entity_id = eav_color_text.entity_id
    AND eav_color_text.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'gold_color')
LEFT JOIN catalog_product_entity_int eav_color_int
    ON p.entity_id = eav_color_int.entity_id
    AND eav_color_int.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'gold_color')
LEFT JOIN catalog_product_entity_varchar eav_group
    ON p.entity_id = eav_group.entity_id
    AND eav_group.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'gold_variant_group')
LEFT JOIN catalog_product_entity_int ps
    ON p.entity_id = ps.entity_id
    AND ps.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'status')
WHERE eav_group.value IS NOT NULL
ORDER BY eav_group.value, p.sku;
```

### Compter les variantes par groupe

```sql
SELECT
    eav_group.value as variant_group,
    COUNT(*) as nb_variants
FROM catalog_product_entity p
LEFT JOIN catalog_product_entity_varchar eav_group
    ON p.entity_id = eav_group.entity_id
    AND eav_group.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'gold_variant_group')
WHERE eav_group.value IS NOT NULL
GROUP BY eav_group.value
HAVING nb_variants >= 2;
```

---

## 📝 Notes importantes

- Le module fonctionne avec **Luma** et **Hyva** themes
- Compatible avec **Magento 2.4.8+** et **PHP 8.4**
- Le module utilise **Alpine.js** pour l'interactivité
- Le sélecteur est responsive (mobile, tablette, desktop)
- Les couleurs hex peuvent être personnalisées dans `ViewModel/GoldColorSelector.php`

---

## 📞 Support

Pour toute question :
- Documentation : `/home/user/ProductConfigurator/GOLD_COLOR_SELECTOR.md`
- Code source : `app/code/ElielWeb/ProductConfigurator/`
- Issues GitHub : https://github.com/eliefirst/ProductConfigurator

---

**Dernière mise à jour : 2025-11-16**

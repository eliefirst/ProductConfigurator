# Gold Color Selector - Documentation

## Vue d'ensemble

Le sélecteur de couleurs d'or permet aux clients de naviguer facilement entre les différentes variantes de couleur d'or (blanc, jaune, rose, noir) pour un même modèle de bijou.

## Installation

### 1. Activer le module et exécuter le setup

```bash
cd /data/www/magento2
bin/magento module:enable ElielWeb_ProductConfigurator
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

Cela créera automatiquement les attributs :
- **gold_color** : Couleur d'or du produit (Or Blanc, Or Jaune, Or Rose, Or Noir)
- **gold_variant_group** : Identifiant de groupe pour lier les variantes

### 2. Configuration des produits

Pour chaque produit bijou, configurez :

#### A. Attribut `gold_color`
Dans l'admin Magento, pour chaque produit :
1. Allez dans **Catalog > Products**
2. Éditez le produit
3. Sélectionnez la couleur d'or dans l'attribut **Gold Color** :
   - Or Blanc
   - Or Jaune
   - Or Rose
   - Or Noir

#### B. Attribut `gold_variant_group`
Pour lier les variantes de couleurs d'or ensemble :
1. Créez un identifiant commun pour le modèle (ex: `PURE-FIL`, `MISS-FIL`)
2. Renseignez le même identifiant dans **Gold Variant Group** pour tous les produits du même modèle

**Exemple :**
```
Produit 1: Bracelet Pure Or Blanc
- SKU: BRACELET-PURE-1B
- Gold Color: Or Blanc
- Gold Variant Group: PURE-FIL

Produit 2: Bracelet Pure Or Jaune
- SKU: BRACELET-PURE-1J
- Gold Color: Or Jaune
- Gold Variant Group: PURE-FIL

Produit 3: Bracelet Pure Or Rose
- SKU: BRACELET-PURE-1R
- Gold Color: Or Rose
- Gold Variant Group: PURE-FIL

Produit 4: Bracelet Pure Or Noir
- SKU: BRACELET-PURE-1N
- Gold Color: Or Noir
- Gold Variant Group: PURE-FIL
```

## Fonctionnement

### Affichage automatique

Le sélecteur de couleurs d'or s'affiche automatiquement sur la page produit si :
1. Le produit a un `gold_variant_group` défini
2. Il existe au moins 2 variantes de couleur d'or dans le même groupe
3. Les variantes sont activées (status = enabled)

### Position

Le sélecteur apparaît **avant les options personnalisées** (taille, couleur de fil) sur la page produit.

### Navigation

- Chaque swatch (pastille colorée) est cliquable
- Cliquer sur un swatch redirige vers la page du produit correspondant
- Le swatch de la couleur actuelle est mis en évidence avec :
  - Une bordure bleue
  - Une coche de validation
  - Un effet de scale

## Codes couleur

Les couleurs d'or utilisent les codes hexadécimaux suivants :

| Couleur     | Hex Code | Code interne |
|-------------|----------|--------------|
| Or Blanc    | #E8E8E8  | white        |
| Or Jaune    | #FFD700  | yellow       |
| Or Rose     | #ECC5C0  | rose         |
| Or Noir     | #2C2C2C  | black        |

## Architecture technique

### Fichiers créés

```
Setup/Patch/Data/
  └── AddGoldColorAttributes.php          # Création des attributs

ViewModel/
  └── GoldColorSelector.php               # Logique métier

view/frontend/
  ├── layout/
  │   └── catalog_product_view.xml        # Intégration layout (mis à jour)
  └── templates/product/
      └── gold-color-selector.phtml       # Template d'affichage
```

### ViewModel : GoldColorSelector

Méthodes principales :

```php
// Récupérer toutes les variantes d'or pour un produit
$variants = $goldColorSelector->getGoldVariants($product);

// Vérifier si le produit a des variantes
$hasVariants = $goldColorSelector->hasGoldVariants($product);

// Obtenir la couleur d'or actuelle
$currentColor = $goldColorSelector->getCurrentGoldColor($product);

// Obtenir les données d'une couleur spécifique
$colorData = $goldColorSelector->getGoldColorData('Or Blanc');
```

## Personnalisation

### Modifier les couleurs hex

Éditez `ViewModel/GoldColorSelector.php` ligne 30-51 :

```php
private const GOLD_COLORS = [
    'Or Blanc' => [
        'hex' => '#E8E8E8',  // <- Modifiez ici
        // ...
    ],
    // ...
];
```

### Modifier le style CSS

Éditez `view/frontend/templates/product/gold-color-selector.phtml` à partir de la ligne 69.

### Changer la position

Éditez `view/frontend/layout/catalog_product_view.xml` :

```xml
<!-- Position actuelle : avant les options -->
<block ... before="product.info.options.wrapper">

<!-- Pour déplacer après les options : -->
<block ... after="product.info.options.wrapper">
```

## Compatibilité

- ✅ Magento 2.4.8+
- ✅ PHP 8.4+
- ✅ Hyva Theme
- ✅ Luma Theme
- ✅ Alpine.js pour les interactions
- ✅ Responsive (mobile, tablette, desktop)

## Troubleshooting

### Le sélecteur ne s'affiche pas

**Vérifications :**
1. Le produit a-t-il un `gold_variant_group` ?
2. Existe-t-il au moins 2 produits avec le même `gold_variant_group` ?
3. Les autres variantes sont-elles activées (Enabled) ?
4. Le cache Magento est-il vidé ?

```bash
bin/magento cache:flush
```

### Les couleurs ne correspondent pas

Vérifiez que l'attribut `gold_color` est bien renseigné avec les valeurs exactes :
- Or Blanc
- Or Jaune
- Or Rose
- Or Noir

(Sensible à la casse et aux espaces)

### Les swatches ne sont pas cliquables

Vérifiez que les URL des produits sont bien générées :
```bash
bin/magento indexer:reindex catalog_product_flat
bin/magento indexer:reindex catalog_url
```

## Support

Pour toute question ou support :
- Documentation Magento : https://devdocs.magento.com/
- Code source : `/app/code/ElielWeb/ProductConfigurator`

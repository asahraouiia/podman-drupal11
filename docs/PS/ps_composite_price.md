# Composite Price Field (ps_composite_price)

Date: 3 Dec 2025
Module: ps_price
Branch: release/0.2-ps

## Overview
A composite field to capture either a single total price or multiple prices when the item is divisible.

Properties:
- `is_divisible` (boolean): toggles mode
- `total` (decimal): used when not divisible
- `prices` (JSON array of decimals): used when divisible

## Implementation
- Field type: `CompositePriceItem` (`ps_composite_price`)
  - Schema columns: `is_divisible` (tiny int), `total` (numeric 14,2), `prices` (text)
  - `isEmpty()` checks based on mode
- Widget: `CompositePriceWidget` (`ps_composite_price_widget`)
  - Checkbox `Is divisible`
  - `Total price` shown when unchecked; hidden when checked
  - `Prices` inputs shown when checked; hidden when unchecked
  - Stable version provides up to 3 price inputs by default (avoids widget state errors)
  - `massageFormValues()` flattens inputs to persist JSON for `prices` or a single `total`
- Formatter: `CompositePriceDefaultFormatter`
  - Renders either total or joined list of prices

## UI Behavior
- Unchecked `Is divisible`:
  - Show `Total price`
  - Hide `Prices`
- Checked `Is divisible`:
  - Show `Prices` inputs (3 by default)
  - Hide `Total price`

## Known limitations
- Dynamic Add-more for prices is disabled to avoid `WidgetBase::getWidgetState()` errors. To enable unlimited UI add-more, implement widget state with `getWidgetState()/setWidgetState()` and an AJAX wrapper with proper `#parents`.

## Quick test
Create field on Offer and displays:
```
drush ev "$fs=\Drupal\field\Entity\FieldStorageConfig::create(['field_name'=>'field_composite_price','entity_type'=>'node','type'=>'ps_composite_price']); $fs->save(); $fi=\Drupal\field\Entity\FieldConfig::create(['field_name'=>'field_composite_price','entity_type'=>'node','bundle'=>'offer','label'=>'Composite Price']); $fi->save();"

drush ev "$fd=\Drupal::entityTypeManager()->getStorage('entity_form_display')->load('node.offer.default'); $fd->setComponent('field_composite_price',['type'=>'ps_composite_price_widget']); $fd->save(); $vd=\Drupal::entityTypeManager()->getStorage('entity_view_display')->load('node.offer.default'); $vd->setComponent('field_composite_price',['type'=>'ps_composite_price_default']); $vd->save();"
```

Create test nodes:
```
# Not divisible
drush ev "$n=\Drupal::entityTypeManager()->getStorage('node')->create(['type'=>'offer','title'=>'Price Total','field_composite_price'=>['is_divisible'=>0,'total'=>1500,'prices'=>json_encode([])]]); $n->save(); echo $n->id();"

# Divisible
drush ev "$n=\Drupal::entityTypeManager()->getStorage('node')->create(['type'=>'offer','title'=>'Price Divisible','field_composite_price'=>['is_divisible'=>1,'total'=>NULL,'prices'=>json_encode([500,700,300])]]); $n->save(); echo $n->id();"
```

Verify persistence:
```
drush ev "$n=\Drupal::entityTypeManager()->getStorage('node')->load(NID); $i=$n->get('field_composite_price')->first(); var_dump(['is_divisible'=>$i->is_divisible,'total'=>$i->total,'prices'=>$i->prices]);"
```

## Next steps
- Optional: Implement dynamic add-more via widget state for true unlimited UI entries.
- Optional: Add currency support and validation of numeric inputs.

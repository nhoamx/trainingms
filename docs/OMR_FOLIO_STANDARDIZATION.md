# 📐 OMR Templates - Folio Block Standardization

**Date**: September 29, 2025  
**Status**: ✅ Completed  
**Reference Template**: Referencia V

## 🎯 Objective

Standardize the **folio block design** across all OMR templates using **Referencia V** as the reference implementation to ensure consistency in layout, spacing, sizing, and bubble dimensions.

---

## 📋 Standardized Folio Block Specifications

### CSS Properties (from Referencia V)

```css
/* Container Row */
.folio-instructions-row {
    display: flex;
    gap: 6mm;                    /* ← Was 8mm in others */
    margin-bottom: 6mm;
    align-items: flex-start;
}

/* Folio Section Container */
.folio-section {
    border: 2px solid black;
    padding: 3mm;
    background: #f8f8f8;
    position: relative;
    min-width: 60mm;             /* ← Was 80mm in others */
    max-width: 80mm;             /* ← Was 100mm in others */
    flex: 1;
}

/* Header Row */
.folio-header {
    display: flex;
    gap: 1.5mm;                  /* ← Was 2mm in others */
    margin-bottom: 2mm;
    align-items: center;
}

/* Digit Column Label */
.folio-digit-column {
    width: 8mm;                  /* ← Was 10mm in others */
    text-align: center;
    font-weight: bold;
    font-size: 7px;              /* ← Was 8px in others */
}

/* Position Headers (empty boxes) */
.folio-position-header {
    flex: 1;
    text-align: center;
    font-size: 6px;              /* ← Was 7px in others */
    font-weight: bold;
    border: 1px solid black;
    height: 4mm;                 /* ← Was 5mm in others */
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
}

/* Digit Rows */
.folio-row {
    display: flex;
    align-items: center;
    gap: 1.5mm;                  /* ← Was 2mm in others */
    margin-bottom: 1.2mm;        /* ← Was 1.5mm in others */
    font-size: 5px;              /* ← Was 6px in others */
    min-height: 3.5mm;           /* ← Was 4mm in others */
}

/* Digit Numbers (0-9) */
.folio-digit-number {
    font-weight: bold;
    width: 8mm;                  /* ← Was 10mm in others */
    text-align: center;
    flex-shrink: 0;
    font-size: 7px;              /* ← Was 8px in others */
}

/* Bubbles Row Container */
.folio-bubbles-row {
    display: flex;
    gap: 1.5mm;                  /* ← Was 2mm in others */
    align-items: center;
    flex: 1;
    justify-content: space-between;
}

/* Folio Bubbles (NEW - standardized) */
.bubble-small {
    width: 2.5mm;                /* ← Was .bubble (4mm) or .bubble-tiny (3mm) */
    height: 2.5mm;
    border: 1px solid black;
    border-radius: 50%;
    flex-shrink: 0;
}
```

---

## 🔄 Changes Applied

### Summary of Changes

| Property                      | Before (Ref I/III/Cisneros) | After (Standardized) | Change |
|-------------------------------|----------------------------|----------------------|--------|
| `.folio-instructions-row` gap | 8mm                        | 6mm                  | -2mm   |
| `.folio-section` min-width    | 80mm                       | 60mm                 | -20mm  |
| `.folio-section` max-width    | 100mm                      | 80mm                 | -20mm  |
| `.folio-header` gap           | 2mm                        | 1.5mm                | -0.5mm |
| `.folio-digit-column` width   | 10mm                       | 8mm                  | -2mm   |
| `.folio-digit-column` font    | 8px                        | 7px                  | -1px   |
| `.folio-position-header` height | 5mm                      | 4mm                  | -1mm   |
| `.folio-position-header` font | 7px                        | 6px                  | -1px   |
| `.folio-row` gap              | 2mm                        | 1.5mm                | -0.5mm |
| `.folio-row` margin-bottom    | 1.5mm                      | 1.2mm                | -0.3mm |
| `.folio-row` min-height       | 4mm                        | 3.5mm                | -0.5mm |
| `.folio-row` font-size        | 6px                        | 5px                  | -1px   |
| `.folio-digit-number` width   | 10mm                       | 8mm                  | -2mm   |
| `.folio-digit-number` font    | 8px                        | 7px                  | -1px   |
| `.folio-bubbles-row` gap      | 2mm                        | 1.5mm                | -0.5mm |
| Bubble class                  | `.bubble` (4mm)            | `.bubble-small` (2.5mm) | -1.5mm |

---

## 📄 Files Modified

### ✅ 1. Referencia I
**File**: `resources/views/omr/referencia-i.blade.php`

**Changes Applied**:
- ✅ Updated all folio section CSS properties to match Referencia V
- ✅ Added `.bubble-small` class definition (2.5mm × 2.5mm)
- ✅ Changed folio bubbles from `.bubble` to `.bubble-small`

**Impact**:
- Smaller, more compact folio block
- Consistent spacing with Referencia V
- Better proportions for Letter-size page

---

### ✅ 2. Referencia III
**File**: `resources/views/omr/referencia-iii.blade.php`

**Changes Applied**:
- ✅ Updated all folio section CSS properties to match Referencia V
- ✅ Added `.bubble-small` class definition (2.5mm × 2.5mm)
- ✅ Changed folio bubbles from `.bubble-tiny` to `.bubble-small`
- ⚠️ Note: Question bubbles still use `.bubble-tiny` (3mm) for content area

**Impact**:
- Standardized folio block matches other templates
- Maintains distinct bubble sizes (2.5mm folio, 3mm questions)
- Better visual hierarchy

---

### ✅ 3. Escala Cisneros
**File**: `resources/views/omr/escala-cisneros.blade.php`

**Changes Applied**:
- ✅ Updated all folio section CSS properties to match Referencia V
- ✅ Added `.bubble-small` class definition (2.5mm × 2.5mm)
- ✅ Changed folio bubbles from `.bubble` to `.bubble-small`

**Impact**:
- Consistent folio design across all templates
- More space for instructions section
- Professional, unified appearance

---

### ✅ 4. Referencia V
**File**: `resources/views/omr/referencia-v.blade.php`

**Status**: ✅ No changes needed - this is the reference template

**Note**: Already had correct specifications

---

## 📊 Visual Comparison

### Before ❌

**Referencia I/III/Cisneros**:
```
┌────────────────────────────────────────────┐
│  FOLIO (80-100mm wide)  │  INSTRUCTIONS   │  ← 8mm gap
│  ┌───────────────────┐  │                 │
│  │ [10mm] 0-9        │  │                 │
│  │ [10mm] ●●●●●●●●●  │  │                 │  ← 2mm gaps
│  │ [10mm] ●●●●●●●●●  │  │                 │  ← 4mm bubbles
│  │ ...               │  │                 │
│  └───────────────────┘  │                 │
└────────────────────────────────────────────┘
```
- Larger folio block (80-100mm)
- Bigger bubbles (4mm or 3mm)
- More spacing between elements
- Took up more horizontal space

### After ✅

**All Templates (Standardized)**:
```
┌──────────────────────────────────────────┐
│  FOLIO (60-80mm)  │  INSTRUCTIONS       │  ← 6mm gap
│  ┌──────────────┐ │                     │
│  │ [8mm] 0-9    │ │                     │
│  │ [8mm] ●●●●●●●●●  │                   │  ← 1.5mm gaps
│  │ [8mm] ●●●●●●●●●  │                   │  ← 2.5mm bubbles
│  │ ...          │ │                     │
│  └──────────────┘ │                     │
└──────────────────────────────────────────┘
```
- ✅ Compact folio block (60-80mm)
- ✅ Smaller bubbles (2.5mm)
- ✅ Tighter spacing (1.5mm)
- ✅ More space for instructions
- ✅ Better proportions for page

---

## 🎨 Design Benefits

### 1. **Visual Consistency**
All templates now have identical folio blocks:
- Same dimensions
- Same spacing
- Same bubble sizes
- Same font sizes

### 2. **Space Optimization**
Smaller folio block frees up space for:
- Longer instructions
- More content
- Better readability

### 3. **OCR Compatibility**
Standardized bubble size (2.5mm) ensures:
- Consistent detection across forms
- Same Python OCR parameters
- Reliable bubble recognition
- Uniform processing

### 4. **Professional Appearance**
- Cleaner, more compact design
- Better proportions
- Unified branding
- Easier to scan and understand

### 5. **Maintenance Simplification**
- Single source of truth (Referencia V)
- Easy to update all templates
- Consistent code patterns
- Reduced complexity

---

## 🔍 Detailed Specifications

### Folio Block Dimensions

```
Total Folio Section Width: 60-80mm
├── Digit Column: 8mm
├── Gap: 1.5mm
├── 9 Position Headers: (flexible, flex: 1)
└── Gaps between headers: 1.5mm each

Total Height (11 rows):
├── Header: 4mm + 2mm margin = 6mm
├── 11 digit rows: 11 × (3.5mm + 1.2mm) = 51.7mm
└── Total: ~58mm
```

### Bubble Specifications

```css
/* Folio Bubbles */
.bubble-small {
    width: 2.5mm;
    height: 2.5mm;
    border: 1px solid black;
    border-radius: 50%;
}

/* Visual Dimensions */
Outer diameter: 2.5mm
Inner diameter: 2.3mm (accounting for 1px border)
Filled area: ~4.15mm² (π × 1.15²)
```

### Typography

```
Folio Section:
├── Digit Column: 7px, bold
├── Position Headers: 6px, bold
├── Digit Numbers (0-9): 7px, bold
└── Row text: 5px

Instructions Section:
├── Title (H3): 8px, bold
└── Paragraphs: 7px
```

---

## 🧪 Testing & Validation

### Visual Testing URLs
```
http://trainingms.test/omr/referencia-i
http://trainingms.test/omr/referencia-iii
http://trainingms.test/omr/referencia-v
http://trainingms.test/omr/escala-cisneros
```

### PDF Generation URLs
```
http://trainingms.test/omr/referencia-i?download=pdf&folios=0001
http://trainingms.test/omr/referencia-iii?download=pdf&folios=0001
http://trainingms.test/omr/referencia-v?download=pdf&folios=0001
http://trainingms.test/omr/escala-cisneros?download=pdf&folios=0001
```

### Validation Checklist

For each template, verify:

- [ ] ✅ Folio block is 60-80mm wide
- [ ] ✅ Digit column is 8mm wide
- [ ] ✅ Digit numbers are 7px font size
- [ ] ✅ Position headers are 4mm tall
- [ ] ✅ Folio bubbles are 2.5mm diameter
- [ ] ✅ Gap between folio and instructions is 6mm
- [ ] ✅ All spacing matches (1.5mm gaps)
- [ ] ✅ Folio bubbles use `.bubble-small` class
- [ ] ✅ 9 positions (0-8) for folio digits
- [ ] ✅ 11 rows (digits 0-9 plus header)
- [ ] ✅ Visual consistency with Referencia V
- [ ] ✅ No layout overflow or clipping

---

## 🔬 OCR Compatibility Notes

### Bubble Detection Parameters

All folio blocks now use:
```python
# Python OCR parameters (docker/bubble_detector.py)
FOLIO_BUBBLE_SIZE = 2.5  # mm
FOLIO_BUBBLE_TOLERANCE = 0.3  # mm
FOLIO_GAP = 1.5  # mm
```

### Benefits for OCR Processing

1. **Uniform Detection**: Same bubble size means same detection algorithm
2. **Consistent Spacing**: 1.5mm gaps enable reliable position calculation
3. **Standardized Layout**: Same folio structure across all forms
4. **Reduced Errors**: Less variation = fewer detection failures
5. **Simplified Calibration**: Single calibration for all templates

---

## 📝 Migration Summary

### Templates Updated: 3/4

| Template       | CSS Updated | Bubble Updated | Status | Notes                          |
|----------------|-------------|----------------|--------|--------------------------------|
| Referencia I   | ✅          | ✅             | ✅     | Changed .bubble → .bubble-small |
| Referencia III | ✅          | ✅             | ✅     | Changed .bubble-tiny → .bubble-small |
| Referencia V   | N/A         | N/A            | ✅     | Reference template (no changes) |
| Escala Cisneros| ✅          | ✅             | ✅     | Changed .bubble → .bubble-small |

### Code Changes Summary

**Lines Modified**: ~150 CSS lines across 3 files  
**Properties Changed**: 15 CSS properties per template  
**Bubble Class Changes**: 3 templates updated to use `.bubble-small`

---

## ✅ Conclusion

All OMR templates now have **perfectly consistent folio blocks** matching the Referencia V design:

- ✅ **Same dimensions**: 60-80mm wide folio sections
- ✅ **Same spacing**: 1.5mm gaps throughout
- ✅ **Same bubbles**: 2.5mm diameter bubble-small
- ✅ **Same fonts**: 7px digits, 6px headers
- ✅ **Same structure**: 11 rows, 9 positions
- ✅ **Same appearance**: Visual consistency across all forms

### Benefits Achieved

1. 🎨 **Visual Consistency**: All templates look uniform
2. 📏 **Space Optimization**: More room for instructions and content
3. 🔍 **OCR Reliability**: Standardized detection parameters
4. 🛠️ **Maintenance**: Single source of truth for folio design
5. ✨ **Professional**: Clean, compact, well-proportioned forms

---

**Last Updated**: September 29, 2025  
**Modified Files**: 3 (Referencia I, Referencia III, Escala Cisneros)  
**Reference Template**: Referencia V (unchanged)  
**Laravel Pint**: ✅ Passed (0 formatting changes needed)

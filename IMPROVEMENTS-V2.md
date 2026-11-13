# WP Font Awesome Settings - V2 Improvements Roadmap

## Overview
This document tracks improvements for the v2 rewrite.
Each item is a discrete, manageable task that can be reviewed and implemented independently.

---

## Phase 1: Critical Security (Must Fix Before Release)

### 1.1 SVG Sanitization
- **Status**: ✅ Complete
- **Priority**: 🔴 CRITICAL
- **Files**: `Custom_Icons.php:432-444`, `wp-font-awesome-settings.php:1019-1032`, `SVG_Loader.php:738-754`
- **Issue**: `wp_kses()` allows `style` attributes which can contain XSS vectors
- **Decision**: ✅ Use `enshrined/svg-sanitize` composer library (v0.22.0) in this package
- **Tasks**:
  - [x] Decide on approach (composer library in this package)
  - [x] Add composer dependency (^0.22 for security patch)
  - [x] Install library (vendor folder committed)
  - [x] Replace sanitization in `Custom_Icons.php`
  - [x] Replace sanitization in `wp-font-awesome-settings.php`
  - [x] Replace sanitization in `SVG_Loader.php` (keeps ID normalization)
  - [x] Ensure vendor autoload in main file
  - [x] Tested - library loads correctly
- **Notes**:
  - Upgraded from 0.19.0 to 0.22.0 to fix CVE-2025-55166
  - SVG_Loader maintains ID normalization after sanitization
  - All three files now use the same battle-tested library

### 1.2 File Upload Validation
- **Status**: ✅ Complete
- **Priority**: 🔴 CRITICAL
- **Files**: `Custom_Icons.php:263-276`, `Custom_Icons.php:617-643`
- **Issue**: Only checked file extension, not MIME type or actual content signature
- **Tasks**:
  - [x] Add MIME type check using `finfo_file()` (lines 263-276)
  - [x] Add .htaccess protection (lines 617-643)
  - [x] Add path traversal validation to upload_icon() (lines 338-344)
  - [x] Add path traversal validation to update_icon() (lines 399-411)
  - [x] Add path traversal validation to delete_icon() (lines 449-455)
- **Notes**:
  - MIME validation supports common SVG types: image/svg+xml, image/svg, text/xml, application/xml, text/plain
  - .htaccess prevents PHP execution: blocks .php, .phtml, .php3, .php4, .php5, .pl, .py, .jsp, .asp, .sh, .cgi
  - Path traversal uses realpath() to verify files stay within custom directory
  - Silent failure acceptable for .htaccess (not all servers support it)

### 1.3 Path Traversal Protection
- **Status**: ✅ Complete (Merged with 1.2)
- **Priority**: 🟡 MEDIUM
- **File**: `Custom_Icons.php` (all file operations)
- **Issue**: `sanitize_file_name()` alone may not be sufficient
- **Tasks**:
  - [x] Add `realpath()` validation
  - [x] Verify resolved paths stay within expected directory
- **Notes**: Completed as part of Task 1.2 - see above for implementation details

---

## Phase 2: Code Quality (Should Fix) ✅ COMPLETED

### 2.1 Duplicate SVG Sanitization Code
- **Status**: ✅ Complete (Done in 1.1)
- **Priority**: 🟡 MEDIUM
- **Issue**: Three identical SVG sanitization methods across files
- **Solution**: All three files now use `enshrined\svgSanitize\Sanitizer` library
- **Tasks**:
  - [x] Replaced all three custom implementations with shared library
  - [x] `Custom_Icons.php` - uses library (lines 432-444)
  - [x] `wp-font-awesome-settings.php` - uses library (lines 1019-1032)
  - [x] `SVG_Loader.php` - uses library + ID normalization (lines 738-754)
- **Notes**: Completed as part of Task 1.1 - all three files now share the same sanitization approach

### 2.2 Add Type Declarations (PHP 7.4+)
- **Status**: ✅ Complete
- **Priority**: 🟢 LOW
- **Files**: All files in `src/`
- **Tasks**:
  - [x] Add `declare(strict_types=1);` to all src/ files
  - [x] Add return types to singleton `instance()` methods
  - [x] Add return types to key public methods in Custom_Icons
  - [x] Use nullable types where appropriate (`?array`)
- **Notes**:
  - All src/ files now have strict types enabled
  - Key public API methods have full type declarations
  - Private/protected methods can be typed incrementally as needed

### 2.3 Standardize Class Naming
- **Status**: ✅ Complete
- **Priority**: 🟢 LOW
- **Issue**: Mixed prefixes (`WP_Font_Awesome_*` vs `AyeCode_Font_Awesome_*`)
- **Decision**: Use `AyeCode_FA_*` prefix for all src/ classes
- **Classes Renamed**:
  - `WP_Font_Awesome_Custom_Icons` → `AyeCode_FA_Custom_Icons`
  - `AyeCode_Font_Awesome_SVG_Loader` → `AyeCode_FA_SVG_Loader`
  - `WP_Font_Awesome_Icon_Library_Generator` → `AyeCode_FA_Icon_Library_Generator`
  - `WP_Font_Awesome_Settings` → **Unchanged** (root file, WP standards)
- **References Updated**:
  - [x] `src/functions.php` - All 9 helper functions
  - [x] `src/Custom_Icons.php` - 3 SVG_Loader references
  - [x] `wp-font-awesome-settings.php` - 6 class_exists checks and instance calls
  - [x] `src/Settings.php` - 6 Custom_Icons and Icon_Library_Generator references
- **Notes**: Shorter, cleaner naming matches `ayecode_*` function prefix pattern

### 2.4 Extract Constants for Hardcoded Values
- **Status**: ✅ COMPLETED
- **Priority**: 🟢 LOW
- **Files**: `src/Constants.php` (new), `src/Icon_Library_Generator.php`, `src/Custom_Icons.php`, `src/SVG_Loader.php`, `wp-font-awesome-settings.php`
- **Tasks**:
  - [x] Created `src/Constants.php` with centralized constants
  - [x] Added `DEFAULT_FA_VERSION = '6.7.2'`
  - [x] Added `CACHE_DIR_NAME`, `LIBRARIES_DIR_NAME`, `CUSTOM_ICONS_DIR_NAME`
  - [x] Added `CUSTOM_ICONS_JSON_FILENAME`, `FA_JSON_FILENAME_PATTERN`, `HTACCESS_FILENAME`
  - [x] Replaced all hardcoded references across 5 files
- **Notes**: All magic strings now centralized in Constants class for easy maintenance

### 2.5 Normalize `local_icon_styles` Handling
- **Status**: ✅ COMPLETED
- **Priority**: 🟡 MEDIUM
- **Issue**: Sometimes string, sometimes array - caused type juggling throughout code
- **Tasks**:
  - [x] Added normalization logic in `get_settings()` to always return array
  - [x] Removed JSON decode logic from `register_custom_icons_library()`
  - [x] Settings.php still stores as JSON for backward compatibility
- **Notes**: `local_icon_styles` is now guaranteed to be an array after `get_settings()` call

---

## Phase 3: WordPress Best Practices (Nice to Have)

### 3.1 Add Uninstall Hook
- **Status**: ⏭️ Skipped
- **Priority**: 🟢 LOW
- **Reason**: This is a library/package bundled into other plugins, not a standalone plugin
- **Tasks**:
  - [ ] Create `uninstall.php` in root
  - [ ] Remove options
  - [ ] Remove transients
  - [ ] Remove uploaded files (fonts, icons, cache)
  - [ ] Test uninstall process
- **Note**: Not applicable for this use case

### 3.2 Add Error Logging (Optional)
- **Status**: ⏸️ Not Started
- **Priority**: 🟢 LOW
- **Tasks**:
  - [ ] Create `log_error()` helper method
  - [ ] Add logging to critical operations
  - [ ] Respect `WP_DEBUG_LOG` setting
- **Estimated Time**: 30 minutes

### 3.3 Add Schema Version to Generated JSON
- **Status**: ✅ COMPLETED
- **Priority**: 🟢 LOW
- **Files**: `Icon_Library_Generator.php`, `Custom_Icons.php`, `wp-font-awesome-settings.php`
- **Tasks**:
  - [x] Added `AYECODE_FA_JSON_SCHEMA_VERSION` constant (set to '2.0')
  - [x] Added `schema_version` field to Font Awesome JSON files
  - [x] Added `schema_version` field to Custom Icons JSON
- **Notes**: All generated JSON files now include schema_version for forward compatibility

### 3.4 Add V1 Migration Flag
- **Status**: ⏸️ Not Started
- **Priority**: 🟡 MEDIUM
- **Tasks**:
  - [ ] Add `migrate_v1_to_v2()` method
  - [ ] Add `_v2_migrated` flag to settings
  - [ ] Test with actual v1 settings
- **Estimated Time**: 30 minutes

### 3.5 Remove Unused Code
- **Status**: ✅ COMPLETED
- **Priority**: 🟡 MEDIUM
- **Files**: `wp-font-awesome-settings.php`, `build/` folder
- **Tasks**:
  - [x] Removed 3 unused AJAX methods (~157 lines):
    - `ajax_upload_custom_svg()` - Replaced by Settings framework
    - `ajax_delete_custom_svg()` - Replaced by Settings framework
    - `sanitize_uploaded_svg()` - Replaced by enshrined/svg-sanitize library
  - [x] Cleaned build folder (deleted ~1.9MB):
    - `build_font_awesome_array.php` - Legacy icon picker builder
    - `readme.txt` - Old workflow instructions
    - `v5.yml` - Font Awesome v5 metadata (565KB)
    - `v6.yml` - Font Awesome v6 metadata (1.3MB)
  - [x] Kept `spyc.php` - Required YAML parser for Icon_Library_Generator
- **Notes**: Custom icon management now fully handled by Settings framework and Custom_Icons class

---

## Phase 4: Architecture (Optional - Consider for v3?)

### 4.1 Refactor God Class
- **Status**: ⏸️ Deferred
- **Priority**: 🟢 LOW
- **Issue**: Main class is 1197 lines, handles too many responsibilities
- **Decision**: Defer to v3 unless needed
- **Note**: Working fine as bundled package, refactoring may not be worth effort

---

## Decisions Needed

### Decision 1: SVG Sanitizer Location ✅ RESOLVED
**Context**: You have multiple packages (settings framework, UI framework, block creation framework, etc.)

**Decision Made**: Use `enshrined/svg-sanitize` composer library in this package

**Rationale**:
- First composer dependency sets good precedent for security-critical code
- Battle-tested, maintained library with regular security updates
- Vendor folder will be committed to git (no composer needed in production)
- If other packages need it later, they can add the same dependency
- Lightweight with no sub-dependencies

---

### Decision 2: Class Naming Convention ✅ RESOLVED
**Decision Made**: Use `AyeCode_FA_*` prefix for all src/ classes

**Structure**:
```php
// Root file (WordPress coding standards)
wp-font-awesome-settings.php → class WP_Font_Awesome_Settings

// All src/ files (modern PHP naming)
src/Settings.php              → class AyeCode_FA_Settings_Framework
src/SVG_Loader.php            → class AyeCode_FA_SVG_Loader
src/Custom_Icons.php          → class AyeCode_FA_Custom_Icons
src/Icon_Library_Generator.php → class AyeCode_FA_Icon_Library_Generator

// Global functions
functions.php → ayecode_*() functions
```

**Note**: Root file class keeps `WP_Font_Awesome_Settings` per WordPress standards

---

### Decision 3: Prioritization
**Question**: Which phases should we tackle for v2 release?

**Minimum viable**:
- [ ] Phase 1: Critical Security (1.1, 1.2, 1.3)

**Recommended**:
- [ ] Phase 1: Critical Security
- [ ] Phase 2: Code Quality (2.1, 2.2, 2.5)
- [ ] Phase 3: Best Practices (3.1, 3.4)

**Your preference?**

---

## Implementation Workflow

**Once decisions are made**:
1. Create feature branch: `v2-improvements`
2. Work through phases in order
3. One commit per improvement item
4. Test after each phase
5. Review before merging to v2 branch

**Estimated Total Time**:
- Phase 1 (Critical): 1-2 hours
- Phase 2 (Quality): 3-4 hours
- Phase 3 (Best Practices): 1-2 hours
- **Total**: ~6-8 hours of focused work

---

## Notes

- Priority levels: 🔴 Critical | 🟡 Medium | 🟢 Low
- Status: ⏸️ Not Started | 🚧 In Progress | ✅ Complete | ⏭️ Deferred
- All tasks are discrete and can be done independently (except where noted as "Blocked by")

# HeritagePress Plugin Activation Test Checklist

## Pre-Activation Checklist

### 1. MAMP Setup
- [ ] MAMP application is running
- [ ] Apache server is started (green status)
- [ ] MySQL server is started (green status)
- [ ] WordPress is accessible at: http://localhost/wordpress/

### 2. WordPress Access
- [ ] WordPress admin accessible at: http://localhost/wordpress/wp-admin/
- [ ] Can log in with admin credentials
- [ ] Plugins page loads without errors

## Plugin Activation Test

### 3. HeritagePress Plugin Status
- [ ] Navigate to Plugins → Installed Plugins
- [ ] "HeritagePress" appears in plugin list
- [ ] Plugin shows current version information
- [ ] Plugin description is visible

### 4. Activation Process
- [ ] Click "Activate" on HeritagePress plugin
- [ ] **NO ERROR MESSAGES** appear during activation
- [ ] Plugin status changes to "Active"
- [ ] Page reloads successfully after activation

### 5. Post-Activation Verification

#### Database Tables Check
Run one of these verification methods:

**Method A: PowerShell Script**
```powershell
.\test-plugin.ps1
```

**Method B: Direct Database Check**
```powershell
& "C:\MAMP\bin\php\php8.1.0\php.exe" direct-db-check.php
```

**Method C: WordPress-based Check**
```powershell
& "C:\MAMP\bin\php\php8.1.0\php.exe" simple-table-check.php
```

#### Expected Results
- [ ] **32 total HeritagePress tables created**
- [ ] All table names have correct prefix (wp_ or custom)
- [ ] No missing tables reported
- [ ] Success message displayed

#### Table Categories to Verify
- [ ] **Core tables (9)**: hp_individuals, hp_families, hp_sources, etc.
- [ ] **GEDCOM 7 tables (9)**: hp_gedcom_files, hp_gedcom_records, etc.
- [ ] **Compliance tables (6)**: hp_compliance_checks, hp_media_links, etc.
- [ ] **Documentation tables (8)**: hp_documentation_pages, hp_user_guides, etc.

### 6. WordPress Integration
- [ ] No PHP errors in WordPress debug log
- [ ] WordPress admin remains accessible
- [ ] Other plugins still function normally
- [ ] WordPress frontend still loads

## Troubleshooting

### If Plugin Won't Activate
1. Check WordPress debug log for PHP errors
2. Verify file permissions on plugin directory
3. Check if required PHP extensions are loaded

### If Tables Aren't Created
1. Run direct database check: `php direct-db-check.php`
2. Check MySQL error logs
3. Verify database user has CREATE TABLE permissions
4. Check WordPress database connection

### Common Issues
- **"Headers already sent" error**: Check for whitespace in PHP files
- **Database connection error**: Verify MAMP MySQL is running
- **Permission denied**: Check file/folder permissions
- **Plugin not found**: Verify plugin files are in correct directory

## Success Criteria
✅ Plugin activates without errors
✅ All 32 database tables are created
✅ WordPress remains functional
✅ No PHP errors or warnings

## Next Steps After Successful Activation
1. Re-enable CalendarSystem in includes/class-heritagepress.php
2. Test basic plugin functionality
3. Remove debug/test files if desired
4. Update .gitignore to exclude test files from commits

---

**Plugin Directory**: `c:\MAMP\htdocs\wordpress\wp-content\plugins\heritagepress\HeritagePress`
**WordPress URL**: http://localhost/wordpress/
**Admin URL**: http://localhost/wordpress/wp-admin/

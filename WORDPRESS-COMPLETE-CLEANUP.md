# WordPress Heritage Press - Complete Cleanup & Reinstall Guide

## 🚨 CURRENT ISSUE
WordPress is looking for the plugin at:
```
/wp-content/plugins/heritage-press/heritage-press/heritage-press.php
```

But it should be at:
```
/wp-content/plugins/heritage-press/heritage-press.php
```

## 🔧 COMPLETE SOLUTION

### Step 1: Complete Plugin Cleanup via cPanel/FTP

1. **Access your hosting control panel (cPanel) or FTP**

2. **Navigate to:** `/public_html/ourbigclan/wp-content/plugins/`

3. **Delete ALL Heritage Press folders:**
   - Delete: `heritage-press/` (if exists)
   - Delete: `heritage-press-1.0.0/` (if exists) 
   - Delete: `heritage-press-1.0.0-1/` (if exists)
   - Delete any other `heritage-press*` folders

4. **Verify the plugins folder is clean:**
   - No Heritage Press folders should remain

### Step 2: Database Cleanup

**Option A: Via WordPress Admin (Recommended)**
1. Go to WordPress Admin → Plugins
2. If Heritage Press still appears in the list, try to delete it
3. If that fails, continue to Option B

**Option B: Manual Database Cleanup**
1. Upload the cleanup script: `wordpress-plugin-cleanup.php` to your WordPress root
2. Access: `http://ourbigclan.com/wordpress-plugin-cleanup.php`
3. Run the cleanup to remove database entries
4. Delete the cleanup script after use

### Step 3: Fresh Installation

1. **Download the correct file:**
   - File: `heritage-press.zip` (290 KB)
   - Location: `C:\Users\Joe\git_heritage_press\heritage-press\dist\heritage-press.zip`

2. **Install via WordPress Admin:**
   - Go to: Plugins → Add New → Upload Plugin
   - Upload: `heritage-press.zip`
   - Click: "Install Now"
   - WordPress should extract to: `/wp-content/plugins/heritage-press/`

3. **Verify correct structure:**
   - Main file should be at: `/wp-content/plugins/heritage-press/heritage-press.php`
   - NOT at: `/wp-content/plugins/heritage-press/heritage-press/heritage-press.php`

### Step 4: Alternative Manual Installation

If WordPress upload continues to fail:

1. **Extract locally:**
   - Extract `heritage-press.zip` on your computer
   - You should get a `heritage-press` folder

2. **Upload via FTP:**
   - Upload the `heritage-press` folder to `/wp-content/plugins/`
   - Final path: `/wp-content/plugins/heritage-press/heritage-press.php`

3. **Activate:**
   - Go to WordPress Admin → Plugins
   - Find "Heritage Press" and click "Activate"

## 🎯 EXPECTED RESULT

After cleanup and reinstall:
- ✅ Plugin appears correctly in WordPress Plugins list
- ✅ Can be activated/deactivated without errors
- ✅ No "file not found" errors
- ✅ No nested folder structure issues

## 🔍 TROUBLESHOOTING

**If you still see the nested structure error:**
1. Check via FTP that the file is at: `/wp-content/plugins/heritage-press/heritage-press.php`
2. NOT at: `/wp-content/plugins/heritage-press/heritage-press/heritage-press.php`
3. If it's nested, manually move the files up one level

**If WordPress won't let you delete the plugin:**
1. Delete the plugin folder via FTP first
2. Then run the database cleanup script
3. Refresh the WordPress admin plugins page

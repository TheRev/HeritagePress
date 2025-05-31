# WordPress Development Environment Setup

## Recommended: wp-env (Official WordPress Development Environment)

### Prerequisites
1. Node.js (v14 or higher)
2. Docker Desktop

### Setup Commands
```bash
# Install wp-env globally
npm install -g @wordpress/env

# In your plugin directory
cd c:\Users\Joe\git_heritage_press\heritage-press

# Create .wp-env.json configuration
# (See .wp-env.json file)

# Start WordPress environment
wp-env start

# Access your WordPress site
# Site: http://localhost:8888
# Admin: http://localhost:8888/wp-admin
# Username: admin
# Password: password
```

### Alternative: Local by Flywheel
- Download: https://localwp.com/
- Create new WordPress site
- Add plugin to `/wp-content/plugins/heritage-press/`

### Alternative: XAMPP/WAMP
- Install XAMPP: https://www.apachefriends.org/
- Download WordPress
- Extract to htdocs/wordpress
- Add plugin to wp-content/plugins

## Testing Checklist
- [ ] Plugin activates without errors
- [ ] Database tables created correctly
- [ ] Admin menus appear
- [ ] AJAX endpoints work
- [ ] Frontend displays correctly
- [ ] File uploads function

## Common Issues to Watch For
1. Database table prefix differences
2. WordPress hook timing
3. Nonce verification
4. User capability checks
5. File upload permissions

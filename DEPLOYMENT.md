# SyncFire Plugin Development Scripts

This directory contains scripts to help with developing and deploying the SyncFire WordPress plugin.

## Available Scripts

### 1. update-plugin.sh
A general-purpose script to deploy the plugin to any WordPress installation.

```bash
# Update plugin to detected WordPress installation
./update-plugin.sh

# Update and activate plugin at a specific path
./update-plugin.sh --path /var/www/html --activate

# Check plugin status only
./update-plugin.sh --check

# Dry run to see what would be done
./update-plugin.sh --dry-run
```

**Options:**
- `-p, --path PATH` - WordPress installation path
- `-a, --activate` - Activate the plugin after deployment
- `-d, --deactivate` - Deactivate the plugin before updating
- `-c, --check` - Only check plugin status, don't update
- `--dry-run` - Show what would be done without making changes
- `-h, --help` - Show help message

### 2. package-plugin.sh
Packages the plugin into a distributable ZIP file.

```bash
# Create a package with current timestamp
./package-plugin.sh
```

## Development Workflow

1. Make changes to the plugin in `/home/flexy/workspace/sync-fire-wp`
2. Use `./update-plugin.sh` to update the plugin in your WordPress installation
3. Test the plugin functionality
4. Use `./package-plugin.sh` to create a distributable package when ready
#!/bin/bash

# SyncFire Plugin Update Script
# This script updates the latest source code to a WordPress installation

set -e  # Exit on any error

# Configuration
PLUGIN_DIR="/home/flexy/workspace/sync-fire-wp"
PLUGIN_NAME=$(basename "$PLUGIN_DIR")  # Use directory name as plugin name
DEFAULT_WP_PATH="/var/www/html"  # Default WordPress installation path
LOG_FILE="/tmp/syncfire-update.log"

# Function to print messages
print_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Function to check if we're in Docker
check_docker_container() {
    if [ -f /.dockerenv ]; then
        return 0
    fi
    return 1
}

# Function to detect WordPress installation
find_wordpress() {
    local search_paths=(
        "/var/www/html"
        "/var/www/wordpress"
        "/opt/lampp/htdocs/wordpress"
        "/opt/bitnami/wordpress"
        "$HOME/wordpress"
        "./wordpress"
        "."
    )

    for path in "${search_paths[@]}"; do
        if [ -d "$path/wp-admin" ] && [ -d "$path/wp-includes" ] && [ -f "$path/wp-config.php" ]; then
            echo "$path"  # Only output the path for command substitution
            return 0
        fi
    done

    return 1
}

# Function to get WordPress path from user
get_wordpress_path() {
    local wp_path=""

    # Try to auto-detect WordPress
    wp_path=$(find_wordpress 2>/dev/null) || true

    if [ -n "$wp_path" ]; then
        echo "$wp_path"
        return 0
    fi

    # Ask user for WordPress path
    echo
    read -p "Enter the full path to your WordPress installation: " wp_path
    
    # Validate WordPress installation
    if [ ! -d "$wp_path/wp-admin" ] || [ ! -d "$wp_path/wp-includes" ] || [ ! -f "$wp_path/wp-config.php" ]; then
        print_message "Error: Invalid WordPress installation path"
        return 1
    fi

    echo "$wp_path"
}

# Function to deploy plugin
deploy_plugin() {
    local wp_path="$1"
    local target_dir="$wp_path/wp-content/plugins/$PLUGIN_NAME"

    print_message "Deploying SyncFire plugin to: $target_dir"

    # Check if plugin directory already exists
    if [ -d "$target_dir" ]; then
        print_message "Plugin already exists. Backing up settings..."
        
        # Preserve settings and logs if they exist
        if [ -d "$target_dir/logs" ]; then
            print_message "Preserving logs directory"
            TEMP_LOGS=$(mktemp -d)
            cp -r "$target_dir/logs"/* "$TEMP_LOGS/" 2>/dev/null || true
        fi
        
        # Remove old plugin directory
        print_message "Removing old plugin directory"
        rm -rf "$target_dir"
    fi

    # Create plugin directory
    mkdir -p "$target_dir"

    # Copy plugin files (excluding .git directory and development files)
    print_message "Copying plugin files..."
    if command -v rsync >/dev/null 2>&1; then
        rsync -av \
            --exclude='.git' \
            --exclude='.gitignore' \
            --exclude='*.sh' \
            --exclude='docker-compose.yml' \
            --exclude='Dockerfile' \
            --exclude='xdebug.ini.example' \
            --exclude='package-plugin.sh' \
            --exclude='teardown-wp-dev.sh' \
            --exclude='node_modules' \
            --exclude='*.zip' \
            --exclude='*.tar.gz' \
            --exclude='.DS_Store' \
            --exclude='Thumbs.db' \
            "$PLUGIN_DIR/" "$target_dir/" | grep -E -v "(sent|total|building)" || true
    else
        print_message "rsync not available, using cp instead..."
        # Use a temporary directory to organize files properly
        TEMP_COPY=$(mktemp -d)
        
        # Copy all files first
        cp -r "$PLUGIN_DIR"/* "$TEMP_COPY"/ 2>/dev/null || true
        
        # Remove excluded files/directories
        find "$TEMP_COPY" -name ".git" -type d -exec rm -rf {} + 2>/dev/null || true
        find "$TEMP_COPY" -name ".gitignore" -exec rm -f {} + 2>/dev/null || true
        find "$TEMP_COPY" -name "*.sh" -exec rm -f {} + 2>/dev/null || true
        find "$TEMP_COPY" -name "docker-compose.yml" -exec rm -f {} + 2>/dev/null || true
        find "$TEMP_COPY" -name "Dockerfile" -exec rm -f {} + 2>/dev/null || true
        find "$TEMP_COPY" -name "xdebug.ini.example" -exec rm -f {} + 2>/dev/null || true
        find "$TEMP_COPY" -name "package-plugin.sh" -exec rm -f {} + 2>/dev/null || true
        find "$TEMP_COPY" -name "teardown-wp-dev.sh" -exec rm -f {} + 2>/dev/null || true
        find "$TEMP_COPY" -path "*/node_modules/*" -exec rm -rf {} + 2>/dev/null || true
        find "$TEMP_COPY" -name "*.zip" -exec rm -f {} + 2>/dev/null || true
        find "$TEMP_COPY" -name "*.tar.gz" -exec rm -f {} + 2>/dev/null || true
        find "$TEMP_COPY" -name ".DS_Store" -exec rm -f {} + 2>/dev/null || true
        find "$TEMP_COPY" -name "Thumbs.db" -exec rm -f {} + 2>/dev/null || true
        
        # Copy the cleaned files to target
        cp -r "$TEMP_COPY"/* "$target_dir"/
        
        # Clean up
        rm -rf "$TEMP_COPY"
    fi

    # Restore logs if they existed
    if [ -n "$TEMP_LOGS" ] && [ -d "$TEMP_LOGS" ]; then
        print_message "Restoring logs directory"
        mkdir -p "$target_dir/logs"
        cp -r "$TEMP_LOGS"/* "$target_dir/logs/" 2>/dev/null || true
        rm -rf "$TEMP_LOGS"
    fi

    # Install Composer dependencies if composer.json exists
    if [ -f "$target_dir/composer.json" ]; then
        print_message "Installing Composer dependencies..."
        cd "$target_dir"
        if command -v composer >/dev/null 2>&1; then
            composer install --no-dev --optimize-autoloader --ignore-platform-reqs
        else
            print_message "Warning: Composer not found, skipping dependency installation"
        fi
    fi

    print_message "Plugin deployed successfully to: $target_dir"
    print_message "Total size: $(du -sh "$target_dir" | cut -f1)"
}

# Function to check if plugin is active
check_plugin_status() {
    local wp_path="$1"
    local plugin_file="$wp_path/wp-content/plugins/$PLUGIN_NAME/sync-fire.php"

    if [ ! -f "$plugin_file" ]; then
        print_message "Plugin not found in WordPress installation"
        return 1
    fi

    # Check if WP CLI is available
    if command -v wp >/dev/null 2>&1; then
        print_message "Checking plugin status with WP CLI..."
        if wp plugin is-active "$PLUGIN_NAME" --path="$wp_path" 2>/dev/null; then
            print_message "Plugin is ACTIVE"
            echo "active"
        else
            print_message "Plugin is INACTIVE or not installed"
            echo "inactive"
        fi
    else
        print_message "WP CLI not available, cannot check plugin status"
        return 1
    fi
}

# Function to activate plugin
activate_plugin() {
    local wp_path="$1"
    local plugin_status=$(check_plugin_status "$wp_path")
    
    if [ "$plugin_status" = "active" ]; then
        print_message "Plugin is already active"
        return 0
    fi
    
    print_message "Activating plugin..."
    if command -v wp >/dev/null 2>&1; then
        wp plugin activate "$PLUGIN_NAME" --path="$wp_path"
        print_message "Plugin activated successfully"
    else
        print_message "WP CLI not available, cannot activate plugin automatically"
        print_message "Please activate the plugin manually from WordPress admin"
        return 1
    fi
}

# Function to show usage information
show_usage() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  -p, --path PATH     WordPress installation path (optional, will auto-detect or prompt)"
    echo "  -a, --activate      Activate the plugin after deployment"
    echo "  -d, --deactivate    Deactivate the plugin before updating"
    echo "  -c, --check         Only check plugin status, don't update"
    echo "  --dry-run           Show what would be done without making changes"
    echo "  -h, --help          Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0                                  # Update and check status"
    echo "  $0 --path /var/www/html            # Update to specific WordPress"
    echo "  $0 --activate --path /var/www/html # Update and activate plugin"
    echo "  $0 --check                         # Check plugin status only"
}

# Parse command line arguments
WORDPRESS_PATH=""
ACTIVATE_PLUGIN=false
DEACTIVATE_PLUGIN=false
CHECK_ONLY=false
DRY_RUN=false

while [[ $# -gt 0 ]]; do
    case $1 in
        -p|--path)
            WORDPRESS_PATH="$2"
            shift 2
            ;;
        -a|--activate)
            ACTIVATE_PLUGIN=true
            shift
            ;;
        -d|--deactivate)
            DEACTIVATE_PLUGIN=true
            shift
            ;;
        -c|--check)
            CHECK_ONLY=true
            shift
            ;;
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        -h|--help)
            show_usage
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            show_usage
            exit 1
            ;;
    esac
done

print_message "Starting SyncFire plugin update process..."

if check_docker_container; then
    print_message "Running inside Docker container"
    DEFAULT_WP_PATH="/var/www/html"
fi

# Determine WordPress path
if [ -z "$WORDPRESS_PATH" ]; then
    if [ "$CHECK_ONLY" = true ]; then
        # For check only mode, try to detect WordPress but don't prompt
        WORDPRESS_PATH=$(find_wordpress 2>/dev/null) || {
            print_message "Error: WordPress installation not found and no path provided"
            exit 1
        }
    else
        WORDPRESS_PATH=$(get_wordpress_path) || exit 1
    fi
else
    # Validate provided WordPress path
    if [ ! -d "$WORDPRESS_PATH/wp-admin" ] || [ ! -d "$WORDPRESS_PATH/wp-includes" ] || [ ! -f "$WORDPRESS_PATH/wp-config.php" ]; then
        print_message "Error: Invalid WordPress installation path: $WORDPRESS_PATH"
        exit 1
    fi
fi

print_message "Using WordPress path: $WORDPRESS_PATH"

# Check if running with dry-run mode
if [ "$DRY_RUN" = true ]; then
    print_message "DRY RUN MODE: Showing what would be done without making changes..."
    echo "Would deploy plugin from: $PLUGIN_DIR"
    echo "Would deploy to: $WORDPRESS_PATH/wp-content/plugins/$PLUGIN_NAME"
    
    if [ "$ACTIVATE_PLUGIN" = true ]; then
        echo "Would activate plugin after deployment"
    fi
    
    exit 0
fi

# Check plugin status before proceeding (unless deactivated requested)
if [ "$DEACTIVATE_PLUGIN" = true ]; then
    print_message "Deactivating plugin before update..."
    if command -v wp >/dev/null 2>&1; then
        wp plugin deactivate "$PLUGIN_NAME" --path="$WORDPRESS_PATH" 2>/dev/null || true
    fi
fi

# If check-only mode, just check status and exit
if [ "$CHECK_ONLY" = true ]; then
    print_message "Checking plugin status..."
    check_plugin_status "$WORDPRESS_PATH"
    exit 0
fi

# Deploy the plugin
deploy_plugin "$WORDPRESS_PATH"

# Activate the plugin if requested
if [ "$ACTIVATE_PLUGIN" = true ]; then
    activate_plugin "$WORDPRESS_PATH"
fi

# Show final status
print_message "Update completed!"
print_message "Plugin deployed to: $WORDPRESS_PATH/wp-content/plugins/$PLUGIN_NAME"
print_message "Current status: $(check_plugin_status "$WORDPRESS_PATH")"

# Provide next steps
echo
print_message "Next steps:"
print_message "1. Log into your WordPress admin panel"
print_message "2. Go to Plugins > Installed Plugins"
print_message "3. Check that SyncFire is activated and working properly"
print_message "4. Visit SyncFire settings page to configure the plugin"

exit 0
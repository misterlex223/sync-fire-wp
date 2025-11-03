#!/bin/bash

# WordPress Plugin Packaging Script for SyncFire
# This script cleans logs, installs composer dependencies, and packages the plugin

# Function to check if a command exists
check_command() {
    if ! command -v "$1" &> /dev/null; then
        echo "Command '$1' not found. Attempting to install..."
        return 1
    else
        return 0
    fi
}

# Function to install required packages
install_package() {
    # Try to install with sudo first
    if command -v apt-get &> /dev/null; then
        echo "Installing $1 using apt-get..."
        sudo apt-get update -qq && sudo apt-get install -y "$1" || {
            echo "Sudo installation failed. Trying without sudo..."
            return 1
        }
    elif command -v yum &> /dev/null; then
        echo "Installing $1 using yum..."
        sudo yum install -y "$1" || {
            echo "Sudo installation failed. Trying without sudo..."
            return 1
        }
    elif command -v brew &> /dev/null; then
        echo "Installing $1 using brew..."
        brew install "$1" || return 1
    else
        echo "No standard package manager found. Trying alternative methods..."
        return 1
    fi
    return 0
}

# Function to handle zip command specifically if sudo fails
install_zip_alternative() {
    echo "Attempting to use alternative method for zip functionality..."
    
    # Check if we can use PHP's ZipArchive as an alternative
    if command -v php &> /dev/null; then
        echo "PHP found, will use PHP's ZipArchive as fallback if needed"
        USE_PHP_ZIP=true
        return 0
    fi
    
    echo "Warning: No alternative zip method available. Package creation may fail."
    return 1
}

# Set variables
PLUGIN_DIR="/home/flexy/workspace/sync-fire-wp"
PACKAGE_NAME="sync-fire-wp"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
OUTPUT_DIR="/home/flexy/workspace/packages"
OUTPUT_FILE="${OUTPUT_DIR}/${PACKAGE_NAME}_${TIMESTAMP}.zip"

# Print header
echo "====================================================="
echo "WordPress Plugin Packaging Script for SyncFire"
echo "====================================================="

# Check for required commands
echo "Checking for required commands..."
REQUIRED_COMMANDS=("composer" "zip" "find")
MISSING_COMMANDS=0

# Initialize variables
USE_PHP_ZIP=false

for cmd in "${REQUIRED_COMMANDS[@]}"; do
    if ! check_command "$cmd"; then
        install_package "$cmd"
        if ! check_command "$cmd"; then
            if [ "$cmd" = "zip" ]; then
                # Try alternative method for zip
                install_zip_alternative
                if [ $? -eq 0 ]; then
                    echo "Will use PHP as an alternative to zip command"
                else
                    echo "Error: Failed to find alternative for $cmd"
                    MISSING_COMMANDS=$((MISSING_COMMANDS+1))
                fi
            else
                echo "Error: Failed to install $cmd. Please install it manually."
                MISSING_COMMANDS=$((MISSING_COMMANDS+1))
            fi
        else
            echo "Successfully installed $cmd"
        fi
    else
        echo "✓ $cmd is installed"
    fi
done

if [ $MISSING_COMMANDS -gt 0 ]; then
    echo "Error: Missing required commands. Please install them manually and try again."
    exit 1
fi

# Check if plugin directory exists
if [ ! -d "$PLUGIN_DIR" ]; then
    echo "Error: Plugin directory not found at $PLUGIN_DIR"
    exit 1
fi

# Create output directory if it doesn't exist
if [ ! -d "$OUTPUT_DIR" ]; then
    echo "Creating output directory: $OUTPUT_DIR"
    mkdir -p "$OUTPUT_DIR"
fi

# Step 1: Clean logs directory
echo "Step 1: Cleaning logs directory..."
if [ -d "$PLUGIN_DIR/logs" ]; then
    echo "  - Removing log files"
    # Try to remove files, but don't fail if permission denied
    find "$PLUGIN_DIR/logs" -name "*.log" -type f -print -exec rm -f {} \; 2>/dev/null || true
    # Create an empty .gitkeep file to ensure the directory exists in the package
    touch "$PLUGIN_DIR/logs/.gitkeep" 2>/dev/null || true
    echo "  - Logs directory cleaned successfully"
else
    echo "  - Logs directory not found, creating it"
    mkdir -p "$PLUGIN_DIR/logs"
    touch "$PLUGIN_DIR/logs/.gitkeep" 2>/dev/null || true
fi

# Step 2: Install Composer dependencies
echo "Step 2: Installing Composer dependencies..."
cd "$PLUGIN_DIR"
if [ -f "composer.json" ]; then
    echo "  - Running composer install"
    # Add --ignore-platform-req=ext-grpc to handle missing PHP extensions
    composer install --no-dev --optimize-autoloader --ignore-platform-reqs
    if [ $? -ne 0 ]; then
        echo "Warning: Composer installation had issues, but continuing with packaging"
        # Don't exit on composer errors, just continue with packaging
    else
        echo "  - Composer dependencies installed successfully"
    fi
else
    echo "Error: composer.json not found in plugin directory"
    exit 1
fi

# Step 3: Package the plugin
echo "Step 3: Creating plugin package..."
cd ..

# Create output directory if it doesn't exist
if [ ! -d "$OUTPUT_DIR" ]; then
    echo "Creating output directory: $OUTPUT_DIR"
    mkdir -p "$OUTPUT_DIR"
fi

if [ "$USE_PHP_ZIP" = true ]; then
    echo "  - Using PHP ZipArchive to create package: $OUTPUT_FILE"
    # Create a temporary PHP script to create the zip file
    PHP_SCRIPT="$(mktemp)"
    cat > "$PHP_SCRIPT" << 'EOF'
<?php
function addFilesToZip($zip, $sourceDir, $baseDir, $excludePatterns = array()) {
    $dirHandle = opendir($sourceDir);
    while (($file = readdir($dirHandle)) !== false) {
        if ($file != '.' && $file != '..') {
            $filePath = $sourceDir . '/' . $file;
            $relativePath = $baseDir === '' ? $file : $baseDir . '/' . $file;
            
            // Check if file should be excluded
            $exclude = false;
            foreach ($excludePatterns as $pattern) {
                if (fnmatch($pattern, $relativePath)) {
                    $exclude = true;
                    break;
                }
            }
            
            if (!$exclude) {
                if (is_dir($filePath)) {
                    // Add empty directory
                    $zip->addEmptyDir($relativePath);
                    // Recursively add files
                    addFilesToZip($zip, $filePath, $relativePath, $excludePatterns);
                } else {
                    // Add file
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }
    }
    closedir($dirHandle);
}

// Get arguments from command line
$sourceDir = $argv[1];
$outputFile = $argv[2];
$excludePatterns = array_slice($argv, 3);

// Create new zip archive
$zip = new ZipArchive();
if ($zip->open($outputFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    exit("Failed to create zip file\n");
}

// Add files to zip
addFilesToZip($zip, $sourceDir, '', $excludePatterns);

// Close zip archive
if ($zip->close() === false) {
    exit("Failed to save zip file\n");
}

echo "Successfully created zip archive: $outputFile\n";
EOF

    # Run the PHP script
    php "$PHP_SCRIPT" "sync-fire-wp" "$OUTPUT_FILE" \
        "sync-fire-wp/logs/*.log" \
        "sync-fire-wp/.git/*" \
        "sync-fire-wp/.gitignore" \
        "sync-fire-wp/node_modules/*" \
        "sync-fire-wp/package-lock.json"
    
    # Remove the temporary PHP script
    rm -f "$PHP_SCRIPT"
    
    if [ ! -f "$OUTPUT_FILE" ]; then
        echo "Error: Failed to create zip archive using PHP"
        exit 1
    fi
else
    echo "  - Creating zip archive: $OUTPUT_FILE"
    zip -r "$OUTPUT_FILE" "sync-fire-wp" \
        -x "sync-fire-wp/logs/*.log" \
        -x "sync-fire-wp/.git/*" \
        -x "sync-fire-wp/.gitignore" \
        -x "sync-fire-wp/node_modules/*" \
        -x "sync-fire-wp/package-lock.json"

    if [ $? -ne 0 ]; then
        echo "Error: Failed to create zip archive"
        exit 1
    fi
fi

# Check if package was created successfully
if [ -f "$OUTPUT_FILE" ]; then
    PACKAGE_SIZE=$(du -h "$OUTPUT_FILE" | cut -f1)
    echo "====================================================="
    echo "Package created successfully!"
    echo "Package: $OUTPUT_FILE"
    echo "Size: $PACKAGE_SIZE"
    echo "====================================================="
else
    echo "Error: Failed to create package"
    exit 1
fi

exit 0

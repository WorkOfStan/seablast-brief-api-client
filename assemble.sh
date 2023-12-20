#!/bin/bash

# color constants
NC='\033[0m' # No Color
HIGHLIGHT='\033[1;32m' # green
WARNING='\033[0;41m' # red background
 
# print string in parameter in programmer's green
chapter() {
    printf "${HIGHLIGHT}%s${NC}\n" "$1"
}

warning() {
    printf "${WARNING}%s${NC}\n" "$1"
}

# Create local config if not present but the dist template is available, if newly created, then stop the script so that the admin may adapt the newly created config
[[ ! -f "conf/app.conf.local.php" && -f "conf/app.conf.local.dist.php" ]] && cp -p conf/app.conf.local.dist.php conf/app.conf.local.php && warning "Check/modify the newly created conf/app.conf.local.php"  && exit 0

chapter "-- composer update code only"
composer update -a --prefer-dist --no-progress

[ ! -f "phpunit.xml" ] && warning "NO phpunit.xml CONFIGURATION"
if [[ -f "phpunit.xml" ]]; then
    chapter "-- phpunit"
    vendor/bin/phpunit
fi

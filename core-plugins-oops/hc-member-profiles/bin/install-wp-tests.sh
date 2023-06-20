#!/usr/bin/env bash

set -ex

# WordPress >= 4.0
WP_VERSION=${1-latest}
# BuddyPress >= 2.1
BP_VERSION=${2-latest}

WP_DIR=/tmp/wordpress
BP_DIR=/tmp/buddypress
WP_SVN=https://develop.svn.wordpress.org
BP_SVN=https://buddypress.svn.wordpress.org
MYSQLI_PLUGIN_URL=https://raw.github.com/markoheijnen/wp-mysqli/master/db.php

# Don't change these values; they are for the test database and are hard-coded
# into wp-tests-config.php. This assumes the database is running on localhost
# and the user running this script can connect to MySQL without a password.
DB_NAME=youremptytestdbnamehere
DB_USER=yourusernamehere
DB_PASS=yourpasswordhere
DB_HOST="127.0.0.1"

# Set SVN paths.
if [ "$WP_VERSION" = "latest" ]; then
  WP_DIR="$WP_DIR/latest"
  WP_SVN="$WP_SVN/trunk"
else
  WP_DIR="$WP_DIR/$WP_VERSION"
  WP_SVN="$WP_SVN/tags/$WP_VERSION"
fi
if [ "$BP_VERSION" = "latest" ]; then
  BP_DIR="$BP_DIR/latest"
  BP_SVN="$BP_SVN/trunk"
else
  BP_DIR="$BP_DIR/$BP_VERSION"
  BP_SVN="$BP_SVN/tags/$BP_VERSION"
fi

# Create directories.
mkdir -p $WP_DIR $BP_DIR

# Install WordPress and test suite. Replace ABSPATH definition with a constant
# that we will supply in our bootstrap.php.
if [ ! -f $WP_DIR/src/wp-content/db.php ]; then
  svn co --quiet $WP_SVN $WP_DIR
  wget -nv -O $WP_DIR/src/wp-content/db.php $MYSQLI_PLUGIN_URL
fi
sed -e "s:'ABSPATH', *dirname( __FILE__ ) . '/src/':'ABSPATH', getenv('WP_ABSPATH'):" -e "s:localhost:127.0.0.1:" $WP_DIR/wp-tests-config-sample.php > $WP_DIR/wp-tests-config.php

# Install BuddyPress and test suite.
if [ ! -d $BP_DIR/src ]; then
  svn co --quiet $BP_SVN $BP_DIR
fi

# Create WP database.
mysql -u root << EOF
create database IF NOT EXISTS $DB_NAME;
grant usage on $DB_NAME.* to $DB_USER@$DB_HOST identified by "$DB_PASS";
grant all privileges on $DB_NAME.* to $DB_USER@$DB_HOST;
EOF

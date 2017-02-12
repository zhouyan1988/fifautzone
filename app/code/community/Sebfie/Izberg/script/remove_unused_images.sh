#!/bin/bash

################################################################################
# PARAMETERS - Please edit before execution
################################################################################

# set your magento path
MAGENTO_PATH="/var/www/magento"

# replace with your MySQL host and database name
DB_HOST=localhost
DB_NAME=magento

# replace with your MySQL credentials
DB_USER=root
DB_PASSWORD=123456

################################################################################

# set path to catalog images inside magento folder
catalog_product_images="$MAGENTO_PATH/media/catalog/product/"

# detect script base name
script_basename="$(basename "$0" | sed -e 's/\.sh$//')"

# create temporary files for listings
db_list_file="$(mktemp -u "/tmp/${script_basename}-db-list_XXXXXXX")"
fs_list_file="$(mktemp -u "/tmp/${script_basename}-fs-list_XXXXXXX")"

# automatically remove created temporary files on exit
trap "rm -f /tmp/${script_basename}*" EXIT

echo "Test: Checking access to magento media..."
if [ ! -d "$MAGENTO_PATH" ]; then
  echo "ERROR: Cannot access Magento path: $MAGENTO_PATH" >&2
  exit 1
fi
if [ ! -d "$catalog_product_images" ]; then
  echo "ERROR: Cannot access Magento's catalog images folder: $catalog_product_images" >&2
  exit 1
fi
if [ ! -w "$catalog_product_images" ]; then
  echo "ERROR: Magento media catalog folder is not writable. Try restarting this script with sudo." >&2
  exit 1
fi

echo "Test: Checking MySQL credentials..."
if ! mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" -e ";"; then
  echo "ERROR: Cannot connect to MySQL. Please review provided credentials." >&2
fi

echo
echo "Loading list of gallery images from DB..."
echo 'SELECT `value` FROM catalog_product_entity_media_gallery;' | \
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" | \
sed 's/^\///' | sort >"$db_list_file"

echo "$(wc -l "$db_list_file" | awk '{print $1}') files"

echo
echo "Getting list of gallery images from filesystem... $catalog_product_images"
( cd "$catalog_product_images" && find . -type f | tail -n +2 | cut -c 3- | sort >"$fs_list_file" )
echo "$(wc -l "$fs_list_file" | awk '{print $1}') files"

echo
echo "Performing checks..."
echo "$(comm -23 "$db_list_file" "$fs_list_file" | wc -l) files are missing in filesystem"

count="$(comm -13 "$db_list_file" "$fs_list_file" | wc -l)"
echo "$count files are not found in database"

echo
if [ "$count" -eq "0" ]; then
  echo "Looks like there is nothing to clean!"
  exit
fi

for t in {10..0}; do
  echo -ne "\\r(WARNING! Ctrl+C if not sure!) This script is deleting $count gallery image files in ${t} sec.."
  sleep 1
done
echo

(
cd $catalog_product_images
echo "Deleting images from $catalog_product_images..."

i=0
comm -13 "$db_list_file" "$fs_list_file" | \
while read f; do
  i=$[i+1]
  echo '[deleting ('$i'/'$count'; '$[i*100/count]'%)]' $f
  rm -f $f
done

echo "Magento catalog images cleanup is done."
)

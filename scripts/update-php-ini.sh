#!/bin/bash
# Update PHP ini settings for large file uploads
sed -i 's/^post_max_size = .*/post_max_size = 2G/' /etc/php/8.3/cli/php.ini
sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 1G/' /etc/php/8.3/cli/php.ini
sed -i 's/^max_file_uploads = .*/max_file_uploads = 100/' /etc/php/8.3/cli/php.ini
echo "Updated. Verifying:"
grep -n "upload_max_filesize\|post_max_size\|max_file_uploads" /etc/php/8.3/cli/php.ini

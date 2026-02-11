                                                                                                                                  config.php                                                                                                                                             
<?php
$CONFIG = array (
  'htaccess.RewriteBase' => '/',
  'memcache.local' => '\\OC\\Memcache\\APCu',
  'apps_paths' => 
  array (
    0 => 
    array (
      'path' => '/var/www/html/apps',
      'url' => '/apps',
      'writable' => false,
    ),
    1 => 
    array (
      'path' => '/var/www/html/custom_apps',
      'url' => '/custom_apps',
      'writable' => true,
    ),
  ),
  'upgrade.disable-web' => true,
  'instanceid' => 'ocrh9f4d515k', # do not change after installation
  'passwordsalt' => 'XX', # generate e.g. with "openssl rand -base64 48" on linux
  						  # example 7e7c00ae2bc82844b1367caca1e0ddfebd8de6b494bac4e45f4e192e609c532f
					      # do not share
  'secret' => 'XX', # generate e.g. with "openssl rand -base64 24" on linux
  					# example qh8Aikhbw0ED28SNkPWYlB8UZI3iPFYl
					# do not share
  'trusted_domains' => 
  array (
         'pi.tailXXXXXX.ts.net', # replace XXXXXX with your tailscale link
  ),
  'trusted_proxies' => ['127.0.0.1', '::1','172.20.0.0/16',],
  'datadirectory' => '/var/www/html/data',
  'dbtype' => 'mysql',
  'version' => '30.0.4.1',
  'overwrite.cli.url' => 'https://pi.tailXXXXXX.ts.net', # replace XXXXXX with your tailscale link
  'overwriteprotocol' => 'https', # might replace in future with more general statement
  'overwritehost' => 'pi.tailXXXXXX.ts.net', # replace XXXXXX with your tailscale link
  'dbname' => 'db',
  'dbhost' => 'db',
  'dbport' => '',
  'dbtableprefix' => 'oc_',
  'mysql.utf8mb4' => true,
  'dbuser' => 'nextcloud',
  'dbpassword' => 'MYSQL_PASSWORD', # Replace with the mysql password you set in db.env file
  'installed' => true,
  'forwarded_for_headers' => ['HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED_PROTO','HTTP_X_FORWARDED_PORT',],
  'mimetypes' => [
    'mjs' => 'application/javascript',
    'js.map' => 'application/json',
  ], 
  'maintenance_window_start' => '15', # Add this line
);



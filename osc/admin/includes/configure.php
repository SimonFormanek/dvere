<?php
// set the level of error reporting
  error_reporting(E_ALL);

  const HTTP_SERVER = 'http://phoenix.local';
  const COOKIE_OPTIONS = [
    'lifetime' => 0,
    'domain' => 'phoenix.local',
    'path' => '/admin',
    'samesite' => 'Lax',
  ];
  const DIR_WS_ADMIN = '/admin/';

  const DIR_FS_DOCUMENT_ROOT = '/home/f/git/CE-Phoenix/osc/';
  const DIR_FS_ADMIN = '/home/f/git/CE-Phoenix/osc/admin/';
  const DIR_FS_BACKUP = DIR_FS_ADMIN . 'backups/';

  const HTTP_CATALOG_SERVER = 'http://phoenix.local';
  const DIR_WS_CATALOG = '/';
  const DIR_FS_CATALOG = '/home/f/git/CE-Phoenix/osc/';

  date_default_timezone_set('Europe/Berlin');

// If you are asked to provide configure.php details
// please remove the data below before sharing
  const DB_SERVER = 'localhost';
  const DB_SERVER_USERNAME = 'phoenix';
  const DB_SERVER_PASSWORD = 'osc';
  const DB_DATABASE = 'phoenix';

<?php
$config = array();
//Full path to the vingrate folder
$config['path'] = 'home/web/public/vinmigrations/';

//Full path to the folder that holds the migration files
$config['migrations_path'] = $config['path'] . 'migrations/';

//Name of the table that holds the list of versions
$config['migrations_table'] = 'migrations';

//Database connection information
$config['db']['hostname'] = 'localhost';
$config['db']['username'] = 'root';
$config['db']['password'] = '';
$config['db']['database'] = 'database';
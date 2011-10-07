<?php
class Roles
{
    function up()
    {
        mysql_query("
            CREATE TABLE roles (
                id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(100)
                );");
        mysql_query("
            ALTER TABLE users ADD role_id INT(11) NOT NULL DEFAULT 0");
    }
    
    function bootstrap()
    {
        mysql_query("
            INSERT INTO roles (name) VALUES ('User'),('Moderator'),('Admin');");
		return TRUE;
    }
    
    function down()
    {
        mysql_query("
            DROP TABLE roles");
        mysql_query("
            ALTER TABLE users DROP COLUMN role_id");
    }
}
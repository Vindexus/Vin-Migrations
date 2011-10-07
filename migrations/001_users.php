<?php
class Users
{
    function up()
    {
        mysql_query("
            CREATE TABLE users (
                id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                username VARCHAR(100)
                );");
    }
    
    function down()
    {
        mysql_query("
            DROP TABLE users");
    }
}
   
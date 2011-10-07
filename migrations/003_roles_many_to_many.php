<?php
class Roles_many_to_many
{
    function up()
    {
        mysql_query("
            CREATE TABLE users_to_roles (
                user_id INT(11),
                role_id INT(11)
                );");
    }
    
    function bootstrap()
    {
        $result = mysql_query("
            SELECT id, role_id
            FROM users
            WHERE role_id > 0");
        
        $inserts = array();
        while($row = mysql_fetch_array($result))
        {
            $inserts[] = "(" . $row['id'] . "," . $row['role_id'] . ")";
        }
        
        if(count($inserts) > 0)
        {
            mysql_query("
                INSERT INTO users_to_roles (user_id, role_id)
                VALUES " . join(",", $inserts));
        }
		
		return TRUE;
    }
    
    function down()
    {
        mysql_query("
            DROP TABLE users_to_roles");
    }
}
    
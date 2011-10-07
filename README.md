<a name="vin-top"></a>
Vin Migrations - Database Versioning With Migration Files in PHP and MySQL
===========================================================================
 * Author: Colin "Vindexus" Kierans</li>
 * Version: 1.0</li>
 * Last updated: Sept 20th 2011</li>
 * License: none at the moment</li>
<div class="section">
	<a name="faq"></
	<h2>Frequently Asked Questions</h2>
	<a href="#vin-top">Back to Top</a>
	<dl>
		<dt>What are database migrations?</dt>
		<dd>Database migrations are files that let you change your database from one version of a schema to another using code. They allow you to both "upgrade" and "downgrade" your database.</dd>
		
		<dt>Why should I use database migrations?</dt>
		<dd>Migrations are an easy way to ensure that any time you are replicating the database across different environments you are being consistent with the schema. This is useful when you have a development environment and a production environment, and everything in between.</dd>
	</dl>
</a>

<div class="section">
	<a name="installing"></a>
	<h2>Installing</h2>
	<a href="#vin-top">Back to Top</a>
	<p>To install the Vin Migrations do the following steps.</p>
	<ol>
		<li>Unzip the Vin Migration migration files</li>
		<li>Copy and paste the contents of the config-sample.php file into a new config.php file</li>
		<li>Upload the Vin Migration files to a new directory on your website</li>
		<li>Edit the config.php file to point to the correct Vin Migration path on your server</li>
	</ol>
</div>

<div class="section">
	<a name="creating"></a>
	<h2>Creating Migrations</h2>
	<a href="#vin-top">Back to Top</a>
	<p>To create a new migration create a new file in the "migrations" folder in your Vin Migrations folder.</p>
	<ul>
		<li>The files must be named in a <em>number</em>_classname.php format</li>
		<li>The numbers of the files must increase by 1 with no skipping</li>
		<li>Each file must contain a class whose name matches the filename without the numbers or underscore</li>
		<li>The class must have an up() and a down() method in them</li>
		<li>The migration class can have an optional bootstrap() function that runs after up(), but not after down()</li>
	</ul>
</div>


<div class="section">
	<a name="download"></a>
	<h2>Example Migrations</h2>
	<a href="#vin-top">Back to Top</a>
	<p>The following migration files are examples of migrations that are created as an application's needs and functions change.</p>
	<a name="001_users"></a>
	<h3>File: 001_users.php</h3>
	<a href="#vin-top">Back to Top</a>
	<p>At this point the application only has a users table.</p>
</div>
<pre>
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
	</pre>
</div>

<a name="002_roles"></a>
<div class="section">
	<h3>File: 002_roles.php</h3>
	<a href="#vin-top">Back to Top</a>
	<p>The application now requires each user to have a role. This migration creates that table. Its "bootstrap" function populates the database with the defaults.</p>
	<pre>
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
			INSERT INTO roles (name) VALUES ('User','Moderator','Admin');");
	}
	
	function down()
	{
		mysql_query("
			DROP TABLE roles");
		mysql_query("
			ALTER TABLE users DROP COLUMN role_id");
	}
}
	</pre>
</div>

<a name="003_roles_many_to_many"></a>
<div class="section">
	<h3>File: 003_roles_many_to_many.php</h3>
	<a href="#vin-top">Back to Top</a>
	<p>The application has changed so that users now have multiple roles instead of just one. The up function creates the table, and the bootstrap function grabs the old data and migrates it into the new schema.</p>
	<pre>
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
	}
	
	function down()
	{
		mysql_query("
			DROP TABLE users_to_roles");
	}
}
	</pre>
</div>

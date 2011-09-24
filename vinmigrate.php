<?php
class Vinmigrate
{
	public $errors = array();
	public $notices = array();
	public $success_message = "";
	protected $migrations = NULL;
	
	
	public function __construct()
	{
	}
	
	public function init()
	{
		if(file_exists('config.php'))
		{
			include('config.php');
		}
		else
		{
			$this->errors[] = "Config.php file not found.";
		}
		
		$this->config = $config;
				
		if(!is_dir($this->config['migrations_path']))
		{
			$this->errors[] = "Cannot find migrationss folder at " . $this->config['migrations_path'];
		}
		
		if($this->connect())
		{
			$this->ensure_migrations_table();
			$this->current_version = $this->get_current_version();
		}
		
		return $this->no_errors();
	}
	
	//Connect to the mysql database based on the config file
	public function connect()
	{
		$db = $this->config['db'];
		if(mysql_connect($db['hostname'], $db['username'], $db['password']))
		{
			if(!mysql_select_db($db['database']))
			{
				$this->errors[] = "Could not select db: " . $db['database'] . ".";
				return FALSE;
			}
			
			return TRUE;
		}
		else
		{
			$this->errors[] = "Could not connect to MySQL.";
			return FALSE;
		}
	}
	
	//This just makes sure that the migrations_table exists in the database
	//If it does not then we create it
	public function ensure_migrations_table()
	{
		$result = mysql_query('
			SHOW TABLES LIKE "' . $this->config['migrations_table'] . '"');
		$row = mysql_fetch_array($result);
		if(!$row)
		{
			mysql_query("
				CREATE TABLE " . $this->config['migrations_table'] . "
				(version INTEGER(11) NOT NULL DEFAULT 0 PRIMARY KEY,
				 bootstrapped TINYINT(1) NOT NULL DEFAULT 0);");
		}
	}
	
	//Returns a specific migration
	public function get_migration($number)
	{
		$migrations = $this->get_migrations();
		
		if(isset($migrations[$number]))
		{
			return $migrations[$number];
		}
		else
		{
			$this->errors[] = "Migration " . $number . " not found.";
			return FALSE;
		}
	}
	
	//Returns an array with the relevant information for each migration file in the migrations
	//folder
	public function get_migrations()
	{
		if(is_null($this->migrations))
		{
			$this->migrations = array();
			$files = glob($this->config['migrations_path']."*.php");
			$file_count = count($files);
	
			for ( $i = 0 ; $i < $file_count ; $i++ ) {
	
				// Mark wrongly formatted files as FALSE for later filtering
				$name = basename($files[$i],".php");
				if(!preg_match('/^\d{1,4}_(\w+)$/',$name))
				{
					$files[$i] = FALSE;
				}	
			}
	
			$files = array_filter($files);
			
			if (!empty($files))
			{
				sort($files);
				foreach($files as $file)
				{
					if(!$this->load_migration($file))
					{
						$this->errors[] = "Could not load migration at file: " . $file . "";
						return FALSE;
					}
				}
				
				//Loop through all of the migration numbers
				//They must be sequential and you can't skip any
				//Log each one that is missing into error array
				$keys = array_keys($this->migrations);
				$prev = 0;
				$missing = FALSE;
				$highest = $this->get_highest_migration_number();
				for($i = 1; $i <= $highest; $i++)
				{
					if(!in_array($i, $keys))
					{
						$this->errors[] = "Missing migration " . ($i) . ".";
						$missing = TRUE;
					}
				}
				
				if($missing)
				{
					return FALSE;
				}
			}
		}
		
		if(count($this->migrations) > 0)
		{
			return $this->migrations;
		}
		else
		{
			$this->errors[] = "No migrations found or failed to load all of them.";
			return FALSE;
		}
	}
	
	//Loads a migration from a full path to the file
	public function load_migration($file_path)
	{
		include_once($file_path);
		
		//Use the proper slash depending on system
		if(substr_count($file_path, "/") > substr_count($file_path, "\\"))
		{
			$parter = "/";
		}
		else
		{
			$parter = "\\";
		}
		
		$parts = explode($parter, $file_path);
		$filename = $parts[count($parts) - 1];
		
		$parts = explode("_", $filename);
		$prefix = $parts[0];
		$number = (int)$prefix;
		
		//The class name is based off the file name
		$class = ucfirst(str_replace(array('.php', $prefix . '_'), '', $filename));
				
		//If we have alread loaded a migration with this number then we need to error out
		if(array_key_exists($number, $this->migrations))
		{
			$this->errors[] = "Cannot load migration " . $filename . ". Number " . $number . " already in use by " . $this->migrations[$number]['filename'] . ".";
			return FALSE;
		}
		
		//Make sure it exists. If it doesn't then the names probably don't match
		if(class_exists($class))
		{
			$object = new $class();
			
			if(method_exists($object, 'up') && method_exists($object, 'down'))
			{
				$this->migrations[$number] = array('path' => $file_path, 'filename' => $filename, 'number' => $number, 'class' => $class, 'object' => $object);
				return $this->migrations[$number];
			}
			else
			{
				$this->errors[] = "Class " . $class . " needs both an up and down function: " . $filename . ".";
			}
		}
		else
		{
			$this->errors[] = "Class " . $class . " not found in migration: " . $filename . ".";
		}
		
		return FALSE;
	}
	
	public function get_current_version()
	{
		$result = mysql_query("SELECT MAX(version) as version FROM " . $this->config['migrations_table']);
		$row = mysql_fetch_array($result);
		if($row)
		{
			return $row['version'];
		}
		
		return FALSE;
	}
	
	//Returns the highest numbered migration number from the migratiosn folder
	public function get_highest_migration_number()
	{
		if($migrations = $this->get_migrations())
		{
			$keys = array_keys($migrations);
			rsort($keys);
			return $keys[0];
		}
		
		return FALSE;
	}
	
	public function no_errors()
	{
		return count($this->errors) == 0;
	}
	
	//Migrates from current version to the highest version
	public function install($options = array())
	{
		$highest = $this->get_highest_migration_number();
		return $this->migrate_to($highest, $options);
	}
	
	public function save_version($migration)
	{
		$migration = is_numeric($migration) ? $this->get_migration($migration) : $migration;
		$sql = "
			INSERT INTO " . $this->config['migrations_table'] . " (version, bootstrapped)
			VALUES (" . $migration['number'] . ",0)";
		return mysql_query($sql);
	}
	
	public function remove_version($migration)
	{
		$migration = is_numeric($migration) ? $this->get_migration($migration) : $migration;
		return mysql_query("
			DELETE FROM " . $this->config['migrations_table'] . "
			WHERE version = " . $migration['number'] . "
			LIMIT 1");
	}
	
	public function save_bootstrapped($migration)
	{
		$migration = is_numeric($migration) ? $this->get_migration($migration) : $migration;
		return mysql_query("
			UPDATE " . $this->config['migrations_table'] . " 
			SET bootstrapped = 1
			WHERE version = " . $migration['number'] . "
			LIMIT 1");
	}
	
	//Runs all the migrations from the next version up to the highest one
	public function migrate_to($number, $options = array())
	{
		$defaults = array(
			'run_bootstraps' => TRUE,
			'skip_migrations' => array()
			);
		
		$options = array_merge($defaults, $options);
		
		$direction = $this->current_version < $number ? 'up' : 'down';
		
		if($number == $this->current_version)
		{
			$this->success_message = "Already at migration " . $number . "";
			return TRUE;
		}

		$this->notices[] = "Migrating to " . $number . " from " . $this->current_version . "";
		
		$i = $this->current_version;
		$return = TRUE;
		
		//Need to boost it for down so that it runs the correct one
		if($direction == 'down')
		{
			$i++;
		}
				
		while($i != $number)
		{
			$i += $direction == 'up' ? 1 : -1;
			if(!in_array($i, $options['skip_migrations']) && $i)
			{
				if(!$migration = $this->get_migration($i))
				{
					return FALSE;
				}
				
				$this->notices[] = "Running '" . $direction . "' for migration " . $migration['number'] . ": " . $migration['class'] . ".";
				
				if($this->run($migration, $direction))
				{
					if($direction == 'up')
					{
						$this->save_version($migration);
						
						if($options['run_bootstraps'] && $this->has_bootstrap($migration))
						{
							$this->save_bootstrapped($migration);
							$this->notices[] = "Bootstrapping migration " . $migration['number'] . "";
							$this->run_bootstrap($migration);
						}
					}
					else
					{
						$this->remove_version($migration);
					}
					
					$this->notices[] = "Successfully ran '" . $direction . "' for migration " . $migration['number'] . " " . $migration['class'] . ".";
				}
				else
				{
					$return = FALSE;
					$this->notices[] = "Migration " . $migration['number'] . " FAILED.";
				}
			}
			else
			{
				$this->notices[] = "Skipping migration " . $number . "";
			}
		}
		
		$this->success_message = "Successfully migrated to " . $number . "";
		
		return $return;
	}
	
	//Returns whether a specific migration has a bootstrap function
	//These are for data migration and setup
	public function has_bootstrap($migration)
	{
		$migration = is_numeric($migration) ? $this->get_migration($migration) : $migration;
		
		return method_exists($migration['object'], 'bootstrap');
	}
	
	/*
	* Runs the "down" function of a migration
	*/
	public function run_up($migration)
	{
		return $this->run($migration, 'up');
	}
	
	/*
	* Runs the "down" function of a migration
	*/
	public function run_down($migration)
	{
		return $this->run($migration, 'down');
	}
	
	/*
	Runs the bootstrap function of a given migration
	Bootstraps are for creating and altering data
	*/
	public function run_bootstrap($migration)
	{
		return $this->run($migration, 'bootstrap');
	}
	
	//Runs a migrations up or down function or its bootstrap function
	//Takes in either the migration data array or migration number
	public function run($migration, $method)
	{
		$migration = is_numeric($migration) ? $this->get_migration($migration) : $migration;
		
		if($migration['object']->$method() !== FALSE)
		{
			return TRUE;
		}
		
		return FALSE;
	}
}
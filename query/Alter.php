<?php

/**
 * @copyright Joe J. Howard
 * @license   https://github.com/kanso-cms/cms/blob/master/LICENSE
 */

namespace phpsqlbuilder\query;

use phpsqlbuilder\connection\ConnectionHandler;
use PDOException;

/**
 * Alter table class.
 *
 * This class is used by Builder to alter, create, delete
 * tables and columns
 *
 * @author Joe J. Howard
 */
class Alter
{
    /**
     * The current table name to operate on.
     *
     * @var string
     */
    private $tableName;

    /**
     * The current column name to operate on.
     *
     * @var string
     */
    private $column;

    /**
     * Array of columns and column parameters of current table.
     *
     * @var array
     */
    private $columns;

    /**
     * @var \phpsqlbuilder\connection\ConnectionHandler
     */
    private $connectionHandler;

    /**
     * Constructor.
     *
     * @param \phpsqlbuilder\connection\ConnectionHandler $connectionHandler The database connection handler to use
     * @param string                                                 $tableName         The table name we are altering
     */
    public function __construct(ConnectionHandler $connectionHandler, string $tableName)
    {
        // Set the current database object instance
        $this->connectionHandler  = $connectionHandler;

        // Set the current table name to operate on
        $this->tableName = $tableName;

        // Load columns and column parameters of current table
        $this->loadColumns();
    }

    /********************************************************************************
    * PUBLIC ACCESS FOR TABLE AND COLUMN MANAGEMENT
    *******************************************************************************/

    /**
     * Add a new column to the current table.
     *
     * @param  string                                $column
     * @param  string                                $dataType The column parameters
     * @throws PDOException                          If the column already exists
     * @return \phpsqlbuilder\query\Alter
     */
    public function ADD_COLUMN(string $column, string $dataType): Alter
    {
        // Convert the column to valid syntax
        $column = $this->indexFilter($column);

        // Validate the column does NOT already exist
        if ($this->columnExists($column))
        {
            throw new PDOException("Error adding column $column. The column already exists.");
        }

        // Execute the SQL
        $this->connectionHandler->query($this->connectionHandler->cleanQuery("ALTER TABLE `$this->tableName` ADD `$column` $dataType"));

        // Reload the columns
        $this->loadColumns();

        // Return Alter for chaining
        return $this;
    }

    /**
     * Drop an existing column from the current table.
     *
     * @param  string                                $column The column name to drop
     * @throws PDOException                          If the column does not exist
     * @return \phpsqlbuilder\query\Alter
     */
    public function DROP_COLUMN(string $column): Alter
    {
        // Convert the column to valid syntax
        $column = $this->indexFilter($column);

        // Validate the column already exists
        if (!$this->columnExists($column))
        {
            throw new PDOException("Error dropping column $column. The column does NOT exist.");
        }

        // Execute the SQL
        $this->connectionHandler->query($this->connectionHandler->cleanQuery("ALTER TABLE `$this->tableName` DROP `$column`"));

        // Remove the column from list
        unset($this->columns[$column]);

        // Return Alter for chaining
        return $this;
    }

    /**
     * Modify a column's parameters or set the current
     * operation to change a columns configuration.
     *
     * Note this function must be called before using
     * any of the configuration/constraint methods below
     *
     * @param  string                                $column
     * @param  string|null                           $dataType (optional) (default null)
     * @throws PDOException                          If the column does not exist
     * @return \phpsqlbuilder\query\Alter
     */
    public function MODIFY_COLUMN(string $column, string $dataType = null): Alter
    {
        // Convert the column to valid syntax
        $column = $this->indexFilter($column);

        // Validate the column already exists
        if (!$this->columnExists($column))
        {
            throw new PDOException("Error modifying column $column. The column does NOT exist.");
        }

        // Set the column
        $this->column = $column;

        // If there is no type return
        if (!$dataType)
        {
            return $this;
        }

        // Otherwise change the columns datatype
        $this->connectionHandler->query($this->connectionHandler->cleanQuery("ALTER TABLE `$this->tableName` MODIFY COLUMN `$column` $dataType"));

        // Reload the column config
        $this->loadColumns();

        // Return Alter for chaining
        return $this;
    }

    /**
     * Add a primary key to the current column.
     *
     * @return \phpsqlbuilder\query\Alter
     */
    public function ADD_PRIMARY_KEY(): Alter
    {
        // Get the existing config
        $colConfig = $this->getColumnConfig();

        // If PRIMARY KEY is already set on this column return
        if (strpos(strtoupper($colConfig), 'PRIMARY KEY') !== false)
        {
            return $this;
        }

        // Change the existing column - Drop the PRIMARY KEY
        $PK = $this->getPrimaryKey();

        if ($PK)
        {
            $this->connectionHandler->query($this->connectionHandler->cleanQuery("ALTER TABLE `$this->tableName` MODIFY COLUMN `$PK` INT(11) NOT NULL UNIQUE, DROP PRIMARY KEY"));
        }

        // Set the PRIMARY KEY to this column
        $this->connectionHandler->query($this->connectionHandler->cleanQuery("ALTER TABLE `$this->tableName` MODIFY COLUMN `$this->column` INT(11) NOT NULL UNIQUE PRIMARY KEY"));

        // Reload the column config
        $this->loadColumns();

        // Return Alter for chaining
        return $this;
    }

    /**
     * Drop the table's current primary key.
     *
     * @return \phpsqlbuilder\query\Alter
     */
    public function DROP_PRIMARY_KEY(): Alter
    {
        // Check if there is an existing primary key
        $PK = $this->getPrimaryKey();

        // Drop the primary key
        if ($PK)
        {
            $this->connectionHandler->query($this->connectionHandler->cleanQuery("ALTER TABLE `$this->tableName` DROP PRIMARY KEY"));
        }

        // Reload the column config
        $this->loadColumns();

        // Return Alter for chaining
        return $this;
    }

    /**
     * Add not null to a column.
     *
     * @param  $notNull mixed Value to set null values to (optional) (default 0)
     * @return \phpsqlbuilder\query\Alter
     */
    public function ADD_NOT_NULL($notNull = 0): Alter
    {
        // Get the existing config
        $colConfig = $this->getColumnConfig();

        // If not null is already set return
        if (strpos(strtoupper($colConfig), 'NOT NULL') !== false)
        {
            return $this;
        }

        // Remove all null values
        $this->connectionHandler->query("UPDATE `$this->tableName` SET `$this->column` = :not_null WHERE `$this->column` IS NULL", ['not_null' => $notNull]);

        // If the default is set to null remove it
        if (strpos(strtoupper($colConfig), 'DEFAULT NULL') !== false)
        {
            $colConfig = str_replace('DEFAULT NULL', "DEFAULT $notNull", $colConfig);
        }

        $colConfig = "$colConfig NOT NULL";

        // Change the column config
        $this->connectionHandler->query($this->connectionHandler->cleanQuery("ALTER TABLE `$this->tableName` MODIFY COLUMN `$this->column` $colConfig"));

        // Reload the column config
        $this->loadColumns();

        // Return Alter for chaining
        return $this;
    }

    /**
     * Drop "not null" on a column.
     *
     * @return \phpsqlbuilder\query\Alter
     */
    public function DROP_NOT_NULL(): Alter
    {
        // Get the existing config
        $colConfig = $this->getColumnConfig();

        // Only change the config if NOT NULL exists
        if (strpos(strtoupper($colConfig), 'NOT NULL') !== false)
        {
            // Remove the NOT NULL
            $colConfig = str_replace('NOT NULL', '', $colConfig);

            // Change the column config
            $this->connectionHandler->query($this->connectionHandler->cleanQuery("ALTER TABLE `$this->tableName` MODIFY COLUMN `$this->column` $colConfig"));
        }

        // Reload the column config
        $this->loadColumns();

        // Return Alter for chaining
        return $this;
    }

    /**
     * Add unsinged to an integer or number based column.
     *
     * @return \phpsqlbuilder\query\Alter
     */
    public function ADD_UNSIGNED(): Alter
    {
        // Get the existing config
        $colConfig = $this->getColumnConfig();

        // If UNSIGNED is already set return
        if (strpos(strtoupper($colConfig), 'UNSIGNED') !== false)
        {
            return $this;
        }

        // Add UNSIGNED as the second value
        $pos       = strpos($colConfig, ' ');
        $colConfig = substr_replace($colConfig, ' UNSIGNED ', $pos, 0);

        // Change the column config
        $this->connectionHandler->query($this->connectionHandler->cleanQuery("ALTER TABLE `$this->tableName` MODIFY COLUMN `$this->column` $colConfig"));

        // Reload the column config
        $this->loadColumns();

        // Return Alter for chaining
        return $this;
    }

    /**
     * Drop unsigned on an integer or number based column.
     *
     * @return \phpsqlbuilder\query\Alter
     */
    public function DROP_UNSIGNED(): Alter
    {
        // Get the existing config
        $colConfig = $this->getColumnConfig();

        // Only change if UNSIGNED is already set
        if (strpos(strtoupper($colConfig), 'UNSIGNED') !== false)
        {
            // Remove the UNSIGNED
            $colConfig = str_replace('UNSIGNED', '', $colConfig);
            $colConfig = str_replace('unsigned', '', $colConfig);

            // Change the column config
            $this->connectionHandler->query($this->connectionHandler->cleanQuery("ALTER TABLE `$this->tableName` MODIFY COLUMN `$this->column` $colConfig"));
        }

        // Reload the column config
        $this->loadColumns();

        // Return Alter for chaining
        return $this;
    }

    /**
     * Add auto increment to an integer primary key column.
     *
     * @return \phpsqlbuilder\query\Alter
     */
    public function SET_AUTO_INCREMENT(): Alter
    {
        // Get the existing config
        $colConfig = $this->getColumnConfig();

        // Only change if AUTO_INCREMENT is not set
        if (strpos(strtoupper($colConfig), 'AUTO_INCREMENT') !== false)
        {
            return $this;
        }

        // If the column is already the primary key
        if (strpos(strtoupper($colConfig), 'PRIMARY KEY') !== false)
        {
            // Change the column config
            $this->connectionHandler->query($this->connectionHandler->cleanQuery("ALTER TABLE `$this->tableName` MODIFY COLUMN `$this->column` INT NOT NULL AUTO_INCREMENT UNIQUE PRIMARY KEY"));
        }
        else
        {
            // Drop the primary key
            if ($this->getPrimaryKey())
            {
                $this->DROP_PRIMARY_KEY();
            }

            // Change the column config
            $this->connectionHandler->query($this->connectionHandler->cleanQuery("ALTER TABLE `$this->tableName` MODIFY COLUMN `$this->column` INT NOT NULL AUTO_INCREMENT UNIQUE PRIMARY KEY"));
        }

        // Reload the column config
        $this->loadColumns();

        // Return Alter for chaining
        return $this;
    }

    /**
     * DROP auto increment on an integer primary key column.
     *
     * @return \phpsqlbuilder\query\Alter
     */
    public function DROP_AUTO_INCREMENT(): Alter
    {
        // Get the existing config
        $colConfig = $this->getColumnConfig();

        // Only change if AUTO_INCREMENT is set
        if (strpos(strtoupper($colConfig), 'AUTO_INCREMENT') !== false)
        {
            $this->connectionHandler->query($this->connectionHandler->cleanQuery("ALTER TABLE `$this->tableName` MODIFY COLUMN `$this->column` INT NOT NULL UNIQUE"));
        }

        // Reload the column config
        $this->loadColumns();

        // Return Alter for chaining
        return $this;
    }

    /**
     * Set default value for column.
     *
     * @return \phpsqlbuilder\query\Alter
     */
    public function SET_DEFAULT($value = 'NULL'): Alter
    {
        // Set the default value
        $this->connectionHandler->query($this->connectionHandler->cleanQuery("ALTER TABLE `$this->tableName` ALTER `$this->column` SET DEFAULT $value"));

        // Reload the column config
        $this->loadColumns();

        // Return Alter for chaining
        return $this;
    }

    /**
     * Drop default value for column.
     *
     * @return \phpsqlbuilder\query\Alter
     */
    public function DROP_DEFAULT(): Alter
    {
        // Remove default value
        $colConfig = str_replace('DEFAULT NULL', '', $this->setColumnConfig('DEFAULT', 'NULL'));

        // Save the column params
        $this->connectionHandler->query($this->connectionHandler->cleanQuery("ALTER TABLE `$this->tableName` MODIFY COLUMN `$this->column` $colConfig"));

        // Reload the column config
        $this->loadColumns();

        // Return Alter for chaining
        return $this;
    }

    /**
     * Add unique contraint on column.
     *
     * @return \phpsqlbuilder\query\Alter
     */
    public function ADD_UNIQUE(): Alter
    {
        // Only change if UNIQUE is not set
        if (strpos(strtoupper($this->getColumnConfig()), 'UNIQUE') !== false)
        {
            return $this;
        }

        // Change the column config
        $colConfig = str_replace('DEFAULT NULL', '', $this->setColumnConfig('Key', 'UNI'));

        // Save the column params
        $this->connectionHandler->query($this->connectionHandler->cleanQuery("ALTER TABLE `$this->tableName` MODIFY COLUMN `$this->column` $colConfig"));

        // Reload the column config
        $this->loadColumns();

        // Return Alter for chaining
        return $this;
    }

    /**
     * Drop unique contraint on column.
     *
     * @return \phpsqlbuilder\query\Alter
     */
    public function DROP_UNIQUE(): Alter
    {
        // Only change if UNIQUE is not set
        if (strpos(strtoupper($this->getColumnConfig()), 'UNIQUE') !== false)
        {
            // Change the column config
            $colConfig = str_replace('DEFAULT NULL', '', $this->setColumnConfig('Key', ''));

            // Save the column params
            $this->connectionHandler->query($this->connectionHandler->cleanQuery("ALTER TABLE `$this->tableName` MODIFY COLUMN `$this->column` $colConfig"));

            // Reload the column config
            $this->loadColumns();
        }

        // Return Alter for chaining
        return $this;
    }

    /**
     * Add a foreign key constraint to a column.
     *
     * @param  string                                $referenceTable The name of the reference table
     * @param  string                                $referenceKey   The name of the column on the reference table
     * @param  string|null                           $constraint     The constraint name to add (optional) (default null)
     * @return \phpsqlbuilder\query\Alter
     */
    public function ADD_FOREIGN_KEY(string $referenceTable, string $referenceKey, string $constraint = null): Alter
    {
        // Prefix the reference table
        $referenceTable = $this->connectionHandler->tablePrefix() . $this->indexFilter($referenceTable);

        // Create a constraint if it was not provided
        $kCol   = substr(str_replace($this->connectionHandler->tablePrefix(), '', $this->column), 0, 3);
        $ktble  = substr(str_replace($this->connectionHandler->tablePrefix(), '', $referenceTable), 0, 3);
        $ktble2 = substr(str_replace($this->connectionHandler->tablePrefix(), '', $this->tableName), 0, 3);
        $kkey   = substr(str_replace($this->connectionHandler->tablePrefix(), '', $referenceKey), 0, 3);
        $constraint   = $constraint ? $constraint : str_replace(' ', '_', "fk $kCol toTable $ktble fromT $ktble2 onCol $kkey");

        // Save the FK
        $this->connectionHandler->query("ALTER TABLE `$this->tableName` ADD CONSTRAINT `$constraint` FOREIGN KEY (`$this->column`) REFERENCES $referenceTable(`$referenceKey`)");

        // Reload the column config
        $this->loadColumns();

        return $this;
    }

    /**
     * Drop a foreign key constraint to a column.
     *
     * @param  string                                $referenceTable The name of the reference table
     * @param  string                                $referenceKey   The name of the column on the reference table
     * @param  string                                $constraint     The constraint name to remove (optional) (default null)
     * @return \phpsqlbuilder\query\Alter
     */
    public function DROP_FOREIGN_KEY($referenceTable, $referenceKey, $constraint = null): Alter
    {
        // Prefix the reference table
        $referenceTable = $this->connectionHandler->tablePrefix() . $this->indexFilter($referenceTable);

        // Create a constraint if it was not provided
        $kCol   = substr(str_replace($this->connectionHandler->tablePrefix(), '', $this->column), 0, 3);
        $ktble  = substr(str_replace($this->connectionHandler->tablePrefix(), '', $referenceTable), 0, 3);
        $ktble2 = substr(str_replace($this->connectionHandler->tablePrefix(), '', $this->tableName), 0, 3);
        $kkey   = substr(str_replace($this->connectionHandler->tablePrefix(), '', $referenceKey), 0, 3);
        $constraint = $constraint ? $constraint : str_replace(' ', '_', "fk $kCol toTable $ktble fromT $ktble2 onCol $kkey");

        // Remove the FK
        $this->connectionHandler->query("ALTER TABLE `$this->tableName` DROP FOREIGN KEY `$constraint`");

        // Reload the column config
        $this->loadColumns();

        return $this;
    }

    /********************************************************************************
    * PRIVATE HELPER METHODS
    *******************************************************************************/

    /**
     * Load the current table's columns and column parameters.
     */
    private function loadColumns(): void
    {
        $columns = [];

        $cols = $this->connectionHandler->query("SHOW COLUMNS FROM `$this->tableName`");

        foreach ($cols as $col)
        {
            $columns[$col['Field']] = $col;
        }

        $this->columns = $columns;
    }

    /**
     * Validate a column exists.
     *
     * @param  string $column The column name
     * @return bool
     */
    private function columnExists(string $column): bool
    {
        return isset($this->columns[$column]);
    }

    /**
     * Get the table's PRIMARY KEY column name.
     *
     * @return string|false
     */
    private function getPrimaryKey()
    {
        $key = $this->connectionHandler->query("SHOW KEYS FROM `$this->tableName` WHERE Key_name = 'PRIMARY'");

        if (isset($key[0]['Column_name']))
        {
            return $key[0]['Column_name'];
        }

        return false;
    }

    /**
     * Get a column or the current column's configuration.
     *
     * @param  string|null $column (optional) (default null)
     * @return string
     */
    private function getColumnConfig(string $column = null): string
    {
        $strConfig = '';

        $config = !$column ? $this->columns[$this->column] : $this->columns[$column];

        $notNull = $config['Null'] === 'NO' ? 'NOT NULL' : '';

        $default = $config['Default'] === null ? 'NULL' : $config['Default'];

        //$key = $config['Key'] === 'PRI' ? 'PRIMARY KEY' : '';

        $key = $config['Key'] === 'UNI' ? 'UNIQUE' : '';

        $extra = strtoupper($config['Extra']);

        $strConfig = $config['Type'] . " DEFAULT $default $notNull $key $extra";

        return trim($strConfig);
    }

    /**
     * Set a key/value pair on a column or the current column configuration.
     *
     * @param  string      $key    The configuration key
     * @param  string      $value  The configuration value for the column
     * @param  string|null $column The column name (optional) (default null)
     * @return string
     */
    private function setColumnConfig(string $key, string $value, string $column = null): string
    {
        $key = ucfirst(strtolower($key));

        $strConfig = '';

        $config = !$column ? $this->columns[$this->column] : $this->columns[$column];

        $config[$key] = $value;

        $notNull = $config['Null'] === 'NO' ? 'NOT NULL' : '';

        $default = $config['Default'] === null ? 'NULL' : $config['Default'];

        //$key = $config['Key'] === 'PRI' ? 'PRIMARY KEY' : '';

        $key = $config['Key'] === 'UNI' ? 'UNIQUE' : '';

        $extra = strtoupper($config['Extra']);

        $strConfig = $config['Type'] . " DEFAULT $default $notNull $key $extra";

        return trim($strConfig);
    }

    /**
     * Filter a column or table name to valid SQL.
     *
     * @param  string $str
     * @return string
     */
    private function indexFilter(string $str): string
    {
        return strtolower(str_replace(' ', '_', $str));
    }

    /**
     * Safely format the query consistently.
     *
     * @param  string $sql SQL query statement
     * @return string
     */
    private function cleanQuery(string $sql): string
    {
       return trim(preg_replace('/\s+/', ' ', $sql));
    }
}

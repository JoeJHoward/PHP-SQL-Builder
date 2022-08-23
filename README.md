<div align="center">
	<h3 align="center">PHP SQL Builder</h3>
  	<p align="center">
    	PHP SQL builder provides a complete 100% safe and secure solution for managing SQL connections through a set of handy PHP utility classes. Never write SQL in your PHP project again!
    	<br />
    	<br />
    	<a href="https://github.com/JoeJHoward/PHP-SQL-Builder/issues">Report Bug</a>
    	·
    	<a href="https://github.com/JoeJHoward/PHP-SQL-Builder/issues">Request Feature</a>
  	</p>
</div>

## Table Of Contents

- [About The Project](#about-the-project)
- [Getting Started](#getting-started)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Configuration](#configuration)
- [Connections](#connections)
	- [Connection Handler](#connection-handler)
	- [Queries](#queries)
	- [Cache](#cache)
- [Query builder](query-builder)
	- [Table Management](#table-management)
		- [Alter](#alter)
		- [Foreign Keys](#foreign-keys)
		- [Alter Chains](#alter-chains)
	- [Query Building](#query-building)
		- [Types](#types)
		- [Filters](#filters)
		- [Organizers](#organizers)
		- [Executions](#executions)
		- [Query Chains](#query-chains)

## About The Project

PHP SQL Builder provides simple yet surprisingly robust handler for managing database connections and queries.

All interactions with your database are done through a single `Database` class. The `Database` class automatically prepares all your statements and queries for you, so you don't need to worry about SQL injection.

## Getting Started

Follow the steps below to start using the SQL builder

### Prerequisites

- PHP >= 7.2
- PDO PHP Extension
- Composer (optional)
- Web Server With SQL

### Installation

The preferred way to install is via composer:

```sh
composer require joejhoward/php-sql-builder
```

### Configuration

Proident sunt consequat cupidatat non proident reprehenderit consectetur id cillum ut sunt occaecat elit sit aliquip dolore id magna proident veniam duis irure non nostrud est sed exercitation do dolore aliquip minim ea.

## Connections & Queries

Creating a database connection is done using the `connection` method:

```php
# Returns connection object using the "default" database configuration defined in the config file
$connection = $database->connection();

# Returns connection object using the "mydb" database configuration defined in the config file
$connection = $database->connection('mydb');
```
<br/>

The `connections` method returns an array of connection objects for all active connections:

```php
$connections = $database->connections();
```
<br/>

The `connect` method attempts to connect to the database and throws a `PDOException` if it fails. If successful, a `PDO` extension instance is returned:

```php
# You may need to try catch this if you don't want the exception to be thrown
try
{
    $pdo = $connection->connect();
}
catch(PDOException $e)
{
    # Do something else here
}
```
<br/>

The `isConnected` method check if the connection is connected to the database and returns a boolean:

```php
if ($connection->isConnected())
{
}
```
<br/>

The `reconnect` method attempts to reconnect to the database and throws a `PDOException` if it fails. If successful, a `PDO` extension instance is returned:

```php
if ($connection->isConnected())
{
    try
    {
        $pdo = $connection->reconnect();
    }
    catch(PDOException $e)
    {
        # Do something else here
    }
}
```
<br/>

The `pdo` method is similar to the reconnect method and attempts to connect to the database and throws a `PDOException` if it fails. If successful, a `PDO` extension instance is returned:

```php
try
{
    $pdo = $connection->pdo();
}
catch(PDOException $e)
{
    # Do something else here
}
```
<br/>

The `isAlive` method is checks if the current connection is alive and returns a boolean:

```php
if ($connection->isAlive())
{
}
```
<br/>

The `close` method closes the connection and destructs the `PDO` extension instance.

```php
$connection->close();
```
<br/>

The `tablePrefix` method returns the string prefix of the database table or an empty string if one is not set.

```php
$prefix = $connection->tablePrefix();
```
<br/>

## Connection Handler
Connections come with a handy `connectionHandler` for interacting with the database and executing queries. When running queries through any database the `connectionHandler` should always be used.

You can call the handler method to get a connection's handler instance.

```php
$handler = $connection->handler();
```
<br/>

The `getLog` method returns an array of all queries executed by the handler.

```php
$log = $handler->getLog();

$lastQuery = array_pop($handler->getLog());
```
<br/>

## Queries
Database queries should be run through a `connectionHandler` instance.

The `query` method executes a given query:

```php
$users = $handler->query('SELECT * FROM kanso_users');
```
<br/>

The `row` method returns a single row or an empty array if the results are empty:

```php
$users = $handler->row('SELECT * FROM kanso_users WHERE email = :email', ['email' => 'email@example.com']);
```
<br/>

The `single` method always returns a single value of a record:

```php
$name = $handler->single('SELECT name FROM kanso_users WHERE id = :id', ['id' => 1]);
```
<br/>

The `column` method always returns a single column:
```php
$names = $handler->column('SELECT name FROM kanso_users');
```
<br/>

### Bindings
Binding parameters is the best way to prevent SQL injection. The class prepares your SQL query and binds the parameters afterwards. There are three different ways to bind parameters:

```php
# 1. Read friendly method  
$handler->bind('id', 1);
$handler->bind('name','John');
$user = $handler->query('SELECT * FROM kanso_users WHERE name = :name AND id = :id');

# 2. Bind more parameters
$handler->bindMore(['name'=>'John','id'=>'1']);
$user = $handler->query('SELECT * FROM kanso_users WHERE name = :name AND id = :id');

# 3. Or just give the parameters to the method
$user = $handler->query('SELECT * FROM kanso_users WHERE name = :name', ['name'=>'John','id'=>'1']);
```
<br/>

### Delete / Update / Insert
When executing the `delete`, `update`, or `insert` statements via the query method the affected rows will be returned:

```php
# Delete
$delete = $handler->query('DELETE FROM kanso_users WHERE Id = :id', ['id'=>'1']);
```

```php
# Update
$update = $handler->query('UPDATE kanso_users SET name = :f WHERE Id = :id', ['f'=>'Jan','id'=>'32']);
```

```php
# Insert
$insert = $handler->query('INSERT INTO kanso_users(name,Age) VALUES(:f,:age)', ['f'=>'Vivek','age'=>'20']);

# Do something with the data 
if($insert > 0 )
{
    return 'Succesfully created a new person !';
}
```
<br/>

The `lastInsertId` method returns the last inserted id:

```php
if ($handler->query('INSERT INTO kanso_users(name,Age) VALUES(:f,:age)', ['f'=>'Vivek','age'=>'20']))
{
    $id = $handler()->lastInsertId();
}
```
<br/>

### Method Params
The `row` and the `query` method have a third optional parameter which is the fetch style.

The default fetch style is `PDO::FETCH_ASSOC` which returns an associative array. You can change this behavior by providing a valid PHP [PDO fetch_style](https://www.php.net/manual/en/pdostatement.fetch.php) as the third parameter.

```php
# Fetch style as third parameter
$authorNum = $connection->row('SELECT * FROM kanso_users WHERE id = :id', ['id' => 1 ], PDO::FETCH_NUM);

print_r($person_num);
# [ [0] => 1 [1] => Johny [2] => Doe [3] => M [4] => 19 ]
```
<br/>

## Cache
The `ConnectionHandler` comes with a very basic cache implementation for caching `SELECT` query results across a single request.

When enabled, the cache will check to see if the same query has already been run during the request and attempt to load it from the cache.

If you execute an `UPDATE` or `DELETE` query, the results will be cleared from the cache.

To access the cache, use the cache method on a `ConnectionHandler` instance:

```php
$cache = $handler->cache();
```
<br/>

The `disable` method disables the cache:

```php
$cache->disable();
```
<br/>

The `enable` method enables the cache:
```php
$cache->enable();
```
<br/>

The `enabled` method returns a boolean on the cache status:
```php
if ($cache->enabled())
{   
}
```
<br/>

## Query Builder

PHP SQL Builder Query Builder allows you to programmatically build SQL queries without having to write giant SQL statements.

Essentially, the Builder class is a chainable wrapper around the SQL syntax. All queries executed by the builder use prepared statements, thus mitigating the risk of SQL injections.

When chaining methods, the chaining order follows the same syntax as if you were to write an SQL query statement.

### Access
You can access the Builder class directly through the IoC container via the Database object:

```php
$builder = $kanso->Database->builder();
```
<br/>

Alternatively if you have a reference to an existing database `connection`, you can access the builder directly through the `connection`.

```php
$builder = $kanso->Database->connection()->builder();
```
<br/>

### Table Management
The Builder class provides various methods to manipulate and interact with database tables. All the table management will return the Builder instance at hand, making them chainable.

The `CREATE_TABLE` method is used to create a table:

```php
$customPosts =
[
    'id'          => 'INTEGER | UNSIGNED | PRIMARY KEY | UNIQUE | AUTO INCREMENT',
    'created'     => 'INTEGER | UNSIGNED',
    'modified'    => 'INTEGER | UNSIGNED',
    'type'        => 'VARCHAR(255)',
];
$builder->CREATE_TABLE('custom_posts' $customPosts);
```
<br/>

The `DROP_TABLE` method drops a table:

```php
$builder->DROP_TABLE('custom_posts');
```
<br/>

The `TRUNCATE_TABLE` method truncates a table's columns:

```php
$builder->TRUNCATE_TABLE('custom_posts');
```
<br/>

#### Alter
To start altering a table, use the `ALTER_TABLE` method:

```php
$table = $builder->ALTER_TABLE('custom_posts');
```
<br/>

> The `Alter` class provides a number of helper methods to interact with the table at hand. The alter methods all return the working instance of the Alter class, making them chainable.

The `ADD_COLUMN` method adds a column to an existing table:

```php
$table->ADD_COLUMN('author_id', 'INTEGER | UNSIGNED');
```
<br/>

The `DROP_COLUMN` method drops an existing column on an existing table:

```php
$table->DROP_COLUMN('author_id');
```
<br/>

The `MODIFY_COLUMN` method can be used to set a column's data-type by providing a second parameter:

```php
$table->MODIFY_COLUMN('author_id', 'INTEGER | UNSIGNED | UNIQUE');
```
<br/>

Or to set the working column for other methods by omitting it.

```php
$column = $table->MODIFY_COLUMN('author_id');
```
<br/>

Once you have called the `MODIFY_COLUMN` method, the following column modification methods are made available:

```php
$column->ADD_PRIMARY_KEY();
$column->DROP_PRIMARY_KEY();
$column->ADD_NOT_NULL();
$column->DROP_NOT_NULL();
$column->ADD_UNSIGNED();
$column->DROP_UNSIGNED();
$column->SET_AUTO_INCREMENT();
$column->DROP_AUTO_INCREMENT();
$column->SET_DEFAULT($value = null);
$column->DROP_DEFAULT();
$column->ADD_UNIQUE();
$column->DROP_UNIQUE();
$column->ADD_FOREIGN_KEY($referenceTable, $referenceKey, $constraint = null);
$column->DROP_FOREIGN_KEY($referenceTable, $referenceKey, $constraint = null);
```
<br/>

#### Foreign Keys
To set a foreign key constraint, use the `ADD_FOREIGN_KEY` method. The first parameter is the reference table, the second is the reference table's column name. The third parameter is optional and is used to set the name of the constraint. If omitted, a constraint name will be generated for you.

```php
$column->ADD_FOREIGN_KEY('users', 'id');
```
<br/>

Dropping a foreign key constraint follows the same rules as the `ADD_FOREIGN_KEY` method.

```php
$column->DROP_FOREIGN_KEY('users', 'id');
```
<br/>

#### Alter Chains
A simple chain starting from a Builder instance might look like this:

```php
$builder->ALTER_TABLE('custom_posts')->ADD_COLUMN('author_id', 'INTEGER | UNSIGNED');
```
<br/>

A more complicated chain starting from a Builder instance might look like this:

```php
$builder->ALTER_TABLE('custom_posts')->ADD_COLUMN('author_id', 'INTEGER | UNSIGNED')->MODIFY_COLUMN('author_id')->ADD_FOREIGN_KEY('users', 'id');
```
<br/>

### Query Building
The Builder class provides almost all SQL query statements by providing a wrapper around the Query class. The methods can be placed into three logical sections:

- Query types
- Query filters
- Query organizers
- Query executions

#### Types

Query types are where you define the type of query you are going to execute `INSERT`, `DELETE`, `UPDATE`, `SET`; as well the table upon which the query will run.

The following methods can be used to set the query type:

```php
# Set the query to query a given table
$builder->FROM($tablename);

# Set the query type to UPDATE on a given table
$builder->UPDATE($tablename);

# Set the query type to INSERT INTO on a given table
$builder->INSERT_INTO($tablename);

# Set the query type to DELETE on a given table
$builder->DELETE_FROM($tablename);

# Set the query type to SELECT on the current 
# table and set the columns to select
$builder->SELECT($tablename);

# Set the query type to INSERT INTO on the current 
# table and set the values to insert
$builder->VALUES($rows);

# Set the query type to SET on the current 
# table and set the values to set
$builder->SET($rows);
```
<br/>

#### Filters
Query filters are where you build your query to filter the table results. The following methods can be used to filter the query:

```php
# Add a WHERE clause
$builder->WHERE($column, $operator, $value);

# Add an AND WHERE clause
$builder->AND_WHERE($column, $operator, $value);

# Add an OR WHERE clause
$builder->OR_WHERE($column, $operator, $value);

# Nested OR WHERE clause
$builder->OR_WHERE($column, $operator, ['foo', 'bar', 'baz']);

# Add a JOIN and ON clause
$builder->JOIN_ON($tablename, $operation);

# Add an INNER JOIN and ON clause
$builder->INNER_JOIN_ON($tablename, $operation);

# Add an LEFT JOIN and ON clause
$builder->LEFT_JOIN_ON($tablename, $operation);

# Add an RIGHT JOIN and ON clause
$builder->RIGHT_JOIN_ON($tablename, $operation);

# Add an OUTER JOIN and ON clause
$builder->OUTER_JOIN_ON($tablename, $operation);
```
<br/>

#### Organisers
Query organizers are where you define how your results should be formatted. The following methods can be used to organize the query results:

```php
# Set the ORDER BY keyword
$builder->ORDER_BY($columnName, $direction = 'DESC');

# Add an GROUP BY keyword
$builder->GROUP_BY($columnName);

# Add a GROUP_CONCAT function
$builder->GROUP_CONCAT($column, $operator, $value);

# Set the limit
$builder->LIMIT($offset, $number);
```
<br/>

#### Executions
Query executions are the final method to call and will immediately execute the query and return the results.

```php
# Execute the query and limit the results to single row
$builder->ROW();

# Execute the query, limit the results to single row
# if an id is provided add (id = $id) AND WHERE clause
$builder->FIND($id = null);

# Execute the query
$builder->FIND_ALL();

# Execute an INSERT, DELETE, UPDATE or SET query
$builder->QUERY();
```
<br/>

#### Query Chains
Query chaining is where it all comes together and allows you to execute a query in the same syntax as if you were to write an SQL query statement. Here are some examples:

```php
# Check if an author is registered under a given email address
$email = $builder->SELECT('*')
         ->export();>FROM('users')
         ->WHERE('email', '=', 'example@email.com')
         ->FIND();

# Check if an author is registered under a given email address and username
$author = $builder->SELECT('*')
          ->export();>FROM('users')
          ->WHERE('username', '=', 'johndoe')
          ->AND_WHERE('email', '=', 'example@email.com')
          ->ROW();

# Check if an author is registered under a multiple email addresses using nested
# Or statements
$authors = $builder->SELECT('*')
           ->FROM('users')
           ->WHERE('email', '=', ['foo@bar.com', 'bar@foo.com'])
           ->FIND_ALL();

# Get all autors that are confirmed
$users = $builder->SELECT('*')
         ->FROM('users')
         ->WHERE('status', '=', 'confirmed')
         ->FIND_ALL();

# Get all of a post's tags
$tags = $builder->SELECT('tags.*')
        ->FROM('tags_to_posts')
        ->LEFT_JOIN_ON('tags', 'tags.id = tags_to_posts.tag_id')
        ->WHERE('tags_to_posts.post_id', '=', 2)
        ->FIND_ALL();

# Insert a row into the categories table
$insert = $builder->INSERT_INTO('categories')
        ->VALUES([
            'name' => 'JavaScript',
            'slug' => 'javascript'
        ])
        ->QUERY();

# Update a post status
$update = $builder->UPDATE('posts')
        ->SET(['status' => 'published'])
        ->WHERE('id', '=', 5)
        ->QUERY();

# Delete some posts
$delete = $builder->DELETE_FROM('posts')
        ->WHERE('created', '<', strtotime('-5 months'))
        ->OR_WHERE('modified', '<', strtotime('-1 year'))
        ->QUERY();

```
<br/>
<!-- ROADMAP -->
## Roadmap

- [ ] Feature 1
- [ ] Feature 2
- [ ] Feature 3
    - [ ] Nested Feature

See the [open issues](https://github.com/JoeJHoward/PHP-SQL-Builder/issues) for a full list of proposed features (and known issues).


<!-- CONTRIBUTING -->
## Contributing

Contributions are what make the open source community such an amazing place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

If you have a suggestion that would make this better, please fork the repo and create a pull request. You can also simply open an issue with the tag "enhancement".
Don't forget to give the project a star! Thanks again!

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request




<!-- LICENSE -->
## License

Distributed under the MIT License. See `LICENSE.txt` for more information.




<!-- CONTACT -->
## Contact

Your Name - [@twitter_handle](https://twitter.com/twitter_handle) - email@email_client.com

Project Link: [https://github.com/JoeJHoward/PHP-SQL-Builder](https://github.com/JoeJHoward/PHP-SQL-Builder)




<!-- ACKNOWLEDGMENTS -->
## Acknowledgments

* []()
* []()
* []()




<!-- MARKDOWN LINKS & IMAGES -->
<!-- https://www.markdownguide.org/basic-syntax/#reference-style-links -->
[contributors-shield]: https://img.shields.io/github/contributors/github_username/repo_name.svg?style=for-the-badge
[contributors-url]: https://github.com/JoeJHoward/PHP-SQL-Builder/graphs/contributors
[forks-shield]: https://img.shields.io/github/forks/github_username/repo_name.svg?style=for-the-badge
[forks-url]: https://github.com/JoeJHoward/PHP-SQL-Builder/network/members
[stars-shield]: https://img.shields.io/github/stars/github_username/repo_name.svg?style=for-the-badge
[stars-url]: https://github.com/JoeJHoward/PHP-SQL-Builder/stargazers
[issues-shield]: https://img.shields.io/github/issues/github_username/repo_name.svg?style=for-the-badge
[issues-url]: https://github.com/JoeJHoward/PHP-SQL-Builder/issues
[license-shield]: https://img.shields.io/github/license/github_username/repo_name.svg?style=for-the-badge
[license-url]: https://github.com/JoeJHoward/PHP-SQL-Builder/blob/master/LICENSE.txt
[linkedin-shield]: https://img.shields.io/badge/-LinkedIn-black.svg?style=for-the-badge&logo=linkedin&colorB=555
[linkedin-url]: https://linkedin.com/in/linkedin_username
[product-screenshot]: images/screenshot.png
[Next.js]: https://img.shields.io/badge/next.js-000000?style=for-the-badge&logo=nextdotjs&logoColor=white
[Next-url]: https://nextjs.org/
[React.js]: https://img.shields.io/badge/React-20232A?style=for-the-badge&logo=react&logoColor=61DAFB
[React-url]: https://reactjs.org/
[Vue.js]: https://img.shields.io/badge/Vue.js-35495E?style=for-the-badge&logo=vuedotjs&logoColor=4FC08D
[Vue-url]: https://vuejs.org/
[Angular.io]: https://img.shields.io/badge/Angular-DD0031?style=for-the-badge&logo=angular&logoColor=white
[Angular-url]: https://angular.io/
[Svelte.dev]: https://img.shields.io/badge/Svelte-4A4A55?style=for-the-badge&logo=svelte&logoColor=FF3E00
[Svelte-url]: https://svelte.dev/
[Laravel.com]: https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white
[Laravel-url]: https://laravel.com
[Bootstrap.com]: https://img.shields.io/badge/Bootstrap-563D7C?style=for-the-badge&logo=bootstrap&logoColor=white
[Bootstrap-url]: https://getbootstrap.com
[JQuery.com]: https://img.shields.io/badge/jQuery-0769AD?style=for-the-badge&logo=jquery&logoColor=white
[JQuery-url]: https://jquery.com 
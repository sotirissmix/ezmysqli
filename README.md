# ezmysqli

A very basic PHP MySQLi helper for projects that don't need much

# Initializing

Create a new ezmysqli instance

# Available Functions

##### These functions build the query.

select(array|string $fields, $table)

where($field,$operator,$value)

or($field,$operator,$value)

and($field,$operator,$value)

##### Returns the results of the query as an array

get()

These functions always return the object itself, so you can use them by chaining them together.
e.g.

> $ez = $ezmsqli($db_username,$db_password, $db_name, $host, $port);
> $user_number_5 = $ez->select('*','users')->where('id','=','5')->get();

### TODO

1. Make all statements prepared

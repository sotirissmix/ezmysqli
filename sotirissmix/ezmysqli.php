<?php

namespace Sotirissmix\EzMysqli;

class EzMysqli {
    private $host;
    private $username;
    private $password;
    private $port;
    private $db;
    
    private $connection;

    private $query;

    private $allowed_instructions;
        
    /**
     * __construct
     * @param string    $username   database user
     * @param string    $password   database password
     * @param string    $host hostname (defaults to localhost)
     * @param int    $port can be left to blank if default
     * @param string    $db database name
     * @return void
     */
    public function __construct(string $username, string $password, string $db, string $host = 'localhost', int $port = null) {
        $this->username = $username;
        $this->password = $password;
        $this->port = $port ?? ini_get('mysqli.default_port');
        $this->db = $db;
        $this->host = $host;
        $this->query = '';
        $this->initializeInstructionsList();
        $this->connect();
    }

    private function connect() {
        $this->connection = new \mysqli($this->host, $this->username, $this->password,  $this->db, $this->port);
        if($this->connection->connect_error) {
            $this->criticalFail('Connection error: ' . $this->connection->connect_error);
        }
    }

    private function fail($error_message) {
        echo "<pre>$error_message</pre>";
    }

    private function criticalFail($error_message) {
        die($error_message);
    }
    
    private function isAllowed($instruction, $error_message = 'Instruction not allowed') {
        if(in_array($instruction,$this->allowed_instructions))
            return true;
        else
            $this->criticalFail($instruction . ' : ' .$error_message);
    }

    public function initializeInstructionsList() {
        $this->allowed_instructions = [];
        $this->allow(['select', 'insert', 'get'])
        ->prohibit(['where', 'and', 'or']);
    }

    private function allow($instructions) {
        foreach($instructions as $instruction) {
            if(!in_array($instruction, $this->allowed_instructions)) {
                array_push($this->allowed_instructions,$instruction);
            }
        }
        return $this;
    }
    private function prohibit($instructions) {
        foreach($instructions as $instruction) {
            if(($key = array_search($instruction, $this->allowed_instructions)) !== false) {
                unset($this->allowed_instructions[$key]);
            }
        }
        return $this;
    }

    private function execute() {
        if($this->connection->query($this->query)===true) {
            return true;
        } else {
            $this->criticalFail("Inserting failed: " . $this->connection->error);
        }
    }
    /**
     *  closes current database connection - usually when you want it to happen before the end of a file's execution
     * @return void
     */
    public function close() {
        $this->connection->close();
    }
    
    /**
     * select
     *
     * @param  array|string $fields fields to be selected from the database
     * @param  string $table
     * @return object
     */
    public function select($fields, string $table) {
        if($this->isAllowed('select')) {
            $this->query = 'SELECT ' . (is_array($fields) ? implode(',',$fields) : $fields) . ' ';
            $this->query .= 'FROM ' . $table . ' ';
            $this->allow(['where','get'])->prohibit(['select','insert']);
            return $this;
        }
    }

    /**
     *  @param $field   field to check
     *  @param $operator    operator to use for comparison
     *  @param $value   value to check against
     * @return object
     */
    public function where($field, $operator,$value) {
        if($this->isAllowed('where')) {
            $this->query .= 'WHERE ' . "$field" . ' ' . $operator . ' ' . "'$value'" . ' ';
            $this->allow(['and','or'])->prohibit(['where']);
            return $this;
        }
    }

    /**
     *  @param $field   field to check
     *  @param $operator    operator to use for comparison
     *  @param $value   value to check against
     * @return object
     */
    public function whereAnd($field, $operator,$value) {
        if($this->isAllowed('and')) {
            $this->query .= 'AND ' . "$field" . ' ' . $operator . ' ' . "'$value'" . ' ';
            return $this;
        }
    }

    /**
     *  @param $field   field to check
     *  @param $operator    operator to use for comparison
     *  @param $value   value to check against
     * @return object
     */
    public function whereOr($field, $operator,$value) {
        if($this->isAllowed('or')) {
            $this->query .= 'OR ' . "$field" . ' ' . $operator . ' ' . "'$value'" . ' ';
            return $this;
        }
    }
    
    
    /**
     * fields_values must be an associative array in the form of key_to_insert => value_to_insert_to_key
     *
     * @param  string $table
     * @param  array $fields_values
     * @return object
     */
    public function insert(string $table, array $fields_values) {
        if($this->isAllowed('insert')) {
            $this->clearCurrentQuery();
            $this->query = 'INSERT INTO ' . $table .
            ' ('. 
                implode(', ', array_keys($fields_values))
            .') VALUES (' .
                implode(', ', array_map(function($item) {return '\''. $item . '\'';}, $fields_values) )  .
            ')';
            return $this->execute();
        }
    }


    /**
     * Clears current query string
     * @return object
     */
    public function clearCurrentQuery() {
        $this->query = '';
        $this->initializeInstructionsList();
        return $this;
    }

    /**
     * Clears query string and replaces it with $query parameter
     * @return object
     */
    public function raw(string $query) {
        $this->clearCurrentQuery();
        $this->query = $query;
        $this->initializeInstructionsList();
        return $this;
    }
    
    /**
     * Returns results of built query so far in array form
     * @return array
     */
    public function get() {
        if($this->isAllowed('get')) {
            $query_result = $this->connection->query($this->query);
            $result = [];
            if($query_result->num_rows > 0) {
                while($row = $query_result->fetch_assoc()) {
                    array_push($result, $row);
                }
            }
            $this->clearCurrentQuery();
            if(count($result)>0) {
                return $result;
            } else {
                return ['results'=>0];
            }
        }
    }
}

?>
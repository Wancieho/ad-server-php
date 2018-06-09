<?php

use Jacwright\RestServer\RestException;

// #TODO: move mysqli into mysqli class

abstract class Model {

    static protected $table = '';

    static public function get($id = null) {
        if (!isset(Config::$driver)) {
            throw new RestException(401, 'Specify Config data driver');
        }

        if (Config::$driver === 'file') {
            
        } elseif (Config::$driver === 'mysqli') {
            // single entry query by ID
            $data = [];

            if ($id !== null) {
                $statement = DB::handler()->prepare('SELECT * FROM ' . self::tableName() . ' WHERE id=' . $id);

                $statement->execute();

                self::queryError($statement);

                $result = $statement->get_result();

                $data = $result->fetch_assoc();

                $statement->close();

                unset($data['id']);

                return (object) $data;
            } else { // all entries for specified table
                $statement = DB::handler()->prepare('SELECT * FROM ' . self::tableName());

                $statement->execute();

                self::queryError($statement);

                $result = $statement->get_result();

                // insert all DB results into array for return
                while ($row = $result->fetch_assoc()) {
                    unset($row['id']);

                    $data[] = $row;
                }

                $statement->close();

                return $data;
            }
        }

        throw new RestException(401, 'Specified Config data driver not supported');
    }

    static public function save($data = null) {
        $keys = [];
        $vals = [];
        $inserts = [];

        foreach ($data as $key => $val) {
            if (!is_null($val)) {
                $keys[] = $key;
                $vals[] = $val;
                $inserts[] = '?';
            }
        }

        $statement = DB::handler()->prepare('INSERT INTO ' . self::tableName() . ' (' . join(',', $keys) . ') VALUES (' . join(',', $inserts) . ')');

        $dataTypes = '';

        foreach ($data as $key => $val) {
            if (is_string($val)) {
                $dataTypes .= 's';
            } elseif (is_int($val)) {
                $dataTypes .= 'i';
            }
        }

        $params = [];

        // add data types to bind params array
        $params[] = &$dataTypes;

        // add values to bind params array
        foreach ($vals as $key => $val) {
            $params[] = &$vals[$key];
        }

        // dynamically call bind_param method based on array of fields to save
        call_user_func_array(array($statement, 'bind_param'), $params);

        $statement->execute();

        self::queryError($statement);

        // store inserted ID for return
        $id = $statement->insert_id;

        $statement->close();

        return ['id' => $id];
    }

    static public function delete($id = null) {
        $statement = DB::handler()->prepare('DELETE FROM ' . self::tableName() . ' WHERE id=' . $id);

        $statement->execute();

        self::queryError($statement);

        $success = false;

        if ($statement->affected_rows > 0) {
            $success = true;
        }

        $statement->close();

        if ($success > 0) {
            return (object) ['id' => $id];
        }

        return (object) [];
    }

    static private function tableName() {
        // if the table class property has not been declared then using the child class name as the DB table name
        if (empty(static::$table)) {
            static::$table = str_replace('model', '', strtolower(get_called_class()));
        }

        return static::$table;
    }

    static private function queryError($statement) {
        if (!empty($statement->error)) {
            throw new RestException(401, $statement->error);
        }
    }

}

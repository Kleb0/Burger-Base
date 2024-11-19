<?php

class DataBase {
    private static $host = "localhost";
    private static $dbName = "burger";
    private static $dbuser = "root";
    private static $dbpass = "";

    private static $connection = null;


    public static function connect()
    {
        if(self::$connection == null ){
            try{
                self::$connection = new PDO("mysql:host=".self::$host.";dbname=".self::$dbName.";charset=utf8", self::$dbuser, self::$dbpass);
            }
            catch(PDOException $e){
                die($e->getMessage());
            }

        }
        return self::$connection;
    }

    public static function disconnect()
    {
        self::$connection = null;
    }
}

DataBase::connect();
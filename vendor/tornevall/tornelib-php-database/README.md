# tornelib-php-database 6.1

The rewritten database driver for the tornelib-series.
Written to autoselect proper driver regardless of system content. 

## Testing

Test works best with a database installed. Installing it automatically is not offered yet. You could do something like this to prepare data if you need to run tests:

    CREATE USER 'tornelib'@'localhost' IDENTIFIED BY 'tornelib1337';
    GRANT ALL PRIVILEGES ON tornelib_tests.* TO tornelib@localhost;

    CREATE DATABASE tornelib_tests;
    USE tornelib_tests;
    DROP TABLE IF EXISTS `tests`;
    CREATE TABLE `tests` (
      `dataindex` int(11) NOT NULL AUTO_INCREMENT,
      `data` varchar(45) NOT NULL,
      PRIMARY KEY (`dataindex`,`data`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;

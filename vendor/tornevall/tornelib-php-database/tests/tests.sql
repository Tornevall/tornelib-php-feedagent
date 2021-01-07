/*
    CREATE USER 'tornelib'@'localhost' IDENTIFIED BY 'tornelib1337';
    GRANT ALL PRIVILEGES ON tornelib_tests.* TO tornelib@localhost;
 */

CREATE DATABASE tornelib_tests;
USE tornelib_tests;
DROP TABLE IF EXISTS `tests`;
CREATE TABLE `tests`
(
    `dataindex` int(11)     NOT NULL AUTO_INCREMENT,
    `data`      varchar(45) NOT NULL,
    PRIMARY KEY (`dataindex`, `data`)
) ENGINE = InnoDB
  DEFAULT CHARSET = latin1;

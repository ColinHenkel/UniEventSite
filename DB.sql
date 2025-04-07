CREATE DATABASE IF NOT EXISTS `EventSite`;
USE `EventSite`;

-- Users table
CREATE TABLE `EventSite`.`Users` (
  `UID` INT NOT NULL AUTO_INCREMENT,
  `firstname` VARCHAR(45) NOT NULL,
  `lastname` VARCHAR(45) NOT NULL,
  `university` VARCHAR(45) NOT NULL,
  `email` VARCHAR(45) NOT NULL,
  `username` VARCHAR(45) NOT NULL,
  `password` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`UID`),
  FOREIGN KEY (`university`) REFERENCES `University`(`Name`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- Admins table
CREATE TABLE `EventSite`.`Admins` (
  `ID` INT NOT NULL AUTO_INCREMENT,
  `University` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`ID`),
  FOREIGN KEY (`University`) REFERENCES `University`(`Name`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`ID`) REFERENCES `Users`(`UID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- SuperAdmins table
CREATE TABLE `EventSite`.`SuperAdmins` (
  `ID` INT NOT NULL AUTO_INCREMENT,
  `University` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`ID`),
  FOREIGN KEY (`University`) REFERENCES `University`(`Name`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`ID`) REFERENCES `Users`(`UID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- University table
CREATE TABLE `EventSite`.`University` (
  `Name` VARCHAR(45) NOT NULL,
  `Address` VARCHAR(255) NOT NULL,
  `Description` VARCHAR(255) NOT NULL,
  `StudentPopulation` INT NOT NULL,
  PRIMARY KEY (`Name`)
) ENGINE = InnoDB;

-- RSOs table
CREATE TABLE `EventSite`.`RSOs` (
  `ID` INT NOT NULL AUTO_INCREMENT,
  `Name` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE = InnoDB;

-- Location table
CREATE TABLE `EventSite`.`Location` (
  `Lname` VARCHAR(255) NOT NULL,
  `Address` VARCHAR(255) NOT NULL,
  `Longitude` DOUBLE NOT NULL,
  `Latitude` DOUBLE NOT NULL,
  PRIMARY KEY (`Lname`)
) ENGINE = InnoDB;

-- Events table (superclass)
CREATE TABLE `EventSite`.`Events` (
  `Event_ID` INT NOT NULL AUTO_INCREMENT,
  `Start` TIME NOT NULL,
  `End` TIME NOT NULL,
  `Date` DATE NOT NULL,
  `Lname` VARCHAR(255) NOT NULL,
  `Event_name` VARCHAR(255) NOT NULL,
  `Desc` TEXT NOT NULL,
  `University` VARCHAR(45) NOT NULL,
  `Admin` INT NOT NULL,
  PRIMARY KEY (`Event_ID`),
  FOREIGN KEY (`University`) REFERENCES `University`(`Name`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`Admin`) REFERENCES `Admins`(`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`Lname`) REFERENCES `Location`(`Lname`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- RSO_Events table (subclass of Events)
CREATE TABLE `EventSite`.`RSO_Events` (
  `Event_ID` INT NOT NULL,
  `RSO_ID` INT NOT NULL,
  PRIMARY KEY (`Event_ID`),
  FOREIGN KEY (`RSO_ID`) REFERENCES `RSOs`(`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`Event_ID`) REFERENCES `Events`(`Event_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- Private_Events table (subclass of Events)
CREATE TABLE `EventSite`.`Private_Events` (
  `Event_ID` INT NOT NULL,
  PRIMARY KEY (`Event_ID`),
  FOREIGN KEY (`Event_ID`) REFERENCES `Events`(`Event_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- Public_Events table (subclass of Events)
CREATE TABLE `EventSite`.`Public_Events` (
  `Event_ID` INT NOT NULL,
  PRIMARY KEY (`Event_ID`),
  FOREIGN KEY (`Event_ID`) REFERENCES `Events`(`Event_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- Comments table (for the diamond relationship)
CREATE TABLE `EventSite`.`Comments` (
  `ID` INT NOT NULL AUTO_INCREMENT,
  `Event_ID` INT NOT NULL,
  `UID` INT NOT NULL,
  `Text` TEXT NOT NULL,
  `rating` INT CHECK (rating BETWEEN 1 AND 5),
  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  FOREIGN KEY (`Event_ID`) REFERENCES `Events`(`Event_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`UID`) REFERENCES `Users`(`UID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- -- Owns relationship between RSOs and RSO_Events
-- CREATE TABLE `EventSite`.`Owns` (
--   `RSO_ID` INT NOT NULL,
--   `Event_ID` INT NOT NULL,
--   PRIMARY KEY (`RSO_ID`, `Event_ID`),
--   FOREIGN KEY (`RSO_ID`) REFERENCES `RSOs`(`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
--   FOREIGN KEY (`Event_ID`) REFERENCES `RSO_Events`(`Event_ID`) ON DELETE CASCADE ON UPDATE CASCADE
-- ) ENGINE = InnoDB;

-- Creates relationship between RSOs and Admins
CREATE TABLE `EventSite`.`RSO_Creates` (
  `RSO_ID` INT NOT NULL,
  `Admin_ID` INT NOT NULL,
  PRIMARY KEY (`RSO_ID`, `Admin_ID`),
  FOREIGN KEY (`RSO_ID`) REFERENCES `RSOs`(`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`Admin_ID`) REFERENCES `Admins`(`ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- Join relationship between Users and RSOs
CREATE TABLE `EventSite`.`Join` (
  `User_ID` INT NOT NULL,
  `RSO_ID` INT NOT NULL,
  PRIMARY KEY (`User_ID`, `RSO_ID`),
  FOREIGN KEY (`User_ID`) REFERENCES `Users`(`UID`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`RSO_ID`) REFERENCES `RSOs`(`ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- MySQL dump 10.13  Distrib 8.0.45, for Win64 (x86_64)
--
-- Host: localhost    Database: reventa
-- ------------------------------------------------------
-- Server version	8.0.43

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `category` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category`
--

LOCK TABLES `category` WRITE;
/*!40000 ALTER TABLE `category` DISABLE KEYS */;
INSERT INTO `category` VALUES (1,'Test'),(2,'Electronics'),(3,'Clothing'),(4,'Furniture'),(5,'Books'),(6,'Sports'),(7,'Toys'),(8,'Vehicles'),(9,'Other');
/*!40000 ALTER TABLE `category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat`
--

DROP TABLE IF EXISTS `chat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat` (
  `id` int NOT NULL AUTO_INCREMENT,
  `listing_id` int DEFAULT NULL,
  `buyer_id` int NOT NULL,
  `seller_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `listing_id` (`listing_id`),
  KEY `buyer_id` (`buyer_id`),
  KEY `seller_id` (`seller_id`),
  CONSTRAINT `chat_ibfk_1` FOREIGN KEY (`listing_id`) REFERENCES `listing` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chat_ibfk_2` FOREIGN KEY (`buyer_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_ibfk_3` FOREIGN KEY (`seller_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat`
--

LOCK TABLES `chat` WRITE;
/*!40000 ALTER TABLE `chat` DISABLE KEYS */;
INSERT INTO `chat` VALUES (1,2,2,1,'2026-03-11 13:40:22'),(2,1,2,1,'2026-03-11 13:41:12'),(5,1,6,1,'2026-03-16 13:44:53'),(6,3,6,1,'2026-03-16 13:52:47'),(7,1,7,1,'2026-04-09 11:53:44'),(8,3,7,1,'2026-04-09 11:58:47'),(9,2,8,1,'2026-04-16 12:04:39'),(10,2,9,1,'2026-04-16 13:45:16'),(11,3,9,1,'2026-04-16 13:51:51'),(12,8,9,7,'2026-04-16 14:02:34');
/*!40000 ALTER TABLE `chat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `condition`
--

DROP TABLE IF EXISTS `condition`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `condition` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `condition`
--

LOCK TABLES `condition` WRITE;
/*!40000 ALTER TABLE `condition` DISABLE KEYS */;
INSERT INTO `condition` VALUES (2,'New'),(3,'Like New'),(4,'Very Good'),(5,'Good'),(6,'Fair'),(7,'Poor');
/*!40000 ALTER TABLE `condition` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `listing`
--

DROP TABLE IF EXISTS `listing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `listing` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `price` varchar(45) DEFAULT NULL,
  `description` text,
  `condition_id` int DEFAULT NULL,
  `seller_id` int DEFAULT NULL,
  `create_date` datetime DEFAULT NULL,
  `is_sold` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `category_id` int DEFAULT NULL,
  `view_count` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `listing_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `listing`
--

LOCK TABLES `listing` WRITE;
/*!40000 ALTER TABLE `listing` DISABLE KEYS */;
INSERT INTO `listing` VALUES (1,'Test','5','Test',3,1,NULL,0,'2026-03-04 14:11:00',3,NULL),(2,'hayden','5','Test',2,1,NULL,0,'2026-03-04 14:11:43',3,NULL),(3,'Test','5','Test',3,1,NULL,0,'2026-03-11 13:41:46',2,NULL),(4,'Test','5','test',2,3,NULL,1,'2026-03-11 13:52:07',1,NULL),(5,'hayden','123','312',2,4,NULL,0,'2026-03-12 12:17:21',3,NULL),(6,'ewae','123','',3,4,NULL,0,'2026-03-12 13:43:13',NULL,NULL),(7,'ewa','0.06','',3,7,NULL,1,'2026-04-09 11:58:59',2,3),(8,'Pipe','5','Test',2,7,NULL,0,'2026-04-09 12:30:15',2,4);
/*!40000 ALTER TABLE `listing` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `listing_category`
--

DROP TABLE IF EXISTS `listing_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `listing_category` (
  `listing_id` int NOT NULL,
  `category_id` int NOT NULL,
  PRIMARY KEY (`listing_id`,`category_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `listing_category_ibfk_1` FOREIGN KEY (`listing_id`) REFERENCES `listing` (`id`) ON DELETE CASCADE,
  CONSTRAINT `listing_category_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `listing_category`
--

LOCK TABLES `listing_category` WRITE;
/*!40000 ALTER TABLE `listing_category` DISABLE KEYS */;
/*!40000 ALTER TABLE `listing_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `listing_like`
--

DROP TABLE IF EXISTS `listing_like`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `listing_like` (
  `user_id` int NOT NULL,
  `listing_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`listing_id`),
  KEY `listing_id` (`listing_id`),
  CONSTRAINT `listing_like_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `listing_like_ibfk_2` FOREIGN KEY (`listing_id`) REFERENCES `listing` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `listing_like`
--

LOCK TABLES `listing_like` WRITE;
/*!40000 ALTER TABLE `listing_like` DISABLE KEYS */;
INSERT INTO `listing_like` VALUES (6,1,'2026-03-17 12:13:47'),(6,2,'2026-03-17 12:13:54'),(6,3,'2026-03-17 12:13:57'),(7,8,'2026-04-09 13:57:11');
/*!40000 ALTER TABLE `listing_like` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `listing_photo`
--

DROP TABLE IF EXISTS `listing_photo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `listing_photo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `listing_id` int NOT NULL,
  `photo_url` varchar(500) NOT NULL,
  `sort_order` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `listing_id` (`listing_id`),
  CONSTRAINT `listing_photo_ibfk_1` FOREIGN KEY (`listing_id`) REFERENCES `listing` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `listing_photo`
--

LOCK TABLES `listing_photo` WRITE;
/*!40000 ALTER TABLE `listing_photo` DISABLE KEYS */;
INSERT INTO `listing_photo` VALUES (1,2,'/uploads/listings/listing_2_69a883ef4859c7.94718400.png',0,'2026-03-04 14:11:43'),(2,3,'/uploads/listings/listing_3_69b1a95a7e3618.30695273.png',0,'2026-03-11 13:41:46'),(3,4,'/Reventa/uploads/listings/listing_4_69b1abc7706808.13246320.png',0,'2026-03-11 13:52:07'),(4,5,'/Reventa/uploads/listings/listing_5_69b2e711c11907.37696150.png',0,'2026-03-12 12:17:21'),(5,5,'/Reventa/uploads/listings/listing_5_69b2e711c210a5.49273576.png',1,'2026-03-12 12:17:21'),(6,5,'/Reventa/uploads/listings/listing_5_69b2e711c2e010.02761825.jpg',2,'2026-03-12 12:17:21'),(7,5,'/Reventa/uploads/listings/listing_5_69b2e711c3cb54.38967527.jpg',3,'2026-03-12 12:17:21'),(8,6,'/Reventa/uploads/listings/listing_6_69b2fb31abbf76.27024397.jpg',0,'2026-03-12 13:43:13');
/*!40000 ALTER TABLE `listing_photo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `chat_id` int NOT NULL,
  `sender_id` int NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `chat_id` (`chat_id`),
  KEY `sender_id` (`sender_id`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`chat_id`) REFERENCES `chat` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` VALUES (1,1,2,'Hi! I\'d like to buy this. Is it still available?','2026-03-11 13:40:42'),(2,1,1,'t','2026-03-11 13:40:48'),(3,2,2,'Hi! I\'d like to buy this. Is it still available?','2026-03-11 13:41:12'),(4,2,1,'Test','2026-03-11 13:41:22'),(7,5,6,'ewaea','2026-03-16 13:44:53'),(8,5,6,'ewaea','2026-03-16 13:52:39'),(9,6,6,'Hi! I\'d like to buy this. Is it still available?','2026-03-16 13:52:47'),(10,5,6,'aaaaaaaaaaaaaaaa','2026-03-17 13:31:41'),(11,5,6,'a','2026-03-17 13:31:43'),(12,5,6,'a','2026-03-17 13:31:43'),(13,5,6,'a','2026-03-17 13:31:44'),(14,5,6,'a','2026-03-17 13:31:45'),(15,5,6,'a','2026-03-17 13:31:45'),(16,5,6,'a','2026-03-17 13:31:46'),(17,5,6,'a','2026-03-17 13:31:47'),(18,5,6,'a','2026-03-17 13:31:47'),(19,5,6,'a','2026-03-17 13:31:48'),(20,5,6,'a','2026-03-17 13:31:49'),(21,5,6,'a','2026-03-17 13:31:50'),(22,5,6,'a','2026-03-17 13:31:51'),(23,5,6,'a','2026-03-17 13:31:52'),(24,5,6,'a','2026-03-17 13:31:53'),(25,5,6,'a','2026-03-17 13:31:59'),(26,7,7,'tt','2026-04-09 11:53:48'),(27,9,8,'yo','2026-04-16 12:04:45');
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seller_ratings`
--

DROP TABLE IF EXISTS `seller_ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `seller_ratings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `seller_id` int unsigned NOT NULL,
  `rater_id` int unsigned NOT NULL,
  `stars` tinyint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_seller_rater` (`seller_id`,`rater_id`),
  KEY `idx_seller_id` (`seller_id`),
  KEY `idx_rater_id` (`rater_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seller_ratings`
--

LOCK TABLES `seller_ratings` WRITE;
/*!40000 ALTER TABLE `seller_ratings` DISABLE KEYS */;
/*!40000 ALTER TABLE `seller_ratings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `state`
--

DROP TABLE IF EXISTS `state`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `state` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `state`
--

LOCK TABLES `state` WRITE;
/*!40000 ALTER TABLE `state` DISABLE KEYS */;
INSERT INTO `state` VALUES (2,'Alabama'),(3,'Alaska'),(4,'Arizona'),(5,'Arkansas'),(6,'California'),(7,'Colorado'),(8,'Connecticut'),(9,'Delaware'),(10,'Florida'),(11,'Georgia'),(12,'Hawaii'),(13,'Idaho'),(14,'Illinois'),(15,'Indiana'),(16,'Iowa'),(17,'Kansas'),(18,'Kentucky'),(19,'Louisiana'),(20,'Maine'),(21,'Maryland'),(22,'Massachusetts'),(23,'Michigan'),(24,'Minnesota'),(25,'Mississippi'),(26,'Missouri'),(27,'Montana'),(28,'Nebraska'),(29,'Nevada'),(30,'New Hampshire'),(31,'New Jersey'),(32,'New Mexico'),(33,'New York'),(34,'North Carolina'),(35,'North Dakota'),(36,'Ohio'),(37,'Oklahoma'),(38,'Oregon'),(39,'Pennsylvania'),(40,'Rhode Island'),(41,'South Carolina'),(42,'South Dakota'),(43,'Tennessee'),(44,'Texas'),(45,'Utah'),(46,'Vermont'),(47,'Virginia'),(48,'Washington'),(49,'West Virginia'),(50,'Wisconsin'),(51,'Wyoming');
/*!40000 ALTER TABLE `state` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(45) DEFAULT NULL,
  `phone_number` varchar(10) DEFAULT NULL,
  `birthday` datetime DEFAULT NULL,
  `username` varchar(15) DEFAULT NULL,
  `full_name` varchar(50) DEFAULT NULL,
  `address` varchar(45) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `state_id` int DEFAULT NULL,
  `country_id` int DEFAULT NULL,
  `profile_picture` varchar(500) DEFAULT NULL,
  `bio` varchar(300) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,'26burchfieldh@mydacc.org','6146146144','2026-03-06 00:00:00','Hayden','Hayden Burchfield','','$2y$10$4a1nfauUiHYxkQoOwPJgiuvG17MsyE7c9CKspQgHAnHWnXQXTORMC',0,NULL,'/uploads/avatars/avatar_1_69b19ee9acc5f.png','Test',NULL),(2,'bob@bob.com','0000000000','2025-04-15 00:00:00','Bob','hayden B','132 road','$2y$10$mLQ2nP1vAeqDQP7o7.siJOs72CCYBPvDeuJ4IR5F7a15RHH.eIxNu',0,NULL,NULL,NULL,'Male'),(3,'test@test.com','6146146144','2026-03-19 00:00:00','test','Test','test','$2y$10$Z4lQyXw4c6PDDhb/mRvDr.TzIs3Srk5O5TefWvkrRiqr77/rDdBWe',0,NULL,'/Reventa/uploads/avatars/avatar_3_1773251859.png','','Male'),(5,'test@test.com',NULL,NULL,NULL,NULL,NULL,'$2y$10$ow36GfUWkaAjbTpEbZtRZupc7CmORdrdDwK005iaVbADK0ppu.lTy',NULL,NULL,NULL,NULL,NULL),(6,'26burchfieldh@mydacc.orgee','6146146144','2026-03-11 00:00:00','Wowwwwwwww','Hayden Burchfield','test 1234','$2y$10$tbNOuXTZjrmlc6wtBAzka..GAyVIm6aViW9lhr.nbpP4GREad1z86',2,NULL,NULL,NULL,'Male'),(7,'26burchfieldh@mydacc.orgewaewaeaw','6146146144','2026-04-15 00:00:00','weaeaeaw','Hayden Burchfield','test 1234','$2y$10$PQLJYlP2PDK7x3g9v6aNT.POJ/QOsdsG87XjzJXkDKEWtK9Ntnw/a',17,NULL,'uploads/avatars/avatar_7_69d7d36742bc4.png','','Other'),(8,'bob@bob.comeeee','0000000000','2026-04-21 00:00:00','Bobeeeeeeeee','hayden B','132 road','$2y$10$2yBxRZgHRp2GI5QyR04J4eEspsvVZNjL1SnxZcReDJgV2Db23vAXe',5,NULL,NULL,NULL,'Male'),(9,'26burchfieldh@mydacc.orgeeee','6146146144','2026-04-13 00:00:00','26burc','Hayden Burchfield','test 1234','$2y$10$75auO7VSNbjdakHiyF/exuGx0Gpf6Bg9yiRsE.9OyJCfU9gFkgcQi',18,NULL,NULL,NULL,'Female');
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-20 12:05:25

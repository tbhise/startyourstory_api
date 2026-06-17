CREATE DATABASE  IF NOT EXISTS `startyourstory` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `startyourstory`;
-- MySQL dump 10.13  Distrib 8.0.41, for Win64 (x86_64)
--
-- Host: 187.127.133.60    Database: startyourstory
-- ------------------------------------------------------
-- Server version	8.0.46-0ubuntu0.24.04.2

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
-- Table structure for table `admin_activity_logs`
--

DROP TABLE IF EXISTS `admin_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_activity_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` bigint unsigned DEFAULT NULL COMMENT 'admin_users.id — no FK by design',
  `admin_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action_type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g. firm_approved, payment_approved',
  `entity_type` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g. firm, premium_request, blog',
  `entity_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_aal_admin` (`admin_id`),
  KEY `idx_aal_action` (`action_type`),
  KEY `idx_aal_entity_type` (`entity_type`),
  KEY `idx_aal_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_activity_logs`
--

LOCK TABLES `admin_activity_logs` WRITE;
/*!40000 ALTER TABLE `admin_activity_logs` DISABLE KEYS */;
INSERT INTO `admin_activity_logs` VALUES (1,2,'TusharB','student_deleted','student','13','Deleted student account for Tushar Bhise (tusharbhise908@gmail.com). Reason: Test Account','106.215.178.46','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-17 23:32:35'),(2,2,'TusharB','firm_approved','firm','66','Approved firm registration for test.','106.215.178.46','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-17 23:45:45'),(3,2,'TusharB','blog_created','blog','11','Created blog \'Stipend vs Exposure: What Matters More in Articleship?\'.','106.215.178.46','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-17 23:58:21'),(4,2,'TusharB','blog_updated','blog','11','Updated blog #11.','106.215.178.46','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-17 23:58:51');
/*!40000 ALTER TABLE `admin_activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_fcm_tokens`
--

DROP TABLE IF EXISTS `admin_fcm_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_fcm_tokens` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `admin_user_id` bigint NOT NULL,
  `token` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'user-agent / platform label',
  `last_active_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_fcm_token` (`token`),
  KEY `idx_admin_fcm_admin` (`admin_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_fcm_tokens`
--

LOCK TABLES `admin_fcm_tokens` WRITE;
/*!40000 ALTER TABLE `admin_fcm_tokens` DISABLE KEYS */;
INSERT INTO `admin_fcm_tokens` VALUES (1,2,'fMvqwnzxXNSDLnmpgj0MhT:APA91bESh0zeyhyLkVEnfUFjiPpW5364lqc6bwkrwhsKd6wzX-GpgzMzt-MIt9h5gFX_nI_ia-Sfpydz7Fb8okBGyRk99Y4KGyjLpiVeV3Fsq1_889pgPew','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-17 23:39:24','2026-06-17 23:29:03','2026-06-17 23:39:24'),(2,2,'ebuTrhmLywcDm7T8joMUV0:APA91bGtyjjufIVE5jbXNOvdJa2xNMof6Ixizm5cVtniqNqm8CwYQMQ8G7gt0HAuTF7idQ8WVTXq5AquAR025pRjfVxUFqf5NMA8WDMEIcfZYewuikBnXQ0','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-17 23:49:39','2026-06-17 23:42:05','2026-06-17 23:49:39');
/*!40000 ALTER TABLE `admin_fcm_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_notifications`
--

DROP TABLE IF EXISTS `admin_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_notifications` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'firm_verification | payment_verification | creator_payout | contact_submission | system_alert | future',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'relative admin path to act on the notification',
  `metadata` json DEFAULT NULL COMMENT 'structured context (ids, amounts, names…)',
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `admin_notifications_type_index` (`type`),
  KEY `idx_admin_notifications_unread` (`is_read`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_notifications`
--

LOCK TABLES `admin_notifications` WRITE;
/*!40000 ALTER TABLE `admin_notifications` DISABLE KEYS */;
INSERT INTO `admin_notifications` VALUES (1,'firm_verification','New firm verification request','Sancheti Lakade & Associates has completed its profile and is ready for verification review.','/admin/firms','{\"firm_name\": \"Sancheti Lakade & Associates\", \"firm_profile_id\": 2}',1,'2026-06-16 22:08:23','2026-06-16 14:16:13','2026-06-16 22:08:23'),(2,'firm_verification','New firm verification request','C A & Co has completed its profile and is ready for verification review.','/admin/firms','{\"firm_name\": \"C A & Co\", \"firm_profile_id\": 3}',1,'2026-06-16 22:08:23','2026-06-16 17:35:15','2026-06-16 22:08:23'),(3,'firm_verification','New firm verification request','B N S T & Co LLP has completed its profile and is ready for verification review.','/admin/firms','{\"firm_name\": \"B N S T & Co LLP\", \"firm_profile_id\": 4}',1,'2026-06-16 22:08:23','2026-06-16 17:43:35','2026-06-16 22:08:23'),(4,'firm_verification','New firm verification request','Vipin Gujarathi & Co has completed its profile and is ready for verification review.','/admin/firms','{\"firm_name\": \"Vipin Gujarathi & Co\", \"firm_profile_id\": 9}',1,'2026-06-17 23:17:05','2026-06-17 13:31:13','2026-06-17 23:17:05'),(5,'firm_verification','New firm verification request','A R Totala & Co. has completed its profile and is ready for verification review.','/admin/firms','{\"firm_name\": \"A R Totala & Co.\", \"firm_profile_id\": 11}',1,'2026-06-17 23:17:05','2026-06-17 13:36:12','2026-06-17 23:17:05'),(6,'firm_verification','New firm verification request','R S Biyani & Co has completed its profile and is ready for verification review.','/admin/firms','{\"firm_name\": \"R S Biyani & Co\", \"firm_profile_id\": 12}',1,'2026-06-17 23:17:05','2026-06-17 20:34:08','2026-06-17 23:17:05'),(7,'firm_verification','New firm verification request','test has completed its profile and is ready for verification review.','/admin/firms','{\"firm_name\": \"test\", \"firm_profile_id\": 14}',1,'2026-06-17 23:47:40','2026-06-17 23:45:04','2026-06-17 23:47:40');
/*!40000 ALTER TABLE `admin_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_users`
--

DROP TABLE IF EXISTS `admin_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `api_token` varchar(255) DEFAULT NULL,
  `role` varchar(50) DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_users`
--

LOCK TABLES `admin_users` WRITE;
/*!40000 ALTER TABLE `admin_users` DISABLE KEYS */;
INSERT INTO `admin_users` VALUES (2,'TusharB','tusharb@startyourstory.in','$2y$12$yBcGG6INYhrMyl1gCYU75.KvqovAFtJ5DWhr4uy4Kcqe25uUEYWHm','glaVVJ5QdydIAH3cEuCKgqpPYIG0lxZiguEOBV6dxDuNPbK6tlUAJlwsmqxDWQI0TAWZ5RTbfHEHHUlq','super_admin',1,'2026-06-10 01:09:26','2026-06-17 23:41:48'),(3,'Ritesh Chandak','ritesh@startyourstory.in','$2y$12$HPOtmTCG1YKoWBJANaUvGOwcoNCkGUOq.OzUo4cQBvVB4tPHq7kqS','Vq0ZKjiSawrknN8BKzrgbbhNjYTUwbNvrVtk9XBbn2LnNk1JB0dAI8HqbLmG3zYmhYdijAeydG0YV4U5','super_admin',1,'2026-06-10 01:11:50','2026-06-16 14:26:38');
/*!40000 ALTER TABLE `admin_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `application_holds`
--

DROP TABLE IF EXISTS `application_holds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `application_holds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `application_id` bigint unsigned NOT NULL,
  `job_id` bigint unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '49.00',
  `status` enum('held','consumed','released','expired') NOT NULL DEFAULT 'held',
  `held_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'held_at + 10 days; used by auto-expiry job',
  `consumed_at` timestamp NULL DEFAULT NULL,
  `released_at` timestamp NULL DEFAULT NULL,
  `release_reason` varchar(100) DEFAULT NULL COMMENT 'rejected | auto_expired | refund',
  `hold_transaction_id` bigint unsigned DEFAULT NULL COMMENT 'wallet_transactions.id for the hold entry',
  `settle_transaction_id` bigint unsigned DEFAULT NULL COMMENT 'wallet_transactions.id for consume/release/expire',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_application` (`application_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_expires_at` (`expires_at`,`status`) COMMENT 'used by auto-expiry scheduler'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `application_holds`
--

LOCK TABLES `application_holds` WRITE;
/*!40000 ALTER TABLE `application_holds` DISABLE KEYS */;
/*!40000 ALTER TABLE `application_holds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `applications`
--

DROP TABLE IF EXISTS `applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `applications` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `job_id` bigint DEFAULT NULL,
  `student_id` bigint DEFAULT NULL,
  `recruiter_status` varchar(100) DEFAULT 'Applied',
  `status` enum('applied','shortlisted','rejected','interview') DEFAULT 'applied',
  `applied_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `shortlisted_at` timestamp NULL DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `interview_requested_at` timestamp NULL DEFAULT NULL,
  `interview_responded_at` timestamp NULL DEFAULT NULL,
  `reschedule_accepted_at` timestamp NULL DEFAULT NULL,
  `reminder_24h_sent_at` datetime DEFAULT NULL,
  `reminder_1h_sent_at` datetime DEFAULT NULL,
  `digest_notified_at` datetime DEFAULT NULL,
  `selected_at` timestamp NULL DEFAULT NULL,
  `interview_date` datetime DEFAULT NULL,
  `interview_mode` varchar(100) DEFAULT NULL,
  `interview_note` text,
  `student_interview_response` varchar(100) DEFAULT NULL,
  `student_response_note` text,
  `recruiter_notes` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `unlocked_by_firm` tinyint(1) DEFAULT '0',
  `unlocked_at` timestamp NULL DEFAULT NULL,
  `is_visible_to_firm` tinyint(1) DEFAULT '0',
  `is_free_application` tinyint(1) NOT NULL DEFAULT '0',
  `application_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_source` varchar(10) DEFAULT NULL,
  `wallet_hold_id` bigint unsigned DEFAULT NULL,
  `coin_hold_id` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_application_job_student` (`job_id`,`student_id`),
  KEY `idx_wallet_hold` (`wallet_hold_id`),
  KEY `idx_reminder_24h` (`reminder_24h_sent_at`),
  KEY `idx_reminder_1h` (`reminder_1h_sent_at`),
  KEY `idx_digest_notified` (`digest_notified_at`),
  KEY `idx_coin_hold` (`coin_hold_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `applications`
--

LOCK TABLES `applications` WRITE;
/*!40000 ALTER TABLE `applications` DISABLE KEYS */;
/*!40000 ALTER TABLE `applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blog_categories`
--

DROP TABLE IF EXISTS `blog_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_categories` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_bc_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_categories`
--

LOCK TABLES `blog_categories` WRITE;
/*!40000 ALTER TABLE `blog_categories` DISABLE KEYS */;
INSERT INTO `blog_categories` VALUES (1,'Career Tips','career-tips',NULL,'2026-06-11 22:10:19','2026-06-11 22:10:19'),(2,'Interview Preparation','interview-preparation',NULL,'2026-06-13 21:26:33','2026-06-13 21:26:33'),(3,'CA Student Resources','ca-student-resources','CA Student Resources','2026-06-13 21:26:46','2026-06-13 21:26:46'),(4,'Articleship Guidance','articleship-guidance','Articleship Guidance','2026-06-13 21:26:58','2026-06-13 21:26:58'),(5,'Workplace Insights','workplace-insights','Firm Selection & Workplace Insights','2026-06-13 21:28:51','2026-06-13 21:29:06'),(6,'Interview Preparation','interview-preparation-1',NULL,'2026-06-13 21:29:22','2026-06-13 21:29:22');
/*!40000 ALTER TABLE `blog_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blog_tag_map`
--

DROP TABLE IF EXISTS `blog_tag_map`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_tag_map` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `blog_id` bigint NOT NULL,
  `tag_id` bigint NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_btm_blog_tag` (`blog_id`,`tag_id`),
  KEY `idx_btm_tag` (`tag_id`),
  CONSTRAINT `fk_btm_blog` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_btm_tag` FOREIGN KEY (`tag_id`) REFERENCES `blog_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_tag_map`
--

LOCK TABLES `blog_tag_map` WRITE;
/*!40000 ALTER TABLE `blog_tag_map` DISABLE KEYS */;
INSERT INTO `blog_tag_map` VALUES (4,11,1),(5,11,12),(6,11,13);
/*!40000 ALTER TABLE `blog_tag_map` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blog_tags`
--

DROP TABLE IF EXISTS `blog_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_tags` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_bt_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_tags`
--

LOCK TABLES `blog_tags` WRITE;
/*!40000 ALTER TABLE `blog_tags` DISABLE KEYS */;
INSERT INTO `blog_tags` VALUES (1,'Articleship','articleship','2026-06-13 21:30:50','2026-06-13 21:30:50'),(2,'CA Students','ca-students','2026-06-13 21:31:00','2026-06-13 21:31:00'),(3,'CA Inter','ca-inter','2026-06-13 21:31:09','2026-06-13 21:31:09'),(4,'Big 4','big-4','2026-06-13 21:31:17','2026-06-13 21:31:17'),(5,'Mid Size Firms','mid-size-firms','2026-06-13 21:31:25','2026-06-13 21:31:25'),(6,'Articleship Interview','articleship-interview','2026-06-13 21:31:31','2026-06-13 21:31:31'),(7,'Career Growth','career-growth','2026-06-13 21:31:38','2026-06-13 21:31:38'),(8,'CA Training','ca-training','2026-06-13 21:31:46','2026-06-13 21:31:46'),(9,'Audit','audit','2026-06-13 21:31:54','2026-06-13 21:31:54'),(10,'Taxation','taxation','2026-06-13 21:32:00','2026-06-13 21:32:00'),(11,'Industrial Training','industrial-training','2026-06-13 21:32:09','2026-06-13 21:32:09'),(12,'Exposure','exposure','2026-06-13 21:32:14','2026-06-13 21:32:14'),(13,'Stipend','stipend','2026-06-13 21:32:20','2026-06-13 21:32:20'),(14,'Articleship Selection','articleship-selection','2026-06-13 21:32:47','2026-06-13 21:32:47'),(15,'CA Career','ca-career','2026-06-13 21:32:52','2026-06-13 21:32:52');
/*!40000 ALTER TABLE `blog_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blog_topics`
--

DROP TABLE IF EXISTS `blog_topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_topics` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `title` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(350) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` bigint DEFAULT NULL,
  `target_keywords` text COLLATE utf8mb4_unicode_ci,
  `search_intent` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `priority` enum('low','medium','high') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `status` enum('pending','generating','generated','published','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `blog_id` bigint DEFAULT NULL,
  `generation_source` enum('manual','gpt','claude','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `ai_model` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `generated_at` datetime DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_by` bigint DEFAULT NULL COMMENT 'admin_users.id — no FK by design',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_bt_slug` (`slug`),
  KEY `idx_bt_status` (`status`),
  KEY `idx_bt_category` (`category_id`),
  KEY `idx_bt_priority` (`priority`),
  KEY `idx_bt_blog` (`blog_id`),
  KEY `idx_bt_created` (`created_at`),
  CONSTRAINT `fk_bt_blog` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_bt_category` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_topics`
--

LOCK TABLES `blog_topics` WRITE;
/*!40000 ALTER TABLE `blog_topics` DISABLE KEYS */;
INSERT INTO `blog_topics` VALUES (1,'How to choose a right CA firm for Articleship','how-to-choose-a-right-ca-firm-for-articleship',NULL,NULL,NULL,NULL,'high','published',1,'manual',NULL,NULL,NULL,2,'2026-06-11 21:25:05','2026-06-13 21:22:16'),(2,'What CA Firms Look for Before Hiring Articles?','what-ca-firms-look-for-before-hiring-articles',2,NULL,NULL,NULL,'medium','published',3,'manual',NULL,NULL,NULL,2,'2026-06-13 21:34:37','2026-06-13 21:57:07'),(5,'10 Questions Every CA Student Should Ask Before Joining a Firm','10-questions-every-ca-student-should-ask-before-joining-a-firm',5,NULL,NULL,NULL,'medium','published',6,'manual',NULL,NULL,NULL,2,'2026-06-13 21:35:21','2026-06-13 22:02:21'),(6,'Big 4 vs Mid-Size CA Firms: Which Is Better for Articleship?','big-4-vs-mid-size-ca-firms-which-is-better-for-articleship',5,NULL,NULL,NULL,'medium','published',7,'manual',NULL,NULL,NULL,2,'2026-06-13 21:35:33','2026-06-13 22:04:01'),(7,'How to Prepare for a CA Articleship Interview ?','how-to-prepare-for-a-ca-articleship-interview',2,NULL,NULL,NULL,'medium','published',9,'manual',NULL,NULL,NULL,2,'2026-06-13 21:35:58','2026-06-13 22:09:54'),(8,'Common Mistakes CA Students Make While Choosing Articleship','common-mistakes-ca-students-make-while-choosing-articleship',4,NULL,NULL,NULL,'medium','published',10,'manual',NULL,NULL,NULL,2,'2026-06-13 21:36:15','2026-06-13 22:11:45'),(9,'Stipend vs Exposure: What Matters More in Articleship?','stipend-vs-exposure-what-matters-more-in-articleship',1,'how to choose a right ca firm for articleship?',NULL,NULL,'high','published',11,'manual',NULL,NULL,NULL,2,'2026-06-17 23:52:45','2026-06-17 23:58:21');
/*!40000 ALTER TABLE `blog_topics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blogs`
--

DROP TABLE IF EXISTS `blogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blogs` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `title` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(350) COLLATE utf8mb4_unicode_ci NOT NULL,
  `excerpt` text COLLATE utf8mb4_unicode_ci,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `featured_image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'storage-relative path',
  `meta_title` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('draft','published') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `category_id` bigint DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_blogs_slug` (`slug`),
  KEY `idx_blogs_status` (`status`),
  KEY `idx_blogs_category` (`category_id`),
  KEY `idx_blogs_published` (`published_at`),
  CONSTRAINT `fk_blogs_category` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blogs`
--

LOCK TABLES `blogs` WRITE;
/*!40000 ALTER TABLE `blogs` DISABLE KEYS */;
INSERT INTO `blogs` VALUES (1,'How to Choose the Right CA Firm for Articleship: Complete Student Guide','how-to-choose-the-right-ca-firm-for-articleship','Selecting the right CA firm for articleship is one of the most important career decisions for a CA student. From audit and taxation exposure to work culture and mentorship, this guide explains the key factors you should evaluate before choosing a firm that aligns with your professional goals.','<p>Articleship is often considered the backbone of a Chartered Accountant\'s professional journey. While examinations build technical knowledge, articleship provides the practical exposure needed to apply that knowledge in real-world business scenarios. Choosing the right CA firm can significantly influence your learning experience, professional growth, and future career opportunities.</p>\r\n\r\n\r\n\r\n<p>Many students make the mistake of selecting a firm based solely on brand name, stipend, or location. However, the ideal articleship firm should align with your long-term career goals and provide meaningful exposure across different areas of practice. Before accepting an offer, it is important to evaluate factors such as domain exposure, client portfolio, mentorship opportunities, work culture, and learning environment.</p>\r\n\r\n\r\n\r\n<p>A good articleship firm should help you develop technical skills in areas such as audit, taxation, GST, compliance, and financial reporting while also improving your communication, problem-solving, and professional skills. The experience gained during these three years often plays a major role in shaping future job opportunities and career direction.</p>\r\n\r\n\r\n\r\n<p>Students should begin by identifying their career interests. If you wish to build a career in audit and assurance, firms with strong audit portfolios may be suitable. If taxation interests you, consider firms known for direct and indirect tax practice. Similarly, students interested in advisory services or consulting should look for firms that offer exposure in those areas.</p>\r\n\r\n\r\n\r\n<p>Exposure is one of the most important factors when evaluating firms. Try to understand the type of assignments articles are typically involved in. A firm that provides opportunities to work on audits, taxation, compliance, client interactions, and industry-specific assignments often offers a more comprehensive learning experience.</p>\r\n\r\n\r\n\r\n<p>Another important consideration is the firm\'s work culture. Speak with existing or former articles whenever possible. Their experiences can provide valuable insights into mentorship quality, team support, learning opportunities, and overall work environment. A supportive culture often contributes significantly to professional development.</p>\r\n\r\n\r\n\r\n<p>Students should also evaluate the firm\'s client base. Exposure to diverse industries such as manufacturing, services, startups, banking, and technology can broaden practical understanding and improve professional competence. Working with varied clients helps articles develop a better understanding of business operations and industry practices.</p>\r\n\r\n\r\n\r\n<p>Location and commute should not be ignored. While learning opportunities remain the primary factor, a reasonable commute can help maintain a healthy balance between office responsibilities and examination preparation. Managing both effectively is essential during the CA journey.</p>\r\n\r\n\r\n\r\n<p>During interviews, students should not hesitate to ask questions about the nature of assignments, team structure, learning opportunities, and areas of practice. This helps in making a more informed decision and demonstrates genuine interest in the role.</p>\r\n\r\n\r\n\r\n<p>Ultimately, there is no single perfect CA firm for every student. The right choice depends on individual career aspirations, preferred learning areas, and personal circumstances. By carefully evaluating available opportunities and prioritizing learning over short-term considerations, students can make a decision that positively impacts their professional future.</p>\r\n\r\n\r\n\r\n<p>Choosing the right CA firm for articleship is an investment in your career. Taking the time to research, evaluate options, and align your decision with long-term goals can help you maximize the value of your articleship experience and build a strong foundation for a successful Chartered Accountancy career.</p>','blog-images/featured/JAzSjWKQM8YA66Gp8aJd4Mqx1mQo6NIfVfM5XX2Z.png','How to Choose the Right CA Firm for Articleship: Complete Student Guide','Confused about selecting a CA firm for articleship? Learn how to evaluate firms based on exposure, work culture, learning opportunities, and long-term career goals.','draft',1,'2026-06-11 22:03:28','2026-06-11 22:03:28','2026-06-16 01:46:06'),(3,'What CA Firms Look for Before Hiring Articles?','what-ca-firms-look-for-before-hiring-articles','Many CA students focus only on marks and technical knowledge when applying for articleship, but CA firms evaluate much more than academic performance. Learn what firms actually look for before hiring articles and how you can improve your chances of getting selected.','<p>Every year, thousands of CA students apply for articleship opportunities across India. While many candidates believe that academic marks alone determine selection, the reality is very different. Most CA firms evaluate a combination of technical knowledge, communication skills, professionalism, attitude, and learning potential before making hiring decisions.</p>\n\n<p>Understanding what firms actually look for can help students prepare more effectively and improve their chances of securing quality articleship opportunities.</p>\n\n<h2>Academic Performance Matters, But It Is Not Everything</h2>\n\n<p>Academic results are usually the first thing recruiters notice on a resume. Good marks demonstrate discipline, consistency, and commitment toward studies.</p>\n\n<p>However, firms rarely make hiring decisions based solely on marks. Many successful articles have average academic scores but possess strong communication skills, confidence, and a willingness to learn.</p>\n\n<p>Students should focus on presenting a balanced profile rather than relying entirely on examination results.</p>\n\n<h2>Communication Skills Create a Strong First Impression</h2>\n\n<p>One of the most important qualities firms evaluate is communication.</p>\n\n<p>Articles regularly interact with:</p>\n\n<ul>\n<li>Clients</li>\n<li>Managers</li>\n<li>Partners</li>\n<li>Team members</li>\n<li>Government departments</li>\n</ul>\n\n<p>Students who can express themselves clearly often stand out during interviews.</p>\n\n<p>Good communication does not mean speaking perfect English. It means being able to explain thoughts confidently, listen carefully, and communicate professionally.</p>\n\n<h2>Learning Attitude Is Highly Valued</h2>\n\n<p>Firms understand that students join articleship to learn. Recruiters do not expect candidates to know everything.</p>\n\n<p>Instead, they often assess whether the student is:</p>\n\n<ul>\n<li>Curious</li>\n<li>Open to feedback</li>\n<li>Willing to learn</li>\n<li>Adaptable</li>\n<li>Professional</li>\n</ul>\n\n<p>A candidate who demonstrates enthusiasm for learning is often preferred over someone who appears overconfident or uninterested.</p>\n\n<h2>Profile Completeness Reflects Seriousness</h2>\n\n<p>Before scheduling interviews, many firms review candidate profiles and resumes carefully.</p>\n\n<p>Students who provide complete information usually create a stronger impression.</p>\n\n<p>A good profile should include:</p>\n\n<ul>\n<li>Educational qualifications</li>\n<li>CA progress details</li>\n<li>Skills</li>\n<li>Certifications</li>\n<li>Career interests</li>\n<li>Resume</li>\n</ul>\n\n<p>An incomplete profile often signals a lack of seriousness toward career opportunities.</p>\n\n<h2>Basic Technical Knowledge Is Expected</h2>\n\n<p>Students are not expected to possess advanced practical experience before joining articleship.</p>\n\n<p>However, firms generally expect clarity in basic concepts such as:</p>\n\n<ul>\n<li>Accounting fundamentals</li>\n<li>Journal entries</li>\n<li>Financial statements</li>\n<li>GST basics</li>\n<li>Income tax fundamentals</li>\n<li>Audit concepts</li>\n</ul>\n\n<p>Students should revise important concepts before attending interviews.</p>\n\n<h2>Professionalism Matters More Than Students Realize</h2>\n\n<p>Professional behavior begins long before the interview itself.</p>\n\n<p>Recruiters often observe:</p>\n\n<ul>\n<li>Email communication</li>\n<li>Resume presentation</li>\n<li>Punctuality</li>\n<li>Dress code</li>\n<li>Interview etiquette</li>\n<li>Responsiveness</li>\n</ul>\n\n<p>Small details can significantly influence hiring decisions.</p>\n\n<h2>Confidence Without Arrogance</h2>\n\n<p>Many students either become too nervous or try to appear overly confident during interviews.</p>\n\n<p>Firms generally prefer candidates who are:</p>\n\n<ul>\n<li>Confident</li>\n<li>Respectful</li>\n<li>Honest</li>\n<li>Professional</li>\n</ul>\n\n<p>If you do not know an answer, admitting it honestly is often better than guessing incorrectly.</p>\n\n<h2>Long-Term Commitment Is Important</h2>\n\n<p>Training an article requires time and effort from the firm.</p>\n\n<p>As a result, firms prefer students who demonstrate commitment and stability.</p>\n\n<p>Recruiters may ask questions to understand:</p>\n\n<ul>\n<li>Career goals</li>\n<li>Reasons for applying</li>\n<li>Interest in specific domains</li>\n<li>Long-term aspirations</li>\n</ul>\n\n<p>Students who show genuine interest in learning and contributing usually make a stronger impression.</p>\n\n<h2>Resume Quality Can Influence Interview Opportunities</h2>\n\n<p>Your resume is often the first interaction a firm has with you.</p>\n\n<p>A strong resume should be:</p>\n\n<ul>\n<li>Professional</li>\n<li>Well-structured</li>\n<li>Error-free</li>\n<li>Easy to read</li>\n<li>Focused on relevant achievements</li>\n</ul>\n\n<p>Even highly capable students may miss opportunities if their resume fails to present their strengths effectively.</p>\n\n<h2>What Makes a Candidate Stand Out?</h2>\n\n<p>The most successful candidates usually combine multiple strengths.</p>\n\n<ul>\n<li>Strong communication skills</li>\n<li>Positive attitude</li>\n<li>Good academic foundation</li>\n<li>Professional behavior</li>\n<li>Learning mindset</li>\n<li>Well-prepared profile</li>\n<li>Clear career goals</li>\n</ul>\n\n<p>Firms are ultimately looking for students who can grow into competent professionals over the course of their articleship.</p>\n\n<h2>Final Thoughts</h2>\n\n<p>Articleship interviews are not designed to identify perfect candidates. They are designed to identify students who have the potential to learn, contribute, and grow.</p>\n\n<p>While academic performance remains important, firms often place equal or greater emphasis on communication, attitude, professionalism, and willingness to learn.</p>\n\n<p>Students who focus on developing these qualities alongside technical knowledge significantly improve their chances of securing opportunities at quality CA firms.</p>\n\n<p><b>The best candidates are not necessarily those who know the mostâ€”they are those who demonstrate the greatest potential to learn and grow.</b></p>',NULL,'What CA Firms Look for Before Hiring Articles | Articleship Selection Guide','Discover what CA firms evaluate before hiring articleship candidates, including communication skills, attitude, learning ability, professionalism, and technical knowledge.','published',2,'2026-06-13 21:57:07','2026-06-13 21:57:07','2026-06-13 21:57:07'),(6,'10 Questions Every CA Student Should Ask Before Joining a Firm','10-questions-every-ca-student-should-ask-before-joining-a-firm','Choosing the right CA firm is one of the most important decisions a CA student will make during articleship. Unfortunately, many students focus only on stipend or firm name and fail to ask questions that reveal the true learning opportunities available. Here are 10 essential questions every CA student should ask before accepting an articleship offer.','<p>Selecting a CA firm for articleship is not just about securing a training position. It is about choosing an environment where you will spend two important years developing technical knowledge, professional skills, and practical experience.</p>\r\n\r\n<p>Many students make the mistake of accepting the first offer they receive or choosing a firm solely based on stipend or brand value. While these factors are important, they rarely determine the quality of learning you will receive during articleship.</p>\r\n\r\n<p>Before joining any CA firm, students should ask the right questions to evaluate whether the opportunity aligns with their career goals.</p>\r\n\r\n<h2>Why Asking Questions Matters</h2>\r\n\r\n<p>Articleship is a significant investment of time and effort. The right firm can provide excellent exposure, mentorship, and growth opportunities, while the wrong choice can limit learning and professional development.</p>\r\n\r\n<p>Asking thoughtful questions demonstrates maturity and genuine interest in your career. It also helps you understand what to expect from the firm before making a commitment.</p>\r\n\r\n<h2>1. What Domains Will I Be Working In?</h2>\r\n\r\n<p>This should be one of the first questions you ask.</p>\r\n\r\n<p>Different firms specialize in different service areas such as:</p>\r\n\r\n<ul>\r\n<li>Statutory Audit</li>\r\n<li>Internal Audit</li>\r\n<li>Tax Audit</li>\r\n<li>Direct Taxation</li>\r\n<li>GST Compliance</li>\r\n<li>ROC Compliance</li>\r\n<li>Advisory Services</li>\r\n</ul>\r\n\r\n<p>Understanding domain exposure helps you evaluate whether the role supports your long-term career goals.</p>\r\n\r\n<h2>2. How Much Audit Exposure Will I Receive?</h2>\r\n\r\n<p>Audit assignments provide valuable learning opportunities related to financial statements, internal controls, risk assessment, and business processes.</p>\r\n\r\n<p>Ask whether articles are actively involved in audits and how responsibilities are distributed within the team.</p>\r\n\r\n<h2>3. Will I Get Exposure to Taxation Work?</h2>\r\n\r\n<p>Taxation remains one of the most important areas for Chartered Accountants.</p>\r\n\r\n<p>Students should understand whether they will gain practical experience in:</p>\r\n\r\n<ul>\r\n<li>Income Tax Returns</li>\r\n<li>Tax Audits</li>\r\n<li>TDS Compliance</li>\r\n<li>GST Compliance</li>\r\n<li>Tax Assessments</li>\r\n</ul>\r\n\r\n<p>Taxation exposure significantly enhances professional competence.</p>\r\n\r\n<h2>4. Will I Have Opportunities to Interact with Clients?</h2>\r\n\r\n<p>Technical knowledge is important, but communication and relationship management skills are equally valuable.</p>\r\n\r\n<p>Client interaction helps students:</p>\r\n\r\n<ul>\r\n<li>Develop confidence</li>\r\n<li>Improve communication skills</li>\r\n<li>Understand business challenges</li>\r\n<li>Build professional maturity</li>\r\n</ul>\r\n\r\n<p>Students should seek opportunities that involve direct client exposure whenever possible.</p>\r\n\r\n<h2>5. Which Industries Does the Firm Serve?</h2>\r\n\r\n<p>Industry exposure can significantly broaden your understanding of business operations.</p>\r\n\r\n<p>Ask about the firm\'s client portfolio and industries served, such as:</p>\r\n\r\n<ul>\r\n<li>Manufacturing</li>\r\n<li>Information Technology</li>\r\n<li>Healthcare</li>\r\n<li>Retail</li>\r\n<li>Financial Services</li>\r\n<li>E-commerce</li>\r\n</ul>\r\n\r\n<p>Exposure to multiple industries provides a broader perspective and improves adaptability.</p>\r\n\r\n<h2>6. Is There a Structured Training or Mentorship Process?</h2>\r\n\r\n<p>Not all firms have formal training systems.</p>\r\n\r\n<p>Ask:</p>\r\n\r\n<ul>\r\n<li>Who will guide articles?</li>\r\n<li>How are assignments allocated?</li>\r\n<li>Are training sessions conducted?</li>\r\n<li>How is performance reviewed?</li>\r\n</ul>\r\n\r\n<p>Strong mentorship often accelerates learning and professional growth.</p>\r\n\r\n<h2>7. What Level of Responsibility Will I Receive?</h2>\r\n\r\n<p>The best learning often comes from responsibility.</p>\r\n\r\n<p>Students should understand whether they will:</p>\r\n\r\n<ul>\r\n<li>Handle assignments independently</li>\r\n<li>Prepare reports</li>\r\n<li>Coordinate with clients</li>\r\n<li>Participate in field work</li>\r\n<li>Work directly with seniors and partners</li>\r\n</ul>\r\n\r\n<p>Greater responsibility usually results in stronger practical learning.</p>\r\n\r\n<h2>8. What Skills Do Successful Articles Typically Develop Here?</h2>\r\n\r\n<p>This question often reveals valuable insights about the firm\'s culture and learning environment.</p>\r\n\r\n<p>The response can help you understand whether the firm focuses on:</p>\r\n\r\n<ul>\r\n<li>Technical development</li>\r\n<li>Leadership skills</li>\r\n<li>Client management</li>\r\n<li>Problem-solving abilities</li>\r\n<li>Industry specialization</li>\r\n</ul>\r\n\r\n<p>The answer can provide a realistic picture of what your growth journey may look like.</p>\r\n\r\n<h2>9. What Are the Expectations from Articles?</h2>\r\n\r\n<p>Every firm has different expectations.</p>\r\n\r\n<p>Understanding these expectations helps avoid future misunderstandings.</p>\r\n\r\n<p>Ask about:</p>\r\n\r\n<ul>\r\n<li>Working hours</li>\r\n<li>Travel requirements</li>\r\n<li>Assignment deadlines</li>\r\n<li>Reporting structure</li>\r\n<li>Professional conduct expectations</li>\r\n</ul>\r\n\r\n<p>Clarity at the beginning creates a smoother articleship experience.</p>\r\n\r\n<h2>10. Why Do Existing Articles Choose to Stay Here?</h2>\r\n\r\n<p>This is one of the most powerful questions students can ask.</p>\r\n\r\n<p>The answer often reveals the firm\'s actual strengths.</p>\r\n\r\n<p>If possible, speak directly with existing articles and ask about:</p>\r\n\r\n<ul>\r\n<li>Learning opportunities</li>\r\n<li>Work culture</li>\r\n<li>Mentorship quality</li>\r\n<li>Professional growth</li>\r\n<li>Overall experience</li>\r\n</ul>\r\n\r\n<p>Current articles can often provide insights that are not visible during interviews.</p>\r\n\r\n<h2>Questions Students Often Forget to Ask</h2>\r\n\r\n<p>In addition to the ten questions above, students should also understand:</p>\r\n\r\n<ul>\r\n<li>How performance feedback is provided</li>\r\n<li>Whether technology tools are used</li>\r\n<li>Exposure to advanced assignments</li>\r\n<li>Availability of support during exams</li>\r\n<li>Long-term learning opportunities</li>\r\n</ul>\r\n\r\n<p>These details can significantly influence the overall quality of articleship.</p>\r\n\r\n<h2>Final Thoughts</h2>\r\n\r\n<p>Choosing a CA firm is one of the most important career decisions a student will make during articleship. The right firm can provide technical expertise, professional confidence, industry exposure, and mentorship that continue to benefit you long after qualification.</p>\r\n\r\n<p>Before accepting an offer, take time to ask meaningful questions and evaluate the opportunity carefully.</p>\r\n\r\n<p><b>The goal is not simply to join a firm. The goal is to join a firm that helps you become a better Chartered Accountant.</b></p>\r\n\r\n<p>The answers to these questions can help you make a more informed decision and maximize the value of your articleship experience.</p>','blog-images/featured/vRHbfq3Ltc9bZX4OZdtB5tCpeIQhmOy4l7xAfMMv.png','10 Questions Every CA Student Should Ask Before Joining a CA Firm','Discover the 10 most important questions CA students should ask before joining a firm for articleship. Learn how to evaluate exposure, mentorship, learning opportunities, and career growth potential.','published',5,'2026-06-13 22:02:21','2026-06-13 22:02:21','2026-06-16 01:42:39'),(7,'Big 4 vs Mid-Size CA Firms: Which Is Better for Articleship?','big-4-vs-mid-size-ca-firms-which-is-better-for-articleship','One of the biggest decisions CA students face before starting articleship is whether to join a Big 4 firm or a mid-sized CA firm. While Big 4 firms offer brand value and structured processes, mid-sized firms often provide broader exposure and greater responsibility. This guide explores the advantages, limitations, and ideal candidate profiles for both options to help students make an informed decision','<p>One of the most common questions CA students ask before beginning articleship is whether they should join a <b>Big 4 firm</b> or a <b>mid-sized CA firm</b>. The debate has existed for years, and there is no universal answer because both options offer unique advantages and challenges.</p>\r\n\r\n<p>Many students automatically assume that securing a Big 4 articleship is the ultimate goal. While Big 4 firms certainly provide valuable opportunities, mid-sized firms can often offer learning experiences that are equally beneficial, depending on your career aspirations.</p>\r\n\r\n<p>The real question is not which option is better overall. The real question is <b>which option is better for you.</b></p>\r\n\r\n<h2>Understanding the Big 4 Firms</h2>\r\n\r\n<p>The term Big 4 generally refers to:</p>\r\n\r\n<ul>\r\n<li>Deloitte</li>\r\n<li>EY</li>\r\n<li>KPMG</li>\r\n<li>PwC</li>\r\n</ul>\r\n\r\n<p>These firms operate globally and serve some of the world\'s largest companies. They are known for structured processes, specialized teams, extensive resources, and strong brand recognition.</p>\r\n\r\n<p>Many CA students are attracted to the Big 4 because of the prestige associated with these organizations.</p>\r\n\r\n<h2>What Is Considered a Mid-Sized CA Firm?</h2>\r\n\r\n<p>Mid-sized firms vary significantly in size and specialization. Some may have multiple offices and hundreds of employees, while others operate with smaller teams and diverse service offerings.</p>\r\n\r\n<p>Unlike Big 4 firms, mid-sized firms often provide services across multiple domains such as:</p>\r\n\r\n<ul>\r\n<li>Statutory Audit</li>\r\n<li>Tax Audit</li>\r\n<li>Direct Taxation</li>\r\n<li>GST Compliance</li>\r\n<li>Internal Audit</li>\r\n<li>ROC Compliance</li>\r\n<li>Advisory Services</li>\r\n</ul>\r\n\r\n<p>This often creates broader learning opportunities for articles.</p>\r\n\r\n<h2>Exposure: Broad vs Specialized</h2>\r\n\r\n<p>This is perhaps the most important difference between the two options.</p>\r\n\r\n<p>In many Big 4 firms, articles work within highly specialized teams. For example, a student may spend most of their articleship working exclusively in statutory audit or internal audit.</p>\r\n\r\n<p>While this creates deep expertise in a particular area, exposure may be limited to that specific function.</p>\r\n\r\n<p>In contrast, mid-sized firms frequently allow students to work across multiple domains. An article may gain exposure to audit, taxation, compliance, and client advisory work within the same training period.</p>\r\n\r\n<p><b>Big 4 often provides depth. Mid-sized firms often provide breadth.</b></p>\r\n\r\n<h2>Client Exposure and Interaction</h2>\r\n\r\n<p>Client interaction is an important part of professional development.</p>\r\n\r\n<p>In large organizations, communication often follows multiple reporting levels. Students may primarily interact with managers and senior team members.</p>\r\n\r\n<p>Mid-sized firms often provide direct exposure to:</p>\r\n\r\n<ul>\r\n<li>Business owners</li>\r\n<li>CFOs</li>\r\n<li>Finance managers</li>\r\n<li>Partners</li>\r\n<li>Entrepreneurs</li>\r\n</ul>\r\n\r\n<p>This direct interaction can significantly improve communication skills and business understanding.</p>\r\n\r\n<h2>Responsibility and Ownership</h2>\r\n\r\n<p>Articleship is most valuable when students receive meaningful responsibility.</p>\r\n\r\n<p>In Big 4 firms, students typically work on larger engagements with defined roles and responsibilities.</p>\r\n\r\n<p>In mid-sized firms, articles may be entrusted with greater ownership at an earlier stage, including:</p>\r\n\r\n<ul>\r\n<li>Handling assignments independently</li>\r\n<li>Preparing reports</li>\r\n<li>Communicating with clients</li>\r\n<li>Managing timelines</li>\r\n<li>Coordinating fieldwork</li>\r\n</ul>\r\n\r\n<p>This responsibility often accelerates learning and confidence.</p>\r\n\r\n<h2>Learning Environment and Training</h2>\r\n\r\n<p>One major advantage of Big 4 firms is their structured training systems.</p>\r\n\r\n<p>Students often benefit from:</p>\r\n\r\n<ul>\r\n<li>Formal training programs</li>\r\n<li>Learning portals</li>\r\n<li>Standardized methodologies</li>\r\n<li>Professional development initiatives</li>\r\n</ul>\r\n\r\n<p>Mid-sized firms may not always offer the same level of structured training, but they often compensate through practical exposure and hands-on learning.</p>\r\n\r\n<h2>Work Culture and Team Size</h2>\r\n\r\n<p>Work culture can vary significantly between organizations.</p>\r\n\r\n<p>Large firms typically operate with extensive teams and defined hierarchies.</p>\r\n\r\n<p>Mid-sized firms often have smaller teams where articles work closely with managers, qualified professionals, and partners.</p>\r\n\r\n<p>This can create stronger mentorship opportunities and more personalized guidance.</p>\r\n\r\n<h2>Career Opportunities After Qualification</h2>\r\n\r\n<p>Both Big 4 and mid-sized firms can open excellent career opportunities after qualification.</p>\r\n\r\n<p>Big 4 experience is often highly valued by:</p>\r\n\r\n<ul>\r\n<li>Multinational corporations</li>\r\n<li>Consulting firms</li>\r\n<li>Global organizations</li>\r\n<li>Large finance teams</li>\r\n</ul>\r\n\r\n<p>Mid-sized firm experience can be equally valuable because students often develop broader practical skills and exposure across multiple domains.</p>\r\n\r\n<p>Recruiters increasingly focus on actual experience rather than firm names alone.</p>\r\n\r\n<h2>Common Myths About Big 4 Articleship</h2>\r\n\r\n<p>Many students believe:</p>\r\n\r\n<ul>\r\n<li>Big 4 guarantees career success.</li>\r\n<li>Mid-sized firms offer limited learning.</li>\r\n<li>Only Big 4 experience matters during interviews.</li>\r\n</ul>\r\n\r\n<p>These assumptions are often inaccurate.</p>\r\n\r\n<p>Successful Chartered Accountants come from both Big 4 and mid-sized firms. Long-term success depends more on learning, skill development, and professional growth than on firm branding alone.</p>\r\n\r\n<h2>Who Should Consider a Big 4 Firm?</h2>\r\n\r\n<p>A Big 4 articleship may be ideal for students who:</p>\r\n\r\n<ul>\r\n<li>Prefer structured learning environments</li>\r\n<li>Want exposure to large corporate clients</li>\r\n<li>Are interested in multinational organizations</li>\r\n<li>Plan to pursue consulting or corporate finance roles</li>\r\n<li>Value global brand recognition</li>\r\n</ul>\r\n\r\n<h2>Who Should Consider a Mid-Sized Firm?</h2>\r\n\r\n<p>A mid-sized firm may be ideal for students who:</p>\r\n\r\n<ul>\r\n<li>Want exposure across multiple domains</li>\r\n<li>Prefer broader practical learning</li>\r\n<li>Enjoy client interaction</li>\r\n<li>Want greater responsibility early in their careers</li>\r\n<li>Plan to enter practice in the future</li>\r\n</ul>\r\n\r\n<h2>Questions to Ask Before Deciding</h2>\r\n\r\n<p>Regardless of firm size, every student should ask:</p>\r\n\r\n<ul>\r\n<li>What domains will I work in?</li>\r\n<li>How much audit exposure will I receive?</li>\r\n<li>Will I work on taxation assignments?</li>\r\n<li>Will I interact with clients?</li>\r\n<li>What training opportunities are available?</li>\r\n<li>How much responsibility will I receive?</li>\r\n</ul>\r\n\r\n<p>The answers to these questions are often more important than the firm\'s name.</p>\r\n\r\n<h2>Final Thoughts</h2>\r\n\r\n<p>The Big 4 versus mid-sized firm debate does not have a universally correct answer. Both options can provide outstanding learning opportunities when aligned with your career goals.</p>\r\n\r\n<p>Instead of asking which firm is more prestigious, ask which firm will help you develop the skills, exposure, and professional confidence you need for your future career.</p>\r\n\r\n<p><b>The best articleship is not necessarily the one with the biggest brand nameâ€”it is the one that helps you become the best Chartered Accountant you can be.</b></p>','blog-images/featured/uGts7BrKmqeLVlRPNkXVFXBQaNOUch0VSa6Ppppv.png','Big 4 vs Mid-Size CA Firms: Which Is Better for Articleship?','Confused between a Big 4 and a mid-sized CA firm for articleship? Compare exposure, learning opportunities, work culture, responsibilities, and career growth to make the right decision.','published',5,'2026-06-13 22:04:01','2026-06-13 22:04:01','2026-06-16 01:42:30'),(9,'How to Prepare for a CA Articleship Interview ?','how-to-prepare-for-a-ca-articleship-interview','Getting shortlisted for an articleship interview is only the first step. The real challenge is convincing a CA firm that you have the potential to learn, contribute, and grow as a professional. This guide covers everything CA students need to know to prepare for an articleship interview, from technical concepts and resume preparation to communication skills and common interview questions.','<p>For many CA students, securing an articleship interview is an exciting milestone. It represents the first step into the professional world and an opportunity to begin gaining practical experience as a future Chartered Accountant.</p>\r\n\r\n<p>However, receiving an interview call is only the beginning. Competition for quality articleship opportunities can be intense, and firms often evaluate multiple candidates before making a decision.</p>\r\n\r\n<p>The good news is that successful articleship interviews rarely depend on extraordinary knowledge. Most firms are looking for students who demonstrate professionalism, communication skills, a willingness to learn, and a strong foundation in basic concepts.</p>\r\n\r\n<p>This guide will help you prepare effectively and improve your chances of success.</p>\r\n\r\n<h2>Understand What Firms Are Actually Looking For</h2>\r\n\r\n<p>One of the biggest misconceptions among students is that firms expect articleship candidates to possess extensive practical experience.</p>\r\n\r\n<p>In reality, firms understand that students are joining to learn.</p>\r\n\r\n<p>Most recruiters evaluate:</p>\r\n\r\n<ul>\r\n<li>Communication skills</li>\r\n<li>Professional attitude</li>\r\n<li>Learning mindset</li>\r\n<li>Basic technical knowledge</li>\r\n<li>Confidence</li>\r\n<li>Reliability</li>\r\n<li>Career interest</li>\r\n</ul>\r\n\r\n<p>Your objective is not to prove that you know everything. Your objective is to demonstrate that you have the potential to become a valuable professional.</p>\r\n\r\n<h2>Research the Firm Before the Interview</h2>\r\n\r\n<p>Many students attend interviews without understanding the firm they are applying to.</p>\r\n\r\n<p>This is a mistake that can easily be avoided.</p>\r\n\r\n<p>Before the interview, learn about:</p>\r\n\r\n<ul>\r\n<li>The firm\'s services</li>\r\n<li>Practice areas</li>\r\n<li>Industries served</li>\r\n<li>Office locations</li>\r\n<li>Partners and leadership</li>\r\n<li>Recent achievements or developments</li>\r\n</ul>\r\n\r\n<p>Understanding the firm helps you answer questions more effectively and demonstrates genuine interest in the opportunity.</p>\r\n\r\n<h2>Prepare a Professional Resume</h2>\r\n\r\n<p>Your resume is often the first impression a firm has of you.</p>\r\n\r\n<p>A strong articleship resume should include:</p>\r\n\r\n<ul>\r\n<li>Educational qualifications</li>\r\n<li>CA progress details</li>\r\n<li>Academic achievements</li>\r\n<li>Technical skills</li>\r\n<li>Certifications</li>\r\n<li>Extracurricular activities</li>\r\n<li>Contact information</li>\r\n</ul>\r\n\r\n<p>Keep the format clean, professional, and easy to read.</p>\r\n\r\n<p>Always review your resume before the interview because many interview questions are based on information mentioned in it.</p>\r\n\r\n<h2>Revise Basic Technical Concepts</h2>\r\n\r\n<p>Most articleship interviews include questions on fundamental accounting, audit, and taxation concepts.</p>\r\n\r\n<p>Students should revise:</p>\r\n\r\n<ul>\r\n<li>Journal Entries</li>\r\n<li>Accounting Principles</li>\r\n<li>Financial Statements</li>\r\n<li>Depreciation</li>\r\n<li>Bank Reconciliation</li>\r\n<li>Audit Basics</li>\r\n<li>GST Fundamentals</li>\r\n<li>Income Tax Basics</li>\r\n</ul>\r\n\r\n<p>Interviewers are generally not looking for advanced expertise. They want to assess whether your conceptual foundation is strong.</p>\r\n\r\n<h2>Prepare for Common HR Questions</h2>\r\n\r\n<p>In addition to technical questions, firms often ask questions to understand your personality and career goals.</p>\r\n\r\n<p>Common questions include:</p>\r\n\r\n<ul>\r\n<li>Tell us about yourself.</li>\r\n<li>Why do you want to join our firm?</li>\r\n<li>Why are you pursuing Chartered Accountancy?</li>\r\n<li>What are your strengths?</li>\r\n<li>What are your weaknesses?</li>\r\n<li>Where do you see yourself in the future?</li>\r\n<li>What do you expect from articleship?</li>\r\n</ul>\r\n\r\n<p>Practice answering these questions confidently and naturally.</p>\r\n\r\n<h2>Improve Your Communication Skills</h2>\r\n\r\n<p>Communication plays a major role in interview performance.</p>\r\n\r\n<p>Students do not need perfect English to succeed. What matters is clarity, confidence, and professionalism.</p>\r\n\r\n<p>Focus on:</p>\r\n\r\n<ul>\r\n<li>Speaking clearly</li>\r\n<li>Listening carefully</li>\r\n<li>Maintaining eye contact</li>\r\n<li>Answering directly</li>\r\n<li>Avoiding unnecessary filler words</li>\r\n</ul>\r\n\r\n<p>Good communication often leaves a stronger impression than memorized answers.</p>\r\n\r\n<h2>Dress Professionally</h2>\r\n\r\n<p>Professional appearance demonstrates seriousness and respect for the opportunity.</p>\r\n\r\n<p>Recommended attire includes:</p>\r\n\r\n<ul>\r\n<li>Formal shirt</li>\r\n<li>Formal trousers</li>\r\n<li>Polished shoes</li>\r\n<li>Neat grooming</li>\r\n</ul>\r\n\r\n<p>Even virtual interviews require professional presentation.</p>\r\n\r\n<p>First impressions matter.</p>\r\n\r\n<h2>Be Ready to Discuss Your Academic Background</h2>\r\n\r\n<p>Interviewers frequently ask questions about academic performance.</p>\r\n\r\n<p>Be prepared to discuss:</p>\r\n\r\n<ul>\r\n<li>CA exam progress</li>\r\n<li>Educational background</li>\r\n<li>Academic strengths</li>\r\n<li>Challenges you have overcome</li>\r\n<li>Subjects you enjoy</li>\r\n</ul>\r\n\r\n<p>Answer honestly and confidently.</p>\r\n\r\n<h2>Practice Mock Interviews</h2>\r\n\r\n<p>One of the most effective preparation techniques is participating in mock interviews.</p>\r\n\r\n<p>Practice with:</p>\r\n\r\n<ul>\r\n<li>Friends</li>\r\n<li>Seniors</li>\r\n<li>Mentors</li>\r\n<li>Faculty members</li>\r\n</ul>\r\n\r\n<p>Mock interviews help identify weaknesses and improve confidence before the actual interview.</p>\r\n\r\n<h2>Questions You Can Ask the Interviewer</h2>\r\n\r\n<p>At the end of the interview, candidates often receive an opportunity to ask questions.</p>\r\n\r\n<p>Good questions include:</p>\r\n\r\n<ul>\r\n<li>What domains will I be exposed to?</li>\r\n<li>How are assignments allocated?</li>\r\n<li>Will I receive client interaction opportunities?</li>\r\n<li>Is there a mentorship structure?</li>\r\n<li>What skills do successful articles develop here?</li>\r\n</ul>\r\n\r\n<p>Thoughtful questions demonstrate curiosity and professionalism.</p>\r\n\r\n<h2>Common Mistakes to Avoid</h2>\r\n\r\n<p>Many candidates make avoidable mistakes during interviews.</p>\r\n\r\n<p>Examples include:</p>\r\n\r\n<ul>\r\n<li>Arriving late</li>\r\n<li>Not researching the firm</li>\r\n<li>Providing vague answers</li>\r\n<li>Overconfidence</li>\r\n<li>Dishonesty</li>\r\n<li>Criticizing previous experiences</li>\r\n<li>Ignoring basic etiquette</li>\r\n</ul>\r\n\r\n<p>Professionalism often matters as much as technical knowledge.</p>\r\n\r\n<h2>What If You Don\'t Know an Answer?</h2>\r\n\r\n<p>Every candidate encounters questions they cannot answer.</p>\r\n\r\n<p>If this happens:</p>\r\n\r\n<ul>\r\n<li>Stay calm</li>\r\n<li>Be honest</li>\r\n<li>Avoid guessing wildly</li>\r\n<li>Express willingness to learn</li>\r\n</ul>\r\n\r\n<p>Interviewers usually appreciate honesty more than incorrect answers presented with confidence.</p>\r\n\r\n<h2>Final Thoughts</h2>\r\n\r\n<p>Articleship interviews are not designed to identify perfect candidates. They are designed to identify students who have the right attitude, strong fundamentals, and the willingness to learn.</p>\r\n\r\n<p>Preparation, professionalism, and confidence can significantly improve your chances of success.</p>\r\n\r\n<p>Remember that firms are not simply hiring for current knowledgeâ€”they are investing in future professionals.</p>\r\n\r\n<p><b>Walk into your interview with confidence, prepare thoroughly, and focus on demonstrating your potential. That approach will often make a stronger impression than trying to appear perfect.</b></p>','blog-images/featured/o8IeGBpNok12tRokPzb02N4zn0m2FCWDfpoSCSVf.png','How to Prepare for a CA Articleship Interview | Complete Interview Guide','Learn how to prepare for a CA articleship interview with practical tips on technical questions, resume preparation, communication skills, interview etiquette, and common mistakes to avoid.','published',2,'2026-06-13 22:09:54','2026-06-13 22:09:54','2026-06-16 01:42:14'),(10,'Common Mistakes CA Students Make While Choosing Articleship','common-mistakes-ca-students-make-while-choosing-articleship','Choosing the right articleship is one of the most important career decisions a CA student will make. Unfortunately, many students focus on the wrong factors and later regret their choices. Understanding the common mistakes students make while selecting articleship can help you make a smarter decision and maximize the value of your training period.','<p>Choosing an articleship is one of the most important decisions in a CA student\'s journey. The firm you join will influence your practical learning, professional skills, confidence, industry exposure, and future career opportunities.</p>\r\n\r\n<p>Despite its importance, many students make decisions based on incomplete information, assumptions, or short-term considerations. The result is often disappointment, limited learning opportunities, and missed career growth.</p>\r\n\r\n<p>Fortunately, most of these mistakes can be avoided with proper planning and research.</p>\r\n\r\n<p>Let\'s look at some of the most common mistakes CA students make while choosing articleship and how you can avoid them.</p>\r\n\r\n<h2>1. Choosing a Firm Based Only on Stipend</h2>\r\n\r\n<p>This is perhaps the most common mistake.</p>\r\n\r\n<p>While stipend is an important factor, it should not be the primary reason for selecting a firm.</p>\r\n\r\n<p>Many students compare offers based solely on monthly compensation without evaluating the quality of exposure they will receive.</p>\r\n\r\n<p>A firm offering slightly lower stipend but significantly better learning opportunities often provides greater long-term value.</p>\r\n\r\n<p>Remember that articleship lasts for a limited period, but the skills you gain can benefit your entire career.</p>\r\n\r\n<h2>2. Assuming Bigger Firms Are Always Better</h2>\r\n\r\n<p>Many students automatically assume that larger firms guarantee better learning.</p>\r\n\r\n<p>While large firms often provide excellent opportunities, they may also involve specialized roles that limit exposure to certain domains.</p>\r\n\r\n<p>Mid-sized firms frequently offer broader practical experience, direct client interaction, and greater responsibility.</p>\r\n\r\n<p>The right choice depends on your learning goals rather than the size of the firm alone.</p>\r\n\r\n<h2>3. Ignoring Domain Exposure</h2>\r\n\r\n<p>Students often focus on firm names while overlooking the actual work they will perform.</p>\r\n\r\n<p>Before joining any firm, understand whether you will gain exposure to:</p>\r\n\r\n<ul>\r\n<li>Statutory Audit</li>\r\n<li>Tax Audit</li>\r\n<li>Direct Taxation</li>\r\n<li>GST Compliance</li>\r\n<li>Internal Audit</li>\r\n<li>ROC Compliance</li>\r\n<li>Advisory Services</li>\r\n</ul>\r\n\r\n<p>The quality and diversity of exposure often determine how much you learn during articleship.</p>\r\n\r\n<h2>4. Not Researching the Firm Properly</h2>\r\n\r\n<p>Many students accept offers without gathering enough information.</p>\r\n\r\n<p>Before joining, research:</p>\r\n\r\n<ul>\r\n<li>The firm\'s service areas</li>\r\n<li>Client portfolio</li>\r\n<li>Industries served</li>\r\n<li>Team size</li>\r\n<li>Work culture</li>\r\n<li>Growth opportunities</li>\r\n</ul>\r\n\r\n<p>A little research can prevent major disappointments later.</p>\r\n\r\n<h2>5. Failing to Speak with Existing Articles</h2>\r\n\r\n<p>Current and former articles often provide the most accurate picture of a firm\'s working environment.</p>\r\n\r\n<p>Many students miss the opportunity to speak with individuals who have firsthand experience.</p>\r\n\r\n<p>Ask existing articles about:</p>\r\n\r\n<ul>\r\n<li>Learning opportunities</li>\r\n<li>Work culture</li>\r\n<li>Mentorship quality</li>\r\n<li>Client interaction</li>\r\n<li>Overall experience</li>\r\n</ul>\r\n\r\n<p>Their insights can help you make a more informed decision.</p>\r\n\r\n<h2>6. Ignoring Mentorship Opportunities</h2>\r\n\r\n<p>Technical work is important, but guidance from experienced professionals can significantly accelerate learning.</p>\r\n\r\n<p>Students should evaluate whether the firm provides:</p>\r\n\r\n<ul>\r\n<li>Partner interaction</li>\r\n<li>Senior guidance</li>\r\n<li>Training sessions</li>\r\n<li>Feedback mechanisms</li>\r\n<li>Learning support</li>\r\n</ul>\r\n\r\n<p>Strong mentorship often makes a huge difference during articleship.</p>\r\n\r\n<h2>7. Not Considering Long-Term Career Goals</h2>\r\n\r\n<p>Your career aspirations should influence your articleship choice.</p>\r\n\r\n<p>For example:</p>\r\n\r\n<ul>\r\n<li>Students interested in practice may benefit from broader exposure.</li>\r\n<li>Students targeting corporate careers may prefer firms with large corporate clients.</li>\r\n<li>Students interested in taxation should seek firms with strong tax practices.</li>\r\n</ul>\r\n\r\n<p>Selecting a firm aligned with your future goals often produces better outcomes.</p>\r\n\r\n<h2>8. Overlooking Client Interaction Opportunities</h2>\r\n\r\n<p>Many students focus entirely on technical work and underestimate the value of client exposure.</p>\r\n\r\n<p>Client interaction helps develop:</p>\r\n\r\n<ul>\r\n<li>Communication skills</li>\r\n<li>Confidence</li>\r\n<li>Professional judgment</li>\r\n<li>Relationship management skills</li>\r\n<li>Business understanding</li>\r\n</ul>\r\n\r\n<p>These skills become extremely valuable after qualification.</p>\r\n\r\n<h2>9. Ignoring Work Culture</h2>\r\n\r\n<p>Work culture can significantly affect your learning experience and overall satisfaction.</p>\r\n\r\n<p>Consider factors such as:</p>\r\n\r\n<ul>\r\n<li>Team environment</li>\r\n<li>Support from seniors</li>\r\n<li>Professional behavior</li>\r\n<li>Learning culture</li>\r\n<li>Collaboration opportunities</li>\r\n</ul>\r\n\r\n<p>A healthy work environment often leads to better growth and motivation.</p>\r\n\r\n<h2>10. Making a Decision Too Quickly</h2>\r\n\r\n<p>Some students accept the first offer they receive without comparing alternatives.</p>\r\n\r\n<p>While securing an articleship quickly may feel reassuring, it is important to evaluate multiple opportunities whenever possible.</p>\r\n\r\n<p>Take time to compare:</p>\r\n\r\n<ul>\r\n<li>Exposure</li>\r\n<li>Mentorship</li>\r\n<li>Client profile</li>\r\n<li>Learning opportunities</li>\r\n<li>Career alignment</li>\r\n</ul>\r\n\r\n<p>A thoughtful decision can significantly improve your articleship experience.</p>\r\n\r\n<h2>11. Focusing Only on Immediate Benefits</h2>\r\n\r\n<p>Many students evaluate firms based on what they will gain in the next few months rather than what they will gain in the next five years.</p>\r\n\r\n<p>Articleship should be viewed as a long-term investment.</p>\r\n\r\n<p>Skills, exposure, and professional development often create far greater value than short-term benefits.</p>\r\n\r\n<h2>How to Make a Better Articleship Decision</h2>\r\n\r\n<p>Before accepting an offer, ask yourself:</p>\r\n\r\n<ul>\r\n<li>Will this firm help me develop practical skills?</li>\r\n<li>Will I gain exposure to multiple domains?</li>\r\n<li>Will I receive mentorship and guidance?</li>\r\n<li>Will I interact with clients?</li>\r\n<li>Does this opportunity align with my career goals?</li>\r\n</ul>\r\n\r\n<p>If the answers are positive, you are likely evaluating the right factors.</p>\r\n\r\n<h2>Final Thoughts</h2>\r\n\r\n<p>Articleship is much more than a mandatory training requirement. It is a crucial phase that shapes your professional identity and prepares you for future opportunities.</p>\r\n\r\n<p>By avoiding common mistakes and focusing on learning, exposure, mentorship, and career alignment, students can make significantly better decisions.</p>\r\n\r\n<p><b>The best articleship choice is not necessarily the most popular, the highest paying, or the biggest firm. It is the opportunity that helps you learn, grow, and become a better Chartered Accountant.</b></p>','blog-images/featured/bQ5h3SQg1xgNKFfcPOWpL19a9bSH1Eai49oSd6DV.png','Common Mistakes CA Students Make While Choosing Articleship','Learn the most common mistakes CA students make while selecting articleship firms and discover how to choose opportunities that support long-term career growth, learning, and professional development.','published',4,'2026-06-13 22:11:45','2026-06-13 22:11:45','2026-06-16 01:41:55'),(11,'Stipend vs Exposure: What Matters More in Articleship?','stipend-vs-exposure-what-matters-more-in-articleship','One of the biggest dilemmas CA students face while choosing an articleship is whether to prioritize stipend or exposure. A higher stipend may seem attractive in the short term, but the quality of exposure can influence your skills, confidence, employability, and long-term career growth. This guide explores both perspectives and helps students make a smarter career decision.','<p>One of the most common questions CA students ask while searching for articleship opportunities is:</p>\r\n \r\n <p><b>\"Should I choose a firm that offers a higher stipend or a firm that provides better exposure?\"</b></p>\r\n \r\n <p>It is a fair question. For many students, articleship is their first professional opportunity, and receiving a higher stipend can be financially rewarding. At the same time, articleship is also the most important practical learning phase of the Chartered Accountancy journey.</p>\r\n \r\n <p>The challenge lies in balancing short-term financial benefits with long-term career growth.</p>\r\n \r\n <p>So what should matter more—stipend or exposure?</p>\r\n \r\n <h2>Understanding the Purpose of Articleship</h2>\r\n \r\n <p>Before comparing stipend and exposure, it is important to understand the primary purpose of articleship.</p>\r\n \r\n <p>Articleship is not designed to be a high-paying job. It is a structured training period where students gain practical experience in:</p>\r\n \r\n <ul>\r\n <li>Audit</li>\r\n <li>Taxation</li>\r\n <li>Compliance</li>\r\n <li>Financial Reporting</li>\r\n <li>Client Management</li>\r\n <li>Business Operations</li>\r\n </ul>\r\n \r\n <p>The objective is to transform theoretical knowledge into professional competence.</p>\r\n \r\n <p>When viewed from this perspective, articleship should be considered an investment in your future career rather than simply a source of income.</p>\r\n \r\n <h2>Why Stipend Matters</h2>\r\n \r\n <p>There is nothing wrong with considering stipend while evaluating opportunities.</p>\r\n \r\n <p>A higher stipend can:</p>\r\n \r\n <ul>\r\n <li>Reduce financial pressure</li>\r\n <li>Cover transportation and living expenses</li>\r\n <li>Increase motivation</li>\r\n <li>Provide greater independence</li>\r\n </ul>\r\n \r\n <p>For students living away from home or managing educational expenses, stipend can play an important role.</p>\r\n \r\n <p>However, stipend should rarely be the only deciding factor.</p>\r\n \r\n <h2>The Real Value of Exposure</h2>\r\n \r\n <p>Exposure refers to the practical learning opportunities available during articleship.</p>\r\n \r\n <p>This includes:</p>\r\n \r\n <ul>\r\n <li>Audit assignments</li>\r\n <li>Taxation work</li>\r\n <li>GST compliance</li>\r\n <li>Client interactions</li>\r\n <li>Industry exposure</li>\r\n <li>Financial statement analysis</li>\r\n <li>Business process understanding</li>\r\n </ul>\r\n \r\n <p>These experiences help students develop skills that remain valuable throughout their professional careers.</p>\r\n \r\n <p>Unlike stipend, which benefits you for a limited period, exposure continues generating returns long after articleship is completed.</p>\r\n \r\n <h2>A Simple Example</h2>\r\n \r\n <p>Consider two hypothetical students.</p>\r\n \r\n <p><b>Student A</b> joins a firm offering a higher stipend but spends most of the time performing repetitive tasks with limited learning opportunities.</p>\r\n \r\n <p><b>Student B</b> joins a firm with slightly lower stipend but gains exposure to audits, taxation, client meetings, compliance work, and multiple industries.</p>\r\n \r\n <p>At the end of two years, Student B is likely to possess stronger technical knowledge, greater confidence, and better interview performance.</p>\r\n \r\n <p>In many cases, the additional skills gained through exposure result in significantly higher earning potential after qualification.</p>\r\n \r\n <h2>How Exposure Impacts Future Career Opportunities</h2>\r\n \r\n <p>Recruiters frequently evaluate candidates based on the practical experience they gained during articleship.</p>\r\n \r\n <p>Common interview questions include:</p>\r\n \r\n <ul>\r\n <li>What type of audits have you worked on?</li>\r\n <li>Which industries have you handled?</li>\r\n <li>What taxation assignments have you completed?</li>\r\n <li>Have you interacted with clients?</li>\r\n <li>What challenges have you solved?</li>\r\n </ul>\r\n \r\n <p>Students with diverse exposure often have stronger answers and greater confidence during interviews.</p>\r\n \r\n <p>This can lead to better job opportunities and faster career progression.</p>\r\n \r\n <h2>When a Higher Stipend Can Be Misleading</h2>\r\n \r\n <p>Many students assume that a higher stipend automatically indicates a better firm.</p>\r\n \r\n <p>This is not always true.</p>\r\n \r\n <p>Some firms may offer attractive stipends but provide limited exposure, repetitive assignments, or restricted learning opportunities.</p>\r\n \r\n <p>On the other hand, some firms offering modest stipends may provide exceptional practical training and mentorship.</p>\r\n \r\n <p>Students should evaluate the complete opportunity rather than focusing only on compensation.</p>\r\n \r\n <h2>Questions to Ask Before Making a Decision</h2>\r\n \r\n <p>Before accepting an articleship offer, students should ask:</p>\r\n \r\n <ul>\r\n <li>What domains will I work in?</li>\r\n <li>How much audit exposure will I receive?</li>\r\n <li>Will I gain taxation experience?</li>\r\n <li>Will I interact with clients?</li>\r\n <li>What industries does the firm serve?</li>\r\n <li>How much responsibility will I receive?</li>\r\n <li>Is there a mentorship or training structure?</li>\r\n </ul>\r\n \r\n <p>The answers to these questions often reveal the true value of the opportunity.</p>\r\n \r\n <h2>Can You Have Both?</h2>\r\n \r\n <p>Absolutely.</p>\r\n \r\n <p>Some firms successfully provide both competitive stipends and strong learning opportunities.</p>\r\n \r\n <p>Whenever possible, students should look for opportunities that offer:</p>\r\n \r\n <ul>\r\n <li>Meaningful exposure</li>\r\n <li>Good mentorship</li>\r\n <li>Professional growth</li>\r\n <li>Reasonable compensation</li>\r\n </ul>\r\n \r\n <p>However, if you must choose between slightly higher stipend and significantly better exposure, exposure is often the smarter long-term investment.</p>\r\n \r\n <h2>Think Beyond Two Years</h2>\r\n \r\n <p>One useful exercise is to ask yourself:</p>\r\n \r\n <p><b>\"Which opportunity will make me a stronger Chartered Accountant after two years?\"</b></p>\r\n \r\n <p>The answer often provides clarity.</p>\r\n \r\n <p>Articleship lasts for a limited period, but the skills and experience gained during that time can influence your career for decades.</p>\r\n \r\n <h2>Final Thoughts</h2>\r\n \r\n <p>Stipend is important, and students should not ignore financial considerations. However, articleship should primarily be evaluated based on the quality of learning and exposure it provides.</p>\r\n \r\n <p>The purpose of articleship is to build professional capability, not maximize short-term earnings.</p>\r\n \r\n <p>Students who prioritize exposure often develop stronger technical skills, broader business understanding, better communication abilities, and greater confidence.</p>\r\n \r\n <p>These advantages frequently translate into better opportunities and higher earning potential after qualification.</p>\r\n \r\n <p><b>When choosing between stipend and exposure, remember that stipend pays you for two years—but exposure can reward you for an entire career.</b></p><p>One of the most common questions CA students ask while searching for articleship opportunities is:</p>\r\n \r\n <p><b>\"Should I choose a firm that offers a higher stipend or a firm that provides better exposure?\"</b></p>\r\n \r\n <p>It is a fair question. For many students, articleship is their first professional opportunity, and receiving a higher stipend can be financially rewarding. At the same time, articleship is also the most important practical learning phase of the Chartered Accountancy journey.</p>\r\n \r\n <p>The challenge lies in balancing short-term financial benefits with long-term career growth.</p>\r\n \r\n <p>So what should matter more—stipend or exposure?</p>\r\n \r\n <h2>Understanding the Purpose of Articleship</h2>\r\n \r\n <p>Before comparing stipend and exposure, it is important to understand the primary purpose of articleship.</p>\r\n \r\n <p>Articleship is not designed to be a high-paying job. It is a structured training period where students gain practical experience in:</p>\r\n \r\n <ul>\r\n <li>Audit</li>\r\n <li>Taxation</li>\r\n <li>Compliance</li>\r\n <li>Financial Reporting</li>\r\n <li>Client Management</li>\r\n <li>Business Operations</li>\r\n </ul>\r\n \r\n <p>The objective is to transform theoretical knowledge into professional competence.</p>\r\n \r\n <p>When viewed from this perspective, articleship should be considered an investment in your future career rather than simply a source of income.</p>\r\n \r\n <h2>Why Stipend Matters</h2>\r\n \r\n <p>There is nothing wrong with considering stipend while evaluating opportunities.</p>\r\n \r\n <p>A higher stipend can:</p>\r\n \r\n <ul>\r\n <li>Reduce financial pressure</li>\r\n <li>Cover transportation and living expenses</li>\r\n <li>Increase motivation</li>\r\n <li>Provide greater independence</li>\r\n </ul>\r\n \r\n <p>For students living away from home or managing educational expenses, stipend can play an important role.</p>\r\n \r\n <p>However, stipend should rarely be the only deciding factor.</p>\r\n \r\n <h2>The Real Value of Exposure</h2>\r\n \r\n <p>Exposure refers to the practical learning opportunities available during articleship.</p>\r\n \r\n <p>This includes:</p>\r\n \r\n <ul>\r\n <li>Audit assignments</li>\r\n <li>Taxation work</li>\r\n <li>GST compliance</li>\r\n <li>Client interactions</li>\r\n <li>Industry exposure</li>\r\n <li>Financial statement analysis</li>\r\n <li>Business process understanding</li>\r\n </ul>\r\n \r\n <p>These experiences help students develop skills that remain valuable throughout their professional careers.</p>\r\n \r\n <p>Unlike stipend, which benefits you for a limited period, exposure continues generating returns long after articleship is completed.</p>\r\n \r\n <h2>A Simple Example</h2>\r\n \r\n <p>Consider two hypothetical students.</p>\r\n \r\n <p><b>Student A</b> joins a firm offering a higher stipend but spends most of the time performing repetitive tasks with limited learning opportunities.</p>\r\n \r\n <p><b>Student B</b> joins a firm with slightly lower stipend but gains exposure to audits, taxation, client meetings, compliance work, and multiple industries.</p>\r\n \r\n <p>At the end of two years, Student B is likely to possess stronger technical knowledge, greater confidence, and better interview performance.</p>\r\n \r\n <p>In many cases, the additional skills gained through exposure result in significantly higher earning potential after qualification.</p>\r\n \r\n <h2>How Exposure Impacts Future Career Opportunities</h2>\r\n \r\n <p>Recruiters frequently evaluate candidates based on the practical experience they gained during articleship.</p>\r\n \r\n <p>Common interview questions include:</p>\r\n \r\n <ul>\r\n <li>What type of audits have you worked on?</li>\r\n <li>Which industries have you handled?</li>\r\n <li>What taxation assignments have you completed?</li>\r\n <li>Have you interacted with clients?</li>\r\n <li>What challenges have you solved?</li>\r\n </ul>\r\n \r\n <p>Students with diverse exposure often have stronger answers and greater confidence during interviews.</p>\r\n \r\n <p>This can lead to better job opportunities and faster career progression.</p>\r\n \r\n <h2>When a Higher Stipend Can Be Misleading</h2>\r\n \r\n <p>Many students assume that a higher stipend automatically indicates a better firm.</p>\r\n \r\n <p>This is not always true.</p>\r\n \r\n <p>Some firms may offer attractive stipends but provide limited exposure, repetitive assignments, or restricted learning opportunities.</p>\r\n \r\n <p>On the other hand, some firms offering modest stipends may provide exceptional practical training and mentorship.</p>\r\n \r\n <p>Students should evaluate the complete opportunity rather than focusing only on compensation.</p>\r\n \r\n <h2>Questions to Ask Before Making a Decision</h2>\r\n \r\n <p>Before accepting an articleship offer, students should ask:</p>\r\n \r\n <ul>\r\n <li>What domains will I work in?</li>\r\n <li>How much audit exposure will I receive?</li>\r\n <li>Will I gain taxation experience?</li>\r\n <li>Will I interact with clients?</li>\r\n <li>What industries does the firm serve?</li>\r\n <li>How much responsibility will I receive?</li>\r\n <li>Is there a mentorship or training structure?</li>\r\n </ul>\r\n \r\n <p>The answers to these questions often reveal the true value of the opportunity.</p>\r\n \r\n <h2>Can You Have Both?</h2>\r\n \r\n <p>Absolutely.</p>\r\n \r\n <p>Some firms successfully provide both competitive stipends and strong learning opportunities.</p>\r\n \r\n <p>Whenever possible, students should look for opportunities that offer:</p>\r\n \r\n <ul>\r\n <li>Meaningful exposure</li>\r\n <li>Good mentorship</li>\r\n <li>Professional growth</li>\r\n <li>Reasonable compensation</li>\r\n </ul>\r\n \r\n <p>However, if you must choose between slightly higher stipend and significantly better exposure, exposure is often the smarter long-term investment.</p>\r\n \r\n <h2>Think Beyond Two Years</h2>\r\n \r\n <p>One useful exercise is to ask yourself:</p>\r\n \r\n <p><b>\"Which opportunity will make me a stronger Chartered Accountant after two years?\"</b></p>\r\n \r\n <p>The answer often provides clarity.</p>\r\n \r\n <p>Articleship lasts for a limited period, but the skills and experience gained during that time can influence your career for decades.</p>\r\n \r\n <h2>Final Thoughts</h2>\r\n \r\n <p>Stipend is important, and students should not ignore financial considerations. However, articleship should primarily be evaluated based on the quality of learning and exposure it provides.</p>\r\n \r\n <p>The purpose of articleship is to build professional capability, not maximize short-term earnings.</p>\r\n \r\n <p>Students who prioritize exposure often develop stronger technical skills, broader business understanding, better communication abilities, and greater confidence.</p>\r\n \r\n <p>These advantages frequently translate into better opportunities and higher earning potential after qualification.</p>\r\n \r\n <p><b>When choosing between stipend and exposure, remember that stipend pays you for two years—but exposure can reward you for an entire career.</b></p>','blog-images/featured/MB80KcQWg0WK1koOzRgbqVpvGua6TPFojeiAW6s8.png','Stipend vs Exposure: What Matters More in Articleship?','Confused between a higher stipend and better exposure during articleship? Learn how stipend and practical experience impact your CA career and discover what should matter most when choosing a firm.','published',1,'2026-06-17 23:58:21','2026-06-17 23:58:21','2026-06-17 23:58:51');
/*!40000 ALTER TABLE `blogs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `city_master`
--

DROP TABLE IF EXISTS `city_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `city_master` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `city_name` varchar(120) NOT NULL,
  `state_name` varchar(120) DEFAULT NULL,
  `slug` varchar(150) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=824 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `city_master`
--

LOCK TABLES `city_master` WRITE;
/*!40000 ALTER TABLE `city_master` DISABLE KEYS */;
INSERT INTO `city_master` VALUES (1,'Adilabad','Andhra Pradesh',NULL,1,NULL,NULL),(2,'Adoni','Andhra Pradesh',NULL,1,NULL,NULL),(3,'Amalapuram','Andhra Pradesh',NULL,1,NULL,NULL),(4,'Anakapalle','Andhra Pradesh',NULL,1,NULL,NULL),(5,'Anantapur','Andhra Pradesh',NULL,1,NULL,NULL),(6,'Badvel','Andhra Pradesh',NULL,1,NULL,NULL),(7,'Bapatla','Andhra Pradesh',NULL,1,NULL,NULL),(8,'Bellampalle','Andhra Pradesh',NULL,1,NULL,NULL),(9,'Bhadrachalam','Andhra Pradesh',NULL,1,NULL,NULL),(10,'Bheemavaram','Andhra Pradesh',NULL,1,NULL,NULL),(11,'Bhimavaram','Andhra Pradesh',NULL,1,NULL,NULL),(12,'Bhongir','Andhra Pradesh',NULL,1,NULL,NULL),(13,'Bobbili','Andhra Pradesh',NULL,1,NULL,NULL),(14,'Bodhan','Andhra Pradesh',NULL,1,NULL,NULL),(15,'Chilakaluripet','Andhra Pradesh',NULL,1,NULL,NULL),(16,'Chirala','Andhra Pradesh',NULL,1,NULL,NULL),(17,'Chittoor','Andhra Pradesh',NULL,1,NULL,NULL),(18,'Cuddapah (Kadapa)','Andhra Pradesh',NULL,1,NULL,NULL),(19,'Dharmavaram','Andhra Pradesh',NULL,1,NULL,NULL),(20,'Eluru','Andhra Pradesh',NULL,1,NULL,NULL),(21,'Farooqnagar','Andhra Pradesh',NULL,1,NULL,NULL),(22,'Gadwal','Andhra Pradesh',NULL,1,NULL,NULL),(23,'Gooty','Andhra Pradesh',NULL,1,NULL,NULL),(24,'Gudivada','Andhra Pradesh',NULL,1,NULL,NULL),(25,'Gudur','Andhra Pradesh',NULL,1,NULL,NULL),(26,'Guntakal','Andhra Pradesh',NULL,1,NULL,NULL),(27,'Guntur','Andhra Pradesh',NULL,1,NULL,NULL),(28,'Hanuman Junction','Andhra Pradesh',NULL,1,NULL,NULL),(29,'Hindupur','Andhra Pradesh',NULL,1,NULL,NULL),(30,'Hyderabad','Andhra Pradesh',NULL,1,NULL,NULL),(31,'Ichchapuram','Andhra Pradesh',NULL,1,NULL,NULL),(32,'Jaggayyapet','Andhra Pradesh',NULL,1,NULL,NULL),(33,'Jagtial','Andhra Pradesh',NULL,1,NULL,NULL),(34,'Jammalamadugu','Andhra Pradesh',NULL,1,NULL,NULL),(35,'Jangaon','Andhra Pradesh',NULL,1,NULL,NULL),(36,'Kadapa','Andhra Pradesh',NULL,1,NULL,NULL),(37,'Kadiyam','Andhra Pradesh',NULL,1,NULL,NULL),(38,'Kagaznagar','Andhra Pradesh',NULL,1,NULL,NULL),(39,'Kakinada','Andhra Pradesh',NULL,1,NULL,NULL),(40,'Kalyandurg','Andhra Pradesh',NULL,1,NULL,NULL),(41,'Kamareddy','Andhra Pradesh',NULL,1,NULL,NULL),(42,'Kandukur','Andhra Pradesh',NULL,1,NULL,NULL),(43,'Karimnagar','Andhra Pradesh',NULL,1,NULL,NULL),(44,'Kavali','Andhra Pradesh',NULL,1,NULL,NULL),(45,'Khammam','Andhra Pradesh',NULL,1,NULL,NULL),(46,'Koratla','Andhra Pradesh',NULL,1,NULL,NULL),(47,'Kothagudem','Andhra Pradesh',NULL,1,NULL,NULL),(48,'Kothapeta','Andhra Pradesh',NULL,1,NULL,NULL),(49,'Kovvur','Andhra Pradesh',NULL,1,NULL,NULL),(50,'Kurnool','Andhra Pradesh',NULL,1,NULL,NULL),(51,'Machilipatnam','Andhra Pradesh',NULL,1,NULL,NULL),(52,'Madanapalle','Andhra Pradesh',NULL,1,NULL,NULL),(53,'Mahbubnagar','Andhra Pradesh',NULL,1,NULL,NULL),(54,'Mandapeta','Andhra Pradesh',NULL,1,NULL,NULL),(55,'Mancherial','Andhra Pradesh',NULL,1,NULL,NULL),(56,'Mandamarri','Andhra Pradesh',NULL,1,NULL,NULL),(57,'Markapur','Andhra Pradesh',NULL,1,NULL,NULL),(58,'Medak','Andhra Pradesh',NULL,1,NULL,NULL),(59,'Miryalaguda','Andhra Pradesh',NULL,1,NULL,NULL),(60,'Nagari','Andhra Pradesh',NULL,1,NULL,NULL),(61,'Nalgonda','Andhra Pradesh',NULL,1,NULL,NULL),(62,'Nandyal','Andhra Pradesh',NULL,1,NULL,NULL),(63,'Narasapur','Andhra Pradesh',NULL,1,NULL,NULL),(64,'Narasaraopet','Andhra Pradesh',NULL,1,NULL,NULL),(65,'Narayankhed','Andhra Pradesh',NULL,1,NULL,NULL),(66,'Narsipatnam','Andhra Pradesh',NULL,1,NULL,NULL),(67,'Nellore','Andhra Pradesh',NULL,1,NULL,NULL),(68,'Nidadavole','Andhra Pradesh',NULL,1,NULL,NULL),(69,'Nirmal','Andhra Pradesh',NULL,1,NULL,NULL),(70,'Nizamabad','Andhra Pradesh',NULL,1,NULL,NULL),(71,'Nuzvid','Andhra Pradesh',NULL,1,NULL,NULL),(72,'Ongole','Andhra Pradesh',NULL,1,NULL,NULL),(73,'Palakollu','Andhra Pradesh',NULL,1,NULL,NULL),(74,'Palasa-Kasibugga','Andhra Pradesh',NULL,1,NULL,NULL),(75,'Palwancha','Andhra Pradesh',NULL,1,NULL,NULL),(76,'Parvathipuram','Andhra Pradesh',NULL,1,NULL,NULL),(77,'Pedana','Andhra Pradesh',NULL,1,NULL,NULL),(78,'Peddapuram','Andhra Pradesh',NULL,1,NULL,NULL),(79,'Pithapuram','Andhra Pradesh',NULL,1,NULL,NULL),(80,'Ponnur','Andhra Pradesh',NULL,1,NULL,NULL),(81,'Proddatur','Andhra Pradesh',NULL,1,NULL,NULL),(82,'Punganur','Andhra Pradesh',NULL,1,NULL,NULL),(83,'Puttur','Andhra Pradesh',NULL,1,NULL,NULL),(84,'Rajahmundry','Andhra Pradesh',NULL,1,NULL,NULL),(85,'Rajam','Andhra Pradesh',NULL,1,NULL,NULL),(86,'Ramagundam','Andhra Pradesh',NULL,1,NULL,NULL),(87,'Rayachoti','Andhra Pradesh',NULL,1,NULL,NULL),(88,'Rayadurg','Andhra Pradesh',NULL,1,NULL,NULL),(89,'Repalle','Andhra Pradesh',NULL,1,NULL,NULL),(90,'Salur','Andhra Pradesh',NULL,1,NULL,NULL),(91,'Sangareddy','Andhra Pradesh',NULL,1,NULL,NULL),(92,'Sattenapalle','Andhra Pradesh',NULL,1,NULL,NULL),(93,'Siddipet','Andhra Pradesh',NULL,1,NULL,NULL),(94,'Sircilla','Andhra Pradesh',NULL,1,NULL,NULL),(95,'Srikakulam','Andhra Pradesh',NULL,1,NULL,NULL),(96,'Srikalahasti','Andhra Pradesh',NULL,1,NULL,NULL),(97,'Suryapet','Andhra Pradesh',NULL,1,NULL,NULL),(98,'Tadepalligudem','Andhra Pradesh',NULL,1,NULL,NULL),(99,'Tadipatri','Andhra Pradesh',NULL,1,NULL,NULL),(100,'Tanuku','Andhra Pradesh',NULL,1,NULL,NULL),(101,'Tenali','Andhra Pradesh',NULL,1,NULL,NULL),(102,'Tirupati','Andhra Pradesh',NULL,1,NULL,NULL),(103,'Tuni','Andhra Pradesh',NULL,1,NULL,NULL),(104,'Uravakonda','Andhra Pradesh',NULL,1,NULL,NULL),(105,'Venkatagiri','Andhra Pradesh',NULL,1,NULL,NULL),(106,'Vicarabad','Andhra Pradesh',NULL,1,NULL,NULL),(107,'Vijayawada','Andhra Pradesh',NULL,1,NULL,NULL),(108,'Vinukonda','Andhra Pradesh',NULL,1,NULL,NULL),(109,'Visakhapatnam','Andhra Pradesh',NULL,1,NULL,NULL),(110,'Vizianagaram','Andhra Pradesh',NULL,1,NULL,NULL),(111,'Wanaparthy','Andhra Pradesh',NULL,1,NULL,NULL),(112,'Warangal','Andhra Pradesh',NULL,1,NULL,NULL),(113,'Yellandu','Andhra Pradesh',NULL,1,NULL,NULL),(114,'Yemmiganur','Andhra Pradesh',NULL,1,NULL,NULL),(115,'Zahirabad','Andhra Pradesh',NULL,1,NULL,NULL),(116,'Ahmednagar','Maharashtra',NULL,1,NULL,NULL),(117,'Akola','Maharashtra',NULL,1,NULL,NULL),(118,'Amravati','Maharashtra',NULL,1,NULL,NULL),(119,'Ambernath','Maharashtra',NULL,1,NULL,NULL),(120,'Baramati','Maharashtra',NULL,1,NULL,NULL),(121,'Barshi','Maharashtra',NULL,1,NULL,NULL),(122,'Beed','Maharashtra',NULL,1,NULL,NULL),(123,'Bhandara','Maharashtra',NULL,1,NULL,NULL),(124,'Bhiwandi','Maharashtra',NULL,1,NULL,NULL),(125,'Bhusawal','Maharashtra',NULL,1,NULL,NULL),(126,'Chalisgaon','Maharashtra',NULL,1,NULL,NULL),(127,'Chandrapur','Maharashtra',NULL,1,NULL,NULL),(128,'Chhatrapati Sambhajinagar','Maharashtra',NULL,1,NULL,NULL),(129,'Dhule','Maharashtra',NULL,1,NULL,NULL),(130,'Dombivli','Maharashtra',NULL,1,NULL,NULL),(131,'Gondia','Maharashtra',NULL,1,NULL,NULL),(132,'Hinganghat','Maharashtra',NULL,1,NULL,NULL),(133,'Ichalkaranji','Maharashtra',NULL,1,NULL,NULL),(134,'Jalgaon','Maharashtra',NULL,1,NULL,NULL),(135,'Jalna','Maharashtra',NULL,1,NULL,NULL),(136,'Kalyan','Maharashtra',NULL,1,NULL,NULL),(137,'Karad','Maharashtra',NULL,1,NULL,NULL),(138,'Kolhapur','Maharashtra',NULL,1,NULL,NULL),(139,'Latur','Maharashtra',NULL,1,NULL,NULL),(140,'Lonavala','Maharashtra',NULL,1,NULL,NULL),(141,'Malegaon','Maharashtra',NULL,1,NULL,NULL),(142,'Malkapur','Maharashtra',NULL,1,NULL,NULL),(143,'Mangaon','Maharashtra',NULL,1,NULL,NULL),(144,'Mangrulpir','Maharashtra',NULL,1,NULL,NULL),(145,'Manjlegaon','Maharashtra',NULL,1,NULL,NULL),(146,'Mira-Bhayandar','Maharashtra',NULL,1,NULL,NULL),(147,'Miraj','Maharashtra',NULL,1,NULL,NULL),(148,'Mumbai','Maharashtra',NULL,1,NULL,NULL),(149,'Murtijapur','Maharashtra',NULL,1,NULL,NULL),(150,'Nagpur','Maharashtra',NULL,1,NULL,NULL),(151,'Nalasopara','Maharashtra',NULL,1,NULL,NULL),(152,'Nanded','Maharashtra',NULL,1,NULL,NULL),(153,'Nandurbar','Maharashtra',NULL,1,NULL,NULL),(154,'Nashik','Maharashtra',NULL,1,NULL,NULL),(155,'Navi Mumbai','Maharashtra',NULL,1,NULL,NULL),(156,'Nilanga','Maharashtra',NULL,1,NULL,NULL),(157,'Osmanabad','Maharashtra',NULL,1,NULL,NULL),(158,'Palghar','Maharashtra',NULL,1,NULL,NULL),(159,'Pandharpur','Maharashtra',NULL,1,NULL,NULL),(160,'Panvel','Maharashtra',NULL,1,NULL,NULL),(161,'Parbhani','Maharashtra',NULL,1,NULL,NULL),(162,'Parli','Maharashtra',NULL,1,NULL,NULL),(163,'Pen','Maharashtra',NULL,1,NULL,NULL),(164,'Phaltan','Maharashtra',NULL,1,NULL,NULL),(165,'Pimpri-Chinchwad','Maharashtra',NULL,1,NULL,NULL),(166,'Pune','Maharashtra',NULL,1,NULL,NULL),(167,'Purna','Maharashtra',NULL,1,NULL,NULL),(168,'Pusad','Maharashtra',NULL,1,NULL,NULL),(169,'Rahuri','Maharashtra',NULL,1,NULL,NULL),(170,'Ratnagiri','Maharashtra',NULL,1,NULL,NULL),(171,'Sangamner','Maharashtra',NULL,1,NULL,NULL),(172,'Sangli','Maharashtra',NULL,1,NULL,NULL),(173,'Sangli-Miraj-Kupwad','Maharashtra',NULL,1,NULL,NULL),(174,'Satara','Maharashtra',NULL,1,NULL,NULL),(175,'Shirdi','Maharashtra',NULL,1,NULL,NULL),(176,'Shirpur-Warwade','Maharashtra',NULL,1,NULL,NULL),(177,'Shrigonda','Maharashtra',NULL,1,NULL,NULL),(178,'Shrirampur','Maharashtra',NULL,1,NULL,NULL),(179,'Sillod','Maharashtra',NULL,1,NULL,NULL),(180,'Sinnar','Maharashtra',NULL,1,NULL,NULL),(181,'Solapur','Maharashtra',NULL,1,NULL,NULL),(182,'Talegaon Dabhade','Maharashtra',NULL,1,NULL,NULL),(183,'Thane','Maharashtra',NULL,1,NULL,NULL),(184,'Tuljapur','Maharashtra',NULL,1,NULL,NULL),(185,'Tumsar','Maharashtra',NULL,1,NULL,NULL),(186,'Udgir','Maharashtra',NULL,1,NULL,NULL),(187,'Ulhasnagar','Maharashtra',NULL,1,NULL,NULL),(188,'Umred','Maharashtra',NULL,1,NULL,NULL),(189,'Uran','Maharashtra',NULL,1,NULL,NULL),(190,'Uran Islampur','Maharashtra',NULL,1,NULL,NULL),(191,'Vadgaon Kasba','Maharashtra',NULL,1,NULL,NULL),(192,'Vaijapur','Maharashtra',NULL,1,NULL,NULL),(193,'Vasai','Maharashtra',NULL,1,NULL,NULL),(194,'Vasai-Virar','Maharashtra',NULL,1,NULL,NULL),(195,'Virar','Maharashtra',NULL,1,NULL,NULL),(196,'Vita','Maharashtra',NULL,1,NULL,NULL),(197,'Wai','Maharashtra',NULL,1,NULL,NULL),(198,'Wani','Maharashtra',NULL,1,NULL,NULL),(199,'Wardha','Maharashtra',NULL,1,NULL,NULL),(200,'Warora','Maharashtra',NULL,1,NULL,NULL),(201,'Washim','Maharashtra',NULL,1,NULL,NULL),(202,'Yavatmal','Maharashtra',NULL,1,NULL,NULL),(203,'Yeola','Maharashtra',NULL,1,NULL,NULL),(204,'Port Blair','Andaman & Nicobar Islands',NULL,1,NULL,NULL),(205,'Diglipur','Andaman & Nicobar Islands',NULL,1,NULL,NULL),(206,'Mayabunder','Andaman & Nicobar Islands',NULL,1,NULL,NULL),(207,'Rangat','Andaman & Nicobar Islands',NULL,1,NULL,NULL),(208,'Bamboo Flat','Andaman & Nicobar Islands',NULL,1,NULL,NULL),(209,'Garacharma','Andaman & Nicobar Islands',NULL,1,NULL,NULL),(210,'Hut Bay','Andaman & Nicobar Islands',NULL,1,NULL,NULL),(211,'Car Nicobar','Andaman & Nicobar Islands',NULL,1,NULL,NULL),(212,'Kamorta','Andaman & Nicobar Islands',NULL,1,NULL,NULL),(213,'Nancowry','Andaman & Nicobar Islands',NULL,1,NULL,NULL),(214,'Itanagar','Arunachal Pradesh',NULL,1,NULL,NULL),(215,'Naharlagun','Arunachal Pradesh',NULL,1,NULL,NULL),(216,'Tawang','Arunachal Pradesh',NULL,1,NULL,NULL),(217,'Ziro','Arunachal Pradesh',NULL,1,NULL,NULL),(218,'Pasighat','Arunachal Pradesh',NULL,1,NULL,NULL),(219,'Bomdila','Arunachal Pradesh',NULL,1,NULL,NULL),(220,'Aalo','Arunachal Pradesh',NULL,1,NULL,NULL),(221,'Tezu','Arunachal Pradesh',NULL,1,NULL,NULL),(222,'Roing','Arunachal Pradesh',NULL,1,NULL,NULL),(223,'Namsai','Arunachal Pradesh',NULL,1,NULL,NULL),(224,'Changlang','Arunachal Pradesh',NULL,1,NULL,NULL),(225,'Khonsa','Arunachal Pradesh',NULL,1,NULL,NULL),(226,'Seppa','Arunachal Pradesh',NULL,1,NULL,NULL),(227,'Yingkiong','Arunachal Pradesh',NULL,1,NULL,NULL),(228,'Anini','Arunachal Pradesh',NULL,1,NULL,NULL),(229,'Daporijo','Arunachal Pradesh',NULL,1,NULL,NULL),(230,'Basar','Arunachal Pradesh',NULL,1,NULL,NULL),(231,'Along','Arunachal Pradesh',NULL,1,NULL,NULL),(232,'Doimukh','Arunachal Pradesh',NULL,1,NULL,NULL),(233,'Kharsang','Arunachal Pradesh',NULL,1,NULL,NULL),(234,'Likabali','Arunachal Pradesh',NULL,1,NULL,NULL),(235,'Ruksin','Arunachal Pradesh',NULL,1,NULL,NULL),(236,'Bhalukpong','Arunachal Pradesh',NULL,1,NULL,NULL),(237,'Dirang','Arunachal Pradesh',NULL,1,NULL,NULL),(238,'Mechuka','Arunachal Pradesh',NULL,1,NULL,NULL),(239,'Guwahati','Assam',NULL,1,NULL,NULL),(240,'Silchar','Assam',NULL,1,NULL,NULL),(241,'Dibrugarh','Assam',NULL,1,NULL,NULL),(242,'Jorhat','Assam',NULL,1,NULL,NULL),(243,'Tezpur','Assam',NULL,1,NULL,NULL),(244,'Nagaon','Assam',NULL,1,NULL,NULL),(245,'Tinsukia','Assam',NULL,1,NULL,NULL),(246,'Sivasagar','Assam',NULL,1,NULL,NULL),(247,'Bongaigaon','Assam',NULL,1,NULL,NULL),(248,'Dhubri','Assam',NULL,1,NULL,NULL),(249,'Karimganj','Assam',NULL,1,NULL,NULL),(250,'Golaghat','Assam',NULL,1,NULL,NULL),(251,'Lakhimpur','Assam',NULL,1,NULL,NULL),(252,'North Lakhimpur','Assam',NULL,1,NULL,NULL),(253,'Mangaldoi','Assam',NULL,1,NULL,NULL),(254,'Diphu','Assam',NULL,1,NULL,NULL),(255,'Haflong','Assam',NULL,1,NULL,NULL),(256,'Barpeta','Assam',NULL,1,NULL,NULL),(257,'Barpeta Road','Assam',NULL,1,NULL,NULL),(258,'Kokrajhar','Assam',NULL,1,NULL,NULL),(259,'Nalbari','Assam',NULL,1,NULL,NULL),(260,'Hojai','Assam',NULL,1,NULL,NULL),(261,'Morigaon','Assam',NULL,1,NULL,NULL),(262,'Biswanath Chariali','Assam',NULL,1,NULL,NULL),(263,'Dhemaji','Assam',NULL,1,NULL,NULL),(264,'Duliajan','Assam',NULL,1,NULL,NULL),(265,'Goalpara','Assam',NULL,1,NULL,NULL),(266,'Hailakandi','Assam',NULL,1,NULL,NULL),(267,'Lumding','Assam',NULL,1,NULL,NULL),(268,'Majuli','Assam',NULL,1,NULL,NULL),(269,'Pathsala','Assam',NULL,1,NULL,NULL),(270,'Rangia','Assam',NULL,1,NULL,NULL),(271,'Sonari','Assam',NULL,1,NULL,NULL),(272,'Titabor','Assam',NULL,1,NULL,NULL),(273,'Udalguri','Assam',NULL,1,NULL,NULL),(274,'Arrah','Bihar',NULL,1,NULL,NULL),(275,'Aurangabad','Bihar',NULL,1,NULL,NULL),(276,'Bagaha','Bihar',NULL,1,NULL,NULL),(277,'Bakhtiarpur','Bihar',NULL,1,NULL,NULL),(278,'Banka','Bihar',NULL,1,NULL,NULL),(279,'Barahiya','Bihar',NULL,1,NULL,NULL),(280,'Barauli','Bihar',NULL,1,NULL,NULL),(281,'Barbigha','Bihar',NULL,1,NULL,NULL),(282,'Barh','Bihar',NULL,1,NULL,NULL),(283,'Begusarai','Bihar',NULL,1,NULL,NULL),(284,'Bettiah','Bihar',NULL,1,NULL,NULL),(285,'Bhabua','Bihar',NULL,1,NULL,NULL),(286,'Bhagalpur','Bihar',NULL,1,NULL,NULL),(287,'Bihar Sharif','Bihar',NULL,1,NULL,NULL),(288,'Bodh Gaya','Bihar',NULL,1,NULL,NULL),(289,'Buxar','Bihar',NULL,1,NULL,NULL),(290,'Chapra','Bihar',NULL,1,NULL,NULL),(291,'Darbhanga','Bihar',NULL,1,NULL,NULL),(292,'Daudnagar','Bihar',NULL,1,NULL,NULL),(293,'Dehri-on-Sone','Bihar',NULL,1,NULL,NULL),(294,'Dumraon','Bihar',NULL,1,NULL,NULL),(295,'Fatwah','Bihar',NULL,1,NULL,NULL),(296,'Forbesganj','Bihar',NULL,1,NULL,NULL),(297,'Gaya','Bihar',NULL,1,NULL,NULL),(298,'Gopalganj','Bihar',NULL,1,NULL,NULL),(299,'Hajipur','Bihar',NULL,1,NULL,NULL),(300,'Hilsa','Bihar',NULL,1,NULL,NULL),(301,'Islampur','Bihar',NULL,1,NULL,NULL),(302,'Jagdishpur','Bihar',NULL,1,NULL,NULL),(303,'Jamalpur','Bihar',NULL,1,NULL,NULL),(304,'Jamui','Bihar',NULL,1,NULL,NULL),(305,'Jehanabad','Bihar',NULL,1,NULL,NULL),(306,'Jogbani','Bihar',NULL,1,NULL,NULL),(307,'Katihar','Bihar',NULL,1,NULL,NULL),(308,'Khagaria','Bihar',NULL,1,NULL,NULL),(309,'Kishanganj','Bihar',NULL,1,NULL,NULL),(310,'Lakhisarai','Bihar',NULL,1,NULL,NULL),(311,'Madhepura','Bihar',NULL,1,NULL,NULL),(312,'Madhubani','Bihar',NULL,1,NULL,NULL),(313,'Maharajganj','Bihar',NULL,1,NULL,NULL),(314,'Maner','Bihar',NULL,1,NULL,NULL),(315,'Masaurhi','Bihar',NULL,1,NULL,NULL),(316,'Mokameh','Bihar',NULL,1,NULL,NULL),(317,'Motihari','Bihar',NULL,1,NULL,NULL),(318,'Munger','Bihar',NULL,1,NULL,NULL),(319,'Muzaffarpur','Bihar',NULL,1,NULL,NULL),(320,'Narkatiaganj','Bihar',NULL,1,NULL,NULL),(321,'Naugachhia','Bihar',NULL,1,NULL,NULL),(322,'Nawada','Bihar',NULL,1,NULL,NULL),(323,'Patna','Bihar',NULL,1,NULL,NULL),(324,'Piro','Bihar',NULL,1,NULL,NULL),(325,'Purnia','Bihar',NULL,1,NULL,NULL),(326,'Rajgir','Bihar',NULL,1,NULL,NULL),(327,'Raxaul','Bihar',NULL,1,NULL,NULL),(328,'Rosera','Bihar',NULL,1,NULL,NULL),(329,'Saharsa','Bihar',NULL,1,NULL,NULL),(330,'Samastipur','Bihar',NULL,1,NULL,NULL),(331,'Sasaram','Bihar',NULL,1,NULL,NULL),(332,'Sheikhpura','Bihar',NULL,1,NULL,NULL),(333,'Sheohar','Bihar',NULL,1,NULL,NULL),(334,'Sitamarhi','Bihar',NULL,1,NULL,NULL),(335,'Siwan','Bihar',NULL,1,NULL,NULL),(336,'Sonepur','Bihar',NULL,1,NULL,NULL),(337,'Supaul','Bihar',NULL,1,NULL,NULL),(338,'Warisaliganj','Bihar',NULL,1,NULL,NULL),(339,'Chandigarh','Chandigarh',NULL,1,NULL,NULL),(340,'Ahiwara','Chhattisgarh',NULL,1,NULL,NULL),(341,'Akaltara','Chhattisgarh',NULL,1,NULL,NULL),(342,'Ambikapur','Chhattisgarh',NULL,1,NULL,NULL),(343,'Arang','Chhattisgarh',NULL,1,NULL,NULL),(344,'Balod','Chhattisgarh',NULL,1,NULL,NULL),(345,'Baloda Bazar','Chhattisgarh',NULL,1,NULL,NULL),(346,'Bemetara','Chhattisgarh',NULL,1,NULL,NULL),(347,'Bhatapara','Chhattisgarh',NULL,1,NULL,NULL),(348,'Bhilai','Chhattisgarh',NULL,1,NULL,NULL),(349,'Bilaspur','Chhattisgarh',NULL,1,NULL,NULL),(350,'Birgaon','Chhattisgarh',NULL,1,NULL,NULL),(351,'Champa','Chhattisgarh',NULL,1,NULL,NULL),(352,'Chirmiri','Chhattisgarh',NULL,1,NULL,NULL),(353,'Dalli-Rajhara','Chhattisgarh',NULL,1,NULL,NULL),(354,'Dhamtari','Chhattisgarh',NULL,1,NULL,NULL),(355,'Dipka','Chhattisgarh',NULL,1,NULL,NULL),(356,'Dongargarh','Chhattisgarh',NULL,1,NULL,NULL),(357,'Durg','Chhattisgarh',NULL,1,NULL,NULL),(358,'Durg-Bhilai Nagar','Chhattisgarh',NULL,1,NULL,NULL),(359,'Gobra Nawapara','Chhattisgarh',NULL,1,NULL,NULL),(360,'Jagdalpur','Chhattisgarh',NULL,1,NULL,NULL),(361,'Janjgir','Chhattisgarh',NULL,1,NULL,NULL),(362,'Jashpur Nagar','Chhattisgarh',NULL,1,NULL,NULL),(363,'Kanker','Chhattisgarh',NULL,1,NULL,NULL),(364,'Kawardha','Chhattisgarh',NULL,1,NULL,NULL),(365,'Kondagaon','Chhattisgarh',NULL,1,NULL,NULL),(366,'Korba','Chhattisgarh',NULL,1,NULL,NULL),(367,'Mahasamund','Chhattisgarh',NULL,1,NULL,NULL),(368,'Mungeli','Chhattisgarh',NULL,1,NULL,NULL),(369,'Naila Janjgir','Chhattisgarh',NULL,1,NULL,NULL),(370,'Raigarh','Chhattisgarh',NULL,1,NULL,NULL),(371,'Raipur','Chhattisgarh',NULL,1,NULL,NULL),(372,'Rajnandgaon','Chhattisgarh',NULL,1,NULL,NULL),(373,'Sakti','Chhattisgarh',NULL,1,NULL,NULL),(374,'Tilda Newra','Chhattisgarh',NULL,1,NULL,NULL),(375,'Delhi','Delhi',NULL,1,NULL,NULL),(376,'New Delhi','Delhi',NULL,1,NULL,NULL),(377,'Amli','Dadra & Nagar Haveli',NULL,1,NULL,NULL),(378,'Dadra','Dadra & Nagar Haveli',NULL,1,NULL,NULL),(379,'Silvassa','Dadra & Nagar Haveli',NULL,1,NULL,NULL),(380,'Daman','Daman & Diu',NULL,1,NULL,NULL),(381,'Diu','Daman & Diu',NULL,1,NULL,NULL),(382,'Panaji','Goa',NULL,1,NULL,NULL),(383,'Margao','Goa',NULL,1,NULL,NULL),(384,'Vasco da Gama','Goa',NULL,1,NULL,NULL),(385,'Mapusa','Goa',NULL,1,NULL,NULL),(386,'Ponda','Goa',NULL,1,NULL,NULL),(387,'Quepem','Goa',NULL,1,NULL,NULL),(388,'Bicholim','Goa',NULL,1,NULL,NULL),(389,'Curchorem','Goa',NULL,1,NULL,NULL),(390,'Canacona','Goa',NULL,1,NULL,NULL),(391,'Valpoi','Goa',NULL,1,NULL,NULL),(392,'Ahmedabad','Gujarat',NULL,1,NULL,NULL),(393,'Surat','Gujarat',NULL,1,NULL,NULL),(394,'Vadodara','Gujarat',NULL,1,NULL,NULL),(395,'Rajkot','Gujarat',NULL,1,NULL,NULL),(396,'Bhavnagar','Gujarat',NULL,1,NULL,NULL),(397,'Jamnagar','Gujarat',NULL,1,NULL,NULL),(398,'Junagadh','Gujarat',NULL,1,NULL,NULL),(399,'Gandhinagar','Gujarat',NULL,1,NULL,NULL),(400,'Anand','Gujarat',NULL,1,NULL,NULL),(401,'Nadiad','Gujarat',NULL,1,NULL,NULL),(402,'Navsari','Gujarat',NULL,1,NULL,NULL),(403,'Valsad','Gujarat',NULL,1,NULL,NULL),(404,'Vapi','Gujarat',NULL,1,NULL,NULL),(405,'Bharuch','Gujarat',NULL,1,NULL,NULL),(406,'Ankleshwar','Gujarat',NULL,1,NULL,NULL),(407,'Mehsana','Gujarat',NULL,1,NULL,NULL),(408,'Palanpur','Gujarat',NULL,1,NULL,NULL),(409,'Morbi','Gujarat',NULL,1,NULL,NULL),(410,'Porbandar','Gujarat',NULL,1,NULL,NULL),(411,'Veraval','Gujarat',NULL,1,NULL,NULL),(412,'Bhuj','Gujarat',NULL,1,NULL,NULL),(413,'Godhra','Gujarat',NULL,1,NULL,NULL),(414,'Patan','Gujarat',NULL,1,NULL,NULL),(415,'Amreli','Gujarat',NULL,1,NULL,NULL),(416,'Botad','Gujarat',NULL,1,NULL,NULL),(417,'Dahod','Gujarat',NULL,1,NULL,NULL),(418,'Gondal','Gujarat',NULL,1,NULL,NULL),(419,'Jetpur','Gujarat',NULL,1,NULL,NULL),(420,'Kalol','Gujarat',NULL,1,NULL,NULL),(421,'Surendranagar','Gujarat',NULL,1,NULL,NULL),(422,'Gurugram','Haryana',NULL,1,NULL,NULL),(423,'Faridabad','Haryana',NULL,1,NULL,NULL),(424,'Panipat','Haryana',NULL,1,NULL,NULL),(425,'Karnal','Haryana',NULL,1,NULL,NULL),(426,'Ambala','Haryana',NULL,1,NULL,NULL),(427,'Hisar','Haryana',NULL,1,NULL,NULL),(428,'Rohtak','Haryana',NULL,1,NULL,NULL),(429,'Sonipat','Haryana',NULL,1,NULL,NULL),(430,'Yamunanagar','Haryana',NULL,1,NULL,NULL),(431,'Panchkula','Haryana',NULL,1,NULL,NULL),(432,'Kurukshetra','Haryana',NULL,1,NULL,NULL),(433,'Bhiwani','Haryana',NULL,1,NULL,NULL),(434,'Sirsa','Haryana',NULL,1,NULL,NULL),(435,'Jind','Haryana',NULL,1,NULL,NULL),(436,'Kaithal','Haryana',NULL,1,NULL,NULL),(437,'Bahadurgarh','Haryana',NULL,1,NULL,NULL),(438,'Rewari','Haryana',NULL,1,NULL,NULL),(439,'Palwal','Haryana',NULL,1,NULL,NULL),(440,'Narnaul','Haryana',NULL,1,NULL,NULL),(441,'Fatehabad','Haryana',NULL,1,NULL,NULL),(442,'Shimla','Himachal Pradesh',NULL,1,NULL,NULL),(443,'Dharamshala','Himachal Pradesh',NULL,1,NULL,NULL),(444,'Solan','Himachal Pradesh',NULL,1,NULL,NULL),(445,'Mandi','Himachal Pradesh',NULL,1,NULL,NULL),(446,'Kullu','Himachal Pradesh',NULL,1,NULL,NULL),(447,'Manali','Himachal Pradesh',NULL,1,NULL,NULL),(448,'Bilaspur','Himachal Pradesh',NULL,1,NULL,NULL),(449,'Hamirpur','Himachal Pradesh',NULL,1,NULL,NULL),(450,'Una','Himachal Pradesh',NULL,1,NULL,NULL),(451,'Chamba','Himachal Pradesh',NULL,1,NULL,NULL),(452,'Kangra','Himachal Pradesh',NULL,1,NULL,NULL),(453,'Nahan','Himachal Pradesh',NULL,1,NULL,NULL),(454,'Palampur','Himachal Pradesh',NULL,1,NULL,NULL),(455,'Baddi','Himachal Pradesh',NULL,1,NULL,NULL),(456,'Sundarnagar','Himachal Pradesh',NULL,1,NULL,NULL),(457,'Kasauli','Himachal Pradesh',NULL,1,NULL,NULL),(458,'Srinagar','Jammu & Kashmir',NULL,1,NULL,NULL),(459,'Jammu','Jammu & Kashmir',NULL,1,NULL,NULL),(460,'Anantnag','Jammu & Kashmir',NULL,1,NULL,NULL),(461,'Baramulla','Jammu & Kashmir',NULL,1,NULL,NULL),(462,'Kathua','Jammu & Kashmir',NULL,1,NULL,NULL),(463,'Udhampur','Jammu & Kashmir',NULL,1,NULL,NULL),(464,'Pulwama','Jammu & Kashmir',NULL,1,NULL,NULL),(465,'Kupwara','Jammu & Kashmir',NULL,1,NULL,NULL),(466,'Shopian','Jammu & Kashmir',NULL,1,NULL,NULL),(467,'Rajouri','Jammu & Kashmir',NULL,1,NULL,NULL),(468,'Poonch','Jammu & Kashmir',NULL,1,NULL,NULL),(469,'Sopore','Jammu & Kashmir',NULL,1,NULL,NULL),(470,'Budgam','Jammu & Kashmir',NULL,1,NULL,NULL),(471,'Kulgam','Jammu & Kashmir',NULL,1,NULL,NULL),(472,'Ganderbal','Jammu & Kashmir',NULL,1,NULL,NULL),(473,'Ranchi','Jharkhand',NULL,1,NULL,NULL),(474,'Jamshedpur','Jharkhand',NULL,1,NULL,NULL),(475,'Dhanbad','Jharkhand',NULL,1,NULL,NULL),(476,'Bokaro','Jharkhand',NULL,1,NULL,NULL),(477,'Deoghar','Jharkhand',NULL,1,NULL,NULL),(478,'Hazaribagh','Jharkhand',NULL,1,NULL,NULL),(479,'Giridih','Jharkhand',NULL,1,NULL,NULL),(480,'Ramgarh','Jharkhand',NULL,1,NULL,NULL),(481,'Medininagar','Jharkhand',NULL,1,NULL,NULL),(482,'Phusro','Jharkhand',NULL,1,NULL,NULL),(483,'Chaibasa','Jharkhand',NULL,1,NULL,NULL),(484,'Dumka','Jharkhand',NULL,1,NULL,NULL),(485,'Godda','Jharkhand',NULL,1,NULL,NULL),(486,'Sahibganj','Jharkhand',NULL,1,NULL,NULL),(487,'Gumla','Jharkhand',NULL,1,NULL,NULL),(488,'Lohardaga','Jharkhand',NULL,1,NULL,NULL),(489,'Pakur','Jharkhand',NULL,1,NULL,NULL),(490,'Bengaluru','Karnataka',NULL,1,NULL,NULL),(491,'Mysuru','Karnataka',NULL,1,NULL,NULL),(492,'Hubballi','Karnataka',NULL,1,NULL,NULL),(493,'Dharwad','Karnataka',NULL,1,NULL,NULL),(494,'Mangaluru','Karnataka',NULL,1,NULL,NULL),(495,'Belagavi','Karnataka',NULL,1,NULL,NULL),(496,'Ballari','Karnataka',NULL,1,NULL,NULL),(497,'Shivamogga','Karnataka',NULL,1,NULL,NULL),(498,'Tumakuru','Karnataka',NULL,1,NULL,NULL),(499,'Udupi','Karnataka',NULL,1,NULL,NULL),(500,'Davanagere','Karnataka',NULL,1,NULL,NULL),(501,'Kalaburagi','Karnataka',NULL,1,NULL,NULL),(502,'Bidar','Karnataka',NULL,1,NULL,NULL),(503,'Vijayapura','Karnataka',NULL,1,NULL,NULL),(504,'Raichur','Karnataka',NULL,1,NULL,NULL),(505,'Hassan','Karnataka',NULL,1,NULL,NULL),(506,'Mandya','Karnataka',NULL,1,NULL,NULL),(507,'Kolar','Karnataka',NULL,1,NULL,NULL),(508,'Chikkamagaluru','Karnataka',NULL,1,NULL,NULL),(509,'Karwar','Karnataka',NULL,1,NULL,NULL),(510,'Hosapete','Karnataka',NULL,1,NULL,NULL),(511,'Bagalkot','Karnataka',NULL,1,NULL,NULL),(512,'Gadag','Karnataka',NULL,1,NULL,NULL),(513,'Haveri','Karnataka',NULL,1,NULL,NULL),(514,'Chitradurga','Karnataka',NULL,1,NULL,NULL),(515,'Ramanagara','Karnataka',NULL,1,NULL,NULL),(516,'Yadgir','Karnataka',NULL,1,NULL,NULL),(517,'Thiruvananthapuram','Kerala',NULL,1,NULL,NULL),(518,'Kochi','Kerala',NULL,1,NULL,NULL),(519,'Kozhikode','Kerala',NULL,1,NULL,NULL),(520,'Thrissur','Kerala',NULL,1,NULL,NULL),(521,'Kollam','Kerala',NULL,1,NULL,NULL),(522,'Kannur','Kerala',NULL,1,NULL,NULL),(523,'Alappuzha','Kerala',NULL,1,NULL,NULL),(524,'Palakkad','Kerala',NULL,1,NULL,NULL),(525,'Kottayam','Kerala',NULL,1,NULL,NULL),(526,'Malappuram','Kerala',NULL,1,NULL,NULL),(527,'Pathanamthitta','Kerala',NULL,1,NULL,NULL),(528,'Kasaragod','Kerala',NULL,1,NULL,NULL),(529,'Idukki','Kerala',NULL,1,NULL,NULL),(530,'Wayanad','Kerala',NULL,1,NULL,NULL),(531,'Vatakara','Kerala',NULL,1,NULL,NULL),(532,'Perinthalmanna','Kerala',NULL,1,NULL,NULL),(533,'Changanassery','Kerala',NULL,1,NULL,NULL),(534,'Ponnani','Kerala',NULL,1,NULL,NULL),(535,'Neyyattinkara','Kerala',NULL,1,NULL,NULL),(536,'Kayamkulam','Kerala',NULL,1,NULL,NULL),(537,'Leh','Ladakh',NULL,1,NULL,NULL),(538,'Kargil','Ladakh',NULL,1,NULL,NULL),(539,'Nubra','Ladakh',NULL,1,NULL,NULL),(540,'Diskit','Ladakh',NULL,1,NULL,NULL),(541,'Zanskar','Ladakh',NULL,1,NULL,NULL),(542,'Kavaratti','Lakshadweep',NULL,1,NULL,NULL),(543,'Agatti','Lakshadweep',NULL,1,NULL,NULL),(544,'Amini','Lakshadweep',NULL,1,NULL,NULL),(545,'Andrott','Lakshadweep',NULL,1,NULL,NULL),(546,'Kadmat','Lakshadweep',NULL,1,NULL,NULL),(547,'Kalpeni','Lakshadweep',NULL,1,NULL,NULL),(548,'Minicoy','Lakshadweep',NULL,1,NULL,NULL),(549,'Bhopal','Madhya Pradesh',NULL,1,NULL,NULL),(550,'Indore','Madhya Pradesh',NULL,1,NULL,NULL),(551,'Jabalpur','Madhya Pradesh',NULL,1,NULL,NULL),(552,'Gwalior','Madhya Pradesh',NULL,1,NULL,NULL),(553,'Ujjain','Madhya Pradesh',NULL,1,NULL,NULL),(554,'Sagar','Madhya Pradesh',NULL,1,NULL,NULL),(555,'Satna','Madhya Pradesh',NULL,1,NULL,NULL),(556,'Ratlam','Madhya Pradesh',NULL,1,NULL,NULL),(557,'Rewa','Madhya Pradesh',NULL,1,NULL,NULL),(558,'Dewas','Madhya Pradesh',NULL,1,NULL,NULL),(559,'Burhanpur','Madhya Pradesh',NULL,1,NULL,NULL),(560,'Khandwa','Madhya Pradesh',NULL,1,NULL,NULL),(561,'Chhindwara','Madhya Pradesh',NULL,1,NULL,NULL),(562,'Katni','Madhya Pradesh',NULL,1,NULL,NULL),(563,'Shivpuri','Madhya Pradesh',NULL,1,NULL,NULL),(564,'Vidisha','Madhya Pradesh',NULL,1,NULL,NULL),(565,'Singrauli','Madhya Pradesh',NULL,1,NULL,NULL),(566,'Sehore','Madhya Pradesh',NULL,1,NULL,NULL),(567,'Neemuch','Madhya Pradesh',NULL,1,NULL,NULL),(568,'Mandsaur','Madhya Pradesh',NULL,1,NULL,NULL),(569,'Itarsi','Madhya Pradesh',NULL,1,NULL,NULL),(570,'Betul','Madhya Pradesh',NULL,1,NULL,NULL),(571,'Datia','Madhya Pradesh',NULL,1,NULL,NULL),(572,'Morena','Madhya Pradesh',NULL,1,NULL,NULL),(573,'Bhind','Madhya Pradesh',NULL,1,NULL,NULL),(574,'Hoshangabad','Madhya Pradesh',NULL,1,NULL,NULL),(575,'Damoh','Madhya Pradesh',NULL,1,NULL,NULL),(576,'Imphal','Manipur',NULL,1,NULL,NULL),(577,'Thoubal','Manipur',NULL,1,NULL,NULL),(578,'Bishnupur','Manipur',NULL,1,NULL,NULL),(579,'Churachandpur','Manipur',NULL,1,NULL,NULL),(580,'Kakching','Manipur',NULL,1,NULL,NULL),(581,'Ukhrul','Manipur',NULL,1,NULL,NULL),(582,'Senapati','Manipur',NULL,1,NULL,NULL),(583,'Tamenglong','Manipur',NULL,1,NULL,NULL),(584,'Jiribam','Manipur',NULL,1,NULL,NULL),(585,'Moreh','Manipur',NULL,1,NULL,NULL),(586,'Moirang','Manipur',NULL,1,NULL,NULL),(587,'Shillong','Meghalaya',NULL,1,NULL,NULL),(588,'Tura','Meghalaya',NULL,1,NULL,NULL),(589,'Jowai','Meghalaya',NULL,1,NULL,NULL),(590,'Nongstoin','Meghalaya',NULL,1,NULL,NULL),(591,'Baghmara','Meghalaya',NULL,1,NULL,NULL),(592,'Williamnagar','Meghalaya',NULL,1,NULL,NULL),(593,'Resubelpara','Meghalaya',NULL,1,NULL,NULL),(594,'Mawkyrwat','Meghalaya',NULL,1,NULL,NULL),(595,'Khliehriat','Meghalaya',NULL,1,NULL,NULL),(596,'Nongpoh','Meghalaya',NULL,1,NULL,NULL),(597,'Aizawl','Mizoram',NULL,1,NULL,NULL),(598,'Lunglei','Mizoram',NULL,1,NULL,NULL),(599,'Champhai','Mizoram',NULL,1,NULL,NULL),(600,'Kolasib','Mizoram',NULL,1,NULL,NULL),(601,'Serchhip','Mizoram',NULL,1,NULL,NULL),(602,'Saiha','Mizoram',NULL,1,NULL,NULL),(603,'Lawngtlai','Mizoram',NULL,1,NULL,NULL),(604,'Mamit','Mizoram',NULL,1,NULL,NULL),(605,'Saitual','Mizoram',NULL,1,NULL,NULL),(606,'Khawzawl','Mizoram',NULL,1,NULL,NULL),(607,'Kohima','Nagaland',NULL,1,NULL,NULL),(608,'Dimapur','Nagaland',NULL,1,NULL,NULL),(609,'Mokokchung','Nagaland',NULL,1,NULL,NULL),(610,'Tuensang','Nagaland',NULL,1,NULL,NULL),(611,'Wokha','Nagaland',NULL,1,NULL,NULL),(612,'Zunheboto','Nagaland',NULL,1,NULL,NULL),(613,'Mon','Nagaland',NULL,1,NULL,NULL),(614,'Phek','Nagaland',NULL,1,NULL,NULL),(615,'Longleng','Nagaland',NULL,1,NULL,NULL),(616,'Kiphire','Nagaland',NULL,1,NULL,NULL),(617,'Bhubaneswar','Odisha',NULL,1,NULL,NULL),(618,'Cuttack','Odisha',NULL,1,NULL,NULL),(619,'Rourkela','Odisha',NULL,1,NULL,NULL),(620,'Sambalpur','Odisha',NULL,1,NULL,NULL),(621,'Berhampur','Odisha',NULL,1,NULL,NULL),(622,'Balasore','Odisha',NULL,1,NULL,NULL),(623,'Puri','Odisha',NULL,1,NULL,NULL),(624,'Bhadrak','Odisha',NULL,1,NULL,NULL),(625,'Baripada','Odisha',NULL,1,NULL,NULL),(626,'Jharsuguda','Odisha',NULL,1,NULL,NULL),(627,'Jeypore','Odisha',NULL,1,NULL,NULL),(628,'Angul','Odisha',NULL,1,NULL,NULL),(629,'Dhenkanal','Odisha',NULL,1,NULL,NULL),(630,'Kendrapara','Odisha',NULL,1,NULL,NULL),(631,'Paradip','Odisha',NULL,1,NULL,NULL),(632,'Rayagada','Odisha',NULL,1,NULL,NULL),(633,'Koraput','Odisha',NULL,1,NULL,NULL),(634,'Balangir','Odisha',NULL,1,NULL,NULL),(635,'Bargarh','Odisha',NULL,1,NULL,NULL),(636,'Keonjhar','Odisha',NULL,1,NULL,NULL),(637,'Puducherry','Puducherry',NULL,1,NULL,NULL),(638,'Karaikal','Puducherry',NULL,1,NULL,NULL),(639,'Mahe','Puducherry',NULL,1,NULL,NULL),(640,'Yanam','Puducherry',NULL,1,NULL,NULL),(641,'Amritsar','Punjab',NULL,1,NULL,NULL),(642,'Ludhiana','Punjab',NULL,1,NULL,NULL),(643,'Jalandhar','Punjab',NULL,1,NULL,NULL),(644,'Patiala','Punjab',NULL,1,NULL,NULL),(645,'Bathinda','Punjab',NULL,1,NULL,NULL),(646,'Mohali','Punjab',NULL,1,NULL,NULL),(647,'Hoshiarpur','Punjab',NULL,1,NULL,NULL),(648,'Pathankot','Punjab',NULL,1,NULL,NULL),(649,'Moga','Punjab',NULL,1,NULL,NULL),(650,'Batala','Punjab',NULL,1,NULL,NULL),(651,'Abohar','Punjab',NULL,1,NULL,NULL),(652,'Khanna','Punjab',NULL,1,NULL,NULL),(653,'Phagwara','Punjab',NULL,1,NULL,NULL),(654,'Firozpur','Punjab',NULL,1,NULL,NULL),(655,'Kapurthala','Punjab',NULL,1,NULL,NULL),(656,'Sangrur','Punjab',NULL,1,NULL,NULL),(657,'Barnala','Punjab',NULL,1,NULL,NULL),(658,'Faridkot','Punjab',NULL,1,NULL,NULL),(659,'Malerkotla','Punjab',NULL,1,NULL,NULL),(660,'Rupnagar','Punjab',NULL,1,NULL,NULL),(661,'Jaipur','Rajasthan',NULL,1,NULL,NULL),(662,'Jodhpur','Rajasthan',NULL,1,NULL,NULL),(663,'Udaipur','Rajasthan',NULL,1,NULL,NULL),(664,'Kota','Rajasthan',NULL,1,NULL,NULL),(665,'Ajmer','Rajasthan',NULL,1,NULL,NULL),(666,'Bikaner','Rajasthan',NULL,1,NULL,NULL),(667,'Alwar','Rajasthan',NULL,1,NULL,NULL),(668,'Bharatpur','Rajasthan',NULL,1,NULL,NULL),(669,'Sikar','Rajasthan',NULL,1,NULL,NULL),(670,'Pali','Rajasthan',NULL,1,NULL,NULL),(671,'Sri Ganganagar','Rajasthan',NULL,1,NULL,NULL),(672,'Bhilwara','Rajasthan',NULL,1,NULL,NULL),(673,'Tonk','Rajasthan',NULL,1,NULL,NULL),(674,'Churu','Rajasthan',NULL,1,NULL,NULL),(675,'Jhunjhunu','Rajasthan',NULL,1,NULL,NULL),(676,'Barmer','Rajasthan',NULL,1,NULL,NULL),(677,'Nagaur','Rajasthan',NULL,1,NULL,NULL),(678,'Hanumangarh','Rajasthan',NULL,1,NULL,NULL),(679,'Dholpur','Rajasthan',NULL,1,NULL,NULL),(680,'Jaisalmer','Rajasthan',NULL,1,NULL,NULL),(681,'Gangtok','Sikkim',NULL,1,NULL,NULL),(682,'Namchi','Sikkim',NULL,1,NULL,NULL),(683,'Gyalshing','Sikkim',NULL,1,NULL,NULL),(684,'Mangan','Sikkim',NULL,1,NULL,NULL),(685,'Singtam','Sikkim',NULL,1,NULL,NULL),(686,'Rangpo','Sikkim',NULL,1,NULL,NULL),(687,'Jorethang','Sikkim',NULL,1,NULL,NULL),(688,'Pakyong','Sikkim',NULL,1,NULL,NULL),(689,'Ravangla','Sikkim',NULL,1,NULL,NULL),(690,'Chennai','Tamil Nadu',NULL,1,NULL,NULL),(691,'Coimbatore','Tamil Nadu',NULL,1,NULL,NULL),(692,'Madurai','Tamil Nadu',NULL,1,NULL,NULL),(693,'Salem','Tamil Nadu',NULL,1,NULL,NULL),(694,'Tiruchirappalli','Tamil Nadu',NULL,1,NULL,NULL),(695,'Tiruppur','Tamil Nadu',NULL,1,NULL,NULL),(696,'Erode','Tamil Nadu',NULL,1,NULL,NULL),(697,'Vellore','Tamil Nadu',NULL,1,NULL,NULL),(698,'Thoothukudi','Tamil Nadu',NULL,1,NULL,NULL),(699,'Dindigul','Tamil Nadu',NULL,1,NULL,NULL),(700,'Thanjavur','Tamil Nadu',NULL,1,NULL,NULL),(701,'Nagercoil','Tamil Nadu',NULL,1,NULL,NULL),(702,'Karur','Tamil Nadu',NULL,1,NULL,NULL),(703,'Kanchipuram','Tamil Nadu',NULL,1,NULL,NULL),(704,'Cuddalore','Tamil Nadu',NULL,1,NULL,NULL),(705,'Hosur','Tamil Nadu',NULL,1,NULL,NULL),(706,'Sivakasi','Tamil Nadu',NULL,1,NULL,NULL),(707,'Namakkal','Tamil Nadu',NULL,1,NULL,NULL),(708,'Tirunelveli','Tamil Nadu',NULL,1,NULL,NULL),(709,'Pollachi','Tamil Nadu',NULL,1,NULL,NULL),(710,'Hyderabad','Telangana',NULL,1,NULL,NULL),(711,'Warangal','Telangana',NULL,1,NULL,NULL),(712,'Karimnagar','Telangana',NULL,1,NULL,NULL),(713,'Nizamabad','Telangana',NULL,1,NULL,NULL),(714,'Khammam','Telangana',NULL,1,NULL,NULL),(715,'Ramagundam','Telangana',NULL,1,NULL,NULL),(716,'Mahbubnagar','Telangana',NULL,1,NULL,NULL),(717,'Siddipet','Telangana',NULL,1,NULL,NULL),(718,'Suryapet','Telangana',NULL,1,NULL,NULL),(719,'Adilabad','Telangana',NULL,1,NULL,NULL),(720,'Miryalaguda','Telangana',NULL,1,NULL,NULL),(721,'Nalgonda','Telangana',NULL,1,NULL,NULL),(722,'Mancherial','Telangana',NULL,1,NULL,NULL),(723,'Sangareddy','Telangana',NULL,1,NULL,NULL),(724,'Kamareddy','Telangana',NULL,1,NULL,NULL),(725,'Jagtial','Telangana',NULL,1,NULL,NULL),(726,'Medak','Telangana',NULL,1,NULL,NULL),(727,'Wanaparthy','Telangana',NULL,1,NULL,NULL),(728,'Vikarabad','Telangana',NULL,1,NULL,NULL),(729,'Kothagudem','Telangana',NULL,1,NULL,NULL),(730,'Agartala','Tripura',NULL,1,NULL,NULL),(731,'Udaipur','Tripura',NULL,1,NULL,NULL),(732,'Dharmanagar','Tripura',NULL,1,NULL,NULL),(733,'Kailasahar','Tripura',NULL,1,NULL,NULL),(734,'Belonia','Tripura',NULL,1,NULL,NULL),(735,'Khowai','Tripura',NULL,1,NULL,NULL),(736,'Ambassa','Tripura',NULL,1,NULL,NULL),(737,'Sonamura','Tripura',NULL,1,NULL,NULL),(738,'Teliamura','Tripura',NULL,1,NULL,NULL),(739,'Sabroom','Tripura',NULL,1,NULL,NULL),(740,'Lucknow','Uttar Pradesh',NULL,1,NULL,NULL),(741,'Kanpur','Uttar Pradesh',NULL,1,NULL,NULL),(742,'Varanasi','Uttar Pradesh',NULL,1,NULL,NULL),(743,'Prayagraj','Uttar Pradesh',NULL,1,NULL,NULL),(744,'Agra','Uttar Pradesh',NULL,1,NULL,NULL),(745,'Meerut','Uttar Pradesh',NULL,1,NULL,NULL),(746,'Ghaziabad','Uttar Pradesh',NULL,1,NULL,NULL),(747,'Noida','Uttar Pradesh',NULL,1,NULL,NULL),(748,'Greater Noida','Uttar Pradesh',NULL,1,NULL,NULL),(749,'Aligarh','Uttar Pradesh',NULL,1,NULL,NULL),(750,'Moradabad','Uttar Pradesh',NULL,1,NULL,NULL),(751,'Bareilly','Uttar Pradesh',NULL,1,NULL,NULL),(752,'Gorakhpur','Uttar Pradesh',NULL,1,NULL,NULL),(753,'Jhansi','Uttar Pradesh',NULL,1,NULL,NULL),(754,'Mathura','Uttar Pradesh',NULL,1,NULL,NULL),(755,'Ayodhya','Uttar Pradesh',NULL,1,NULL,NULL),(756,'Saharanpur','Uttar Pradesh',NULL,1,NULL,NULL),(757,'Muzaffarnagar','Uttar Pradesh',NULL,1,NULL,NULL),(758,'Firozabad','Uttar Pradesh',NULL,1,NULL,NULL),(759,'Shahjahanpur','Uttar Pradesh',NULL,1,NULL,NULL),(760,'Rampur','Uttar Pradesh',NULL,1,NULL,NULL),(761,'Basti','Uttar Pradesh',NULL,1,NULL,NULL),(762,'Sitapur','Uttar Pradesh',NULL,1,NULL,NULL),(763,'Unnao','Uttar Pradesh',NULL,1,NULL,NULL),(764,'Raebareli','Uttar Pradesh',NULL,1,NULL,NULL),(765,'Etawah','Uttar Pradesh',NULL,1,NULL,NULL),(766,'Mainpuri','Uttar Pradesh',NULL,1,NULL,NULL),(767,'Bulandshahr','Uttar Pradesh',NULL,1,NULL,NULL),(768,'Hapur','Uttar Pradesh',NULL,1,NULL,NULL),(769,'Amroha','Uttar Pradesh',NULL,1,NULL,NULL),(770,'Ghazipur','Uttar Pradesh',NULL,1,NULL,NULL),(771,'Jaunpur','Uttar Pradesh',NULL,1,NULL,NULL),(772,'Mirzapur','Uttar Pradesh',NULL,1,NULL,NULL),(773,'Azamgarh','Uttar Pradesh',NULL,1,NULL,NULL),(774,'Ballia','Uttar Pradesh',NULL,1,NULL,NULL),(775,'Deoria','Uttar Pradesh',NULL,1,NULL,NULL),(776,'Mau','Uttar Pradesh',NULL,1,NULL,NULL),(777,'Sultanpur','Uttar Pradesh',NULL,1,NULL,NULL),(778,'Lakhimpur Kheri','Uttar Pradesh',NULL,1,NULL,NULL),(779,'Hardoi','Uttar Pradesh',NULL,1,NULL,NULL),(780,'Fatehpur','Uttar Pradesh',NULL,1,NULL,NULL),(781,'Orai','Uttar Pradesh',NULL,1,NULL,NULL),(782,'Banda','Uttar Pradesh',NULL,1,NULL,NULL),(783,'Dehradun','Uttarakhand',NULL,1,NULL,NULL),(784,'Haridwar','Uttarakhand',NULL,1,NULL,NULL),(785,'Roorkee','Uttarakhand',NULL,1,NULL,NULL),(786,'Haldwani','Uttarakhand',NULL,1,NULL,NULL),(787,'Rudrapur','Uttarakhand',NULL,1,NULL,NULL),(788,'Kashipur','Uttarakhand',NULL,1,NULL,NULL),(789,'Rishikesh','Uttarakhand',NULL,1,NULL,NULL),(790,'Nainital','Uttarakhand',NULL,1,NULL,NULL),(791,'Almora','Uttarakhand',NULL,1,NULL,NULL),(792,'Pithoragarh','Uttarakhand',NULL,1,NULL,NULL),(793,'Kotdwar','Uttarakhand',NULL,1,NULL,NULL),(794,'Srinagar','Uttarakhand',NULL,1,NULL,NULL),(795,'Tehri','Uttarakhand',NULL,1,NULL,NULL),(796,'Chamoli','Uttarakhand',NULL,1,NULL,NULL),(797,'Bageshwar','Uttarakhand',NULL,1,NULL,NULL),(798,'Uttarkashi','Uttarakhand',NULL,1,NULL,NULL),(799,'Ramnagar','Uttarakhand',NULL,1,NULL,NULL),(800,'Kolkata','West Bengal',NULL,1,NULL,NULL),(801,'Howrah','West Bengal',NULL,1,NULL,NULL),(802,'Durgapur','West Bengal',NULL,1,NULL,NULL),(803,'Asansol','West Bengal',NULL,1,NULL,NULL),(804,'Siliguri','West Bengal',NULL,1,NULL,NULL),(805,'Kharagpur','West Bengal',NULL,1,NULL,NULL),(806,'Haldia','West Bengal',NULL,1,NULL,NULL),(807,'Bardhaman','West Bengal',NULL,1,NULL,NULL),(808,'Malda','West Bengal',NULL,1,NULL,NULL),(809,'Berhampore','West Bengal',NULL,1,NULL,NULL),(810,'Krishnanagar','West Bengal',NULL,1,NULL,NULL),(811,'Raiganj','West Bengal',NULL,1,NULL,NULL),(812,'Balurghat','West Bengal',NULL,1,NULL,NULL),(813,'Purulia','West Bengal',NULL,1,NULL,NULL),(814,'Bankura','West Bengal',NULL,1,NULL,NULL),(815,'Darjeeling','West Bengal',NULL,1,NULL,NULL),(816,'Jalpaiguri','West Bengal',NULL,1,NULL,NULL),(817,'Cooch Behar','West Bengal',NULL,1,NULL,NULL),(818,'Alipurduar','West Bengal',NULL,1,NULL,NULL),(819,'Chandannagar','West Bengal',NULL,1,NULL,NULL),(820,'Barrackpore','West Bengal',NULL,1,NULL,NULL),(821,'Habra','West Bengal',NULL,1,NULL,NULL),(822,'Barasat','West Bengal',NULL,1,NULL,NULL),(823,'Midnapore','West Bengal',NULL,1,NULL,NULL);
/*!40000 ALTER TABLE `city_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_submissions`
--

DROP TABLE IF EXISTS `contact_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_submissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cs_email` (`email`),
  KEY `idx_cs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_submissions`
--

LOCK TABLES `contact_submissions` WRITE;
/*!40000 ALTER TABLE `contact_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conversations`
--

DROP TABLE IF EXISTS `conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversations` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `candidate_id` bigint NOT NULL,
  `firm_id` bigint NOT NULL,
  `initiated_by` enum('candidate','firm') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','active','ignored','blocked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `last_message_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_conversation_pair` (`candidate_id`,`firm_id`),
  KEY `idx_conv_firm` (`firm_id`,`status`,`last_message_at`),
  KEY `idx_conv_candidate` (`candidate_id`,`status`,`last_message_at`),
  CONSTRAINT `fk_conv_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_conv_firm` FOREIGN KEY (`firm_id`) REFERENCES `firm_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversations`
--

LOCK TABLES `conversations` WRITE;
/*!40000 ALTER TABLE `conversations` DISABLE KEYS */;
/*!40000 ALTER TABLE `conversations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupon_usages`
--

DROP TABLE IF EXISTS `coupon_usages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupon_usages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `coupon_id` bigint unsigned NOT NULL,
  `firm_id` bigint unsigned NOT NULL,
  `firm_subscription_id` bigint unsigned NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_coupon_id` (`coupon_id`),
  KEY `idx_firm_id` (`firm_id`),
  KEY `idx_firm_subscription_id` (`firm_subscription_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupon_usages`
--

LOCK TABLES `coupon_usages` WRITE;
/*!40000 ALTER TABLE `coupon_usages` DISABLE KEYS */;
/*!40000 ALTER TABLE `coupon_usages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(100) NOT NULL,
  `type` enum('flat','percentage') NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int DEFAULT NULL,
  `used_count` int DEFAULT '0',
  `expires_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupons`
--

LOCK TABLES `coupons` WRITE;
/*!40000 ALTER TABLE `coupons` DISABLE KEYS */;
/*!40000 ALTER TABLE `coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `creator_bank_details`
--

DROP TABLE IF EXISTS `creator_bank_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `creator_bank_details` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `creator_id` bigint NOT NULL,
  `account_holder_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bank_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_number` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ifsc_code` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cbd_creator` (`creator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `creator_bank_details`
--

LOCK TABLES `creator_bank_details` WRITE;
/*!40000 ALTER TABLE `creator_bank_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `creator_bank_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `creator_engagement_payments`
--

DROP TABLE IF EXISTS `creator_engagement_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `creator_engagement_payments` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `engagement_id` bigint NOT NULL,
  `firm_id` bigint NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'INR',
  `payment_method` enum('razorpay','phonepe','manual') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','paid','awaiting_verification','verified','escrow_held','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `gateway_order_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gateway_payment_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gateway_signature` text COLLATE utf8mb4_unicode_ci,
  `gateway_response` json DEFAULT NULL,
  `utr_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `screenshot_url` text COLLATE utf8mb4_unicode_ci,
  `payment_date` date DEFAULT NULL,
  `admin_remarks` text COLLATE utf8mb4_unicode_ci,
  `reviewed_by` bigint DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cep_engagement` (`engagement_id`),
  KEY `idx_cep_firm_status` (`firm_id`,`status`),
  KEY `idx_cep_gateway_order` (`gateway_order_id`),
  KEY `idx_cep_status` (`status`),
  CONSTRAINT `fk_cep_engagement` FOREIGN KEY (`engagement_id`) REFERENCES `creator_engagements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `creator_engagement_payments`
--

LOCK TABLES `creator_engagement_payments` WRITE;
/*!40000 ALTER TABLE `creator_engagement_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `creator_engagement_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `creator_engagements`
--

DROP TABLE IF EXISTS `creator_engagements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `creator_engagements` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `creator_requirement_id` bigint NOT NULL,
  `bid_id` bigint NOT NULL,
  `creator_id` bigint NOT NULL,
  `firm_id` bigint NOT NULL,
  `accepted_bid_amount` decimal(12,2) NOT NULL,
  `delivery_days` smallint unsigned NOT NULL DEFAULT '7',
  `status` enum('awaiting_payment','payment_pending','active','submitted','revision_requested','resubmitted','approved','payout_pending','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'awaiting_payment',
  `creator_accepted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_engagement_bid` (`bid_id`),
  KEY `idx_ce_creator_status` (`creator_id`,`status`),
  KEY `idx_ce_firm_status` (`firm_id`,`status`),
  KEY `idx_ce_req` (`creator_requirement_id`),
  CONSTRAINT `fk_ce_bid` FOREIGN KEY (`bid_id`) REFERENCES `creator_project_bids` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ce_req` FOREIGN KEY (`creator_requirement_id`) REFERENCES `creator_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `creator_engagements`
--

LOCK TABLES `creator_engagements` WRITE;
/*!40000 ALTER TABLE `creator_engagements` DISABLE KEYS */;
/*!40000 ALTER TABLE `creator_engagements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `creator_marketplace_notifications`
--

DROP TABLE IF EXISTS `creator_marketplace_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `creator_marketplace_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci,
  `data` json DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cmn_user_unread` (`user_id`,`read_at`),
  KEY `idx_cmn_user_created` (`user_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `creator_marketplace_notifications`
--

LOCK TABLES `creator_marketplace_notifications` WRITE;
/*!40000 ALTER TABLE `creator_marketplace_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `creator_marketplace_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `creator_payouts`
--

DROP TABLE IF EXISTS `creator_payouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `creator_payouts` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `engagement_id` bigint NOT NULL,
  `creator_id` bigint NOT NULL,
  `gross_amount` decimal(12,2) NOT NULL,
  `commission_rate` decimal(5,2) NOT NULL DEFAULT '10.00',
  `commission_amount` decimal(12,2) NOT NULL,
  `net_amount` decimal(12,2) NOT NULL,
  `status` enum('pending','paid','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `transaction_reference` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `processed_by` bigint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cp_engagement` (`engagement_id`),
  KEY `idx_cp_creator_status` (`creator_id`,`status`),
  KEY `idx_cp_status` (`status`),
  CONSTRAINT `fk_cp_engagement` FOREIGN KEY (`engagement_id`) REFERENCES `creator_engagements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `creator_payouts`
--

LOCK TABLES `creator_payouts` WRITE;
/*!40000 ALTER TABLE `creator_payouts` DISABLE KEYS */;
/*!40000 ALTER TABLE `creator_payouts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `creator_project_bids`
--

DROP TABLE IF EXISTS `creator_project_bids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `creator_project_bids` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `project_id` bigint NOT NULL,
  `creator_id` bigint NOT NULL COMMENT 'users.id of the bidding creator',
  `bid_amount` decimal(12,2) NOT NULL,
  `delivery_days` smallint unsigned NOT NULL,
  `proposal` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `portfolio_links` json DEFAULT NULL,
  `status` enum('pending','shortlisted','selected','rejected','withdrawn','creator_declined') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_project_creator` (`project_id`,`creator_id`),
  KEY `idx_creator_status` (`creator_id`,`status`),
  KEY `idx_project_status` (`project_id`,`status`),
  CONSTRAINT `fk_cpb_creator` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cpb_project` FOREIGN KEY (`project_id`) REFERENCES `creator_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `creator_project_bids`
--

LOCK TABLES `creator_project_bids` WRITE;
/*!40000 ALTER TABLE `creator_project_bids` DISABLE KEYS */;
/*!40000 ALTER TABLE `creator_project_bids` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `creator_projects`
--

DROP TABLE IF EXISTS `creator_projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `creator_projects` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `firm_id` bigint NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `budget_type` enum('fixed','range','negotiable') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fixed',
  `budget_min` decimal(12,2) DEFAULT NULL,
  `budget_max` decimal(12,2) DEFAULT NULL,
  `delivery_days` smallint unsigned DEFAULT NULL,
  `skills_required` json DEFAULT NULL,
  `attachments` json DEFAULT NULL,
  `status` enum('draft','published','closed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_creator_projects_slug` (`slug`),
  KEY `idx_firm_status` (`firm_id`,`status`),
  KEY `idx_status_category` (`status`,`category`),
  KEY `idx_published_at` (`published_at`),
  CONSTRAINT `fk_creator_projects_firm` FOREIGN KEY (`firm_id`) REFERENCES `firm_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `creator_projects`
--

LOCK TABLES `creator_projects` WRITE;
/*!40000 ALTER TABLE `creator_projects` DISABLE KEYS */;
/*!40000 ALTER TABLE `creator_projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_logs`
--

DROP TABLE IF EXISTS `email_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_logs` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `recipient_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'student | firm | admin | user',
  `email_purpose` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'EmailPurpose enum value',
  `template_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Mail class short name',
  `sender_identity` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Sender key from config/email.php',
  `subject` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','sent','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email_purpose_status` (`email_purpose`,`status`),
  KEY `idx_recipient_created` (`recipient_email`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=159 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_logs`
--

LOCK TABLES `email_logs` WRITE;
/*!40000 ALTER TABLE `email_logs` DISABLE KEYS */;
INSERT INTO `email_logs` VALUES (1,'rituchandak7876@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-10 15:23:27','2026-06-10 15:23:24','2026-06-10 15:23:27'),(2,'rituchandak7876@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-10 15:25:55','2026-06-10 15:23:52','2026-06-10 15:25:55'),(3,'riteshchandak4648@gmail.com','firm','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-10 15:32:18','2026-06-10 15:32:16','2026-06-10 15:32:18'),(4,'riteshchandak4648@gmail.com','firm','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-10 15:35:27','2026-06-10 15:33:24','2026-06-10 15:35:27'),(5,'riteshchandak4648@gmail.com','firm','firm_approved','FirmApprovedMail','default','Your Firm Account Has Been Approved — Start Your Story','sent',NULL,'2026-06-10 15:37:25','2026-06-10 15:37:22','2026-06-10 15:37:25'),(6,'mardariddhi04@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-10 20:36:16','2026-06-10 20:36:16','2026-06-10 20:36:16'),(7,'mardariddhi04@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-10 20:38:47','2026-06-10 20:36:45','2026-06-10 20:38:47'),(8,'snehahake9@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-11 13:15:03','2026-06-11 13:15:03','2026-06-11 13:15:03'),(9,'snehahake9@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-11 13:19:33','2026-06-11 13:17:31','2026-06-11 13:19:33'),(10,'rohankolety23@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-11 22:30:24','2026-06-11 22:30:23','2026-06-11 22:30:24'),(11,'rohankolety23@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-11 22:33:15','2026-06-11 22:31:13','2026-06-11 22:33:15'),(12,'pratikkokadwar@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-12 14:30:57','2026-06-12 14:30:56','2026-06-12 14:30:57'),(13,'pratikkokadwar@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-12 14:33:48','2026-06-12 14:31:45','2026-06-12 14:33:48'),(14,'mayurikokil2510@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-12 14:38:41','2026-06-12 14:38:41','2026-06-12 14:38:41'),(15,'mayurikokil2510@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-12 14:41:24','2026-06-12 14:39:20','2026-06-12 14:41:24'),(16,'shraddhagharke16@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-12 17:01:49','2026-06-12 17:01:47','2026-06-12 17:01:49'),(17,'shraddhagharke16@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-12 17:02:33','2026-06-12 17:02:32','2026-06-12 17:02:33'),(18,'shraddhagharke16@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-12 17:05:28','2026-06-12 17:03:26','2026-06-12 17:05:28'),(19,'bhumigolani1@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-12 17:15:37','2026-06-12 17:15:36','2026-06-12 17:15:37'),(20,'bhumigolani1@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-12 17:16:23','2026-06-12 17:16:23','2026-06-12 17:16:23'),(21,'bhumigolani1@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-12 17:18:40','2026-06-12 17:16:37','2026-06-12 17:18:40'),(22,'rutujamundada96@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-12 17:28:20','2026-06-12 17:28:19','2026-06-12 17:28:20'),(23,'rutujamundada96@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-12 17:29:13','2026-06-12 17:29:12','2026-06-12 17:29:13'),(24,'rutujamundada96@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-12 17:31:26','2026-06-12 17:29:24','2026-06-12 17:31:26'),(25,'rituchandak7876@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-12 22:31:04','2026-06-12 22:31:02','2026-06-12 22:31:04'),(26,'rituchandak7876@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-12 22:33:29','2026-06-12 22:31:27','2026-06-12 22:33:29'),(27,'shraddhakoli1804@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-14 10:49:14','2026-06-14 10:49:13','2026-06-14 10:49:14'),(28,'shraddhakoli1804@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-14 10:51:51','2026-06-14 10:49:49','2026-06-14 10:51:51'),(29,'tusharbhise908@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-15 12:05:02','2026-06-15 12:05:01','2026-06-15 12:05:02'),(30,'tusharbhise908@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-15 12:07:36','2026-06-15 12:05:33','2026-06-15 12:07:36'),(31,'ymutha424@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-15 12:24:33','2026-06-15 12:24:33','2026-06-15 12:24:33'),(32,'ymutha424@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-15 12:24:53','2026-06-15 12:24:52','2026-06-15 12:24:53'),(33,'ymutha424@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-15 12:27:10','2026-06-15 12:25:08','2026-06-15 12:27:10'),(34,'anumitasingh0511@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-15 23:59:16','2026-06-15 23:59:15','2026-06-15 23:59:16'),(35,'anumitasingh0511@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-15 23:59:36','2026-06-15 23:59:36','2026-06-15 23:59:36'),(36,'anumitasingh0511@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-16 00:02:06','2026-06-16 00:00:03','2026-06-16 00:02:06'),(37,'pubggamer94442@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 00:32:49','2026-06-16 00:32:48','2026-06-16 00:32:49'),(38,'pubggamer94442@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 00:34:14','2026-06-16 00:34:13','2026-06-16 00:34:14'),(39,'pubggamer94442@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-16 00:37:12','2026-06-16 00:35:09','2026-06-16 00:37:12'),(40,'rashiagrawal0122@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 01:25:17','2026-06-16 01:25:17','2026-06-16 01:25:17'),(41,'rajmantri6@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 12:35:01','2026-06-16 12:35:00','2026-06-16 12:35:01'),(42,'rajmantri6@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-16 12:38:45','2026-06-16 12:35:41','2026-06-16 12:38:45'),(43,'adityagramesh2004@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 12:36:59','2026-06-16 12:36:57','2026-06-16 12:36:59'),(44,'adityagramesh2004@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 12:37:18','2026-06-16 12:37:17','2026-06-16 12:37:18'),(45,'adityagramesh2004@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-16 12:40:50','2026-06-16 12:38:48','2026-06-16 12:40:50'),(46,'krishna17jaju@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 13:21:30','2026-06-16 13:21:28','2026-06-16 13:21:30'),(47,'krishna17jaju@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 13:21:55','2026-06-16 13:21:54','2026-06-16 13:21:55'),(48,'krishna17jaju@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-16 13:24:06','2026-06-16 13:22:03','2026-06-16 13:24:06'),(49,'ak.anshamalpani13@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 13:39:48','2026-06-16 13:39:47','2026-06-16 13:39:48'),(50,'ak.anshamalpani13@gmail.com','creator','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-16 13:42:35','2026-06-16 13:40:31','2026-06-16 13:42:35'),(51,'capranavsancheti@gmail.com','firm','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 14:03:24','2026-06-16 14:03:23','2026-06-16 14:03:24'),(52,'capranavsancheti@gmail.com','firm','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-16 14:05:53','2026-06-16 14:03:50','2026-06-16 14:05:53'),(53,'prajwalalhat7728@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 14:04:02','2026-06-16 14:04:01','2026-06-16 14:04:02'),(54,'prajwalalhat7728@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-16 14:06:23','2026-06-16 14:04:21','2026-06-16 14:06:23'),(55,'dsangle659@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 14:16:54','2026-06-16 14:16:54','2026-06-16 14:16:54'),(56,'dsangle659@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 14:17:47','2026-06-16 14:17:46','2026-06-16 14:17:47'),(57,'dsangle659@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-16 14:20:19','2026-06-16 14:18:16','2026-06-16 14:20:19'),(58,'ishadhoka2000@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 14:51:06','2026-06-16 14:51:05','2026-06-16 14:51:06'),(59,'ishadhoka2000@gmail.com','user','password_reset','PasswordResetMail','verify','Reset Your Password — Start Your Story','sent',NULL,'2026-06-16 14:52:11','2026-06-16 14:52:09','2026-06-16 14:52:11'),(60,'ishadhoka2000@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 14:53:02','2026-06-16 14:53:02','2026-06-16 14:53:02'),(61,'ishadhoka2000@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-16 14:55:39','2026-06-16 14:53:36','2026-06-16 14:55:39'),(62,'kamal@caco.in','firm','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 17:29:37','2026-06-16 17:29:36','2026-06-16 17:29:37'),(63,'kamal@caco.in','user','password_reset','PasswordResetMail','verify','Reset Your Password — Start Your Story','sent',NULL,'2026-06-16 17:30:09','2026-06-16 17:30:06','2026-06-16 17:30:09'),(64,'kamal@caco.in','firm','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-16 17:32:23','2026-06-16 17:30:21','2026-06-16 17:32:23'),(65,'kamal@caco.in','user','password_reset','PasswordResetMail','verify','Reset Your Password — Start Your Story','sent',NULL,'2026-06-16 17:32:04','2026-06-16 17:31:02','2026-06-16 17:32:04'),(66,'bnst.ca@gmail.com','firm','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 17:37:44','2026-06-16 17:37:42','2026-06-16 17:37:44'),(67,'bnst.ca@gmail.com','firm','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 17:37:57','2026-06-16 17:37:55','2026-06-16 17:37:57'),(68,'bnst.ca@gmail.com','firm','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-16 17:40:06','2026-06-16 17:38:04','2026-06-16 17:40:06'),(69,'rekhani.saraogi@gmail.com','firm','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 18:10:34','2026-06-16 18:10:31','2026-06-16 18:10:34'),(70,'rekhani.saraogi@gmail.com','firm','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-16 18:14:00','2026-06-16 18:11:59','2026-06-16 18:14:00'),(71,'sumitsawant2311@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 18:24:49','2026-06-16 18:24:49','2026-06-16 18:24:49'),(72,'siddhesh99shinde@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 18:34:59','2026-06-16 18:34:59','2026-06-16 18:34:59'),(73,'siddhesh99shinde@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-16 18:37:35','2026-06-16 18:35:33','2026-06-16 18:37:35'),(74,'darshanpatni09@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 18:52:32','2026-06-16 18:52:31','2026-06-16 18:52:32'),(75,'darshanpatni09@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 18:52:56','2026-06-16 18:52:56','2026-06-16 18:52:56'),(76,'darshanpatni09@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-16 18:55:24','2026-06-16 18:53:22','2026-06-16 18:55:24'),(77,'minakshikusha@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 18:57:45','2026-06-16 18:57:44','2026-06-16 18:57:45'),(78,'kakdesuraj383@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 18:57:56','2026-06-16 18:57:55','2026-06-16 18:57:56'),(79,'minakshikusha@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-16 19:00:01','2026-06-16 18:57:59','2026-06-16 19:00:01'),(80,'kakdesuraj383@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-16 19:01:56','2026-06-16 18:59:54','2026-06-16 19:01:56'),(81,'sanchitanagwani@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 19:10:09','2026-06-16 19:10:08','2026-06-16 19:10:09'),(82,'sanchitanagwani@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-16 19:13:18','2026-06-16 19:11:15','2026-06-16 19:13:18'),(83,'gajupawar775@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 19:32:09','2026-06-16 19:32:08','2026-06-16 19:32:09'),(84,'gajupawar775@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 19:32:26','2026-06-16 19:32:24','2026-06-16 19:32:26'),(85,'gajupawar775@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-16 19:35:03','2026-06-16 19:33:01','2026-06-16 19:35:03'),(86,'tusharbhise@gmail.com','firm','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 20:07:06','2026-06-16 20:07:05','2026-06-16 20:07:06'),(87,'mantripb77@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 20:32:45','2026-06-16 20:32:44','2026-06-16 20:32:45'),(88,'pawalerohan1999@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 20:33:28','2026-06-16 20:33:26','2026-06-16 20:33:28'),(89,'mantripb77@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-16 20:35:42','2026-06-16 20:33:41','2026-06-16 20:35:42'),(90,'pawalerohan1999@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-16 20:36:55','2026-06-16 20:33:52','2026-06-16 20:36:55'),(91,'akashpund2003@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 20:35:15','2026-06-16 20:35:14','2026-06-16 20:35:15'),(92,'akashpund2003@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 20:35:28','2026-06-16 20:35:27','2026-06-16 20:35:28'),(93,'akashpund2003@gmail.com','creator','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-16 20:37:44','2026-06-16 20:35:42','2026-06-16 20:37:44'),(94,'pratishbansode07@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 20:40:46','2026-06-16 20:40:45','2026-06-16 20:40:46'),(95,'pratishbansode07@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 20:41:14','2026-06-16 20:41:14','2026-06-16 20:41:14'),(96,'pratishbansode07@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-16 20:43:56','2026-06-16 20:41:54','2026-06-16 20:43:56'),(97,'surajingleprofessional007@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 20:49:37','2026-06-16 20:49:36','2026-06-16 20:49:37'),(98,'surajingleprofessional007@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 20:50:21','2026-06-16 20:50:21','2026-06-16 20:50:21'),(99,'surajingleprofessional007@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-16 20:52:46','2026-06-16 20:50:43','2026-06-16 20:52:46'),(100,'atharvpatil4328@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 21:15:46','2026-06-16 21:15:45','2026-06-16 21:15:46'),(101,'atharvpatil4328@gmail.com','creator','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-16 21:18:20','2026-06-16 21:16:17','2026-06-16 21:18:20'),(102,'capranavsancheti@gmail.com','firm','firm_approved','FirmApprovedMail','default','Your Firm Account Has Been Approved — Start Your Story','sent',NULL,'2026-06-16 21:37:08','2026-06-16 21:37:06','2026-06-16 21:37:08'),(103,'kamal@caco.in','firm','firm_approved','FirmApprovedMail','default','Your Firm Account Has Been Approved — Start Your Story','sent',NULL,'2026-06-16 21:37:31','2026-06-16 21:37:28','2026-06-16 21:37:31'),(104,'bnst.ca@gmail.com','firm','firm_approved','FirmApprovedMail','default','Your Firm Account Has Been Approved — Start Your Story','sent',NULL,'2026-06-16 21:37:37','2026-06-16 21:37:36','2026-06-16 21:37:37'),(105,'prajaktashitole01@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-16 23:26:17','2026-06-16 23:26:16','2026-06-16 23:26:17'),(106,'prajaktashitole01@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-16 23:30:53','2026-06-16 23:28:50','2026-06-16 23:30:53'),(107,'kartikajain29@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-17 07:14:41','2026-06-17 07:14:41','2026-06-17 07:14:41'),(108,'kartikajain29@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-17 07:15:06','2026-06-17 07:15:06','2026-06-17 07:15:06'),(109,'kartikajain29@gmail.com','creator','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-17 07:17:31','2026-06-17 07:15:29','2026-06-17 07:17:31'),(110,'casunilshenoy@gmail.com','firm','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-17 08:09:06','2026-06-17 08:09:04','2026-06-17 08:09:06'),(111,'casunilshenoy@gmail.com','firm','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-17 08:11:48','2026-06-17 08:09:47','2026-06-17 08:11:48'),(112,'anushka.shinde.1217@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-17 09:11:25','2026-06-17 09:11:25','2026-06-17 09:11:25'),(113,'anushka.shinde.1217@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-17 09:14:04','2026-06-17 09:12:01','2026-06-17 09:14:04'),(114,'garvitabansal7@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-17 09:43:15','2026-06-17 09:43:14','2026-06-17 09:43:15'),(115,'garvitabansal7@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-17 09:43:59','2026-06-17 09:43:57','2026-06-17 09:43:59'),(116,'garvitabansal7@gmail.com','creator','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-17 09:46:13','2026-06-17 09:44:11','2026-06-17 09:46:13'),(117,'varunmulay9@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-17 09:56:52','2026-06-17 09:56:50','2026-06-17 09:56:52'),(118,'varunmulay9@gmail.com','creator','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-17 09:59:53','2026-06-17 09:57:51','2026-06-17 09:59:53'),(119,'mohittinwar1234@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-17 10:03:17','2026-06-17 10:03:16','2026-06-17 10:03:17'),(120,'mohittinwar1234@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-17 10:05:55','2026-06-17 10:03:53','2026-06-17 10:05:55'),(121,'akanshaagrawal0918@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-17 11:05:42','2026-06-17 11:05:42','2026-06-17 11:05:42'),(122,'amolrtotla@gmail.cm','firm','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-17 11:06:38','2026-06-17 11:06:37','2026-06-17 11:06:38'),(123,'akanshaagrawal0918@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-17 11:09:04','2026-06-17 11:07:03','2026-06-17 11:09:04'),(124,'kasargautam19@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-17 11:23:08','2026-06-17 11:23:07','2026-06-17 11:23:08'),(125,'kasargautam19@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-17 11:25:52','2026-06-17 11:23:49','2026-06-17 11:25:52'),(126,'piyushagrawal4833@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-17 11:28:01','2026-06-17 11:28:00','2026-06-17 11:28:01'),(127,'piyushagrawal4833@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-17 11:30:28','2026-06-17 11:28:27','2026-06-17 11:30:28'),(128,'vipingujarathico@gmail.com','firm','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-17 12:16:19','2026-06-17 12:16:19','2026-06-17 12:16:19'),(129,'hr@prachay.com','firm','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-17 12:34:58','2026-06-17 12:34:58','2026-06-17 12:34:58'),(130,'hr@prachay.com','firm','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-17 12:37:49','2026-06-17 12:35:43','2026-06-17 12:37:49'),(131,'vipingujarathico@gmail.com','firm','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-17 13:02:35','2026-06-17 13:00:34','2026-06-17 13:02:35'),(132,'amolrtotla@gmail.com','firm','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-17 13:28:18','2026-06-17 13:28:17','2026-06-17 13:28:18'),(133,'amolrtotla@gmail.com','firm','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-17 13:30:34','2026-06-17 13:28:32','2026-06-17 13:30:34'),(134,'vipingujarathico@gmail.com','firm','firm_approved','FirmApprovedMail','default','Your Firm Account Has Been Approved — Start Your Story','sent',NULL,'2026-06-17 17:56:44','2026-06-17 17:56:41','2026-06-17 17:56:44'),(135,'amolrtotla@gmail.com','firm','firm_approved','FirmApprovedMail','default','Your Firm Account Has Been Approved — Start Your Story','sent',NULL,'2026-06-17 17:56:48','2026-06-17 17:56:46','2026-06-17 17:56:48'),(136,'tishajain0906@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-17 19:02:41','2026-06-17 19:02:39','2026-06-17 19:02:41'),(137,'tishajain0906@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-17 19:05:15','2026-06-17 19:03:13','2026-06-17 19:05:15'),(138,'kukrejamekii2703@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-17 20:04:00','2026-06-17 20:03:59','2026-06-17 20:04:00'),(139,'kukrejamekii2703@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-17 20:04:23','2026-06-17 20:04:23','2026-06-17 20:04:23'),(140,'kukrejamekii2703@gmail.com','creator','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-17 20:06:42','2026-06-17 20:04:38','2026-06-17 20:06:42'),(141,'carsbiyani@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-17 20:24:26','2026-06-17 20:24:25','2026-06-17 20:24:26'),(142,'carsbiyani@gmail.com','creator','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-17 20:27:00','2026-06-17 20:24:57','2026-06-17 20:27:00'),(143,'rsbitsolution@gmail.com','firm','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-17 20:28:15','2026-06-17 20:28:14','2026-06-17 20:28:15'),(144,'rsbitsolution@gmail.com','firm','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-17 20:30:28','2026-06-17 20:28:27','2026-06-17 20:30:28'),(145,'nitishbadak14@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-17 22:36:31','2026-06-17 22:36:31','2026-06-17 22:36:31'),(146,'cacspriya@tnlac.in','firm','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-17 22:57:46','2026-06-17 22:57:44','2026-06-17 22:57:46'),(147,'cacspriya@tnlac.in','firm','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-17 22:58:03','2026-06-17 22:58:02','2026-06-17 22:58:03'),(148,'cacspriya@tnlac.in','firm','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-17 23:00:19','2026-06-17 22:58:17','2026-06-17 23:00:19'),(149,'kakaniatharv06@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-17 23:18:46','2026-06-17 23:18:46','2026-06-17 23:18:46'),(150,'kakaniatharv06@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-17 23:19:01','2026-06-17 23:19:01','2026-06-17 23:19:01'),(151,'kakaniatharv06@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-17 23:21:53','2026-06-17 23:19:50','2026-06-17 23:21:53'),(152,'anjumantri@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-17 23:26:21','2026-06-17 23:26:20','2026-06-17 23:26:21'),(153,'anjumantri@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-17 23:29:11','2026-06-17 23:27:02','2026-06-17 23:29:11'),(154,'tusharbhise908@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-17 23:39:02','2026-06-17 23:38:59','2026-06-17 23:39:02'),(155,'tusharbhise908@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-17 23:41:49','2026-06-17 23:39:47','2026-06-17 23:41:49'),(156,'tusharb.live@gmail.com','firm','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-17 23:43:40','2026-06-17 23:43:39','2026-06-17 23:43:40'),(157,'tusharb.live@gmail.com','firm','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-17 23:46:17','2026-06-17 23:44:15','2026-06-17 23:46:17'),(158,'tusharb.live@gmail.com','firm','firm_approved','FirmApprovedMail','default','Your Firm Account Has Been Approved — Start Your Story','sent',NULL,'2026-06-17 23:45:48','2026-06-17 23:45:45','2026-06-17 23:45:48');
/*!40000 ALTER TABLE `email_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `engagement_brief_attachments`
--

DROP TABLE IF EXISTS `engagement_brief_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `engagement_brief_attachments` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `engagement_id` bigint NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` int DEFAULT NULL,
  `uploaded_by` bigint NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_eba_engagement` (`engagement_id`),
  CONSTRAINT `fk_eba_engagement` FOREIGN KEY (`engagement_id`) REFERENCES `creator_engagements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `engagement_brief_attachments`
--

LOCK TABLES `engagement_brief_attachments` WRITE;
/*!40000 ALTER TABLE `engagement_brief_attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `engagement_brief_attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `engagement_briefs`
--

DROP TABLE IF EXISTS `engagement_briefs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `engagement_briefs` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `engagement_id` bigint NOT NULL,
  `detailed_brief` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `additional_notes` text COLLATE utf8mb4_unicode_ci,
  `updated_by` bigint NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_eb_engagement` (`engagement_id`),
  CONSTRAINT `fk_eb_engagement` FOREIGN KEY (`engagement_id`) REFERENCES `creator_engagements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `engagement_briefs`
--

LOCK TABLES `engagement_briefs` WRITE;
/*!40000 ALTER TABLE `engagement_briefs` DISABLE KEYS */;
/*!40000 ALTER TABLE `engagement_briefs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `engagement_submission_files`
--

DROP TABLE IF EXISTS `engagement_submission_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `engagement_submission_files` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `submission_id` bigint NOT NULL,
  `engagement_id` bigint NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` int DEFAULT NULL,
  `video_link` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_esf_submission` (`submission_id`),
  KEY `idx_esf_engagement` (`engagement_id`),
  CONSTRAINT `fk_esf_submission` FOREIGN KEY (`submission_id`) REFERENCES `engagement_submissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `engagement_submission_files`
--

LOCK TABLES `engagement_submission_files` WRITE;
/*!40000 ALTER TABLE `engagement_submission_files` DISABLE KEYS */;
/*!40000 ALTER TABLE `engagement_submission_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `engagement_submissions`
--

DROP TABLE IF EXISTS `engagement_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `engagement_submissions` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `engagement_id` bigint NOT NULL,
  `creator_id` bigint NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `revision_round` tinyint NOT NULL DEFAULT '1',
  `status` enum('submitted','revision_requested','approved') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'submitted',
  `revision_notes` text COLLATE utf8mb4_unicode_ci,
  `reviewed_by` bigint DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_es_engagement` (`engagement_id`),
  KEY `idx_es_creator` (`creator_id`),
  CONSTRAINT `fk_es_engagement` FOREIGN KEY (`engagement_id`) REFERENCES `creator_engagements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `engagement_submissions`
--

LOCK TABLES `engagement_submissions` WRITE;
/*!40000 ALTER TABLE `engagement_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `engagement_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `engagement_timeline`
--

DROP TABLE IF EXISTS `engagement_timeline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `engagement_timeline` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `engagement_id` bigint NOT NULL,
  `user_id` bigint DEFAULT NULL,
  `role` enum('firm','creator','system') COLLATE utf8mb4_unicode_ci NOT NULL,
  `event` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_et_engagement` (`engagement_id`,`created_at`),
  CONSTRAINT `fk_et_engagement` FOREIGN KEY (`engagement_id`) REFERENCES `creator_engagements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `engagement_timeline`
--

LOCK TABLES `engagement_timeline` WRITE;
/*!40000 ALTER TABLE `engagement_timeline` DISABLE KEYS */;
/*!40000 ALTER TABLE `engagement_timeline` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `error_logs`
--

DROP TABLE IF EXISTS `error_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `error_logs` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `source` enum('api','frontend') COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `error_summary` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` smallint DEFAULT NULL,
  `url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stack` text COLLATE utf8mb4_unicode_ci,
  `user_id` bigint DEFAULT NULL,
  `user_role` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_error_source_created` (`source`,`created_at`),
  KEY `idx_error_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=379 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `error_logs`
--

LOCK TABLES `error_logs` WRITE;
/*!40000 ALTER TABLE `error_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `error_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `firm_branches`
--

DROP TABLE IF EXISTS `firm_branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `firm_branches` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `firm_id` bigint DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `address` text,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `firm_branches`
--

LOCK TABLES `firm_branches` WRITE;
/*!40000 ALTER TABLE `firm_branches` DISABLE KEYS */;
INSERT INTO `firm_branches` VALUES (10,2,'SANGLI','Office no 3, Garden View Apartment, Near Trikoni baug, Civil Hospital road','MAHARASHTRA','416416'),(11,1,'','','',''),(13,3,'','','',''),(14,4,'PUNE','Khed Shivapur','MAHARASHTRA','412205'),(15,9,'','','',''),(16,11,'','','',''),(17,12,'LATUR','1st Floor, Above Dr Unni, Vyapari Dharmshala Complex, Main Road,','MAHARASHTRA','413512'),(18,14,'','','','');
/*!40000 ALTER TABLE `firm_branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `firm_content_credits`
--

DROP TABLE IF EXISTS `firm_content_credits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `firm_content_credits` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `firm_id` bigint NOT NULL,
  `total_credits` tinyint NOT NULL DEFAULT '3',
  `used_credits` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fcc_firm` (`firm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `firm_content_credits`
--

LOCK TABLES `firm_content_credits` WRITE;
/*!40000 ALTER TABLE `firm_content_credits` DISABLE KEYS */;
/*!40000 ALTER TABLE `firm_content_credits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `firm_departments`
--

DROP TABLE IF EXISTS `firm_departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `firm_departments` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `firm_id` bigint DEFAULT NULL,
  `department_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `firm_departments`
--

LOCK TABLES `firm_departments` WRITE;
/*!40000 ALTER TABLE `firm_departments` DISABLE KEYS */;
/*!40000 ALTER TABLE `firm_departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `firm_profiles`
--

DROP TABLE IF EXISTS `firm_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `firm_profiles` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` bigint DEFAULT NULL,
  `frn` varchar(100) DEFAULT NULL,
  `is_branch` tinyint(1) NOT NULL DEFAULT '0',
  `parent_firm_id` bigint DEFAULT NULL,
  `parent_frn` varchar(50) DEFAULT NULL,
  `firm_name` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `address` text,
  `hr_name` varchar(255) DEFAULT NULL,
  `partners_count` int DEFAULT NULL,
  `employees_count` int DEFAULT NULL,
  `articles_count` int DEFAULT NULL,
  `exposure_type` varchar(500) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `is_premium` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `verification_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `rejection_reason` text,
  `about` text,
  `firm_type` varchar(100) DEFAULT NULL,
  `establishment_year` varchar(10) DEFAULT NULL,
  `services_offered` text,
  `industries_served` text,
  `work_modes` json DEFAULT NULL,
  `training_details` text,
  `stipend_details` text,
  `instagram_url` varchar(255) DEFAULT NULL,
  `facebook_url` varchar(255) DEFAULT NULL,
  `other_links` text,
  `office_images` json DEFAULT NULL,
  `additional_contacts` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fp_is_branch` (`is_branch`,`parent_frn`),
  KEY `idx_fp_parent_firm_id` (`parent_firm_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `firm_profiles`
--

LOCK TABLES `firm_profiles` WRITE;
/*!40000 ALTER TABLE `firm_profiles` DISABLE KEYS */;
INSERT INTO `firm_profiles` VALUES (1,2,'123456W',0,NULL,NULL,'Test','PUNE','pune','Ritesh',2,2,2,'[\"overall\"]',NULL,NULL,'firm/logo/1781086388_logo.png',0,'2026-06-10 15:32:16','2026-06-16 14:23:15','approved',NULL,'Taxation firm','Proprietorship','2026',NULL,NULL,'[]',NULL,NULL,NULL,NULL,NULL,'[\"firm/office-images/1781285917_6a2c441dcaf13.png\"]','[{\"name\": \"\", \"role\": \"\", \"phone\": \"\"}]'),(2,22,'162224W',0,NULL,NULL,'Sancheti Lakade & Associates','PUNE','B-826, Gera Imperium Gateway, Near Nashik Phata Metro Station, Pune - 411034','Pranav Sancheti',2,1,0,'[\"overall\"]','https://www.linkedin.com/in/ca-ruturaj-lakade-252082233?utm_source=share_via&utm_content=profile&utm_medium=member_ios',NULL,'firm/logo/1781599573_logo.jpeg',0,'2026-06-16 14:03:23','2026-06-16 21:37:06','approved',NULL,'Accounting & Bookkeeping – Maintaining accurate financial records and preparing financial statements to support informed decision-making.\r\n\r\nTaxation Services – Expert assistance in Income Tax, GST compliance, tax planning, return filing, assessments, and advisory.\r\n\r\nAudit & Assurance – Conducting statutory audits, internal audits, tax audits, and assurance engagements to enhance transparency and compliance.\r\n\r\nBusiness Registration & Compliance – Assistance with company incorporation, LLP registration, startup registration, and ongoing regulatory compliance.\r\n\r\nIncentives and subsidies - All incentives and subsidies under the central and state government','Partnership','2026','Schemes & Incentives (State & Central Schemes), Audits, Taxation , Project reports,','Manufacturing, IT, Education & Misc','[]',NULL,NULL,NULL,NULL,NULL,'[\"firm/office-images/1781599573_6a310d55c7fa1.jpeg\", \"firm/office-images/1781599573_6a310d55c807e.jpeg\", \"firm/office-images/1781599573_6a310d55c8163.jpeg\", \"firm/office-images/1781599573_6a310d55c8255.jpeg\", \"firm/office-images/1781599573_6a310d55c8329.jpeg\", \"firm/office-images/1781599573_6a310d55c8409.jpeg\", \"firm/office-images/1781599573_6a310d55c8523.jpeg\"]','[{\"name\": \"\", \"role\": \"\", \"phone\": \"\"}]'),(3,26,'127597W',0,NULL,NULL,'C A & Co','RAJKOT','3rd Floor Aadarsh Sukham 150 Feet Ring Road KKV Circle Behind Sanskar Complex, Rajkot - 360 005','Kamal Bhambhani',2,8,2,'[\"overall\"]',NULL,NULL,NULL,0,'2026-06-16 17:29:36','2026-06-16 21:37:28','approved',NULL,'We are a firm of Chartered Accountants practicing in Audits, Direct Taxes, Indirect Taxes and Litigations.','Partnership','2005','Audit, Direct Tax, Indirect Tax, Litigations, Advisory',NULL,'[]',NULL,NULL,NULL,NULL,NULL,NULL,'[{\"name\": \"\", \"role\": \"\", \"phone\": \"\"}]'),(4,27,'W101215',0,NULL,NULL,'B N S T & Co LLP','PUNE','B107-108, The Greater Good, Mohammadwadi, Pune 411060','CA Akshay Nikam',3,7,1,'[\"Statutory Audit\",\"Tax Audit\",\"Direct Tax\",\"GST & Indirect Tax\",\"Accounting & Bookkeeping\",\"Advisory & Consulting\",\"Corporate Laws & LLP\",\"Forensic Audit & Investigation\",\"Payroll Services\",\"Virtual CFO Services\",\"Due Diligence\",\"NGO / Trust Audit\"]',NULL,'https://bnstca.com/',NULL,0,'2026-06-16 17:37:42','2026-06-16 21:37:36','approved',NULL,'B N S T & CO LLP is a Pune-based chartered accountancy firm offering income tax, GST, statutory and regulatory compliance, ROC and corporate law, bank loan and project finance, and audit services. Our audit practice has a strong focus on banking audits, including statutory, concurrent, revenue, and credit audits. We work across diverse industries with a verification-first, quality-driven approach, giving our team hands-on exposure to high-value assurance and advisory engagements.','LLP','2026','Income Tax, GST, Statutory & Regulatory Compliance, ROC & Corporate Law, Bank Loans & Project Finance, Statutory Audit, Tax Audit, Bank Audit (Concurrent / Revenue / Credit), Internal Audit, Business Advisory','Manufacturing, BFSI, IT & Software, Trading & Retail, Real Estate & Construction, Services, Exports','[]',NULL,NULL,NULL,NULL,NULL,NULL,'[{\"name\": \"CA Akshay Nikam\", \"role\": \"Partner\", \"phone\": \"7888080300\"}, {\"name\": \"CA Tanmay Shedge\", \"role\": \"Partner\", \"phone\": \"7888080200\"}]'),(5,28,NULL,0,NULL,NULL,'Rekhani & Saraogi','SURAT',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-06-16 18:10:31','2026-06-16 18:10:31','pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(6,36,NULL,0,NULL,NULL,'Test','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-06-16 20:07:05','2026-06-16 20:07:05','pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(7,45,NULL,0,NULL,NULL,'Sunil Shenoy and Associates','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-06-17 08:09:04','2026-06-17 08:09:04','pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(8,51,NULL,0,NULL,NULL,'A R Totala & Co.','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-06-17 11:06:37','2026-06-17 11:06:37','pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(9,54,'109573W',0,NULL,NULL,'Vipin Gujarathi & Co','PUNE','1009 to 1014, Apex Business Court, Bibwewadi Kondhwa Road, Bibwewadi, Pune','Nalini Shah',3,2,12,'[\"overall\"]','https://www.linkedin.com/company/vipin-gujarathi-associates/',NULL,'firm/logo/1781683273_logo.jpeg',0,'2026-06-17 12:16:19','2026-06-17 17:56:41','approved',NULL,'Established in 1981, the firm has rich experience of four decades. Founded by Late CA Vipin Gujarathi, an eminent name in direct tax and litigation professional circles, the firm continues to be committed to its clients, offering bouquet of services. The firm is head quartered in Pune. It functions from 2 offices located across Pune and Mumbai. Currently, the firm boasts of a strong work force of 50 members including 10 Chartered Accountants and is continuously growing its strength.','Partnership','1981','Tax Audit, Statutory Audit, Internal Audit, Advisory, TDS and GST Compliances, Direct Taxes Litigation, Return filing, etc','Professional, Trader, Real Estate, Logistics, etc.','[]',NULL,NULL,NULL,NULL,NULL,'[\"firm/office-images/1781683273_6a3254498543a.jpeg\", \"firm/office-images/1781683273_6a32544985538.jpeg\", \"firm/office-images/1781683273_6a32544985619.jpeg\"]','[{\"name\": \"\", \"role\": \"\", \"phone\": \"\"}]'),(10,55,NULL,0,NULL,NULL,'Prachay Grouo','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-06-17 12:34:58','2026-06-17 12:34:58','pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(11,56,'139367W',0,NULL,NULL,'A R Totala & Co.','PUNE','Off. No.5, 3rd Floor, Sai Complex, 917/22, Ganeshwadi, Opp. Hotel Roopali, F. C. Road, Pune - 411 004','Amol Totala',0,1,1,'[\"overall\"]',NULL,NULL,NULL,0,'2026-06-17 13:28:17','2026-06-17 17:56:46','approved',NULL,'Firm competency\r\n•	Professional Approach with Integrity & complete confidentiality\r\n•	Innovative Ideas\r\n•	Sound knowledgebase and counseling\r\n•	Onsite and Offsite Service as per requirement\r\n•	Supervision by professional having deep accounting and compliance knowledge and tried-and-tested methodologies\r\n•	Accuracy of information\r\n•	Helps management to focus on core areas of business\r\n•	Cost effective being lower overheads and helps to achieve the management results','Proprietorship','2014','•	Statutory compliances (Direct & Indirect taxes)\r\n•	Statutory, internal, tax, due diligence and special audit\r\n•	Direct Tax & Indirect tax planning, provisioning & compliance, advisory, representation services\r\n•	Preparation and evaluation of project proposals\r\n•	Cash to accrual system audit\r\n•	Documentation, preparation and review of statutory return\r\n•	Alignment of tax with business strategy, identifying tax exposure and planning\r\n•	Designing standard operating processes, internal audit and diagnostic reviews','Manufacturing, Service, IT, Government Independent Body.','[]',NULL,NULL,NULL,NULL,NULL,NULL,'[{\"name\": \"\", \"role\": \"\", \"phone\": \"\"}]'),(12,60,'122630W',0,NULL,NULL,'R S Biyani & Co','LATUR','Shop No 1, 1st Floor, Above Dr Unni , Vyapari Dharmshala Complex, Main Road, Latur','CA Radhesham Biyani',0,2,0,'[\"Subsidy\",\"Bank Finance\"]',NULL,NULL,NULL,0,'2026-06-17 20:28:14','2026-06-17 20:34:08','pending',NULL,'Firm is established in 2002 and having office at Latur','Proprietorship','2002','Subsidy , Bank Finance','Agro Based Industries , Manufacturing Industries','[]',NULL,NULL,NULL,NULL,NULL,NULL,'[{\"name\": \"\", \"role\": \"\", \"phone\": \"\"}]'),(13,62,NULL,0,NULL,NULL,'N J LOHE & CO.','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-06-17 22:57:44','2026-06-17 22:57:44','pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(14,66,'091199',0,NULL,NULL,'test','PUNE','Pune','Test',2,5,5,'[\"overall\"]',NULL,NULL,NULL,0,'2026-06-17 23:43:39','2026-06-17 23:45:45','approved',NULL,'Test','Proprietorship','2025',NULL,NULL,'[]',NULL,NULL,NULL,NULL,NULL,NULL,'[{\"name\": \"\", \"role\": \"\", \"phone\": \"\"}]');
/*!40000 ALTER TABLE `firm_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `firm_subscriptions`
--

DROP TABLE IF EXISTS `firm_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `firm_subscriptions` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `firm_id` bigint NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `plan` varchar(25) DEFAULT 'free',
  `amount` decimal(10,2) DEFAULT NULL,
  `currency` varchar(10) DEFAULT 'INR',
  `payment_gateway` enum('manual','razorpay','phonepe') DEFAULT 'manual',
  `gateway_order_id` varchar(255) DEFAULT NULL,
  `gateway_payment_id` varchar(255) DEFAULT NULL,
  `gateway_signature` text,
  `payment_method` varchar(100) DEFAULT NULL,
  `razorpay_response` json DEFAULT NULL,
  `payment_status` enum('pending','paid','failed','manual_verification','cancelled','refunded') DEFAULT 'pending',
  `transaction_id` varchar(255) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `screenshot_url` text,
  `remarks` text,
  `status` enum('pending','active','expired','cancelled') DEFAULT 'pending',
  `starts_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `index` (`id`,`firm_id`,`plan`,`status`),
  KEY `idx_firm_id` (`firm_id`),
  KEY `idx_gateway_order_id` (`gateway_order_id`),
  KEY `idx_gateway_payment_id` (`gateway_payment_id`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `firm_subscriptions`
--

LOCK TABLES `firm_subscriptions` WRITE;
/*!40000 ALTER TABLE `firm_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `firm_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `free_content_deliverables`
--

DROP TABLE IF EXISTS `free_content_deliverables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `free_content_deliverables` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `request_id` bigint NOT NULL,
  `file_path` varchar(1000) NOT NULL,
  `file_name` varchar(500) NOT NULL,
  `uploaded_by` bigint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fcd_request_id` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `free_content_deliverables`
--

LOCK TABLES `free_content_deliverables` WRITE;
/*!40000 ALTER TABLE `free_content_deliverables` DISABLE KEYS */;
/*!40000 ALTER TABLE `free_content_deliverables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `free_content_requests`
--

DROP TABLE IF EXISTS `free_content_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `free_content_requests` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `firm_id` bigint NOT NULL,
  `brief` text NOT NULL,
  `delivery_date` date DEFAULT NULL,
  `notes` text,
  `attachments` json DEFAULT NULL,
  `status` enum('pending','confirmed','in_progress','delivered','rejected') NOT NULL DEFAULT 'pending',
  `admin_notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fcr_firm_id` (`firm_id`),
  KEY `idx_fcr_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `free_content_requests`
--

LOCK TABLES `free_content_requests` WRITE;
/*!40000 ALTER TABLE `free_content_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `free_content_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `firm_id` bigint DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `work_mode` varchar(100) DEFAULT NULL,
  `experience_level` varchar(100) DEFAULT NULL,
  `openings` int DEFAULT NULL,
  `required_skills` text,
  `benefits` text,
  `required_qualification` text,
  `application_deadline` date DEFAULT NULL,
  `salary` varchar(100) DEFAULT NULL,
  `description` text,
  `hiring_for` varchar(55) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `status` varchar(25) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `hiring_for` (`hiring_for`) /*!80000 INVISIBLE */
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_history`
--

DROP TABLE IF EXISTS `login_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_history` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` bigint NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `device_type` varchar(20) DEFAULT NULL,
  `browser` varchar(100) DEFAULT NULL,
  `os` varchar(100) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `logged_in_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_login_history_user_id` (`user_id`),
  KEY `idx_login_history_logged_in_at` (`logged_in_at`),
  CONSTRAINT `fk_login_history_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=248 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_history`
--

LOCK TABLES `login_history` WRITE;
/*!40000 ALTER TABLE `login_history` DISABLE KEYS */;
INSERT INTO `login_history` VALUES (3,2,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-10 15:33:16','2026-06-10 15:33:16'),(5,2,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-10 15:40:27','2026-06-10 15:40:27'),(9,2,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-10 16:56:32','2026-06-10 16:56:32'),(12,2,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-10 20:30:23','2026-06-10 20:30:23'),(13,3,'152.58.180.147','mobile','Chrome 149','Android 10','Kolkata, West Bengal, India','2026-06-10 20:36:27','2026-06-10 20:36:27'),(14,3,'152.58.180.147','mobile','Chrome 149','Android 10','Kolkata, West Bengal, India','2026-06-10 20:37:01','2026-06-10 20:37:01'),(15,3,'152.58.180.147','mobile','Chrome 149','Android 10','Kolkata, West Bengal, India','2026-06-10 20:41:08','2026-06-10 20:41:08'),(16,2,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-10 20:55:56','2026-06-10 20:55:56'),(17,4,'157.32.198.213','mobile','Chrome 138','Android 9','Pune, Maharashtra, India','2026-06-11 13:15:34','2026-06-11 13:15:34'),(18,4,'157.32.198.213','mobile','Chrome 138','Android 9','Pune, Maharashtra, India','2026-06-11 13:15:50','2026-06-11 13:15:50'),(19,4,'157.32.198.213','mobile','Chrome 123','Android 9','Pune, Maharashtra, India','2026-06-11 13:18:19','2026-06-11 13:18:19'),(20,5,'152.58.44.210','mobile','Chrome 148','Android 10','Mumbai, Maharashtra, India','2026-06-11 22:30:47','2026-06-11 22:30:47'),(21,5,'152.58.44.210','mobile','Chrome 148','Android 10','Mumbai, Maharashtra, India','2026-06-11 22:31:22','2026-06-11 22:31:22'),(22,6,'157.32.113.113','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-12 14:31:21','2026-06-12 14:31:21'),(23,6,'157.32.113.113','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-12 14:32:11','2026-06-12 14:32:11'),(24,7,'110.227.185.177','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-12 14:39:58','2026-06-12 14:39:58'),(25,7,'110.227.185.177','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-12 14:48:25','2026-06-12 14:48:25'),(27,8,'27.97.174.133','mobile','Chrome 147','Android 10','Nagpur, Maharashtra, India','2026-06-12 17:02:15','2026-06-12 17:02:15'),(28,8,'27.97.174.133','mobile','Chrome 147','Android 10','Nagpur, Maharashtra, India','2026-06-12 17:03:53','2026-06-12 17:03:53'),(29,9,'27.97.180.154','mobile','Samsung Browser 29','Android 10','Amravati, Maharashtra, India','2026-06-12 17:16:17','2026-06-12 17:16:17'),(30,9,'27.97.180.154','mobile','Samsung Browser 29','Android 10','Amravati, Maharashtra, India','2026-06-12 17:16:46','2026-06-12 17:16:46'),(32,2,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-12 17:20:33','2026-06-12 17:20:33'),(34,10,'106.213.86.255','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-12 17:28:55','2026-06-12 17:28:55'),(41,11,'223.236.99.173','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-12 22:31:06','2026-06-12 22:31:06'),(42,11,'223.236.99.173','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-12 22:31:36','2026-06-12 22:31:36'),(43,2,'223.236.99.173','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-12 23:06:12','2026-06-12 23:06:12'),(44,11,'152.58.16.59','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-13 01:53:32','2026-06-13 01:53:32'),(45,11,'152.58.16.164','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-13 01:57:25','2026-06-13 01:57:25'),(46,11,'223.236.99.144','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-13 17:40:17','2026-06-13 17:40:17'),(47,12,'106.210.227.201','mobile','Chrome 148','Android 10','Kātol, Maharashtra, India','2026-06-14 10:49:26','2026-06-14 10:49:26'),(48,12,'106.210.227.201','mobile','Chrome 148','Android 10','Kātol, Maharashtra, India','2026-06-14 10:50:07','2026-06-14 10:50:07'),(49,11,'152.58.31.43','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-14 13:05:44','2026-06-14 13:05:44'),(50,11,'122.183.33.89','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-14 14:22:23','2026-06-14 14:22:23'),(51,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 10:18:39','2026-06-15 10:18:39'),(52,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 10:18:42','2026-06-15 10:18:42'),(53,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 10:18:46','2026-06-15 10:18:46'),(54,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 10:18:58','2026-06-15 10:18:58'),(55,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 10:19:00','2026-06-15 10:19:00'),(56,2,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 10:19:06','2026-06-15 10:19:06'),(57,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 10:19:09','2026-06-15 10:19:09'),(58,2,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 10:19:12','2026-06-15 10:19:12'),(59,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 10:19:24','2026-06-15 10:19:24'),(60,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 10:19:26','2026-06-15 10:19:26'),(61,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 10:20:18','2026-06-15 10:20:18'),(62,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 10:20:21','2026-06-15 10:20:21'),(63,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 10:20:42','2026-06-15 10:20:42'),(64,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 10:20:44','2026-06-15 10:20:44'),(65,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 10:23:08','2026-06-15 10:23:08'),(66,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 10:23:10','2026-06-15 10:23:10'),(67,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 10:23:21','2026-06-15 10:23:21'),(68,2,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 10:23:26','2026-06-15 10:23:26'),(69,11,'103.226.205.156','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-15 10:23:26','2026-06-15 10:23:26'),(70,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 10:23:32','2026-06-15 10:23:32'),(71,2,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 10:23:36','2026-06-15 10:23:36'),(72,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 10:24:00','2026-06-15 10:24:00'),(73,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 10:24:02','2026-06-15 10:24:02'),(74,11,'103.226.205.156','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-15 10:24:03','2026-06-15 10:24:03'),(75,2,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 10:24:07','2026-06-15 10:24:07'),(76,11,'103.226.205.156','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-15 10:24:16','2026-06-15 10:24:16'),(77,11,'103.226.205.156','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-15 10:25:39','2026-06-15 10:25:39'),(78,11,'103.226.205.156','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-15 10:28:09','2026-06-15 10:28:09'),(79,2,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 10:31:32','2026-06-15 10:31:32'),(80,2,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 10:32:07','2026-06-15 10:32:07'),(81,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 11:05:37','2026-06-15 11:05:37'),(82,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 11:44:03','2026-06-15 11:44:03'),(83,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 11:44:31','2026-06-15 11:44:31'),(85,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 12:06:07','2026-06-15 12:06:07'),(86,2,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 12:11:05','2026-06-15 12:11:05'),(87,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 12:16:51','2026-06-15 12:16:51'),(88,2,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 12:20:20','2026-06-15 12:20:20'),(89,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 12:20:23','2026-06-15 12:20:23'),(90,2,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 12:20:32','2026-06-15 12:20:32'),(91,2,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 12:21:12','2026-06-15 12:21:12'),(92,14,'152.57.156.135','mobile','Chrome 149','Android 10','Hyderabad, Telangana, India','2026-06-15 12:24:49','2026-06-15 12:24:49'),(93,2,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 12:44:53','2026-06-15 12:44:53'),(95,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 14:46:58','2026-06-15 14:46:58'),(96,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 14:47:02','2026-06-15 14:47:02'),(97,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 14:47:19','2026-06-15 14:47:19'),(98,2,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 14:47:22','2026-06-15 14:47:22'),(99,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 14:47:44','2026-06-15 14:47:44'),(100,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 14:48:41','2026-06-15 14:48:41'),(101,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 14:48:50','2026-06-15 14:48:50'),(103,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 14:49:23','2026-06-15 14:49:23'),(104,2,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 14:49:50','2026-06-15 14:49:50'),(106,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 15:15:53','2026-06-15 15:15:53'),(107,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 15:19:29','2026-06-15 15:19:29'),(108,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 15:19:31','2026-06-15 15:19:31'),(109,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 15:19:54','2026-06-15 15:19:54'),(110,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 15:21:58','2026-06-15 15:21:58'),(111,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 15:22:23','2026-06-15 15:22:23'),(112,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 16:45:12','2026-06-15 16:45:12'),(113,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 16:48:18','2026-06-15 16:48:18'),(114,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 16:48:22','2026-06-15 16:48:22'),(115,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 16:48:46','2026-06-15 16:48:46'),(116,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 20:08:23','2026-06-15 20:08:23'),(117,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 20:08:52','2026-06-15 20:08:52'),(118,2,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 20:13:24','2026-06-15 20:13:24'),(119,11,'122.183.33.74','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 22:31:09','2026-06-15 22:31:09'),(120,11,'122.183.33.74','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 22:31:37','2026-06-15 22:31:37'),(121,2,'122.183.33.74','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 22:31:47','2026-06-15 22:31:47'),(122,11,'122.183.33.74','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-15 22:32:55','2026-06-15 22:32:55'),(123,11,'122.183.33.74','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-15 22:41:20','2026-06-15 22:41:20'),(124,15,'182.69.179.145','desktop','Chrome 147','Windows 10 / 11','New Delhi, National Capital Territory of Delhi, India','2026-06-15 23:59:24','2026-06-15 23:59:24'),(125,15,'182.69.179.145','desktop','Chrome 147','Windows 10 / 11','New Delhi, National Capital Territory of Delhi, India','2026-06-16 00:00:17','2026-06-16 00:00:17'),(126,16,'103.49.254.7','desktop','Edge 149','Windows 10 / 11','Badlapur, Maharashtra, India','2026-06-16 00:33:58','2026-06-16 00:33:58'),(133,17,'152.59.29.76','mobile','Chrome 148','Android 10','Jabalpur, Madhya Pradesh, India','2026-06-16 01:25:31','2026-06-16 01:25:31'),(135,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-16 11:54:44','2026-06-16 11:54:44'),(136,2,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-16 11:55:36','2026-06-16 11:55:36'),(137,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-16 11:59:01','2026-06-16 11:59:01'),(138,2,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-16 12:02:28','2026-06-16 12:02:28'),(139,18,'223.228.136.12','mobile','Safari 18','iOS 18.5','Pune, Maharashtra, India','2026-06-16 12:35:25','2026-06-16 12:35:25'),(140,18,'223.228.136.12','mobile','Safari 18','iOS 18.5','Pune, Maharashtra, India','2026-06-16 12:36:05','2026-06-16 12:36:05'),(141,19,'103.160.175.18','desktop','Chrome 148','Windows 10 / 11','Pune, Maharashtra, India','2026-06-16 12:37:09','2026-06-16 12:37:09'),(143,20,'110.227.185.177','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-16 13:21:46','2026-06-16 13:21:46'),(144,20,'110.227.185.177','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-16 13:36:08','2026-06-16 13:36:08'),(145,20,'110.227.185.177','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-16 13:36:16','2026-06-16 13:36:16'),(146,21,'106.221.220.177','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 13:40:13','2026-06-16 13:40:13'),(147,21,'106.221.220.177','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 13:40:42','2026-06-16 13:40:42'),(148,20,'110.227.185.177','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-16 13:43:42','2026-06-16 13:43:42'),(149,22,'103.174.77.243','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-16 14:03:38','2026-06-16 14:03:38'),(150,23,'42.104.216.11','mobile','Unknown Browser','iOS 26.2.0','Pune, Maharashtra, India','2026-06-16 14:05:50','2026-06-16 14:05:50'),(151,24,'106.193.199.173','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 14:17:30','2026-06-16 14:17:30'),(152,24,'106.193.199.173','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 14:18:49','2026-06-16 14:18:49'),(153,2,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-16 14:22:59','2026-06-16 14:22:59'),(154,22,'103.174.77.243','desktop','Edge 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-16 14:25:49','2026-06-16 14:25:49'),(155,25,'103.22.140.214','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 14:52:53','2026-06-16 14:52:53'),(156,25,'103.22.140.214','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 14:56:50','2026-06-16 14:56:50'),(157,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-16 17:21:13','2026-06-16 17:21:13'),(158,2,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-16 17:27:11','2026-06-16 17:27:11'),(159,26,'103.250.190.58','desktop','Chrome 149','Windows 10 / 11','Rajkot, Gujarat, India','2026-06-16 17:31:56','2026-06-16 17:31:56'),(160,27,'103.204.38.118','desktop','Chrome 148','macOS 10.15.7','Pune, Maharashtra, India','2026-06-16 17:37:52','2026-06-16 17:37:52'),(161,27,'103.204.38.118','desktop','Chrome 148','macOS 10.15.7','Pune, Maharashtra, India','2026-06-16 17:38:19','2026-06-16 17:38:19'),(162,20,'110.227.185.177','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-16 18:07:15','2026-06-16 18:07:15'),(163,11,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-16 18:21:51','2026-06-16 18:21:51'),(164,26,'103.250.190.58','desktop','Chrome 149','Windows 10 / 11','Rajkot, Gujarat, India','2026-06-16 18:33:55','2026-06-16 18:33:55'),(165,30,'110.227.185.177','mobile','Chrome 148','Android 16','Pune, Maharashtra, India','2026-06-16 18:35:15','2026-06-16 18:35:15'),(166,30,'110.227.185.177','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 18:35:41','2026-06-16 18:35:41'),(167,31,'110.227.185.177','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-16 18:52:52','2026-06-16 18:52:52'),(168,32,'122.161.243.76','mobile','Chrome 148','Android 10','Jammu, Jammu and Kashmir, India','2026-06-16 18:57:49','2026-06-16 18:57:49'),(169,32,'122.161.243.76','mobile','Chrome 148','Android 10','Jammu, Jammu and Kashmir, India','2026-06-16 18:58:09','2026-06-16 18:58:09'),(170,33,'106.220.141.136','mobile','Chrome 148','Android 10','Pune, Maharashtra, India','2026-06-16 18:58:29','2026-06-16 18:58:29'),(171,33,'106.220.141.136','mobile','Chrome 148','Android 10','Pune, Maharashtra, India','2026-06-16 19:00:05','2026-06-16 19:00:05'),(172,34,'103.159.35.112','mobile','Chrome 149','Android 10','Delhi, National Capital Territory of Delhi, India','2026-06-16 19:10:56','2026-06-16 19:10:56'),(173,34,'103.159.35.112','mobile','Chrome 149','Android 10','Delhi, National Capital Territory of Delhi, India','2026-06-16 19:11:26','2026-06-16 19:11:26'),(174,35,'152.56.6.27','mobile','Chrome 146','Android 10','Aurangabad, Maharashtra, India','2026-06-16 19:32:21','2026-06-16 19:32:21'),(175,35,'152.56.6.27','mobile','Chrome 146','Android 10','Aurangabad, Maharashtra, India','2026-06-16 19:33:08','2026-06-16 19:33:08'),(176,37,'152.58.36.111','mobile','Chrome 149','Android 15','Surat, Gujarat, India','2026-06-16 20:33:17','2026-06-16 20:33:17'),(177,38,'157.32.218.38','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 20:33:38','2026-06-16 20:33:38'),(178,38,'157.32.218.38','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 20:34:06','2026-06-16 20:34:06'),(179,37,'152.58.36.111','mobile','Chrome 149','Android 15','Surat, Gujarat, India','2026-06-16 20:34:19','2026-06-16 20:34:19'),(180,37,'152.58.36.111','mobile','Chrome 149','Android 15','Surat, Gujarat, India','2026-06-16 20:34:37','2026-06-16 20:34:37'),(181,39,'152.59.63.54','mobile','Chrome 148','Android 10','Pune, Maharashtra, India','2026-06-16 20:35:19','2026-06-16 20:35:19'),(182,39,'152.59.63.54','mobile','Chrome 148','Android 10','Pune, Maharashtra, India','2026-06-16 20:36:34','2026-06-16 20:36:34'),(183,40,'152.58.33.95','mobile','Safari 26','iOS 18.7','Pune, Maharashtra, India','2026-06-16 20:41:00','2026-06-16 20:41:00'),(184,40,'152.58.33.181','mobile','Safari 26','iOS 18.7','Pune, Maharashtra, India','2026-06-16 20:42:11','2026-06-16 20:42:11'),(185,39,'152.59.63.54','mobile','Chrome 148','Android 10','Pune, Maharashtra, India','2026-06-16 20:45:21','2026-06-16 20:45:21'),(186,39,'152.59.63.54','mobile','Chrome 148','Android 10','Pune, Maharashtra, India','2026-06-16 20:47:38','2026-06-16 20:47:38'),(187,39,'152.59.63.54','mobile','Chrome 148','Android 10','Pune, Maharashtra, India','2026-06-16 20:49:37','2026-06-16 20:49:37'),(188,41,'223.233.83.212','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 20:49:59','2026-06-16 20:49:59'),(189,41,'223.233.83.212','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 20:50:51','2026-06-16 20:50:51'),(190,42,'152.59.63.212','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 21:15:59','2026-06-16 21:15:59'),(191,42,'152.59.63.212','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 21:16:24','2026-06-16 21:16:24'),(192,42,'152.59.63.212','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 21:18:37','2026-06-16 21:18:37'),(193,22,'152.58.17.121','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 21:37:49','2026-06-16 21:37:49'),(194,43,'49.36.51.198','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 23:26:34','2026-06-16 23:26:34'),(195,43,'49.36.51.198','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 23:28:41','2026-06-16 23:28:41'),(196,11,'106.195.6.36','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-17 01:20:30','2026-06-17 01:20:30'),(197,44,'152.58.33.55','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-17 07:14:58','2026-06-17 07:14:58'),(198,44,'152.58.33.55','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-17 07:15:36','2026-06-17 07:15:36'),(199,45,'103.97.242.194','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-17 08:09:31','2026-06-17 08:09:31'),(200,46,'152.58.32.118','mobile','Chrome 148','Android 10','Pune, Maharashtra, India','2026-06-17 09:11:45','2026-06-17 09:11:45'),(201,46,'152.58.32.118','mobile','Chrome 148','Android 10','Pune, Maharashtra, India','2026-06-17 09:12:20','2026-06-17 09:12:20'),(202,46,'152.58.32.118','mobile','Chrome 148','Android 10','Pune, Maharashtra, India','2026-06-17 09:19:34','2026-06-17 09:19:34'),(203,26,'103.250.190.58','desktop','Chrome 149','Windows 10 / 11','Rajkot, Gujarat, India','2026-06-17 09:43:02','2026-06-17 09:43:02'),(204,47,'171.61.113.230','mobile','Chrome 149','Android 10','Jabalpur, Madhya Pradesh, India','2026-06-17 09:43:51','2026-06-17 09:43:51'),(205,47,'171.61.113.230','mobile','Chrome 149','Android 10','Jabalpur, Madhya Pradesh, India','2026-06-17 09:44:38','2026-06-17 09:44:38'),(206,48,'106.192.220.249','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-17 09:57:11','2026-06-17 09:57:11'),(207,48,'106.192.220.249','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-17 09:57:59','2026-06-17 09:57:59'),(208,49,'152.58.16.172','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-17 10:03:37','2026-06-17 10:03:37'),(209,49,'152.58.16.172','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-17 10:04:00','2026-06-17 10:04:00'),(211,50,'223.181.81.192','mobile','Safari 26','iOS 18.7','Raipur, Chhattisgarh, India','2026-06-17 11:06:10','2026-06-17 11:06:10'),(212,52,'59.184.16.24','mobile','Chrome 149','Android 10','Nandurbar, Maharashtra, India','2026-06-17 11:23:29','2026-06-17 11:23:29'),(213,52,'59.184.16.24','mobile','Chrome 149','Android 10','Nandurbar, Maharashtra, India','2026-06-17 11:23:58','2026-06-17 11:23:58'),(214,53,'223.185.41.135','mobile','Chrome 149','Android 10','Nagpur, Maharashtra, India','2026-06-17 11:28:14','2026-06-17 11:28:14'),(215,53,'223.185.41.135','mobile','Chrome 149','Android 10','Nagpur, Maharashtra, India','2026-06-17 11:28:44','2026-06-17 11:28:44'),(216,53,'223.185.41.135','mobile','Chrome 149','Android 10','Nagpur, Maharashtra, India','2026-06-17 11:29:53','2026-06-17 11:29:53'),(217,52,'59.184.16.24','mobile','Chrome 149','Android 10','Nandurbar, Maharashtra, India','2026-06-17 11:57:41','2026-06-17 11:57:41'),(218,54,'110.227.185.177','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-17 12:16:49','2026-06-17 12:16:49'),(219,2,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-17 12:17:35','2026-06-17 12:17:35'),(220,2,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-17 12:19:11','2026-06-17 12:19:11'),(221,55,'14.96.212.78','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-17 12:35:17','2026-06-17 12:35:17'),(222,55,'14.96.212.78','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-17 12:36:11','2026-06-17 12:36:11'),(223,54,'110.227.185.177','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-17 13:01:00','2026-06-17 13:01:00'),(224,56,'58.84.63.27','desktop','Chrome 109','Windows 8.1','Pune, Maharashtra, India','2026-06-17 13:28:58','2026-06-17 13:28:58'),(225,5,'152.58.47.9','mobile','Chrome 149','Android 10','Mumbai, Maharashtra, India','2026-06-17 14:12:12','2026-06-17 14:12:12'),(227,57,'106.192.114.113','mobile','Chrome 149','Android 14','Pune, Maharashtra, India','2026-06-17 19:03:02','2026-06-17 19:03:02'),(228,58,'152.56.14.156','mobile','Chrome 149','Android 10','Nagpur, Maharashtra, India','2026-06-17 20:04:16','2026-06-17 20:04:16'),(229,59,'103.216.147.88','desktop','Chrome 149','Windows 10 / 11','Latur, Maharashtra, India','2026-06-17 20:26:04','2026-06-17 20:26:04'),(230,60,'103.216.147.88','desktop','Chrome 149','Windows 10 / 11','Latur, Maharashtra, India','2026-06-17 20:28:53','2026-06-17 20:28:53'),(232,11,'106.215.181.94','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-17 21:39:10','2026-06-17 21:39:10'),(233,11,'106.215.181.94','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-17 21:42:45','2026-06-17 21:42:45'),(234,11,'106.215.181.94','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-17 21:43:45','2026-06-17 21:43:45'),(235,2,'106.215.181.94','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-17 21:45:49','2026-06-17 21:45:49'),(239,62,'49.36.48.61','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-17 22:57:59','2026-06-17 22:57:59'),(240,62,'49.36.48.61','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-17 22:58:28','2026-06-17 22:58:28'),(241,63,'152.58.33.15','desktop','Chrome 148','Windows 10 / 11','Pune, Maharashtra, India','2026-06-17 23:18:58','2026-06-17 23:18:58'),(242,64,'42.108.239.21','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-17 23:26:45','2026-06-17 23:26:45'),(243,64,'42.108.239.21','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-17 23:27:14','2026-06-17 23:27:14'),(244,65,'106.215.178.46','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-17 23:39:10','2026-06-17 23:39:10'),(245,66,'106.215.178.46','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-17 23:43:58','2026-06-17 23:43:58'),(246,66,'106.215.178.46','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-18 00:03:13','2026-06-18 00:03:13'),(247,65,'106.215.178.46','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-18 00:03:29','2026-06-18 00:03:29');
/*!40000 ALTER TABLE `login_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `message_requests`
--

DROP TABLE IF EXISTS `message_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_requests` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint NOT NULL,
  `recipient_id` bigint NOT NULL,
  `recipient_type` enum('candidate','firm') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','accepted','ignored') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_request_conv` (`conversation_id`),
  KEY `idx_request_recipient` (`recipient_id`,`recipient_type`,`status`),
  CONSTRAINT `fk_request_conv` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `message_requests`
--

LOCK TABLES `message_requests` WRITE;
/*!40000 ALTER TABLE `message_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `message_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint NOT NULL,
  `sender_id` bigint NOT NULL,
  `sender_type` enum('candidate','firm') COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_messages_conv_created` (`conversation_id`,`created_at`),
  KEY `idx_messages_sender` (`sender_id`,`sender_type`),
  KEY `idx_messages_unread` (`conversation_id`,`is_read`),
  CONSTRAINT `fk_messages_conv` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messaging_limits`
--

DROP TABLE IF EXISTS `messaging_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messaging_limits` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `firm_id` bigint NOT NULL,
  `lifetime_conversations_started` int NOT NULL DEFAULT '0',
  `lifetime_requests_unlocked` int NOT NULL DEFAULT '0',
  `monthly_conversations_started` int NOT NULL DEFAULT '0',
  `current_period_start` date NOT NULL,
  `current_period_end` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_messaging_limits_firm` (`firm_id`),
  CONSTRAINT `fk_messaging_limits_firm` FOREIGN KEY (`firm_id`) REFERENCES `firm_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messaging_limits`
--

LOCK TABLES `messaging_limits` WRITE;
/*!40000 ALTER TABLE `messaging_limits` DISABLE KEYS */;
/*!40000 ALTER TABLE `messaging_limits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messaging_settings`
--

DROP TABLE IF EXISTS `messaging_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `messaging_settings` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `firm_id` bigint NOT NULL,
  `accept_direct_messages` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_messaging_settings_firm` (`firm_id`),
  CONSTRAINT `fk_messaging_settings_firm` FOREIGN KEY (`firm_id`) REFERENCES `firm_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messaging_settings`
--

LOCK TABLES `messaging_settings` WRITE;
/*!40000 ALTER TABLE `messaging_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `messaging_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newsletter_subscribers`
--

DROP TABLE IF EXISTS `newsletter_subscribers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `newsletter_subscribers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subscribed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ns_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newsletter_subscribers`
--

LOCK TABLES `newsletter_subscribers` WRITE;
/*!40000 ALTER TABLE `newsletter_subscribers` DISABLE KEYS */;
INSERT INTO `newsletter_subscribers` VALUES (1,'palwesuraj2020@gmail.com','157.33.240.177','2026-06-17 13:32:10','2026-06-17 13:32:10','2026-06-17 13:32:10');
/*!40000 ALTER TABLE `newsletter_subscribers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` bigint DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_logs`
--

DROP TABLE IF EXISTS `payment_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `firm_subscription_id` bigint DEFAULT NULL,
  `payment_id` bigint unsigned DEFAULT NULL,
  `event_type` varchar(255) NOT NULL,
  `payload` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_payment_id` (`payment_id`,`firm_subscription_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_logs`
--

LOCK TABLES `payment_logs` WRITE;
/*!40000 ALTER TABLE `payment_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `firm_id` bigint unsigned NOT NULL,
  `plan_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'INR',
  `payment_gateway` enum('razorpay','manual') NOT NULL,
  `gateway_order_id` varchar(255) DEFAULT NULL,
  `gateway_payment_id` varchar(255) DEFAULT NULL,
  `gateway_signature` text,
  `transaction_reference` varchar(255) DEFAULT NULL,
  `payment_method` varchar(100) DEFAULT NULL,
  `screenshot` varchar(255) DEFAULT NULL,
  `status` enum('pending','paid','failed','cancelled','refunded','manual_verification') DEFAULT 'pending',
  `notes` text,
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_firm_id` (`firm_id`),
  KEY `idx_plan_id` (`plan_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plans`
--

DROP TABLE IF EXISTS `plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `duration_days` int NOT NULL,
  `features` json DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plans`
--

LOCK TABLES `plans` WRITE;
/*!40000 ALTER TABLE `plans` DISABLE KEYS */;
/*!40000 ALTER TABLE `plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `platform_settings`
--

DROP TABLE IF EXISTS `platform_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `platform_settings` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` bigint DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ps_key` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `platform_settings`
--

LOCK TABLES `platform_settings` WRITE;
/*!40000 ALTER TABLE `platform_settings` DISABLE KEYS */;
INSERT INTO `platform_settings` VALUES (1,'show_companies_to_students','false',NULL,3,'2026-06-12 23:13:16'),(2,'show_students_to_firms','false',NULL,3,'2026-06-12 23:10:38'),(3,'online_payments_enabled','false',NULL,3,'2026-06-15 12:48:05');
/*!40000 ALTER TABLE `platform_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `premium_requests`
--

DROP TABLE IF EXISTS `premium_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `premium_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `firm_id` bigint unsigned NOT NULL,
  `contact_person` varchar(255) NOT NULL,
  `firm_name` varchar(255) NOT NULL,
  `plan` varchar(100) NOT NULL,
  `amount` varchar(45) DEFAULT NULL,
  `transaction_id` varchar(255) NOT NULL,
  `payment_date` date NOT NULL,
  `screenshot_url` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `remarks` text,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `premium_requests`
--

LOCK TABLES `premium_requests` WRITE;
/*!40000 ALTER TABLE `premium_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `premium_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `queue_jobs`
--

DROP TABLE IF EXISTS `queue_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `queue_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1029 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `queue_jobs`
--

LOCK TABLES `queue_jobs` WRITE;
/*!40000 ALTER TABLE `queue_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `queue_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recruiter_actions`
--

DROP TABLE IF EXISTS `recruiter_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recruiter_actions` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `firm_id` bigint NOT NULL,
  `student_id` bigint NOT NULL,
  `visible_to` enum('student','firm','both') DEFAULT 'student',
  `job_id` bigint DEFAULT NULL,
  `application_id` bigint DEFAULT NULL,
  `action_type` varchar(100) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text,
  `action_status` varchar(100) DEFAULT NULL,
  `action_date` datetime DEFAULT NULL,
  `action_note` text,
  `meta` json DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_recruiter_actions_student_id` (`student_id`),
  KEY `idx_recruiter_actions_firm_id` (`firm_id`),
  KEY `idx_recruiter_actions_job_id` (`job_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recruiter_actions`
--

LOCK TABLES `recruiter_actions` WRITE;
/*!40000 ALTER TABLE `recruiter_actions` DISABLE KEYS */;
INSERT INTO `recruiter_actions` VALUES (1,1,4,'student',NULL,NULL,'profile_viewed','Profile viewed','Test viewed your profile.','viewed',NULL,NULL,NULL,0,'2026-06-11 15:50:32'),(2,1,3,'student',NULL,NULL,'profile_viewed','Profile viewed','Test viewed your profile.','viewed',NULL,NULL,NULL,0,'2026-06-11 17:03:16'),(3,1,8,'student',NULL,NULL,'profile_viewed','Profile viewed','Test viewed your profile.','viewed',NULL,NULL,NULL,0,'2026-06-12 17:20:51'),(4,1,10,'student',NULL,NULL,'profile_viewed','Profile viewed','Test viewed your profile.','viewed',NULL,NULL,NULL,0,'2026-06-12 23:10:59'),(5,1,6,'student',NULL,NULL,'profile_viewed','Profile viewed','Test viewed your profile.','viewed',NULL,NULL,NULL,0,'2026-06-12 23:12:06');
/*!40000 ALTER TABLE `recruiter_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `referral_payouts`
--

DROP TABLE IF EXISTS `referral_payouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `referral_payouts` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `referrer_user_id` bigint NOT NULL,
  `referred_user_id` bigint NOT NULL,
  `firm_subscription_id` bigint DEFAULT NULL,
  `plan` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reward_amount` decimal(10,2) NOT NULL DEFAULT '2000.00',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approved_by` bigint DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `paid_reference` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_referral_payout_referred` (`referred_user_id`),
  KEY `idx_referral_payout_referrer` (`referrer_user_id`),
  KEY `idx_referral_payout_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `referral_payouts`
--

LOCK TABLES `referral_payouts` WRITE;
/*!40000 ALTER TABLE `referral_payouts` DISABLE KEYS */;
/*!40000 ALTER TABLE `referral_payouts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reported_profiles`
--

DROP TABLE IF EXISTS `reported_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reported_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `reported_by` bigint unsigned NOT NULL,
  `reason` varchar(100) NOT NULL,
  `reported_field` varchar(100) DEFAULT NULL,
  `description` text,
  `remarks` text,
  `evidence_path` varchar(255) DEFAULT NULL,
  `admin_remarks` text,
  `status` enum('pending','reviewed','dismissed','awaiting_student','warning_issued') NOT NULL DEFAULT 'pending',
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_student` (`student_id`),
  KEY `idx_reported_by` (`reported_by`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reported_profiles`
--

LOCK TABLES `reported_profiles` WRITE;
/*!40000 ALTER TABLE `reported_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `reported_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `saved_jobs`
--

DROP TABLE IF EXISTS `saved_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `saved_jobs` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `student_id` bigint DEFAULT NULL,
  `job_id` bigint DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `idx_saved_student_job` (`student_id`,`job_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saved_jobs`
--

LOCK TABLES `saved_jobs` WRITE;
/*!40000 ALTER TABLE `saved_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `saved_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `saved_students`
--

DROP TABLE IF EXISTS `saved_students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `saved_students` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `firm_id` bigint DEFAULT NULL,
  `student_id` bigint DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saved_students`
--

LOCK TABLES `saved_students` WRITE;
/*!40000 ALTER TABLE `saved_students` DISABLE KEYS */;
/*!40000 ALTER TABLE `saved_students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_departments`
--

DROP TABLE IF EXISTS `student_departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_departments` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `student_profile_id` bigint DEFAULT NULL,
  `department_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_profile_id` (`student_profile_id`),
  CONSTRAINT `student_departments_ibfk_1` FOREIGN KEY (`student_profile_id`) REFERENCES `student_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_departments`
--

LOCK TABLES `student_departments` WRITE;
/*!40000 ALTER TABLE `student_departments` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_premium_requests`
--

DROP TABLE IF EXISTS `student_premium_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_premium_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `plan` enum('premium-monthly','premium-quarterly','premium-yearly') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `utr_number` varchar(100) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `screenshot_url` text,
  `payment_date` date NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_remarks` text,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_premium_requests`
--

LOCK TABLES `student_premium_requests` WRITE;
/*!40000 ALTER TABLE `student_premium_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_premium_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_profiles`
--

DROP TABLE IF EXISTS `student_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_profiles` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` bigint DEFAULT NULL,
  `looking_for` varchar(50) DEFAULT NULL,
  `is_creator` tinyint(1) NOT NULL DEFAULT '0',
  `preferred_categories` json DEFAULT NULL,
  `srn` varchar(100) DEFAULT NULL,
  `address` text,
  `city` varchar(120) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `passing_month` varchar(50) DEFAULT NULL,
  `ca_status` varchar(50) DEFAULT NULL,
  `registration_type` varchar(50) DEFAULT NULL,
  `articleship_status` varchar(50) DEFAULT NULL,
  `preferred_location` json DEFAULT NULL,
  `it_oc_status` varchar(50) DEFAULT NULL,
  `exposure_type` json DEFAULT NULL,
  `core_department` varchar(100) DEFAULT NULL,
  `attempts` varchar(50) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `portfolio_url` varchar(255) DEFAULT NULL,
  `instagram_url` varchar(500) DEFAULT NULL,
  `website_url` varchar(500) DEFAULT NULL,
  `current_firm_id` bigint DEFAULT NULL,
  `current_firm_name` varchar(255) DEFAULT NULL,
  `experience_years` varchar(50) DEFAULT NULL,
  `industry_worked_in` json DEFAULT NULL,
  `experience_department` json DEFAULT NULL,
  `why_should_hire_you` text,
  `qualification` varchar(50) DEFAULT NULL,
  `availability_status` varchar(50) DEFAULT NULL,
  `current_ctc` varchar(50) DEFAULT NULL,
  `expected_ctc` varchar(50) DEFAULT NULL,
  `marksheet_path` varchar(255) DEFAULT NULL,
  `show_in_directory` tinyint(1) DEFAULT '1',
  `apply_limit_modal_dismissed` tinyint(1) NOT NULL DEFAULT '0',
  `resume_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_student_looking_for` (`looking_for`),
  KEY `idx_student_city` (`city`),
  KEY `idx_student_registration_type` (`registration_type`),
  KEY `idx_student_passing_month` (`passing_month`,`show_in_directory`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_profiles`
--

LOCK TABLES `student_profiles` WRITE;
/*!40000 ALTER TABLE `student_profiles` DISABLE KEYS */;
INSERT INTO `student_profiles` VALUES (1,1,'articleship',0,NULL,'WRO0800459','PUNE','PUNE','male',NULL,'inter-g2','provisional',NULL,'[]',NULL,'[]',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ARTH & Associates',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,NULL,'2026-06-10 15:23:24','2026-06-12 20:29:50'),(2,3,'doing-articleship',0,NULL,'ERO0268744','KOLKATA','KOLKATA','female',NULL,'doing-articleship','provisional',NULL,'[]',NULL,'[]',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Komandoor & Co LLP',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-10 20:36:16','2026-06-10 20:41:59'),(3,4,'articleship',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-11 13:15:03','2026-06-11 13:15:03'),(4,5,'semi-qualified',0,NULL,'WRO0793585','KALYAN','KALYAN','male',NULL,NULL,'confirm',NULL,'[\"THANE\", \"MUMBAI\"]',NULL,'[\"Statutory Audit\", \"Tax Audit\", \"Direct Tax\", \"GST & Indirect Tax\", \"International Taxation\", \"Transfer Pricing\", \"Accounting & Bookkeeping\", \"Advisory & Consulting\", \"Corporate Laws & LLP\", \"FEMA & Foreign Trade\", \"Information Technology Services\", \"RERA Compliance\"]','Tax Audit',NULL,NULL,NULL,NULL,NULL,NULL,'Khushalani & Co CA Firm','3','[\"Other\"]','[\"Statutory Audit\", \"Tax Audit\", \"Direct Tax\", \"GST & Indirect Tax\", \"Accounting & Bookkeeping\"]',NULL,NULL,NULL,NULL,'360000',NULL,1,1,'resumes/1781197793_resume.pdf','2026-06-11 22:30:23','2026-06-12 10:20:14'),(5,6,'semi-qualified',0,NULL,'WRO0697511','PUNE','PUNE','male',NULL,NULL,'confirm',NULL,'[\"PUNE\"]',NULL,'[\"Internal Audit\", \"Bank & Concurrent Audit\", \"Forensic Audit & Investigation\", \"Risk & Internal Controls\"]','Internal Audit',NULL,NULL,NULL,NULL,NULL,NULL,'ARTH and Associates','3','[\"Manufacturing\"]','[\"Internal Audit\", \"Bank & Concurrent Audit\", \"Forensic Audit & Investigation\", \"Risk & Internal Controls\"]',NULL,NULL,NULL,NULL,NULL,NULL,1,1,'resumes/1781255556_resume.jpg','2026-06-12 14:30:56','2026-06-12 10:20:14'),(6,7,'articleship',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-12 14:38:41','2026-06-12 14:38:41'),(7,8,'articleship',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-12 17:01:47','2026-06-12 17:01:47'),(8,9,'articleship',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-12 17:15:36','2026-06-12 17:15:36'),(9,10,'articleship',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-12 17:28:19','2026-06-12 17:28:19'),(10,11,'articleship',0,NULL,'uhfwehj','PUNE','PUNE','male','May 2026','inter-both','confirm',NULL,'[\"PUNE\"]','pending','[\"GST & Indirect Tax\", \"Internal Audit\", \"Accounting & Bookkeeping\"]','Internal Audit','3+',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CA Student',NULL,NULL,NULL,NULL,1,1,'resumes/1781284789_resume.pdf','2026-06-12 22:31:02','2026-06-17 21:39:53'),(11,12,'articleship',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-14 10:49:13','2026-06-14 10:49:13'),(12,13,'semi-qualified',0,NULL,'1234567890','PUNE','PUNE','male',NULL,NULL,'confirm',NULL,'[\"PUNE\"]',NULL,'[\"overall\"]','Direct Tax',NULL,NULL,NULL,NULL,NULL,1,'Test','2','[\"Manufacturing\", \"IT / SaaS\"]','[\"Statutory Audit\", \"Internal Audit\"]','test',NULL,NULL,'4','6',NULL,1,0,NULL,'2026-06-15 12:05:01','2026-06-17 13:28:23'),(13,14,'semi-qualified',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-15 12:24:33','2026-06-15 12:24:33'),(14,15,'semi-qualified',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-15 23:59:15','2026-06-15 23:59:15'),(15,16,'articleship',0,NULL,'WRO0825626','KALYAN','KALYAN','male',NULL,'pursuing-inter','provisional',NULL,'[]',NULL,'[]',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,NULL,'2026-06-16 00:32:48','2026-06-16 00:36:35'),(16,17,'articleship',0,NULL,NULL,'INDORE','INDORE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 01:25:17','2026-06-16 01:25:17'),(17,18,'semi-qualified',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 12:35:00','2026-06-16 12:35:00'),(18,19,'articleship',0,NULL,NULL,'PIMPRI-CHINCHWAD','PIMPRI-CHINCHWAD',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 12:36:57','2026-06-16 12:36:57'),(19,20,'articleship',0,NULL,'WRO0795484','LATUR','LATUR','male','Jan 2026','inter-both','confirm',NULL,'[\"PUNE\", \"MUMBAI\"]','both','[\"overall\"]','Valuation','3+',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,NULL,'2026-06-16 13:21:28','2026-06-16 13:25:00'),(20,21,'creator',0,NULL,NULL,'CHHATRAPATI SAMBHAJINAGAR','CHHATRAPATI SAMBHAJINAGAR',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 13:39:47','2026-06-16 13:39:47'),(21,23,'articleship',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 14:04:01','2026-06-16 14:04:01'),(22,24,'qualified',0,NULL,NULL,'NANDED','NANDED',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 14:16:54','2026-06-16 14:16:54'),(23,25,'semi-qualified',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 14:51:05','2026-06-16 14:51:05'),(24,29,'articleship',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 18:24:49','2026-06-16 18:24:49'),(25,30,'articleship',0,NULL,'WRO0623067','PUNE','PUNE','male','Sep 2025','inter-both','confirm',NULL,'[\"PUNE\"]','both','[\"overall\"]','Internal Audit','3+',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,NULL,'2026-06-16 18:34:59','2026-06-16 18:39:13'),(26,31,'articleship',0,NULL,'wro0781576','SILLOD','SILLOD','male','Jan 2026','inter-both','confirm',NULL,'[\"PUNE\", \"SILLOD\", \"MUMBAI\"]','both','[\"overall\"]','Statutory Audit','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,NULL,'2026-06-16 18:52:31','2026-06-16 18:56:48'),(27,32,'articleship',0,NULL,NULL,'JAMMU','JAMMU',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 18:57:44','2026-06-16 18:57:44'),(28,33,'articleship',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 18:57:55','2026-06-16 18:57:55'),(29,34,'articleship',0,NULL,'APP3780910','AKOLA','AKOLA','female',NULL,'pursuing-inter','provisional',NULL,'[]',NULL,'[]',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,NULL,'2026-06-16 19:10:08','2026-06-16 19:13:29'),(30,35,'articleship',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 19:32:08','2026-06-16 19:32:08'),(31,37,'qualified',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 20:32:43','2026-06-16 20:32:43'),(32,38,'semi-qualified',0,NULL,'WRO0559795','PUNE','PUNE','male',NULL,NULL,'confirm',NULL,'[\"PUNE\"]',NULL,'[\"overall\"]','Statutory Audit',NULL,NULL,NULL,NULL,NULL,NULL,'MSDN and Associates','3',NULL,'[\"Statutory Audit\", \"Tax Audit\", \"Direct Tax\", \"GST & Indirect Tax\", \"Accounting & Bookkeeping\"]','I bring a strong foundation in accounting and finance along with practical audit experience.\r\nAs a semi-qualified CA, I possess analytical skills, attention to detail, and a solid understanding of financial processes.\r\nI am a quick learner, adaptable to new systems and committed to continuous improvement.',NULL,NULL,'0.96','6.5','marksheets/1781622523_marksheet.jpg',1,0,NULL,'2026-06-16 20:33:26','2026-06-16 20:38:43'),(33,39,'creator',0,'[\"Finance Content Creator\", \"Finance Article / Blog Writer\"]',NULL,'PUNE','PUNE','male',NULL,NULL,'provisional',NULL,'[]',NULL,'[]',NULL,NULL,'https://www.linkedin.com/in/akash-pund-771602323?utm_source=share_via&utm_content=profile&utm_medium=member_android',NULL,NULL,NULL,NULL,NULL,'0',NULL,NULL,'I am a quick learner and adept at acquiring business knowledge relevant to my projects. Additionally, I excel both as an individual contributor and as a team player.','Semi Qualified CA','Part Time',NULL,NULL,NULL,1,0,NULL,'2026-06-16 20:35:14','2026-06-16 20:49:47'),(34,40,'articleship',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 20:40:45','2026-06-16 20:40:45'),(35,41,'articleship',0,NULL,NULL,'JALNA','JALNA',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 20:49:36','2026-06-16 20:49:36'),(36,42,'creator',0,'[\"PPT & Presentation Designer\"]',NULL,'PUNE','PUNE','male',NULL,NULL,'provisional',NULL,'[]',NULL,'[]',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0',NULL,NULL,'I am a quick learner and want to give my best wherever I get opportunity.','CA Student','Available',NULL,NULL,NULL,1,0,NULL,'2026-06-16 21:15:45','2026-06-16 21:18:24'),(37,43,'qualified',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 23:26:16','2026-06-16 23:26:16'),(38,44,'creator',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-17 07:14:41','2026-06-17 07:14:41'),(39,46,'qualified',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-17 09:11:25','2026-06-17 09:11:25'),(40,47,'creator',0,NULL,NULL,'INDORE','INDORE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-17 09:43:14','2026-06-17 09:43:14'),(41,48,'creator',0,NULL,NULL,'AHMEDNAGAR','AHMEDNAGAR',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-17 09:56:50','2026-06-17 09:56:50'),(42,49,'semi-qualified',0,NULL,'Wro0613485','PUNE','PUNE','male',NULL,NULL,'confirm',NULL,'[\"PUNE\", \"MUMBAI\", \"AKOLA\"]',NULL,'[\"overall\"]','GST & Indirect Tax',NULL,NULL,NULL,NULL,NULL,NULL,'Complyhappy Finserve Pvt Ltd','3','[\"Services\"]','[\"Tax Audit\", \"Direct Tax\", \"GST & Indirect Tax\", \"Accounting & Bookkeeping\"]',NULL,NULL,NULL,'4.2','6.5',NULL,1,1,'resumes/1781670976_resume.pdf','2026-06-17 10:03:16','2026-06-17 10:06:26'),(43,50,'qualified',0,NULL,NULL,'RAIPUR','RAIPUR',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-17 11:05:42','2026-06-17 11:05:42'),(44,52,'articleship',1,'[\"PPT & Presentation Designer\", \"Short Video Creator (Reels/Shorts)\"]','WRO0770438','DHULE','DHULE','male',NULL,'pursuing-inter','provisional',NULL,'[]',NULL,'[]',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1',NULL,NULL,'I believe your firm should hire me because I can transform complex information into clear, engaging, and visually appealing presentations and videos. I have a strong understanding of presentation design, content organization, and visual storytelling, which helps communicate ideas effectively.\r\n\r\nI am detail-oriented, creative, and committed to delivering high-quality work within deadlines. Whether it is creating professional PowerPoint presentations, designing impactful slides, or editing short videos for social media and business purposes, I focus on making content both informative and engaging.\r\n\r\nI am also eager to learn new tools, adapt to client requirements, and continuously improve my skills. My goal is not only to complete tasks but to help the firm present its ideas in a way that attracts attention and creates a lasting impact.','CA Student','Part Time',NULL,NULL,NULL,1,0,NULL,'2026-06-17 11:23:07','2026-06-17 11:46:21'),(45,53,'articleship',0,NULL,NULL,'MUMBAI','MUMBAI',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-17 11:28:00','2026-06-17 11:28:00'),(46,57,'articleship',0,NULL,'WRO0743109','PUNE','PUNE','female',NULL,'pursuing-inter','provisional',NULL,'[]',NULL,'[]',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-17 19:02:39','2026-06-17 19:03:47'),(47,58,'creator',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-17 20:03:58','2026-06-17 20:03:58'),(48,59,'creator',0,NULL,NULL,'LATUR','LATUR',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-17 20:24:25','2026-06-17 20:24:25'),(49,61,'creator',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-17 22:36:31','2026-06-17 22:36:31'),(50,63,'articleship',0,NULL,'WRO0746956','MALEGAON','MALEGAON','male','Sep 2025','inter-both','confirm',NULL,'[\"MUMBAI\"]','both','[\"overall\"]','Tax Audit','3+',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,NULL,'2026-06-17 23:18:46','2026-06-17 23:23:44'),(51,64,'semi-qualified',0,NULL,'WRO0238888','PUNE','PUNE','female',NULL,NULL,'confirm',NULL,'[\"PUNE\"]',NULL,'[\"Statutory Audit\", \"Internal Audit\", \"Tax Audit\", \"Direct Tax\", \"GST & Indirect Tax\"]','GST & Indirect Tax',NULL,NULL,NULL,NULL,NULL,NULL,'Splash and company','13',NULL,'[\"Statutory Audit\", \"Internal Audit\", \"Tax Audit\", \"Direct Tax\", \"GST & Indirect Tax\", \"Accounting & Bookkeeping\"]','I have 13 years of relevant experience in a CA firm, strong technical knowledge, and a proven track record of handling assignments efficiently. I can contribute immediately and help the organization achieve its goals.',NULL,NULL,'540000','700000',NULL,1,1,'resumes/1781719534_resume.pdf','2026-06-17 23:26:20','2026-06-17 23:47:46'),(52,65,'articleship',0,NULL,'1234567891','PUNE','PUNE','male',NULL,'inter-g2','provisional',NULL,'[]',NULL,'[]',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-17 23:38:59','2026-06-17 23:41:11');
/*!40000 ALTER TABLE `student_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_subscriptions`
--

DROP TABLE IF EXISTS `student_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `plan` varchar(50) NOT NULL DEFAULT 'premium',
  `status` enum('active','expired','cancelled') NOT NULL DEFAULT 'active',
  `starts_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_expires` (`expires_at`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_subscriptions`
--

LOCK TABLES `student_subscriptions` WRITE;
/*!40000 ALTER TABLE `student_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_wallets`
--

DROP TABLE IF EXISTS `student_wallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_wallets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `available_balance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `hold_balance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `consumed_balance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `free_applications_used` int NOT NULL DEFAULT '0',
  `free_applications_limit` int NOT NULL DEFAULT '3',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_id` (`user_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_wallets`
--

LOCK TABLES `student_wallets` WRITE;
/*!40000 ALTER TABLE `student_wallets` DISABLE KEYS */;
INSERT INTO `student_wallets` VALUES (1,1,0.00,0.00,0.00,0,3,'2026-06-10 16:37:56','2026-06-10 16:37:56'),(2,5,0.00,0.00,0.00,0,3,'2026-06-11 22:40:36','2026-06-11 22:40:36'),(3,6,0.00,0.00,0.00,0,3,'2026-06-12 14:43:55','2026-06-12 14:43:55'),(4,11,0.00,0.00,0.00,0,3,'2026-06-12 22:50:42','2026-06-12 22:50:42'),(5,16,0.00,0.00,0.00,0,3,'2026-06-16 00:36:36','2026-06-16 00:36:36'),(6,13,0.00,0.00,0.00,0,3,'2026-06-16 01:22:05','2026-06-16 01:22:05'),(7,20,0.00,0.00,0.00,0,3,'2026-06-16 13:25:00','2026-06-16 13:25:00'),(8,30,0.00,0.00,0.00,0,3,'2026-06-16 18:39:14','2026-06-16 18:39:14'),(9,31,0.00,0.00,0.00,0,3,'2026-06-16 18:56:48','2026-06-16 18:56:48'),(10,34,0.00,0.00,0.00,0,3,'2026-06-16 19:13:04','2026-06-16 19:13:04'),(11,49,0.00,0.00,0.00,0,3,'2026-06-17 10:06:26','2026-06-17 10:06:26'),(12,52,0.00,0.00,0.00,0,3,'2026-06-17 11:46:29','2026-06-17 11:46:29'),(13,57,0.00,0.00,0.00,0,3,'2026-06-17 19:04:09','2026-06-17 19:04:09'),(14,65,0.00,0.00,0.00,0,3,'2026-06-17 23:41:18','2026-06-17 23:41:18'),(15,64,0.00,0.00,0.00,0,3,'2026-06-17 23:47:48','2026-06-17 23:47:48');
/*!40000 ALTER TABLE `student_wallets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_coin_accounts`
--

DROP TABLE IF EXISTS `sys_coin_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_coin_accounts` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` bigint NOT NULL,
  `available_coins` int NOT NULL DEFAULT '0',
  `hold_coins` int NOT NULL DEFAULT '0',
  `consumed_coins` int NOT NULL DEFAULT '0',
  `lifetime_earned` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_sys_coin_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_coin_accounts`
--

LOCK TABLES `sys_coin_accounts` WRITE;
/*!40000 ALTER TABLE `sys_coin_accounts` DISABLE KEYS */;
INSERT INTO `sys_coin_accounts` VALUES (1,13,0,0,0,0,'2026-06-16 01:22:05','2026-06-16 01:22:05'),(2,11,0,0,0,0,'2026-06-16 11:59:47','2026-06-16 11:59:47'),(3,20,0,0,0,0,'2026-06-16 13:25:00','2026-06-16 13:25:00'),(4,30,0,0,0,0,'2026-06-16 18:39:14','2026-06-16 18:39:14'),(5,31,0,0,0,0,'2026-06-16 18:56:48','2026-06-16 18:56:48'),(6,34,100,0,0,100,'2026-06-16 19:12:56','2026-06-16 19:12:56'),(7,49,0,0,0,0,'2026-06-17 10:06:26','2026-06-17 10:06:26'),(8,52,100,0,0,100,'2026-06-17 11:41:19','2026-06-17 11:41:19'),(9,5,0,0,0,0,'2026-06-17 14:12:23','2026-06-17 14:12:23'),(10,57,100,0,0,100,'2026-06-17 19:03:47','2026-06-17 19:03:47'),(11,65,100,0,0,100,'2026-06-17 23:41:11','2026-06-17 23:41:11'),(12,64,0,0,0,0,'2026-06-17 23:47:48','2026-06-17 23:47:48');
/*!40000 ALTER TABLE `sys_coin_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_coin_holds`
--

DROP TABLE IF EXISTS `sys_coin_holds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_coin_holds` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` bigint NOT NULL,
  `application_id` bigint NOT NULL,
  `job_id` bigint DEFAULT NULL,
  `amount` int NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'held',
  `held_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `consumed_at` timestamp NULL DEFAULT NULL,
  `released_at` timestamp NULL DEFAULT NULL,
  `release_reason` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hold_transaction_id` bigint DEFAULT NULL,
  `settle_transaction_id` bigint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sys_coin_hold_application` (`application_id`),
  KEY `idx_sys_coin_hold_user_status` (`user_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_coin_holds`
--

LOCK TABLES `sys_coin_holds` WRITE;
/*!40000 ALTER TABLE `sys_coin_holds` DISABLE KEYS */;
/*!40000 ALTER TABLE `sys_coin_holds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_coin_transactions`
--

DROP TABLE IF EXISTS `sys_coin_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sys_coin_transactions` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` bigint NOT NULL,
  `amount` int NOT NULL,
  `type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_type` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` bigint DEFAULT NULL,
  `application_id` bigint DEFAULT NULL,
  `job_id` bigint DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `balance_before` int NOT NULL DEFAULT '0',
  `balance_after` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sys_coin_tx_user` (`user_id`),
  KEY `idx_sys_coin_tx_type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_coin_transactions`
--

LOCK TABLES `sys_coin_transactions` WRITE;
/*!40000 ALTER TABLE `sys_coin_transactions` DISABLE KEYS */;
INSERT INTO `sys_coin_transactions` VALUES (1,34,100,'WELCOME_BONUS','welcome',34,NULL,NULL,'100 SYS Coins welcome bonus',0,100,'2026-06-16 19:12:56'),(2,52,100,'WELCOME_BONUS','welcome',52,NULL,NULL,'100 SYS Coins welcome bonus',0,100,'2026-06-17 11:41:19'),(3,57,100,'WELCOME_BONUS','welcome',57,NULL,NULL,'100 SYS Coins welcome bonus',0,100,'2026-06-17 19:03:47'),(4,65,100,'WELCOME_BONUS','welcome',65,NULL,NULL,'100 SYS Coins welcome bonus',0,100,'2026-06-17 23:41:11');
/*!40000 ALTER TABLE `sys_coin_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_setting_audits`
--

DROP TABLE IF EXISTS `system_setting_audits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_setting_audits` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_value` text COLLATE utf8mb4_unicode_ci,
  `new_value` text COLLATE utf8mb4_unicode_ci,
  `admin_user_id` bigint DEFAULT NULL,
  `admin_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_setting_audits_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_setting_audits`
--

LOCK TABLES `system_setting_audits` WRITE;
/*!40000 ALTER TABLE `system_setting_audits` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_setting_audits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_settings` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `setting_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string' COMMENT 'string|integer|decimal|boolean',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `is_editable` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `system_settings_setting_key_unique` (`setting_key`),
  KEY `idx_system_settings_category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'student_referral_reward','50','integer','Student Referral Reward','SYS Coins credited to the referrer when a referred student completes onboarding.','rewards',1,'2026-06-14 22:20:48','2026-06-14 22:35:27'),(2,'firm_premium_purchase_reward','500','integer','Firm Premium Purchase Reward','Amount (â‚¹) rewarded to the referrer when a referred firm buys premium.','rewards',1,'2026-06-14 22:20:48','2026-06-14 22:36:17'),(3,'welcome_bonus_coins','100','integer','Welcome Bonus Coins','SYS Coins granted once to provisional students on completing onboarding.','welcome_bonus',1,'2026-06-14 22:20:48','2026-06-14 22:20:48'),(4,'free_applications_count','3','integer','Free Applications Count','Number of free job applications a student gets before fees apply.','application',1,'2026-06-14 22:20:48','2026-06-14 22:20:48'),(5,'application_fee_amount','49','integer','Application Fee Amount','Wallet fee (â‚¹) charged per job application beyond the free quota.','application',1,'2026-06-14 22:20:48','2026-06-14 22:24:13'),(6,'minimum_wallet_recharge','150','integer','Minimum Wallet Recharge Amount','Smallest allowed wallet recharge amount (â‚¹).','wallet',1,'2026-06-14 22:20:48','2026-06-14 22:20:48'),(7,'payment_account_holder','MR. RITESH CHANDAK','string','Account Holder Name','Name on the bank account that receives manual payments.','payment',1,'2026-06-17 14:50:25','2026-06-17 14:50:25'),(8,'payment_bank_name','Bank of Baroda','string','Bank Name','Bank where the receiving account is held.','payment',1,'2026-06-17 14:50:25','2026-06-17 14:50:25'),(9,'payment_account_number','97980100019171','string','Account Number','Receiving bank account number.','payment',1,'2026-06-17 14:50:25','2026-06-17 14:50:25'),(10,'payment_ifsc','BARBODBMURU','string','IFSC Code','IFSC code of the receiving bank branch.','payment',1,'2026-06-17 14:50:25','2026-06-17 14:50:25'),(11,'payment_upi_id','9156235503@ybl','string','UPI ID','UPI ID shown to firms for manual UPI payments.','payment',1,'2026-06-17 14:50:25','2026-06-17 14:50:25'),(12,'payment_qr_code','','string','Payment QR Code','QR code image for manual UPI payments (optional).','payment',0,'2026-06-17 14:50:25','2026-06-17 14:50:25');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training_partners`
--

DROP TABLE IF EXISTS `training_partners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `training_partners` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `institute_name` varchar(255) NOT NULL,
  `logo` varchar(500) DEFAULT NULL,
  `banner_image` varchar(500) DEFAULT NULL,
  `short_description` text,
  `website_url` varchar(500) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `city` varchar(150) DEFAULT NULL,
  `state` varchar(150) DEFAULT NULL,
  `display_order` int DEFAULT '0',
  `starts_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_partners`
--

LOCK TABLES `training_partners` WRITE;
/*!40000 ALTER TABLE `training_partners` DISABLE KEYS */;
/*!40000 ALTER TABLE `training_partners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_sessions` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` bigint NOT NULL,
  `token` varchar(80) NOT NULL,
  `device_type` varchar(20) DEFAULT NULL,
  `browser` varchar(100) DEFAULT NULL,
  `os` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `last_activity_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_sessions_token` (`token`),
  KEY `idx_user_sessions_user_id` (`user_id`),
  CONSTRAINT `fk_user_sessions_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=248 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_sessions`
--

LOCK TABLES `user_sessions` WRITE;
/*!40000 ALTER TABLE `user_sessions` DISABLE KEYS */;
INSERT INTO `user_sessions` VALUES (3,2,'MENRWXlFTmk4UjV0dWtrblRIWkNPR1Zqd0RXWTB6OWFBdVlJTmdIOQ==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-17 15:33:16','2026-06-10 15:35:29','2026-06-10 15:33:16','2026-06-10 15:35:29'),(9,2,'ZW45YzNNVnJVbEE1Rk5kNVpaUTFybThDT3plUFdiaFE5Unh6d3lQVQ==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-17 16:56:32','2026-06-10 16:57:13','2026-06-10 16:56:32','2026-06-10 16:57:13'),(13,3,'dVljemxQVFJ3emt6MjUxTzlkOWFrc3lYV1VCeXAxSHNMdERhQUdUSA==','mobile','Chrome 149','Android 10','152.58.180.147','Kolkata, West Bengal, India','2026-06-17 20:36:27','2026-06-10 20:36:51','2026-06-10 20:36:27','2026-06-10 20:36:51'),(14,3,'RVFMT2dnb0xEWkVqaDNTRlVZVDJtWjFCR3ozUW9lZE8wQzVFRVJxNw==','mobile','Chrome 149','Android 10','152.58.180.147','Kolkata, West Bengal, India','2026-06-17 20:37:01','2026-06-10 20:40:27','2026-06-10 20:37:01','2026-06-10 20:40:27'),(15,3,'WXgwQzBGZXFuMmUyQkhLaTJBVjlkMHNHNXYwbEdlNnpwVFlRSXVjUg==','mobile','Chrome 149','Android 10','152.58.180.147','Kolkata, West Bengal, India','2026-06-17 20:41:08','2026-06-11 16:55:44','2026-06-10 20:41:08','2026-06-11 16:55:44'),(16,2,'NGpLZHJEakc3SE5hNG01STc1T3QzanZZZnBVQVZUWmJrTk5EMm1DUw==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-17 20:55:55','2026-06-11 17:03:58','2026-06-10 20:55:56','2026-06-11 17:03:58'),(17,4,'cGVzRHE0RGVIaFJRMXA2NDh2U3kwYmh0QzBSUjJ1elJxaUZBd3BiTw==','mobile','Chrome 138','Android 9','157.32.198.213','Pune, Maharashtra, India','2026-06-18 13:15:34','2026-06-11 13:15:34','2026-06-11 13:15:34','2026-06-11 13:15:34'),(18,4,'ekpIYWM1RkZzT2lRMXNPdnNmankwQmRsUXBTUmVpMEhIbGJhMkszZg==','mobile','Chrome 138','Android 9','157.32.198.213','Pune, Maharashtra, India','2026-06-18 13:15:50','2026-06-11 13:15:50','2026-06-11 13:15:50','2026-06-11 13:15:50'),(19,4,'cEdjbFJVZzlVelJEU2pEbGtIY3B2dUdnaGFFVEI2YUJxOWVKZUhXWg==','mobile','Chrome 123','Android 9','157.32.198.213','Pune, Maharashtra, India','2026-06-18 13:18:19','2026-06-11 13:18:25','2026-06-11 13:18:19','2026-06-11 13:18:25'),(20,5,'bDA3b0lSSzY1dlBiTnA5ckdWMWhaVDV1NTlHS0YzdktVanNPYUlGbQ==','mobile','Chrome 148','Android 10','152.58.44.210','Mumbai, Maharashtra, India','2026-06-18 22:30:47','2026-06-11 22:31:15','2026-06-11 22:30:47','2026-06-11 22:31:15'),(22,6,'d213Ykg1cDlUMG5NUDY5NjVXOGZ2R0hNbzVJQ014QzRackgxNXd5MA==','mobile','Chrome 149','Android 10','157.32.113.113','Pune, Maharashtra, India','2026-06-19 14:31:21','2026-06-12 14:31:55','2026-06-12 14:31:21','2026-06-12 14:31:55'),(23,6,'UHFTbzdEejF5TGt1ODd4VWF6ZmJzSnk5ZHNCcnA3cE9icFVVeGJQMA==','mobile','Chrome 149','Android 10','157.32.113.113','Pune, Maharashtra, India','2026-06-19 14:32:10','2026-06-16 18:14:23','2026-06-12 14:32:11','2026-06-16 18:14:23'),(24,7,'Q1ZsdE1pMmdwaElod3hiemZseWRBS29BM2JFeUVJeXVYUXF6cTYzZA==','desktop','Chrome 149','Windows 10 / 11','110.227.185.177','Pune, Maharashtra, India','2026-06-19 14:39:58','2026-06-12 14:39:59','2026-06-12 14:39:58','2026-06-12 14:39:59'),(25,7,'d21HOTlNOHhjcmZkTUZObU1ZM2xHVGlIRW9RQWFrcVo2OTluWFJrdQ==','desktop','Chrome 149','Windows 10 / 11','110.227.185.177','Pune, Maharashtra, India','2026-06-19 14:48:25','2026-06-12 14:48:26','2026-06-12 14:48:25','2026-06-12 14:48:26'),(27,8,'Q2lUQTdveHhjYThKc0plSHhmd0FuWHlwbHJ0d25jODV1YnNiWm5ldg==','mobile','Chrome 147','Android 10','27.97.174.133','Nagpur, Maharashtra, India','2026-06-19 17:02:15','2026-06-12 17:02:15','2026-06-12 17:02:15','2026-06-12 17:02:15'),(28,8,'MXhseldVM3JyVTd2TlRWRnpQUU9NRjJjVjl4eUZ2eUQ5QU9TTmxwVQ==','mobile','Chrome 147','Android 10','27.97.174.133','Nagpur, Maharashtra, India','2026-06-19 17:03:53','2026-06-12 17:07:03','2026-06-12 17:03:53','2026-06-12 17:07:03'),(29,9,'Yzd2NlZiTTREWmhuUG52ZUpZZTV3cVVZSnZPU1ZiVVQ0Q0pCRzFBbQ==','mobile','Samsung Browser 29','Android 10','27.97.180.154','Amravati, Maharashtra, India','2026-06-19 17:16:17','2026-06-12 17:16:43','2026-06-12 17:16:17','2026-06-12 17:16:43'),(30,9,'U3h4TktUSWRiQXJaellUN1dTOVYyUHQ4bnpZV2ZoN1NVdHVkMDlaRg==','mobile','Samsung Browser 29','Android 10','27.97.180.154','Amravati, Maharashtra, India','2026-06-19 17:16:46','2026-06-13 07:06:21','2026-06-12 17:16:46','2026-06-13 07:06:21'),(34,10,'ZTYwamx0aTBuMGpYS2NsYnNxREVuUHRuMmlSNnFXVjNyZjhic3doaw==','mobile','Chrome 149','Android 10','106.213.86.255','Pune, Maharashtra, India','2026-06-19 17:28:55','2026-06-12 18:43:44','2026-06-12 17:28:55','2026-06-12 18:43:44'),(41,11,'TGlZYVB1N1VXY29LUExTTlJFWUl1WExWdUx4b2E0ZEc4Qkt3T0Nmcw==','desktop','Chrome 149','Windows 10 / 11','223.236.99.173','Pune, Maharashtra, India','2026-06-19 22:31:06','2026-06-12 22:31:06','2026-06-12 22:31:06','2026-06-12 22:31:06'),(43,2,'NlpVbnZ3WXNlVm5KaEtHTVphZDYzbkhsQ1dvUE1YUTNoSFNmV0M5cA==','desktop','Chrome 149','Windows 10 / 11','223.236.99.173','Pune, Maharashtra, India','2026-06-19 23:06:12','2026-06-12 23:13:27','2026-06-12 23:06:12','2026-06-12 23:13:27'),(44,11,'QzNsSklWYVltckFJaEZMVXNpRnY3QVd0VGlrZldVWWZORThrZUZxcA==','mobile','Chrome 149','Android 10','152.58.16.59','Pune, Maharashtra, India','2026-06-20 01:53:32','2026-06-13 01:56:41','2026-06-13 01:53:32','2026-06-13 01:56:41'),(45,11,'R2ZyUm1RRktMYUhUNjd6VXJlb0pKVmVJYVpHcHFQdUd6bmF3RkFZRQ==','mobile','Chrome 149','Android 10','152.58.16.164','Pune, Maharashtra, India','2026-06-20 01:57:25','2026-06-13 01:57:49','2026-06-13 01:57:25','2026-06-13 01:57:49'),(46,11,'c0ZiT2VCYzY1QkpPZ3dMSWJxWGxmUnhOd25hVk5FeXpWdUZ3WGxHMw==','mobile','Chrome 149','Android 10','223.236.99.144','Pune, Maharashtra, India','2026-06-20 17:40:17','2026-06-13 17:40:35','2026-06-13 17:40:17','2026-06-13 17:40:35'),(47,12,'anhxaWViT0lrQXdZUmJabTVpekNxR1l5REEybzExbmY5M2Q0aEtHSA==','mobile','Chrome 148','Android 10','106.210.227.201','Kātol, Maharashtra, India','2026-06-21 10:49:26','2026-06-14 10:49:59','2026-06-14 10:49:26','2026-06-14 10:49:59'),(48,12,'VGI4U3VvSjZUS3Z1WTlhWlh4Uks4cThnRWZwNEZWa2hTWVRXN1lieQ==','mobile','Chrome 148','Android 10','106.210.227.201','Kātol, Maharashtra, India','2026-06-21 10:50:07','2026-06-14 10:50:08','2026-06-14 10:50:07','2026-06-14 10:50:08'),(49,11,'aG9pM2Q5MWlPcHVrQVR4N2d5Y1kyRGY5bDNQMlY3cXhKbVhoeWNxZA==','desktop','Chrome 149','Windows 10 / 11','152.58.31.43','Pune, Maharashtra, India','2026-06-21 13:05:44','2026-06-14 13:27:52','2026-06-14 13:05:44','2026-06-14 13:27:52'),(51,11,'cVBqcTRueU9VVU8wbEJldm9GRGZReEpHTVFZZ2pFdTRQQWF4QmMyTQ==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 10:18:39','2026-06-15 10:18:39','2026-06-15 10:18:39','2026-06-15 10:18:39'),(52,11,'Z2hyZXJ5dVdiV09qNlRCdXN5VGpXZFBwMVhHd2pCOHZGUzU2cHkyZg==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 10:18:42','2026-06-15 10:18:42','2026-06-15 10:18:42','2026-06-15 10:18:42'),(53,11,'RHhtRkVJMmJQd0ZOYU1CUkFyUFU5UWpUeTNRb0h5RlQ5ZExUUTVmZQ==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 10:18:46','2026-06-15 10:18:46','2026-06-15 10:18:46','2026-06-15 10:18:46'),(54,11,'U2ZVcnE0SDdvQlcyWE1IUU9hMng2WmcwMWFZU3NEN1JkWnZ0Sk1Daw==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 10:18:58','2026-06-15 10:18:58','2026-06-15 10:18:58','2026-06-15 10:18:58'),(55,11,'TEsxbU15cGhzUkNYdHhTRm1GY0JSVHZ5NGVGWmx1R29IcHF1Qjl5Rw==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 10:19:00','2026-06-15 10:19:00','2026-06-15 10:19:00','2026-06-15 10:19:00'),(56,2,'WHU0TTVoeFF3ZENoWDdTZFpkVWJKaEY3d2dIOHJVTWE4ZWNwREdKcQ==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 10:19:06','2026-06-15 10:19:06','2026-06-15 10:19:06','2026-06-15 10:19:06'),(57,11,'UjhkYkg1cFFPQlNkbmJLQUNWWmUybFhlSlNzSUZ1RTBDcTc3dVJxbA==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 10:19:09','2026-06-15 10:19:09','2026-06-15 10:19:09','2026-06-15 10:19:09'),(58,2,'V3lGd00zdHF5SDRMT0drQTl1Vm9EV1VUNFlDOUFLMXlPN0VUemhjZw==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 10:19:12','2026-06-15 10:19:12','2026-06-15 10:19:12','2026-06-15 10:19:12'),(59,11,'UFJaOEg2OGhqWTFkMWZRSVIzWUhVclRuWk0xT1JNQ2VrTmEwckpZMA==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 10:19:24','2026-06-15 10:19:24','2026-06-15 10:19:24','2026-06-15 10:19:24'),(60,11,'UEphUFNtR0hPa0RVRHpZRWVSa1dtNm9uYms5UFFwN0liNzhKZU5uSQ==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 10:19:26','2026-06-15 10:19:26','2026-06-15 10:19:26','2026-06-15 10:19:26'),(61,11,'WHowVFlOSTFUaGFDSE9GWFB0RXdSc0E1ZVljc2c4c1ptWmxoRmRlSQ==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 10:20:18','2026-06-15 10:20:18','2026-06-15 10:20:18','2026-06-15 10:20:18'),(62,11,'YVhuMmEwaTc5TDJtNHlmYUtjQ0ExWGllU3lhQlhhdUVUTGVKeVo0Rg==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 10:20:20','2026-06-15 10:20:21','2026-06-15 10:20:21','2026-06-15 10:20:21'),(63,11,'WE1TekR1TWVqR0VSRUV1dFVORGFJeVRCeFV5cGpRQXE1REd2VEFlNw==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 10:20:42','2026-06-15 10:20:42','2026-06-15 10:20:42','2026-06-15 10:20:42'),(64,11,'RjBpclFVOHpkaWJJUFBBSnlHQjRBNUdWSXhXR3FhNHlGOVZ2TzVLeg==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 10:20:43','2026-06-15 10:20:44','2026-06-15 10:20:44','2026-06-15 10:20:44'),(65,11,'VTFjeHdSRWRrUzVydTUwcnBjeXFpS0d0eEFrQlRxZjRoa1BjbWtHMA==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 10:23:07','2026-06-15 10:23:08','2026-06-15 10:23:08','2026-06-15 10:23:08'),(66,11,'cHpkMnFSdWJoVkNJd2s2a1lkYWpWNWxXZHRwVUlNVXVVQkpHdWwyTw==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 10:23:10','2026-06-15 10:23:10','2026-06-15 10:23:10','2026-06-15 10:23:10'),(67,11,'UzhVMk9NU2JMZk9CZ29JMWduT1dEbjN2VXpVNGZOR25VSTh5bUJyUA==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 10:23:21','2026-06-15 10:23:21','2026-06-15 10:23:21','2026-06-15 10:23:21'),(68,2,'WXR6V2M1UURVVWwwaWVXZ3AyN1lod3p3QTZzNzQybUx5OTNPZEpyZw==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 10:23:26','2026-06-15 10:23:26','2026-06-15 10:23:26','2026-06-15 10:23:26'),(70,11,'WnpHaWpLOWdaV3BxU0YycnJUM2ppNjcwYVM0VEk0VDVuSkJ3cGxCcA==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 10:23:32','2026-06-15 10:23:32','2026-06-15 10:23:32','2026-06-15 10:23:32'),(71,2,'RVdhaG5ldHA4eUNRblBlU2pYekliZWhPbEtWVWgxY1hPeTlWQmJiRg==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 10:23:36','2026-06-15 10:23:36','2026-06-15 10:23:36','2026-06-15 10:23:36'),(72,11,'dXZaWmFxZGdZZ0pFbUU3ZWwwQ0pGVnZxaDJvUDhrbk1RUUZrS3RnUw==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 10:24:00','2026-06-15 10:24:00','2026-06-15 10:24:00','2026-06-15 10:24:00'),(73,11,'dXNKMm1PeVFaNkdySFBycjd1SVVNWVdoUFNGWnlJdW9BMFlZQk5XVQ==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 10:24:02','2026-06-15 10:24:02','2026-06-15 10:24:02','2026-06-15 10:24:02'),(74,11,'c3dHR2tVYTJ0QzliellES3RNeGRWbXBvWHRpdWtKMkZ6ZG1LRWZEeA==','mobile','Chrome 149','Android 10','103.226.205.156','Pune, Maharashtra, India','2026-06-22 10:24:03','2026-06-15 10:24:04','2026-06-15 10:24:03','2026-06-15 10:24:04'),(75,2,'endZUVVxR1BqdWE2dGxPNHBVdUNJT012S01OZ0tkdWJTT0VkNmh2MA==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 10:24:07','2026-06-15 10:24:07','2026-06-15 10:24:07','2026-06-15 10:24:07'),(78,11,'cUFDZ2VjeElQNVkwTVhrMEpYVnJlRlhVenpCY2dMRElHR1ZJODRZbg==','mobile','Chrome 149','Android 10','103.226.205.156','Pune, Maharashtra, India','2026-06-22 10:28:09','2026-06-15 11:27:58','2026-06-15 10:28:09','2026-06-15 11:27:58'),(81,11,'TllnQmVuWFNDcUFmM2MxUUF4VDBzeGIyQnFna290Rk5kdEUwbVM0eA==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 11:05:37','2026-06-15 11:05:59','2026-06-15 11:05:37','2026-06-15 11:05:59'),(86,2,'aU9aVmpkUm80TjdSTm12Q1FRblNiRGt1N1pja3hyRDlaTm1xRzlZSQ==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 12:11:05','2026-06-15 12:11:09','2026-06-15 12:11:05','2026-06-15 12:11:09'),(87,11,'UHNkTHpTejVMNkN2ZGdoaUJrWTd3b2FYcHRFZmhxY3pZV0pMRkdjZw==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 12:16:51','2026-06-15 12:20:15','2026-06-15 12:16:51','2026-06-15 12:20:15'),(88,2,'dDVBTWZsYVR5MmpHaWQzazZ4WUd4OVE0dVBZeDgzYjlPVTJ5WXczMw==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 12:20:20','2026-06-15 12:20:20','2026-06-15 12:20:20','2026-06-15 12:20:20'),(89,11,'SDc1VkYyQzlST21RRU5NYXdIcGlwUWFnTjhpVUVOWHVWZ0FUUklmRw==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 12:20:23','2026-06-15 12:20:23','2026-06-15 12:20:23','2026-06-15 12:20:23'),(90,2,'VkU4VG1nOG1zMUZJNjZWenRNMEZDQ3VQR2szRmF6S2tPYVlTbEhlRg==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 12:20:32','2026-06-15 12:20:32','2026-06-15 12:20:32','2026-06-15 12:20:32'),(91,2,'VDJ2SXpnODB3YzF6OFJvQ3lsM2oyeXJ4TjF4Wno5b0hCbnozckI1Qg==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 12:21:12','2026-06-15 12:44:00','2026-06-15 12:21:12','2026-06-15 12:44:00'),(92,14,'MHFkNWc2VEFvUFJEd0s0R2YxbGN1UGRSSnU1S1RqU3hzYkxlZG5hZg==','mobile','Chrome 149','Android 10','152.57.156.135','Hyderabad, Telangana, India','2026-06-22 12:24:49','2026-06-15 12:26:53','2026-06-15 12:24:49','2026-06-15 12:26:53'),(95,11,'MTU3MjBqSWZ1aUtLY3Jicnk0a1J1UFdzdXZEeVpnQXRLd3hmMXY2Mg==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 14:46:58','2026-06-15 14:46:58','2026-06-15 14:46:58','2026-06-15 14:46:58'),(96,11,'SldWV3ZiNDd2WEtDa2d1Q3pFTVFoYTR3SGpsNXlLYW5iM1l5dlZ2cA==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 14:47:02','2026-06-15 14:47:02','2026-06-15 14:47:02','2026-06-15 14:47:02'),(97,11,'Sks3Tk1IUkRuaFlZWFBieXA0SENiRFZ2NXdIbkFiOHM3c3J0Q0NyRw==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 14:47:19','2026-06-15 14:47:19','2026-06-15 14:47:19','2026-06-15 14:47:19'),(98,2,'cnowcEEwdGs1cU1ZbEhZenJLNVROdlJoY05TS0loSXkwUW9YUlpOMw==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 14:47:22','2026-06-15 14:47:22','2026-06-15 14:47:22','2026-06-15 14:47:22'),(99,11,'ZkZyZk16dHU2Q0NjdHNMVmN6NkNYNlJDMUc4SFA4eUgzMktKSVRiVQ==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 14:47:44','2026-06-15 14:47:44','2026-06-15 14:47:44','2026-06-15 14:47:44'),(100,11,'bkExaEJDOUNEeUxrY1RPRXZ6RGQ5ZnN0Y001ODFtWXJSMzE0elpVZQ==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 14:48:41','2026-06-15 14:48:41','2026-06-15 14:48:41','2026-06-15 14:48:41'),(101,11,'bEp4VWdIbERCNFhrOEh0TGlpeUJCTDRvVmFHaGJvQnZ5ZnRlMkZzeQ==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 14:48:50','2026-06-15 14:48:50','2026-06-15 14:48:50','2026-06-15 14:48:50'),(104,2,'NmJ0VmdubUZ2aWc0YWQ0YkpaUkptVDBIOXhuaGdqb3VGdzNyNmZXdg==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 14:49:50','2026-06-15 14:52:55','2026-06-15 14:49:50','2026-06-15 14:52:55'),(106,11,'VlgyZkxFTVZpeWNvRXpLUUtoNEI1NHpVaWhTY25OeFZ6YmFkb0lhWg==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 15:15:53','2026-06-15 15:15:53','2026-06-15 15:15:53','2026-06-15 15:15:53'),(107,11,'aXl5V1VHWWFsWmw5dElaM0RKNzZ6OXJ4MUM2bHpYSnBLTkpzb25OMA==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 15:19:28','2026-06-15 15:19:29','2026-06-15 15:19:29','2026-06-15 15:19:29'),(108,11,'UWg0RUNIRjJPN2k0ZUFuMWgySGR1ZWpYQ3VNTHg4T2JBN1JvYVV1Tg==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 15:19:31','2026-06-15 15:19:31','2026-06-15 15:19:31','2026-06-15 15:19:31'),(109,11,'bkREaTFuYktPUVY0M0ZvY3NtOTgzcjRWRkprSlNTUVltMVFMSm9WOQ==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 15:19:54','2026-06-15 15:19:54','2026-06-15 15:19:54','2026-06-15 15:19:54'),(110,11,'WExiSTVpZUh5dklXcnl2RnlRY2ZacHROOGVrWjlIbnRTMGhnYlZ4Ng==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 15:21:58','2026-06-15 15:22:00','2026-06-15 15:21:58','2026-06-15 15:22:00'),(111,11,'bHpRVzhVMUk4b3FYY1BTSk9KMWhjeXd6M1ROSHc0TWpQcG5HT1ltdg==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 15:22:23','2026-06-15 15:22:23','2026-06-15 15:22:23','2026-06-15 15:22:23'),(112,11,'cFgxRE53Nzl0UExrV2kwc3BvZ1hWOWpFUmEzMHI4bnRKN0paaGhmYg==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 16:45:12','2026-06-15 16:45:12','2026-06-15 16:45:12','2026-06-15 16:45:12'),(113,11,'bmEzMGNLN3A1YUVlRVEzdVBuZUdEVjNWZ2RWdlp1YmtaZzN3eVBOTg==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 16:48:18','2026-06-15 16:48:18','2026-06-15 16:48:18','2026-06-15 16:48:18'),(114,11,'RXZwUFdoeXJNNm0zTzdBSzBtblp4NXVFZmxDZnBzNnYwMGVuWGk5MQ==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 16:48:22','2026-06-15 16:48:22','2026-06-15 16:48:22','2026-06-15 16:48:22'),(116,11,'OTIwVmxHOXU1cDZQZ3NsU1E1eUlCZ08zc2o4WFFabnNOR2EyNVFQSA==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-22 20:08:22','2026-06-15 20:08:23','2026-06-15 20:08:23','2026-06-15 20:08:23'),(119,11,'NGVua3RZenk1RllTcTVXMGh2RlRETVh0eEVxekZyZ2t4Z3E5WVd5TA==','desktop','Chrome 149','Windows 10 / 11','122.183.33.74','Pune, Maharashtra, India','2026-06-22 22:31:09','2026-06-15 22:31:09','2026-06-15 22:31:09','2026-06-15 22:31:09'),(120,11,'N2c5T3pQWnpEYm1QV25TV3JEd3RKbUlwanpWZ3NKdGtlRGpxWnJDVg==','desktop','Chrome 149','Windows 10 / 11','122.183.33.74','Pune, Maharashtra, India','2026-06-22 22:31:37','2026-06-15 22:31:37','2026-06-15 22:31:37','2026-06-15 22:31:37'),(121,2,'TXBHY0lVOEtXMUFkZE5XY0VlUVdpRmVwVnE1NUlPWTRWZXl5b0N0cg==','desktop','Chrome 149','Windows 10 / 11','122.183.33.74','Pune, Maharashtra, India','2026-06-22 22:31:47','2026-06-15 22:31:47','2026-06-15 22:31:47','2026-06-15 22:31:47'),(123,11,'dmhERlp4QlZzMHFMSFZ5REk5a0V4Y0lTVDZFM3EzREVCMmlBT2ZhQw==','mobile','Chrome 149','Android 10','122.183.33.74','Pune, Maharashtra, India','2026-06-22 22:41:20','2026-06-15 22:42:21','2026-06-15 22:41:20','2026-06-15 22:42:21'),(124,15,'SzBPbEZGZU9ldzdMWWZHT3hYVVlJRktzUVNzOVJZM0gyRHRWd2hJcg==','desktop','Chrome 147','Windows 10 / 11','182.69.179.145','New Delhi, National Capital Territory of Delhi, India','2026-06-22 23:59:24','2026-06-16 00:00:06','2026-06-15 23:59:24','2026-06-16 00:00:06'),(125,15,'WktQa0dnY1RrMzRMeTdpMm5YNnZJVDc1NWQybDhyOXpENWdxbTdSNA==','desktop','Chrome 147','Windows 10 / 11','182.69.179.145','New Delhi, National Capital Territory of Delhi, India','2026-06-23 00:00:16','2026-06-16 00:07:18','2026-06-16 00:00:17','2026-06-16 00:07:18'),(126,16,'bEpHdkhSWVM2SDZNR2M5MUF6SjhnS05qSEhGdWd1UWRCNHZIbklxYg==','desktop','Edge 149','Windows 10 / 11','103.49.254.7','Badlapur, Maharashtra, India','2026-06-23 00:33:58','2026-06-16 00:37:24','2026-06-16 00:33:58','2026-06-16 00:37:24'),(133,17,'dUxLTlQ0N2dNcm5mS2VucVltbW5wRHJVRDE3Sk1mSTVmcDU4U1pBcg==','mobile','Chrome 148','Android 10','152.59.29.76','Jabalpur, Madhya Pradesh, India','2026-06-23 01:25:31','2026-06-16 01:25:31','2026-06-16 01:25:31','2026-06-16 01:25:31'),(138,2,'alZlWUZMN0xoN1FTR0ljWUFhZENVV2FQZ1VvN3dUY1FKQm9panREVw==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-23 12:02:28','2026-06-16 12:04:20','2026-06-16 12:02:28','2026-06-16 12:04:20'),(139,18,'WXZvTzB6eE0wOWg4Q2lHcFRLeTJnR0JzS0pSbnFFVGw3TXByeUk0Rg==','mobile','Safari 18','iOS 18.5','223.228.136.12','Pune, Maharashtra, India','2026-06-23 12:35:25','2026-06-16 12:35:25','2026-06-16 12:35:25','2026-06-16 12:35:25'),(140,18,'VmlmMmhHOFdVU1g2Slh1dDUybDM3enFHTmFkTG8ydXFxNk9RaTF5Qw==','mobile','Safari 18','iOS 18.5','223.228.136.12','Pune, Maharashtra, India','2026-06-23 12:36:05','2026-06-16 13:20:28','2026-06-16 12:36:05','2026-06-16 13:20:28'),(141,19,'cTZSSEFvSHFuaWlFd0pzdHhROThKZXFQRG5ZdEVlQUF0d1dWT2hqTA==','desktop','Chrome 148','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-23 12:37:09','2026-06-16 12:39:02','2026-06-16 12:37:09','2026-06-16 12:39:02'),(144,20,'M09wQmhFTU0wWjlrYUZoSzhBdjlHNTVZUjdUVEFkMHcyMmdLckFMVA==','desktop','Chrome 149','Windows 10 / 11','110.227.185.177','Pune, Maharashtra, India','2026-06-23 13:36:08','2026-06-16 13:36:09','2026-06-16 13:36:08','2026-06-16 13:36:09'),(146,21,'UXRXRWdRWVVYQmVCUXNIQVdXRW9DWWNwTFRheWNQMmVZNFJQNUV6YQ==','mobile','Chrome 149','Android 10','106.221.220.177','Pune, Maharashtra, India','2026-06-23 13:40:13','2026-06-16 13:40:37','2026-06-16 13:40:13','2026-06-16 13:40:37'),(147,21,'SzVzUHpJZ0g1RVBlUHN4SndCeGZnVzJsQUR1OWxSMVVSMFpNc1FyZg==','mobile','Chrome 149','Android 10','106.221.220.177','Pune, Maharashtra, India','2026-06-23 13:40:41','2026-06-16 13:40:43','2026-06-16 13:40:42','2026-06-16 13:40:43'),(150,23,'TWtLUkltaGxCcXhNWFNKVmFhYXFZY2paVXBpcUxkSjlXc0NSbXpRdw==','mobile','Unknown Browser','iOS 26.2.0','42.104.216.11','Pune, Maharashtra, India','2026-06-23 14:05:49','2026-06-16 14:05:53','2026-06-16 14:05:50','2026-06-16 14:05:53'),(151,24,'dnVSQkF5ZmNEWEZobHpXeXA0WjVZaDluaDFLcW5yaGQwRjd5UlR2TA==','mobile','Chrome 149','Android 10','106.193.199.173','Pune, Maharashtra, India','2026-06-23 14:17:30','2026-06-16 14:18:18','2026-06-16 14:17:30','2026-06-16 14:18:18'),(152,24,'cGNwcHhuNE50RFIxRk1QaVlwaWpkb25CSjRFU1Yyak1qRlA2SEFRbQ==','mobile','Chrome 149','Android 10','106.193.199.173','Pune, Maharashtra, India','2026-06-23 14:18:49','2026-06-16 14:18:51','2026-06-16 14:18:49','2026-06-16 14:18:51'),(153,2,'OVNWTzB2YWpZVHdKRHJpQ053dXk3YmZiVEhuMmFvbXhBcXlTcGRqNQ==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-23 14:22:59','2026-06-16 14:23:46','2026-06-16 14:22:59','2026-06-16 14:23:46'),(154,22,'VE9MQVFqblZFS2FnS3JxYkQ4cVFqbTVPRm1JdzNubjBCaUk0ODB1dQ==','desktop','Edge 149','Windows 10 / 11','103.174.77.243','Pune, Maharashtra, India','2026-06-23 14:25:49','2026-06-16 14:25:49','2026-06-16 14:25:49','2026-06-16 14:25:49'),(155,25,'TEpCZUU0ZWRsTlhVWG5FWG1aU3ZYRVR1OHpIZHVQSEp3SFNlVDdFcA==','mobile','Chrome 149','Android 10','103.22.140.214','Pune, Maharashtra, India','2026-06-23 14:52:52','2026-06-16 14:52:53','2026-06-16 14:52:53','2026-06-16 14:52:53'),(156,25,'cWxFeWNhVXp4N2VsQ2MzUDliU09jTW0yTW5VdkoyNkxtVk5FNjhZRQ==','mobile','Chrome 149','Android 10','103.22.140.214','Pune, Maharashtra, India','2026-06-23 14:56:50','2026-06-17 07:12:41','2026-06-16 14:56:50','2026-06-17 07:12:41'),(158,2,'bWpab25qbHZaMXFRSFZSZ3p0NkZqd0h5UzJpT0VpTlpXVG9jYzdYZQ==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-23 17:27:11','2026-06-17 01:04:23','2026-06-16 17:27:11','2026-06-17 01:04:23'),(159,26,'aHR3M0dtckg4RFdoeDk5S2R3ZGtxUVFUMW9pcXpHRjB2dE1sUlY4Sw==','desktop','Chrome 149','Windows 10 / 11','103.250.190.58','Rajkot, Gujarat, India','2026-06-23 17:31:56','2026-06-16 17:35:23','2026-06-16 17:31:56','2026-06-16 17:35:23'),(160,27,'cEw0Y2pqcTRXT1dBeWF4YllhbmJqOHVrOTlvdEFCVTRxUGw4bVJ1ag==','desktop','Chrome 148','macOS 10.15.7','103.204.38.118','Pune, Maharashtra, India','2026-06-23 17:37:52','2026-06-16 17:38:14','2026-06-16 17:37:52','2026-06-16 17:38:14'),(164,26,'dmc1V1NGNVA5UXZBMjNNMXBhYWZMRmxGOHM5eklTMmFSUWY5WGN5Vg==','desktop','Chrome 149','Windows 10 / 11','103.250.190.58','Rajkot, Gujarat, India','2026-06-23 18:33:55','2026-06-16 18:33:55','2026-06-16 18:33:55','2026-06-16 18:33:55'),(165,30,'dWFhMWptYXNURHNvcDlVcElnYVZxdzNWQ3c0d3RjU2R6V2NUdEJzRQ==','mobile','Chrome 148','Android 16','110.227.185.177','Pune, Maharashtra, India','2026-06-23 18:35:15','2026-06-16 18:35:37','2026-06-16 18:35:15','2026-06-16 18:35:37'),(166,30,'c1lDZ2duS0d6aXhmMEUxRURZWWgwenNzeXVjeWdpQjQzQklIZzdVcw==','mobile','Chrome 149','Android 10','110.227.185.177','Pune, Maharashtra, India','2026-06-23 18:35:41','2026-06-16 18:44:36','2026-06-16 18:35:41','2026-06-16 18:44:36'),(168,32,'RkRGWDNuNGd6THZZVk9odmJYTks4VGY1a0FTeFRQQ1FqS043WkF0SQ==','mobile','Chrome 148','Android 10','122.161.243.76','Jammu, Jammu and Kashmir, India','2026-06-23 18:57:49','2026-06-16 18:58:01','2026-06-16 18:57:49','2026-06-16 18:58:01'),(169,32,'eDQ5SHJlVDNmaFhBS1ZraTFmcEc0WDR4R29TajhnUG9wQVk2d2h0Ng==','mobile','Chrome 148','Android 10','122.161.243.76','Jammu, Jammu and Kashmir, India','2026-06-23 18:58:09','2026-06-16 18:58:10','2026-06-16 18:58:09','2026-06-16 18:58:10'),(170,33,'MEJENHBlclUxeXltU0VDT0xuZTBMMXE2dzZpQ3U0WExCQ2gzZDVKWQ==','mobile','Chrome 148','Android 10','106.220.141.136','Pune, Maharashtra, India','2026-06-23 18:58:29','2026-06-16 18:58:29','2026-06-16 18:58:29','2026-06-16 18:58:29'),(171,33,'MTNNYndEY3E5RDRaVFRLYTR6eEhGSkFqUU5ycGw0QnAwQ3hoakdmSQ==','mobile','Chrome 148','Android 10','106.220.141.136','Pune, Maharashtra, India','2026-06-23 19:00:05','2026-06-17 21:06:34','2026-06-16 19:00:05','2026-06-17 21:06:34'),(172,34,'UG1ZRnFQRWVCTzhqZVRIS1V6WDd3NEVvNG5Uc2JNamxHTVE4UnJVNQ==','mobile','Chrome 149','Android 10','103.159.35.112','Delhi, National Capital Territory of Delhi, India','2026-06-23 19:10:55','2026-06-16 19:10:56','2026-06-16 19:10:56','2026-06-16 19:10:56'),(173,34,'ckhmZXp5MHZGVjVkbkxmWFZ5SllGa2JaR2JKb2phMDF0dm1XaFlhUQ==','mobile','Chrome 149','Android 10','103.159.35.112','Delhi, National Capital Territory of Delhi, India','2026-06-23 19:11:24','2026-06-16 19:15:36','2026-06-16 19:11:26','2026-06-16 19:15:36'),(174,35,'RU4yM0ozU1h0TDhRNnhSZk85ZUpwWWJnVG5NY0hmUk84TTlnV1Vjaw==','mobile','Chrome 146','Android 10','152.56.6.27','Aurangabad, Maharashtra, India','2026-06-23 19:32:20','2026-06-16 19:33:04','2026-06-16 19:32:21','2026-06-16 19:33:04'),(175,35,'NVlPeHhINDNyVUJsVmVSUmMzVzExZWlBaDRlb2puRmpmeUxWcDZIQw==','mobile','Chrome 146','Android 10','152.56.6.27','Aurangabad, Maharashtra, India','2026-06-23 19:33:08','2026-06-16 19:33:10','2026-06-16 19:33:08','2026-06-16 19:33:10'),(176,37,'alNIRU1CN3JXMkIxOXZ5Um9rcFRyeXZNZzFzUzBpdzF6WFd4OHNZSw==','mobile','Chrome 149','Android 15','152.58.36.111','Surat, Gujarat, India','2026-06-23 20:33:17','2026-06-16 20:33:46','2026-06-16 20:33:17','2026-06-16 20:33:46'),(177,38,'T1BwdWpSRTlyVW5td0tNY0QzT2dWRmljcGxIQ2I2dU5NVTJ1ZjVuSA==','mobile','Chrome 149','Android 10','157.32.218.38','Pune, Maharashtra, India','2026-06-23 20:33:38','2026-06-16 20:34:02','2026-06-16 20:33:38','2026-06-16 20:34:02'),(178,38,'eVBaTXBVaER5aG1FdTFER3FmaUl0MWlaWnJIM3VaUlJzM3Q1OE9PVg==','mobile','Chrome 149','Android 10','157.32.218.38','Pune, Maharashtra, India','2026-06-23 20:34:06','2026-06-17 12:11:41','2026-06-16 20:34:06','2026-06-17 12:11:41'),(179,37,'UmVOYWpHM0dybHlRdk13Zkx6dmo5ZVdSSzRTMXBvNDF1cTloRDJlUA==','mobile','Chrome 149','Android 15','152.58.36.111','Surat, Gujarat, India','2026-06-23 20:34:19','2026-06-16 20:34:22','2026-06-16 20:34:19','2026-06-16 20:34:22'),(180,37,'bkNVS2Z5b0Nkb1BLYk5KQThZMFU3ZDRaVkpSeHc5ZWllYVJXa21uOQ==','mobile','Chrome 149','Android 15','152.58.36.111','Surat, Gujarat, India','2026-06-23 20:34:36','2026-06-16 20:34:40','2026-06-16 20:34:37','2026-06-16 20:34:40'),(181,39,'Umo1SkxyUDlOR2YwZHkyWjhkbEozU1ZqZ0ZDTkQ0cEtMemxtVXF1Mg==','mobile','Chrome 148','Android 10','152.59.63.54','Pune, Maharashtra, India','2026-06-23 20:35:19','2026-06-16 20:35:52','2026-06-16 20:35:19','2026-06-16 20:35:52'),(182,39,'MWxZZjJUU3EzVW5SSTZEbW9ScjFVRzdPdmRhV1g5ZEg1dldJU1NqNQ==','mobile','Chrome 148','Android 10','152.59.63.54','Pune, Maharashtra, India','2026-06-23 20:36:34','2026-06-16 20:45:11','2026-06-16 20:36:34','2026-06-16 20:45:11'),(183,40,'bzBtMnk0V2IzOEpOSFJmUVRocWRJbkQ3cld5M1dwMlZhTU1ZdERVUg==','mobile','Safari 26','iOS 18.7','152.58.33.95','Pune, Maharashtra, India','2026-06-23 20:41:00','2026-06-16 20:41:00','2026-06-16 20:41:00','2026-06-16 20:41:00'),(184,40,'Q2EzOUdLa05HNEM5VDhpS1dZUEdKTlowMm1jcXI1UGRQSDc5eVRtUw==','mobile','Safari 26','iOS 18.7','152.58.33.181','Pune, Maharashtra, India','2026-06-23 20:42:11','2026-06-16 21:04:49','2026-06-16 20:42:11','2026-06-16 21:04:49'),(185,39,'M2VhZWsySWFxbnNLMmpRd3EzcUtOMnNNaVVvTmgxSnpLb2lrbzREWA==','mobile','Chrome 148','Android 10','152.59.63.54','Pune, Maharashtra, India','2026-06-23 20:45:21','2026-06-16 20:46:46','2026-06-16 20:45:21','2026-06-16 20:46:46'),(186,39,'c3F3RE1SbkxoTUZtYzBtRHpBcUdvVEkxRlhzT2MwcWgzNEM0S0ZldA==','mobile','Chrome 148','Android 10','152.59.63.54','Pune, Maharashtra, India','2026-06-23 20:47:38','2026-06-16 20:49:18','2026-06-16 20:47:38','2026-06-16 20:49:18'),(187,39,'dmgyeWhtbVBibHo3VnYyODVHNGNLZGpHTzVSdWE2M3pYVjVCWHVBZg==','mobile','Chrome 148','Android 10','152.59.63.54','Pune, Maharashtra, India','2026-06-23 20:49:37','2026-06-16 20:49:47','2026-06-16 20:49:37','2026-06-16 20:49:47'),(188,41,'bUFPUGIwYXJWc04xbTRXN0ZEcWVmVGp1ZFh2eHhBcU5FWGNrSHZOdg==','mobile','Chrome 149','Android 10','223.233.83.212','Pune, Maharashtra, India','2026-06-23 20:49:59','2026-06-16 20:49:59','2026-06-16 20:49:59','2026-06-16 20:49:59'),(189,41,'WEZSQ2g1dDBYYnJsN2lRVTRzdUlCV1c2cmNVRXc0ZThmNWpJNGk0YQ==','mobile','Chrome 149','Android 10','223.233.83.212','Pune, Maharashtra, India','2026-06-23 20:50:51','2026-06-16 20:56:05','2026-06-16 20:50:51','2026-06-16 20:56:05'),(190,42,'NHkxYmVDakRuT3p3Y2Z1S0luT1hyQXhYR20zaksyUUpZQ3JyYlFwbw==','mobile','Chrome 149','Android 10','152.59.63.212','Pune, Maharashtra, India','2026-06-23 21:15:58','2026-06-16 21:16:21','2026-06-16 21:15:59','2026-06-16 21:16:21'),(191,42,'cVh0YTdZYlcybnRkM2pYeDFRZVNrN2lKeG5mSWNKbVBkeXlaQThYbg==','mobile','Chrome 149','Android 10','152.59.63.212','Pune, Maharashtra, India','2026-06-23 21:16:24','2026-06-16 21:18:24','2026-06-16 21:16:24','2026-06-16 21:18:24'),(192,42,'Q2ZGQkFJcFhYVjVQVkZ6TkZGaThCUUhGMFQ2QVQxRXpNVnY1aU1VOA==','mobile','Chrome 149','Android 10','152.59.63.212','Pune, Maharashtra, India','2026-06-23 21:18:36','2026-06-17 00:05:19','2026-06-16 21:18:37','2026-06-17 00:05:19'),(193,22,'UEFaZ3F3N01NTEpTa2xzV3FtVkRHY2JVTmZhT2J1Yng1YTdHVWNmbA==','mobile','Chrome 149','Android 10','152.58.17.121','Pune, Maharashtra, India','2026-06-23 21:37:49','2026-06-16 21:39:54','2026-06-16 21:37:49','2026-06-16 21:39:54'),(195,43,'MjU0NHpEQ1BadVpKRFB5bU1HZUNkeXFPNGFxQ0NoWGJtckFBeU1tOA==','mobile','Chrome 149','Android 10','49.36.51.198','Pune, Maharashtra, India','2026-06-23 23:28:41','2026-06-16 23:29:24','2026-06-16 23:28:41','2026-06-16 23:29:24'),(196,11,'QldMcWxPa2ZQV2x1dzZSNHhla294WkZIcnpicm9iWE00ZVBhbWpCZA==','mobile','Chrome 149','Android 10','106.195.6.36','Pune, Maharashtra, India','2026-06-24 01:20:30','2026-06-17 01:21:32','2026-06-17 01:20:30','2026-06-17 01:21:32'),(197,44,'QXlFS1NCNGVCZEZiZ1c0d0d4OWxHNHVPS0NPOEhMOWpmOHh2c25HVg==','mobile','Chrome 149','Android 10','152.58.33.55','Pune, Maharashtra, India','2026-06-24 07:14:57','2026-06-17 07:15:34','2026-06-17 07:14:58','2026-06-17 07:15:34'),(198,44,'SG1DSzV0VHBaTlhUTGM0ZXdLMjB5ZUJDU0YzYVVmMmdpMWRrMXJXZg==','mobile','Chrome 149','Android 10','152.58.33.55','Pune, Maharashtra, India','2026-06-24 07:15:36','2026-06-17 07:15:39','2026-06-17 07:15:36','2026-06-17 07:15:39'),(199,45,'NlNTRUU0R21rVklyVUQwOGpUdWNKdVppQ1ExQVBXSjZITGZ5dUZiQQ==','desktop','Chrome 149','Windows 10 / 11','103.97.242.194','Pune, Maharashtra, India','2026-06-24 08:09:31','2026-06-17 08:09:53','2026-06-17 08:09:31','2026-06-17 08:09:53'),(200,46,'VFBUcmdzYnZNR3lzYXNPVlV1Z3YxWU93RERFY0FtRlEyY0xMN1cyNg==','mobile','Chrome 148','Android 10','152.58.32.118','Pune, Maharashtra, India','2026-06-24 09:11:45','2026-06-17 09:12:07','2026-06-17 09:11:45','2026-06-17 09:12:07'),(201,46,'c0lxaTBzSnhZdVpqRTRCZlBaMFY5bHh0YkhwUTlYSHBvaVUwd21oRw==','mobile','Chrome 148','Android 10','152.58.32.118','Pune, Maharashtra, India','2026-06-24 09:12:20','2026-06-17 09:14:55','2026-06-17 09:12:20','2026-06-17 09:14:55'),(202,46,'anJnWVNKeW9mS3haOG5GRFBva09Mc1IwcERkanFIREFqM1ljYVp0Sg==','mobile','Chrome 148','Android 10','152.58.32.118','Pune, Maharashtra, India','2026-06-24 09:19:34','2026-06-17 09:21:05','2026-06-17 09:19:34','2026-06-17 09:21:05'),(203,26,'RlNpTXlBS1ZrMm9OS2FNT3hLRzdtUjAyam9TVVNMbzBqUmo3WE5xSw==','desktop','Chrome 149','Windows 10 / 11','103.250.190.58','Rajkot, Gujarat, India','2026-06-24 09:43:02','2026-06-17 09:44:32','2026-06-17 09:43:02','2026-06-17 09:44:32'),(204,47,'WWZ2WTluQk5xMVlTUmdHYlgzeEpPekRrMzh2YWIxN1EyQVpkdmFrMw==','mobile','Chrome 149','Android 10','171.61.113.230','Jabalpur, Madhya Pradesh, India','2026-06-24 09:43:50','2026-06-17 09:44:14','2026-06-17 09:43:51','2026-06-17 09:44:14'),(205,47,'cjNvcDNsb1BscEZZcGpNNDh3VE9icHhiYnQwaEV1NllaWEJ2N3FYaA==','mobile','Chrome 149','Android 10','171.61.113.230','Jabalpur, Madhya Pradesh, India','2026-06-24 09:44:38','2026-06-17 10:08:22','2026-06-17 09:44:38','2026-06-17 10:08:22'),(206,48,'V0FzcmhYSU1meUU5Ylh6cmlFMEk3YWduMzVRWG1ZTENUaXB6UkRHUw==','desktop','Chrome 149','Windows 10 / 11','106.192.220.249','Pune, Maharashtra, India','2026-06-24 09:57:11','2026-06-17 09:57:54','2026-06-17 09:57:11','2026-06-17 09:57:54'),(207,48,'SkRoWTRpZzRkMU5ZQ042bjJBTXFwRjdxS2tXZzl3UzBMVFVSanRpbQ==','desktop','Chrome 149','Windows 10 / 11','106.192.220.249','Pune, Maharashtra, India','2026-06-24 09:57:59','2026-06-17 09:58:00','2026-06-17 09:57:59','2026-06-17 09:58:00'),(208,49,'ekNScmFPblROUXY2dkZhMEJHS1J1clRMQWV6U011ZHh3UGZPam1Gcg==','mobile','Chrome 149','Android 10','152.58.16.172','Pune, Maharashtra, India','2026-06-24 10:03:37','2026-06-17 10:04:00','2026-06-17 10:03:37','2026-06-17 10:04:00'),(209,49,'NUxUUzN0TTFTTW1PbmxLNmpRVnNQcXVYZnd6VGpwaVQzOXBZQ0RKNA==','mobile','Chrome 149','Android 10','152.58.16.172','Pune, Maharashtra, India','2026-06-24 10:04:00','2026-06-17 10:06:31','2026-06-17 10:04:00','2026-06-17 10:06:31'),(211,50,'Rk1XTmh4bHhCNnc2a2ZHNVNaZHBaT1Bkb0pnVUFtSXlpUDgyc3IzSg==','mobile','Safari 26','iOS 18.7','223.181.81.192','Raipur, Chhattisgarh, India','2026-06-24 11:06:10','2026-06-17 21:10:31','2026-06-17 11:06:10','2026-06-17 21:10:31'),(212,52,'T0RmZ3hPdjI3YXpDbTV3Y1pJVTZld0ltSjhvY3JzNXE5dVBZdEIzMg==','mobile','Chrome 149','Android 10','59.184.16.24','Nandurbar, Maharashtra, India','2026-06-24 11:23:29','2026-06-17 11:23:53','2026-06-17 11:23:29','2026-06-17 11:23:53'),(213,52,'RzlTc1FRYmhPWEFWbXd4eTJwdVVMOHVaMVdtSWE5OUhVcFB2WVZEZQ==','mobile','Chrome 149','Android 10','59.184.16.24','Nandurbar, Maharashtra, India','2026-06-24 11:23:57','2026-06-17 11:47:01','2026-06-17 11:23:58','2026-06-17 11:47:01'),(214,53,'cExIRXdmUXd6bWIwSmpyWXJ3eUtRVnFjYUtxQUIyQWxJZWNoekx3UQ==','mobile','Chrome 149','Android 10','223.185.41.135','Nagpur, Maharashtra, India','2026-06-24 11:28:14','2026-06-17 11:28:36','2026-06-17 11:28:14','2026-06-17 11:28:36'),(215,53,'a0hScE11UUNGTVlZSnd5OGYzaHNpR0JmVlFRRGFYajRRcVRxanlxTQ==','mobile','Chrome 149','Android 10','223.185.41.135','Nagpur, Maharashtra, India','2026-06-24 11:28:44','2026-06-17 11:28:45','2026-06-17 11:28:44','2026-06-17 11:28:45'),(216,53,'VkFlamFBZ05VdmZNRnZaT0dRWjlUVkJycWtFSHl1SzVxa3JHV21obA==','mobile','Chrome 149','Android 10','223.185.41.135','Nagpur, Maharashtra, India','2026-06-24 11:29:53','2026-06-17 11:30:26','2026-06-17 11:29:53','2026-06-17 11:30:26'),(217,52,'bjlCOEc2TFppSFpqWWxybG5GWUZjVGNkYlNaNU9mTnZqVmc2NjV4QQ==','mobile','Chrome 149','Android 10','59.184.16.24','Nandurbar, Maharashtra, India','2026-06-24 11:57:41','2026-06-17 16:12:24','2026-06-17 11:57:41','2026-06-17 16:12:24'),(220,2,'OThPemt0ODdQVnlWUnlRZ2p0cUhWcGg2OGNSRDZKdFkwMUluQUhhMw==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India','2026-06-24 12:19:11','2026-06-17 13:04:29','2026-06-17 12:19:11','2026-06-17 13:04:29'),(221,55,'ZDlFM281MXpsdUR2eHY5WW0wUDY3ZWZmNWVjalRKSWtWZUJTTEVXRw==','desktop','Chrome 149','Windows 10 / 11','14.96.212.78','Pune, Maharashtra, India','2026-06-24 12:35:17','2026-06-17 12:35:48','2026-06-17 12:35:17','2026-06-17 12:35:48'),(222,55,'THRabnhEd21helFHbWYzdVF4U1NoUGFlQ0F3Q3lmbmMzZllzRmxjTg==','desktop','Chrome 149','Windows 10 / 11','14.96.212.78','Pune, Maharashtra, India','2026-06-24 12:36:11','2026-06-17 12:36:14','2026-06-17 12:36:11','2026-06-17 12:36:14'),(227,57,'UmxpQjVyTjlidUVoRm5ScEVCdkpvWDlHbXhGaFAzN3hLZ3A2ZGNtTg==','mobile','Chrome 149','Android 14','106.192.114.113','Pune, Maharashtra, India','2026-06-24 19:03:01','2026-06-17 19:04:58','2026-06-17 19:03:02','2026-06-17 19:04:58'),(228,58,'Z1Mzb1V6ZE5QUHN4QmgxeEhvdTlJUzFFR2hBNWFpNlRSbTBkUUQ0SA==','mobile','Chrome 149','Android 10','152.56.14.156','Nagpur, Maharashtra, India','2026-06-24 20:04:16','2026-06-17 20:04:42','2026-06-17 20:04:16','2026-06-17 20:04:42'),(229,59,'Ukk1OWI4bHlmeDdnVHBjTTJQSlJBTDYxZDdSQXY5dm43RHlaTXFiZg==','desktop','Chrome 149','Windows 10 / 11','103.216.147.88','Latur, Maharashtra, India','2026-06-24 20:26:04','2026-06-17 20:28:37','2026-06-17 20:26:04','2026-06-17 20:28:37'),(230,60,'eGZwUDcxNG1tYjhrUW0wb1BiNEl2Y29XQ1ZjdVRHOEJUQWZPV2FHbQ==','desktop','Chrome 149','Windows 10 / 11','103.216.147.88','Latur, Maharashtra, India','2026-06-24 20:28:53','2026-06-17 20:34:08','2026-06-17 20:28:53','2026-06-17 20:34:08'),(239,62,'MHcwSnVyNllzMGFrWk1mRU8zRWk5Vm8xMGdvY0thUjNiWU9lWERVbA==','mobile','Chrome 149','Android 10','49.36.48.61','Pune, Maharashtra, India','2026-06-24 22:57:59','2026-06-17 22:57:59','2026-06-17 22:57:59','2026-06-17 22:57:59'),(240,62,'bks5d3VZSURvbE81WEZ5WFNrUUNmbDNxbDV1a2FacVJ6dThNbTFHQw==','mobile','Chrome 149','Android 10','49.36.48.61','Pune, Maharashtra, India','2026-06-24 22:58:28','2026-06-17 22:58:29','2026-06-17 22:58:28','2026-06-17 22:58:29'),(241,63,'NlFwY1lNQm05dFQ0U2d3YzFJWFh6amJLcUhIZ2F1cGUzRVZzZGFMeA==','desktop','Chrome 148','Windows 10 / 11','152.58.33.15','Pune, Maharashtra, India','2026-06-24 23:18:57','2026-06-17 23:24:16','2026-06-17 23:18:58','2026-06-17 23:24:16'),(242,64,'aDh5S3VXZGIzem5Ccmd1TWNTN045Wk93UEFFbUx2VFZ0TXF2dDVuYg==','mobile','Chrome 149','Android 10','42.108.239.21','Pune, Maharashtra, India','2026-06-24 23:26:44','2026-06-17 23:27:12','2026-06-17 23:26:45','2026-06-17 23:27:12'),(243,64,'bW50SXdxRUJQRVdoSlh2d1dZbmFBVTNtVUd2SVBFbkpwMHpBYVpQdw==','mobile','Chrome 149','Android 10','42.108.239.21','Pune, Maharashtra, India','2026-06-24 23:27:14','2026-06-17 23:47:48','2026-06-17 23:27:14','2026-06-17 23:47:48'),(245,66,'RWh2Y0lLNnZKbjV2OEJEd3JiUzFIVXBlcjNRZHROeU53Z2gwbXJXUQ==','desktop','Chrome 149','Windows 10 / 11','106.215.178.46','Pune, Maharashtra, India','2026-06-24 23:43:58','2026-06-17 23:47:58','2026-06-17 23:43:58','2026-06-17 23:47:58');
/*!40000 ALTER TABLE `user_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('student','firm','admin') DEFAULT NULL,
  `profile_completed` tinyint(1) DEFAULT '0',
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `is_deleted` tinyint DEFAULT '0',
  `api_token` varchar(80) DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_expires_at` timestamp NULL DEFAULT NULL,
  `token_expires_at` datetime DEFAULT NULL,
  `deletion_requested_at` datetime DEFAULT NULL,
  `scheduled_deletion_at` datetime DEFAULT NULL,
  `deletion_reason` varchar(500) DEFAULT NULL,
  `referral_code` varchar(50) DEFAULT NULL,
  `referred_by` bigint unsigned DEFAULT NULL,
  `referral_count` int DEFAULT '0',
  `email_verified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `referral_code` (`referral_code`),
  KEY `index` (`id`,`is_deleted`,`token_expires_at`,`api_token`,`email_verified_at`) /*!80000 INVISIBLE */,
  KEY `idx_users_password_reset_token` (`password_reset_token`),
  KEY `idx_users_scheduled_deletion` (`scheduled_deletion_at`,`is_deleted`)
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (2,'Test','riteshchandak4648@gmail.com','9156235502','$2y$12$1UMJpwu8EWD/EQD0BAO18O.8FsannvBas.Xbtv1CiV5.3wQiZXnLW','firm',1,'firm/logo/1781086388_logo.png','2026-06-10 15:32:16','2026-06-17 21:45:49',0,NULL,NULL,NULL,'2026-06-24 21:45:49',NULL,NULL,NULL,'TESTBTBFM',NULL,0,'2026-06-10 15:33:24'),(3,'Riddhi Marda','mardariddhi04@gmail.com','7449924397','$2y$12$fcWgR2SxsTVs/EO/fwP9P.AjCgbUKG3CfZd7OMzwGgxN9c6oA.Qbi','student',0,NULL,'2026-06-10 20:36:16','2026-06-10 20:41:47',0,'WXgwQzBGZXFuMmUyQkhLaTJBVjlkMHNHNXYwbEdlNnpwVFlRSXVjUg==',NULL,NULL,'2026-06-17 20:41:08',NULL,NULL,NULL,'RIDDKQZFH',NULL,0,'2026-06-10 20:36:45'),(4,'Sneha Hake','snehahake9@gmail.com','8208585385','$2y$12$Fge1Zc.FiX5It3W79euPDOvuh85OHWkGjmjMMK./X1Pql7z7SyHMC','student',0,NULL,'2026-06-11 13:15:03','2026-06-11 13:18:19',0,'cEdjbFJVZzlVelJEU2pEbGtIY3B2dUdnaGFFVEI2YUJxOWVKZUhXWg==',NULL,NULL,'2026-06-18 13:18:19',NULL,NULL,NULL,'SNEHDTKY6',NULL,0,'2026-06-11 13:17:31'),(5,'Rohan Kolety','rohankolety23@gmail.com','8850480765','$2y$12$ExjfI6OfHjmpobwqHUM4ve/82Uu0tyZfA87E5ma/ATaJyiXjC/rJ.','student',1,NULL,'2026-06-11 22:30:23','2026-06-17 14:12:12',0,NULL,NULL,NULL,'2026-06-24 14:12:12',NULL,NULL,NULL,'ROHA7TG2P',NULL,0,'2026-06-11 22:31:13'),(6,'Pratik Kokadwar','pratikkokadwar@gmail.com','7020843696','$2y$12$Baye0l2Mnf/tPlJA1e8jluFrJ4Q4gA9BZbRYgYsedk0cKy5X4Krze','student',1,NULL,'2026-06-12 14:30:56','2026-06-12 14:42:36',0,'UHFTbzdEejF5TGt1ODd4VWF6ZmJzSnk5ZHNCcnA3cE9icFVVeGJQMA==',NULL,NULL,'2026-06-19 14:32:10',NULL,NULL,NULL,'PRAT9CCLC',NULL,0,'2026-06-12 14:31:45'),(7,'Mayuri kokil','mayurikokil2510@gmail.com','7709625877','$2y$12$O4VcHNwx7J/QpEqrKbgVEuZjt8ELu7AC/ryDi6BBlHTBsrTci2XjK','student',0,NULL,'2026-06-12 14:38:41','2026-06-12 14:48:25',0,'d21HOTlNOHhjcmZkTUZObU1ZM2xHVGlIRW9RQWFrcVo2OTluWFJrdQ==',NULL,NULL,'2026-06-19 14:48:25',NULL,NULL,NULL,'MAYU68Z11',NULL,0,'2026-06-12 14:39:20'),(8,'Shraddha Gharke','shraddhagharke16@gmail.com','8788885926','$2y$12$gJy2lX5fgwVonuC3vSPcEONnSl5YxEhqZuKnP73Q.q/bVaZqkhkCC','student',0,NULL,'2026-06-12 17:01:47','2026-06-12 17:03:53',0,'MXhseldVM3JyVTd2TlRWRnpQUU9NRjJjVjl4eUZ2eUQ5QU9TTmxwVQ==',NULL,NULL,'2026-06-19 17:03:53',NULL,NULL,NULL,'SHRAVJZ8B',NULL,0,'2026-06-12 17:03:26'),(9,'Bhumika golani','bhumigolani1@gmail.com','7720059068','$2y$12$XgVIKuG1RtrBiTsmh7MOnOMxy4uc7aYWgsWShVP/jLvAVYYAlU0sC','student',0,NULL,'2026-06-12 17:15:36','2026-06-12 17:16:46',0,'U3h4TktUSWRiQXJaellUN1dTOVYyUHQ4bnpZV2ZoN1NVdHVkMDlaRg==',NULL,NULL,'2026-06-19 17:16:46',NULL,NULL,NULL,'BHUMLZB0D',NULL,0,'2026-06-12 17:16:37'),(10,'Rutuja mundada','rutujamundada96@gmail.com','9156050126','$2y$12$AJshJir8n.8jtJLFHmVW0.u08gPx2saPGBF7TGqWVp/4uh5.eBCwW','student',0,NULL,'2026-06-12 17:28:19','2026-06-12 17:29:24',0,'ZTYwamx0aTBuMGpYS2NsYnNxREVuUHRuMmlSNnFXVjNyZjhic3doaw==',NULL,NULL,'2026-06-19 17:28:55',NULL,NULL,NULL,'RUTUFHI07',NULL,0,'2026-06-12 17:29:24'),(11,'Ritesh Chandak','rituchandak7876@gmail.com','9156235503','$2y$12$LHxgIBck1OcRWj1DjE5.5OuQnkWApanM/Y90se0zqjcZLXUwNUjT.','student',1,NULL,'2026-06-12 22:31:02','2026-06-17 21:43:44',0,NULL,NULL,NULL,'2026-06-24 21:43:44',NULL,NULL,NULL,'RITEZ76NI',NULL,0,'2026-06-12 22:31:27'),(12,'SHRADDHA SHIVLING KOLI','shraddhakoli1804@gmail.com','8317268595','$2y$12$Uz9pThotQolS0SPrUJAleuTh3b4YzBT0TnOwgXz4/TMJtFUKXpMQW','student',0,NULL,'2026-06-14 10:49:13','2026-06-14 10:50:07',0,'VGI4U3VvSjZUS3Z1WTlhWlh4Uks4cThnRWZwNEZWa2hTWVRXN1lieQ==',NULL,NULL,'2026-06-21 10:50:07',NULL,NULL,NULL,'SHRAH8N3M',NULL,0,'2026-06-14 10:49:49'),(14,'Yug mutha','ymutha424@gmail.com','9405435675','$2y$12$pnVqz5qbtERYNFXnJabhK.ZtPjbWqI7wW04PzisKTBCKuqlI2Lxjm','student',0,NULL,'2026-06-15 12:24:33','2026-06-15 12:25:08',0,'MHFkNWc2VEFvUFJEd0s0R2YxbGN1UGRSSnU1S1RqU3hzYkxlZG5hZg==',NULL,NULL,'2026-06-22 12:24:49',NULL,NULL,NULL,'YUGM7GZK6',NULL,0,'2026-06-15 12:25:08'),(15,'ANUMITA SINGH','anumitasingh0511@gmail.com','6307750923','$2y$12$BKQbnsRTGjXNnt/V5cbkz.Z1zoXk76GTr9F3nKV93IH8ZRPd.V0d2','student',0,NULL,'2026-06-15 23:59:15','2026-06-16 00:00:16',0,'WktQa0dnY1RrMzRMeTdpMm5YNnZJVDc1NWQybDhyOXpENWdxbTdSNA==',NULL,NULL,'2026-06-23 00:00:16',NULL,NULL,NULL,'ANUMNIF25',NULL,0,'2026-06-16 00:00:03'),(16,'Tabrez Khan','pubggamer94442@gmail.com','9167594941','$2y$12$IDCba/hcVyvy3AVF1RbCJebhyZFv0h2QzVZsyh9UcaKsh7hwfqDEm','student',1,NULL,'2026-06-16 00:32:48','2026-06-16 00:36:12',0,'bEpHdkhSWVM2SDZNR2M5MUF6SjhnS05qSEhGdWd1UWRCNHZIbklxYg==',NULL,NULL,'2026-06-23 00:33:58',NULL,NULL,NULL,'TABRIVVF2',NULL,0,'2026-06-16 00:35:09'),(17,'Rashi Agrawal','rashiagrawal0122@gmail.com','9302541018','$2y$12$0qon0EBDt.ikR80Z6sWZS.9sB5tRbwxbhA0SNetWwF.T84maBrlUK','student',0,NULL,'2026-06-16 01:25:17','2026-06-16 01:25:31',0,'dUxLTlQ0N2dNcm5mS2VucVltbW5wRHJVRDE3Sk1mSTVmcDU4U1pBcg==',NULL,NULL,'2026-06-23 01:25:31',NULL,NULL,NULL,'RASHD86A4',NULL,0,NULL),(18,'Raj Mantri','rajmantri6@gmail.com','7798963362','$2y$12$FuQSZ1.H1d0TuUbq3NU5hO/5GwpKUXsbwcHAbnEyO/sxLhkWxx/zS','student',0,NULL,'2026-06-16 12:35:00','2026-06-16 12:36:05',0,'VmlmMmhHOFdVU1g2Slh1dDUybDM3enFHTmFkTG8ydXFxNk9RaTF5Qw==',NULL,NULL,'2026-06-23 12:36:05',NULL,NULL,NULL,'RAJMP18IU',NULL,0,'2026-06-16 12:35:41'),(19,'Aditya Ramesh Gaikwad','adityagramesh2004@gmail.com','9021178829','$2y$12$Chun0UWqR8T6i6gRjmAZUew9q1/njM6rv.Vk/Qqp7C1ZOAhWjTM5y','student',0,NULL,'2026-06-16 12:36:57','2026-06-16 12:38:48',0,'cTZSSEFvSHFuaWlFd0pzdHhROThKZXFQRG5ZdEVlQUF0d1dWT2hqTA==',NULL,NULL,'2026-06-23 12:37:09',NULL,NULL,NULL,'ADITLYTHZ',NULL,0,'2026-06-16 12:38:48'),(20,'krishna jaju','krishna17jaju@gmail.com','8483950730','$2y$12$q4g14pvHkQRE3joMYn0ORuwpE0r2xUSrF2xIqSc/GmciyCYA9Ezgq','student',1,NULL,'2026-06-16 13:21:28','2026-06-16 18:07:15',0,NULL,NULL,NULL,'2026-06-23 18:07:15',NULL,NULL,NULL,'KRISKEENW',NULL,0,'2026-06-16 13:22:03'),(21,'Akansha Malpani','ak.anshamalpani13@gmail.com','9423816450','$2y$12$rvtv/NxpR3AT/.Ke1m9Q8uK7eP8Pspubh4gIlpx23L5.qF6AmsNGC','student',0,NULL,'2026-06-16 13:39:47','2026-06-16 13:40:41',0,'SzVzUHpJZ0g1RVBlUHN4SndCeGZnVzJsQUR1OWxSMVVSMFpNc1FyZg==',NULL,NULL,'2026-06-23 13:40:41',NULL,NULL,NULL,'AKAN0ZB5Y',NULL,0,'2026-06-16 13:40:31'),(22,'Sancheti Lakade & Associates','capranavsancheti@gmail.com','7030133165','$2y$12$zY6piKl1C9C/DixUz4W1hufsXfLY7G7co3mYVfeYazt8mT2gJOBAi','firm',1,'firm/logo/1781599573_logo.jpeg','2026-06-16 14:03:23','2026-06-16 21:37:49',0,'UEFaZ3F3N01NTEpTa2xzV3FtVkRHY2JVTmZhT2J1Yng1YTdHVWNmbA==',NULL,NULL,'2026-06-23 21:37:49',NULL,NULL,NULL,'SANCW9QEA',NULL,0,'2026-06-16 14:03:50'),(23,'Prajwal Deepak alhat','prajwalalhat7728@gmail.com','9518572476','$2y$12$XUIrAVtI6Tm/sN9v3dzgXOrmxwyPKOsQCkJfOVOML.pREtHYwh6.y','student',0,NULL,'2026-06-16 14:04:01','2026-06-16 14:05:49',0,'TWtLUkltaGxCcXhNWFNKVmFhYXFZY2paVXBpcUxkSjlXc0NSbXpRdw==',NULL,NULL,'2026-06-23 14:05:49',NULL,NULL,NULL,'PRAJL8LSX',NULL,0,'2026-06-16 14:04:21'),(24,'Kiran Sangle','dsangle659@gmail.com','7262826820','$2y$12$Nd2KyfQgJawYPEE2YkWZ6e0/2Mtoxjhdda0jgymhU.ryVhk0ebxC6','student',0,NULL,'2026-06-16 14:16:54','2026-06-16 14:18:49',0,'cGNwcHhuNE50RFIxRk1QaVlwaWpkb25CSjRFU1Yyak1qRlA2SEFRbQ==',NULL,NULL,'2026-06-23 14:18:49',NULL,NULL,NULL,'KIRAMAUEP',NULL,0,'2026-06-16 14:18:16'),(25,'Isha Dhoka','ishadhoka2000@gmail.com','8983825825','$2y$12$7KOOXM1vEfFrmGvHNSKeaO5jakGB1Ao58jEls/ufb5MnjF37q2GQq','student',0,NULL,'2026-06-16 14:51:05','2026-06-16 14:56:50',0,'cWxFeWNhVXp4N2VsQ2MzUDliU09jTW0yTW5VdkoyNkxtVk5FNjhZRQ==',NULL,NULL,'2026-06-23 14:56:50',NULL,NULL,NULL,'ISHA82O78',NULL,0,'2026-06-16 14:53:36'),(26,'C A & Co','kamal@caco.in','9824800548','$2y$12$qkJUwxzmSyNoE5NNQZS5ae6rYjTdERGdMJYYN9edUzlHdoy3z0WY.','firm',1,NULL,'2026-06-16 17:29:36','2026-06-17 09:43:02',0,'RlNpTXlBS1ZrMm9OS2FNT3hLRzdtUjAyam9TVVNMbzBqUmo3WE5xSw==','0de73580c96d9fba590ca7b09327e4bdfda272acde3566ced8485cd6735e67e5','2026-06-16 18:31:02','2026-06-24 09:43:02',NULL,NULL,NULL,'CACOVRYFB',NULL,0,'2026-06-16 17:30:21'),(27,'B N S T & Co LLP','bnst.ca@gmail.com','7888080300','$2y$12$CyOJDOg.HNBSCi.ZZkFqoupWXjAXV5gbKQ3Om9pFZJW7ReKun1HEG','firm',1,NULL,'2026-06-16 17:37:42','2026-06-16 17:43:35',0,NULL,NULL,NULL,'2026-06-23 17:38:19',NULL,NULL,NULL,'BNSTMXRAZ',NULL,0,'2026-06-16 17:38:04'),(28,'Rekhani & Saraogi','rekhani.saraogi@gmail.com','9327961291','$2y$12$WaNFStgFELv/.dyNJNCzkuA5t/PXpWM2RQEcGIACBtcVUWfaPJYJK','firm',0,NULL,'2026-06-16 18:10:31','2026-06-16 18:11:59',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'REKHI347L',NULL,0,'2026-06-16 18:11:59'),(29,'Sumit Sawant','sumitsawant2311@gmail.com','8390388932','$2y$12$gxF5S4wMgTme7Y7Tebxu8ONv0nE8x8oxL6DulLnLfo.r4GCHD2V3m','student',0,NULL,'2026-06-16 18:24:49','2026-06-16 18:24:49',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'SUMITCM04',NULL,0,NULL),(30,'Siddhesh Shinde','siddhesh99shinde@gmail.com','7030601585','$2y$12$h/sNWCAqm1ukCHhNyNicFOLI4xRtoyTNtFZ79.JJVUfZB.4b.yfOK','student',1,NULL,'2026-06-16 18:34:59','2026-06-16 18:38:59',0,'c1lDZ2duS0d6aXhmMEUxRURZWWgwenNzeXVjeWdpQjQzQklIZzdVcw==',NULL,NULL,'2026-06-23 18:35:41',NULL,NULL,NULL,'SIDDNMRM7',NULL,0,'2026-06-16 18:35:33'),(31,'darshan patni','darshanpatni09@gmail.com','8956066565','$2y$12$.P5X3hoB.LnIUkiWlV96yuP4CGKmAp1W0VtTplHJ2CU5wa5RSE0rC','student',1,NULL,'2026-06-16 18:52:31','2026-06-16 18:55:57',0,NULL,NULL,NULL,'2026-06-23 18:52:52',NULL,NULL,NULL,'DARSEY8EG',NULL,0,'2026-06-16 18:53:22'),(32,'Kusha Sharma','minakshikusha@gmail.com','7889774620','$2y$12$wAvquM9c.82DpvohGMriPeFGQ8DKws.gjWwczZe48Im3GbpRtFPhC','student',0,NULL,'2026-06-16 18:57:44','2026-06-16 18:58:09',0,'eDQ5SHJlVDNmaFhBS1ZraTFmcEc0WDR4R29TajhnUG9wQVk2d2h0Ng==',NULL,NULL,'2026-06-23 18:58:09',NULL,NULL,NULL,'KUSHBQ9DF',NULL,0,'2026-06-16 18:57:59'),(33,'Suraj Narsing Kakde','kakdesuraj383@gmail.com','9075581387','$2y$12$pqNZrlnWiJ2jM1Td6HVqQ.ff4f7IjN2abLizp0Og.HVdfAlBf2wp2','student',0,NULL,'2026-06-16 18:57:55','2026-06-16 19:00:05',0,'MTNNYndEY3E5RDRaVFRLYTR6eEhGSkFqUU5ycGw0QnAwQ3hoakdmSQ==',NULL,NULL,'2026-06-23 19:00:05',NULL,NULL,NULL,'SURAT6HV0',NULL,0,'2026-06-16 18:59:54'),(34,'Sanchita Nagwani','sanchitanagwani@gmail.com','9699110536','$2y$12$252.xQMN8g31H.EQk.9XiujJocVdtkj2Go.Nxa6LYHk0Qg7SbtDNq','student',1,'profile/1781617536_profile.jpg','2026-06-16 19:10:08','2026-06-16 19:15:36',0,'ckhmZXp5MHZGVjVkbkxmWFZ5SllGa2JaR2JKb2phMDF0dm1XaFlhUQ==',NULL,NULL,'2026-06-23 19:11:24',NULL,NULL,NULL,'SANCACCFY',NULL,0,'2026-06-16 19:11:15'),(35,'Gajanan Pawar','gajupawar775@gmail.com','9325315085','$2y$12$.8.yO94dn.lF0XiwBcLvquHtEKKsSmlINnD6JiNmmTJBo.jTrRez2','student',0,NULL,'2026-06-16 19:32:08','2026-06-16 19:33:08',0,'NVlPeHhINDNyVUJsVmVSUmMzVzExZWlBaDRlb2puRmpmeUxWcDZIQw==',NULL,NULL,'2026-06-23 19:33:08',NULL,NULL,NULL,'GAJA1ZYS5',NULL,0,'2026-06-16 19:33:00'),(37,'PRANAV MANTRI','mantripb77@gmail.com','9404664882','$2y$12$NNhk.1GaSwiWm4l.VG.Qg.AFfpmJQpYfiytlAQ3sEzcIpZbMigr7W','student',0,NULL,'2026-06-16 20:32:43','2026-06-16 20:34:36',0,'bkNVS2Z5b0Nkb1BLYk5KQThZMFU3ZDRaVkpSeHc5ZWllYVJXa21uOQ==',NULL,NULL,'2026-06-23 20:34:36',NULL,NULL,NULL,'PRANJVCPU',NULL,0,'2026-06-16 20:33:41'),(38,'Rohan Pawale','pawalerohan1999@gmail.com','9823279176','$2y$12$OmgIODfAOg2ArlbtE5lXj.3PdV8g7xuPZTVmNMkt6n8AY6ZZxCrOe','student',1,NULL,'2026-06-16 20:33:26','2026-06-16 20:38:43',0,'eVBaTXBVaER5aG1FdTFER3FmaUl0MWlaWnJIM3VaUlJzM3Q1OE9PVg==',NULL,NULL,'2026-06-23 20:34:06',NULL,NULL,NULL,'ROHAOY002',NULL,0,'2026-06-16 20:33:52'),(39,'Akash Pund','akashpund2003@gmail.com','8767436589','$2y$12$/lfEob77kH9gO/xKg87VnuH7c4Hl9fokNT7clk2sGr0YQdB2gI6zO','student',0,NULL,'2026-06-16 20:35:14','2026-06-16 20:49:47',0,'dmgyeWhtbVBibHo3VnYyODVHNGNLZGpHTzVSdWE2M3pYVjVCWHVBZg==',NULL,NULL,'2026-06-23 20:49:37',NULL,NULL,NULL,'AKAS475AJ',NULL,0,'2026-06-16 20:35:42'),(40,'Pratish Bansode','pratishbansode07@gmail.com','9307170214','$2y$12$N7eQpo61vwk1zy08EvCNYOe8Pjr4h18MP2MQoC2m2Y.4XwHcmyeMe','student',0,NULL,'2026-06-16 20:40:45','2026-06-16 20:42:11',0,'Q2EzOUdLa05HNEM5VDhpS1dZUEdKTlowMm1jcXI1UGRQSDc5eVRtUw==',NULL,NULL,'2026-06-23 20:42:11',NULL,NULL,NULL,'PRAT5YIN2',NULL,0,'2026-06-16 20:41:54'),(41,'Suraj Sandeep Ingle','surajingleprofessional007@gmail.com','9370088634','$2y$12$XK0Q2/eodpl6fXLk3PpFd.VXA.ciH5QN5xXVcPVZMXRw7bs3ta1k6','student',0,NULL,'2026-06-16 20:49:36','2026-06-16 20:50:51',0,'WEZSQ2g1dDBYYnJsN2lRVTRzdUlCV1c2cmNVRXc0ZThmNWpJNGk0YQ==',NULL,NULL,'2026-06-23 20:50:51',NULL,NULL,NULL,'SURAJKYXW',NULL,0,'2026-06-16 20:50:43'),(42,'Atharv Patil','atharvpatil4328@gmail.com','9767461519','$2y$12$EplBNAx8q4ETHoN3AkpBueMRWxAsVaZJyLx4RQCte9RqaRJBzRMji','student',0,NULL,'2026-06-16 21:15:45','2026-06-16 21:18:36',0,'Q2ZGQkFJcFhYVjVQVkZ6TkZGaThCUUhGMFQ2QVQxRXpNVnY1aU1VOA==',NULL,NULL,'2026-06-23 21:18:36',NULL,NULL,NULL,'ATHAD4PSY',NULL,0,'2026-06-16 21:16:17'),(43,'Prajakta Shitole','prajaktashitole01@gmail.com','9890682415','$2y$12$2wR2o1PtxJ7Y6aHhPWeJ0.Emz.Mz0PkR3dtgANH5FQCsJACDC3E3G','student',0,NULL,'2026-06-16 23:26:16','2026-06-16 23:28:50',0,'MjU0NHpEQ1BadVpKRFB5bU1HZUNkeXFPNGFxQ0NoWGJtckFBeU1tOA==',NULL,NULL,'2026-06-23 23:28:41',NULL,NULL,NULL,'PRAJL07B2',NULL,0,'2026-06-16 23:28:50'),(44,'Kartik Rajesh jain','kartikajain29@gmail.com','9322504094','$2y$12$E6P5Mn1JTQbnDsBcREhvHObVa4fsCSvcUI3cqL44kuqqwoSehfy3O','student',0,NULL,'2026-06-17 07:14:41','2026-06-17 07:15:36',0,'SG1DSzV0VHBaTlhUTGM0ZXdLMjB5ZUJDU0YzYVVmMmdpMWRrMXJXZg==',NULL,NULL,'2026-06-24 07:15:36',NULL,NULL,NULL,'KARTBXY5U',NULL,0,'2026-06-17 07:15:29'),(45,'Sunil Shenoy and Associates','casunilshenoy@gmail.com','9890946372','$2y$12$z.8uXDFsrfqSPvyYbkPPJe5yiGGTDM8kZcQEGavEx53P6LrArpU6O','firm',0,NULL,'2026-06-17 08:09:04','2026-06-17 08:09:47',0,'NlNTRUU0R21rVklyVUQwOGpUdWNKdVppQ1ExQVBXSjZITGZ5dUZiQQ==',NULL,NULL,'2026-06-24 08:09:31',NULL,NULL,NULL,'SUNI00MJ9',NULL,0,'2026-06-17 08:09:47'),(46,'Anushka Shinde','anushka.shinde.1217@gmail.com','7028285800','$2y$12$7WH5NP5pY6oweoGw9xGNNOOjw8b.tNuA7pZRHbuymmmyvv3QnhWfm','student',0,NULL,'2026-06-17 09:11:25','2026-06-17 09:19:34',0,'anJnWVNKeW9mS3haOG5GRFBva09Mc1IwcERkanFIREFqM1ljYVp0Sg==',NULL,NULL,'2026-06-24 09:19:34',NULL,NULL,NULL,'ANUSJCHVB',NULL,0,'2026-06-17 09:12:01'),(47,'Garvita Bansal','garvitabansal7@gmail.com','7049734305','$2y$12$JrBT66q5HM2eRYI52DXDYudvl2.kEmsmqF2Do0bNxzlKZq4LOD1si','student',0,NULL,'2026-06-17 09:43:14','2026-06-17 09:44:38',0,'cjNvcDNsb1BscEZZcGpNNDh3VE9icHhiYnQwaEV1NllaWEJ2N3FYaA==',NULL,NULL,'2026-06-24 09:44:38',NULL,NULL,NULL,'GARVFOPRA',NULL,0,'2026-06-17 09:44:11'),(48,'Varun Mulay','varunmulay9@gmail.com','7709847063','$2y$12$oOlRDkZ4PjVqzzdlBFL8LOBVrQtPTQvQZV0BWZFBVmDJlpZH6iXzu','student',0,NULL,'2026-06-17 09:56:50','2026-06-17 09:57:59',0,'SkRoWTRpZzRkMU5ZQ042bjJBTXFwRjdxS2tXZzl3UzBMVFVSanRpbQ==',NULL,NULL,'2026-06-24 09:57:59',NULL,NULL,NULL,'VARURUET4',NULL,0,'2026-06-17 09:57:51'),(49,'Mohit Tinwar','mohittinwar1234@gmail.com','9011120940','$2y$12$rB8QgCC3zkeWNNkN7cs/cevQS04pFol6s90qXRRtmH8M4RTjiLs5u','student',1,NULL,'2026-06-17 10:03:16','2026-06-17 10:06:16',0,'NUxUUzN0TTFTTW1PbmxLNmpRVnNQcXVYZnd6VGpwaVQzOXBZQ0RKNA==',NULL,NULL,'2026-06-24 10:04:00',NULL,NULL,NULL,'MOHIZ1DJB',NULL,0,'2026-06-17 10:03:53'),(50,'Akansha Malhotra','akanshaagrawal0918@gmail.com','9109994200','$2y$12$z8D24LRONg75RG5d764SOeVTaSUOv1YXKgtCwTJsfuX38KUV7z9yG','student',0,NULL,'2026-06-17 11:05:42','2026-06-17 11:07:03',0,'Rk1XTmh4bHhCNnc2a2ZHNVNaZHBaT1Bkb0pnVUFtSXlpUDgyc3IzSg==',NULL,NULL,'2026-06-24 11:06:10',NULL,NULL,NULL,'AKAN8PTOA',NULL,0,'2026-06-17 11:07:03'),(52,'Gautam kasar','kasargautam19@gmail.com','7666776067','$2y$12$Ho0udqpDqiVJ0ilJPtM/ruZa3bsPvSv3dD4A29gK2r9DIYXzdGM3a','student',1,NULL,'2026-06-17 11:23:07','2026-06-17 11:57:41',0,'bjlCOEc2TFppSFpqWWxybG5GWUZjVGNkYlNaNU9mTnZqVmc2NjV4QQ==',NULL,NULL,'2026-06-24 11:57:41',NULL,NULL,NULL,'GAUTKF6IL',NULL,0,'2026-06-17 11:23:49'),(53,'Piyush Agrawal','piyushagrawal4833@gmail.com','8766568212','$2y$12$dTGpNxlsvvfIYd/l.fPrP.XjQpxt4zIEhzVzdq2cgn9Za4xUqU5g.','student',0,NULL,'2026-06-17 11:28:00','2026-06-17 11:29:53',0,'VkFlamFBZ05VdmZNRnZaT0dRWjlUVkJycWtFSHl1SzVxa3JHV21obA==',NULL,NULL,'2026-06-24 11:29:53',NULL,NULL,NULL,'PIYUCMWHT',NULL,0,'2026-06-17 11:28:27'),(54,'Vipin Gujarathi & Co','vipingujarathico@gmail.com','9665041457','$2y$12$RJKj/PDDIznInxmZYoPbJuFo83RRn5JEe7RjPFYGIpd8aC4cVlto.','firm',1,'firm/logo/1781683273_logo.jpeg','2026-06-17 12:16:19','2026-06-17 13:31:13',0,NULL,NULL,NULL,'2026-06-24 13:01:00',NULL,NULL,NULL,'VIPIY6E97',NULL,0,'2026-06-17 13:00:34'),(55,'Prachay Grouo','hr@prachay.com','9028666187','$2y$12$VitB1.pJR1c2a27XygxfH.ARTzGS6aXWM5jscFl0Tf0/I/rc3VHfm','firm',0,NULL,'2026-06-17 12:34:58','2026-06-17 12:36:11',0,'THRabnhEd21helFHbWYzdVF4U1NoUGFlQ0F3Q3lmbmMzZllzRmxjTg==',NULL,NULL,'2026-06-24 12:36:11',NULL,NULL,NULL,'PRACRG3Z7',NULL,0,'2026-06-17 12:35:43'),(56,'A R Totala & Co.','amolrtotla@gmail.com','7447711021','$2y$12$5kNkTCHvy921AZUwE0dMA.HQPzPmO7a1TiskMLxr2Z5.jlDLFIj.y','firm',1,NULL,'2026-06-17 13:28:17','2026-06-17 13:36:12',0,NULL,NULL,NULL,'2026-06-24 13:28:58',NULL,NULL,NULL,'ARTOIHEWG',NULL,0,'2026-06-17 13:28:32'),(57,'Tisha','tishajain0906@gmail.com','7558620917','$2y$12$y8eZJd2wyE0akXmH73NK9uIVYruRNSUq0bNmgdTw/yOof.VgXIuu2','student',1,NULL,'2026-06-17 19:02:39','2026-06-17 19:03:47',0,'UmxpQjVyTjlidUVoRm5ScEVCdkpvWDlHbXhGaFAzN3hLZ3A2ZGNtTg==',NULL,NULL,'2026-06-24 19:03:01',NULL,NULL,NULL,'TISHNDCEH',NULL,0,'2026-06-17 19:03:13'),(58,'Megha anil kukreja','kukrejamekii2703@gmail.com','7058373947','$2y$12$tMrBgCRMOKULMvcVHQXeGegmH6fnw2.E06lw//j/LVdw8rM99hlmu','student',0,NULL,'2026-06-17 20:03:58','2026-06-17 20:04:38',0,'Z1Mzb1V6ZE5QUHN4QmgxeEhvdTlJUzFFR2hBNWFpNlRSbTBkUUQ0SA==',NULL,NULL,'2026-06-24 20:04:16',NULL,NULL,NULL,'MEGHVPT0K',NULL,0,'2026-06-17 20:04:38'),(59,'Radhesham Biyani','carsbiyani@gmail.com','8888654567','$2y$12$k6dMPSYmSqmGOUB3f1cktO01D/CcLAQ2gqe.jwGaJLuq9g6VYRN1m','student',0,NULL,'2026-06-17 20:24:25','2026-06-17 20:26:04',0,'Ukk1OWI4bHlmeDdnVHBjTTJQSlJBTDYxZDdSQXY5dm43RHlaTXFiZg==',NULL,NULL,'2026-06-24 20:26:04',NULL,NULL,NULL,'RADHDD9XV',NULL,0,'2026-06-17 20:24:57'),(60,'R S Biyani & Co','rsbitsolution@gmail.com','7020009889','$2y$12$Hq5N8ODerG9cTbP6pWqBn.wotmOrL00qGEp4hZyupJGzzTydEa5Tm','firm',1,NULL,'2026-06-17 20:28:14','2026-06-17 20:34:08',0,'eGZwUDcxNG1tYjhrUW0wb1BiNEl2Y29XQ1ZjdVRHOEJUQWZPV2FHbQ==',NULL,NULL,'2026-06-24 20:28:53',NULL,NULL,NULL,'RSBIEI4P7',NULL,0,'2026-06-17 20:28:27'),(61,'Nitish Badak','nitishbadak14@gmail.com','9511864107','$2y$12$Cx1RgBAIoSfxQCwRQMSUtuZaeaICWb914MfblxLy4i5C0fM.n5Gf6','student',0,NULL,'2026-06-17 22:36:31','2026-06-17 22:36:31',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'NITIJTXDZ',NULL,0,NULL),(62,'N J LOHE & CO.','cacspriya@tnlac.in','9028485515','$2y$12$x4KOIl8IMtVoWS3U94CYVueKSq09JjEzj2YuTSDUZMsEWY3TRKr5i','firm',0,NULL,'2026-06-17 22:57:44','2026-06-17 22:58:28',0,'bks5d3VZSURvbE81WEZ5WFNrUUNmbDNxbDV1a2FacVJ6dThNbTFHQw==',NULL,NULL,'2026-06-24 22:58:28',NULL,NULL,NULL,'NJLOBNR93',NULL,0,'2026-06-17 22:58:17'),(63,'Atharv','kakaniatharv06@gmail.com','7028312405','$2y$12$Z95kBMHRRggpeoUsAMNWdO3BHet5AjhYEbHuxYLFXLKTpFz6hEar6','student',1,NULL,'2026-06-17 23:18:46','2026-06-17 23:22:55',0,'NlFwY1lNQm05dFQ0U2d3YzFJWFh6amJLcUhIZ2F1cGUzRVZzZGFMeA==',NULL,NULL,'2026-06-24 23:18:57',NULL,NULL,NULL,'ATHAOIWSA',NULL,0,'2026-06-17 23:19:50'),(64,'Anjali Bhutada','anjumantri@gmail.com','9922222335','$2y$12$NjFpkMBokf/MEzaYh3Qmx.gZPGsJkV0XR3TR4mo5eRMOdJWux0cna','student',1,NULL,'2026-06-17 23:26:20','2026-06-17 23:35:34',0,'bW50SXdxRUJQRVdoSlh2d1dZbmFBVTNtVUd2SVBFbkpwMHpBYVpQdw==',NULL,NULL,'2026-06-24 23:27:14',NULL,NULL,NULL,'ANJAJU66Z',NULL,0,'2026-06-17 23:27:02'),(65,'Tushar Bhise','tusharbhise908@gmail.com','9284581330','$2y$12$BT.1O9A9Uugbg0YSTewBi.t2b5LfxQU7TVTa1HpxdzP9PLhbQTjDO','student',1,NULL,'2026-06-17 23:38:59','2026-06-18 00:03:29',0,NULL,NULL,NULL,'2026-06-25 00:03:29',NULL,NULL,NULL,'TUSH5XOVB',NULL,0,'2026-06-17 23:39:47'),(66,'test','tusharb.live@gmail.com','7083555377','$2y$12$0RnuSD97IAp8QNLj7CY97e5f8CUUE3re7An7D6syvX7amHmc7u/oq','firm',1,NULL,'2026-06-17 23:43:39','2026-06-18 00:03:12',0,NULL,NULL,NULL,'2026-06-25 00:03:12',NULL,NULL,NULL,'TESTXWBQO',NULL,0,'2026-06-17 23:44:15');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wallet_recharge_packs`
--

DROP TABLE IF EXISTS `wallet_recharge_packs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallet_recharge_packs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `amount` decimal(10,2) NOT NULL,
  `label` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active_sort` (`is_active`,`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallet_recharge_packs`
--

LOCK TABLES `wallet_recharge_packs` WRITE;
/*!40000 ALTER TABLE `wallet_recharge_packs` DISABLE KEYS */;
INSERT INTO `wallet_recharge_packs` VALUES (1,150.00,'₹150',1,1,'2026-06-04 17:51:51','2026-06-04 17:51:51'),(2,300.00,'₹300',1,2,'2026-06-04 17:51:51','2026-06-04 17:51:51'),(3,500.00,'₹500',1,3,'2026-06-04 17:51:51','2026-06-04 17:51:51'),(4,1000.00,'₹1000',1,4,'2026-06-04 17:51:51','2026-06-04 17:51:51');
/*!40000 ALTER TABLE `wallet_recharge_packs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wallet_recharges`
--

DROP TABLE IF EXISTS `wallet_recharges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallet_recharges` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('razorpay','phonepe','manual') NOT NULL DEFAULT 'razorpay',
  `status` enum('pending','approved','rejected','expired') NOT NULL DEFAULT 'pending',
  `gateway_order_id` varchar(255) DEFAULT NULL,
  `gateway_payment_id` varchar(255) DEFAULT NULL,
  `gateway_signature` text,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `razorpay_response` json DEFAULT NULL,
  `gateway_response` json DEFAULT NULL,
  `payment_method_used` varchar(100) DEFAULT NULL COMMENT 'upi, card, netbanking etc.',
  `utr_number` varchar(100) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `screenshot_url` text,
  `payment_date` date DEFAULT NULL,
  `remarks` text,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejected_at` timestamp NULL DEFAULT NULL,
  `wallet_transaction_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_method` (`payment_method`),
  KEY `idx_gateway_order` (`gateway_order_id`),
  KEY `idx_gateway_payment` (`gateway_payment_id`),
  KEY `idx_user_status_created` (`user_id`,`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallet_recharges`
--

LOCK TABLES `wallet_recharges` WRITE;
/*!40000 ALTER TABLE `wallet_recharges` DISABLE KEYS */;
/*!40000 ALTER TABLE `wallet_recharges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wallet_transactions`
--

DROP TABLE IF EXISTS `wallet_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallet_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('credit','hold','release','consume','refund') NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL COMMENT 'recharge | application',
  `reference_id` bigint unsigned DEFAULT NULL COMMENT 'wallet_recharges.id or application_holds.id',
  `job_id` bigint unsigned DEFAULT NULL,
  `application_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `balance_before` decimal(10,2) DEFAULT NULL COMMENT 'available_balance before this operation',
  `balance_after` decimal(10,2) DEFAULT NULL COMMENT 'available_balance after this operation',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_ref` (`reference_type`,`reference_id`),
  KEY `idx_application` (`application_id`),
  KEY `idx_job` (`job_id`),
  KEY `idx_user_type_created` (`user_id`,`type`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallet_transactions`
--

LOCK TABLES `wallet_transactions` WRITE;
/*!40000 ALTER TABLE `wallet_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `wallet_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'startyourstory'
--

--
-- Dumping routines for database 'startyourstory'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-18  0:05:42

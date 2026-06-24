-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: localhost    Database: startyourstory
-- ------------------------------------------------------
-- Server version	8.0.46-0ubuntu0.24.04.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_activity_logs`
--

LOCK TABLES `admin_activity_logs` WRITE;
/*!40000 ALTER TABLE `admin_activity_logs` DISABLE KEYS */;
INSERT INTO `admin_activity_logs` VALUES (1,2,'TusharB','student_deleted','student','13','Deleted student account for Tushar Bhise (tusharbhise908@gmail.com). Reason: Test Account','106.215.178.46','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-17 23:32:35'),(2,2,'TusharB','firm_approved','firm','66','Approved firm registration for test.','106.215.178.46','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-17 23:45:45'),(3,2,'TusharB','blog_created','blog','11','Created blog \'Stipend vs Exposure: What Matters More in Articleship?\'.','106.215.178.46','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-17 23:58:21'),(4,2,'TusharB','blog_updated','blog','11','Updated blog #11.','106.215.178.46','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-17 23:58:51'),(5,3,'Ritesh Chandak','firm_approved','firm','60','Approved firm registration for R S Biyani & Co.','103.160.175.18','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 14:54:01'),(6,2,'TusharB','blog_updated','blog','1','Updated blog #1.','106.215.180.208','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-18 22:45:02'),(7,2,'TusharB','blog_published','blog','1','Published blog #1.','106.215.180.208','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-18 22:45:41'),(8,2,'TusharB','blog_updated','blog','1','Updated blog #1.','106.215.180.208','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-18 22:49:14'),(9,3,'Ritesh Chandak','firm_approved','firm','74','Approved firm registration for K A R M & CO. Chartered Accountants.','103.160.175.18','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-19 11:17:14'),(10,2,'TusharB','impersonation_started','student','76','Started impersonating student #76 (patil431717@gmail.com).','122.183.33.252','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-19 22:23:06'),(11,2,'TusharB','impersonation_started','student','76','Started impersonating student #76 (patil431717@gmail.com).','122.183.33.252','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-19 22:23:33'),(12,2,'TusharB','impersonation_started','student','72','Started impersonating student #72 (hemanshukopulwar21@gmail.com).','122.183.33.252','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-19 22:23:52'),(13,2,'TusharB','impersonation_ended','student','72','Ended impersonation of user #72.','122.183.33.252','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-19 22:26:51'),(14,2,'TusharB','impersonation_started','student','76','Started impersonating student #76 (patil431717@gmail.com).','122.183.33.252','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-19 22:26:57'),(15,2,'TusharB','impersonation_started','student','76','Started impersonating student #76 (patil431717@gmail.com).','122.183.33.252','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-19 22:28:49'),(16,2,'TusharB','blog_created','blog','12','Created blog \'Articleship Roadmap: What to Learn in Your 2 Years of Training\'.','122.183.35.39','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-20 21:46:38'),(17,3,'Ritesh Chandak','firm_approved','firm','79','Approved firm registration for SMW AND Co..','152.58.31.39','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 23:23:06'),(18,2,'TusharB','payment_settings_updated','system_setting','payment_ifsc','Updated system setting \'payment_ifsc\'.','106.205.4.40','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-21 21:31:42'),(19,2,'TusharB','firm_approved','firm','87','Approved firm registration for Test Phonepay.','106.205.4.40','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-21 21:37:24'),(20,3,'Ritesh Chandak','firm_approved','firm','88','Approved firm registration for V S A P & Company.','103.160.175.18','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 19:48:44'),(21,3,'Ritesh Chandak','firm_approved','firm','91','Approved firm registration for H Mistry & Associates.','103.160.175.18','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 19:44:53'),(22,3,'Ritesh Chandak','platform_settings_updated','platform_setting','show_students_to_firms','Updated platform setting \'show_students_to_firms\' to \'true\'.','103.160.175.18','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 11:04:08'),(23,3,'Ritesh Chandak','platform_settings_updated','platform_setting','show_students_to_firms','Updated platform setting \'show_students_to_firms\' to \'false\'.','103.160.175.18','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 11:05:02'),(24,3,'Ritesh Chandak','platform_settings_updated','platform_setting','show_students_to_firms','Updated platform setting \'show_students_to_firms\' to \'true\'.','103.160.175.18','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 11:05:13'),(25,3,'Ritesh Chandak','platform_settings_updated','platform_setting','show_students_to_firms','Updated platform setting \'show_students_to_firms\' to \'false\'.','103.160.175.18','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 11:05:39'),(26,3,'Ritesh Chandak','firm_approved','firm','97','Approved firm registration for ARTH & Associates.','103.160.175.18','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 11:32:30'),(27,3,'Ritesh Chandak','platform_settings_updated','platform_setting','show_students_to_firms','Updated platform setting \'show_students_to_firms\' to \'true\'.','103.160.175.18','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 11:32:57'),(28,3,'Ritesh Chandak','platform_settings_updated','platform_setting','show_companies_to_students','Updated platform setting \'show_companies_to_students\' to \'true\'.','103.160.175.18','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 11:42:34'),(29,3,'Ritesh Chandak','firm_approved','firm','96','Approved firm registration for MUTTHA AND LAHOTI.','103.160.175.18','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 11:44:43'),(30,3,'Ritesh Chandak','firm_approved','firm','92','Approved firm registration for Ketki Dagha And Associates.','103.160.175.18','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 12:36:09'),(31,3,'Ritesh Chandak','firm_approved','firm','62','Approved firm registration for N J LOHE & CO..','103.160.175.18','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 12:44:53');
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_fcm_tokens`
--

LOCK TABLES `admin_fcm_tokens` WRITE;
/*!40000 ALTER TABLE `admin_fcm_tokens` DISABLE KEYS */;
INSERT INTO `admin_fcm_tokens` VALUES (2,2,'ebuTrhmLywcDm7T8joMUV0:APA91bGtyjjufIVE5jbXNOvdJa2xNMof6Ixizm5cVtniqNqm8CwYQMQ8G7gt0HAuTF7idQ8WVTXq5AquAR025pRjfVxUFqf5NMA8WDMEIcfZYewuikBnXQ0','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36 Edg/149.0.0.0','2026-06-22 21:26:08','2026-06-17 23:42:05','2026-06-22 21:26:08'),(8,2,'fMvqwnzxXNSDLnmpgj0MhT:APA91bFfcD1zV_mAzXKFoyNtsKBYCH0wrpf59W15AyeirNOs7VIITekseQSI1BPCleGgLYQNXSyqJ1roq2EPK8FAHrOLDaqmpsrVFkRCZwBwQuEuRPDrypQ','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 22:19:11','2026-06-21 20:40:48','2026-06-22 22:19:11'),(10,2,'fy-_JTHPNf6-hPNUP_p9IH:APA91bFdubPI7zcUetwg7LJoqb3QqyVswpmO0-wg8nDiEYf2LwYf9GgKZ-7CABNlX6snF8ugaa47mkld-QHAkaJlp91ByvmZ5158b_9RcAHMVSXnb9cEaPc','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-24 14:32:04','2026-06-23 10:12:06','2026-06-24 14:32:04');
/*!40000 ALTER TABLE `admin_fcm_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_impersonation_sessions`
--

DROP TABLE IF EXISTS `admin_impersonation_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_impersonation_sessions` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `admin_id` bigint NOT NULL,
  `admin_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_user_id` bigint NOT NULL,
  `target_role` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login_time` datetime NOT NULL,
  `logout_time` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ais_admin` (`admin_id`),
  KEY `idx_ais_target` (`target_user_id`),
  KEY `idx_ais_token` (`token`),
  KEY `idx_ais_logout` (`logout_time`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_impersonation_sessions`
--

LOCK TABLES `admin_impersonation_sessions` WRITE;
/*!40000 ALTER TABLE `admin_impersonation_sessions` DISABLE KEYS */;
INSERT INTO `admin_impersonation_sessions` VALUES (1,2,'TusharB',76,'student','d1RCYlJEWnB0OFhvcDBHcVBpNEplNHdPbWhVckNFSkZJRTIxdmM2Yg==','122.183.33.252','2026-06-19 22:23:06','2026-06-19 22:23:33','2026-06-19 22:23:06'),(2,2,'TusharB',76,'student','bVJnNTZZZnhhZGZpMjNFSjNMdUtRZzhvOVhTam55TkVQVlVYeWdOWA==','122.183.33.252','2026-06-19 22:23:33','2026-06-19 22:23:52','2026-06-19 22:23:33'),(3,2,'TusharB',72,'student','bHV3ZVhxdDNaNDhEMjVCem93M3Z0cHB6dE0xOUJNMWhYRDd1cXl3aA==','122.183.33.252','2026-06-19 22:23:52','2026-06-19 22:26:51','2026-06-19 22:23:52'),(4,2,'TusharB',76,'student','VVRaUENDMnFsRnduUHB3cExUZ1EyZHJUQlV5OUk2RHowUHNpS1hXQQ==','122.183.33.252','2026-06-19 22:26:57','2026-06-19 22:28:49','2026-06-19 22:26:57'),(5,2,'TusharB',76,'student','R2d6aXNxYnlpcnJMS21aSldlVjRIQXNHazZlZDB0bkpYcnVBODJVYQ==','122.183.33.252','2026-06-19 22:28:49',NULL,'2026-06-19 22:28:49');
/*!40000 ALTER TABLE `admin_impersonation_sessions` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_notifications`
--

LOCK TABLES `admin_notifications` WRITE;
/*!40000 ALTER TABLE `admin_notifications` DISABLE KEYS */;
INSERT INTO `admin_notifications` VALUES (1,'firm_verification','New firm verification request','Sancheti Lakade & Associates has completed its profile and is ready for verification review.','/admin/firms','{\"firm_name\": \"Sancheti Lakade & Associates\", \"firm_profile_id\": 2}',1,'2026-06-16 22:08:23','2026-06-16 14:16:13','2026-06-16 22:08:23'),(2,'firm_verification','New firm verification request','C A & Co has completed its profile and is ready for verification review.','/admin/firms','{\"firm_name\": \"C A & Co\", \"firm_profile_id\": 3}',1,'2026-06-16 22:08:23','2026-06-16 17:35:15','2026-06-16 22:08:23'),(3,'firm_verification','New firm verification request','B N S T & Co LLP has completed its profile and is ready for verification review.','/admin/firms','{\"firm_name\": \"B N S T & Co LLP\", \"firm_profile_id\": 4}',1,'2026-06-16 22:08:23','2026-06-16 17:43:35','2026-06-16 22:08:23'),(4,'firm_verification','New firm verification request','Vipin Gujarathi & Co has completed its profile and is ready for verification review.','/admin/firms','{\"firm_name\": \"Vipin Gujarathi & Co\", \"firm_profile_id\": 9}',1,'2026-06-17 23:17:05','2026-06-17 13:31:13','2026-06-17 23:17:05'),(5,'firm_verification','New firm verification request','A R Totala & Co. has completed its profile and is ready for verification review.','/admin/firms','{\"firm_name\": \"A R Totala & Co.\", \"firm_profile_id\": 11}',1,'2026-06-17 23:17:05','2026-06-17 13:36:12','2026-06-17 23:17:05'),(6,'firm_verification','New firm verification request','R S Biyani & Co has completed its profile and is ready for verification review.','/admin/firms','{\"firm_name\": \"R S Biyani & Co\", \"firm_profile_id\": 12}',1,'2026-06-17 23:17:05','2026-06-17 20:34:08','2026-06-17 23:17:05'),(7,'firm_verification','New firm verification request','test has completed its profile and is ready for verification review.','/admin/firms','{\"firm_name\": \"test\", \"firm_profile_id\": 14}',1,'2026-06-17 23:47:40','2026-06-17 23:45:04','2026-06-17 23:47:40'),(8,'contact_submission','New contact form submission','Tushar Bhise sent a message: Feedback','/admin/feedback','{\"name\": \"Tushar Bhise\", \"email\": \"tusharbhise908@gmail.com\", \"subject\": \"Feedback\"}',1,'2026-06-18 00:22:02','2026-06-18 00:21:18','2026-06-18 00:22:02'),(9,'firm_verification','New firm verification request','K A R M & CO. Chartered Accountants has completed its profile and is ready for verification review.','/admin/firms','{\"firm_name\": \"K A R M & CO. Chartered Accountants\", \"firm_profile_id\": 16}',1,'2026-06-19 11:19:42','2026-06-18 20:34:50','2026-06-19 11:19:42'),(10,'firm_verification','New firm verification request','SMW AND Co. has completed its profile and is ready for verification review.','/admin/firms','{\"firm_name\": \"SMW AND Co.\", \"firm_profile_id\": 18}',1,'2026-06-20 21:44:04','2026-06-20 11:45:33','2026-06-20 21:44:04'),(11,'firm_verification','New firm verification request','sys and assosiates has completed its profile and is ready for verification review.','/admin/firms','{\"firm_name\": \"sys and assosiates\", \"firm_profile_id\": 19}',1,'2026-06-20 21:44:04','2026-06-20 12:54:50','2026-06-20 21:44:04'),(12,'firm_verification','New firm verification request','V S A P & Company has completed its profile and is ready for verification review.','/admin/firms','{\"firm_name\": \"V S A P & Company\", \"firm_profile_id\": 24}',1,'2026-06-22 14:36:13','2026-06-22 14:35:43','2026-06-22 14:36:13'),(13,'firm_verification','New firm verification request','H Mistry & Associates has completed its profile and is ready for verification review.','/admin/firms','{\"firm_name\": \"H Mistry & Associates\", \"firm_profile_id\": 25}',1,'2026-06-23 19:45:39','2026-06-23 19:18:55','2026-06-23 19:45:39'),(14,'firm_verification','New firm verification request','MUTTHA AND LAHOTI has completed its profile and is ready for verification review.','/admin/firms','{\"firm_name\": \"MUTTHA AND LAHOTI\", \"firm_profile_id\": 28}',1,'2026-06-24 11:48:18','2026-06-24 10:28:09','2026-06-24 11:48:18'),(15,'firm_verification','New firm verification request','ARTH & Associates has completed its profile and is ready for verification review.','/admin/firms','{\"firm_name\": \"ARTH & Associates\", \"firm_profile_id\": 29}',1,'2026-06-24 11:42:43','2026-06-24 11:31:31','2026-06-24 11:42:43'),(16,'firm_verification','New firm verification request','N J LOHE & CO. has completed its profile and is ready for verification review.','/admin/firms','{\"firm_name\": \"N J LOHE & CO.\", \"firm_profile_id\": 13}',1,'2026-06-24 12:12:18','2026-06-24 12:07:04','2026-06-24 12:12:18'),(17,'firm_verification','New firm verification request','Ketki Dagha And Associates has completed its profile and is ready for verification review.','/admin/firms','{\"firm_name\": \"Ketki Dagha And Associates\", \"firm_profile_id\": 26}',1,'2026-06-24 12:12:13','2026-06-24 12:07:23','2026-06-24 12:12:13');
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
INSERT INTO `admin_users` VALUES (2,'TusharB','tusharb@startyourstory.in','$2y$12$yBcGG6INYhrMyl1gCYU75.KvqovAFtJ5DWhr4uy4Kcqe25uUEYWHm','VZ3wtSs465NUrNbJfIvy9PzJQw3t4XSStdMqjMfgfdg8DpE6irjmRvmh8hzaSh82YkvJ5ayzM1bTEYaI','admin',1,'2026-06-10 01:09:26','2026-06-22 22:30:43'),(3,'Ritesh Chandak','ritesh@startyourstory.in','$2y$12$HPOtmTCG1YKoWBJANaUvGOwcoNCkGUOq.OzUo4cQBvVB4tPHq7kqS','Vq0ZKjiSawrknN8BKzrgbbhNjYTUwbNvrVtk9XBbn2LnNk1JB0dAI8HqbLmG3zYmhYdijAeydG0YV4U5','admin',1,'2026-06-10 01:11:50','2026-06-19 16:59:28');
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_tag_map`
--

LOCK TABLES `blog_tag_map` WRITE;
/*!40000 ALTER TABLE `blog_tag_map` DISABLE KEYS */;
INSERT INTO `blog_tag_map` VALUES (11,1,1),(12,1,7),(13,1,14),(14,1,15),(4,11,1),(5,11,12),(6,11,13);
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_topics`
--

LOCK TABLES `blog_topics` WRITE;
/*!40000 ALTER TABLE `blog_topics` DISABLE KEYS */;
INSERT INTO `blog_topics` VALUES (1,'How to choose a right CA firm for Articleship','how-to-choose-a-right-ca-firm-for-articleship',NULL,NULL,NULL,NULL,'high','published',1,'manual',NULL,NULL,NULL,2,'2026-06-11 21:25:05','2026-06-13 21:22:16'),(2,'What CA Firms Look for Before Hiring Articles?','what-ca-firms-look-for-before-hiring-articles',2,NULL,NULL,NULL,'medium','published',3,'manual',NULL,NULL,NULL,2,'2026-06-13 21:34:37','2026-06-13 21:57:07'),(5,'10 Questions Every CA Student Should Ask Before Joining a Firm','10-questions-every-ca-student-should-ask-before-joining-a-firm',5,NULL,NULL,NULL,'medium','published',6,'manual',NULL,NULL,NULL,2,'2026-06-13 21:35:21','2026-06-13 22:02:21'),(6,'Big 4 vs Mid-Size CA Firms: Which Is Better for Articleship?','big-4-vs-mid-size-ca-firms-which-is-better-for-articleship',5,NULL,NULL,NULL,'medium','published',7,'manual',NULL,NULL,NULL,2,'2026-06-13 21:35:33','2026-06-13 22:04:01'),(7,'How to Prepare for a CA Articleship Interview ?','how-to-prepare-for-a-ca-articleship-interview',2,NULL,NULL,NULL,'medium','published',9,'manual',NULL,NULL,NULL,2,'2026-06-13 21:35:58','2026-06-13 22:09:54'),(8,'Common Mistakes CA Students Make While Choosing Articleship','common-mistakes-ca-students-make-while-choosing-articleship',4,NULL,NULL,NULL,'medium','published',10,'manual',NULL,NULL,NULL,2,'2026-06-13 21:36:15','2026-06-13 22:11:45'),(9,'Stipend vs Exposure: What Matters More in Articleship?','stipend-vs-exposure-what-matters-more-in-articleship',1,'how to choose a right ca firm for articleship?',NULL,NULL,'high','published',11,'manual',NULL,NULL,NULL,2,'2026-06-17 23:52:45','2026-06-17 23:58:21'),(11,'Articleship Roadmap: What to Learn in Your 2 Years of Training','articleship-roadmap-what-to-learn-in-your-2-years-of-training',4,'CA Articleship Roadmap, What to Learn During Articleship, CA Articleship Skills, Articleship Training Guide, CA articleship roadmap for students, skills to learn during articleship training',NULL,NULL,'medium','published',12,'manual',NULL,NULL,NULL,2,'2026-06-20 21:45:18','2026-06-20 21:46:38');
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
  `social_caption` text COLLATE utf8mb4_unicode_ci,
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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blogs`
--

LOCK TABLES `blogs` WRITE;
/*!40000 ALTER TABLE `blogs` DISABLE KEYS */;
INSERT INTO `blogs` VALUES (1,'How to Choose the Right CA Firm for Articleship: Complete Student Guide','how-to-choose-the-right-ca-firm-for-articleship','Selecting the right CA firm for articleship is one of the most important career decisions for a CA student. From audit and taxation exposure to work culture and mentorship, this guide explains the key factors you should evaluate before choosing a firm that aligns with your professional goals.','<p>Articleship is often considered the backbone of a Chartered Accountant\'s professional journey. While examinations build technical knowledge, articleship provides the practical exposure needed to apply that knowledge in real-world business scenarios. Choosing the right CA firm can significantly influence your learning experience, professional growth, and future career opportunities.</p>\r\n\r\n\r\n\r\n<p>Many students make the mistake of selecting a firm based solely on brand name, stipend, or location. However, the ideal articleship firm should align with your long-term career goals and provide meaningful exposure across different areas of practice. Before accepting an offer, it is important to evaluate factors such as domain exposure, client portfolio, mentorship opportunities, work culture, and learning environment.</p>\r\n\r\n\r\n\r\n<p>A good articleship firm should help you develop technical skills in areas such as audit, taxation, GST, compliance, and financial reporting while also improving your communication, problem-solving, and professional skills. The experience gained during these three years often plays a major role in shaping future job opportunities and career direction.</p>\r\n\r\n\r\n\r\n<p>Students should begin by identifying their career interests. If you wish to build a career in audit and assurance, firms with strong audit portfolios may be suitable. If taxation interests you, consider firms known for direct and indirect tax practice. Similarly, students interested in advisory services or consulting should look for firms that offer exposure in those areas.</p>\r\n\r\n\r\n\r\n<p>Exposure is one of the most important factors when evaluating firms. Try to understand the type of assignments articles are typically involved in. A firm that provides opportunities to work on audits, taxation, compliance, client interactions, and industry-specific assignments often offers a more comprehensive learning experience.</p>\r\n\r\n\r\n\r\n<p>Another important consideration is the firm\'s work culture. Speak with existing or former articles whenever possible. Their experiences can provide valuable insights into mentorship quality, team support, learning opportunities, and overall work environment. A supportive culture often contributes significantly to professional development.</p>\r\n\r\n\r\n\r\n<p>Students should also evaluate the firm\'s client base. Exposure to diverse industries such as manufacturing, services, startups, banking, and technology can broaden practical understanding and improve professional competence. Working with varied clients helps articles develop a better understanding of business operations and industry practices.</p>\r\n\r\n\r\n\r\n<p>Location and commute should not be ignored. While learning opportunities remain the primary factor, a reasonable commute can help maintain a healthy balance between office responsibilities and examination preparation. Managing both effectively is essential during the CA journey.</p>\r\n\r\n\r\n\r\n<p>During interviews, students should not hesitate to ask questions about the nature of assignments, team structure, learning opportunities, and areas of practice. This helps in making a more informed decision and demonstrates genuine interest in the role.</p>\r\n\r\n\r\n\r\n<p>Ultimately, there is no single perfect CA firm for every student. The right choice depends on individual career aspirations, preferred learning areas, and personal circumstances. By carefully evaluating available opportunities and prioritizing learning over short-term considerations, students can make a decision that positively impacts their professional future.</p>\r\n\r\n\r\n\r\n<p>Choosing the right CA firm for articleship is an investment in your career. Taking the time to research, evaluate options, and align your decision with long-term goals can help you maximize the value of your articleship experience and build a strong foundation for a successful Chartered Accountancy career.</p>','blog-images/featured/WuVhJqTBx8KQvZk9qwuiHgW2E0gTekOlalNIiEId.webp','How to Choose the Right CA Firm for Articleship: Complete Student Guide','Confused about selecting a CA firm for articleship? Learn how to evaluate firms based on exposure, work culture, learning opportunities, and long-term career goals.',NULL,'published',1,'2026-06-18 22:45:41','2026-06-11 22:03:28','2026-06-18 22:49:14'),(3,'What CA Firms Look for Before Hiring Articles?','what-ca-firms-look-for-before-hiring-articles','Many CA students focus only on marks and technical knowledge when applying for articleship, but CA firms evaluate much more than academic performance. Learn what firms actually look for before hiring articles and how you can improve your chances of getting selected.','<p>Every year, thousands of CA students apply for articleship opportunities across India. While many candidates believe that academic marks alone determine selection, the reality is very different. Most CA firms evaluate a combination of technical knowledge, communication skills, professionalism, attitude, and learning potential before making hiring decisions.</p>\n\n<p>Understanding what firms actually look for can help students prepare more effectively and improve their chances of securing quality articleship opportunities.</p>\n\n<h2>Academic Performance Matters, But It Is Not Everything</h2>\n\n<p>Academic results are usually the first thing recruiters notice on a resume. Good marks demonstrate discipline, consistency, and commitment toward studies.</p>\n\n<p>However, firms rarely make hiring decisions based solely on marks. Many successful articles have average academic scores but possess strong communication skills, confidence, and a willingness to learn.</p>\n\n<p>Students should focus on presenting a balanced profile rather than relying entirely on examination results.</p>\n\n<h2>Communication Skills Create a Strong First Impression</h2>\n\n<p>One of the most important qualities firms evaluate is communication.</p>\n\n<p>Articles regularly interact with:</p>\n\n<ul>\n<li>Clients</li>\n<li>Managers</li>\n<li>Partners</li>\n<li>Team members</li>\n<li>Government departments</li>\n</ul>\n\n<p>Students who can express themselves clearly often stand out during interviews.</p>\n\n<p>Good communication does not mean speaking perfect English. It means being able to explain thoughts confidently, listen carefully, and communicate professionally.</p>\n\n<h2>Learning Attitude Is Highly Valued</h2>\n\n<p>Firms understand that students join articleship to learn. Recruiters do not expect candidates to know everything.</p>\n\n<p>Instead, they often assess whether the student is:</p>\n\n<ul>\n<li>Curious</li>\n<li>Open to feedback</li>\n<li>Willing to learn</li>\n<li>Adaptable</li>\n<li>Professional</li>\n</ul>\n\n<p>A candidate who demonstrates enthusiasm for learning is often preferred over someone who appears overconfident or uninterested.</p>\n\n<h2>Profile Completeness Reflects Seriousness</h2>\n\n<p>Before scheduling interviews, many firms review candidate profiles and resumes carefully.</p>\n\n<p>Students who provide complete information usually create a stronger impression.</p>\n\n<p>A good profile should include:</p>\n\n<ul>\n<li>Educational qualifications</li>\n<li>CA progress details</li>\n<li>Skills</li>\n<li>Certifications</li>\n<li>Career interests</li>\n<li>Resume</li>\n</ul>\n\n<p>An incomplete profile often signals a lack of seriousness toward career opportunities.</p>\n\n<h2>Basic Technical Knowledge Is Expected</h2>\n\n<p>Students are not expected to possess advanced practical experience before joining articleship.</p>\n\n<p>However, firms generally expect clarity in basic concepts such as:</p>\n\n<ul>\n<li>Accounting fundamentals</li>\n<li>Journal entries</li>\n<li>Financial statements</li>\n<li>GST basics</li>\n<li>Income tax fundamentals</li>\n<li>Audit concepts</li>\n</ul>\n\n<p>Students should revise important concepts before attending interviews.</p>\n\n<h2>Professionalism Matters More Than Students Realize</h2>\n\n<p>Professional behavior begins long before the interview itself.</p>\n\n<p>Recruiters often observe:</p>\n\n<ul>\n<li>Email communication</li>\n<li>Resume presentation</li>\n<li>Punctuality</li>\n<li>Dress code</li>\n<li>Interview etiquette</li>\n<li>Responsiveness</li>\n</ul>\n\n<p>Small details can significantly influence hiring decisions.</p>\n\n<h2>Confidence Without Arrogance</h2>\n\n<p>Many students either become too nervous or try to appear overly confident during interviews.</p>\n\n<p>Firms generally prefer candidates who are:</p>\n\n<ul>\n<li>Confident</li>\n<li>Respectful</li>\n<li>Honest</li>\n<li>Professional</li>\n</ul>\n\n<p>If you do not know an answer, admitting it honestly is often better than guessing incorrectly.</p>\n\n<h2>Long-Term Commitment Is Important</h2>\n\n<p>Training an article requires time and effort from the firm.</p>\n\n<p>As a result, firms prefer students who demonstrate commitment and stability.</p>\n\n<p>Recruiters may ask questions to understand:</p>\n\n<ul>\n<li>Career goals</li>\n<li>Reasons for applying</li>\n<li>Interest in specific domains</li>\n<li>Long-term aspirations</li>\n</ul>\n\n<p>Students who show genuine interest in learning and contributing usually make a stronger impression.</p>\n\n<h2>Resume Quality Can Influence Interview Opportunities</h2>\n\n<p>Your resume is often the first interaction a firm has with you.</p>\n\n<p>A strong resume should be:</p>\n\n<ul>\n<li>Professional</li>\n<li>Well-structured</li>\n<li>Error-free</li>\n<li>Easy to read</li>\n<li>Focused on relevant achievements</li>\n</ul>\n\n<p>Even highly capable students may miss opportunities if their resume fails to present their strengths effectively.</p>\n\n<h2>What Makes a Candidate Stand Out?</h2>\n\n<p>The most successful candidates usually combine multiple strengths.</p>\n\n<ul>\n<li>Strong communication skills</li>\n<li>Positive attitude</li>\n<li>Good academic foundation</li>\n<li>Professional behavior</li>\n<li>Learning mindset</li>\n<li>Well-prepared profile</li>\n<li>Clear career goals</li>\n</ul>\n\n<p>Firms are ultimately looking for students who can grow into competent professionals over the course of their articleship.</p>\n\n<h2>Final Thoughts</h2>\n\n<p>Articleship interviews are not designed to identify perfect candidates. They are designed to identify students who have the potential to learn, contribute, and grow.</p>\n\n<p>While academic performance remains important, firms often place equal or greater emphasis on communication, attitude, professionalism, and willingness to learn.</p>\n\n<p>Students who focus on developing these qualities alongside technical knowledge significantly improve their chances of securing opportunities at quality CA firms.</p>\n\n<p><b>The best candidates are not necessarily those who know the mostâ€”they are those who demonstrate the greatest potential to learn and grow.</b></p>',NULL,'What CA Firms Look for Before Hiring Articles | Articleship Selection Guide','Discover what CA firms evaluate before hiring articleship candidates, including communication skills, attitude, learning ability, professionalism, and technical knowledge.',NULL,'published',2,'2026-06-13 21:57:07','2026-06-13 21:57:07','2026-06-13 21:57:07'),(6,'10 Questions Every CA Student Should Ask Before Joining a Firm','10-questions-every-ca-student-should-ask-before-joining-a-firm','Choosing the right CA firm is one of the most important decisions a CA student will make during articleship. Unfortunately, many students focus only on stipend or firm name and fail to ask questions that reveal the true learning opportunities available. Here are 10 essential questions every CA student should ask before accepting an articleship offer.','<p>Selecting a CA firm for articleship is not just about securing a training position. It is about choosing an environment where you will spend two important years developing technical knowledge, professional skills, and practical experience.</p>\r\n\r\n<p>Many students make the mistake of accepting the first offer they receive or choosing a firm solely based on stipend or brand value. While these factors are important, they rarely determine the quality of learning you will receive during articleship.</p>\r\n\r\n<p>Before joining any CA firm, students should ask the right questions to evaluate whether the opportunity aligns with their career goals.</p>\r\n\r\n<h2>Why Asking Questions Matters</h2>\r\n\r\n<p>Articleship is a significant investment of time and effort. The right firm can provide excellent exposure, mentorship, and growth opportunities, while the wrong choice can limit learning and professional development.</p>\r\n\r\n<p>Asking thoughtful questions demonstrates maturity and genuine interest in your career. It also helps you understand what to expect from the firm before making a commitment.</p>\r\n\r\n<h2>1. What Domains Will I Be Working In?</h2>\r\n\r\n<p>This should be one of the first questions you ask.</p>\r\n\r\n<p>Different firms specialize in different service areas such as:</p>\r\n\r\n<ul>\r\n<li>Statutory Audit</li>\r\n<li>Internal Audit</li>\r\n<li>Tax Audit</li>\r\n<li>Direct Taxation</li>\r\n<li>GST Compliance</li>\r\n<li>ROC Compliance</li>\r\n<li>Advisory Services</li>\r\n</ul>\r\n\r\n<p>Understanding domain exposure helps you evaluate whether the role supports your long-term career goals.</p>\r\n\r\n<h2>2. How Much Audit Exposure Will I Receive?</h2>\r\n\r\n<p>Audit assignments provide valuable learning opportunities related to financial statements, internal controls, risk assessment, and business processes.</p>\r\n\r\n<p>Ask whether articles are actively involved in audits and how responsibilities are distributed within the team.</p>\r\n\r\n<h2>3. Will I Get Exposure to Taxation Work?</h2>\r\n\r\n<p>Taxation remains one of the most important areas for Chartered Accountants.</p>\r\n\r\n<p>Students should understand whether they will gain practical experience in:</p>\r\n\r\n<ul>\r\n<li>Income Tax Returns</li>\r\n<li>Tax Audits</li>\r\n<li>TDS Compliance</li>\r\n<li>GST Compliance</li>\r\n<li>Tax Assessments</li>\r\n</ul>\r\n\r\n<p>Taxation exposure significantly enhances professional competence.</p>\r\n\r\n<h2>4. Will I Have Opportunities to Interact with Clients?</h2>\r\n\r\n<p>Technical knowledge is important, but communication and relationship management skills are equally valuable.</p>\r\n\r\n<p>Client interaction helps students:</p>\r\n\r\n<ul>\r\n<li>Develop confidence</li>\r\n<li>Improve communication skills</li>\r\n<li>Understand business challenges</li>\r\n<li>Build professional maturity</li>\r\n</ul>\r\n\r\n<p>Students should seek opportunities that involve direct client exposure whenever possible.</p>\r\n\r\n<h2>5. Which Industries Does the Firm Serve?</h2>\r\n\r\n<p>Industry exposure can significantly broaden your understanding of business operations.</p>\r\n\r\n<p>Ask about the firm\'s client portfolio and industries served, such as:</p>\r\n\r\n<ul>\r\n<li>Manufacturing</li>\r\n<li>Information Technology</li>\r\n<li>Healthcare</li>\r\n<li>Retail</li>\r\n<li>Financial Services</li>\r\n<li>E-commerce</li>\r\n</ul>\r\n\r\n<p>Exposure to multiple industries provides a broader perspective and improves adaptability.</p>\r\n\r\n<h2>6. Is There a Structured Training or Mentorship Process?</h2>\r\n\r\n<p>Not all firms have formal training systems.</p>\r\n\r\n<p>Ask:</p>\r\n\r\n<ul>\r\n<li>Who will guide articles?</li>\r\n<li>How are assignments allocated?</li>\r\n<li>Are training sessions conducted?</li>\r\n<li>How is performance reviewed?</li>\r\n</ul>\r\n\r\n<p>Strong mentorship often accelerates learning and professional growth.</p>\r\n\r\n<h2>7. What Level of Responsibility Will I Receive?</h2>\r\n\r\n<p>The best learning often comes from responsibility.</p>\r\n\r\n<p>Students should understand whether they will:</p>\r\n\r\n<ul>\r\n<li>Handle assignments independently</li>\r\n<li>Prepare reports</li>\r\n<li>Coordinate with clients</li>\r\n<li>Participate in field work</li>\r\n<li>Work directly with seniors and partners</li>\r\n</ul>\r\n\r\n<p>Greater responsibility usually results in stronger practical learning.</p>\r\n\r\n<h2>8. What Skills Do Successful Articles Typically Develop Here?</h2>\r\n\r\n<p>This question often reveals valuable insights about the firm\'s culture and learning environment.</p>\r\n\r\n<p>The response can help you understand whether the firm focuses on:</p>\r\n\r\n<ul>\r\n<li>Technical development</li>\r\n<li>Leadership skills</li>\r\n<li>Client management</li>\r\n<li>Problem-solving abilities</li>\r\n<li>Industry specialization</li>\r\n</ul>\r\n\r\n<p>The answer can provide a realistic picture of what your growth journey may look like.</p>\r\n\r\n<h2>9. What Are the Expectations from Articles?</h2>\r\n\r\n<p>Every firm has different expectations.</p>\r\n\r\n<p>Understanding these expectations helps avoid future misunderstandings.</p>\r\n\r\n<p>Ask about:</p>\r\n\r\n<ul>\r\n<li>Working hours</li>\r\n<li>Travel requirements</li>\r\n<li>Assignment deadlines</li>\r\n<li>Reporting structure</li>\r\n<li>Professional conduct expectations</li>\r\n</ul>\r\n\r\n<p>Clarity at the beginning creates a smoother articleship experience.</p>\r\n\r\n<h2>10. Why Do Existing Articles Choose to Stay Here?</h2>\r\n\r\n<p>This is one of the most powerful questions students can ask.</p>\r\n\r\n<p>The answer often reveals the firm\'s actual strengths.</p>\r\n\r\n<p>If possible, speak directly with existing articles and ask about:</p>\r\n\r\n<ul>\r\n<li>Learning opportunities</li>\r\n<li>Work culture</li>\r\n<li>Mentorship quality</li>\r\n<li>Professional growth</li>\r\n<li>Overall experience</li>\r\n</ul>\r\n\r\n<p>Current articles can often provide insights that are not visible during interviews.</p>\r\n\r\n<h2>Questions Students Often Forget to Ask</h2>\r\n\r\n<p>In addition to the ten questions above, students should also understand:</p>\r\n\r\n<ul>\r\n<li>How performance feedback is provided</li>\r\n<li>Whether technology tools are used</li>\r\n<li>Exposure to advanced assignments</li>\r\n<li>Availability of support during exams</li>\r\n<li>Long-term learning opportunities</li>\r\n</ul>\r\n\r\n<p>These details can significantly influence the overall quality of articleship.</p>\r\n\r\n<h2>Final Thoughts</h2>\r\n\r\n<p>Choosing a CA firm is one of the most important career decisions a student will make during articleship. The right firm can provide technical expertise, professional confidence, industry exposure, and mentorship that continue to benefit you long after qualification.</p>\r\n\r\n<p>Before accepting an offer, take time to ask meaningful questions and evaluate the opportunity carefully.</p>\r\n\r\n<p><b>The goal is not simply to join a firm. The goal is to join a firm that helps you become a better Chartered Accountant.</b></p>\r\n\r\n<p>The answers to these questions can help you make a more informed decision and maximize the value of your articleship experience.</p>','blog-images/featured/vRHbfq3Ltc9bZX4OZdtB5tCpeIQhmOy4l7xAfMMv.png','10 Questions Every CA Student Should Ask Before Joining a CA Firm','Discover the 10 most important questions CA students should ask before joining a firm for articleship. Learn how to evaluate exposure, mentorship, learning opportunities, and career growth potential.',NULL,'published',5,'2026-06-13 22:02:21','2026-06-13 22:02:21','2026-06-16 01:42:39'),(7,'Big 4 vs Mid-Size CA Firms: Which Is Better for Articleship?','big-4-vs-mid-size-ca-firms-which-is-better-for-articleship','One of the biggest decisions CA students face before starting articleship is whether to join a Big 4 firm or a mid-sized CA firm. While Big 4 firms offer brand value and structured processes, mid-sized firms often provide broader exposure and greater responsibility. This guide explores the advantages, limitations, and ideal candidate profiles for both options to help students make an informed decision','<p>One of the most common questions CA students ask before beginning articleship is whether they should join a <b>Big 4 firm</b> or a <b>mid-sized CA firm</b>. The debate has existed for years, and there is no universal answer because both options offer unique advantages and challenges.</p>\r\n\r\n<p>Many students automatically assume that securing a Big 4 articleship is the ultimate goal. While Big 4 firms certainly provide valuable opportunities, mid-sized firms can often offer learning experiences that are equally beneficial, depending on your career aspirations.</p>\r\n\r\n<p>The real question is not which option is better overall. The real question is <b>which option is better for you.</b></p>\r\n\r\n<h2>Understanding the Big 4 Firms</h2>\r\n\r\n<p>The term Big 4 generally refers to:</p>\r\n\r\n<ul>\r\n<li>Deloitte</li>\r\n<li>EY</li>\r\n<li>KPMG</li>\r\n<li>PwC</li>\r\n</ul>\r\n\r\n<p>These firms operate globally and serve some of the world\'s largest companies. They are known for structured processes, specialized teams, extensive resources, and strong brand recognition.</p>\r\n\r\n<p>Many CA students are attracted to the Big 4 because of the prestige associated with these organizations.</p>\r\n\r\n<h2>What Is Considered a Mid-Sized CA Firm?</h2>\r\n\r\n<p>Mid-sized firms vary significantly in size and specialization. Some may have multiple offices and hundreds of employees, while others operate with smaller teams and diverse service offerings.</p>\r\n\r\n<p>Unlike Big 4 firms, mid-sized firms often provide services across multiple domains such as:</p>\r\n\r\n<ul>\r\n<li>Statutory Audit</li>\r\n<li>Tax Audit</li>\r\n<li>Direct Taxation</li>\r\n<li>GST Compliance</li>\r\n<li>Internal Audit</li>\r\n<li>ROC Compliance</li>\r\n<li>Advisory Services</li>\r\n</ul>\r\n\r\n<p>This often creates broader learning opportunities for articles.</p>\r\n\r\n<h2>Exposure: Broad vs Specialized</h2>\r\n\r\n<p>This is perhaps the most important difference between the two options.</p>\r\n\r\n<p>In many Big 4 firms, articles work within highly specialized teams. For example, a student may spend most of their articleship working exclusively in statutory audit or internal audit.</p>\r\n\r\n<p>While this creates deep expertise in a particular area, exposure may be limited to that specific function.</p>\r\n\r\n<p>In contrast, mid-sized firms frequently allow students to work across multiple domains. An article may gain exposure to audit, taxation, compliance, and client advisory work within the same training period.</p>\r\n\r\n<p><b>Big 4 often provides depth. Mid-sized firms often provide breadth.</b></p>\r\n\r\n<h2>Client Exposure and Interaction</h2>\r\n\r\n<p>Client interaction is an important part of professional development.</p>\r\n\r\n<p>In large organizations, communication often follows multiple reporting levels. Students may primarily interact with managers and senior team members.</p>\r\n\r\n<p>Mid-sized firms often provide direct exposure to:</p>\r\n\r\n<ul>\r\n<li>Business owners</li>\r\n<li>CFOs</li>\r\n<li>Finance managers</li>\r\n<li>Partners</li>\r\n<li>Entrepreneurs</li>\r\n</ul>\r\n\r\n<p>This direct interaction can significantly improve communication skills and business understanding.</p>\r\n\r\n<h2>Responsibility and Ownership</h2>\r\n\r\n<p>Articleship is most valuable when students receive meaningful responsibility.</p>\r\n\r\n<p>In Big 4 firms, students typically work on larger engagements with defined roles and responsibilities.</p>\r\n\r\n<p>In mid-sized firms, articles may be entrusted with greater ownership at an earlier stage, including:</p>\r\n\r\n<ul>\r\n<li>Handling assignments independently</li>\r\n<li>Preparing reports</li>\r\n<li>Communicating with clients</li>\r\n<li>Managing timelines</li>\r\n<li>Coordinating fieldwork</li>\r\n</ul>\r\n\r\n<p>This responsibility often accelerates learning and confidence.</p>\r\n\r\n<h2>Learning Environment and Training</h2>\r\n\r\n<p>One major advantage of Big 4 firms is their structured training systems.</p>\r\n\r\n<p>Students often benefit from:</p>\r\n\r\n<ul>\r\n<li>Formal training programs</li>\r\n<li>Learning portals</li>\r\n<li>Standardized methodologies</li>\r\n<li>Professional development initiatives</li>\r\n</ul>\r\n\r\n<p>Mid-sized firms may not always offer the same level of structured training, but they often compensate through practical exposure and hands-on learning.</p>\r\n\r\n<h2>Work Culture and Team Size</h2>\r\n\r\n<p>Work culture can vary significantly between organizations.</p>\r\n\r\n<p>Large firms typically operate with extensive teams and defined hierarchies.</p>\r\n\r\n<p>Mid-sized firms often have smaller teams where articles work closely with managers, qualified professionals, and partners.</p>\r\n\r\n<p>This can create stronger mentorship opportunities and more personalized guidance.</p>\r\n\r\n<h2>Career Opportunities After Qualification</h2>\r\n\r\n<p>Both Big 4 and mid-sized firms can open excellent career opportunities after qualification.</p>\r\n\r\n<p>Big 4 experience is often highly valued by:</p>\r\n\r\n<ul>\r\n<li>Multinational corporations</li>\r\n<li>Consulting firms</li>\r\n<li>Global organizations</li>\r\n<li>Large finance teams</li>\r\n</ul>\r\n\r\n<p>Mid-sized firm experience can be equally valuable because students often develop broader practical skills and exposure across multiple domains.</p>\r\n\r\n<p>Recruiters increasingly focus on actual experience rather than firm names alone.</p>\r\n\r\n<h2>Common Myths About Big 4 Articleship</h2>\r\n\r\n<p>Many students believe:</p>\r\n\r\n<ul>\r\n<li>Big 4 guarantees career success.</li>\r\n<li>Mid-sized firms offer limited learning.</li>\r\n<li>Only Big 4 experience matters during interviews.</li>\r\n</ul>\r\n\r\n<p>These assumptions are often inaccurate.</p>\r\n\r\n<p>Successful Chartered Accountants come from both Big 4 and mid-sized firms. Long-term success depends more on learning, skill development, and professional growth than on firm branding alone.</p>\r\n\r\n<h2>Who Should Consider a Big 4 Firm?</h2>\r\n\r\n<p>A Big 4 articleship may be ideal for students who:</p>\r\n\r\n<ul>\r\n<li>Prefer structured learning environments</li>\r\n<li>Want exposure to large corporate clients</li>\r\n<li>Are interested in multinational organizations</li>\r\n<li>Plan to pursue consulting or corporate finance roles</li>\r\n<li>Value global brand recognition</li>\r\n</ul>\r\n\r\n<h2>Who Should Consider a Mid-Sized Firm?</h2>\r\n\r\n<p>A mid-sized firm may be ideal for students who:</p>\r\n\r\n<ul>\r\n<li>Want exposure across multiple domains</li>\r\n<li>Prefer broader practical learning</li>\r\n<li>Enjoy client interaction</li>\r\n<li>Want greater responsibility early in their careers</li>\r\n<li>Plan to enter practice in the future</li>\r\n</ul>\r\n\r\n<h2>Questions to Ask Before Deciding</h2>\r\n\r\n<p>Regardless of firm size, every student should ask:</p>\r\n\r\n<ul>\r\n<li>What domains will I work in?</li>\r\n<li>How much audit exposure will I receive?</li>\r\n<li>Will I work on taxation assignments?</li>\r\n<li>Will I interact with clients?</li>\r\n<li>What training opportunities are available?</li>\r\n<li>How much responsibility will I receive?</li>\r\n</ul>\r\n\r\n<p>The answers to these questions are often more important than the firm\'s name.</p>\r\n\r\n<h2>Final Thoughts</h2>\r\n\r\n<p>The Big 4 versus mid-sized firm debate does not have a universally correct answer. Both options can provide outstanding learning opportunities when aligned with your career goals.</p>\r\n\r\n<p>Instead of asking which firm is more prestigious, ask which firm will help you develop the skills, exposure, and professional confidence you need for your future career.</p>\r\n\r\n<p><b>The best articleship is not necessarily the one with the biggest brand nameâ€”it is the one that helps you become the best Chartered Accountant you can be.</b></p>','blog-images/featured/uGts7BrKmqeLVlRPNkXVFXBQaNOUch0VSa6Ppppv.png','Big 4 vs Mid-Size CA Firms: Which Is Better for Articleship?','Confused between a Big 4 and a mid-sized CA firm for articleship? Compare exposure, learning opportunities, work culture, responsibilities, and career growth to make the right decision.',NULL,'published',5,'2026-06-13 22:04:01','2026-06-13 22:04:01','2026-06-16 01:42:30'),(9,'How to Prepare for a CA Articleship Interview ?','how-to-prepare-for-a-ca-articleship-interview','Getting shortlisted for an articleship interview is only the first step. The real challenge is convincing a CA firm that you have the potential to learn, contribute, and grow as a professional. This guide covers everything CA students need to know to prepare for an articleship interview, from technical concepts and resume preparation to communication skills and common interview questions.','<p>For many CA students, securing an articleship interview is an exciting milestone. It represents the first step into the professional world and an opportunity to begin gaining practical experience as a future Chartered Accountant.</p>\r\n\r\n<p>However, receiving an interview call is only the beginning. Competition for quality articleship opportunities can be intense, and firms often evaluate multiple candidates before making a decision.</p>\r\n\r\n<p>The good news is that successful articleship interviews rarely depend on extraordinary knowledge. Most firms are looking for students who demonstrate professionalism, communication skills, a willingness to learn, and a strong foundation in basic concepts.</p>\r\n\r\n<p>This guide will help you prepare effectively and improve your chances of success.</p>\r\n\r\n<h2>Understand What Firms Are Actually Looking For</h2>\r\n\r\n<p>One of the biggest misconceptions among students is that firms expect articleship candidates to possess extensive practical experience.</p>\r\n\r\n<p>In reality, firms understand that students are joining to learn.</p>\r\n\r\n<p>Most recruiters evaluate:</p>\r\n\r\n<ul>\r\n<li>Communication skills</li>\r\n<li>Professional attitude</li>\r\n<li>Learning mindset</li>\r\n<li>Basic technical knowledge</li>\r\n<li>Confidence</li>\r\n<li>Reliability</li>\r\n<li>Career interest</li>\r\n</ul>\r\n\r\n<p>Your objective is not to prove that you know everything. Your objective is to demonstrate that you have the potential to become a valuable professional.</p>\r\n\r\n<h2>Research the Firm Before the Interview</h2>\r\n\r\n<p>Many students attend interviews without understanding the firm they are applying to.</p>\r\n\r\n<p>This is a mistake that can easily be avoided.</p>\r\n\r\n<p>Before the interview, learn about:</p>\r\n\r\n<ul>\r\n<li>The firm\'s services</li>\r\n<li>Practice areas</li>\r\n<li>Industries served</li>\r\n<li>Office locations</li>\r\n<li>Partners and leadership</li>\r\n<li>Recent achievements or developments</li>\r\n</ul>\r\n\r\n<p>Understanding the firm helps you answer questions more effectively and demonstrates genuine interest in the opportunity.</p>\r\n\r\n<h2>Prepare a Professional Resume</h2>\r\n\r\n<p>Your resume is often the first impression a firm has of you.</p>\r\n\r\n<p>A strong articleship resume should include:</p>\r\n\r\n<ul>\r\n<li>Educational qualifications</li>\r\n<li>CA progress details</li>\r\n<li>Academic achievements</li>\r\n<li>Technical skills</li>\r\n<li>Certifications</li>\r\n<li>Extracurricular activities</li>\r\n<li>Contact information</li>\r\n</ul>\r\n\r\n<p>Keep the format clean, professional, and easy to read.</p>\r\n\r\n<p>Always review your resume before the interview because many interview questions are based on information mentioned in it.</p>\r\n\r\n<h2>Revise Basic Technical Concepts</h2>\r\n\r\n<p>Most articleship interviews include questions on fundamental accounting, audit, and taxation concepts.</p>\r\n\r\n<p>Students should revise:</p>\r\n\r\n<ul>\r\n<li>Journal Entries</li>\r\n<li>Accounting Principles</li>\r\n<li>Financial Statements</li>\r\n<li>Depreciation</li>\r\n<li>Bank Reconciliation</li>\r\n<li>Audit Basics</li>\r\n<li>GST Fundamentals</li>\r\n<li>Income Tax Basics</li>\r\n</ul>\r\n\r\n<p>Interviewers are generally not looking for advanced expertise. They want to assess whether your conceptual foundation is strong.</p>\r\n\r\n<h2>Prepare for Common HR Questions</h2>\r\n\r\n<p>In addition to technical questions, firms often ask questions to understand your personality and career goals.</p>\r\n\r\n<p>Common questions include:</p>\r\n\r\n<ul>\r\n<li>Tell us about yourself.</li>\r\n<li>Why do you want to join our firm?</li>\r\n<li>Why are you pursuing Chartered Accountancy?</li>\r\n<li>What are your strengths?</li>\r\n<li>What are your weaknesses?</li>\r\n<li>Where do you see yourself in the future?</li>\r\n<li>What do you expect from articleship?</li>\r\n</ul>\r\n\r\n<p>Practice answering these questions confidently and naturally.</p>\r\n\r\n<h2>Improve Your Communication Skills</h2>\r\n\r\n<p>Communication plays a major role in interview performance.</p>\r\n\r\n<p>Students do not need perfect English to succeed. What matters is clarity, confidence, and professionalism.</p>\r\n\r\n<p>Focus on:</p>\r\n\r\n<ul>\r\n<li>Speaking clearly</li>\r\n<li>Listening carefully</li>\r\n<li>Maintaining eye contact</li>\r\n<li>Answering directly</li>\r\n<li>Avoiding unnecessary filler words</li>\r\n</ul>\r\n\r\n<p>Good communication often leaves a stronger impression than memorized answers.</p>\r\n\r\n<h2>Dress Professionally</h2>\r\n\r\n<p>Professional appearance demonstrates seriousness and respect for the opportunity.</p>\r\n\r\n<p>Recommended attire includes:</p>\r\n\r\n<ul>\r\n<li>Formal shirt</li>\r\n<li>Formal trousers</li>\r\n<li>Polished shoes</li>\r\n<li>Neat grooming</li>\r\n</ul>\r\n\r\n<p>Even virtual interviews require professional presentation.</p>\r\n\r\n<p>First impressions matter.</p>\r\n\r\n<h2>Be Ready to Discuss Your Academic Background</h2>\r\n\r\n<p>Interviewers frequently ask questions about academic performance.</p>\r\n\r\n<p>Be prepared to discuss:</p>\r\n\r\n<ul>\r\n<li>CA exam progress</li>\r\n<li>Educational background</li>\r\n<li>Academic strengths</li>\r\n<li>Challenges you have overcome</li>\r\n<li>Subjects you enjoy</li>\r\n</ul>\r\n\r\n<p>Answer honestly and confidently.</p>\r\n\r\n<h2>Practice Mock Interviews</h2>\r\n\r\n<p>One of the most effective preparation techniques is participating in mock interviews.</p>\r\n\r\n<p>Practice with:</p>\r\n\r\n<ul>\r\n<li>Friends</li>\r\n<li>Seniors</li>\r\n<li>Mentors</li>\r\n<li>Faculty members</li>\r\n</ul>\r\n\r\n<p>Mock interviews help identify weaknesses and improve confidence before the actual interview.</p>\r\n\r\n<h2>Questions You Can Ask the Interviewer</h2>\r\n\r\n<p>At the end of the interview, candidates often receive an opportunity to ask questions.</p>\r\n\r\n<p>Good questions include:</p>\r\n\r\n<ul>\r\n<li>What domains will I be exposed to?</li>\r\n<li>How are assignments allocated?</li>\r\n<li>Will I receive client interaction opportunities?</li>\r\n<li>Is there a mentorship structure?</li>\r\n<li>What skills do successful articles develop here?</li>\r\n</ul>\r\n\r\n<p>Thoughtful questions demonstrate curiosity and professionalism.</p>\r\n\r\n<h2>Common Mistakes to Avoid</h2>\r\n\r\n<p>Many candidates make avoidable mistakes during interviews.</p>\r\n\r\n<p>Examples include:</p>\r\n\r\n<ul>\r\n<li>Arriving late</li>\r\n<li>Not researching the firm</li>\r\n<li>Providing vague answers</li>\r\n<li>Overconfidence</li>\r\n<li>Dishonesty</li>\r\n<li>Criticizing previous experiences</li>\r\n<li>Ignoring basic etiquette</li>\r\n</ul>\r\n\r\n<p>Professionalism often matters as much as technical knowledge.</p>\r\n\r\n<h2>What If You Don\'t Know an Answer?</h2>\r\n\r\n<p>Every candidate encounters questions they cannot answer.</p>\r\n\r\n<p>If this happens:</p>\r\n\r\n<ul>\r\n<li>Stay calm</li>\r\n<li>Be honest</li>\r\n<li>Avoid guessing wildly</li>\r\n<li>Express willingness to learn</li>\r\n</ul>\r\n\r\n<p>Interviewers usually appreciate honesty more than incorrect answers presented with confidence.</p>\r\n\r\n<h2>Final Thoughts</h2>\r\n\r\n<p>Articleship interviews are not designed to identify perfect candidates. They are designed to identify students who have the right attitude, strong fundamentals, and the willingness to learn.</p>\r\n\r\n<p>Preparation, professionalism, and confidence can significantly improve your chances of success.</p>\r\n\r\n<p>Remember that firms are not simply hiring for current knowledgeâ€”they are investing in future professionals.</p>\r\n\r\n<p><b>Walk into your interview with confidence, prepare thoroughly, and focus on demonstrating your potential. That approach will often make a stronger impression than trying to appear perfect.</b></p>','blog-images/featured/o8IeGBpNok12tRokPzb02N4zn0m2FCWDfpoSCSVf.png','How to Prepare for a CA Articleship Interview | Complete Interview Guide','Learn how to prepare for a CA articleship interview with practical tips on technical questions, resume preparation, communication skills, interview etiquette, and common mistakes to avoid.',NULL,'published',2,'2026-06-13 22:09:54','2026-06-13 22:09:54','2026-06-16 01:42:14'),(10,'Common Mistakes CA Students Make While Choosing Articleship','common-mistakes-ca-students-make-while-choosing-articleship','Choosing the right articleship is one of the most important career decisions a CA student will make. Unfortunately, many students focus on the wrong factors and later regret their choices. Understanding the common mistakes students make while selecting articleship can help you make a smarter decision and maximize the value of your training period.','<p>Choosing an articleship is one of the most important decisions in a CA student\'s journey. The firm you join will influence your practical learning, professional skills, confidence, industry exposure, and future career opportunities.</p>\r\n\r\n<p>Despite its importance, many students make decisions based on incomplete information, assumptions, or short-term considerations. The result is often disappointment, limited learning opportunities, and missed career growth.</p>\r\n\r\n<p>Fortunately, most of these mistakes can be avoided with proper planning and research.</p>\r\n\r\n<p>Let\'s look at some of the most common mistakes CA students make while choosing articleship and how you can avoid them.</p>\r\n\r\n<h2>1. Choosing a Firm Based Only on Stipend</h2>\r\n\r\n<p>This is perhaps the most common mistake.</p>\r\n\r\n<p>While stipend is an important factor, it should not be the primary reason for selecting a firm.</p>\r\n\r\n<p>Many students compare offers based solely on monthly compensation without evaluating the quality of exposure they will receive.</p>\r\n\r\n<p>A firm offering slightly lower stipend but significantly better learning opportunities often provides greater long-term value.</p>\r\n\r\n<p>Remember that articleship lasts for a limited period, but the skills you gain can benefit your entire career.</p>\r\n\r\n<h2>2. Assuming Bigger Firms Are Always Better</h2>\r\n\r\n<p>Many students automatically assume that larger firms guarantee better learning.</p>\r\n\r\n<p>While large firms often provide excellent opportunities, they may also involve specialized roles that limit exposure to certain domains.</p>\r\n\r\n<p>Mid-sized firms frequently offer broader practical experience, direct client interaction, and greater responsibility.</p>\r\n\r\n<p>The right choice depends on your learning goals rather than the size of the firm alone.</p>\r\n\r\n<h2>3. Ignoring Domain Exposure</h2>\r\n\r\n<p>Students often focus on firm names while overlooking the actual work they will perform.</p>\r\n\r\n<p>Before joining any firm, understand whether you will gain exposure to:</p>\r\n\r\n<ul>\r\n<li>Statutory Audit</li>\r\n<li>Tax Audit</li>\r\n<li>Direct Taxation</li>\r\n<li>GST Compliance</li>\r\n<li>Internal Audit</li>\r\n<li>ROC Compliance</li>\r\n<li>Advisory Services</li>\r\n</ul>\r\n\r\n<p>The quality and diversity of exposure often determine how much you learn during articleship.</p>\r\n\r\n<h2>4. Not Researching the Firm Properly</h2>\r\n\r\n<p>Many students accept offers without gathering enough information.</p>\r\n\r\n<p>Before joining, research:</p>\r\n\r\n<ul>\r\n<li>The firm\'s service areas</li>\r\n<li>Client portfolio</li>\r\n<li>Industries served</li>\r\n<li>Team size</li>\r\n<li>Work culture</li>\r\n<li>Growth opportunities</li>\r\n</ul>\r\n\r\n<p>A little research can prevent major disappointments later.</p>\r\n\r\n<h2>5. Failing to Speak with Existing Articles</h2>\r\n\r\n<p>Current and former articles often provide the most accurate picture of a firm\'s working environment.</p>\r\n\r\n<p>Many students miss the opportunity to speak with individuals who have firsthand experience.</p>\r\n\r\n<p>Ask existing articles about:</p>\r\n\r\n<ul>\r\n<li>Learning opportunities</li>\r\n<li>Work culture</li>\r\n<li>Mentorship quality</li>\r\n<li>Client interaction</li>\r\n<li>Overall experience</li>\r\n</ul>\r\n\r\n<p>Their insights can help you make a more informed decision.</p>\r\n\r\n<h2>6. Ignoring Mentorship Opportunities</h2>\r\n\r\n<p>Technical work is important, but guidance from experienced professionals can significantly accelerate learning.</p>\r\n\r\n<p>Students should evaluate whether the firm provides:</p>\r\n\r\n<ul>\r\n<li>Partner interaction</li>\r\n<li>Senior guidance</li>\r\n<li>Training sessions</li>\r\n<li>Feedback mechanisms</li>\r\n<li>Learning support</li>\r\n</ul>\r\n\r\n<p>Strong mentorship often makes a huge difference during articleship.</p>\r\n\r\n<h2>7. Not Considering Long-Term Career Goals</h2>\r\n\r\n<p>Your career aspirations should influence your articleship choice.</p>\r\n\r\n<p>For example:</p>\r\n\r\n<ul>\r\n<li>Students interested in practice may benefit from broader exposure.</li>\r\n<li>Students targeting corporate careers may prefer firms with large corporate clients.</li>\r\n<li>Students interested in taxation should seek firms with strong tax practices.</li>\r\n</ul>\r\n\r\n<p>Selecting a firm aligned with your future goals often produces better outcomes.</p>\r\n\r\n<h2>8. Overlooking Client Interaction Opportunities</h2>\r\n\r\n<p>Many students focus entirely on technical work and underestimate the value of client exposure.</p>\r\n\r\n<p>Client interaction helps develop:</p>\r\n\r\n<ul>\r\n<li>Communication skills</li>\r\n<li>Confidence</li>\r\n<li>Professional judgment</li>\r\n<li>Relationship management skills</li>\r\n<li>Business understanding</li>\r\n</ul>\r\n\r\n<p>These skills become extremely valuable after qualification.</p>\r\n\r\n<h2>9. Ignoring Work Culture</h2>\r\n\r\n<p>Work culture can significantly affect your learning experience and overall satisfaction.</p>\r\n\r\n<p>Consider factors such as:</p>\r\n\r\n<ul>\r\n<li>Team environment</li>\r\n<li>Support from seniors</li>\r\n<li>Professional behavior</li>\r\n<li>Learning culture</li>\r\n<li>Collaboration opportunities</li>\r\n</ul>\r\n\r\n<p>A healthy work environment often leads to better growth and motivation.</p>\r\n\r\n<h2>10. Making a Decision Too Quickly</h2>\r\n\r\n<p>Some students accept the first offer they receive without comparing alternatives.</p>\r\n\r\n<p>While securing an articleship quickly may feel reassuring, it is important to evaluate multiple opportunities whenever possible.</p>\r\n\r\n<p>Take time to compare:</p>\r\n\r\n<ul>\r\n<li>Exposure</li>\r\n<li>Mentorship</li>\r\n<li>Client profile</li>\r\n<li>Learning opportunities</li>\r\n<li>Career alignment</li>\r\n</ul>\r\n\r\n<p>A thoughtful decision can significantly improve your articleship experience.</p>\r\n\r\n<h2>11. Focusing Only on Immediate Benefits</h2>\r\n\r\n<p>Many students evaluate firms based on what they will gain in the next few months rather than what they will gain in the next five years.</p>\r\n\r\n<p>Articleship should be viewed as a long-term investment.</p>\r\n\r\n<p>Skills, exposure, and professional development often create far greater value than short-term benefits.</p>\r\n\r\n<h2>How to Make a Better Articleship Decision</h2>\r\n\r\n<p>Before accepting an offer, ask yourself:</p>\r\n\r\n<ul>\r\n<li>Will this firm help me develop practical skills?</li>\r\n<li>Will I gain exposure to multiple domains?</li>\r\n<li>Will I receive mentorship and guidance?</li>\r\n<li>Will I interact with clients?</li>\r\n<li>Does this opportunity align with my career goals?</li>\r\n</ul>\r\n\r\n<p>If the answers are positive, you are likely evaluating the right factors.</p>\r\n\r\n<h2>Final Thoughts</h2>\r\n\r\n<p>Articleship is much more than a mandatory training requirement. It is a crucial phase that shapes your professional identity and prepares you for future opportunities.</p>\r\n\r\n<p>By avoiding common mistakes and focusing on learning, exposure, mentorship, and career alignment, students can make significantly better decisions.</p>\r\n\r\n<p><b>The best articleship choice is not necessarily the most popular, the highest paying, or the biggest firm. It is the opportunity that helps you learn, grow, and become a better Chartered Accountant.</b></p>','blog-images/featured/bQ5h3SQg1xgNKFfcPOWpL19a9bSH1Eai49oSd6DV.png','Common Mistakes CA Students Make While Choosing Articleship','Learn the most common mistakes CA students make while selecting articleship firms and discover how to choose opportunities that support long-term career growth, learning, and professional development.',NULL,'published',4,'2026-06-13 22:11:45','2026-06-13 22:11:45','2026-06-16 01:41:55'),(11,'Stipend vs Exposure: What Matters More in Articleship?','stipend-vs-exposure-what-matters-more-in-articleship','One of the biggest dilemmas CA students face while choosing an articleship is whether to prioritize stipend or exposure. A higher stipend may seem attractive in the short term, but the quality of exposure can influence your skills, confidence, employability, and long-term career growth. This guide explores both perspectives and helps students make a smarter career decision.','<p>One of the most common questions CA students ask while searching for articleship opportunities is:</p>\r\n \r\n <p><b>\"Should I choose a firm that offers a higher stipend or a firm that provides better exposure?\"</b></p>\r\n \r\n <p>It is a fair question. For many students, articleship is their first professional opportunity, and receiving a higher stipend can be financially rewarding. At the same time, articleship is also the most important practical learning phase of the Chartered Accountancy journey.</p>\r\n \r\n <p>The challenge lies in balancing short-term financial benefits with long-term career growth.</p>\r\n \r\n <p>So what should matter more—stipend or exposure?</p>\r\n \r\n <h2>Understanding the Purpose of Articleship</h2>\r\n \r\n <p>Before comparing stipend and exposure, it is important to understand the primary purpose of articleship.</p>\r\n \r\n <p>Articleship is not designed to be a high-paying job. It is a structured training period where students gain practical experience in:</p>\r\n \r\n <ul>\r\n <li>Audit</li>\r\n <li>Taxation</li>\r\n <li>Compliance</li>\r\n <li>Financial Reporting</li>\r\n <li>Client Management</li>\r\n <li>Business Operations</li>\r\n </ul>\r\n \r\n <p>The objective is to transform theoretical knowledge into professional competence.</p>\r\n \r\n <p>When viewed from this perspective, articleship should be considered an investment in your future career rather than simply a source of income.</p>\r\n \r\n <h2>Why Stipend Matters</h2>\r\n \r\n <p>There is nothing wrong with considering stipend while evaluating opportunities.</p>\r\n \r\n <p>A higher stipend can:</p>\r\n \r\n <ul>\r\n <li>Reduce financial pressure</li>\r\n <li>Cover transportation and living expenses</li>\r\n <li>Increase motivation</li>\r\n <li>Provide greater independence</li>\r\n </ul>\r\n \r\n <p>For students living away from home or managing educational expenses, stipend can play an important role.</p>\r\n \r\n <p>However, stipend should rarely be the only deciding factor.</p>\r\n \r\n <h2>The Real Value of Exposure</h2>\r\n \r\n <p>Exposure refers to the practical learning opportunities available during articleship.</p>\r\n \r\n <p>This includes:</p>\r\n \r\n <ul>\r\n <li>Audit assignments</li>\r\n <li>Taxation work</li>\r\n <li>GST compliance</li>\r\n <li>Client interactions</li>\r\n <li>Industry exposure</li>\r\n <li>Financial statement analysis</li>\r\n <li>Business process understanding</li>\r\n </ul>\r\n \r\n <p>These experiences help students develop skills that remain valuable throughout their professional careers.</p>\r\n \r\n <p>Unlike stipend, which benefits you for a limited period, exposure continues generating returns long after articleship is completed.</p>\r\n \r\n <h2>A Simple Example</h2>\r\n \r\n <p>Consider two hypothetical students.</p>\r\n \r\n <p><b>Student A</b> joins a firm offering a higher stipend but spends most of the time performing repetitive tasks with limited learning opportunities.</p>\r\n \r\n <p><b>Student B</b> joins a firm with slightly lower stipend but gains exposure to audits, taxation, client meetings, compliance work, and multiple industries.</p>\r\n \r\n <p>At the end of two years, Student B is likely to possess stronger technical knowledge, greater confidence, and better interview performance.</p>\r\n \r\n <p>In many cases, the additional skills gained through exposure result in significantly higher earning potential after qualification.</p>\r\n \r\n <h2>How Exposure Impacts Future Career Opportunities</h2>\r\n \r\n <p>Recruiters frequently evaluate candidates based on the practical experience they gained during articleship.</p>\r\n \r\n <p>Common interview questions include:</p>\r\n \r\n <ul>\r\n <li>What type of audits have you worked on?</li>\r\n <li>Which industries have you handled?</li>\r\n <li>What taxation assignments have you completed?</li>\r\n <li>Have you interacted with clients?</li>\r\n <li>What challenges have you solved?</li>\r\n </ul>\r\n \r\n <p>Students with diverse exposure often have stronger answers and greater confidence during interviews.</p>\r\n \r\n <p>This can lead to better job opportunities and faster career progression.</p>\r\n \r\n <h2>When a Higher Stipend Can Be Misleading</h2>\r\n \r\n <p>Many students assume that a higher stipend automatically indicates a better firm.</p>\r\n \r\n <p>This is not always true.</p>\r\n \r\n <p>Some firms may offer attractive stipends but provide limited exposure, repetitive assignments, or restricted learning opportunities.</p>\r\n \r\n <p>On the other hand, some firms offering modest stipends may provide exceptional practical training and mentorship.</p>\r\n \r\n <p>Students should evaluate the complete opportunity rather than focusing only on compensation.</p>\r\n \r\n <h2>Questions to Ask Before Making a Decision</h2>\r\n \r\n <p>Before accepting an articleship offer, students should ask:</p>\r\n \r\n <ul>\r\n <li>What domains will I work in?</li>\r\n <li>How much audit exposure will I receive?</li>\r\n <li>Will I gain taxation experience?</li>\r\n <li>Will I interact with clients?</li>\r\n <li>What industries does the firm serve?</li>\r\n <li>How much responsibility will I receive?</li>\r\n <li>Is there a mentorship or training structure?</li>\r\n </ul>\r\n \r\n <p>The answers to these questions often reveal the true value of the opportunity.</p>\r\n \r\n <h2>Can You Have Both?</h2>\r\n \r\n <p>Absolutely.</p>\r\n \r\n <p>Some firms successfully provide both competitive stipends and strong learning opportunities.</p>\r\n \r\n <p>Whenever possible, students should look for opportunities that offer:</p>\r\n \r\n <ul>\r\n <li>Meaningful exposure</li>\r\n <li>Good mentorship</li>\r\n <li>Professional growth</li>\r\n <li>Reasonable compensation</li>\r\n </ul>\r\n \r\n <p>However, if you must choose between slightly higher stipend and significantly better exposure, exposure is often the smarter long-term investment.</p>\r\n \r\n <h2>Think Beyond Two Years</h2>\r\n \r\n <p>One useful exercise is to ask yourself:</p>\r\n \r\n <p><b>\"Which opportunity will make me a stronger Chartered Accountant after two years?\"</b></p>\r\n \r\n <p>The answer often provides clarity.</p>\r\n \r\n <p>Articleship lasts for a limited period, but the skills and experience gained during that time can influence your career for decades.</p>\r\n \r\n <h2>Final Thoughts</h2>\r\n \r\n <p>Stipend is important, and students should not ignore financial considerations. However, articleship should primarily be evaluated based on the quality of learning and exposure it provides.</p>\r\n \r\n <p>The purpose of articleship is to build professional capability, not maximize short-term earnings.</p>\r\n \r\n <p>Students who prioritize exposure often develop stronger technical skills, broader business understanding, better communication abilities, and greater confidence.</p>\r\n \r\n <p>These advantages frequently translate into better opportunities and higher earning potential after qualification.</p>\r\n \r\n <p><b>When choosing between stipend and exposure, remember that stipend pays you for two years—but exposure can reward you for an entire career.</b></p><p>One of the most common questions CA students ask while searching for articleship opportunities is:</p>\r\n \r\n <p><b>\"Should I choose a firm that offers a higher stipend or a firm that provides better exposure?\"</b></p>\r\n \r\n <p>It is a fair question. For many students, articleship is their first professional opportunity, and receiving a higher stipend can be financially rewarding. At the same time, articleship is also the most important practical learning phase of the Chartered Accountancy journey.</p>\r\n \r\n <p>The challenge lies in balancing short-term financial benefits with long-term career growth.</p>\r\n \r\n <p>So what should matter more—stipend or exposure?</p>\r\n \r\n <h2>Understanding the Purpose of Articleship</h2>\r\n \r\n <p>Before comparing stipend and exposure, it is important to understand the primary purpose of articleship.</p>\r\n \r\n <p>Articleship is not designed to be a high-paying job. It is a structured training period where students gain practical experience in:</p>\r\n \r\n <ul>\r\n <li>Audit</li>\r\n <li>Taxation</li>\r\n <li>Compliance</li>\r\n <li>Financial Reporting</li>\r\n <li>Client Management</li>\r\n <li>Business Operations</li>\r\n </ul>\r\n \r\n <p>The objective is to transform theoretical knowledge into professional competence.</p>\r\n \r\n <p>When viewed from this perspective, articleship should be considered an investment in your future career rather than simply a source of income.</p>\r\n \r\n <h2>Why Stipend Matters</h2>\r\n \r\n <p>There is nothing wrong with considering stipend while evaluating opportunities.</p>\r\n \r\n <p>A higher stipend can:</p>\r\n \r\n <ul>\r\n <li>Reduce financial pressure</li>\r\n <li>Cover transportation and living expenses</li>\r\n <li>Increase motivation</li>\r\n <li>Provide greater independence</li>\r\n </ul>\r\n \r\n <p>For students living away from home or managing educational expenses, stipend can play an important role.</p>\r\n \r\n <p>However, stipend should rarely be the only deciding factor.</p>\r\n \r\n <h2>The Real Value of Exposure</h2>\r\n \r\n <p>Exposure refers to the practical learning opportunities available during articleship.</p>\r\n \r\n <p>This includes:</p>\r\n \r\n <ul>\r\n <li>Audit assignments</li>\r\n <li>Taxation work</li>\r\n <li>GST compliance</li>\r\n <li>Client interactions</li>\r\n <li>Industry exposure</li>\r\n <li>Financial statement analysis</li>\r\n <li>Business process understanding</li>\r\n </ul>\r\n \r\n <p>These experiences help students develop skills that remain valuable throughout their professional careers.</p>\r\n \r\n <p>Unlike stipend, which benefits you for a limited period, exposure continues generating returns long after articleship is completed.</p>\r\n \r\n <h2>A Simple Example</h2>\r\n \r\n <p>Consider two hypothetical students.</p>\r\n \r\n <p><b>Student A</b> joins a firm offering a higher stipend but spends most of the time performing repetitive tasks with limited learning opportunities.</p>\r\n \r\n <p><b>Student B</b> joins a firm with slightly lower stipend but gains exposure to audits, taxation, client meetings, compliance work, and multiple industries.</p>\r\n \r\n <p>At the end of two years, Student B is likely to possess stronger technical knowledge, greater confidence, and better interview performance.</p>\r\n \r\n <p>In many cases, the additional skills gained through exposure result in significantly higher earning potential after qualification.</p>\r\n \r\n <h2>How Exposure Impacts Future Career Opportunities</h2>\r\n \r\n <p>Recruiters frequently evaluate candidates based on the practical experience they gained during articleship.</p>\r\n \r\n <p>Common interview questions include:</p>\r\n \r\n <ul>\r\n <li>What type of audits have you worked on?</li>\r\n <li>Which industries have you handled?</li>\r\n <li>What taxation assignments have you completed?</li>\r\n <li>Have you interacted with clients?</li>\r\n <li>What challenges have you solved?</li>\r\n </ul>\r\n \r\n <p>Students with diverse exposure often have stronger answers and greater confidence during interviews.</p>\r\n \r\n <p>This can lead to better job opportunities and faster career progression.</p>\r\n \r\n <h2>When a Higher Stipend Can Be Misleading</h2>\r\n \r\n <p>Many students assume that a higher stipend automatically indicates a better firm.</p>\r\n \r\n <p>This is not always true.</p>\r\n \r\n <p>Some firms may offer attractive stipends but provide limited exposure, repetitive assignments, or restricted learning opportunities.</p>\r\n \r\n <p>On the other hand, some firms offering modest stipends may provide exceptional practical training and mentorship.</p>\r\n \r\n <p>Students should evaluate the complete opportunity rather than focusing only on compensation.</p>\r\n \r\n <h2>Questions to Ask Before Making a Decision</h2>\r\n \r\n <p>Before accepting an articleship offer, students should ask:</p>\r\n \r\n <ul>\r\n <li>What domains will I work in?</li>\r\n <li>How much audit exposure will I receive?</li>\r\n <li>Will I gain taxation experience?</li>\r\n <li>Will I interact with clients?</li>\r\n <li>What industries does the firm serve?</li>\r\n <li>How much responsibility will I receive?</li>\r\n <li>Is there a mentorship or training structure?</li>\r\n </ul>\r\n \r\n <p>The answers to these questions often reveal the true value of the opportunity.</p>\r\n \r\n <h2>Can You Have Both?</h2>\r\n \r\n <p>Absolutely.</p>\r\n \r\n <p>Some firms successfully provide both competitive stipends and strong learning opportunities.</p>\r\n \r\n <p>Whenever possible, students should look for opportunities that offer:</p>\r\n \r\n <ul>\r\n <li>Meaningful exposure</li>\r\n <li>Good mentorship</li>\r\n <li>Professional growth</li>\r\n <li>Reasonable compensation</li>\r\n </ul>\r\n \r\n <p>However, if you must choose between slightly higher stipend and significantly better exposure, exposure is often the smarter long-term investment.</p>\r\n \r\n <h2>Think Beyond Two Years</h2>\r\n \r\n <p>One useful exercise is to ask yourself:</p>\r\n \r\n <p><b>\"Which opportunity will make me a stronger Chartered Accountant after two years?\"</b></p>\r\n \r\n <p>The answer often provides clarity.</p>\r\n \r\n <p>Articleship lasts for a limited period, but the skills and experience gained during that time can influence your career for decades.</p>\r\n \r\n <h2>Final Thoughts</h2>\r\n \r\n <p>Stipend is important, and students should not ignore financial considerations. However, articleship should primarily be evaluated based on the quality of learning and exposure it provides.</p>\r\n \r\n <p>The purpose of articleship is to build professional capability, not maximize short-term earnings.</p>\r\n \r\n <p>Students who prioritize exposure often develop stronger technical skills, broader business understanding, better communication abilities, and greater confidence.</p>\r\n \r\n <p>These advantages frequently translate into better opportunities and higher earning potential after qualification.</p>\r\n \r\n <p><b>When choosing between stipend and exposure, remember that stipend pays you for two years—but exposure can reward you for an entire career.</b></p>','blog-images/featured/MB80KcQWg0WK1koOzRgbqVpvGua6TPFojeiAW6s8.png','Stipend vs Exposure: What Matters More in Articleship?','Confused between a higher stipend and better exposure during articleship? Learn how stipend and practical experience impact your CA career and discover what should matter most when choosing a firm.',NULL,'published',1,'2026-06-17 23:58:21','2026-06-17 23:58:21','2026-06-17 23:58:51'),(12,'Articleship Roadmap: What to Learn in Your 2 Years of Training','articleship-roadmap-what-to-learn-in-your-2-years-of-training','Articleship is where CA students build practical skills that shape their careers. This complete 2-year roadmap covers everything you should learn during Articleship—from audit and taxation to compliance, Excel, and professional communication.','<h1>Articleship Roadmap: What to Learn in Your 2 Years of Training</h1>\r\n\r\n<p>Articleship is one of the most important phases in a CA student’s journey.</p>\r\n\r\n<p>It is the stage where theory meets practical exposure. During these 2 years, you gain real-world experience in audit, taxation, compliance, and client handling—skills that define your future career.</p>\r\n\r\n<p>However, many students start Articleship without a clear learning roadmap.</p>\r\n\r\n<p>They focus only on completing office work rather than developing meaningful professional skills.</p>\r\n\r\n<p>The right approach is simple:</p>\r\n\r\n<p><strong>Use your Articleship as a learning opportunity, not just as mandatory training.</strong></p>\r\n\r\n<p>This roadmap will help you understand what skills and knowledge you should focus on during your Articleship.</p>\r\n\r\n<h2>Why Articleship Matters So Much</h2>\r\n\r\n<p>Your Articleship experience impacts:</p>\r\n\r\n<ul>\r\n  <li>Practical knowledge</li>\r\n  <li>Technical expertise</li>\r\n  <li>Professional confidence</li>\r\n  <li>Future job opportunities</li>\r\n  <li>Career specialization</li>\r\n</ul>\r\n\r\n<p>A strong Articleship builds the foundation for long-term success as a Chartered Accountant.</p>\r\n\r\n<h2>Year 1: Build Strong Fundamentals</h2>\r\n\r\n<p>The first year is about understanding how real-world finance and compliance work.</p>\r\n\r\n<p>Your focus should be on learning basics thoroughly.</p>\r\n\r\n<h2>1. Audit Fundamentals</h2>\r\n\r\n<p>Audit is one of the most critical areas in Articleship.</p>\r\n\r\n<p>Learn:</p>\r\n\r\n<ul>\r\n  <li>Audit documentation</li>\r\n  <li>Vouching</li>\r\n  <li>Verification</li>\r\n  <li>Ledger scrutiny</li>\r\n  <li>Working papers</li>\r\n  <li>Internal controls</li>\r\n</ul>\r\n\r\n<p>Understand why each audit procedure matters.</p>\r\n\r\n<h2>2. Taxation Basics</h2>\r\n\r\n<p>Tax knowledge is essential for every CA student.</p>\r\n\r\n<p>Focus on:</p>\r\n\r\n<h3>Direct Tax</h3>\r\n<ul>\r\n  <li>Income Tax Return filing</li>\r\n  <li>TDS basics</li>\r\n  <li>Tax computation</li>\r\n  <li>Tax notices</li>\r\n</ul>\r\n\r\n<h3>Indirect Tax</h3>\r\n<ul>\r\n  <li>GST returns</li>\r\n  <li>GST registration</li>\r\n  <li>Input Tax Credit</li>\r\n  <li>GST compliance</li>\r\n</ul>\r\n\r\n<h2>3. ROC & Compliance Work</h2>\r\n\r\n<p>Learn corporate compliance work such as:</p>\r\n\r\n<ul>\r\n  <li>Company incorporation</li>\r\n  <li>Annual filings</li>\r\n  <li>ROC forms</li>\r\n  <li>MCA portal handling</li>\r\n  <li>Basic legal documentation</li>\r\n</ul>\r\n\r\n<h2>4. Excel Mastery</h2>\r\n\r\n<p>Excel is a must-have skill.</p>\r\n\r\n<p>Learn:</p>\r\n\r\n<ul>\r\n  <li>Pivot Tables</li>\r\n  <li>VLOOKUP / XLOOKUP</li>\r\n  <li>Data cleaning</li>\r\n  <li>Basic formulas</li>\r\n  <li>Financial analysis</li>\r\n</ul>\r\n\r\n<h2>5. Client Communication</h2>\r\n\r\n<p>Technical skills alone are not enough.</p>\r\n\r\n<ul>\r\n  <li>Professional communication</li>\r\n  <li>Email writing</li>\r\n  <li>Client interaction</li>\r\n  <li>Asking better questions</li>\r\n  <li>Presenting findings clearly</li>\r\n</ul>\r\n\r\n<h2>Year 2: Build Advanced Skills</h2>\r\n\r\n<p>Once fundamentals are strong, focus on advanced learning.</p>\r\n\r\n<p>Year 2 should prepare you for real career opportunities.</p>\r\n\r\n<h2>6. Advanced Audit Exposure</h2>\r\n\r\n<ul>\r\n  <li>Statutory Audit</li>\r\n  <li>Tax Audit</li>\r\n  <li>Internal Audit</li>\r\n  <li>Bank Audit</li>\r\n</ul>\r\n\r\n<h2>7. Financial Analysis & Reporting</h2>\r\n\r\n<p>Understand:</p>\r\n\r\n<ul>\r\n  <li>Financial statements</li>\r\n  <li>Ratio analysis</li>\r\n  <li>Profitability analysis</li>\r\n  <li>Cash flow analysis</li>\r\n  <li>Budgeting basics</li>\r\n</ul>\r\n\r\n<h2>8. Problem Solving</h2>\r\n\r\n<p>Build:</p>\r\n\r\n<ul>\r\n  <li>Analytical thinking</li>\r\n  <li>Problem-solving approach</li>\r\n  <li>Risk identification mindset</li>\r\n</ul>\r\n\r\n<h2>9. Industry Knowledge</h2>\r\n\r\n<p>Learn about industries such as:</p>\r\n\r\n<ul>\r\n  <li>Manufacturing</li>\r\n  <li>Banking</li>\r\n  <li>Startups</li>\r\n  <li>E-commerce</li>\r\n  <li>Consulting</li>\r\n</ul>\r\n\r\n<h2>10. Career Planning</h2>\r\n\r\n<p>By the second year, start planning your next step.</p>\r\n\r\n<ul>\r\n  <li>Practice or Job?</li>\r\n  <li>Audit or Tax?</li>\r\n  <li>Industry or Consulting?</li>\r\n  <li>Big 4 or Mid-Sized Firm?</li>\r\n</ul>\r\n\r\n<h2>Common Mistakes Students Make During Articleship</h2>\r\n\r\n<ul>\r\n  <li>Working without learning</li>\r\n  <li>Not asking questions</li>\r\n  <li>Ignoring communication skills</li>\r\n  <li>Avoiding difficult assignments</li>\r\n  <li>Focusing only on stipend</li>\r\n</ul>\r\n\r\n<h2>Final Thoughts</h2>\r\n\r\n<p>Your 2 years of Articleship can shape your entire career.</p>\r\n\r\n<p>The goal should not be just completing training.</p>\r\n\r\n<p>The goal should be becoming a capable professional.</p>\r\n\r\n<p>Focus on audit, taxation, compliance, Excel, communication, and problem-solving.</p>\r\n\r\n<p>Every assignment is a chance to learn.</p>\r\n\r\n<p><strong>Make your Articleship count.</strong></p>','blog-images/featured/6ZpEojHq0scpzhV28ruxyuN6P5WDoD9uhENqLWA3.webp','Articleship Roadmap for CA Students: What to Learn in 2 Years of Training','Confused about what to learn during CA Articleship? Explore a complete 2-year Articleship roadmap covering audit, taxation, GST, compliance, and essential professional skills.','🚀 Articleship is more than mandatory training—it’s your foundation for a successful CA career.\r\n\r\nBut are you learning the right things?\r\n\r\nIn our latest blog, we cover a complete 2-year Articleship roadmap for CA students.\r\n\r\nLearn:\r\n✅ Audit Fundamentals\r\n✅ Taxation Skills\r\n✅ GST & Compliance\r\n✅ Excel & Financial Analysis\r\n✅ Communication & Career Planning\r\n\r\nMake your Articleship count.\r\n\r\n#CAStudents #Articleship #CharteredAccountancy #CAJourney #CareerGrowth #StartYourStory','published',4,'2026-06-20 21:46:38','2026-06-20 21:46:38','2026-06-20 21:46:38');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_submissions`
--

LOCK TABLES `contact_submissions` WRITE;
/*!40000 ALTER TABLE `contact_submissions` DISABLE KEYS */;
INSERT INTO `contact_submissions` VALUES (1,'Tushar Bhise','tusharbhise908@gmail.com','Feedback','Test feedback','106.215.178.46','2026-06-18 00:21:18','2026-06-18 00:21:18');
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
  `click_count` int NOT NULL DEFAULT '0',
  `clicked_at` timestamp NULL DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email_purpose_status` (`email_purpose`,`status`),
  KEY `idx_recipient_created` (`recipient_email`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=229 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_logs`
--

LOCK TABLES `email_logs` WRITE;
/*!40000 ALTER TABLE `email_logs` DISABLE KEYS */;
INSERT INTO `email_logs` VALUES (1,'mardariddhi04@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:55:21',0,NULL,'2026-06-20 19:55:19','2026-06-20 19:55:21'),(2,'snehahake9@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:55:23',0,NULL,'2026-06-20 19:55:22','2026-06-20 19:55:23'),(3,'mardariddhi04@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:56:16',0,NULL,'2026-06-20 19:56:14','2026-06-20 19:56:16'),(4,'snehahake9@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:56:17',0,NULL,'2026-06-20 19:56:17','2026-06-20 19:56:17'),(5,'mayurikokil2510@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:56:19',0,NULL,'2026-06-20 19:56:18','2026-06-20 19:56:19'),(6,'shraddhagharke16@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:56:21',0,NULL,'2026-06-20 19:56:20','2026-06-20 19:56:21'),(7,'bhumigolani1@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:56:35',0,NULL,'2026-06-20 19:56:22','2026-06-20 19:56:35'),(8,'rutujamundada96@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:56:37',0,NULL,'2026-06-20 19:56:36','2026-06-20 19:56:37'),(9,'shraddhakoli1804@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:56:38',0,NULL,'2026-06-20 19:56:38','2026-06-20 19:56:38'),(10,'ymutha424@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:56:40',0,NULL,'2026-06-20 19:56:39','2026-06-20 19:56:40'),(11,'anumitasingh0511@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:56:42',0,NULL,'2026-06-20 19:56:41','2026-06-20 19:56:42'),(12,'rashiagrawal0122@gmail.com','student','reengagement','ReEngagementMail','marketing','Verify your email to start applying — Start Your Story','sent',NULL,'2026-06-20 19:56:44',0,NULL,'2026-06-20 19:56:43','2026-06-20 19:56:44'),(13,'rajmantri6@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:56:45',0,NULL,'2026-06-20 19:56:45','2026-06-20 19:56:45'),(14,'adityagramesh2004@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:56:47',0,NULL,'2026-06-20 19:56:46','2026-06-20 19:56:47'),(15,'ak.anshamalpani13@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your creator profile to get discovered — Start Your Story','sent',NULL,'2026-06-20 19:56:49',0,NULL,'2026-06-20 19:56:48','2026-06-20 19:56:49'),(16,'prajwalalhat7728@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:56:51',0,NULL,'2026-06-20 19:56:50','2026-06-20 19:56:51'),(17,'dsangle659@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:56:52',0,NULL,'2026-06-20 19:56:52','2026-06-20 19:56:52'),(18,'ishadhoka2000@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:56:54',0,NULL,'2026-06-20 19:56:53','2026-06-20 19:56:54'),(19,'rekhani.saraogi@gmail.com','firm','reengagement','ReEngagementMail','marketing','Complete your firm profile and start hiring — Start Your Story','sent',NULL,'2026-06-20 19:56:56',0,NULL,'2026-06-20 19:56:55','2026-06-20 19:56:56'),(20,'sumitsawant2311@gmail.com','student','reengagement','ReEngagementMail','marketing','Verify your email to start applying — Start Your Story','sent',NULL,'2026-06-20 19:56:58',0,NULL,'2026-06-20 19:56:57','2026-06-20 19:56:58'),(21,'minakshikusha@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:57:00',0,NULL,'2026-06-20 19:56:59','2026-06-20 19:57:00'),(22,'kakdesuraj383@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:57:01',0,NULL,'2026-06-20 19:57:01','2026-06-20 19:57:01'),(23,'gajupawar775@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:57:09',0,NULL,'2026-06-20 19:57:02','2026-06-20 19:57:09'),(24,'mantripb77@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:57:11',0,NULL,'2026-06-20 19:57:10','2026-06-20 19:57:11'),(25,'akashpund2003@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your creator profile to get discovered — Start Your Story','sent',NULL,'2026-06-20 19:57:12',0,NULL,'2026-06-20 19:57:12','2026-06-20 19:57:12'),(26,'pratishbansode07@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:57:14',0,NULL,'2026-06-20 19:57:13','2026-06-20 19:57:14'),(27,'surajingleprofessional007@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:57:16',0,NULL,'2026-06-20 19:57:15','2026-06-20 19:57:16'),(28,'atharvpatil4328@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your creator profile to get discovered — Start Your Story','sent',NULL,'2026-06-20 19:57:18',0,NULL,'2026-06-20 19:57:17','2026-06-20 19:57:18'),(29,'prajaktashitole01@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:57:19',0,NULL,'2026-06-20 19:57:19','2026-06-20 19:57:19'),(30,'kartikajain29@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your creator profile to get discovered — Start Your Story','sent',NULL,'2026-06-20 19:57:21',0,NULL,'2026-06-20 19:57:20','2026-06-20 19:57:21'),(31,'casunilshenoy@gmail.com','firm','reengagement','ReEngagementMail','marketing','Complete your firm profile and start hiring — Start Your Story','sent',NULL,'2026-06-20 19:57:23',0,NULL,'2026-06-20 19:57:22','2026-06-20 19:57:23'),(32,'anushka.shinde.1217@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:57:25',0,NULL,'2026-06-20 19:57:24','2026-06-20 19:57:25'),(33,'garvitabansal7@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your creator profile to get discovered — Start Your Story','sent',NULL,'2026-06-20 19:57:26',0,NULL,'2026-06-20 19:57:26','2026-06-20 19:57:26'),(34,'varunmulay9@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your creator profile to get discovered — Start Your Story','sent',NULL,'2026-06-20 19:57:28',0,NULL,'2026-06-20 19:57:27','2026-06-20 19:57:28'),(35,'akanshaagrawal0918@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:57:30',0,NULL,'2026-06-20 19:57:29','2026-06-20 19:57:30'),(36,'piyushagrawal4833@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:57:35',0,NULL,'2026-06-20 19:57:31','2026-06-20 19:57:35'),(37,'hr@prachay.com','firm','reengagement','ReEngagementMail','marketing','Complete your firm profile and start hiring — Start Your Story','sent',NULL,'2026-06-20 19:57:38',0,NULL,'2026-06-20 19:57:36','2026-06-20 19:57:38'),(38,'kukrejamekii2703@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your creator profile to get discovered — Start Your Story','sent',NULL,'2026-06-20 19:57:40',0,NULL,'2026-06-20 19:57:39','2026-06-20 19:57:40'),(39,'carsbiyani@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your creator profile to get discovered — Start Your Story','sent',NULL,'2026-06-20 19:57:42',0,NULL,'2026-06-20 19:57:41','2026-06-20 19:57:42'),(40,'nitishbadak14@gmail.com','student','reengagement','ReEngagementMail','marketing','Verify your email to get discovered — Start Your Story','sent',NULL,'2026-06-20 19:57:45',0,NULL,'2026-06-20 19:57:43','2026-06-20 19:57:45'),(41,'cacspriya@tnlac.in','firm','reengagement','ReEngagementMail','marketing','Complete your firm profile and start hiring — Start Your Story','sent',NULL,'2026-06-20 19:57:47',0,NULL,'2026-06-20 19:57:46','2026-06-20 19:57:47'),(42,'mohitbalrampatil@gmail.com','student','reengagement','ReEngagementMail','marketing','Verify your email to start applying — Start Your Story','sent',NULL,'2026-06-20 19:57:49',0,NULL,'2026-06-20 19:57:48','2026-06-20 19:57:49'),(43,'adepsharwari8@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:57:50',0,NULL,'2026-06-20 19:57:50','2026-06-20 19:57:50'),(44,'wemay34217@afterdo.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:57:52',0,NULL,'2026-06-20 19:57:51','2026-06-20 19:57:52'),(45,'cakiranbafna@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your creator profile to get discovered — Start Your Story','sent',NULL,'2026-06-20 19:57:54',0,NULL,'2026-06-20 19:57:53','2026-06-20 19:57:54'),(46,'raj.varma2004@gmail.com','firm','reengagement','ReEngagementMail','marketing','Complete your firm profile and start hiring — Start Your Story','sent',NULL,'2026-06-20 19:57:56',0,NULL,'2026-06-20 19:57:55','2026-06-20 19:57:56'),(47,'hr@rajendraco.com','firm','reengagement','ReEngagementMail','marketing','Verify your email to start hiring — Start Your Story','sent',NULL,'2026-06-20 19:57:57',0,NULL,'2026-06-20 19:57:57','2026-06-20 19:57:57'),(48,'patil431717@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:57:59',0,NULL,'2026-06-20 19:57:58','2026-06-20 19:57:59'),(49,'rahulc1403@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:58:01',0,NULL,'2026-06-20 19:58:00','2026-06-20 19:58:01'),(50,'mrunmayimulay124421@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-20 19:58:04',0,NULL,'2026-06-20 19:58:02','2026-06-20 19:58:04'),(51,'hr@skpn.in','firm','reengagement','ReEngagementMail','marketing','Verify your email to start hiring — Start Your Story','sent',NULL,'2026-06-20 19:58:05',0,NULL,'2026-06-20 19:58:05','2026-06-20 19:58:05'),(52,'ca.sunil.wadhwani@gmail.com','firm','firm_approved','FirmApprovedMail','default','Your Firm Account Has Been Approved — Start Your Story','sent',NULL,'2026-06-20 23:23:09',0,NULL,'2026-06-20 23:23:06','2026-06-20 23:23:09'),(53,'mr.shindekunal99@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-21 14:49:52',0,NULL,'2026-06-21 14:49:51','2026-06-21 14:49:52'),(54,'mr.shindekunal99@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-21 14:50:08',0,NULL,'2026-06-21 14:50:07','2026-06-21 14:50:08'),(55,'mr.shindekunal99@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-21 14:52:26',0,NULL,'2026-06-21 14:50:24','2026-06-21 14:52:26'),(56,'hirodkarv@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-21 15:44:41',0,NULL,'2026-06-21 15:44:40','2026-06-21 15:44:41'),(57,'hirodkarv@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-21 15:51:51',0,NULL,'2026-06-21 15:49:49','2026-06-21 15:51:51'),(58,'brmass.ca@gmail.com','firm','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-21 16:02:44',0,NULL,'2026-06-21 16:02:43','2026-06-21 16:02:44'),(59,'sonal789987456@gmail.com','firm','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-21 20:09:05',0,NULL,'2026-06-21 20:09:03','2026-06-21 20:09:05'),(60,'test_phonepay@gmail.com','firm','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-21 21:35:35',0,NULL,'2026-06-21 21:35:33','2026-06-21 21:35:35'),(61,'test_phonepay@gmail.com','firm','firm_approved','FirmApprovedMail','default','Your Firm Account Has Been Approved — Start Your Story','sent',NULL,'2026-06-21 21:37:26',0,NULL,'2026-06-21 21:37:24','2026-06-21 21:37:26'),(62,'hr@vsap.co.in','firm','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-22 12:24:32',0,NULL,'2026-06-22 12:24:32','2026-06-22 12:24:32'),(63,'hr@vsap.co.in','firm','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-22 12:27:39',0,NULL,'2026-06-22 12:25:37','2026-06-22 12:27:39'),(64,'sonal789987456@gmail.com','firm','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-22 13:33:40',0,NULL,'2026-06-22 13:33:37','2026-06-22 13:33:40'),(65,'sonal789987456@gmail.com','firm','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-22 14:35:25',0,NULL,'2026-06-22 14:33:21','2026-06-22 14:35:25'),(66,'hr@vsap.co.in','firm','firm_approved','FirmApprovedMail','default','Your Firm Account Has Been Approved — Start Your Story','sent',NULL,'2026-06-22 19:48:49',0,NULL,'2026-06-22 19:48:44','2026-06-22 19:48:49'),(67,'rohit01mantri@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-22 20:28:41',0,NULL,'2026-06-22 20:28:39','2026-06-22 20:28:41'),(68,'mardariddhi04@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:29:47',0,NULL,'2026-06-22 21:29:45','2026-06-22 21:29:47'),(69,'snehahake9@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:29:49',0,NULL,'2026-06-22 21:29:48','2026-06-22 21:29:49'),(70,'mayurikokil2510@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:29:51',0,NULL,'2026-06-22 21:29:50','2026-06-22 21:29:51'),(71,'shraddhagharke16@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:29:52',0,NULL,'2026-06-22 21:29:52','2026-06-22 21:29:52'),(72,'bhumigolani1@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:29:54',0,NULL,'2026-06-22 21:29:53','2026-06-22 21:29:54'),(73,'rutujamundada96@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:29:56',0,NULL,'2026-06-22 21:29:55','2026-06-22 21:29:56'),(74,'shraddhakoli1804@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:29:58',0,NULL,'2026-06-22 21:29:57','2026-06-22 21:29:58'),(75,'ymutha424@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:30:00',0,NULL,'2026-06-22 21:29:59','2026-06-22 21:30:00'),(76,'anumitasingh0511@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:30:01',0,NULL,'2026-06-22 21:30:01','2026-06-22 21:30:01'),(77,'rashiagrawal0122@gmail.com','student','reengagement','ReEngagementMail','marketing','Verify your email to start applying — Start Your Story','sent',NULL,'2026-06-22 21:30:03',0,NULL,'2026-06-22 21:30:02','2026-06-22 21:30:03'),(78,'rajmantri6@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:30:05',0,NULL,'2026-06-22 21:30:04','2026-06-22 21:30:05'),(79,'adityagramesh2004@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:30:07',0,NULL,'2026-06-22 21:30:06','2026-06-22 21:30:07'),(80,'ak.anshamalpani13@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your creator profile to get discovered — Start Your Story','sent',NULL,'2026-06-22 21:30:09',0,NULL,'2026-06-22 21:30:08','2026-06-22 21:30:09'),(81,'prajwalalhat7728@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:30:10',0,NULL,'2026-06-22 21:30:10','2026-06-22 21:30:10'),(82,'dsangle659@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:30:12',0,NULL,'2026-06-22 21:30:11','2026-06-22 21:30:12'),(83,'ishadhoka2000@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:30:14',0,NULL,'2026-06-22 21:30:13','2026-06-22 21:30:14'),(84,'rekhani.saraogi@gmail.com','firm','reengagement','ReEngagementMail','marketing','Complete your firm profile and start hiring — Start Your Story','sent',NULL,'2026-06-22 21:30:18',0,NULL,'2026-06-22 21:30:15','2026-06-22 21:30:18'),(85,'sumitsawant2311@gmail.com','student','reengagement','ReEngagementMail','marketing','Verify your email to start applying — Start Your Story','sent',NULL,'2026-06-22 21:30:20',0,NULL,'2026-06-22 21:30:19','2026-06-22 21:30:20'),(86,'minakshikusha@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:30:22',0,NULL,'2026-06-22 21:30:21','2026-06-22 21:30:22'),(87,'kakdesuraj383@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:30:24',0,NULL,'2026-06-22 21:30:23','2026-06-22 21:30:24'),(88,'gajupawar775@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:30:25',0,NULL,'2026-06-22 21:30:25','2026-06-22 21:30:25'),(89,'mantripb77@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:30:27',0,NULL,'2026-06-22 21:30:26','2026-06-22 21:30:27'),(90,'pratishbansode07@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:30:29',0,NULL,'2026-06-22 21:30:28','2026-06-22 21:30:29'),(91,'surajingleprofessional007@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:30:31',0,NULL,'2026-06-22 21:30:30','2026-06-22 21:30:31'),(92,'atharvpatil4328@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your creator profile to get discovered — Start Your Story','sent',NULL,'2026-06-22 21:30:33',0,NULL,'2026-06-22 21:30:32','2026-06-22 21:30:33'),(93,'prajaktashitole01@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:30:35',0,NULL,'2026-06-22 21:30:34','2026-06-22 21:30:35'),(94,'kartikajain29@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your creator profile to get discovered — Start Your Story','sent',NULL,'2026-06-22 21:30:37',0,NULL,'2026-06-22 21:30:36','2026-06-22 21:30:37'),(95,'casunilshenoy@gmail.com','firm','reengagement','ReEngagementMail','marketing','Complete your firm profile and start hiring — Start Your Story','sent',NULL,'2026-06-22 21:30:50',0,NULL,'2026-06-22 21:30:38','2026-06-22 21:30:50'),(96,'garvitabansal7@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your creator profile to get discovered — Start Your Story','sent',NULL,'2026-06-22 21:30:52',0,NULL,'2026-06-22 21:30:51','2026-06-22 21:30:52'),(97,'varunmulay9@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your creator profile to get discovered — Start Your Story','sent',NULL,'2026-06-22 21:30:54',0,NULL,'2026-06-22 21:30:53','2026-06-22 21:30:54'),(98,'akanshaagrawal0918@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:30:55',0,NULL,'2026-06-22 21:30:55','2026-06-22 21:30:55'),(99,'piyushagrawal4833@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:30:57',0,NULL,'2026-06-22 21:30:56','2026-06-22 21:30:57'),(100,'hr@prachay.com','firm','reengagement','ReEngagementMail','marketing','Complete your firm profile and start hiring — Start Your Story','sent',NULL,'2026-06-22 21:30:59',0,NULL,'2026-06-22 21:30:58','2026-06-22 21:30:59'),(101,'kukrejamekii2703@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your creator profile to get discovered — Start Your Story','sent',NULL,'2026-06-22 21:31:01',0,NULL,'2026-06-22 21:31:00','2026-06-22 21:31:01'),(102,'carsbiyani@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your creator profile to get discovered — Start Your Story','sent',NULL,'2026-06-22 21:31:03',0,NULL,'2026-06-22 21:31:02','2026-06-22 21:31:03'),(103,'nitishbadak14@gmail.com','student','reengagement','ReEngagementMail','marketing','Verify your email to get discovered — Start Your Story','sent',NULL,'2026-06-22 21:31:05',0,NULL,'2026-06-22 21:31:04','2026-06-22 21:31:05'),(104,'cacspriya@tnlac.in','firm','reengagement','ReEngagementMail','marketing','Complete your firm profile and start hiring — Start Your Story','sent',NULL,'2026-06-22 21:31:07',0,NULL,'2026-06-22 21:31:06','2026-06-22 21:31:07'),(105,'mohitbalrampatil@gmail.com','student','reengagement','ReEngagementMail','marketing','Verify your email to start applying — Start Your Story','sent',NULL,'2026-06-22 21:31:08',0,NULL,'2026-06-22 21:31:08','2026-06-22 21:31:08'),(106,'adepsharwari8@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:31:10',0,NULL,'2026-06-22 21:31:09','2026-06-22 21:31:10'),(107,'wemay34217@afterdo.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:31:12',0,NULL,'2026-06-22 21:31:11','2026-06-22 21:31:12'),(108,'cakiranbafna@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your creator profile to get discovered — Start Your Story','sent',NULL,'2026-06-22 21:31:14',0,NULL,'2026-06-22 21:31:13','2026-06-22 21:31:14'),(109,'raj.varma2004@gmail.com','firm','reengagement','ReEngagementMail','marketing','Complete your firm profile and start hiring — Start Your Story','sent',NULL,'2026-06-22 21:31:16',0,NULL,'2026-06-22 21:31:15','2026-06-22 21:31:16'),(110,'hr@rajendraco.com','firm','reengagement','ReEngagementMail','marketing','Verify your email to start hiring — Start Your Story','sent',NULL,'2026-06-22 21:31:18',0,NULL,'2026-06-22 21:31:17','2026-06-22 21:31:18'),(111,'patil431717@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:31:19',0,NULL,'2026-06-22 21:31:19','2026-06-22 21:31:19'),(112,'rahulc1403@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:31:21',0,NULL,'2026-06-22 21:31:20','2026-06-22 21:31:21'),(113,'mrunmayimulay124421@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:31:23',0,NULL,'2026-06-22 21:31:22','2026-06-22 21:31:23'),(114,'hr@skpn.in','firm','reengagement','ReEngagementMail','marketing','Verify your email to start hiring — Start Your Story','sent',NULL,'2026-06-22 21:31:25',0,NULL,'2026-06-22 21:31:24','2026-06-22 21:31:25'),(115,'mr.shindekunal99@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying — Start Your Story','sent',NULL,'2026-06-22 21:31:26',0,NULL,'2026-06-22 21:31:26','2026-06-22 21:31:26'),(116,'brmass.ca@gmail.com','firm','reengagement','ReEngagementMail','marketing','Verify your email to start hiring — Start Your Story','sent',NULL,'2026-06-22 21:31:28',0,NULL,'2026-06-22 21:31:27','2026-06-22 21:31:28'),(117,'sonal789987456@gmail.com','firm','reengagement','ReEngagementMail','marketing','Complete your firm profile and start hiring — Start Your Story','sent',NULL,'2026-06-22 21:31:30',0,NULL,'2026-06-22 21:31:29','2026-06-22 21:31:30'),(118,'rohit01mantri@gmail.com','student','reengagement','ReEngagementMail','marketing','Verify your email to start applying — Start Your Story','sent',NULL,'2026-06-22 21:31:32',0,NULL,'2026-06-22 21:31:31','2026-06-22 21:31:32'),(119,'jigs180902@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-23 01:13:03',0,NULL,'2026-06-23 01:13:01','2026-06-23 01:13:03'),(120,'jigs180902@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-23 01:16:16',0,NULL,'2026-06-23 01:14:14','2026-06-23 01:16:16'),(121,'harshal@cahmistry.com','firm','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-23 19:07:33',0,NULL,'2026-06-23 19:07:33','2026-06-23 19:07:33'),(122,'harshal@cahmistry.com','firm','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-23 19:09:56',0,NULL,'2026-06-23 19:07:53','2026-06-23 19:09:56'),(123,'harshal@cahmistry.com','firm','firm_approved','FirmApprovedMail','default','Your Firm Account Has Been Approved — Start Your Story','sent',NULL,'2026-06-23 19:44:57',0,NULL,'2026-06-23 19:44:53','2026-06-23 19:44:57'),(124,'ketkidaghaandassociates@gmail.com','firm','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-23 20:57:46',0,NULL,'2026-06-23 20:57:45','2026-06-23 20:57:46'),(125,'ketkidaghaandassociates@gmail.com','firm','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-23 21:00:32',0,NULL,'2026-06-23 20:58:28','2026-06-23 21:00:32'),(126,'Mittalmukul80@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-23 23:49:12',0,NULL,'2026-06-23 23:49:11','2026-06-23 23:49:12'),(127,'Mittalmukul80@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-23 23:51:52',0,NULL,'2026-06-23 23:49:50','2026-06-23 23:51:52'),(128,'rajputmangla4@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-24 09:15:53',0,NULL,'2026-06-24 09:15:52','2026-06-24 09:15:53'),(129,'rajputmangla4@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-24 09:16:02',0,NULL,'2026-06-24 09:16:01','2026-06-24 09:16:02'),(130,'rajputmangla4@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-24 09:19:36',0,NULL,'2026-06-24 09:17:34','2026-06-24 09:19:36'),(131,'admin@mrogroup.in','firm','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-24 10:05:59',0,NULL,'2026-06-24 10:05:59','2026-06-24 10:05:59'),(132,'pranjal.mlca@gmail.com','firm','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-24 10:19:46',0,NULL,'2026-06-24 10:19:45','2026-06-24 10:19:46'),(133,'pranjal.mlca@gmail.com','firm','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-24 10:22:07',0,NULL,'2026-06-24 10:20:04','2026-06-24 10:22:07'),(134,'hr@arth.net.in','firm','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-24 11:13:55',0,NULL,'2026-06-24 11:13:55','2026-06-24 11:13:55'),(135,'hr@arth.net.in','firm','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-24 11:17:11',0,NULL,'2026-06-24 11:15:10','2026-06-24 11:17:11'),(136,'hr@arth.net.in','firm','firm_approved','FirmApprovedMail','default','Your Firm Account Has Been Approved — Start Your Story','sent',NULL,'2026-06-24 11:32:33',0,NULL,'2026-06-24 11:32:30','2026-06-24 11:32:33'),(137,'mutthalahoti@gmail.com','firm','firm_approved','FirmApprovedMail','default','Your Firm Account Has Been Approved — Start Your Story','sent',NULL,'2026-06-24 11:44:46',0,NULL,'2026-06-24 11:44:43','2026-06-24 11:44:46'),(138,'kevinmendonca4@gmail.com','student','verification','VerifyEmailMail','verify','Verify Your Email Address','sent',NULL,'2026-06-24 12:35:16',0,NULL,'2026-06-24 12:35:15','2026-06-24 12:35:16'),(139,'kevinmendonca4@gmail.com','student','welcome','WelcomeEmail','default','Welcome to Start Your Story','sent',NULL,'2026-06-24 12:37:41',0,NULL,'2026-06-24 12:35:39','2026-06-24 12:37:41'),(140,'ketkidaghaandassociates@gmail.com','firm','firm_approved','FirmApprovedMail','default','Your Firm Account Has Been Approved — Start Your Story','sent',NULL,'2026-06-24 12:37:14',0,NULL,'2026-06-24 12:36:09','2026-06-24 12:37:14'),(141,'cacspriya@tnlac.in','firm','firm_approved','FirmApprovedMail','default','Your Firm Account Has Been Approved — Start Your Story','sent',NULL,'2026-06-24 12:44:55',0,NULL,'2026-06-24 12:44:53','2026-06-24 12:44:55'),(142,'rekhani.saraogi@gmail.com','firm','reengagement','ReEngagementMail','marketing','Complete your firm profile and start hiring','sent',NULL,'2026-06-24 14:19:59',2,'2026-06-24 14:20:43','2026-06-24 14:19:57','2026-06-24 14:20:53'),(143,'mardariddhi04@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:23:51',0,NULL,'2026-06-24 14:23:49','2026-06-24 14:23:51'),(144,'snehahake9@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:23:53',0,NULL,'2026-06-24 14:23:52','2026-06-24 14:23:53'),(145,'rohankolety23@gmail.com','student','reengagement','ReEngagementMail','marketing','New jobs and firms are waiting for you','sent',NULL,'2026-06-24 14:24:10',0,NULL,'2026-06-24 14:23:54','2026-06-24 14:24:10'),(146,'pratikkokadwar@gmail.com','student','reengagement','ReEngagementMail','marketing','New jobs and firms are waiting for you','sent',NULL,'2026-06-24 14:24:12',0,NULL,'2026-06-24 14:24:11','2026-06-24 14:24:12'),(147,'mayurikokil2510@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:24:30',0,NULL,'2026-06-24 14:24:13','2026-06-24 14:24:30'),(148,'shraddhagharke16@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:24:31',0,NULL,'2026-06-24 14:24:31','2026-06-24 14:24:31'),(149,'bhumigolani1@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:24:33',0,NULL,'2026-06-24 14:24:32','2026-06-24 14:24:33'),(150,'rutujamundada96@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:24:35',0,NULL,'2026-06-24 14:24:34','2026-06-24 14:24:35'),(151,'shraddhakoli1804@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:24:36',0,NULL,'2026-06-24 14:24:36','2026-06-24 14:24:36'),(152,'ymutha424@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:24:38',0,NULL,'2026-06-24 14:24:37','2026-06-24 14:24:38'),(153,'anumitasingh0511@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:24:40',0,NULL,'2026-06-24 14:24:39','2026-06-24 14:24:40'),(154,'pubggamer94442@gmail.com','student','reengagement','ReEngagementMail','marketing','New jobs and firms are waiting for you','sent',NULL,'2026-06-24 14:24:42',0,NULL,'2026-06-24 14:24:41','2026-06-24 14:24:42'),(155,'rashiagrawal0122@gmail.com','student','reengagement','ReEngagementMail','marketing','Verify your email to start applying','sent',NULL,'2026-06-24 14:24:43',0,NULL,'2026-06-24 14:24:43','2026-06-24 14:24:43'),(156,'rajmantri6@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:24:45',0,NULL,'2026-06-24 14:24:44','2026-06-24 14:24:45'),(157,'adityagramesh2004@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:24:47',0,NULL,'2026-06-24 14:24:46','2026-06-24 14:24:47'),(158,'ak.anshamalpani13@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your creator profile to get discovered','sent',NULL,'2026-06-24 14:24:49',0,NULL,'2026-06-24 14:24:48','2026-06-24 14:24:49'),(159,'capranavsancheti@gmail.com','firm','reengagement','ReEngagementMail','marketing','Start posting jobs and reaching candidates','sent',NULL,'2026-06-24 14:24:51',0,NULL,'2026-06-24 14:24:50','2026-06-24 14:24:51'),(160,'prajwalalhat7728@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:24:52',0,NULL,'2026-06-24 14:24:52','2026-06-24 14:24:52'),(161,'dsangle659@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:24:54',0,NULL,'2026-06-24 14:24:53','2026-06-24 14:24:54'),(162,'ishadhoka2000@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:24:56',0,NULL,'2026-06-24 14:24:55','2026-06-24 14:24:56'),(163,'kamal@caco.in','firm','reengagement','ReEngagementMail','marketing','Start posting jobs and reaching candidates','sent',NULL,'2026-06-24 14:25:07',0,NULL,'2026-06-24 14:24:57','2026-06-24 14:25:07'),(164,'bnst.ca@gmail.com','firm','reengagement','ReEngagementMail','marketing','Start posting jobs and reaching candidates','sent',NULL,'2026-06-24 14:25:09',0,NULL,'2026-06-24 14:25:08','2026-06-24 14:25:09'),(165,'rekhani.saraogi@gmail.com','firm','reengagement','ReEngagementMail','marketing','Complete your firm profile and start hiring','sent',NULL,'2026-06-24 14:25:22',0,NULL,'2026-06-24 14:25:10','2026-06-24 14:25:22'),(166,'sumitsawant2311@gmail.com','student','reengagement','ReEngagementMail','marketing','Verify your email to start applying','sent',NULL,'2026-06-24 14:25:24',0,NULL,'2026-06-24 14:25:23','2026-06-24 14:25:24'),(167,'siddhesh99shinde@gmail.com','student','reengagement','ReEngagementMail','marketing','New jobs and firms are waiting for you','sent',NULL,'2026-06-24 14:25:26',0,NULL,'2026-06-24 14:25:25','2026-06-24 14:25:26'),(168,'darshanpatni09@gmail.com','student','reengagement','ReEngagementMail','marketing','New jobs and firms are waiting for you','sent',NULL,'2026-06-24 14:25:28',0,NULL,'2026-06-24 14:25:27','2026-06-24 14:25:28'),(169,'minakshikusha@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:25:30',0,NULL,'2026-06-24 14:25:29','2026-06-24 14:25:30'),(170,'kakdesuraj383@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:25:31',0,NULL,'2026-06-24 14:25:31','2026-06-24 14:25:31'),(171,'sanchitanagwani@gmail.com','student','reengagement','ReEngagementMail','marketing','New jobs and firms are waiting for you','sent',NULL,'2026-06-24 14:25:33',0,NULL,'2026-06-24 14:25:32','2026-06-24 14:25:33'),(172,'gajupawar775@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:25:35',0,NULL,'2026-06-24 14:25:34','2026-06-24 14:25:35'),(173,'mantripb77@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:25:36',0,NULL,'2026-06-24 14:25:36','2026-06-24 14:25:36'),(174,'pawalerohan1999@gmail.com','student','reengagement','ReEngagementMail','marketing','New jobs and firms are waiting for you','sent',NULL,'2026-06-24 14:25:38',0,NULL,'2026-06-24 14:25:37','2026-06-24 14:25:38'),(175,'akashpund2003@gmail.com','student','reengagement','ReEngagementMail','marketing','Get discovered for new content projects','sent',NULL,'2026-06-24 14:25:40',0,NULL,'2026-06-24 14:25:39','2026-06-24 14:25:40'),(176,'pratishbansode07@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:25:42',0,NULL,'2026-06-24 14:25:41','2026-06-24 14:25:42'),(177,'surajingleprofessional007@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:25:52',0,NULL,'2026-06-24 14:25:43','2026-06-24 14:25:52'),(178,'atharvpatil4328@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your creator profile to get discovered','sent',NULL,'2026-06-24 14:25:54',0,NULL,'2026-06-24 14:25:53','2026-06-24 14:25:54'),(179,'prajaktashitole01@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:25:56',0,NULL,'2026-06-24 14:25:55','2026-06-24 14:25:56'),(180,'kartikajain29@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your creator profile to get discovered','sent',NULL,'2026-06-24 14:25:57',0,NULL,'2026-06-24 14:25:57','2026-06-24 14:25:57'),(181,'casunilshenoy@gmail.com','firm','reengagement','ReEngagementMail','marketing','Complete your firm profile and start hiring','sent',NULL,'2026-06-24 14:25:59',0,NULL,'2026-06-24 14:25:58','2026-06-24 14:25:59'),(182,'anushka.shinde.1217@gmail.com','student','reengagement','ReEngagementMail','marketing','New jobs and firms are waiting for you','sent',NULL,'2026-06-24 14:26:01',0,NULL,'2026-06-24 14:26:00','2026-06-24 14:26:01'),(183,'garvitabansal7@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your creator profile to get discovered','sent',NULL,'2026-06-24 14:26:02',0,NULL,'2026-06-24 14:26:02','2026-06-24 14:26:02'),(184,'varunmulay9@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your creator profile to get discovered','sent',NULL,'2026-06-24 14:26:04',0,NULL,'2026-06-24 14:26:03','2026-06-24 14:26:04'),(185,'mohittinwar1234@gmail.com','student','reengagement','ReEngagementMail','marketing','New jobs and firms are waiting for you','sent',NULL,'2026-06-24 14:26:06',0,NULL,'2026-06-24 14:26:05','2026-06-24 14:26:06'),(186,'akanshaagrawal0918@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:26:08',0,NULL,'2026-06-24 14:26:07','2026-06-24 14:26:08'),(187,'kasargautam19@gmail.com','student','reengagement','ReEngagementMail','marketing','New jobs and firms are waiting for you','sent',NULL,'2026-06-24 14:26:09',0,NULL,'2026-06-24 14:26:09','2026-06-24 14:26:09'),(188,'piyushagrawal4833@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:26:15',0,NULL,'2026-06-24 14:26:10','2026-06-24 14:26:15'),(189,'vipingujarathico@gmail.com','firm','reengagement','ReEngagementMail','marketing','Start posting jobs and reaching candidates','sent',NULL,'2026-06-24 14:26:17',0,NULL,'2026-06-24 14:26:16','2026-06-24 14:26:17'),(190,'hr@prachay.com','firm','reengagement','ReEngagementMail','marketing','Complete your firm profile and start hiring','sent',NULL,'2026-06-24 14:26:21',0,NULL,'2026-06-24 14:26:18','2026-06-24 14:26:21'),(191,'amolrtotla@gmail.com','firm','reengagement','ReEngagementMail','marketing','Start posting jobs and reaching candidates','sent',NULL,'2026-06-24 14:26:23',0,NULL,'2026-06-24 14:26:22','2026-06-24 14:26:23'),(192,'tishajain0906@gmail.com','student','reengagement','ReEngagementMail','marketing','New jobs and firms are waiting for you','sent',NULL,'2026-06-24 14:26:25',0,NULL,'2026-06-24 14:26:24','2026-06-24 14:26:25'),(193,'kukrejamekii2703@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your creator profile to get discovered','sent',NULL,'2026-06-24 14:26:26',0,NULL,'2026-06-24 14:26:26','2026-06-24 14:26:26'),(194,'carsbiyani@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your creator profile to get discovered','sent',NULL,'2026-06-24 14:26:28',0,NULL,'2026-06-24 14:26:27','2026-06-24 14:26:28'),(195,'rsbitsolution@gmail.com','firm','reengagement','ReEngagementMail','marketing','Start posting jobs and reaching candidates','sent',NULL,'2026-06-24 14:26:30',0,NULL,'2026-06-24 14:26:29','2026-06-24 14:26:30'),(196,'nitishbadak14@gmail.com','student','reengagement','ReEngagementMail','marketing','Verify your email to get discovered','sent',NULL,'2026-06-24 14:26:34',0,NULL,'2026-06-24 14:26:31','2026-06-24 14:26:34'),(197,'cacspriya@tnlac.in','firm','reengagement','ReEngagementMail','marketing','Start posting jobs and reaching candidates','sent',NULL,'2026-06-24 14:26:35',0,NULL,'2026-06-24 14:26:35','2026-06-24 14:26:35'),(198,'kakaniatharv06@gmail.com','student','reengagement','ReEngagementMail','marketing','New jobs and firms are waiting for you','sent',NULL,'2026-06-24 14:26:37',0,NULL,'2026-06-24 14:26:36','2026-06-24 14:26:37'),(199,'anjumantri@gmail.com','student','reengagement','ReEngagementMail','marketing','New jobs and firms are waiting for you','sent',NULL,'2026-06-24 14:26:39',0,NULL,'2026-06-24 14:26:38','2026-06-24 14:26:39'),(200,'priyanisaklecha9@gmail.com','student','reengagement','ReEngagementMail','marketing','New jobs and firms are waiting for you','sent',NULL,'2026-06-24 14:26:41',1,'2026-06-24 14:29:47','2026-06-24 14:26:40','2026-06-24 14:29:47'),(201,'mohitbalrampatil@gmail.com','student','reengagement','ReEngagementMail','marketing','Verify your email to start applying','sent',NULL,'2026-06-24 14:26:42',0,NULL,'2026-06-24 14:26:42','2026-06-24 14:26:42'),(202,'adepsharwari8@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:26:44',0,NULL,'2026-06-24 14:26:43','2026-06-24 14:26:44'),(203,'cakiranbafna@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your creator profile to get discovered','sent',NULL,'2026-06-24 14:26:46',0,NULL,'2026-06-24 14:26:45','2026-06-24 14:26:46'),(204,'hemanshukopulwar21@gmail.com','student','reengagement','ReEngagementMail','marketing','New jobs and firms are waiting for you','sent',NULL,'2026-06-24 14:26:47',0,NULL,'2026-06-24 14:26:47','2026-06-24 14:26:47'),(205,'raj.varma2004@gmail.com','firm','reengagement','ReEngagementMail','marketing','Complete your firm profile and start hiring','sent',NULL,'2026-06-24 14:26:49',0,NULL,'2026-06-24 14:26:48','2026-06-24 14:26:49'),(206,'cakarmandco@gmail.com','firm','reengagement','ReEngagementMail','marketing','Start posting jobs and reaching candidates','sent',NULL,'2026-06-24 14:26:51',0,NULL,'2026-06-24 14:26:50','2026-06-24 14:26:51'),(207,'hr@rajendraco.com','firm','reengagement','ReEngagementMail','marketing','Verify your email to start hiring','sent',NULL,'2026-06-24 14:26:53',0,NULL,'2026-06-24 14:26:52','2026-06-24 14:26:53'),(208,'patil431717@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:26:54',0,NULL,'2026-06-24 14:26:54','2026-06-24 14:26:54'),(209,'komal.mopkar@gmail.com','student','reengagement','ReEngagementMail','marketing','New jobs and firms are waiting for you','sent',NULL,'2026-06-24 14:26:56',0,NULL,'2026-06-24 14:26:55','2026-06-24 14:26:56'),(210,'rahulc1403@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:26:58',0,NULL,'2026-06-24 14:26:57','2026-06-24 14:26:58'),(211,'ca.sunil.wadhwani@gmail.com','firm','reengagement','ReEngagementMail','marketing','Start posting jobs and reaching candidates','sent',NULL,'2026-06-24 14:27:00',0,NULL,'2026-06-24 14:26:59','2026-06-24 14:27:00'),(212,'mrunmayimulay124421@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:27:01',0,NULL,'2026-06-24 14:27:01','2026-06-24 14:27:01'),(213,'hr@skpn.in','firm','reengagement','ReEngagementMail','marketing','Verify your email to start hiring','sent',NULL,'2026-06-24 14:27:03',0,NULL,'2026-06-24 14:27:02','2026-06-24 14:27:03'),(214,'mr.shindekunal99@gmail.com','student','reengagement','ReEngagementMail','marketing','New jobs and firms are waiting for you','sent',NULL,'2026-06-24 14:27:05',0,NULL,'2026-06-24 14:27:04','2026-06-24 14:27:05'),(215,'hirodkarv@gmail.com','student','reengagement','ReEngagementMail','marketing','New jobs and firms are waiting for you','sent',NULL,'2026-06-24 14:27:06',0,NULL,'2026-06-24 14:27:06','2026-06-24 14:27:06'),(216,'brmass.ca@gmail.com','firm','reengagement','ReEngagementMail','marketing','Verify your email to start hiring','sent',NULL,'2026-06-24 14:27:08',0,NULL,'2026-06-24 14:27:07','2026-06-24 14:27:08'),(217,'sonal789987456@gmail.com','firm','reengagement','ReEngagementMail','marketing','Complete your firm profile and start hiring','sent',NULL,'2026-06-24 14:27:10',0,NULL,'2026-06-24 14:27:09','2026-06-24 14:27:10'),(218,'hr@vsap.co.in','firm','reengagement','ReEngagementMail','marketing','Start posting jobs and reaching candidates','sent',NULL,'2026-06-24 14:27:12',0,NULL,'2026-06-24 14:27:11','2026-06-24 14:27:12'),(219,'rohit01mantri@gmail.com','student','reengagement','ReEngagementMail','marketing','Verify your email to start applying','sent',NULL,'2026-06-24 14:27:13',0,NULL,'2026-06-24 14:27:13','2026-06-24 14:27:13'),(220,'jigs180902@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:27:15',0,NULL,'2026-06-24 14:27:14','2026-06-24 14:27:15'),(221,'harshal@cahmistry.com','firm','reengagement','ReEngagementMail','marketing','Start posting jobs and reaching candidates','sent',NULL,'2026-06-24 14:27:17',0,NULL,'2026-06-24 14:27:16','2026-06-24 14:27:17'),(222,'ketkidaghaandassociates@gmail.com','firm','reengagement','ReEngagementMail','marketing','Start posting jobs and reaching candidates','sent',NULL,'2026-06-24 14:27:19',0,NULL,'2026-06-24 14:27:18','2026-06-24 14:27:19'),(223,'Mittalmukul80@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:27:20',0,NULL,'2026-06-24 14:27:20','2026-06-24 14:27:20'),(224,'rajputmangla4@gmail.com','student','reengagement','ReEngagementMail','marketing','Complete your profile and start applying','sent',NULL,'2026-06-24 14:27:22',0,NULL,'2026-06-24 14:27:21','2026-06-24 14:27:22'),(225,'admin@mrogroup.in','firm','reengagement','ReEngagementMail','marketing','Verify your email to start hiring','sent',NULL,'2026-06-24 14:27:24',0,NULL,'2026-06-24 14:27:23','2026-06-24 14:27:24'),(226,'mutthalahoti@gmail.com','firm','reengagement','ReEngagementMail','marketing','Start posting jobs and reaching candidates','sent',NULL,'2026-06-24 14:27:26',0,NULL,'2026-06-24 14:27:25','2026-06-24 14:27:26'),(227,'hr@arth.net.in','firm','reengagement','ReEngagementMail','marketing','Start posting jobs and reaching candidates','sent',NULL,'2026-06-24 14:27:28',0,NULL,'2026-06-24 14:27:27','2026-06-24 14:27:28'),(228,'kevinmendonca4@gmail.com','student','reengagement','ReEngagementMail','marketing','New jobs and firms are waiting for you','sent',NULL,'2026-06-24 14:27:29',0,NULL,'2026-06-24 14:27:29','2026-06-24 14:27:29');
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
  `error_summary` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=778 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `error_logs`
--

LOCK TABLES `error_logs` WRITE;
/*!40000 ALTER TABLE `error_logs` DISABLE KEYS */;
INSERT INTO `error_logs` VALUES (764,'api','Unauthorized','Unauthorized',401,'/admin/me',NULL,NULL,NULL,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','106.205.5.156','2026-06-22 22:19:36'),(765,'api','Invalid token','Invalid token',401,'/admin/me',NULL,NULL,NULL,'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','106.205.5.156','2026-06-22 22:30:42'),(766,'api','User not found','User not found',404,'/login',NULL,NULL,NULL,'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','157.32.218.4','2026-06-23 17:06:16'),(767,'frontend','Script error.','Script error.',NULL,'/register',NULL,NULL,NULL,'Mozilla/5.0 (iPhone; CPU iPhone OS 26_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/149.0.7827.137 Mobile/15E148 Safari/604.1','223.228.54.43','2026-06-23 20:57:42'),(768,'frontend','Script error.','Script error.',NULL,'/register',NULL,NULL,NULL,'Mozilla/5.0 (iPhone; CPU iPhone OS 26_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/149.0.7827.137 Mobile/15E148 Safari/604.1','223.228.54.43','2026-06-23 20:57:42'),(769,'api','Send Verification Link API called','Send Verification Link API called',500,'/api/email/send-verification-link',NULL,94,'student','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','47.11.1.77','2026-06-24 09:16:01'),(770,'api','User not found','User not found',404,'/login',NULL,NULL,NULL,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','103.160.175.18','2026-06-24 11:45:24'),(771,'frontend','Illegal invocation','Illegal invocation',NULL,'/','TypeError: Illegal invocation\n    at chrome-extension://hfjnppljknigdnnpocjjgdcfmnodoafe/inject-main-world.bundle.js:1:973\n    at O (https://startyourstory.in/assets/index-MxpUE6nq.js:10:71756)\n    at https://startyourstory.in/assets/index-MxpUE6nq.js:10:71959',NULL,NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','123.201.245.210','2026-06-24 12:35:59'),(772,'api','Expected response code \"250\" but got code \"421\", with message \"421 4.4.2 smtp.hostinger.com Error: timeout exceeded\".','Expected response code \"250\" but got code \"421\", with message \"421 4.4.2 smtp.hostinger.com Error: timeout exceeded\".',500,'/',NULL,NULL,NULL,'Symfony','127.0.0.1','2026-06-24 12:36:12'),(773,'api','User not found','User not found',404,'/login',NULL,NULL,NULL,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','103.160.175.18','2026-06-24 12:46:21'),(774,'api','User not found','User not found',404,'/login',NULL,NULL,NULL,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','103.160.175.18','2026-06-24 12:46:27'),(775,'api','Invalid password','Invalid password',401,'/login',NULL,67,'student','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','117.229.145.76','2026-06-24 14:30:09'),(776,'api','Unauthorized','Unauthorized',401,'/admin/me',NULL,NULL,NULL,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','154.84.246.6','2026-06-24 14:35:42'),(777,'api','Unauthorized','Unauthorized',401,'/admin/firms-stats',NULL,NULL,NULL,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','154.84.246.6','2026-06-24 14:35:42');
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
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `firm_branches`
--

LOCK TABLES `firm_branches` WRITE;
/*!40000 ALTER TABLE `firm_branches` DISABLE KEYS */;
INSERT INTO `firm_branches` VALUES (10,2,'SANGLI','Office no 3, Garden View Apartment, Near Trikoni baug, Civil Hospital road','MAHARASHTRA','416416'),(11,1,'','','',''),(13,3,'','','',''),(14,4,'PUNE','Khed Shivapur','MAHARASHTRA','412205'),(15,9,'','','',''),(16,11,'','','',''),(17,12,'LATUR','1st Floor, Above Dr Unni, Vyapari Dharmshala Complex, Main Road,','MAHARASHTRA','413512'),(18,14,'','','',''),(20,16,'','','',''),(21,18,'','','',''),(22,19,'','','',''),(25,24,'','','',''),(26,25,'','','',''),(27,28,'','','MAHARASHTRA',''),(28,29,'AKOLA','375, Baheti Arcade, Alsi Plot, Akola, Maharashtra','MAHARASHTRA','444001'),(29,29,'MUMBAI','Mayura heights, Plot No. 15,16,17, Sector 18, Kharghar, Mumbai','MAHARASHTRA','410210'),(31,13,'AHMEDNAGAR','PUNE','MAHARASHTRA','411037'),(33,26,'','','','');
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
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `firm_profiles`
--

LOCK TABLES `firm_profiles` WRITE;
/*!40000 ALTER TABLE `firm_profiles` DISABLE KEYS */;
INSERT INTO `firm_profiles` VALUES (1,2,'123456W',0,NULL,NULL,'Test','PUNE','pune','Ritesh',2,2,2,'[\"overall\"]',NULL,NULL,'firm/logo/1781086388_logo.png',0,'2026-06-10 15:32:16','2026-06-16 14:23:15','approved',NULL,'Taxation firm','Proprietorship','2026',NULL,NULL,'[]',NULL,NULL,NULL,NULL,NULL,'[\"firm/office-images/1781285917_6a2c441dcaf13.png\"]','[{\"name\": \"\", \"role\": \"\", \"phone\": \"\"}]'),(2,22,'162224W',0,NULL,NULL,'Sancheti Lakade & Associates','PUNE','B-826, Gera Imperium Gateway, Near Nashik Phata Metro Station, Pune - 411034','Pranav Sancheti',2,1,0,'[\"overall\"]','https://www.linkedin.com/in/ca-ruturaj-lakade-252082233?utm_source=share_via&utm_content=profile&utm_medium=member_ios',NULL,'firm/logo/1781599573_logo.jpeg',0,'2026-06-16 14:03:23','2026-06-16 21:37:06','approved',NULL,'Accounting & Bookkeeping – Maintaining accurate financial records and preparing financial statements to support informed decision-making.\r\n\r\nTaxation Services – Expert assistance in Income Tax, GST compliance, tax planning, return filing, assessments, and advisory.\r\n\r\nAudit & Assurance – Conducting statutory audits, internal audits, tax audits, and assurance engagements to enhance transparency and compliance.\r\n\r\nBusiness Registration & Compliance – Assistance with company incorporation, LLP registration, startup registration, and ongoing regulatory compliance.\r\n\r\nIncentives and subsidies - All incentives and subsidies under the central and state government','Partnership','2026','Schemes & Incentives (State & Central Schemes), Audits, Taxation , Project reports,','Manufacturing, IT, Education & Misc','[]',NULL,NULL,NULL,NULL,NULL,'[\"firm/office-images/1781599573_6a310d55c7fa1.jpeg\", \"firm/office-images/1781599573_6a310d55c807e.jpeg\", \"firm/office-images/1781599573_6a310d55c8163.jpeg\", \"firm/office-images/1781599573_6a310d55c8255.jpeg\", \"firm/office-images/1781599573_6a310d55c8329.jpeg\", \"firm/office-images/1781599573_6a310d55c8409.jpeg\", \"firm/office-images/1781599573_6a310d55c8523.jpeg\"]','[{\"name\": \"\", \"role\": \"\", \"phone\": \"\"}]'),(3,26,'127597W',0,NULL,NULL,'C A & Co','RAJKOT','3rd Floor Aadarsh Sukham 150 Feet Ring Road KKV Circle Behind Sanskar Complex, Rajkot - 360 005','Kamal Bhambhani',2,8,2,'[\"overall\"]',NULL,NULL,NULL,0,'2026-06-16 17:29:36','2026-06-16 21:37:28','approved',NULL,'We are a firm of Chartered Accountants practicing in Audits, Direct Taxes, Indirect Taxes and Litigations.','Partnership','2005','Audit, Direct Tax, Indirect Tax, Litigations, Advisory',NULL,'[]',NULL,NULL,NULL,NULL,NULL,NULL,'[{\"name\": \"\", \"role\": \"\", \"phone\": \"\"}]'),(4,27,'W101215',0,NULL,NULL,'B N S T & Co LLP','PUNE','B107-108, The Greater Good, Mohammadwadi, Pune 411060','CA Akshay Nikam',3,7,1,'[\"Statutory Audit\",\"Tax Audit\",\"Direct Tax\",\"GST & Indirect Tax\",\"Accounting & Bookkeeping\",\"Advisory & Consulting\",\"Corporate Laws & LLP\",\"Forensic Audit & Investigation\",\"Payroll Services\",\"Virtual CFO Services\",\"Due Diligence\",\"NGO / Trust Audit\"]',NULL,'https://bnstca.com/',NULL,0,'2026-06-16 17:37:42','2026-06-16 21:37:36','approved',NULL,'B N S T & CO LLP is a Pune-based chartered accountancy firm offering income tax, GST, statutory and regulatory compliance, ROC and corporate law, bank loan and project finance, and audit services. Our audit practice has a strong focus on banking audits, including statutory, concurrent, revenue, and credit audits. We work across diverse industries with a verification-first, quality-driven approach, giving our team hands-on exposure to high-value assurance and advisory engagements.','LLP','2026','Income Tax, GST, Statutory & Regulatory Compliance, ROC & Corporate Law, Bank Loans & Project Finance, Statutory Audit, Tax Audit, Bank Audit (Concurrent / Revenue / Credit), Internal Audit, Business Advisory','Manufacturing, BFSI, IT & Software, Trading & Retail, Real Estate & Construction, Services, Exports','[]',NULL,NULL,NULL,NULL,NULL,NULL,'[{\"name\": \"CA Akshay Nikam\", \"role\": \"Partner\", \"phone\": \"7888080300\"}, {\"name\": \"CA Tanmay Shedge\", \"role\": \"Partner\", \"phone\": \"7888080200\"}]'),(5,28,NULL,0,NULL,NULL,'Rekhani & Saraogi','SURAT',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-06-16 18:10:31','2026-06-16 18:10:31','pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(6,36,NULL,0,NULL,NULL,'Test','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-06-16 20:07:05','2026-06-16 20:07:05','pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(7,45,NULL,0,NULL,NULL,'Sunil Shenoy and Associates','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-06-17 08:09:04','2026-06-17 08:09:04','pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(8,51,NULL,0,NULL,NULL,'A R Totala & Co.','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-06-17 11:06:37','2026-06-17 11:06:37','pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(9,54,'109573W',0,NULL,NULL,'Vipin Gujarathi & Co','PUNE','1009 to 1014, Apex Business Court, Bibwewadi Kondhwa Road, Bibwewadi, Pune','Nalini Shah',3,2,12,'[\"overall\"]','https://www.linkedin.com/company/vipin-gujarathi-associates/',NULL,'firm/logo/1781683273_logo.jpeg',0,'2026-06-17 12:16:19','2026-06-17 17:56:41','approved',NULL,'Established in 1981, the firm has rich experience of four decades. Founded by Late CA Vipin Gujarathi, an eminent name in direct tax and litigation professional circles, the firm continues to be committed to its clients, offering bouquet of services. The firm is head quartered in Pune. It functions from 2 offices located across Pune and Mumbai. Currently, the firm boasts of a strong work force of 50 members including 10 Chartered Accountants and is continuously growing its strength.','Partnership','1981','Tax Audit, Statutory Audit, Internal Audit, Advisory, TDS and GST Compliances, Direct Taxes Litigation, Return filing, etc','Professional, Trader, Real Estate, Logistics, etc.','[]',NULL,NULL,NULL,NULL,NULL,'[\"firm/office-images/1781683273_6a3254498543a.jpeg\", \"firm/office-images/1781683273_6a32544985538.jpeg\", \"firm/office-images/1781683273_6a32544985619.jpeg\"]','[{\"name\": \"\", \"role\": \"\", \"phone\": \"\"}]'),(10,55,NULL,0,NULL,NULL,'Prachay Grouo','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-06-17 12:34:58','2026-06-17 12:34:58','pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(11,56,'139367W',0,NULL,NULL,'A R Totala & Co.','PUNE','Off. No.5, 3rd Floor, Sai Complex, 917/22, Ganeshwadi, Opp. Hotel Roopali, F. C. Road, Pune - 411 004','Amol Totala',0,1,1,'[\"overall\"]',NULL,NULL,NULL,0,'2026-06-17 13:28:17','2026-06-17 17:56:46','approved',NULL,'Firm competency\r\n•	Professional Approach with Integrity & complete confidentiality\r\n•	Innovative Ideas\r\n•	Sound knowledgebase and counseling\r\n•	Onsite and Offsite Service as per requirement\r\n•	Supervision by professional having deep accounting and compliance knowledge and tried-and-tested methodologies\r\n•	Accuracy of information\r\n•	Helps management to focus on core areas of business\r\n•	Cost effective being lower overheads and helps to achieve the management results','Proprietorship','2014','•	Statutory compliances (Direct & Indirect taxes)\r\n•	Statutory, internal, tax, due diligence and special audit\r\n•	Direct Tax & Indirect tax planning, provisioning & compliance, advisory, representation services\r\n•	Preparation and evaluation of project proposals\r\n•	Cash to accrual system audit\r\n•	Documentation, preparation and review of statutory return\r\n•	Alignment of tax with business strategy, identifying tax exposure and planning\r\n•	Designing standard operating processes, internal audit and diagnostic reviews','Manufacturing, Service, IT, Government Independent Body.','[]',NULL,NULL,NULL,NULL,NULL,NULL,'[{\"name\": \"\", \"role\": \"\", \"phone\": \"\"}]'),(12,60,'122630W',0,NULL,NULL,'R S Biyani & Co','LATUR','Shop No 1, 1st Floor, Above Dr Unni , Vyapari Dharmshala Complex, Main Road, Latur','CA Radhesham Biyani',0,2,0,'[\"Subsidy\",\"Bank Finance\"]',NULL,NULL,NULL,0,'2026-06-17 20:28:14','2026-06-18 14:54:01','approved',NULL,'Firm is established in 2002 and having office at Latur','Proprietorship','2002','Subsidy , Bank Finance','Agro Based Industries , Manufacturing Industries','[]',NULL,NULL,NULL,NULL,NULL,NULL,'[{\"name\": \"\", \"role\": \"\", \"phone\": \"\"}]'),(13,62,'105437W',0,NULL,NULL,'N J LOHE & CO.','PUNE','PUNE','Priyanka Lohe',4,20,NULL,'[\"overall\"]',NULL,NULL,NULL,0,'2026-06-17 22:57:44','2026-06-24 12:44:53','approved',NULL,'A firm since past 40years specialized in accounting, taxation, GST, Corporate compliance, mergers & acquisition, project financing','Partnership','1980','Specialized in accounting, taxation, GST, Corporate compliance, mergers & acquisition, project financing','Manufacturing, Retail, Profession, Business, Banking sector, FMCG','[]',NULL,NULL,NULL,NULL,NULL,NULL,'[{\"name\": \"Tushar Lohe\", \"role\": \"Partner\", \"phone\": \"+91 9028185515\"}]'),(14,66,'091199',0,NULL,NULL,'test','PUNE','Pune','Test',2,5,5,'[\"overall\"]',NULL,NULL,NULL,0,'2026-06-17 23:43:39','2026-06-17 23:45:45','approved',NULL,'Test','Proprietorship','2025',NULL,NULL,'[]',NULL,NULL,NULL,NULL,NULL,NULL,'[{\"name\": \"\", \"role\": \"\", \"phone\": \"\"}]'),(15,73,NULL,0,NULL,NULL,'RAJESH VARMA & ASSOCIATES','MUMBAI',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-06-18 19:33:35','2026-06-18 19:33:35','pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(16,74,'105237W',0,NULL,NULL,'K A R M & CO. Chartered Accountants','MUMBAI','201 Sterling Chambers, 56 Mogra village, Off old nagardas road, Andheri east','Tanuj Jain',7,110,1,'[\"overall\"]','https://www.linkedin.com/company/karmandco/','Https://www.karmandco.com',NULL,0,'2026-06-18 20:28:41','2026-06-19 11:17:14','approved',NULL,'Founded in 1981, KARM & CO is a well-established firm\r\nwith extensive expertise in the field of chartered\r\naccountancy. With a team of 7 partners and highly\r\nqualified staff, the firm offers tailored solutions to meet\r\nthe needs of its diverse client base, which includes\r\nleading private and public sector enterprises. KARM & CO\r\nis empanelled with esteemed regulatory authorities such\r\nas the Comptroller and Auditor General of India, Reserve\r\nBank of India, and Securities & Exchange Board of India.\r\nLeveraging specialized areas of expertise, the firm is\r\ncommitted to providing innovative solutions with a focus\r\non integrity, excellence, and professionalism. Joining\r\nKARM & CO offers exciting career opportunities in a\r\ndynamic and impactful work environment.','Partnership','1981','Audit, Tax, consultancy,','Manufacturing, Shipping, BFSI, Service','[]',NULL,NULL,NULL,NULL,NULL,NULL,'[{\"name\": \"\", \"role\": \"\", \"phone\": \"\"}]'),(17,75,NULL,0,NULL,NULL,'RCO','MUMBAI',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-06-19 14:41:58','2026-06-19 14:41:58','pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(18,79,'164559W',0,NULL,NULL,'SMW AND Co.','PUNE','2nd Floor, Bhagya Building, Opp. Coronet Hotel, Ape Road, Pune','Sunil Wadhwani',1,2,3,'[\"overall\"]',NULL,NULL,NULL,0,'2026-06-20 11:36:59','2026-06-20 23:23:06','approved',NULL,'SMW AND CO is a professional services firm specializing in providing accounting, direct and indirect taxation, compliance and advisory solutions in Pune. The firm values professional growth, ethical practices, and a collaborative work environment.','Proprietorship','2016','Accounts, Compliance, Advisory, Litigation in Income Tax and GST, Certifications in RERA, MIS, Virtual CFO, Zoho Implementation, MCA Compliance Support, Audit etc.','IT, ITES, Real Estate, Education, Trading, Consultancy, Services etc.','[]',NULL,NULL,NULL,NULL,NULL,NULL,'[{\"name\": \"\", \"role\": \"\", \"phone\": \"\"}]'),(19,80,'123455',0,NULL,NULL,'sys and assosiates','PUNE','shukrawar peth pune','anuj karewad',NULL,NULL,NULL,'[\"overall\"]',NULL,NULL,NULL,0,'2026-06-20 12:49:46','2026-06-20 12:54:50','pending',NULL,'a whfdbdjdn  isdiawf uf aef efgauefafea e','LLP','2025',NULL,NULL,'[]',NULL,NULL,NULL,NULL,NULL,NULL,'[{\"name\": \"\", \"role\": \"\", \"phone\": \"\"}]'),(20,82,NULL,0,NULL,NULL,'SKPN & Associates LLP','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-06-20 18:10:57','2026-06-20 18:10:57','pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(21,85,NULL,0,NULL,NULL,'B R M A S S & ASSOCIATES','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-06-21 16:02:43','2026-06-21 16:02:43','pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(22,86,NULL,0,NULL,NULL,'Kothari Chandel & Co.','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-06-21 20:09:03','2026-06-21 20:09:03','pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(23,87,NULL,0,NULL,NULL,'Test Phonepay','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-06-21 21:35:33','2026-06-21 21:37:24','approved',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(24,88,'137979W',0,NULL,NULL,'V S A P & Company','PUNE','OFFICE NO 102 1ST FLOOR ASPIRO\r\nPIMPRI STATION ROAD OPPOSITE\r\nTHRSSENKRUPP COMPANY PIMPRI','Sayali Dighe',5,20,9,'[\"overall\"]','https://in.linkedin.com/company/vsap-company#:~:text=We%20are%20a%20professional%20CA,having%20staff%20strength%20of%2026.','https://vsap.co.in/','firm/logo/1782119234_logo.png',0,'2026-06-22 12:24:32','2026-06-22 19:48:44','approved',NULL,'We are a professional CA firm established in 2013 having offices in Pimpri, Pune, Jalgaon & Shirpur in Dhule district having staff strength of 26. Our firm V S A P stands for CA Vaibhav R Mayur, CA Sachin B Malpani, CA Swapnil Rathi, CA Paras Zavar and CA Adarsh Sharma. We provide our services to various sectors viz. agricultural, banking, co-operative, engineering, FMCG, manufacturing, power, pharmaceutical, real estate, etc. We have started our services from consulting in direct & indirect taxation and auditing. Now  we have expended our services’ portfolio by compliance on Real Estate Regulation Act (RERA), Co-Operative societies and banks, international/NRI taxation & certification, internal auditing, project financing, planning & implementation of new projects and subsidy consultation.','Partnership','2013','Audit, GST, Income Tax, Business Consultation','Real Estate Developer, Manufacturer','[]',NULL,NULL,NULL,NULL,NULL,NULL,'[{\"name\": \"\", \"role\": \"\", \"phone\": \"\"}]'),(25,91,'143146W',0,NULL,NULL,'H Mistry & Associates','PUNE','C - 327, Clover Center, Moledina Road, Pune Camp, Maharashtra, 411001','CA Harshal Mistry',3,6,7,'[\"overall\"]','https://www.linkedin.com/company/h-mistry-associates/?originalSubdomain=in','https://cahmistry.com/',NULL,0,'2026-06-23 19:07:33','2026-06-23 19:44:53','approved',NULL,'H MISTRY & ASSOCIATES is a premier Chartered Accountancy firm offering professional services in areas of Statutory Audit, Direct & Indirect tax, RERA, accounting and relating services to domestic and international business entities. We are a team of energetic & enthusiastic partners having a positive mindset to provide expert and professional services within the ambit of professional ethics.\r\n\r\nOur firm tries to provide the maximum in client service and individual staff capabilities at the minimum cost, which can be achieved within the highest professional standards and competence levels.\r\n\r\nOur firm is based in a centrally located area of Pune Camp, which gives us and our clients easy access to one another. We are planning to have a Mumbai branch in the near future.','Partnership','2016','Audit, Registration, RERA, Accounting, Direct & Indirect Tax.','Hotel Industries / Restaurants, Manufacturing Industries, Construction Companies, Trading Companies, Charitable Organizations, Advertising Industries, Telecommunication, Food Industry, Textile, Petroleum industry, Electronics & Transportation','[]',NULL,NULL,'https://www.instagram.com/h_mistry_and_associates?igsh=aTVhejBkNWMzbHNp',NULL,NULL,NULL,'[{\"name\": \"\", \"role\": \"\", \"phone\": \"\"}]'),(26,92,'131380W',0,NULL,NULL,'Ketki Dagha And Associates','MUMBAI','226, AVIOR CORPORATE PARK, M M MALVIYA ROAD\r\nNEAR P&T COLONY , MULUND WEST, MUMBAI','KETKI  DIPESH DAGHA',0,10,3,'[\"overall\"]',NULL,NULL,'firm/logo/1782283065_logo.jpeg',0,'2026-06-23 20:57:45','2026-06-24 12:36:09','approved',NULL,'Ketki Dagha & Associates, Chartered Accountants is a Mumbai-based CA firm providing professional services in the areas of GST, Income Tax, Audit & Assurance, ROC Compliances, Due Diligence, and Business Advisory. We believe in combining strong technical expertise with practical business understanding to deliver value-driven solutions to our clients. Our team is committed to maintaining the highest standards of professionalism, integrity, and continuous learning while providing article assistants with hands-on exposure to real-world assignments and direct client interaction.','Proprietorship','2010','Accounting , GST , Income Tax , TDS , MSME , Statutory Audit , Income Tax Audit , Due diligence , Corporate affairs & ROC Work.','Manufacturing, Traders, Jewellers , Education,  Salaried , Retailers , Sevice sectors , Freelancers , Hospitals','[]',NULL,NULL,'https://www.linkedin.com/company/81309288/admin/dashboard/',NULL,NULL,NULL,'[{\"name\": \"Siddhi\", \"role\": \"Manager\", \"phone\": \"9012490624\"}]'),(27,95,NULL,0,NULL,NULL,'M R O & Associates','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,'2026-06-24 10:05:59','2026-06-24 10:05:59','pending',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),(28,96,'126769W',0,NULL,NULL,'MUTTHA AND LAHOTI','PUNE','A-14/15/16, Dr. Herekar Park, Off. Bhandarkar Road, Decaan Gymkhana, Pune-411004','CA SMITA LAHOTI',5,8,25,'[\"overall\"]','https://in.linkedin.com/company/muttha-lahoti-chartered-accountants','https://mlca.in','firm/logo/1782277089_logo.png',0,'2026-06-24 10:19:45','2026-06-24 11:44:43','approved',NULL,'Muttha & Lahoti Chartered Accountants is a Pune-based Chartered Accountancy firm providing comprehensive professional services in the areas of audit, taxation, regulatory compliance, and business advisory. Established through the professional journey of its founder partners commencing in 1999, the firm has grown into a multidisciplinary practice serving clients across diverse industries and business sectors. The firm is headquartered in Pune with a branch office at Solapur and is supported by a team of over 40 professionals.\r\n\r\nThe firm offers a wide range of services including:\r\n\r\nAudit & Assurance Services (Statutory Audits, Internal Audits, Bank Audits and Special Purpose Audits)\r\nDirect Tax Advisory, Compliance & Litigation\r\nGST and Indirect Tax Advisory, Compliance & Litigation\r\nRERA Compliance, Advisory & Litigation\r\nCompany Law and Corporate Compliance Services\r\nTransaction Support Services\r\nBusiness Structuring and Valuation Services\r\nEstate and Trust Planning Services\r\n\r\nMuttha & Lahoti is committed to delivering practical, value-driven, and timely solutions to complex business and regulatory challenges. The firm\'s approach is founded on the core values of Integrity, Togetherness, and Wisdom, enabling it to provide clients with reliable professional guidance and long-term business support.\r\n\r\nWith experienced leadership, continuous professional development, and a client-centric service philosophy, the firm has built long-standing relationships with businesses, entrepreneurs, financial institutions, trusts, and professionals across various sectors. Its focus on quality, responsiveness, and technical excellence has helped establish Muttha & Lahoti as a trusted professional advisory firm in Western India.','Partnership','2005','Audit & Assurance Services (Statutory Audits, Internal Audits, Bank Audits and Special Purpose Audits)\r\nDirect Tax Advisory, Compliance & Litigation\r\nGST and Indirect Tax Advisory, Compliance & Litigation\r\nRERA Compliance, Advisory & Litigation\r\nCompany Law and Corporate Compliance Services\r\nTransaction Support Services\r\nBusiness Structuring and Valuation Services\r\nEstate and Trust Planning Services','Real Estate & Construction\r\nHealthcare & Hospitals\r\nBanking & Financial Services\r\nNon-Banking Financial Companies (NBFCs)\r\nShare Broking & Capital Markets\r\nManufacturing & Engineering\r\nInformation Technology (IT) & IT Enabled Services (ITES)\r\nInfrastructure & EPC Contractors\r\nExporters & International Trade Businesses\r\nProfessional & Consultancy Services\r\nRetail & Trading Enterprises\r\nHospitality, Hotels & Restaurants\r\nEducation & Training Institutions\r\nLogistics & Transportation\r\nPharmaceutical & Life Sciences\r\nTrusts, NGOs & Charitable Institutions\r\nCo-operative Societies & Housing Societies\r\nStart-ups & Emerging Businesses\r\nFamily-Owned Businesses & SMEs\r\nPartnership Firms, LLPs & Private Limited Companies','[]',NULL,NULL,NULL,'https://www.facebook.com/mutthalahoti/',NULL,NULL,'[{\"name\": \"CA PRANJAL SARSANDE\", \"role\": \"Partner\", \"phone\": \"9823252959\"}]'),(29,97,'0100868W',0,NULL,NULL,'ARTH & Associates','PUNE','3rd Floor, Krishna Chambers, Pashan-Sus\r\nRoad, PUNE, Maharashtra, 411021','Ankush Mane',6,NULL,NULL,'[\"Internal Audit\",\"Statutory Audit\",\"Tax Audit\",\"GST & Indirect Tax\",\"Direct Tax\",\"Transfer Pricing\",\"Accounting & Bookkeeping\",\"Advisory & Consulting\",\"Bank & Concurrent Audit\",\"Forensic Audit & Investigation\",\"Risk & Internal Controls\",\"Payroll Services\",\"Due Diligence\",\"Certification Services\",\"Virtual CFO Services\",\"NGO / Trust Audit\"]','https://www.linkedin.com/company/arth-associates/?viewAsMember=true','https://www.arth.net.in',NULL,0,'2026-06-24 11:13:55','2026-06-24 11:32:30','approved',NULL,'ARTH and Associates Chartered Accountants was established in the Year 1978. The Firm today has offices at Pune, Mumbai and Akola. The Firm has been formed by qualified professionals who have extensive experience working with large Multinational Companies and Consulting Firms. The Firm has 6 experienced partners driven by strong commitment towards professional excellence, integrity and value driven client service. Firm is working in more than 20 different industries across different segments.  \r\n\r\nThe Firm provides a complete suite of services including:\r\n\r\nAudit Services\r\nRisk Management and Internal Audits\r\nBank and CAG Empanelled Audits\r\nFinancial Accounting and Advisory Services\r\nDirect and Indirect Taxation Advisory and Compliance Services\r\nSupport Services w.r.t. debt resolution','Partnership','1978','Internal audit,\r\nStatutory audit,\r\nTax audit,\r\nTransfer pricing,\r\nDirect Tax advisory and compliances,\r\nIndirect Tax advisory and compliances,\r\nAccounting support\r\nPayroll and compliances support\r\nRevenue leakages audit\r\nBank audits\r\nASM audits','Banking and NBFC,\r\nManufacturing & Engineering,\r\nIT/ITES,\r\nFood Processing,\r\nLogistics & Transport,\r\nPrecious metals,\r\nReal estate and EPC,\r\nAuto Dealership,\r\nInfra and metro rail projects,\r\nRobotics and automations,\r\nAviation,\r\nAerospace,\r\nPetroleum & Energy,\r\nIndustrial park development etc.','[]',NULL,NULL,NULL,NULL,NULL,NULL,'[{\"name\": \"\", \"role\": \"\", \"phone\": \"\"}]');
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
INSERT INTO `jobs` VALUES (1,16,'Qualified CA - Finalisation of Accounts (Shipping & Logistics)','MUMBAI','qualified','Accounting & Bookkeeping','On-site','1-3 Years',2,'[\"ERP, analytical skills, accounting, auditing, finalisation of books\"]',NULL,'CA qualified, M.com, Bcom',NULL,'60000-75000','Key Responsibilities\n1. Financial Reporting & Accounting\n• Assist in preparation and finalization of financial statements\n• Support month-end and year-end closing activities\n• Prepare schedules, reconciliations, and management reports\n• Ensure compliance with accounting standards and internal policies\n2. Audit Support\n• Coordinate with statutory, internal, and external auditors\n• Prepare audit schedules and supporting documentation\n• Ensure timely submission of information and responses to audit queries\n• Assist in implementation of audit recommendations\n3. ERP Implementation\n• Support implementation and rollout of ERP systems\n• Participate in master data validation and data migration activities\n• Assist in process documentation and system testing\n• Coordinate with internal stakeholders during ERP projects\n4. Compliance & Process Improvement\n• Assist in maintaining accounting controls and procedures\n• Support process optimization initiatives\n• Ensure accurate record-keeping and documentation','qualified',1,'Active','2026-06-19 11:41:27','2026-06-19 11:41:27'),(2,16,'Semi-Qualified CA - Finalisation of books( shipping & logistics)','MUMBAI','semi-qualified','Accounting & Bookkeeping','On-site','1-3 Years',2,'[\"Analytical skills, ledger scrutiny, finalisation of accounts, reconciliation, ERP\"]',NULL,'CA inter (cleared any one group), Bcom, Mcom',NULL,'35000-50000','Key Responsibilities\n1. Financial Reporting & Accounting\n• Assist in preparation and finalization of financial statements\n• Support month-end and year-end closing activities\n• Prepare schedules, reconciliations, and management reports\n• Ensure compliance with accounting standards and internal policies\n2. Audit Support\n• Coordinate with statutory, internal, and external auditors\n• Prepare audit schedules and supporting documentation\n• Ensure timely submission of information and responses to audit queries\n• Assist in implementation of audit recommendations\n3. ERP Implementation\n• Support implementation and rollout of ERP systems\n• Participate in master data validation and data migration activities\n• Assist in process documentation and system testing\n• Coordinate with internal stakeholders during ERP projects\n4. Compliance & Process Improvement\n• Assist in maintaining accounting controls and procedures\n• Support process optimization initiatives\n• Ensure accurate record-keeping and documentation','semi-qualified',1,'Active','2026-06-19 11:44:31','2026-06-19 11:44:31'),(3,18,'Articleship Trainee - Accounts, GST & Direct Tax','PUNE','articleship','GST & Indirect Tax','On-site','Fresher',2,'[\"GST\",\"Income Tax\",\"Zoho Books\",\"Tally\",\"Accounts\",\"Audit\",\"Compliance\"]',NULL,'CA Inter Cleared.',NULL,'10000','Vacancies for Article Assistants - Opportunity to gain exposure in core areas of professional practice including taxation, accounts, compliance, litigation. \nKey Areas of Exposure- Direct Tax - Indirect Tax - Accounting & Financial Reporting - Regulatory Compliances etc. \nInterested candidates can send their resume on ca.sunil.wadhwani@gmail.com','articleship',1,'Active','2026-06-21 14:58:57','2026-06-21 14:58:57'),(4,24,'Articleship Trainee - GST & Indirect Tax','PUNE','articleship','GST & Indirect Tax','On-site','Fresher',2,'[\"Time Management\",\"Communication Skills\",\"Adaptability\",\"Analytical Thinking\",\"Attention to Detail\"]',NULL,'CA Inter both group Cleared',NULL,'12000','Assisted in statutory audits, tax audits, and internal audits of various clients.\nPrepared income tax and GST returns, reconciliations, and compliance reports.\nMaintained accounting records and prepared financial statements.\nCoordinated with clients for documentation and regulatory compliance requirements.\nPerformed financial analysis and prepared management reports.\nEnsured timely completion of assignments while adhering to professional standards.','articleship',1,'Active','2026-06-23 11:18:41','2026-06-23 11:18:41'),(5,24,'Qualified CA - Direct Tax','PUNE','qualified','Direct Tax','On-site','3+ Years',1,'[\"GST Notice Handling & Departmental Replies\",\"Client Coordination & Communication\",\"Team Management & Review\",\"Problem-Solving & Analytical Skills\",\"Ownership and Independent Working\",\"GST Advisory\"]',NULL,'Chartered Accountant (CA)',NULL,'70,000-75,000','This role is ideal for someone who have and need to enhance:\n\n✔ Practical exposure in GST compliance & advisory  \n✔ Direct client interaction  \n✔ Ownership and responsibility  \n✔ Long-term growth opportunity  \n\n𝗥𝗼𝗹𝗲 𝗜𝗻𝗰𝗹𝘂𝗱𝗲𝘀:\n• GST compliance & advisory  \n• GST notices & departmental replies  \n• Client coordination  \n• Team handling & review  \n• Practical problem-solving for businesses','qualified',1,'Active','2026-06-23 11:23:23','2026-06-23 11:23:23'),(6,29,'Qualified CA - Senior Audit Associates - Internal Audit','PUNE','qualified','Internal Audit','On-site','1-3 Years',2,'[\"Core process based internal audit experience  perferably with CA Firms\"]',NULL,'Qualified CA',NULL,NULL,'1. Execute internal audit assignments across various industries and business functions.\n2. Perform process walkthroughs, risk assessments, and control testing.\n3. Identify control gaps, process inefficiencies, and compliance issues.\n4. Prepare audit working papers, observations, and audit reports.\n5. Conduct reviews of finance, procurement, inventory, HR, payroll, sales, and operational processes.\n6. Verify compliance with company policies, statutory requirements, and regulatory guidelines.\n7. Follow up on implementation of audit recommendations and corrective actions.\n8. Assist in developing risk-based audit plans and audit programs.\n9. Coordinate with client management for obtaining information and resolving audit queries.\n10. Support special reviews, investigations, and management assignments as required.','qualified',1,'Active','2026-06-24 11:38:36','2026-06-24 11:38:36'),(7,25,'Articleship Trainee','PUNE','articleship',NULL,'On-site','Fresher',5,NULL,NULL,'CA Inter (both groups cleared)',NULL,'1st Year - 7,500/- & 2nd Year - 9,000/-','We are looking for a motivated CA Article Assistant to join our firm and gain comprehensive practical exposure in taxation, audit, accounting, and compliance.\n\nKey Responsibilities:\n• Income Tax Return Filing and Compliance\n• GST Return Filing, Assessments, and Notices\n• Tax Audit, Statutory Audit, Internal Audit, and Bank Audit\n• TDS Compliance and Return Filing\n• Accounting and Bookkeeping in Tally Prime\n• Financial Statement Preparation and Finalization\n• ROC and Company Law Compliance\n• Client Coordination and Departmental Representation\n\nRequirements:\n• CA Intermediate cleared/pursuing and eligible for Articleship\n• Basic knowledge of Accounting, Taxation, GST, and MS Excel\n• Good communication and analytical skills\n• Willingness to learn and take responsibility\n\nWhat We Offer:\n• End-to-end exposure to audit, taxation, accounting, and compliance assignments\n• Hands-on experience across diverse industries\n• Professional mentorship and career development\n\nLocation: Pune Camp\nStipend: As stated above\n\nInterested candidates may share their resume at hmistryandassociates@gmail.com','articleship',1,'Active','2026-06-24 12:35:59','2026-06-24 12:35:59'),(8,26,'Articleship Trainee','MUMBAI','articleship',NULL,'On-site','Fresher',2,'[\"Passion to learn and ready to take up responsibility\"]',NULL,'CA Inter (Both Group cleared)',NULL,'6000-7500','Article will get over all exposure in all fields and hands-on experience.',NULL,1,'Active','2026-06-24 13:25:19','2026-06-24 13:37:58');
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
) ENGINE=InnoDB AUTO_INCREMENT=367 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_history`
--

LOCK TABLES `login_history` WRITE;
/*!40000 ALTER TABLE `login_history` DISABLE KEYS */;
INSERT INTO `login_history` VALUES (13,3,'152.58.180.147','mobile','Chrome 149','Android 10','Kolkata, West Bengal, India','2026-06-10 20:36:27','2026-06-10 20:36:27'),(14,3,'152.58.180.147','mobile','Chrome 149','Android 10','Kolkata, West Bengal, India','2026-06-10 20:37:01','2026-06-10 20:37:01'),(15,3,'152.58.180.147','mobile','Chrome 149','Android 10','Kolkata, West Bengal, India','2026-06-10 20:41:08','2026-06-10 20:41:08'),(17,4,'157.32.198.213','mobile','Chrome 138','Android 9','Pune, Maharashtra, India','2026-06-11 13:15:34','2026-06-11 13:15:34'),(18,4,'157.32.198.213','mobile','Chrome 138','Android 9','Pune, Maharashtra, India','2026-06-11 13:15:50','2026-06-11 13:15:50'),(19,4,'157.32.198.213','mobile','Chrome 123','Android 9','Pune, Maharashtra, India','2026-06-11 13:18:19','2026-06-11 13:18:19'),(20,5,'152.58.44.210','mobile','Chrome 148','Android 10','Mumbai, Maharashtra, India','2026-06-11 22:30:47','2026-06-11 22:30:47'),(21,5,'152.58.44.210','mobile','Chrome 148','Android 10','Mumbai, Maharashtra, India','2026-06-11 22:31:22','2026-06-11 22:31:22'),(22,6,'157.32.113.113','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-12 14:31:21','2026-06-12 14:31:21'),(23,6,'157.32.113.113','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-12 14:32:11','2026-06-12 14:32:11'),(24,7,'110.227.185.177','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-12 14:39:58','2026-06-12 14:39:58'),(25,7,'110.227.185.177','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-12 14:48:25','2026-06-12 14:48:25'),(27,8,'27.97.174.133','mobile','Chrome 147','Android 10','Nagpur, Maharashtra, India','2026-06-12 17:02:15','2026-06-12 17:02:15'),(28,8,'27.97.174.133','mobile','Chrome 147','Android 10','Nagpur, Maharashtra, India','2026-06-12 17:03:53','2026-06-12 17:03:53'),(29,9,'27.97.180.154','mobile','Samsung Browser 29','Android 10','Amravati, Maharashtra, India','2026-06-12 17:16:17','2026-06-12 17:16:17'),(30,9,'27.97.180.154','mobile','Samsung Browser 29','Android 10','Amravati, Maharashtra, India','2026-06-12 17:16:46','2026-06-12 17:16:46'),(34,10,'106.213.86.255','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-12 17:28:55','2026-06-12 17:28:55'),(47,12,'106.210.227.201','mobile','Chrome 148','Android 10','Kātol, Maharashtra, India','2026-06-14 10:49:26','2026-06-14 10:49:26'),(48,12,'106.210.227.201','mobile','Chrome 148','Android 10','Kātol, Maharashtra, India','2026-06-14 10:50:07','2026-06-14 10:50:07'),(92,14,'152.57.156.135','mobile','Chrome 149','Android 10','Hyderabad, Telangana, India','2026-06-15 12:24:49','2026-06-15 12:24:49'),(124,15,'182.69.179.145','desktop','Chrome 147','Windows 10 / 11','New Delhi, National Capital Territory of Delhi, India','2026-06-15 23:59:24','2026-06-15 23:59:24'),(125,15,'182.69.179.145','desktop','Chrome 147','Windows 10 / 11','New Delhi, National Capital Territory of Delhi, India','2026-06-16 00:00:17','2026-06-16 00:00:17'),(126,16,'103.49.254.7','desktop','Edge 149','Windows 10 / 11','Badlapur, Maharashtra, India','2026-06-16 00:33:58','2026-06-16 00:33:58'),(133,17,'152.59.29.76','mobile','Chrome 148','Android 10','Jabalpur, Madhya Pradesh, India','2026-06-16 01:25:31','2026-06-16 01:25:31'),(139,18,'223.228.136.12','mobile','Safari 18','iOS 18.5','Pune, Maharashtra, India','2026-06-16 12:35:25','2026-06-16 12:35:25'),(140,18,'223.228.136.12','mobile','Safari 18','iOS 18.5','Pune, Maharashtra, India','2026-06-16 12:36:05','2026-06-16 12:36:05'),(141,19,'103.160.175.18','desktop','Chrome 148','Windows 10 / 11','Pune, Maharashtra, India','2026-06-16 12:37:09','2026-06-16 12:37:09'),(146,21,'106.221.220.177','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 13:40:13','2026-06-16 13:40:13'),(147,21,'106.221.220.177','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 13:40:42','2026-06-16 13:40:42'),(149,22,'103.174.77.243','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-16 14:03:38','2026-06-16 14:03:38'),(150,23,'42.104.216.11','mobile','Unknown Browser','iOS 26.2.0','Pune, Maharashtra, India','2026-06-16 14:05:50','2026-06-16 14:05:50'),(151,24,'106.193.199.173','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 14:17:30','2026-06-16 14:17:30'),(152,24,'106.193.199.173','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 14:18:49','2026-06-16 14:18:49'),(154,22,'103.174.77.243','desktop','Edge 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-16 14:25:49','2026-06-16 14:25:49'),(155,25,'103.22.140.214','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 14:52:53','2026-06-16 14:52:53'),(156,25,'103.22.140.214','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 14:56:50','2026-06-16 14:56:50'),(159,26,'103.250.190.58','desktop','Chrome 149','Windows 10 / 11','Rajkot, Gujarat, India','2026-06-16 17:31:56','2026-06-16 17:31:56'),(160,27,'103.204.38.118','desktop','Chrome 148','macOS 10.15.7','Pune, Maharashtra, India','2026-06-16 17:37:52','2026-06-16 17:37:52'),(161,27,'103.204.38.118','desktop','Chrome 148','macOS 10.15.7','Pune, Maharashtra, India','2026-06-16 17:38:19','2026-06-16 17:38:19'),(164,26,'103.250.190.58','desktop','Chrome 149','Windows 10 / 11','Rajkot, Gujarat, India','2026-06-16 18:33:55','2026-06-16 18:33:55'),(165,30,'110.227.185.177','mobile','Chrome 148','Android 16','Pune, Maharashtra, India','2026-06-16 18:35:15','2026-06-16 18:35:15'),(166,30,'110.227.185.177','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 18:35:41','2026-06-16 18:35:41'),(167,31,'110.227.185.177','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-16 18:52:52','2026-06-16 18:52:52'),(168,32,'122.161.243.76','mobile','Chrome 148','Android 10','Jammu, Jammu and Kashmir, India','2026-06-16 18:57:49','2026-06-16 18:57:49'),(169,32,'122.161.243.76','mobile','Chrome 148','Android 10','Jammu, Jammu and Kashmir, India','2026-06-16 18:58:09','2026-06-16 18:58:09'),(170,33,'106.220.141.136','mobile','Chrome 148','Android 10','Pune, Maharashtra, India','2026-06-16 18:58:29','2026-06-16 18:58:29'),(171,33,'106.220.141.136','mobile','Chrome 148','Android 10','Pune, Maharashtra, India','2026-06-16 19:00:05','2026-06-16 19:00:05'),(172,34,'103.159.35.112','mobile','Chrome 149','Android 10','Delhi, National Capital Territory of Delhi, India','2026-06-16 19:10:56','2026-06-16 19:10:56'),(173,34,'103.159.35.112','mobile','Chrome 149','Android 10','Delhi, National Capital Territory of Delhi, India','2026-06-16 19:11:26','2026-06-16 19:11:26'),(174,35,'152.56.6.27','mobile','Chrome 146','Android 10','Aurangabad, Maharashtra, India','2026-06-16 19:32:21','2026-06-16 19:32:21'),(175,35,'152.56.6.27','mobile','Chrome 146','Android 10','Aurangabad, Maharashtra, India','2026-06-16 19:33:08','2026-06-16 19:33:08'),(176,37,'152.58.36.111','mobile','Chrome 149','Android 15','Surat, Gujarat, India','2026-06-16 20:33:17','2026-06-16 20:33:17'),(177,38,'157.32.218.38','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 20:33:38','2026-06-16 20:33:38'),(178,38,'157.32.218.38','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 20:34:06','2026-06-16 20:34:06'),(179,37,'152.58.36.111','mobile','Chrome 149','Android 15','Surat, Gujarat, India','2026-06-16 20:34:19','2026-06-16 20:34:19'),(180,37,'152.58.36.111','mobile','Chrome 149','Android 15','Surat, Gujarat, India','2026-06-16 20:34:37','2026-06-16 20:34:37'),(181,39,'152.59.63.54','mobile','Chrome 148','Android 10','Pune, Maharashtra, India','2026-06-16 20:35:19','2026-06-16 20:35:19'),(182,39,'152.59.63.54','mobile','Chrome 148','Android 10','Pune, Maharashtra, India','2026-06-16 20:36:34','2026-06-16 20:36:34'),(183,40,'152.58.33.95','mobile','Safari 26','iOS 18.7','Pune, Maharashtra, India','2026-06-16 20:41:00','2026-06-16 20:41:00'),(184,40,'152.58.33.181','mobile','Safari 26','iOS 18.7','Pune, Maharashtra, India','2026-06-16 20:42:11','2026-06-16 20:42:11'),(185,39,'152.59.63.54','mobile','Chrome 148','Android 10','Pune, Maharashtra, India','2026-06-16 20:45:21','2026-06-16 20:45:21'),(186,39,'152.59.63.54','mobile','Chrome 148','Android 10','Pune, Maharashtra, India','2026-06-16 20:47:38','2026-06-16 20:47:38'),(187,39,'152.59.63.54','mobile','Chrome 148','Android 10','Pune, Maharashtra, India','2026-06-16 20:49:37','2026-06-16 20:49:37'),(188,41,'223.233.83.212','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 20:49:59','2026-06-16 20:49:59'),(189,41,'223.233.83.212','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 20:50:51','2026-06-16 20:50:51'),(190,42,'152.59.63.212','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 21:15:59','2026-06-16 21:15:59'),(191,42,'152.59.63.212','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 21:16:24','2026-06-16 21:16:24'),(192,42,'152.59.63.212','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 21:18:37','2026-06-16 21:18:37'),(193,22,'152.58.17.121','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 21:37:49','2026-06-16 21:37:49'),(194,43,'49.36.51.198','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 23:26:34','2026-06-16 23:26:34'),(195,43,'49.36.51.198','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-16 23:28:41','2026-06-16 23:28:41'),(197,44,'152.58.33.55','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-17 07:14:58','2026-06-17 07:14:58'),(198,44,'152.58.33.55','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-17 07:15:36','2026-06-17 07:15:36'),(199,45,'103.97.242.194','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-17 08:09:31','2026-06-17 08:09:31'),(200,46,'152.58.32.118','mobile','Chrome 148','Android 10','Pune, Maharashtra, India','2026-06-17 09:11:45','2026-06-17 09:11:45'),(201,46,'152.58.32.118','mobile','Chrome 148','Android 10','Pune, Maharashtra, India','2026-06-17 09:12:20','2026-06-17 09:12:20'),(202,46,'152.58.32.118','mobile','Chrome 148','Android 10','Pune, Maharashtra, India','2026-06-17 09:19:34','2026-06-17 09:19:34'),(203,26,'103.250.190.58','desktop','Chrome 149','Windows 10 / 11','Rajkot, Gujarat, India','2026-06-17 09:43:02','2026-06-17 09:43:02'),(204,47,'171.61.113.230','mobile','Chrome 149','Android 10','Jabalpur, Madhya Pradesh, India','2026-06-17 09:43:51','2026-06-17 09:43:51'),(205,47,'171.61.113.230','mobile','Chrome 149','Android 10','Jabalpur, Madhya Pradesh, India','2026-06-17 09:44:38','2026-06-17 09:44:38'),(206,48,'106.192.220.249','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-17 09:57:11','2026-06-17 09:57:11'),(207,48,'106.192.220.249','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-17 09:57:59','2026-06-17 09:57:59'),(208,49,'152.58.16.172','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-17 10:03:37','2026-06-17 10:03:37'),(209,49,'152.58.16.172','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-17 10:04:00','2026-06-17 10:04:00'),(211,50,'223.181.81.192','mobile','Safari 26','iOS 18.7','Raipur, Chhattisgarh, India','2026-06-17 11:06:10','2026-06-17 11:06:10'),(212,52,'59.184.16.24','mobile','Chrome 149','Android 10','Nandurbar, Maharashtra, India','2026-06-17 11:23:29','2026-06-17 11:23:29'),(213,52,'59.184.16.24','mobile','Chrome 149','Android 10','Nandurbar, Maharashtra, India','2026-06-17 11:23:58','2026-06-17 11:23:58'),(214,53,'223.185.41.135','mobile','Chrome 149','Android 10','Nagpur, Maharashtra, India','2026-06-17 11:28:14','2026-06-17 11:28:14'),(215,53,'223.185.41.135','mobile','Chrome 149','Android 10','Nagpur, Maharashtra, India','2026-06-17 11:28:44','2026-06-17 11:28:44'),(216,53,'223.185.41.135','mobile','Chrome 149','Android 10','Nagpur, Maharashtra, India','2026-06-17 11:29:53','2026-06-17 11:29:53'),(217,52,'59.184.16.24','mobile','Chrome 149','Android 10','Nandurbar, Maharashtra, India','2026-06-17 11:57:41','2026-06-17 11:57:41'),(218,54,'110.227.185.177','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-17 12:16:49','2026-06-17 12:16:49'),(221,55,'14.96.212.78','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-17 12:35:17','2026-06-17 12:35:17'),(222,55,'14.96.212.78','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-17 12:36:11','2026-06-17 12:36:11'),(223,54,'110.227.185.177','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-17 13:01:00','2026-06-17 13:01:00'),(224,56,'58.84.63.27','desktop','Chrome 109','Windows 8.1','Pune, Maharashtra, India','2026-06-17 13:28:58','2026-06-17 13:28:58'),(225,5,'152.58.47.9','mobile','Chrome 149','Android 10','Mumbai, Maharashtra, India','2026-06-17 14:12:12','2026-06-17 14:12:12'),(227,57,'106.192.114.113','mobile','Chrome 149','Android 14','Pune, Maharashtra, India','2026-06-17 19:03:02','2026-06-17 19:03:02'),(228,58,'152.56.14.156','mobile','Chrome 149','Android 10','Nagpur, Maharashtra, India','2026-06-17 20:04:16','2026-06-17 20:04:16'),(229,59,'103.216.147.88','desktop','Chrome 149','Windows 10 / 11','Latur, Maharashtra, India','2026-06-17 20:26:04','2026-06-17 20:26:04'),(230,60,'103.216.147.88','desktop','Chrome 149','Windows 10 / 11','Latur, Maharashtra, India','2026-06-17 20:28:53','2026-06-17 20:28:53'),(239,62,'49.36.48.61','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-17 22:57:59','2026-06-17 22:57:59'),(240,62,'49.36.48.61','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-17 22:58:28','2026-06-17 22:58:28'),(241,63,'152.58.33.15','desktop','Chrome 148','Windows 10 / 11','Pune, Maharashtra, India','2026-06-17 23:18:58','2026-06-17 23:18:58'),(242,64,'42.108.239.21','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-17 23:26:45','2026-06-17 23:26:45'),(243,64,'42.108.239.21','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-17 23:27:14','2026-06-17 23:27:14'),(249,67,'152.59.13.185','mobile','Chrome 149','Android 10','Chiplūn, Maharashtra, India','2026-06-18 13:08:05','2026-06-18 13:08:05'),(250,67,'152.59.13.185','mobile','Chrome 149','Android 10','Chiplūn, Maharashtra, India','2026-06-18 13:08:59','2026-06-18 13:08:59'),(251,67,'152.59.13.9','mobile','Chrome 149','Android 10','Chiplūn, Maharashtra, India','2026-06-18 13:13:44','2026-06-18 13:13:44'),(252,27,'103.204.38.118','desktop','Chrome 148','macOS 10.15.7','Pune, Maharashtra, India','2026-06-18 13:54:36','2026-06-18 13:54:36'),(253,69,'223.233.83.254','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-18 15:04:14','2026-06-18 15:04:14'),(254,69,'223.233.83.254','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-18 15:04:40','2026-06-18 15:04:40'),(255,69,'223.233.83.254','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-18 15:05:17','2026-06-18 15:05:17'),(263,71,'150.129.159.74','desktop','Chrome 148','Windows 10 / 11','Pune, Maharashtra, India','2026-06-18 18:01:45','2026-06-18 18:01:45'),(264,71,'150.129.159.74','desktop','Chrome 148','Windows 10 / 11','Pune, Maharashtra, India','2026-06-18 18:08:06','2026-06-18 18:08:06'),(265,71,'150.129.159.74','desktop','Chrome 148','Windows 10 / 11','Pune, Maharashtra, India','2026-06-18 18:24:35','2026-06-18 18:24:35'),(266,72,'223.185.36.47','desktop','Chrome 148','Windows 10 / 11','Nagpur, Maharashtra, India','2026-06-18 18:40:35','2026-06-18 18:40:35'),(267,72,'223.185.36.47','desktop','Chrome 148','Windows 10 / 11','Nagpur, Maharashtra, India','2026-06-18 18:41:05','2026-06-18 18:41:05'),(268,73,'42.108.75.159','mobile','Chrome 149','Android 10','Mumbai, Maharashtra, India','2026-06-18 19:34:31','2026-06-18 19:34:31'),(269,73,'42.108.75.159','mobile','Chrome 149','Android 10','Mumbai, Maharashtra, India','2026-06-18 19:36:19','2026-06-18 19:36:19'),(270,73,'42.108.75.159','mobile','Chrome 149','Android 10','Mumbai, Maharashtra, India','2026-06-18 19:39:05','2026-06-18 19:39:05'),(271,73,'42.108.75.159','mobile','Chrome 149','Android 10','Mumbai, Maharashtra, India','2026-06-18 20:10:30','2026-06-18 20:10:30'),(272,74,'103.235.122.96','mobile','Chrome 149','Android 10','Mumbai, Maharashtra, India','2026-06-18 20:28:59','2026-06-18 20:28:59'),(273,74,'103.235.122.96','mobile','Chrome 149','Android 10','Mumbai, Maharashtra, India','2026-06-18 20:29:28','2026-06-18 20:29:28'),(274,74,'103.235.122.96','mobile','Chrome 149','Android 10','Mumbai, Maharashtra, India','2026-06-18 20:37:26','2026-06-18 20:37:26'),(275,74,'103.235.122.96','mobile','Chrome 149','Android 10','Mumbai, Maharashtra, India','2026-06-18 20:41:43','2026-06-18 20:41:43'),(277,74,'103.235.122.96','mobile','Chrome 149','Android 10','Mumbai, Maharashtra, India','2026-06-19 02:13:01','2026-06-19 02:13:01'),(278,74,'103.235.122.96','mobile','Chrome 149','Android 10','Mumbai, Maharashtra, India','2026-06-19 09:59:05','2026-06-19 09:59:05'),(279,74,'42.108.72.9','mobile','Chrome 149','Android 10','Mumbai, Maharashtra, India','2026-06-19 11:36:42','2026-06-19 11:36:42'),(280,74,'42.108.72.9','mobile','Chrome 149','Android 10','Mumbai, Maharashtra, India','2026-06-19 12:07:41','2026-06-19 12:07:41'),(281,74,'103.58.4.75','desktop','Chrome 148','Windows 10 / 11','Mumbai, Maharashtra, India','2026-06-19 14:17:44','2026-06-19 14:17:44'),(282,76,'49.36.41.206','desktop','Firefox 152','Windows 10 / 11','Nagpur, Maharashtra, India','2026-06-19 17:53:09','2026-06-19 17:53:09'),(283,76,'49.36.41.206','desktop','Firefox 152','Windows 10 / 11','Nagpur, Maharashtra, India','2026-06-19 18:04:33','2026-06-19 18:04:33'),(284,67,'117.229.132.125','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-19 18:33:41','2026-06-19 18:33:41'),(289,77,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-20 10:37:33','2026-06-20 10:37:33'),(290,78,'223.228.38.21','mobile','Samsung Browser 30','Android 10','Pune, Maharashtra, India','2026-06-20 10:59:39','2026-06-20 10:59:39'),(291,78,'223.228.38.21','mobile','Samsung Browser 30','Android 10','Pune, Maharashtra, India','2026-06-20 11:00:05','2026-06-20 11:00:05'),(292,79,'219.91.251.56','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-20 11:37:21','2026-06-20 11:37:21'),(297,81,'106.200.122.147','desktop','Chrome 115','Linux','Mumbai, Maharashtra, India','2026-06-20 15:00:13','2026-06-20 15:00:13'),(298,81,'106.200.122.147','desktop','Chrome 115','Linux','Mumbai, Maharashtra, India','2026-06-20 15:01:01','2026-06-20 15:01:01'),(300,79,'219.91.251.56','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-20 16:39:53','2026-06-20 16:39:53'),(301,82,'152.58.31.62','mobile','Unknown Browser','iOS 26.2.1','Pune, Maharashtra, India','2026-06-20 18:11:31','2026-06-20 18:11:31'),(309,64,'42.108.238.109','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-20 19:57:38','2026-06-20 19:57:38'),(311,39,'152.58.16.89','mobile','Chrome 148','Android 10','Pune, Maharashtra, India','2026-06-21 00:39:12','2026-06-21 00:39:12'),(312,39,'152.58.16.89','mobile','Chrome 148','Android 10','Pune, Maharashtra, India','2026-06-21 00:39:33','2026-06-21 00:39:33'),(315,83,'152.59.63.82','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-21 14:50:00','2026-06-21 14:50:00'),(316,83,'152.59.63.82','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-21 14:50:35','2026-06-21 14:50:35'),(317,79,'106.192.213.64','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-21 14:53:45','2026-06-21 14:53:45'),(318,84,'106.220.235.82','desktop','Chrome 149','Windows 10 / 11','Latur, Maharashtra, India','2026-06-21 15:45:05','2026-06-21 15:45:05'),(319,85,'152.58.33.48','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-21 16:03:04','2026-06-21 16:03:04'),(323,88,'123.201.192.28','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-22 12:25:09','2026-06-22 12:25:09'),(324,88,'123.201.192.28','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-22 12:26:01','2026-06-22 12:26:01'),(325,86,'152.58.17.5','mobile','Safari 26','iOS 18.7','Pune, Maharashtra, India','2026-06-22 13:33:15','2026-06-22 13:33:15'),(326,86,'152.58.17.5','mobile','Safari 26','iOS 18.7','Pune, Maharashtra, India','2026-06-22 14:34:16','2026-06-22 14:34:16'),(327,88,'123.201.192.28','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-22 15:33:04','2026-06-22 15:33:04'),(328,3,'152.56.157.78','mobile','Chrome 149','Android 10','Kolkata, West Bengal, India','2026-06-22 19:47:05','2026-06-22 19:47:05'),(329,89,'42.107.82.14','mobile','Samsung Browser 30','Android 10','Pune, Maharashtra, India','2026-06-22 20:28:54','2026-06-22 20:28:54'),(334,50,'106.202.76.139','mobile','Unknown Browser','iOS 26.4.2','Indore, Madhya Pradesh, India','2026-06-22 23:12:06','2026-06-22 23:12:06'),(335,90,'49.34.225.82','mobile','Chrome 149','Android 12','Ahmedabad, Gujarat, India','2026-06-23 01:13:22','2026-06-23 01:13:22'),(336,90,'49.34.225.82','mobile','Chrome 123','Android 12','Ahmedabad, Gujarat, India','2026-06-23 01:15:01','2026-06-23 01:15:01'),(337,88,'123.201.192.28','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-23 09:47:15','2026-06-23 09:47:15'),(338,91,'106.215.178.95','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-23 19:07:45','2026-06-23 19:07:45'),(339,92,'223.228.54.43','mobile','Unknown Browser','iOS 26.5.0','Mumbai, Maharashtra, India','2026-06-23 20:58:10','2026-06-23 20:58:10'),(340,92,'223.228.54.43','mobile','Unknown Browser','iOS 26.5.0','Mumbai, Maharashtra, India','2026-06-23 20:58:38','2026-06-23 20:58:38'),(341,17,'152.56.245.82','mobile','Chrome 148','Android 10','Jabalpur, Madhya Pradesh, India','2026-06-23 23:36:28','2026-06-23 23:36:28'),(342,93,'157.48.99.187','mobile','Samsung Browser 30','Android 10','Jaipur, Rajasthan, India','2026-06-23 23:49:38','2026-06-23 23:49:38'),(343,93,'157.48.99.187','mobile','Samsung Browser 30','Android 10','Jaipur, Rajasthan, India','2026-06-23 23:49:57','2026-06-23 23:49:57'),(344,83,'47.11.6.176','mobile','Chrome 149','Android 10','Nagpur, Maharashtra, India','2026-06-24 08:57:07','2026-06-24 08:57:07'),(345,5,'152.58.0.250','mobile','Chrome 149','Android 10','Mumbai, Maharashtra, India','2026-06-24 09:05:01','2026-06-24 09:05:01'),(346,83,'47.11.1.77','mobile','Chrome 149','Android 10','Nagpur, Maharashtra, India','2026-06-24 09:09:44','2026-06-24 09:09:44'),(347,94,'47.11.1.77','mobile','Chrome 149','Android 10','Nagpur, Maharashtra, India','2026-06-24 09:15:58','2026-06-24 09:15:58'),(348,91,'106.214.47.9','desktop','Chrome 148','Windows 10 / 11','Pune, Maharashtra, India','2026-06-24 09:48:51','2026-06-24 09:48:51'),(349,91,'106.215.178.95','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-24 09:54:21','2026-06-24 09:54:21'),(350,91,'106.215.178.95','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-24 09:56:46','2026-06-24 09:56:46'),(351,95,'106.220.218.217','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-24 10:06:19','2026-06-24 10:06:19'),(352,96,'117.248.203.68','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-24 10:19:57','2026-06-24 10:19:57'),(353,96,'117.248.203.68','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-24 10:20:15','2026-06-24 10:20:15'),(356,97,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-24 11:14:26','2026-06-24 11:14:26'),(357,92,'223.181.59.222','desktop','Chrome 149','Windows 10 / 11','Mumbai, Maharashtra, India','2026-06-24 11:38:01','2026-06-24 11:38:01'),(358,62,'152.58.32.210','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-24 12:00:50','2026-06-24 12:00:50'),(359,92,'223.181.59.222','desktop','Chrome 149','Windows 10 / 11','Mumbai, Maharashtra, India','2026-06-24 12:08:13','2026-06-24 12:08:13'),(360,98,'157.119.201.150','desktop','Chrome 148','Windows 10 / 11','Mumbai, Maharashtra, India','2026-06-24 12:35:24','2026-06-24 12:35:24'),(361,98,'157.119.201.150','desktop','Chrome 148','Windows 10 / 11','Mumbai, Maharashtra, India','2026-06-24 12:35:48','2026-06-24 12:35:48'),(362,98,'157.119.201.150','desktop','Chrome 148','Windows 10 / 11','Mumbai, Maharashtra, India','2026-06-24 12:46:03','2026-06-24 12:46:03'),(363,97,'103.160.175.18','desktop','Chrome 149','Windows 10 / 11','Pune, Maharashtra, India','2026-06-24 12:46:36','2026-06-24 12:46:36'),(364,92,'223.181.59.222','desktop','Chrome 149','Windows 10 / 11','Mumbai, Maharashtra, India','2026-06-24 13:08:44','2026-06-24 13:08:44'),(365,92,'223.181.59.222','desktop','Chrome 149','Windows 10 / 11','Mumbai, Maharashtra, India','2026-06-24 13:09:44','2026-06-24 13:09:44'),(366,67,'117.229.145.76','mobile','Chrome 149','Android 10','Pune, Maharashtra, India','2026-06-24 14:30:31','2026-06-24 14:30:31');
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
INSERT INTO `platform_settings` VALUES (1,'show_companies_to_students','true',NULL,3,'2026-06-24 11:42:34'),(2,'show_students_to_firms','true',NULL,3,'2026-06-24 11:32:57'),(3,'online_payments_enabled','false',NULL,3,'2026-06-15 12:48:05');
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
) ENGINE=InnoDB AUTO_INCREMENT=1789 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recruiter_actions`
--

LOCK TABLES `recruiter_actions` WRITE;
/*!40000 ALTER TABLE `recruiter_actions` DISABLE KEYS */;
INSERT INTO `recruiter_actions` VALUES (1,1,4,'student',NULL,NULL,'profile_viewed','Profile viewed','Test viewed your profile.','viewed',NULL,NULL,NULL,0,'2026-06-11 15:50:32'),(2,1,3,'student',NULL,NULL,'profile_viewed','Profile viewed','Test viewed your profile.','viewed',NULL,NULL,NULL,0,'2026-06-11 17:03:16'),(3,1,8,'student',NULL,NULL,'profile_viewed','Profile viewed','Test viewed your profile.','viewed',NULL,NULL,NULL,0,'2026-06-12 17:20:51'),(4,1,10,'student',NULL,NULL,'profile_viewed','Profile viewed','Test viewed your profile.','viewed',NULL,NULL,NULL,0,'2026-06-12 23:10:59'),(5,1,6,'student',NULL,NULL,'profile_viewed','Profile viewed','Test viewed your profile.','viewed',NULL,NULL,NULL,0,'2026-06-12 23:12:06'),(6,1,63,'student',NULL,NULL,'profile_viewed','Profile viewed','Test viewed your profile.','viewed',NULL,NULL,NULL,0,'2026-06-24 11:04:43'),(7,25,31,'student',NULL,NULL,'profile_viewed','Profile viewed','H Mistry & Associates viewed your profile.','viewed',NULL,NULL,NULL,0,'2026-06-24 12:37:21'),(8,25,63,'student',NULL,NULL,'profile_viewed','Profile viewed','H Mistry & Associates viewed your profile.','viewed',NULL,NULL,NULL,0,'2026-06-24 12:37:39'),(9,25,30,'student',NULL,NULL,'profile_viewed','Profile viewed','H Mistry & Associates viewed your profile.','viewed',NULL,NULL,NULL,0,'2026-06-24 12:37:56'),(10,25,31,'student',NULL,NULL,'shortlisted','Profile shortlisted','H Mistry & Associates shortlisted your profile.','shortlisted',NULL,NULL,NULL,0,'2026-06-24 12:38:14');
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
-- Table structure for table `resume_templates`
--

DROP TABLE IF EXISTS `resume_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `resume_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `template_name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `template_key` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `html_content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `css_content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `preview_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `resume_templates_template_key_unique` (`template_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resume_templates`
--

LOCK TABLES `resume_templates` WRITE;
/*!40000 ALTER TABLE `resume_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `resume_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resumes`
--

DROP TABLE IF EXISTS `resumes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `resumes` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` bigint NOT NULL,
  `template_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'classic_professional',
  `resume_data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_resumes_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resumes`
--

LOCK TABLES `resumes` WRITE;
/*!40000 ALTER TABLE `resumes` DISABLE KEYS */;
/*!40000 ALTER TABLE `resumes` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_profiles`
--

LOCK TABLES `student_profiles` WRITE;
/*!40000 ALTER TABLE `student_profiles` DISABLE KEYS */;
INSERT INTO `student_profiles` VALUES (1,1,'articleship',0,NULL,'WRO0800459','PUNE','PUNE','male',NULL,'inter-g2','provisional',NULL,'[]',NULL,'[]',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ARTH & Associates',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,NULL,'2026-06-10 15:23:24','2026-06-12 20:29:50'),(2,3,'doing-articleship',0,NULL,'ERO0268744','KOLKATA','KOLKATA','female',NULL,'doing-articleship','provisional',NULL,'[]',NULL,'[]',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Komandoor & Co LLP',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-10 20:36:16','2026-06-10 20:41:59'),(3,4,'articleship',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-11 13:15:03','2026-06-11 13:15:03'),(4,5,'semi-qualified',0,NULL,'WRO0793585','KALYAN','KALYAN','male',NULL,NULL,'confirm',NULL,'[\"THANE\", \"MUMBAI\"]',NULL,'[\"Statutory Audit\", \"Tax Audit\", \"Direct Tax\", \"GST & Indirect Tax\", \"International Taxation\", \"Transfer Pricing\", \"Accounting & Bookkeeping\", \"Advisory & Consulting\", \"Corporate Laws & LLP\", \"FEMA & Foreign Trade\", \"Information Technology Services\", \"RERA Compliance\"]','Tax Audit',NULL,NULL,NULL,NULL,NULL,NULL,'Khushalani & Co CA Firm','3','[\"Other\"]','[\"Statutory Audit\", \"Tax Audit\", \"Direct Tax\", \"GST & Indirect Tax\", \"Accounting & Bookkeeping\"]',NULL,NULL,NULL,NULL,'360000',NULL,1,1,'resumes/1781197793_resume.pdf','2026-06-11 22:30:23','2026-06-12 10:20:14'),(5,6,'semi-qualified',0,NULL,'WRO0697511','PUNE','PUNE','male',NULL,NULL,'confirm',NULL,'[\"PUNE\"]',NULL,'[\"Internal Audit\", \"Bank & Concurrent Audit\", \"Forensic Audit & Investigation\", \"Risk & Internal Controls\"]','Internal Audit',NULL,NULL,NULL,NULL,NULL,NULL,'ARTH and Associates','3','[\"Manufacturing\"]','[\"Internal Audit\", \"Bank & Concurrent Audit\", \"Forensic Audit & Investigation\", \"Risk & Internal Controls\"]',NULL,NULL,NULL,NULL,NULL,NULL,1,1,'resumes/1781255556_resume.jpg','2026-06-12 14:30:56','2026-06-12 10:20:14'),(6,7,'articleship',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-12 14:38:41','2026-06-12 14:38:41'),(7,8,'articleship',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-12 17:01:47','2026-06-12 17:01:47'),(8,9,'articleship',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-12 17:15:36','2026-06-12 17:15:36'),(9,10,'articleship',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-12 17:28:19','2026-06-12 17:28:19'),(10,11,'articleship',0,NULL,'uhfwehj','PUNE','PUNE','male','May 2026','inter-both','confirm',NULL,'[\"PUNE\"]','pending','[\"GST & Indirect Tax\", \"Internal Audit\", \"Accounting & Bookkeeping\"]','Internal Audit','3+',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'CA Student',NULL,NULL,NULL,NULL,1,1,'resumes/1781284789_resume.pdf','2026-06-12 22:31:02','2026-06-17 21:39:53'),(11,12,'articleship',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-14 10:49:13','2026-06-14 10:49:13'),(12,13,'semi-qualified',0,NULL,'1234567890','PUNE','PUNE','male',NULL,NULL,'confirm',NULL,'[\"PUNE\"]',NULL,'[\"overall\"]','Direct Tax',NULL,NULL,NULL,NULL,NULL,1,'Test','2','[\"Manufacturing\", \"IT / SaaS\"]','[\"Statutory Audit\", \"Internal Audit\"]','test',NULL,NULL,'4','6',NULL,1,0,NULL,'2026-06-15 12:05:01','2026-06-17 13:28:23'),(13,14,'semi-qualified',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-15 12:24:33','2026-06-15 12:24:33'),(14,15,'semi-qualified',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-15 23:59:15','2026-06-15 23:59:15'),(15,16,'articleship',0,NULL,'WRO0825626','KALYAN','KALYAN','male',NULL,'pursuing-inter','provisional',NULL,'[]',NULL,'[]',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,NULL,'2026-06-16 00:32:48','2026-06-16 00:36:35'),(16,17,'articleship',0,NULL,NULL,'INDORE','INDORE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 01:25:17','2026-06-16 01:25:17'),(17,18,'semi-qualified',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 12:35:00','2026-06-16 12:35:00'),(18,19,'articleship',0,NULL,NULL,'PIMPRI-CHINCHWAD','PIMPRI-CHINCHWAD',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 12:36:57','2026-06-16 12:36:57'),(19,20,'articleship',0,NULL,'WRO0795484','LATUR','LATUR','male','Jan 2026','inter-both','confirm',NULL,'[\"PUNE\", \"MUMBAI\"]','both','[\"overall\"]','Valuation','3+',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,NULL,'2026-06-16 13:21:28','2026-06-16 13:25:00'),(20,21,'creator',0,NULL,NULL,'CHHATRAPATI SAMBHAJINAGAR','CHHATRAPATI SAMBHAJINAGAR',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 13:39:47','2026-06-16 13:39:47'),(21,23,'articleship',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 14:04:01','2026-06-16 14:04:01'),(22,24,'qualified',0,NULL,NULL,'NANDED','NANDED',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 14:16:54','2026-06-16 14:16:54'),(23,25,'semi-qualified',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 14:51:05','2026-06-16 14:51:05'),(24,29,'articleship',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 18:24:49','2026-06-16 18:24:49'),(25,30,'articleship',0,NULL,'WRO0623067','PUNE','PUNE','male','Sep 2025','inter-both','confirm',NULL,'[\"PUNE\"]','both','[\"overall\"]','Internal Audit','3+',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,NULL,'2026-06-16 18:34:59','2026-06-16 18:39:13'),(26,31,'articleship',0,NULL,'wro0781576','SILLOD','SILLOD','male','Jan 2026','inter-both','confirm',NULL,'[\"PUNE\", \"SILLOD\", \"MUMBAI\"]','both','[\"overall\"]','Statutory Audit','1',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,NULL,'2026-06-16 18:52:31','2026-06-16 18:56:48'),(27,32,'articleship',0,NULL,NULL,'JAMMU','JAMMU',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 18:57:44','2026-06-16 18:57:44'),(28,33,'articleship',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 18:57:55','2026-06-16 18:57:55'),(29,34,'articleship',0,NULL,'APP3780910','AKOLA','AKOLA','female',NULL,'pursuing-inter','provisional',NULL,'[]',NULL,'[]',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,NULL,'2026-06-16 19:10:08','2026-06-16 19:13:29'),(30,35,'articleship',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 19:32:08','2026-06-16 19:32:08'),(31,37,'qualified',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 20:32:43','2026-06-16 20:32:43'),(32,38,'semi-qualified',0,NULL,'WRO0559795','PUNE','PUNE','male',NULL,NULL,'confirm',NULL,'[\"PUNE\"]',NULL,'[\"overall\"]','Statutory Audit',NULL,NULL,NULL,NULL,NULL,NULL,'MSDN and Associates','3',NULL,'[\"Statutory Audit\", \"Tax Audit\", \"Direct Tax\", \"GST & Indirect Tax\", \"Accounting & Bookkeeping\"]','I bring a strong foundation in accounting and finance along with practical audit experience.\r\nAs a semi-qualified CA, I possess analytical skills, attention to detail, and a solid understanding of financial processes.\r\nI am a quick learner, adaptable to new systems and committed to continuous improvement.',NULL,NULL,'0.96','6.5','marksheets/1781622523_marksheet.jpg',1,0,NULL,'2026-06-16 20:33:26','2026-06-16 20:38:43'),(33,39,'creator',0,'[\"Finance Content Creator\", \"Finance Article / Blog Writer\"]',NULL,'PUNE','PUNE','male',NULL,NULL,'provisional',NULL,'[]',NULL,'[]',NULL,NULL,'https://www.linkedin.com/in/akash-pund-771602323?utm_source=share_via&utm_content=profile&utm_medium=member_android',NULL,NULL,NULL,NULL,NULL,'0',NULL,NULL,'I am a quick learner and adept at acquiring business knowledge relevant to my projects. Additionally, I excel both as an individual contributor and as a team player.','Semi Qualified CA','Part Time',NULL,NULL,NULL,1,0,NULL,'2026-06-16 20:35:14','2026-06-21 00:38:31'),(34,40,'articleship',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 20:40:45','2026-06-16 20:40:45'),(35,41,'articleship',0,NULL,NULL,'JALNA','JALNA',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 20:49:36','2026-06-16 20:49:36'),(36,42,'creator',0,'[\"PPT & Presentation Designer\"]',NULL,'PUNE','PUNE','male',NULL,NULL,'provisional',NULL,'[]',NULL,'[]',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'0',NULL,NULL,'I am a quick learner and want to give my best wherever I get opportunity.','CA Student','Available',NULL,NULL,NULL,1,0,NULL,'2026-06-16 21:15:45','2026-06-16 21:18:24'),(37,43,'qualified',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-16 23:26:16','2026-06-16 23:26:16'),(38,44,'creator',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-17 07:14:41','2026-06-17 07:14:41'),(39,46,'qualified',0,NULL,'646567','PUNE','PUNE','female','2026',NULL,'confirm',NULL,'[\"PUNE\", \"PIMPRI-CHINCHWAD\", \"NAVI MUMBAI\", \"THANE\", \"MUMBAI\"]',NULL,'[\"Direct Tax\", \"GST & Indirect Tax\", \"Tax Audit\", \"International Taxation\", \"Advisory & Consulting\", \"Corporate Laws & LLP\", \"FEMA & Foreign Trade\", \"Valuation\", \"Virtual CFO Services\", \"Investment Advisory\"]','Direct Tax',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[\"Real Estate\"]','[\"Direct Tax\", \"Tax Audit\", \"GST & Indirect Tax\", \"Accounting & Bookkeeping\", \"Bank & Concurrent Audit\"]',NULL,NULL,NULL,NULL,'10',NULL,1,1,'resumes/1781982639_resume.pdf','2026-06-17 09:11:25','2026-06-21 00:40:51'),(40,47,'creator',0,NULL,NULL,'INDORE','INDORE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-17 09:43:14','2026-06-17 09:43:14'),(41,48,'creator',0,NULL,NULL,'AHMEDNAGAR','AHMEDNAGAR',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-17 09:56:50','2026-06-17 09:56:50'),(42,49,'semi-qualified',0,NULL,'Wro0613485','PUNE','PUNE','male',NULL,NULL,'confirm',NULL,'[\"PUNE\", \"MUMBAI\", \"AKOLA\"]',NULL,'[\"overall\"]','GST & Indirect Tax',NULL,NULL,NULL,NULL,NULL,NULL,'Complyhappy Finserve Pvt Ltd','3','[\"Services\"]','[\"Tax Audit\", \"Direct Tax\", \"GST & Indirect Tax\", \"Accounting & Bookkeeping\"]',NULL,NULL,NULL,'4.2','6.5',NULL,1,1,'resumes/1781670976_resume.pdf','2026-06-17 10:03:16','2026-06-17 10:06:26'),(43,50,'qualified',0,NULL,NULL,'RAIPUR','RAIPUR',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-17 11:05:42','2026-06-17 11:05:42'),(44,52,'articleship',1,'[\"PPT & Presentation Designer\", \"Short Video Creator (Reels/Shorts)\"]','WRO0770438','DHULE','DHULE','male',NULL,'pursuing-inter','provisional',NULL,'[]',NULL,'[]',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'1',NULL,NULL,'I believe your firm should hire me because I can transform complex information into clear, engaging, and visually appealing presentations and videos. I have a strong understanding of presentation design, content organization, and visual storytelling, which helps communicate ideas effectively.\r\n\r\nI am detail-oriented, creative, and committed to delivering high-quality work within deadlines. Whether it is creating professional PowerPoint presentations, designing impactful slides, or editing short videos for social media and business purposes, I focus on making content both informative and engaging.\r\n\r\nI am also eager to learn new tools, adapt to client requirements, and continuously improve my skills. My goal is not only to complete tasks but to help the firm present its ideas in a way that attracts attention and creates a lasting impact.','CA Student','Part Time',NULL,NULL,NULL,1,0,NULL,'2026-06-17 11:23:07','2026-06-17 11:46:21'),(45,53,'articleship',0,NULL,NULL,'MUMBAI','MUMBAI',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-17 11:28:00','2026-06-17 11:28:00'),(46,57,'articleship',0,NULL,'WRO0743109','PUNE','PUNE','female',NULL,'pursuing-inter','provisional',NULL,'[]',NULL,'[]',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-17 19:02:39','2026-06-17 19:03:47'),(47,58,'creator',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-17 20:03:58','2026-06-17 20:03:58'),(48,59,'creator',0,NULL,NULL,'LATUR','LATUR',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-17 20:24:25','2026-06-17 20:24:25'),(49,61,'creator',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-17 22:36:31','2026-06-17 22:36:31'),(50,63,'articleship',0,NULL,'WRO0746956','MALEGAON','MALEGAON','male','Sep 2025','inter-both','confirm',NULL,'[\"MUMBAI\"]','both','[\"overall\"]','Tax Audit','3+',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,NULL,'2026-06-17 23:18:46','2026-06-17 23:23:44'),(51,64,'semi-qualified',0,NULL,'WRO0238888','PUNE','PUNE','female',NULL,NULL,'confirm',NULL,'[\"PUNE\"]',NULL,'[\"Statutory Audit\", \"Internal Audit\", \"Tax Audit\", \"Direct Tax\", \"GST & Indirect Tax\"]','GST & Indirect Tax',NULL,NULL,NULL,NULL,NULL,NULL,'Splash and company','13',NULL,'[\"Statutory Audit\", \"Internal Audit\", \"Tax Audit\", \"Direct Tax\", \"GST & Indirect Tax\", \"Accounting & Bookkeeping\"]','I have 13 years of relevant experience in a CA firm, strong technical knowledge, and a proven track record of handling assignments efficiently. I can contribute immediately and help the organization achieve its goals.',NULL,NULL,'540000','700000',NULL,1,1,'resumes/1781719534_resume.pdf','2026-06-17 23:26:20','2026-06-17 23:47:46'),(52,65,'articleship',0,NULL,'1234567891','PUNE','PUNE','male',NULL,'inter-g2','provisional',NULL,'[]',NULL,'[]',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-17 23:38:59','2026-06-22 22:20:49'),(53,67,'articleship',0,NULL,'Wro0813736','YAVATMAL','YAVATMAL','female',NULL,'inter-g1','provisional',NULL,'[]',NULL,'[]',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,NULL,'2026-06-18 13:07:38','2026-06-18 13:14:22'),(54,68,'articleship',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-18 15:02:36','2026-06-18 15:02:36'),(55,69,'articleship',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-18 15:03:55','2026-06-18 15:03:55'),(56,70,'qualified',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-18 16:06:58','2026-06-18 16:06:58'),(57,71,'creator',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-18 17:59:18','2026-06-18 17:59:18'),(58,72,'semi-qualified',0,NULL,'WRO0613622','PUNE','PUNE','male',NULL,NULL,'confirm',NULL,'[\"PUNE\", \"MUMBAI\"]',NULL,'[\"Statutory Audit\", \"Tax Audit\", \"Direct Tax\", \"GST & Indirect Tax\", \"Accounting & Bookkeeping\", \"Valuation\", \"Payroll Services\", \"Statutory Audit\"]','Statutory Audit',NULL,NULL,NULL,NULL,NULL,NULL,'Rathi Rathi and co.','3','[\"Manufacturing\", \"IT / SaaS\", \"Pharma & Healthcare\", \"Education\", \"Services\"]','[\"Statutory Audit\", \"Tax Audit\", \"Direct Tax\", \"GST & Indirect Tax\", \"Accounting & Bookkeeping\", \"Valuation\"]','I bring a combination of 3 years of articleship across statutory audit, direct taxation, and indirect taxation at a reputed Pune CA firm, hands-on experience with manufacturing and IT services clients, and independent upskilling in financial valuation and modelling through a structured AVFM program.\r\nI am CA Final Group 1 cleared, candidate having exposure in Financial analysis and number interpretation\r\nAudit documentation and working paper preparation\r\nGST compliance and reconciliation',NULL,NULL,'2.5','5',NULL,1,1,'resumes/1781792769_resume.pdf','2026-06-18 18:40:22','2026-06-18 19:56:18'),(59,76,'semi-qualified',0,NULL,'0','NANDED','NANDED','male',NULL,NULL,'confirm',NULL,'[\"NANDED\", \"PUNE\", \"HYDERABAD\"]',NULL,'[\"Tax Audit\", \"GST & Indirect Tax\", \"Direct Tax\", \"Accounting & Bookkeeping\", \"Advisory & Consulting\", \"Internal Audit\", \"Payroll Services\"]','Accounting & Bookkeeping',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[\"Internal Audit\", \"Tax Audit\", \"GST & Indirect Tax\", \"Direct Tax\", \"Accounting & Bookkeeping\", \"Advisory & Consulting\", \"Investment Advisory\"]','I have hands-on experience dealing with TDS returns, challan issues, notices, income tax return filing, client follow-ups, and practical tax-related problems. Because of this exposure, I can start contributing faster than someone who only has theoretical knowledge. I enjoy solving compliance issues, and I am comfortable communicating with clients and government portals. I am eager to learn from experienced professionals and grow with the firm while delivering accurate and timely work.',NULL,NULL,'1.8','2.8',NULL,1,0,'resumes/1781872436_resume.pdf','2026-06-19 17:52:54','2026-06-19 18:04:53'),(60,77,'already_doing_articleship',0,NULL,'WRO0842624','PUNE','PUNE','female',NULL,NULL,'provisional',NULL,'[]',NULL,'[]',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'ARTH & Associates',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-20 10:37:12','2026-06-20 10:40:03'),(61,78,'qualified',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-20 10:59:22','2026-06-20 10:59:22'),(62,81,'articleship',0,NULL,NULL,'AHMEDNAGAR','AHMEDNAGAR',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-20 14:58:50','2026-06-20 14:58:50'),(63,83,'already_doing_articleship',0,NULL,'WRO0850294','JALGAON','JALGAON','male',NULL,NULL,'provisional',NULL,'[]',NULL,'[]',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'00000',NULL,NULL,NULL,NULL,'CA Student',NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-21 14:49:51','2026-06-24 09:00:14'),(64,84,'already_doing_articleship',0,NULL,'WRO0733354','PUNE','PUNE','female',NULL,NULL,'provisional',NULL,'[]',NULL,'[]',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Arth and Associates',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-21 15:44:40','2026-06-21 15:51:25'),(65,89,'already_doing_articleship',0,NULL,NULL,'PUNE','PUNE',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-22 20:28:39','2026-06-22 20:28:39'),(66,90,'already_doing_articleship',0,NULL,NULL,'AHMEDABAD','AHMEDABAD',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-23 01:13:01','2026-06-23 01:13:01'),(67,93,'already_doing_articleship',0,NULL,NULL,'JAIPUR','JAIPUR',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-23 23:49:11','2026-06-23 23:49:11'),(68,94,'articleship',0,NULL,NULL,'JALGAON','JALGAON',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2026-06-24 09:15:52','2026-06-24 09:15:52'),(69,98,'qualified',0,NULL,'197430','MUMBAI','MUMBAI','male','2019',NULL,'confirm',NULL,'[\"MUMBAI\"]',NULL,'[\"Direct Tax\", \"Tax Audit\", \"International Taxation\", \"GST & Indirect Tax\", \"Transfer Pricing\", \"Accounting & Bookkeeping\", \"Advisory & Consulting\", \"Virtual CFO Services\", \"RERA Compliance\", \"Valuation\"]','Direct Tax',NULL,'https://www.linkedin.com/in/ca-kevin-mendonca-2236a71b5/',NULL,NULL,'https://cakevinmendonca.in/',NULL,'Planet Retail Holdings Pvt Ltd','1','[\"Real Estate\", \"FMCG / Retail\", \"Services\"]','[\"Direct Tax\", \"GST & Indirect Tax\", \"Tax Audit\", \"International Taxation\", \"Accounting & Bookkeeping\", \"Advisory & Consulting\"]','CA with 11+ years of experience (including Pre-CA) in various industries in corporate. Frequently changed jobs as I got used to/bored of same routine work (basically working for 1 client...my employer). Transitioned recently into solo CA practice with focus on taxation and business consultancy. This is because I felt I could have some independence on the choice of work/assignment I could take; however it is not working out as I envisaged due to my lack of marketing/sales skills. Long-term orient','Qualified CA',NULL,'11.76','12',NULL,1,0,'resumes/1782285215_resume.pdf','2026-06-24 12:35:15','2026-06-24 12:43:35');
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
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_wallets`
--

LOCK TABLES `student_wallets` WRITE;
/*!40000 ALTER TABLE `student_wallets` DISABLE KEYS */;
INSERT INTO `student_wallets` VALUES (1,1,0.00,0.00,0.00,0,3,'2026-06-10 16:37:56','2026-06-10 16:37:56'),(2,5,0.00,0.00,0.00,0,3,'2026-06-11 22:40:36','2026-06-11 22:40:36'),(3,6,0.00,0.00,0.00,0,3,'2026-06-12 14:43:55','2026-06-12 14:43:55'),(4,11,0.00,0.00,0.00,0,3,'2026-06-12 22:50:42','2026-06-12 22:50:42'),(5,16,0.00,0.00,0.00,0,3,'2026-06-16 00:36:36','2026-06-16 00:36:36'),(6,13,0.00,0.00,0.00,0,3,'2026-06-16 01:22:05','2026-06-16 01:22:05'),(7,20,0.00,0.00,0.00,0,3,'2026-06-16 13:25:00','2026-06-16 13:25:00'),(8,30,0.00,0.00,0.00,0,3,'2026-06-16 18:39:14','2026-06-16 18:39:14'),(9,31,0.00,0.00,0.00,0,3,'2026-06-16 18:56:48','2026-06-16 18:56:48'),(10,34,0.00,0.00,0.00,0,3,'2026-06-16 19:13:04','2026-06-16 19:13:04'),(11,49,0.00,0.00,0.00,0,3,'2026-06-17 10:06:26','2026-06-17 10:06:26'),(12,52,0.00,0.00,0.00,0,3,'2026-06-17 11:46:29','2026-06-17 11:46:29'),(13,57,0.00,0.00,0.00,0,3,'2026-06-17 19:04:09','2026-06-17 19:04:09'),(14,65,0.00,0.00,0.00,0,3,'2026-06-17 23:41:18','2026-06-17 23:41:18'),(15,64,0.00,0.00,0.00,0,3,'2026-06-17 23:47:48','2026-06-17 23:47:48'),(16,67,0.00,0.00,0.00,0,3,'2026-06-18 13:14:23','2026-06-18 13:14:23'),(17,72,0.00,0.00,0.00,0,3,'2026-06-18 19:56:19','2026-06-18 19:56:19'),(18,46,0.00,0.00,0.00,0,3,'2026-06-21 00:40:52','2026-06-21 00:40:52'),(19,98,0.00,0.00,0.00,0,3,'2026-06-24 12:44:58','2026-06-24 12:44:58');
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
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_coin_accounts`
--

LOCK TABLES `sys_coin_accounts` WRITE;
/*!40000 ALTER TABLE `sys_coin_accounts` DISABLE KEYS */;
INSERT INTO `sys_coin_accounts` VALUES (1,13,0,0,0,0,'2026-06-16 01:22:05','2026-06-16 01:22:05'),(2,11,0,0,0,0,'2026-06-16 11:59:47','2026-06-16 11:59:47'),(3,20,0,0,0,0,'2026-06-16 13:25:00','2026-06-16 13:25:00'),(4,30,0,0,0,0,'2026-06-16 18:39:14','2026-06-16 18:39:14'),(5,31,0,0,0,0,'2026-06-16 18:56:48','2026-06-16 18:56:48'),(6,34,100,0,0,100,'2026-06-16 19:12:56','2026-06-16 19:12:56'),(7,49,0,0,0,0,'2026-06-17 10:06:26','2026-06-17 10:06:26'),(8,52,100,0,0,100,'2026-06-17 11:41:19','2026-06-17 11:41:19'),(9,5,0,0,0,0,'2026-06-17 14:12:23','2026-06-17 14:12:23'),(10,57,100,0,0,100,'2026-06-17 19:03:47','2026-06-17 19:03:47'),(11,65,100,0,0,100,'2026-06-17 23:41:11','2026-06-17 23:41:11'),(12,64,0,0,0,0,'2026-06-17 23:47:48','2026-06-17 23:47:48'),(13,67,100,0,0,100,'2026-06-18 13:14:10','2026-06-18 13:14:10'),(14,6,0,0,0,0,'2026-06-18 15:48:31','2026-06-18 15:48:31'),(15,72,0,0,0,0,'2026-06-18 19:56:19','2026-06-18 19:56:19'),(16,39,100,0,0,100,'2026-06-21 00:38:31','2026-06-21 00:38:31'),(17,46,0,0,0,0,'2026-06-21 00:40:52','2026-06-21 00:40:52'),(18,98,0,0,0,0,'2026-06-24 12:44:58','2026-06-24 12:44:58');
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_coin_transactions`
--

LOCK TABLES `sys_coin_transactions` WRITE;
/*!40000 ALTER TABLE `sys_coin_transactions` DISABLE KEYS */;
INSERT INTO `sys_coin_transactions` VALUES (1,34,100,'WELCOME_BONUS','welcome',34,NULL,NULL,'100 SYS Coins welcome bonus',0,100,'2026-06-16 19:12:56'),(2,52,100,'WELCOME_BONUS','welcome',52,NULL,NULL,'100 SYS Coins welcome bonus',0,100,'2026-06-17 11:41:19'),(3,57,100,'WELCOME_BONUS','welcome',57,NULL,NULL,'100 SYS Coins welcome bonus',0,100,'2026-06-17 19:03:47'),(4,65,100,'WELCOME_BONUS','welcome',65,NULL,NULL,'100 SYS Coins welcome bonus',0,100,'2026-06-17 23:41:11'),(5,67,100,'WELCOME_BONUS','welcome',67,NULL,NULL,'100 SYS Coins welcome bonus',0,100,'2026-06-18 13:14:10'),(6,39,100,'WELCOME_BONUS','welcome',39,NULL,NULL,'100 SYS Coins welcome bonus',0,100,'2026-06-21 00:38:31');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_setting_audits`
--

LOCK TABLES `system_setting_audits` WRITE;
/*!40000 ALTER TABLE `system_setting_audits` DISABLE KEYS */;
INSERT INTO `system_setting_audits` VALUES (1,'payment_ifsc','BARBODBMURU','BARB0DBMURU',2,'TusharB','2026-06-21 21:31:42','2026-06-21 21:31:42');
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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'student_referral_reward','50','integer','Student Referral Reward','SYS Coins credited to the referrer when a referred student completes onboarding.','rewards',1,'2026-06-14 22:20:48','2026-06-14 22:35:27'),(2,'firm_premium_purchase_reward','500','integer','Firm Premium Purchase Reward','Amount (â‚¹) rewarded to the referrer when a referred firm buys premium.','rewards',1,'2026-06-14 22:20:48','2026-06-14 22:36:17'),(3,'welcome_bonus_coins','100','integer','Welcome Bonus Coins','SYS Coins granted once to provisional students on completing onboarding.','welcome_bonus',1,'2026-06-14 22:20:48','2026-06-14 22:20:48'),(4,'free_applications_count','3','integer','Free Applications Count','Number of free job applications a student gets before fees apply.','application',1,'2026-06-14 22:20:48','2026-06-14 22:20:48'),(5,'application_fee_amount','49','integer','Application Fee Amount','Wallet fee (â‚¹) charged per job application beyond the free quota.','application',1,'2026-06-14 22:20:48','2026-06-14 22:24:13'),(6,'minimum_wallet_recharge','150','integer','Minimum Wallet Recharge Amount','Smallest allowed wallet recharge amount (â‚¹).','wallet',1,'2026-06-14 22:20:48','2026-06-14 22:20:48'),(7,'payment_account_holder','MR. RITESH CHANDAK','string','Account Holder Name','Name on the bank account that receives manual payments.','payment',1,'2026-06-17 14:50:25','2026-06-17 14:50:25'),(8,'payment_bank_name','Bank of Baroda','string','Bank Name','Bank where the receiving account is held.','payment',1,'2026-06-17 14:50:25','2026-06-17 14:50:25'),(9,'payment_account_number','97980100019171','string','Account Number','Receiving bank account number.','payment',1,'2026-06-17 14:50:25','2026-06-17 14:50:25'),(10,'payment_ifsc','BARB0DBMURU','string','IFSC Code','IFSC code of the receiving bank branch.','payment',1,'2026-06-17 14:50:25','2026-06-21 21:31:42'),(11,'payment_upi_id','9156235503@ybl','string','UPI ID','UPI ID shown to firms for manual UPI payments.','payment',1,'2026-06-17 14:50:25','2026-06-17 14:50:25'),(12,'payment_qr_code','','string','Payment QR Code','QR code image for manual UPI payments (optional).','payment',0,'2026-06-17 14:50:25','2026-06-17 14:50:25');
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
  `is_impersonation` tinyint(1) NOT NULL DEFAULT '0',
  `impersonated_by` bigint DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `last_activity_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_sessions_token` (`token`),
  KEY `idx_user_sessions_user_id` (`user_id`),
  CONSTRAINT `fk_user_sessions_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=372 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_sessions`
--

LOCK TABLES `user_sessions` WRITE;
/*!40000 ALTER TABLE `user_sessions` DISABLE KEYS */;
INSERT INTO `user_sessions` VALUES (13,3,'dVljemxQVFJ3emt6MjUxTzlkOWFrc3lYV1VCeXAxSHNMdERhQUdUSA==','mobile','Chrome 149','Android 10','152.58.180.147','Kolkata, West Bengal, India',0,NULL,'2026-06-17 20:36:27','2026-06-10 20:36:51','2026-06-10 20:36:27','2026-06-10 20:36:51'),(14,3,'RVFMT2dnb0xEWkVqaDNTRlVZVDJtWjFCR3ozUW9lZE8wQzVFRVJxNw==','mobile','Chrome 149','Android 10','152.58.180.147','Kolkata, West Bengal, India',0,NULL,'2026-06-17 20:37:01','2026-06-10 20:40:27','2026-06-10 20:37:01','2026-06-10 20:40:27'),(15,3,'WXgwQzBGZXFuMmUyQkhLaTJBVjlkMHNHNXYwbEdlNnpwVFlRSXVjUg==','mobile','Chrome 149','Android 10','152.58.180.147','Kolkata, West Bengal, India',0,NULL,'2026-06-17 20:41:08','2026-06-11 16:55:44','2026-06-10 20:41:08','2026-06-11 16:55:44'),(17,4,'cGVzRHE0RGVIaFJRMXA2NDh2U3kwYmh0QzBSUjJ1elJxaUZBd3BiTw==','mobile','Chrome 138','Android 9','157.32.198.213','Pune, Maharashtra, India',0,NULL,'2026-06-18 13:15:34','2026-06-11 13:15:34','2026-06-11 13:15:34','2026-06-11 13:15:34'),(18,4,'ekpIYWM1RkZzT2lRMXNPdnNmankwQmRsUXBTUmVpMEhIbGJhMkszZg==','mobile','Chrome 138','Android 9','157.32.198.213','Pune, Maharashtra, India',0,NULL,'2026-06-18 13:15:50','2026-06-11 13:15:50','2026-06-11 13:15:50','2026-06-11 13:15:50'),(19,4,'cEdjbFJVZzlVelJEU2pEbGtIY3B2dUdnaGFFVEI2YUJxOWVKZUhXWg==','mobile','Chrome 123','Android 9','157.32.198.213','Pune, Maharashtra, India',0,NULL,'2026-06-18 13:18:19','2026-06-11 13:18:25','2026-06-11 13:18:19','2026-06-11 13:18:25'),(20,5,'bDA3b0lSSzY1dlBiTnA5ckdWMWhaVDV1NTlHS0YzdktVanNPYUlGbQ==','mobile','Chrome 148','Android 10','152.58.44.210','Mumbai, Maharashtra, India',0,NULL,'2026-06-18 22:30:47','2026-06-11 22:31:15','2026-06-11 22:30:47','2026-06-11 22:31:15'),(22,6,'d213Ykg1cDlUMG5NUDY5NjVXOGZ2R0hNbzVJQ014QzRackgxNXd5MA==','mobile','Chrome 149','Android 10','157.32.113.113','Pune, Maharashtra, India',0,NULL,'2026-06-19 14:31:21','2026-06-12 14:31:55','2026-06-12 14:31:21','2026-06-12 14:31:55'),(23,6,'UHFTbzdEejF5TGt1ODd4VWF6ZmJzSnk5ZHNCcnA3cE9icFVVeGJQMA==','mobile','Chrome 149','Android 10','157.32.113.113','Pune, Maharashtra, India',0,NULL,'2026-06-19 14:32:10','2026-06-18 15:48:38','2026-06-12 14:32:11','2026-06-18 15:48:38'),(24,7,'Q1ZsdE1pMmdwaElod3hiemZseWRBS29BM2JFeUVJeXVYUXF6cTYzZA==','desktop','Chrome 149','Windows 10 / 11','110.227.185.177','Pune, Maharashtra, India',0,NULL,'2026-06-19 14:39:58','2026-06-12 14:39:59','2026-06-12 14:39:58','2026-06-12 14:39:59'),(25,7,'d21HOTlNOHhjcmZkTUZObU1ZM2xHVGlIRW9RQWFrcVo2OTluWFJrdQ==','desktop','Chrome 149','Windows 10 / 11','110.227.185.177','Pune, Maharashtra, India',0,NULL,'2026-06-19 14:48:25','2026-06-12 14:48:26','2026-06-12 14:48:25','2026-06-12 14:48:26'),(27,8,'Q2lUQTdveHhjYThKc0plSHhmd0FuWHlwbHJ0d25jODV1YnNiWm5ldg==','mobile','Chrome 147','Android 10','27.97.174.133','Nagpur, Maharashtra, India',0,NULL,'2026-06-19 17:02:15','2026-06-12 17:02:15','2026-06-12 17:02:15','2026-06-12 17:02:15'),(28,8,'MXhseldVM3JyVTd2TlRWRnpQUU9NRjJjVjl4eUZ2eUQ5QU9TTmxwVQ==','mobile','Chrome 147','Android 10','27.97.174.133','Nagpur, Maharashtra, India',0,NULL,'2026-06-19 17:03:53','2026-06-12 17:07:03','2026-06-12 17:03:53','2026-06-12 17:07:03'),(29,9,'Yzd2NlZiTTREWmhuUG52ZUpZZTV3cVVZSnZPU1ZiVVQ0Q0pCRzFBbQ==','mobile','Samsung Browser 29','Android 10','27.97.180.154','Amravati, Maharashtra, India',0,NULL,'2026-06-19 17:16:17','2026-06-12 17:16:43','2026-06-12 17:16:17','2026-06-12 17:16:43'),(30,9,'U3h4TktUSWRiQXJaellUN1dTOVYyUHQ4bnpZV2ZoN1NVdHVkMDlaRg==','mobile','Samsung Browser 29','Android 10','27.97.180.154','Amravati, Maharashtra, India',0,NULL,'2026-06-19 17:16:46','2026-06-13 07:06:21','2026-06-12 17:16:46','2026-06-13 07:06:21'),(34,10,'ZTYwamx0aTBuMGpYS2NsYnNxREVuUHRuMmlSNnFXVjNyZjhic3doaw==','mobile','Chrome 149','Android 10','106.213.86.255','Pune, Maharashtra, India',0,NULL,'2026-06-19 17:28:55','2026-06-12 18:43:44','2026-06-12 17:28:55','2026-06-12 18:43:44'),(47,12,'anhxaWViT0lrQXdZUmJabTVpekNxR1l5REEybzExbmY5M2Q0aEtHSA==','mobile','Chrome 148','Android 10','106.210.227.201','Kātol, Maharashtra, India',0,NULL,'2026-06-21 10:49:26','2026-06-14 10:49:59','2026-06-14 10:49:26','2026-06-14 10:49:59'),(48,12,'VGI4U3VvSjZUS3Z1WTlhWlh4Uks4cThnRWZwNEZWa2hTWVRXN1lieQ==','mobile','Chrome 148','Android 10','106.210.227.201','Kātol, Maharashtra, India',0,NULL,'2026-06-21 10:50:07','2026-06-14 10:50:08','2026-06-14 10:50:07','2026-06-14 10:50:08'),(92,14,'MHFkNWc2VEFvUFJEd0s0R2YxbGN1UGRSSnU1S1RqU3hzYkxlZG5hZg==','mobile','Chrome 149','Android 10','152.57.156.135','Hyderabad, Telangana, India',0,NULL,'2026-06-22 12:24:49','2026-06-15 12:26:53','2026-06-15 12:24:49','2026-06-15 12:26:53'),(124,15,'SzBPbEZGZU9ldzdMWWZHT3hYVVlJRktzUVNzOVJZM0gyRHRWd2hJcg==','desktop','Chrome 147','Windows 10 / 11','182.69.179.145','New Delhi, National Capital Territory of Delhi, India',0,NULL,'2026-06-22 23:59:24','2026-06-16 00:00:06','2026-06-15 23:59:24','2026-06-16 00:00:06'),(125,15,'WktQa0dnY1RrMzRMeTdpMm5YNnZJVDc1NWQybDhyOXpENWdxbTdSNA==','desktop','Chrome 147','Windows 10 / 11','182.69.179.145','New Delhi, National Capital Territory of Delhi, India',0,NULL,'2026-06-23 00:00:16','2026-06-16 00:07:18','2026-06-16 00:00:17','2026-06-16 00:07:18'),(126,16,'bEpHdkhSWVM2SDZNR2M5MUF6SjhnS05qSEhGdWd1UWRCNHZIbklxYg==','desktop','Edge 149','Windows 10 / 11','103.49.254.7','Badlapur, Maharashtra, India',0,NULL,'2026-06-23 00:33:58','2026-06-16 00:37:24','2026-06-16 00:33:58','2026-06-16 00:37:24'),(133,17,'dUxLTlQ0N2dNcm5mS2VucVltbW5wRHJVRDE3Sk1mSTVmcDU4U1pBcg==','mobile','Chrome 148','Android 10','152.59.29.76','Jabalpur, Madhya Pradesh, India',0,NULL,'2026-06-23 01:25:31','2026-06-16 01:25:31','2026-06-16 01:25:31','2026-06-16 01:25:31'),(139,18,'WXZvTzB6eE0wOWg4Q2lHcFRLeTJnR0JzS0pSbnFFVGw3TXByeUk0Rg==','mobile','Safari 18','iOS 18.5','223.228.136.12','Pune, Maharashtra, India',0,NULL,'2026-06-23 12:35:25','2026-06-16 12:35:25','2026-06-16 12:35:25','2026-06-16 12:35:25'),(140,18,'VmlmMmhHOFdVU1g2Slh1dDUybDM3enFHTmFkTG8ydXFxNk9RaTF5Qw==','mobile','Safari 18','iOS 18.5','223.228.136.12','Pune, Maharashtra, India',0,NULL,'2026-06-23 12:36:05','2026-06-16 13:20:28','2026-06-16 12:36:05','2026-06-16 13:20:28'),(141,19,'cTZSSEFvSHFuaWlFd0pzdHhROThKZXFQRG5ZdEVlQUF0d1dWT2hqTA==','desktop','Chrome 148','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India',0,NULL,'2026-06-23 12:37:09','2026-06-16 12:39:02','2026-06-16 12:37:09','2026-06-16 12:39:02'),(146,21,'UXRXRWdRWVVYQmVCUXNIQVdXRW9DWWNwTFRheWNQMmVZNFJQNUV6YQ==','mobile','Chrome 149','Android 10','106.221.220.177','Pune, Maharashtra, India',0,NULL,'2026-06-23 13:40:13','2026-06-16 13:40:37','2026-06-16 13:40:13','2026-06-16 13:40:37'),(147,21,'SzVzUHpJZ0g1RVBlUHN4SndCeGZnVzJsQUR1OWxSMVVSMFpNc1FyZg==','mobile','Chrome 149','Android 10','106.221.220.177','Pune, Maharashtra, India',0,NULL,'2026-06-23 13:40:41','2026-06-16 13:40:43','2026-06-16 13:40:42','2026-06-16 13:40:43'),(150,23,'TWtLUkltaGxCcXhNWFNKVmFhYXFZY2paVXBpcUxkSjlXc0NSbXpRdw==','mobile','Unknown Browser','iOS 26.2.0','42.104.216.11','Pune, Maharashtra, India',0,NULL,'2026-06-23 14:05:49','2026-06-16 14:05:53','2026-06-16 14:05:50','2026-06-16 14:05:53'),(151,24,'dnVSQkF5ZmNEWEZobHpXeXA0WjVZaDluaDFLcW5yaGQwRjd5UlR2TA==','mobile','Chrome 149','Android 10','106.193.199.173','Pune, Maharashtra, India',0,NULL,'2026-06-23 14:17:30','2026-06-16 14:18:18','2026-06-16 14:17:30','2026-06-16 14:18:18'),(152,24,'cGNwcHhuNE50RFIxRk1QaVlwaWpkb25CSjRFU1Yyak1qRlA2SEFRbQ==','mobile','Chrome 149','Android 10','106.193.199.173','Pune, Maharashtra, India',0,NULL,'2026-06-23 14:18:49','2026-06-16 14:18:51','2026-06-16 14:18:49','2026-06-16 14:18:51'),(154,22,'VE9MQVFqblZFS2FnS3JxYkQ4cVFqbTVPRm1JdzNubjBCaUk0ODB1dQ==','desktop','Edge 149','Windows 10 / 11','103.174.77.243','Pune, Maharashtra, India',0,NULL,'2026-06-23 14:25:49','2026-06-16 14:25:49','2026-06-16 14:25:49','2026-06-16 14:25:49'),(155,25,'TEpCZUU0ZWRsTlhVWG5FWG1aU3ZYRVR1OHpIZHVQSEp3SFNlVDdFcA==','mobile','Chrome 149','Android 10','103.22.140.214','Pune, Maharashtra, India',0,NULL,'2026-06-23 14:52:52','2026-06-16 14:52:53','2026-06-16 14:52:53','2026-06-16 14:52:53'),(156,25,'cWxFeWNhVXp4N2VsQ2MzUDliU09jTW0yTW5VdkoyNkxtVk5FNjhZRQ==','mobile','Chrome 149','Android 10','103.22.140.214','Pune, Maharashtra, India',0,NULL,'2026-06-23 14:56:50','2026-06-18 12:56:54','2026-06-16 14:56:50','2026-06-18 12:56:54'),(159,26,'aHR3M0dtckg4RFdoeDk5S2R3ZGtxUVFUMW9pcXpHRjB2dE1sUlY4Sw==','desktop','Chrome 149','Windows 10 / 11','103.250.190.58','Rajkot, Gujarat, India',0,NULL,'2026-06-23 17:31:56','2026-06-16 17:35:23','2026-06-16 17:31:56','2026-06-16 17:35:23'),(160,27,'cEw0Y2pqcTRXT1dBeWF4YllhbmJqOHVrOTlvdEFCVTRxUGw4bVJ1ag==','desktop','Chrome 148','macOS 10.15.7','103.204.38.118','Pune, Maharashtra, India',0,NULL,'2026-06-23 17:37:52','2026-06-16 17:38:14','2026-06-16 17:37:52','2026-06-16 17:38:14'),(164,26,'dmc1V1NGNVA5UXZBMjNNMXBhYWZMRmxGOHM5eklTMmFSUWY5WGN5Vg==','desktop','Chrome 149','Windows 10 / 11','103.250.190.58','Rajkot, Gujarat, India',0,NULL,'2026-06-23 18:33:55','2026-06-16 18:33:55','2026-06-16 18:33:55','2026-06-16 18:33:55'),(165,30,'dWFhMWptYXNURHNvcDlVcElnYVZxdzNWQ3c0d3RjU2R6V2NUdEJzRQ==','mobile','Chrome 148','Android 16','110.227.185.177','Pune, Maharashtra, India',0,NULL,'2026-06-23 18:35:15','2026-06-16 18:35:37','2026-06-16 18:35:15','2026-06-16 18:35:37'),(166,30,'c1lDZ2duS0d6aXhmMEUxRURZWWgwenNzeXVjeWdpQjQzQklIZzdVcw==','mobile','Chrome 149','Android 10','110.227.185.177','Pune, Maharashtra, India',0,NULL,'2026-06-23 18:35:41','2026-06-16 18:44:36','2026-06-16 18:35:41','2026-06-16 18:44:36'),(168,32,'RkRGWDNuNGd6THZZVk9odmJYTks4VGY1a0FTeFRQQ1FqS043WkF0SQ==','mobile','Chrome 148','Android 10','122.161.243.76','Jammu, Jammu and Kashmir, India',0,NULL,'2026-06-23 18:57:49','2026-06-16 18:58:01','2026-06-16 18:57:49','2026-06-16 18:58:01'),(169,32,'eDQ5SHJlVDNmaFhBS1ZraTFmcEc0WDR4R29TajhnUG9wQVk2d2h0Ng==','mobile','Chrome 148','Android 10','122.161.243.76','Jammu, Jammu and Kashmir, India',0,NULL,'2026-06-23 18:58:09','2026-06-16 18:58:10','2026-06-16 18:58:09','2026-06-16 18:58:10'),(170,33,'MEJENHBlclUxeXltU0VDT0xuZTBMMXE2dzZpQ3U0WExCQ2gzZDVKWQ==','mobile','Chrome 148','Android 10','106.220.141.136','Pune, Maharashtra, India',0,NULL,'2026-06-23 18:58:29','2026-06-16 18:58:29','2026-06-16 18:58:29','2026-06-16 18:58:29'),(171,33,'MTNNYndEY3E5RDRaVFRLYTR6eEhGSkFqUU5ycGw0QnAwQ3hoakdmSQ==','mobile','Chrome 148','Android 10','106.220.141.136','Pune, Maharashtra, India',0,NULL,'2026-06-23 19:00:05','2026-06-17 21:06:34','2026-06-16 19:00:05','2026-06-17 21:06:34'),(172,34,'UG1ZRnFQRWVCTzhqZVRIS1V6WDd3NEVvNG5Uc2JNamxHTVE4UnJVNQ==','mobile','Chrome 149','Android 10','103.159.35.112','Delhi, National Capital Territory of Delhi, India',0,NULL,'2026-06-23 19:10:55','2026-06-16 19:10:56','2026-06-16 19:10:56','2026-06-16 19:10:56'),(173,34,'ckhmZXp5MHZGVjVkbkxmWFZ5SllGa2JaR2JKb2phMDF0dm1XaFlhUQ==','mobile','Chrome 149','Android 10','103.159.35.112','Delhi, National Capital Territory of Delhi, India',0,NULL,'2026-06-23 19:11:24','2026-06-16 19:15:36','2026-06-16 19:11:26','2026-06-16 19:15:36'),(174,35,'RU4yM0ozU1h0TDhRNnhSZk85ZUpwWWJnVG5NY0hmUk84TTlnV1Vjaw==','mobile','Chrome 146','Android 10','152.56.6.27','Aurangabad, Maharashtra, India',0,NULL,'2026-06-23 19:32:20','2026-06-16 19:33:04','2026-06-16 19:32:21','2026-06-16 19:33:04'),(175,35,'NVlPeHhINDNyVUJsVmVSUmMzVzExZWlBaDRlb2puRmpmeUxWcDZIQw==','mobile','Chrome 146','Android 10','152.56.6.27','Aurangabad, Maharashtra, India',0,NULL,'2026-06-23 19:33:08','2026-06-16 19:33:10','2026-06-16 19:33:08','2026-06-16 19:33:10'),(176,37,'alNIRU1CN3JXMkIxOXZ5Um9rcFRyeXZNZzFzUzBpdzF6WFd4OHNZSw==','mobile','Chrome 149','Android 15','152.58.36.111','Surat, Gujarat, India',0,NULL,'2026-06-23 20:33:17','2026-06-16 20:33:46','2026-06-16 20:33:17','2026-06-16 20:33:46'),(177,38,'T1BwdWpSRTlyVW5td0tNY0QzT2dWRmljcGxIQ2I2dU5NVTJ1ZjVuSA==','mobile','Chrome 149','Android 10','157.32.218.38','Pune, Maharashtra, India',0,NULL,'2026-06-23 20:33:38','2026-06-16 20:34:02','2026-06-16 20:33:38','2026-06-16 20:34:02'),(178,38,'eVBaTXBVaER5aG1FdTFER3FmaUl0MWlaWnJIM3VaUlJzM3Q1OE9PVg==','mobile','Chrome 149','Android 10','157.32.218.38','Pune, Maharashtra, India',0,NULL,'2026-06-23 20:34:06','2026-06-17 12:11:41','2026-06-16 20:34:06','2026-06-17 12:11:41'),(179,37,'UmVOYWpHM0dybHlRdk13Zkx6dmo5ZVdSSzRTMXBvNDF1cTloRDJlUA==','mobile','Chrome 149','Android 15','152.58.36.111','Surat, Gujarat, India',0,NULL,'2026-06-23 20:34:19','2026-06-16 20:34:22','2026-06-16 20:34:19','2026-06-16 20:34:22'),(180,37,'bkNVS2Z5b0Nkb1BLYk5KQThZMFU3ZDRaVkpSeHc5ZWllYVJXa21uOQ==','mobile','Chrome 149','Android 15','152.58.36.111','Surat, Gujarat, India',0,NULL,'2026-06-23 20:34:36','2026-06-16 20:34:40','2026-06-16 20:34:37','2026-06-16 20:34:40'),(181,39,'Umo1SkxyUDlOR2YwZHkyWjhkbEozU1ZqZ0ZDTkQ0cEtMemxtVXF1Mg==','mobile','Chrome 148','Android 10','152.59.63.54','Pune, Maharashtra, India',0,NULL,'2026-06-23 20:35:19','2026-06-16 20:35:52','2026-06-16 20:35:19','2026-06-16 20:35:52'),(182,39,'MWxZZjJUU3EzVW5SSTZEbW9ScjFVRzdPdmRhV1g5ZEg1dldJU1NqNQ==','mobile','Chrome 148','Android 10','152.59.63.54','Pune, Maharashtra, India',0,NULL,'2026-06-23 20:36:34','2026-06-16 20:45:11','2026-06-16 20:36:34','2026-06-16 20:45:11'),(183,40,'bzBtMnk0V2IzOEpOSFJmUVRocWRJbkQ3cld5M1dwMlZhTU1ZdERVUg==','mobile','Safari 26','iOS 18.7','152.58.33.95','Pune, Maharashtra, India',0,NULL,'2026-06-23 20:41:00','2026-06-16 20:41:00','2026-06-16 20:41:00','2026-06-16 20:41:00'),(184,40,'Q2EzOUdLa05HNEM5VDhpS1dZUEdKTlowMm1jcXI1UGRQSDc5eVRtUw==','mobile','Safari 26','iOS 18.7','152.58.33.181','Pune, Maharashtra, India',0,NULL,'2026-06-23 20:42:11','2026-06-16 21:04:49','2026-06-16 20:42:11','2026-06-16 21:04:49'),(185,39,'M2VhZWsySWFxbnNLMmpRd3EzcUtOMnNNaVVvTmgxSnpLb2lrbzREWA==','mobile','Chrome 148','Android 10','152.59.63.54','Pune, Maharashtra, India',0,NULL,'2026-06-23 20:45:21','2026-06-16 20:46:46','2026-06-16 20:45:21','2026-06-16 20:46:46'),(186,39,'c3F3RE1SbkxoTUZtYzBtRHpBcUdvVEkxRlhzT2MwcWgzNEM0S0ZldA==','mobile','Chrome 148','Android 10','152.59.63.54','Pune, Maharashtra, India',0,NULL,'2026-06-23 20:47:38','2026-06-16 20:49:18','2026-06-16 20:47:38','2026-06-16 20:49:18'),(187,39,'dmgyeWhtbVBibHo3VnYyODVHNGNLZGpHTzVSdWE2M3pYVjVCWHVBZg==','mobile','Chrome 148','Android 10','152.59.63.54','Pune, Maharashtra, India',0,NULL,'2026-06-23 20:49:37','2026-06-21 00:38:59','2026-06-16 20:49:37','2026-06-21 00:38:59'),(188,41,'bUFPUGIwYXJWc04xbTRXN0ZEcWVmVGp1ZFh2eHhBcU5FWGNrSHZOdg==','mobile','Chrome 149','Android 10','223.233.83.212','Pune, Maharashtra, India',0,NULL,'2026-06-23 20:49:59','2026-06-16 20:49:59','2026-06-16 20:49:59','2026-06-16 20:49:59'),(189,41,'WEZSQ2g1dDBYYnJsN2lRVTRzdUlCV1c2cmNVRXc0ZThmNWpJNGk0YQ==','mobile','Chrome 149','Android 10','223.233.83.212','Pune, Maharashtra, India',0,NULL,'2026-06-23 20:50:51','2026-06-18 16:18:27','2026-06-16 20:50:51','2026-06-18 16:18:27'),(190,42,'NHkxYmVDakRuT3p3Y2Z1S0luT1hyQXhYR20zaksyUUpZQ3JyYlFwbw==','mobile','Chrome 149','Android 10','152.59.63.212','Pune, Maharashtra, India',0,NULL,'2026-06-23 21:15:58','2026-06-16 21:16:21','2026-06-16 21:15:59','2026-06-16 21:16:21'),(191,42,'cVh0YTdZYlcybnRkM2pYeDFRZVNrN2lKeG5mSWNKbVBkeXlaQThYbg==','mobile','Chrome 149','Android 10','152.59.63.212','Pune, Maharashtra, India',0,NULL,'2026-06-23 21:16:24','2026-06-16 21:18:24','2026-06-16 21:16:24','2026-06-16 21:18:24'),(192,42,'Q2ZGQkFJcFhYVjVQVkZ6TkZGaThCUUhGMFQ2QVQxRXpNVnY1aU1VOA==','mobile','Chrome 149','Android 10','152.59.63.212','Pune, Maharashtra, India',0,NULL,'2026-06-23 21:18:36','2026-06-17 00:05:19','2026-06-16 21:18:37','2026-06-17 00:05:19'),(193,22,'UEFaZ3F3N01NTEpTa2xzV3FtVkRHY2JVTmZhT2J1Yng1YTdHVWNmbA==','mobile','Chrome 149','Android 10','152.58.17.121','Pune, Maharashtra, India',0,NULL,'2026-06-23 21:37:49','2026-06-16 21:39:54','2026-06-16 21:37:49','2026-06-16 21:39:54'),(195,43,'MjU0NHpEQ1BadVpKRFB5bU1HZUNkeXFPNGFxQ0NoWGJtckFBeU1tOA==','mobile','Chrome 149','Android 10','49.36.51.198','Pune, Maharashtra, India',0,NULL,'2026-06-23 23:28:41','2026-06-20 22:14:47','2026-06-16 23:28:41','2026-06-20 22:14:47'),(197,44,'QXlFS1NCNGVCZEZiZ1c0d0d4OWxHNHVPS0NPOEhMOWpmOHh2c25HVg==','mobile','Chrome 149','Android 10','152.58.33.55','Pune, Maharashtra, India',0,NULL,'2026-06-24 07:14:57','2026-06-17 07:15:34','2026-06-17 07:14:58','2026-06-17 07:15:34'),(198,44,'SG1DSzV0VHBaTlhUTGM0ZXdLMjB5ZUJDU0YzYVVmMmdpMWRrMXJXZg==','mobile','Chrome 149','Android 10','152.58.33.55','Pune, Maharashtra, India',0,NULL,'2026-06-24 07:15:36','2026-06-17 07:15:39','2026-06-17 07:15:36','2026-06-17 07:15:39'),(199,45,'NlNTRUU0R21rVklyVUQwOGpUdWNKdVppQ1ExQVBXSjZITGZ5dUZiQQ==','desktop','Chrome 149','Windows 10 / 11','103.97.242.194','Pune, Maharashtra, India',0,NULL,'2026-06-24 08:09:31','2026-06-17 08:09:53','2026-06-17 08:09:31','2026-06-17 08:09:53'),(200,46,'VFBUcmdzYnZNR3lzYXNPVlV1Z3YxWU93RERFY0FtRlEyY0xMN1cyNg==','mobile','Chrome 148','Android 10','152.58.32.118','Pune, Maharashtra, India',0,NULL,'2026-06-24 09:11:45','2026-06-17 09:12:07','2026-06-17 09:11:45','2026-06-17 09:12:07'),(201,46,'c0lxaTBzSnhZdVpqRTRCZlBaMFY5bHh0YkhwUTlYSHBvaVUwd21oRw==','mobile','Chrome 148','Android 10','152.58.32.118','Pune, Maharashtra, India',0,NULL,'2026-06-24 09:12:20','2026-06-17 09:14:55','2026-06-17 09:12:20','2026-06-17 09:14:55'),(202,46,'anJnWVNKeW9mS3haOG5GRFBva09Mc1IwcERkanFIREFqM1ljYVp0Sg==','mobile','Chrome 148','Android 10','152.58.32.118','Pune, Maharashtra, India',0,NULL,'2026-06-24 09:19:34','2026-06-21 00:42:38','2026-06-17 09:19:34','2026-06-21 00:42:38'),(203,26,'RlNpTXlBS1ZrMm9OS2FNT3hLRzdtUjAyam9TVVNMbzBqUmo3WE5xSw==','desktop','Chrome 149','Windows 10 / 11','103.250.190.58','Rajkot, Gujarat, India',0,NULL,'2026-06-24 09:43:02','2026-06-17 09:44:32','2026-06-17 09:43:02','2026-06-17 09:44:32'),(204,47,'WWZ2WTluQk5xMVlTUmdHYlgzeEpPekRrMzh2YWIxN1EyQVpkdmFrMw==','mobile','Chrome 149','Android 10','171.61.113.230','Jabalpur, Madhya Pradesh, India',0,NULL,'2026-06-24 09:43:50','2026-06-17 09:44:14','2026-06-17 09:43:51','2026-06-17 09:44:14'),(205,47,'cjNvcDNsb1BscEZZcGpNNDh3VE9icHhiYnQwaEV1NllaWEJ2N3FYaA==','mobile','Chrome 149','Android 10','171.61.113.230','Jabalpur, Madhya Pradesh, India',0,NULL,'2026-06-24 09:44:38','2026-06-17 10:08:22','2026-06-17 09:44:38','2026-06-17 10:08:22'),(206,48,'V0FzcmhYSU1meUU5Ylh6cmlFMEk3YWduMzVRWG1ZTENUaXB6UkRHUw==','desktop','Chrome 149','Windows 10 / 11','106.192.220.249','Pune, Maharashtra, India',0,NULL,'2026-06-24 09:57:11','2026-06-17 09:57:54','2026-06-17 09:57:11','2026-06-17 09:57:54'),(207,48,'SkRoWTRpZzRkMU5ZQ042bjJBTXFwRjdxS2tXZzl3UzBMVFVSanRpbQ==','desktop','Chrome 149','Windows 10 / 11','106.192.220.249','Pune, Maharashtra, India',0,NULL,'2026-06-24 09:57:59','2026-06-17 09:58:00','2026-06-17 09:57:59','2026-06-17 09:58:00'),(208,49,'ekNScmFPblROUXY2dkZhMEJHS1J1clRMQWV6U011ZHh3UGZPam1Gcg==','mobile','Chrome 149','Android 10','152.58.16.172','Pune, Maharashtra, India',0,NULL,'2026-06-24 10:03:37','2026-06-17 10:04:00','2026-06-17 10:03:37','2026-06-17 10:04:00'),(209,49,'NUxUUzN0TTFTTW1PbmxLNmpRVnNQcXVYZnd6VGpwaVQzOXBZQ0RKNA==','mobile','Chrome 149','Android 10','152.58.16.172','Pune, Maharashtra, India',0,NULL,'2026-06-24 10:04:00','2026-06-17 10:06:31','2026-06-17 10:04:00','2026-06-17 10:06:31'),(211,50,'Rk1XTmh4bHhCNnc2a2ZHNVNaZHBaT1Bkb0pnVUFtSXlpUDgyc3IzSg==','mobile','Safari 26','iOS 18.7','223.181.81.192','Raipur, Chhattisgarh, India',0,NULL,'2026-06-24 11:06:10','2026-06-17 21:10:31','2026-06-17 11:06:10','2026-06-17 21:10:31'),(212,52,'T0RmZ3hPdjI3YXpDbTV3Y1pJVTZld0ltSjhvY3JzNXE5dVBZdEIzMg==','mobile','Chrome 149','Android 10','59.184.16.24','Nandurbar, Maharashtra, India',0,NULL,'2026-06-24 11:23:29','2026-06-17 11:23:53','2026-06-17 11:23:29','2026-06-17 11:23:53'),(213,52,'RzlTc1FRYmhPWEFWbXd4eTJwdVVMOHVaMVdtSWE5OUhVcFB2WVZEZQ==','mobile','Chrome 149','Android 10','59.184.16.24','Nandurbar, Maharashtra, India',0,NULL,'2026-06-24 11:23:57','2026-06-17 11:47:01','2026-06-17 11:23:58','2026-06-17 11:47:01'),(214,53,'cExIRXdmUXd6bWIwSmpyWXJ3eUtRVnFjYUtxQUIyQWxJZWNoekx3UQ==','mobile','Chrome 149','Android 10','223.185.41.135','Nagpur, Maharashtra, India',0,NULL,'2026-06-24 11:28:14','2026-06-17 11:28:36','2026-06-17 11:28:14','2026-06-17 11:28:36'),(215,53,'a0hScE11UUNGTVlZSnd5OGYzaHNpR0JmVlFRRGFYajRRcVRxanlxTQ==','mobile','Chrome 149','Android 10','223.185.41.135','Nagpur, Maharashtra, India',0,NULL,'2026-06-24 11:28:44','2026-06-17 11:28:45','2026-06-17 11:28:44','2026-06-17 11:28:45'),(216,53,'VkFlamFBZ05VdmZNRnZaT0dRWjlUVkJycWtFSHl1SzVxa3JHV21obA==','mobile','Chrome 149','Android 10','223.185.41.135','Nagpur, Maharashtra, India',0,NULL,'2026-06-24 11:29:53','2026-06-17 11:30:26','2026-06-17 11:29:53','2026-06-17 11:30:26'),(217,52,'bjlCOEc2TFppSFpqWWxybG5GWUZjVGNkYlNaNU9mTnZqVmc2NjV4QQ==','mobile','Chrome 149','Android 10','59.184.16.24','Nandurbar, Maharashtra, India',0,NULL,'2026-06-24 11:57:41','2026-06-17 16:12:24','2026-06-17 11:57:41','2026-06-17 16:12:24'),(221,55,'ZDlFM281MXpsdUR2eHY5WW0wUDY3ZWZmNWVjalRKSWtWZUJTTEVXRw==','desktop','Chrome 149','Windows 10 / 11','14.96.212.78','Pune, Maharashtra, India',0,NULL,'2026-06-24 12:35:17','2026-06-17 12:35:48','2026-06-17 12:35:17','2026-06-17 12:35:48'),(222,55,'THRabnhEd21helFHbWYzdVF4U1NoUGFlQ0F3Q3lmbmMzZllzRmxjTg==','desktop','Chrome 149','Windows 10 / 11','14.96.212.78','Pune, Maharashtra, India',0,NULL,'2026-06-24 12:36:11','2026-06-17 12:36:14','2026-06-17 12:36:11','2026-06-17 12:36:14'),(227,57,'UmxpQjVyTjlidUVoRm5ScEVCdkpvWDlHbXhGaFAzN3hLZ3A2ZGNtTg==','mobile','Chrome 149','Android 14','106.192.114.113','Pune, Maharashtra, India',0,NULL,'2026-06-24 19:03:01','2026-06-17 19:04:58','2026-06-17 19:03:02','2026-06-17 19:04:58'),(228,58,'Z1Mzb1V6ZE5QUHN4QmgxeEhvdTlJUzFFR2hBNWFpNlRSbTBkUUQ0SA==','mobile','Chrome 149','Android 10','152.56.14.156','Nagpur, Maharashtra, India',0,NULL,'2026-06-24 20:04:16','2026-06-17 20:04:42','2026-06-17 20:04:16','2026-06-17 20:04:42'),(229,59,'Ukk1OWI4bHlmeDdnVHBjTTJQSlJBTDYxZDdSQXY5dm43RHlaTXFiZg==','desktop','Chrome 149','Windows 10 / 11','103.216.147.88','Latur, Maharashtra, India',0,NULL,'2026-06-24 20:26:04','2026-06-17 20:28:37','2026-06-17 20:26:04','2026-06-17 20:28:37'),(230,60,'eGZwUDcxNG1tYjhrUW0wb1BiNEl2Y29XQ1ZjdVRHOEJUQWZPV2FHbQ==','desktop','Chrome 149','Windows 10 / 11','103.216.147.88','Latur, Maharashtra, India',0,NULL,'2026-06-24 20:28:53','2026-06-20 20:51:24','2026-06-17 20:28:53','2026-06-20 20:51:24'),(239,62,'MHcwSnVyNllzMGFrWk1mRU8zRWk5Vm8xMGdvY0thUjNiWU9lWERVbA==','mobile','Chrome 149','Android 10','49.36.48.61','Pune, Maharashtra, India',0,NULL,'2026-06-24 22:57:59','2026-06-17 22:57:59','2026-06-17 22:57:59','2026-06-17 22:57:59'),(240,62,'bks5d3VZSURvbE81WEZ5WFNrUUNmbDNxbDV1a2FacVJ6dThNbTFHQw==','mobile','Chrome 149','Android 10','49.36.48.61','Pune, Maharashtra, India',0,NULL,'2026-06-24 22:58:28','2026-06-17 22:58:29','2026-06-17 22:58:28','2026-06-17 22:58:29'),(241,63,'NlFwY1lNQm05dFQ0U2d3YzFJWFh6amJLcUhIZ2F1cGUzRVZzZGFMeA==','desktop','Chrome 148','Windows 10 / 11','152.58.33.15','Pune, Maharashtra, India',0,NULL,'2026-06-24 23:18:57','2026-06-17 23:24:16','2026-06-17 23:18:58','2026-06-17 23:24:16'),(242,64,'aDh5S3VXZGIzem5Ccmd1TWNTN045Wk93UEFFbUx2VFZ0TXF2dDVuYg==','mobile','Chrome 149','Android 10','42.108.239.21','Pune, Maharashtra, India',0,NULL,'2026-06-24 23:26:44','2026-06-17 23:27:12','2026-06-17 23:26:45','2026-06-17 23:27:12'),(243,64,'bW50SXdxRUJQRVdoSlh2d1dZbmFBVTNtVUd2SVBFbkpwMHpBYVpQdw==','mobile','Chrome 149','Android 10','42.108.239.21','Pune, Maharashtra, India',0,NULL,'2026-06-24 23:27:14','2026-06-17 23:47:48','2026-06-17 23:27:14','2026-06-17 23:47:48'),(249,67,'QTRLRFFDWUV4Q2h0cThFV3VMcnhxa3NIZThqeHNaTGMwMW0zMkhPRQ==','mobile','Chrome 149','Android 10','152.59.13.185','Chiplūn, Maharashtra, India',0,NULL,'2026-06-25 13:08:04','2026-06-18 13:08:49','2026-06-18 13:08:05','2026-06-18 13:08:49'),(250,67,'V1JGRUIyTVFuNGVuZDFzZW1MamljRWdBRGczMWgxTUQwQVhKR0l3WA==','mobile','Chrome 149','Android 10','152.59.13.185','Chiplūn, Maharashtra, India',0,NULL,'2026-06-25 13:08:59','2026-06-18 13:12:12','2026-06-18 13:08:59','2026-06-18 13:12:12'),(251,67,'a3ByWFNsMzgwaTR2Z2dZNjFqOEY0eWtGQjdYb1N2UXVIa1NpR3J6dg==','mobile','Chrome 149','Android 10','152.59.13.9','Chiplūn, Maharashtra, India',0,NULL,'2026-06-25 13:13:43','2026-06-19 18:33:28','2026-06-18 13:13:44','2026-06-19 18:33:28'),(252,27,'ZjFxY3hHbkgwd0k0V2tIbjJmVW94YU83QUJsTGNoTjRBNW1wcEdPSw==','desktop','Chrome 148','macOS 10.15.7','103.204.38.118','Pune, Maharashtra, India',0,NULL,'2026-06-25 13:54:36','2026-06-18 13:54:56','2026-06-18 13:54:36','2026-06-18 13:54:56'),(253,69,'d1Z0OGZzM3V5U285cFB0S0dMRXlPaUtEdHJiT1FrdEl6OUFoMFo1Rg==','mobile','Chrome 149','Android 10','223.233.83.254','Pune, Maharashtra, India',0,NULL,'2026-06-25 15:04:14','2026-06-18 15:04:14','2026-06-18 15:04:14','2026-06-18 15:04:14'),(254,69,'QlloMmdVYkFOOW5DRlBvOFJTU1VhQVVlTkxUM3VRN1RjWGFKTU1WaQ==','mobile','Chrome 149','Android 10','223.233.83.254','Pune, Maharashtra, India',0,NULL,'2026-06-25 15:04:40','2026-06-18 15:05:12','2026-06-18 15:04:40','2026-06-18 15:05:12'),(255,69,'RzFVQUFOaHhBb3hoZTFyclluRnJUNjdkZUd5M0xHdGVaZWJXaXc0dg==','mobile','Chrome 149','Android 10','223.233.83.254','Pune, Maharashtra, India',0,NULL,'2026-06-25 15:05:16','2026-06-18 15:05:18','2026-06-18 15:05:17','2026-06-18 15:05:18'),(263,71,'TDJVbEtySWRQbFdMTXRyNkRDZnhYUHZBYmFiTzFFOHNSUk5yMngzaA==','desktop','Chrome 148','Windows 10 / 11','150.129.159.74','Pune, Maharashtra, India',0,NULL,'2026-06-25 18:01:44','2026-06-18 18:02:07','2026-06-18 18:01:45','2026-06-18 18:02:07'),(264,71,'S0JlUDJmMGFUVzJKcDNteTY3OVdlU0hFSWt4Tk5adlVCN3pGbXVWMg==','desktop','Chrome 148','Windows 10 / 11','150.129.159.74','Pune, Maharashtra, India',0,NULL,'2026-06-25 18:08:06','2026-06-18 18:08:08','2026-06-18 18:08:06','2026-06-18 18:08:08'),(265,71,'M3U2dzlCVFhDV29kVnhVUzN3MnNjMng0UDRPaXNiZDJ5RXBLT09CVQ==','desktop','Chrome 148','Windows 10 / 11','150.129.159.74','Pune, Maharashtra, India',0,NULL,'2026-06-25 18:24:35','2026-06-18 18:25:43','2026-06-18 18:24:35','2026-06-18 18:25:43'),(266,72,'TUMyZVBhMmF6dFRuT3RHNUxvMEF2VDluWGdrTXBLZUo1bDhBS2F0Rg==','desktop','Chrome 148','Windows 10 / 11','223.185.36.47','Nagpur, Maharashtra, India',0,NULL,'2026-06-25 18:40:35','2026-06-18 18:40:57','2026-06-18 18:40:35','2026-06-18 18:40:57'),(267,72,'ODRTZ1JVeW1NYkRVN2h0ektjZGxabFk2SEZ5YkpDZW5zZVp6YWEwOQ==','desktop','Chrome 148','Windows 10 / 11','223.185.36.47','Nagpur, Maharashtra, India',0,NULL,'2026-06-25 18:41:05','2026-06-18 19:56:37','2026-06-18 18:41:05','2026-06-18 19:56:37'),(268,73,'RjVDODZMTXdLT09tS2tBVzV5MmFQN0ZRZnVyVGtKaUlpa1Y4S3RzeQ==','mobile','Chrome 149','Android 10','42.108.75.159','Mumbai, Maharashtra, India',0,NULL,'2026-06-25 19:34:31','2026-06-18 19:34:31','2026-06-18 19:34:31','2026-06-18 19:34:31'),(269,73,'dWNOcHYySHRSempqNU9aQXJwY3d6Q2RhTjZRaWNUVXFDVjc2aUV3Tw==','mobile','Chrome 149','Android 10','42.108.75.159','Mumbai, Maharashtra, India',0,NULL,'2026-06-25 19:36:19','2026-06-18 19:36:22','2026-06-18 19:36:19','2026-06-18 19:36:22'),(270,73,'RmozRlZsREhBZGtoN3N3OVJiUlN5TENTQjh3RXZqYm11Z01uQnNLVA==','mobile','Chrome 149','Android 10','42.108.75.159','Mumbai, Maharashtra, India',0,NULL,'2026-06-25 19:39:05','2026-06-18 19:39:08','2026-06-18 19:39:05','2026-06-18 19:39:08'),(271,73,'Z1FQWXBCc0tPa1IySzVXM2JwV1NtZUpHUnl3U2lJSU5NWlBrVHpwcg==','mobile','Chrome 149','Android 10','42.108.75.159','Mumbai, Maharashtra, India',0,NULL,'2026-06-25 20:10:30','2026-06-20 20:19:17','2026-06-18 20:10:30','2026-06-20 20:19:17'),(272,74,'ZldjWTBVS3VFV2NkdmwwaTB5aHVldVJ0dnZaalJQcGcyY284amtkOA==','mobile','Chrome 149','Android 10','103.235.122.96','Mumbai, Maharashtra, India',0,NULL,'2026-06-25 20:28:59','2026-06-18 20:29:21','2026-06-18 20:28:59','2026-06-18 20:29:21'),(274,74,'SE1hVTluVktvWmJ4NWZrSWxYSlh1NzNkUXd2bFo5SG51eEw4QkNLdQ==','mobile','Chrome 149','Android 10','103.235.122.96','Mumbai, Maharashtra, India',0,NULL,'2026-06-25 20:37:26','2026-06-18 20:37:26','2026-06-18 20:37:26','2026-06-18 20:37:26'),(275,74,'dHM4QkxxekMxOGZraFFCbWxsWllnY0hvNGpMR2pYb0Y4aDRiM2oyRw==','mobile','Chrome 149','Android 10','103.235.122.96','Mumbai, Maharashtra, India',0,NULL,'2026-06-25 20:41:43','2026-06-18 20:41:43','2026-06-18 20:41:43','2026-06-18 20:41:43'),(277,74,'TmNyNXpBS3BjYzViQTJjeTVzMkd2a25uQm5PWGUxQTBTazJaenhBTA==','mobile','Chrome 149','Android 10','103.235.122.96','Mumbai, Maharashtra, India',0,NULL,'2026-06-26 02:13:01','2026-06-19 02:13:01','2026-06-19 02:13:01','2026-06-19 02:13:01'),(278,74,'bWtMRXZJbWI1VVhZZWlBS1F3czNlN21DY0ZNd3dBalV3Z0h1cnpZSA==','mobile','Chrome 149','Android 10','103.235.122.96','Mumbai, Maharashtra, India',0,NULL,'2026-06-26 09:59:05','2026-06-19 09:59:05','2026-06-19 09:59:05','2026-06-19 09:59:05'),(279,74,'Wmo3ZU9Mbkxqb1FYTUNQdm9RRnpWZGpXYndrY3g5dTNSaG1vTnRsNQ==','mobile','Chrome 149','Android 10','42.108.72.9','Mumbai, Maharashtra, India',0,NULL,'2026-06-26 11:36:42','2026-06-19 11:47:51','2026-06-19 11:36:42','2026-06-19 11:47:51'),(280,74,'ZHNZSE5DYktoakhQcXZ2aUxSclFZRjg3c1Fpb0UwNjlrbjM4YUZkQQ==','mobile','Chrome 149','Android 10','42.108.72.9','Mumbai, Maharashtra, India',0,NULL,'2026-06-26 12:07:41','2026-06-19 12:07:56','2026-06-19 12:07:41','2026-06-19 12:07:56'),(281,74,'bVd6cFd3cTZJV1pGVU10MHhxMU1abFdRQW9iWTNSNkRvdG5ITDJDWA==','desktop','Chrome 148','Windows 10 / 11','103.58.4.75','Mumbai, Maharashtra, India',0,NULL,'2026-06-26 14:17:44','2026-06-19 16:39:19','2026-06-19 14:17:44','2026-06-19 16:39:19'),(282,76,'bnBFTVlUd3RVNmJiaEs5V054OTZLQkZ2c1piU3lkbFFRc1g0TWw5Mg==','desktop','Firefox 152','Windows 10 / 11','49.36.41.206','Nagpur, Maharashtra, India',0,NULL,'2026-06-26 17:53:09','2026-06-19 18:03:56','2026-06-19 17:53:09','2026-06-19 18:03:56'),(283,76,'VDVwQjM2dHBGcHRhQjBxQ2g2R2YyMGtRcWRSVVc4bmY0alRIcW54NQ==','desktop','Firefox 152','Windows 10 / 11','49.36.41.206','Nagpur, Maharashtra, India',0,NULL,'2026-06-26 18:04:33','2026-06-19 18:04:53','2026-06-19 18:04:33','2026-06-19 18:04:53'),(284,67,'REY4NThBZzQwNWp1YkN3OTZJaUtITG0ySHhJTnViNlBHWXB1M3Jnaw==','mobile','Chrome 149','Android 10','117.229.132.125','Pune, Maharashtra, India',0,NULL,'2026-06-26 18:33:41','2026-06-19 18:34:21','2026-06-19 18:33:41','2026-06-19 18:34:21'),(289,76,'R2d6aXNxYnlpcnJMS21aSldlVjRIQXNHazZlZDB0bkpYcnVBODJVYQ==','desktop','Admin Impersonation',NULL,'122.183.33.252','Admin Impersonation',1,2,'2026-06-19 23:28:49','2026-06-19 22:28:49','2026-06-19 22:28:49','2026-06-19 22:28:49'),(295,78,'SU8wNWdiWmxidkhtZENJWlljcUU5QlZVY08yR3JLQ0pFRDA1RURnaA==','mobile','Samsung Browser 30','Android 10','223.228.38.21','Pune, Maharashtra, India',0,NULL,'2026-06-27 10:59:39','2026-06-20 10:59:52','2026-06-20 10:59:39','2026-06-20 10:59:52'),(296,78,'U1dRMUFmWXlOWThCdFlWSTlHN0JjYU56RVNxZ2tlV2hMMmNhNHE1cQ==','mobile','Samsung Browser 30','Android 10','223.228.38.21','Pune, Maharashtra, India',0,NULL,'2026-06-27 11:00:05','2026-06-20 11:00:07','2026-06-20 11:00:05','2026-06-20 11:00:07'),(297,79,'NlpXS0ZCNkF0ZUxCeE1KUUpxZjFyMkpRcTFGaHFSZ0hUTENmTThiMA==','desktop','Chrome 149','Windows 10 / 11','219.91.251.56','Pune, Maharashtra, India',0,NULL,'2026-06-27 11:37:21','2026-06-20 11:45:33','2026-06-20 11:37:21','2026-06-20 11:45:33'),(302,81,'bzNWQ1E1dVE3QWpFQmN2M0JwQm8zOU9rdnpYZnR3TEJMaDBrSmN3cA==','desktop','Chrome 115','Linux','106.200.122.147','Mumbai, Maharashtra, India',0,NULL,'2026-06-27 15:00:12','2026-06-20 15:00:13','2026-06-20 15:00:13','2026-06-20 15:00:13'),(303,81,'Qjh4ckV4dDJtbHdxMkVOZHlRTmRJVVNUZjBSY1dwYXNEbzhRU0JHTA==','desktop','Chrome 115','Linux','106.200.122.147','Mumbai, Maharashtra, India',0,NULL,'2026-06-27 15:01:01','2026-06-20 15:02:33','2026-06-20 15:01:01','2026-06-20 15:02:33'),(314,64,'Q2NZeGxGa2x0VkN6ZFpOOFBqZm5iUlVIeVM5RGY0VXYxOUFBakFGbg==','mobile','Chrome 149','Android 10','42.108.238.109','Pune, Maharashtra, India',0,NULL,'2026-06-27 19:57:38','2026-06-20 19:58:14','2026-06-20 19:57:38','2026-06-20 19:58:14'),(316,39,'UkZaaERlRDB6NHNXMUtOS2RTTFYwekNVTXlFWng3YjYxeFg4Y2NSSQ==','mobile','Chrome 148','Android 10','152.58.16.89','Pune, Maharashtra, India',0,NULL,'2026-06-28 00:39:12','2026-06-21 00:39:27','2026-06-21 00:39:12','2026-06-21 00:39:27'),(317,39,'UEhMcXBNZFF3SldGSnd0eldMbG9XZHBoWjBFa2VDanZCN2FCdEIxdw==','mobile','Chrome 148','Android 10','152.58.16.89','Pune, Maharashtra, India',0,NULL,'2026-06-28 00:39:33','2026-06-21 00:40:29','2026-06-21 00:39:33','2026-06-21 00:40:29'),(320,83,'UnB6Q3R5dUR2MVl3N0RIVnlDcGFYalllc0h4QVBzbDdLWjZpQ1BuaQ==','mobile','Chrome 149','Android 10','152.59.63.82','Pune, Maharashtra, India',0,NULL,'2026-06-28 14:50:00','2026-06-21 14:50:25','2026-06-21 14:50:00','2026-06-21 14:50:25'),(321,83,'VWpiU3JVQms3Wk85UTVSY3FxeHZBbERPdFR2ZlZmRXpldHVoZGp1Zw==','mobile','Chrome 149','Android 10','152.59.63.82','Pune, Maharashtra, India',0,NULL,'2026-06-28 14:50:35','2026-06-21 14:50:36','2026-06-21 14:50:35','2026-06-21 14:50:36'),(323,84,'cHpRZTFjUFRuek5ua1Nqc1pTaFR0MmFZNGRaZE16UHlYdHYwOXNTVw==','desktop','Chrome 149','Windows 10 / 11','106.220.235.82','Latur, Maharashtra, India',0,NULL,'2026-06-28 15:45:05','2026-06-21 15:51:46','2026-06-21 15:45:05','2026-06-21 15:51:46'),(324,85,'b0FPV3V3aHlLdGFkQnFMRkRSV0Zxc0xyREhIWmlxWXZQcjYxbVNBRg==','mobile','Chrome 149','Android 10','152.58.33.48','Pune, Maharashtra, India',0,NULL,'2026-06-28 16:03:04','2026-06-21 16:03:04','2026-06-21 16:03:04','2026-06-21 16:03:04'),(328,88,'UXpTdWpwaHFueEZkSmw1OUczb0R0U3hnUEQ5eEs0dzRrV0UxZDBFTg==','desktop','Chrome 149','Windows 10 / 11','123.201.192.28','Pune, Maharashtra, India',0,NULL,'2026-06-29 12:25:09','2026-06-22 14:37:14','2026-06-22 12:25:09','2026-06-22 14:37:14'),(329,88,'NWZxcW01MVFZRGdSczFjeEc5ZkJjajRVRmY2eEZUZzcyWUhYc3FpWQ==','desktop','Chrome 149','Windows 10 / 11','123.201.192.28','Pune, Maharashtra, India',0,NULL,'2026-06-29 12:26:00','2026-06-22 12:26:01','2026-06-22 12:26:01','2026-06-22 12:26:01'),(330,86,'Z0hDVUdONklpMHFWTmUwRVI0TlA3M1RaUk1DaEFYdTFDbmhjSnVPaA==','mobile','Safari 26','iOS 18.7','152.58.17.5','Pune, Maharashtra, India',0,NULL,'2026-06-29 13:33:15','2026-06-22 13:33:15','2026-06-22 13:33:15','2026-06-22 13:33:15'),(331,86,'NVYwNlVwQmhXcVA5YTJ4QTdOZmw4RGpuNW8zNjgzUUpHRm5sWGdjeQ==','mobile','Safari 26','iOS 18.7','152.58.17.5','Pune, Maharashtra, India',0,NULL,'2026-06-29 14:34:16','2026-06-22 14:34:20','2026-06-22 14:34:16','2026-06-22 14:34:20'),(332,88,'UWpWblF2ODF1MmpRZ092a0dXRm1tc0w1R1RGc0JaWGNkQTJpeXd4Rg==','desktop','Chrome 149','Windows 10 / 11','123.201.192.28','Pune, Maharashtra, India',0,NULL,'2026-06-29 15:33:03','2026-06-22 15:33:04','2026-06-22 15:33:04','2026-06-22 15:33:04'),(333,3,'MEZhZmVON01wMDVCRVM2cnA4ajc4ZVJiWFkxRFdQOUtyOTBzUWNCcA==','mobile','Chrome 149','Android 10','152.56.157.78','Kolkata, West Bengal, India',0,NULL,'2026-06-29 19:47:05','2026-06-24 10:21:41','2026-06-22 19:47:05','2026-06-24 10:21:41'),(334,89,'cGZCR1ZTQ3hITEdDR25WNlNrdGRjeFdiaE5raUlSZkRNVFdMbEtERQ==','mobile','Samsung Browser 30','Android 10','42.107.82.14','Pune, Maharashtra, India',0,NULL,'2026-06-29 20:28:54','2026-06-22 20:28:54','2026-06-22 20:28:54','2026-06-22 20:28:54'),(339,50,'a1U2SFlaMUlPUXNOQ1Q1SmlteDVFbWN1MFJReTRpSTZvclA3clFoNQ==','mobile','Unknown Browser','iOS 26.4.2','106.202.76.139','Indore, Madhya Pradesh, India',0,NULL,'2026-06-29 23:12:06','2026-06-23 15:50:02','2026-06-22 23:12:06','2026-06-23 15:50:02'),(340,90,'c0VGVFdjb2dvZDZlU05MNXo5VTFTT1ZnbnRWRm5ZRXV2dHFJcVd5aQ==','mobile','Chrome 149','Android 12','49.34.225.82','Ahmedabad, Gujarat, India',0,NULL,'2026-06-30 01:13:22','2026-06-23 01:13:22','2026-06-23 01:13:22','2026-06-23 01:13:22'),(341,90,'QjZiempNNzUxbDZDdVpmZjZld3QxTnVBRjdQT0hMWk40TjlFSkcweQ==','mobile','Chrome 123','Android 12','49.34.225.82','Ahmedabad, Gujarat, India',0,NULL,'2026-06-30 01:15:01','2026-06-23 01:15:59','2026-06-23 01:15:01','2026-06-23 01:15:59'),(342,88,'T29aWk5BcUR4RWZ4cHdGWnlKeUp5SmFvTHlCU2N5eDdjTndTVG11Rw==','desktop','Chrome 149','Windows 10 / 11','123.201.192.28','Pune, Maharashtra, India',0,NULL,'2026-06-30 09:47:15','2026-06-24 11:36:39','2026-06-23 09:47:15','2026-06-24 11:36:39'),(343,91,'NVlkc2RzS2t6bWZVYm1xeUVnRk45Z1p3N3pCSUN6VGZHbmZKVGc0eQ==','desktop','Chrome 149','Windows 10 / 11','106.215.178.95','Pune, Maharashtra, India',0,NULL,'2026-06-30 19:07:45','2026-06-23 19:18:55','2026-06-23 19:07:45','2026-06-23 19:18:55'),(344,92,'OUxLVUdjR3U1VThpRlJKUjZPZU5vOXBiUjIya2dwcDNSWXltb3c2TQ==','mobile','Unknown Browser','iOS 26.5.0','223.228.54.43','Mumbai, Maharashtra, India',0,NULL,'2026-06-30 20:58:10','2026-06-23 20:58:10','2026-06-23 20:58:10','2026-06-23 20:58:10'),(345,92,'UHAwRm41TllqelRlWk9wSkxkYkVGN3dOZ29RYTc3b0Z6Znhxc0l2eQ==','mobile','Unknown Browser','iOS 26.5.0','223.228.54.43','Mumbai, Maharashtra, India',0,NULL,'2026-06-30 20:58:38','2026-06-24 07:17:13','2026-06-23 20:58:38','2026-06-24 07:17:13'),(346,17,'MUlJc3pIUGRzdVFlTGhWSHJEakhIV2g4NVF1ZGl4SzlQSGF1N3B4dA==','mobile','Chrome 148','Android 10','152.56.245.82','Jabalpur, Madhya Pradesh, India',0,NULL,'2026-06-30 23:36:28','2026-06-23 23:36:28','2026-06-23 23:36:28','2026-06-23 23:36:28'),(347,93,'bjFWT0ExNEIyZHZrbHRsT2JyZXFxdWRta2JiZEVod2JTSEtmZ29xTQ==','mobile','Samsung Browser 30','Android 10','157.48.99.187','Jaipur, Rajasthan, India',0,NULL,'2026-06-30 23:49:38','2026-06-23 23:49:38','2026-06-23 23:49:38','2026-06-23 23:49:38'),(348,93,'UE1Sc0J1NDYyaHl5S3ZQaEt6U21zREhzb2lURnUyMVBPcnBwcXV5Yg==','mobile','Samsung Browser 30','Android 10','157.48.99.187','Jaipur, Rajasthan, India',0,NULL,'2026-06-30 23:49:56','2026-06-23 23:50:13','2026-06-23 23:49:57','2026-06-23 23:50:13'),(349,83,'UFVIY0tUb1k5bTRtYncxVFMwM0RUdUcwelpZUThQQnVZcFowZTJkNA==','mobile','Chrome 149','Android 10','47.11.6.176','Nagpur, Maharashtra, India',0,NULL,'2026-07-01 08:57:07','2026-06-24 09:02:00','2026-06-24 08:57:07','2026-06-24 09:02:00'),(352,94,'aXVMTFI2dEVFa2MwTENQM1hXeTlnZHRMakl3MWU1V00xYkY1OVpueA==','mobile','Chrome 149','Android 10','47.11.1.77','Nagpur, Maharashtra, India',0,NULL,'2026-07-01 09:15:58','2026-06-24 09:17:36','2026-06-24 09:15:58','2026-06-24 09:17:36'),(353,91,'Q1V4WjRXNW9yVFRCNGR3T1BVb0NYS1ZocVl0RmdLNDFIczlleThaQg==','desktop','Chrome 148','Windows 10 / 11','106.214.47.9','Pune, Maharashtra, India',0,NULL,'2026-07-01 09:48:51','2026-06-24 09:49:29','2026-06-24 09:48:51','2026-06-24 09:49:29'),(354,91,'dDRoYjRKZE9sQVhpUVlxRDQ1YkFyNGtBd1l1Y25TUDNZM1hvSUpWQw==','desktop','Chrome 149','Windows 10 / 11','106.215.178.95','Pune, Maharashtra, India',0,NULL,'2026-07-01 09:54:21','2026-06-24 09:54:22','2026-06-24 09:54:21','2026-06-24 09:54:22'),(355,91,'RmVoVjdmWUprZjhoQXl0b2JUSFRTdWlYeHVUZUh6RVNMZFlNS0x2cA==','desktop','Chrome 149','Windows 10 / 11','106.215.178.95','Pune, Maharashtra, India',0,NULL,'2026-07-01 09:56:46','2026-06-24 12:38:34','2026-06-24 09:56:46','2026-06-24 12:38:34'),(356,95,'Q2ZKTGNsbWtQcjRUaVdOZDlCOGNkdFBZMElhRVM2NDRScHp4eFVjbQ==','mobile','Chrome 149','Android 10','106.220.218.217','Pune, Maharashtra, India',0,NULL,'2026-07-01 10:06:19','2026-06-24 10:06:19','2026-06-24 10:06:19','2026-06-24 10:06:19'),(357,96,'blRLZFc4ZFEwT1ZSS0pOejIyWG5TOGhEQXlnTXpPWGRURmV2ZnRMVQ==','desktop','Chrome 149','Windows 10 / 11','117.248.203.68','Pune, Maharashtra, India',0,NULL,'2026-07-01 10:19:57','2026-06-24 10:20:09','2026-06-24 10:19:57','2026-06-24 10:20:09'),(358,96,'bDEyMmh2c3A0SnhRU3hkUFdJZXRzdnphVzJwOEtpNzdsSGpLVldvOQ==','desktop','Chrome 149','Windows 10 / 11','117.248.203.68','Pune, Maharashtra, India',0,NULL,'2026-07-01 10:20:15','2026-06-24 10:28:09','2026-06-24 10:20:15','2026-06-24 10:28:09'),(362,92,'bWpBQnk1RnExaGZxdktkZEN2czM4UmtjekYwdlVUVEhhcnU1UzFGTw==','desktop','Chrome 149','Windows 10 / 11','223.181.59.222','Mumbai, Maharashtra, India',0,NULL,'2026-07-01 11:38:00','2026-06-24 12:07:45','2026-06-24 11:38:01','2026-06-24 12:07:45'),(363,62,'NmozWnpsZVB3UTh4eXd2SmF5NnVFTHBlVlU5VG1VaUV4a2laNXhRdg==','mobile','Chrome 149','Android 10','152.58.32.210','Pune, Maharashtra, India',0,NULL,'2026-07-01 12:00:50','2026-06-24 12:07:19','2026-06-24 12:00:50','2026-06-24 12:07:19'),(364,92,'ZWUzMjJFNWRvM3doaUxhQjE3MXk2ZFQxTm81Wk9YdHIwS1VvdGFYWA==','desktop','Chrome 149','Windows 10 / 11','223.181.59.222','Mumbai, Maharashtra, India',0,NULL,'2026-07-01 12:08:13','2026-06-24 12:08:13','2026-06-24 12:08:13','2026-06-24 12:08:13'),(365,98,'a0pZODFHZ3ViekxPdlRaY2tnQ0tkY1R6RElmYUhxbzF0R1lNUDJPbA==','desktop','Chrome 148','Windows 10 / 11','157.119.201.150','Mumbai, Maharashtra, India',0,NULL,'2026-07-01 12:35:24','2026-06-24 12:35:46','2026-06-24 12:35:24','2026-06-24 12:35:46'),(366,98,'eGFrWEJMM3ZFZHZ4Mm43NmNicVExeE8waHBrUzhhYTNSdENWbjdxcw==','desktop','Chrome 148','Windows 10 / 11','157.119.201.150','Mumbai, Maharashtra, India',0,NULL,'2026-07-01 12:35:48','2026-06-24 12:44:58','2026-06-24 12:35:48','2026-06-24 12:44:58'),(367,98,'cWhQRE5UMlZWNmhqa1ZXaFFBbW5tUTBhMk55eFYzUGplQWlXRnFkTg==','desktop','Chrome 148','Windows 10 / 11','157.119.201.150','Mumbai, Maharashtra, India',0,NULL,'2026-07-01 12:46:03','2026-06-24 12:46:03','2026-06-24 12:46:03','2026-06-24 12:46:03'),(368,97,'cXVrdDFXWFNOMU03RnNLWFhlT1cwejhwSUI1Y0lKU3lxU3pxQ1Nlbg==','desktop','Chrome 149','Windows 10 / 11','103.160.175.18','Pune, Maharashtra, India',0,NULL,'2026-07-01 12:46:36','2026-06-24 12:47:15','2026-06-24 12:46:36','2026-06-24 12:47:15'),(369,92,'dlhvWDBRczVqWU9tanVHb0VHWDFGalJzTDFvR09lcGFEeHVTN1VaSQ==','desktop','Chrome 149','Windows 10 / 11','223.181.59.222','Mumbai, Maharashtra, India',0,NULL,'2026-07-01 13:08:44','2026-06-24 13:08:58','2026-06-24 13:08:44','2026-06-24 13:08:58'),(370,92,'Zk1FVmlzUzJ2Y1I2aEdHUnE2Z3FwUkFIREEyQW5FaHN1OUc1RWN2cA==','desktop','Chrome 149','Windows 10 / 11','223.181.59.222','Mumbai, Maharashtra, India',0,NULL,'2026-07-01 13:09:44','2026-06-24 13:38:04','2026-06-24 13:09:44','2026-06-24 13:38:04'),(371,67,'MTRicGtSNWNKMlVQSTducEROMDJ3MUgzRVRBNnlPV1laQ1pFRWZNcw==','mobile','Chrome 149','Android 10','117.229.145.76','Pune, Maharashtra, India',0,NULL,'2026-07-01 14:30:31','2026-06-24 14:31:54','2026-06-24 14:30:31','2026-06-24 14:31:54');
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
) ENGINE=InnoDB AUTO_INCREMENT=99 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (3,'Riddhi Marda','mardariddhi04@gmail.com','7449924397','$2y$12$fcWgR2SxsTVs/EO/fwP9P.AjCgbUKG3CfZd7OMzwGgxN9c6oA.Qbi','student',0,NULL,'2026-06-10 20:36:16','2026-06-22 19:47:05',0,'MEZhZmVON01wMDVCRVM2cnA4ajc4ZVJiWFkxRFdQOUtyOTBzUWNCcA==',NULL,NULL,'2026-06-29 19:47:05',NULL,NULL,NULL,'RIDDKQZFH',NULL,0,'2026-06-10 20:36:45'),(4,'Sneha Hake','snehahake9@gmail.com','8208585385','$2y$12$Fge1Zc.FiX5It3W79euPDOvuh85OHWkGjmjMMK./X1Pql7z7SyHMC','student',0,NULL,'2026-06-11 13:15:03','2026-06-11 13:18:19',0,'cEdjbFJVZzlVelJEU2pEbGtIY3B2dUdnaGFFVEI2YUJxOWVKZUhXWg==',NULL,NULL,'2026-06-18 13:18:19',NULL,NULL,NULL,'SNEHDTKY6',NULL,0,'2026-06-11 13:17:31'),(5,'Rohan Kolety','rohankolety23@gmail.com','8850480765','$2y$12$ExjfI6OfHjmpobwqHUM4ve/82Uu0tyZfA87E5ma/ATaJyiXjC/rJ.','student',1,NULL,'2026-06-11 22:30:23','2026-06-24 09:05:01',0,NULL,NULL,NULL,'2026-07-01 09:05:01',NULL,NULL,NULL,'ROHA7TG2P',NULL,0,'2026-06-11 22:31:13'),(6,'Pratik Kokadwar','pratikkokadwar@gmail.com','7020843696','$2y$12$Baye0l2Mnf/tPlJA1e8jluFrJ4Q4gA9BZbRYgYsedk0cKy5X4Krze','student',1,NULL,'2026-06-12 14:30:56','2026-06-12 14:42:36',0,'UHFTbzdEejF5TGt1ODd4VWF6ZmJzSnk5ZHNCcnA3cE9icFVVeGJQMA==',NULL,NULL,'2026-06-19 14:32:10',NULL,NULL,NULL,'PRAT9CCLC',NULL,0,'2026-06-12 14:31:45'),(7,'Mayuri kokil','mayurikokil2510@gmail.com','7709625877','$2y$12$O4VcHNwx7J/QpEqrKbgVEuZjt8ELu7AC/ryDi6BBlHTBsrTci2XjK','student',0,NULL,'2026-06-12 14:38:41','2026-06-12 14:48:25',0,'d21HOTlNOHhjcmZkTUZObU1ZM2xHVGlIRW9RQWFrcVo2OTluWFJrdQ==',NULL,NULL,'2026-06-19 14:48:25',NULL,NULL,NULL,'MAYU68Z11',NULL,0,'2026-06-12 14:39:20'),(8,'Shraddha Gharke','shraddhagharke16@gmail.com','8788885926','$2y$12$gJy2lX5fgwVonuC3vSPcEONnSl5YxEhqZuKnP73Q.q/bVaZqkhkCC','student',0,NULL,'2026-06-12 17:01:47','2026-06-12 17:03:53',0,'MXhseldVM3JyVTd2TlRWRnpQUU9NRjJjVjl4eUZ2eUQ5QU9TTmxwVQ==',NULL,NULL,'2026-06-19 17:03:53',NULL,NULL,NULL,'SHRAVJZ8B',NULL,0,'2026-06-12 17:03:26'),(9,'Bhumika golani','bhumigolani1@gmail.com','7720059068','$2y$12$XgVIKuG1RtrBiTsmh7MOnOMxy4uc7aYWgsWShVP/jLvAVYYAlU0sC','student',0,NULL,'2026-06-12 17:15:36','2026-06-12 17:16:46',0,'U3h4TktUSWRiQXJaellUN1dTOVYyUHQ4bnpZV2ZoN1NVdHVkMDlaRg==',NULL,NULL,'2026-06-19 17:16:46',NULL,NULL,NULL,'BHUMLZB0D',NULL,0,'2026-06-12 17:16:37'),(10,'Rutuja mundada','rutujamundada96@gmail.com','9156050126','$2y$12$AJshJir8n.8jtJLFHmVW0.u08gPx2saPGBF7TGqWVp/4uh5.eBCwW','student',0,NULL,'2026-06-12 17:28:19','2026-06-12 17:29:24',0,'ZTYwamx0aTBuMGpYS2NsYnNxREVuUHRuMmlSNnFXVjNyZjhic3doaw==',NULL,NULL,'2026-06-19 17:28:55',NULL,NULL,NULL,'RUTUFHI07',NULL,0,'2026-06-12 17:29:24'),(12,'SHRADDHA SHIVLING KOLI','shraddhakoli1804@gmail.com','8317268595','$2y$12$Uz9pThotQolS0SPrUJAleuTh3b4YzBT0TnOwgXz4/TMJtFUKXpMQW','student',0,NULL,'2026-06-14 10:49:13','2026-06-14 10:50:07',0,'VGI4U3VvSjZUS3Z1WTlhWlh4Uks4cThnRWZwNEZWa2hTWVRXN1lieQ==',NULL,NULL,'2026-06-21 10:50:07',NULL,NULL,NULL,'SHRAH8N3M',NULL,0,'2026-06-14 10:49:49'),(14,'Yug mutha','ymutha424@gmail.com','9405435675','$2y$12$pnVqz5qbtERYNFXnJabhK.ZtPjbWqI7wW04PzisKTBCKuqlI2Lxjm','student',0,NULL,'2026-06-15 12:24:33','2026-06-15 12:25:08',0,'MHFkNWc2VEFvUFJEd0s0R2YxbGN1UGRSSnU1S1RqU3hzYkxlZG5hZg==',NULL,NULL,'2026-06-22 12:24:49',NULL,NULL,NULL,'YUGM7GZK6',NULL,0,'2026-06-15 12:25:08'),(15,'ANUMITA SINGH','anumitasingh0511@gmail.com','6307750923','$2y$12$BKQbnsRTGjXNnt/V5cbkz.Z1zoXk76GTr9F3nKV93IH8ZRPd.V0d2','student',0,NULL,'2026-06-15 23:59:15','2026-06-16 00:00:16',0,'WktQa0dnY1RrMzRMeTdpMm5YNnZJVDc1NWQybDhyOXpENWdxbTdSNA==',NULL,NULL,'2026-06-23 00:00:16',NULL,NULL,NULL,'ANUMNIF25',NULL,0,'2026-06-16 00:00:03'),(16,'Tabrez Khan','pubggamer94442@gmail.com','9167594941','$2y$12$IDCba/hcVyvy3AVF1RbCJebhyZFv0h2QzVZsyh9UcaKsh7hwfqDEm','student',1,NULL,'2026-06-16 00:32:48','2026-06-16 00:36:12',0,'bEpHdkhSWVM2SDZNR2M5MUF6SjhnS05qSEhGdWd1UWRCNHZIbklxYg==',NULL,NULL,'2026-06-23 00:33:58',NULL,NULL,NULL,'TABRIVVF2',NULL,0,'2026-06-16 00:35:09'),(17,'Rashi Agrawal','rashiagrawal0122@gmail.com','9302541018','$2y$12$0qon0EBDt.ikR80Z6sWZS.9sB5tRbwxbhA0SNetWwF.T84maBrlUK','student',0,NULL,'2026-06-16 01:25:17','2026-06-23 23:36:28',0,'MUlJc3pIUGRzdVFlTGhWSHJEakhIV2g4NVF1ZGl4SzlQSGF1N3B4dA==',NULL,NULL,'2026-06-30 23:36:28',NULL,NULL,NULL,'RASHD86A4',NULL,0,NULL),(18,'Raj Mantri','rajmantri6@gmail.com','7798963362','$2y$12$FuQSZ1.H1d0TuUbq3NU5hO/5GwpKUXsbwcHAbnEyO/sxLhkWxx/zS','student',0,NULL,'2026-06-16 12:35:00','2026-06-16 12:36:05',0,'VmlmMmhHOFdVU1g2Slh1dDUybDM3enFHTmFkTG8ydXFxNk9RaTF5Qw==',NULL,NULL,'2026-06-23 12:36:05',NULL,NULL,NULL,'RAJMP18IU',NULL,0,'2026-06-16 12:35:41'),(19,'Aditya Ramesh Gaikwad','adityagramesh2004@gmail.com','9021178829','$2y$12$Chun0UWqR8T6i6gRjmAZUew9q1/njM6rv.Vk/Qqp7C1ZOAhWjTM5y','student',0,NULL,'2026-06-16 12:36:57','2026-06-16 12:38:48',0,'cTZSSEFvSHFuaWlFd0pzdHhROThKZXFQRG5ZdEVlQUF0d1dWT2hqTA==',NULL,NULL,'2026-06-23 12:37:09',NULL,NULL,NULL,'ADITLYTHZ',NULL,0,'2026-06-16 12:38:48'),(21,'Akansha Malpani','ak.anshamalpani13@gmail.com','9423816450','$2y$12$rvtv/NxpR3AT/.Ke1m9Q8uK7eP8Pspubh4gIlpx23L5.qF6AmsNGC','student',0,NULL,'2026-06-16 13:39:47','2026-06-16 13:40:41',0,'SzVzUHpJZ0g1RVBlUHN4SndCeGZnVzJsQUR1OWxSMVVSMFpNc1FyZg==',NULL,NULL,'2026-06-23 13:40:41',NULL,NULL,NULL,'AKAN0ZB5Y',NULL,0,'2026-06-16 13:40:31'),(22,'Sancheti Lakade & Associates','capranavsancheti@gmail.com','7030133165','$2y$12$zY6piKl1C9C/DixUz4W1hufsXfLY7G7co3mYVfeYazt8mT2gJOBAi','firm',1,'firm/logo/1781599573_logo.jpeg','2026-06-16 14:03:23','2026-06-16 21:37:49',0,'UEFaZ3F3N01NTEpTa2xzV3FtVkRHY2JVTmZhT2J1Yng1YTdHVWNmbA==',NULL,NULL,'2026-06-23 21:37:49',NULL,NULL,NULL,'SANCW9QEA',NULL,0,'2026-06-16 14:03:50'),(23,'Prajwal Deepak alhat','prajwalalhat7728@gmail.com','9518572476','$2y$12$XUIrAVtI6Tm/sN9v3dzgXOrmxwyPKOsQCkJfOVOML.pREtHYwh6.y','student',0,NULL,'2026-06-16 14:04:01','2026-06-16 14:05:49',0,'TWtLUkltaGxCcXhNWFNKVmFhYXFZY2paVXBpcUxkSjlXc0NSbXpRdw==',NULL,NULL,'2026-06-23 14:05:49',NULL,NULL,NULL,'PRAJL8LSX',NULL,0,'2026-06-16 14:04:21'),(24,'Kiran Sangle','dsangle659@gmail.com','7262826820','$2y$12$Nd2KyfQgJawYPEE2YkWZ6e0/2Mtoxjhdda0jgymhU.ryVhk0ebxC6','student',0,NULL,'2026-06-16 14:16:54','2026-06-16 14:18:49',0,'cGNwcHhuNE50RFIxRk1QaVlwaWpkb25CSjRFU1Yyak1qRlA2SEFRbQ==',NULL,NULL,'2026-06-23 14:18:49',NULL,NULL,NULL,'KIRAMAUEP',NULL,0,'2026-06-16 14:18:16'),(25,'Isha Dhoka','ishadhoka2000@gmail.com','8983825825','$2y$12$7KOOXM1vEfFrmGvHNSKeaO5jakGB1Ao58jEls/ufb5MnjF37q2GQq','student',0,NULL,'2026-06-16 14:51:05','2026-06-16 14:56:50',0,'cWxFeWNhVXp4N2VsQ2MzUDliU09jTW0yTW5VdkoyNkxtVk5FNjhZRQ==',NULL,NULL,'2026-06-23 14:56:50',NULL,NULL,NULL,'ISHA82O78',NULL,0,'2026-06-16 14:53:36'),(26,'C A & Co','kamal@caco.in','9824800548','$2y$12$qkJUwxzmSyNoE5NNQZS5ae6rYjTdERGdMJYYN9edUzlHdoy3z0WY.','firm',1,NULL,'2026-06-16 17:29:36','2026-06-17 09:43:02',0,'RlNpTXlBS1ZrMm9OS2FNT3hLRzdtUjAyam9TVVNMbzBqUmo3WE5xSw==','0de73580c96d9fba590ca7b09327e4bdfda272acde3566ced8485cd6735e67e5','2026-06-16 18:31:02','2026-06-24 09:43:02',NULL,NULL,NULL,'CACOVRYFB',NULL,0,'2026-06-16 17:30:21'),(27,'B N S T & Co LLP','bnst.ca@gmail.com','7888080300','$2y$12$CyOJDOg.HNBSCi.ZZkFqoupWXjAXV5gbKQ3Om9pFZJW7ReKun1HEG','firm',1,NULL,'2026-06-16 17:37:42','2026-06-18 13:54:36',0,'ZjFxY3hHbkgwd0k0V2tIbjJmVW94YU83QUJsTGNoTjRBNW1wcEdPSw==',NULL,NULL,'2026-06-25 13:54:36',NULL,NULL,NULL,'BNSTMXRAZ',NULL,0,'2026-06-16 17:38:04'),(28,'Rekhani & Saraogi','rekhani.saraogi@gmail.com','9327961291','$2y$12$WaNFStgFELv/.dyNJNCzkuA5t/PXpWM2RQEcGIACBtcVUWfaPJYJK','firm',0,NULL,'2026-06-16 18:10:31','2026-06-16 18:11:59',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'REKHI347L',NULL,0,'2026-06-16 18:11:59'),(29,'Sumit Sawant','sumitsawant2311@gmail.com','8390388932','$2y$12$gxF5S4wMgTme7Y7Tebxu8ONv0nE8x8oxL6DulLnLfo.r4GCHD2V3m','student',0,NULL,'2026-06-16 18:24:49','2026-06-16 18:24:49',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'SUMITCM04',NULL,0,NULL),(30,'Siddhesh Shinde','siddhesh99shinde@gmail.com','7030601585','$2y$12$h/sNWCAqm1ukCHhNyNicFOLI4xRtoyTNtFZ79.JJVUfZB.4b.yfOK','student',1,NULL,'2026-06-16 18:34:59','2026-06-16 18:38:59',0,'c1lDZ2duS0d6aXhmMEUxRURZWWgwenNzeXVjeWdpQjQzQklIZzdVcw==',NULL,NULL,'2026-06-23 18:35:41',NULL,NULL,NULL,'SIDDNMRM7',NULL,0,'2026-06-16 18:35:33'),(31,'darshan patni','darshanpatni09@gmail.com','8956066565','$2y$12$.P5X3hoB.LnIUkiWlV96yuP4CGKmAp1W0VtTplHJ2CU5wa5RSE0rC','student',1,NULL,'2026-06-16 18:52:31','2026-06-16 18:55:57',0,NULL,NULL,NULL,'2026-06-23 18:52:52',NULL,NULL,NULL,'DARSEY8EG',NULL,0,'2026-06-16 18:53:22'),(32,'Kusha Sharma','minakshikusha@gmail.com','7889774620','$2y$12$wAvquM9c.82DpvohGMriPeFGQ8DKws.gjWwczZe48Im3GbpRtFPhC','student',0,NULL,'2026-06-16 18:57:44','2026-06-16 18:58:09',0,'eDQ5SHJlVDNmaFhBS1ZraTFmcEc0WDR4R29TajhnUG9wQVk2d2h0Ng==',NULL,NULL,'2026-06-23 18:58:09',NULL,NULL,NULL,'KUSHBQ9DF',NULL,0,'2026-06-16 18:57:59'),(33,'Suraj Narsing Kakde','kakdesuraj383@gmail.com','9075581387','$2y$12$pqNZrlnWiJ2jM1Td6HVqQ.ff4f7IjN2abLizp0Og.HVdfAlBf2wp2','student',0,NULL,'2026-06-16 18:57:55','2026-06-16 19:00:05',0,'MTNNYndEY3E5RDRaVFRLYTR6eEhGSkFqUU5ycGw0QnAwQ3hoakdmSQ==',NULL,NULL,'2026-06-23 19:00:05',NULL,NULL,NULL,'SURAT6HV0',NULL,0,'2026-06-16 18:59:54'),(34,'Sanchita Nagwani','sanchitanagwani@gmail.com','9699110536','$2y$12$252.xQMN8g31H.EQk.9XiujJocVdtkj2Go.Nxa6LYHk0Qg7SbtDNq','student',1,'profile/1781617536_profile.jpg','2026-06-16 19:10:08','2026-06-16 19:15:36',0,'ckhmZXp5MHZGVjVkbkxmWFZ5SllGa2JaR2JKb2phMDF0dm1XaFlhUQ==',NULL,NULL,'2026-06-23 19:11:24',NULL,NULL,NULL,'SANCACCFY',NULL,0,'2026-06-16 19:11:15'),(35,'Gajanan Pawar','gajupawar775@gmail.com','9325315085','$2y$12$.8.yO94dn.lF0XiwBcLvquHtEKKsSmlINnD6JiNmmTJBo.jTrRez2','student',0,NULL,'2026-06-16 19:32:08','2026-06-16 19:33:08',0,'NVlPeHhINDNyVUJsVmVSUmMzVzExZWlBaDRlb2puRmpmeUxWcDZIQw==',NULL,NULL,'2026-06-23 19:33:08',NULL,NULL,NULL,'GAJA1ZYS5',NULL,0,'2026-06-16 19:33:00'),(37,'PRANAV MANTRI','mantripb77@gmail.com','9404664882','$2y$12$NNhk.1GaSwiWm4l.VG.Qg.AFfpmJQpYfiytlAQ3sEzcIpZbMigr7W','student',0,NULL,'2026-06-16 20:32:43','2026-06-16 20:34:36',0,'bkNVS2Z5b0Nkb1BLYk5KQThZMFU3ZDRaVkpSeHc5ZWllYVJXa21uOQ==',NULL,NULL,'2026-06-23 20:34:36',NULL,NULL,NULL,'PRANJVCPU',NULL,0,'2026-06-16 20:33:41'),(38,'Rohan Pawale','pawalerohan1999@gmail.com','9823279176','$2y$12$OmgIODfAOg2ArlbtE5lXj.3PdV8g7xuPZTVmNMkt6n8AY6ZZxCrOe','student',1,NULL,'2026-06-16 20:33:26','2026-06-16 20:38:43',0,'eVBaTXBVaER5aG1FdTFER3FmaUl0MWlaWnJIM3VaUlJzM3Q1OE9PVg==',NULL,NULL,'2026-06-23 20:34:06',NULL,NULL,NULL,'ROHAOY002',NULL,0,'2026-06-16 20:33:52'),(39,'Akash Pund','akashpund2003@gmail.com','8767436589','$2y$12$/lfEob77kH9gO/xKg87VnuH7c4Hl9fokNT7clk2sGr0YQdB2gI6zO','student',1,NULL,'2026-06-16 20:35:14','2026-06-21 00:39:33',0,'UEhMcXBNZFF3SldGSnd0eldMbG9XZHBoWjBFa2VDanZCN2FCdEIxdw==',NULL,NULL,'2026-06-28 00:39:33',NULL,NULL,NULL,'AKAS475AJ',NULL,0,'2026-06-16 20:35:42'),(40,'Pratish Bansode','pratishbansode07@gmail.com','9307170214','$2y$12$N7eQpo61vwk1zy08EvCNYOe8Pjr4h18MP2MQoC2m2Y.4XwHcmyeMe','student',0,NULL,'2026-06-16 20:40:45','2026-06-16 20:42:11',0,'Q2EzOUdLa05HNEM5VDhpS1dZUEdKTlowMm1jcXI1UGRQSDc5eVRtUw==',NULL,NULL,'2026-06-23 20:42:11',NULL,NULL,NULL,'PRAT5YIN2',NULL,0,'2026-06-16 20:41:54'),(41,'Suraj Sandeep Ingle','surajingleprofessional007@gmail.com','9370088634','$2y$12$XK0Q2/eodpl6fXLk3PpFd.VXA.ciH5QN5xXVcPVZMXRw7bs3ta1k6','student',0,NULL,'2026-06-16 20:49:36','2026-06-16 20:50:51',0,'WEZSQ2g1dDBYYnJsN2lRVTRzdUlCV1c2cmNVRXc0ZThmNWpJNGk0YQ==',NULL,NULL,'2026-06-23 20:50:51',NULL,NULL,NULL,'SURAJKYXW',NULL,0,'2026-06-16 20:50:43'),(42,'Atharv Patil','atharvpatil4328@gmail.com','9767461519','$2y$12$EplBNAx8q4ETHoN3AkpBueMRWxAsVaZJyLx4RQCte9RqaRJBzRMji','student',0,NULL,'2026-06-16 21:15:45','2026-06-16 21:18:36',0,'Q2ZGQkFJcFhYVjVQVkZ6TkZGaThCUUhGMFQ2QVQxRXpNVnY1aU1VOA==',NULL,NULL,'2026-06-23 21:18:36',NULL,NULL,NULL,'ATHAD4PSY',NULL,0,'2026-06-16 21:16:17'),(43,'Prajakta Shitole','prajaktashitole01@gmail.com','9890682415','$2y$12$2wR2o1PtxJ7Y6aHhPWeJ0.Emz.Mz0PkR3dtgANH5FQCsJACDC3E3G','student',0,NULL,'2026-06-16 23:26:16','2026-06-16 23:28:50',0,'MjU0NHpEQ1BadVpKRFB5bU1HZUNkeXFPNGFxQ0NoWGJtckFBeU1tOA==',NULL,NULL,'2026-06-23 23:28:41',NULL,NULL,NULL,'PRAJL07B2',NULL,0,'2026-06-16 23:28:50'),(44,'Kartik Rajesh jain','kartikajain29@gmail.com','9322504094','$2y$12$E6P5Mn1JTQbnDsBcREhvHObVa4fsCSvcUI3cqL44kuqqwoSehfy3O','student',0,NULL,'2026-06-17 07:14:41','2026-06-17 07:15:36',0,'SG1DSzV0VHBaTlhUTGM0ZXdLMjB5ZUJDU0YzYVVmMmdpMWRrMXJXZg==',NULL,NULL,'2026-06-24 07:15:36',NULL,NULL,NULL,'KARTBXY5U',NULL,0,'2026-06-17 07:15:29'),(45,'Sunil Shenoy and Associates','casunilshenoy@gmail.com','9890946372','$2y$12$z.8uXDFsrfqSPvyYbkPPJe5yiGGTDM8kZcQEGavEx53P6LrArpU6O','firm',0,NULL,'2026-06-17 08:09:04','2026-06-17 08:09:47',0,'NlNTRUU0R21rVklyVUQwOGpUdWNKdVppQ1ExQVBXSjZITGZ5dUZiQQ==',NULL,NULL,'2026-06-24 08:09:31',NULL,NULL,NULL,'SUNI00MJ9',NULL,0,'2026-06-17 08:09:47'),(46,'Anushka Shinde','anushka.shinde.1217@gmail.com','7028285800','$2y$12$7WH5NP5pY6oweoGw9xGNNOOjw8b.tNuA7pZRHbuymmmyvv3QnhWfm','student',1,NULL,'2026-06-17 09:11:25','2026-06-21 00:40:39',0,'anJnWVNKeW9mS3haOG5GRFBva09Mc1IwcERkanFIREFqM1ljYVp0Sg==',NULL,NULL,'2026-06-24 09:19:34',NULL,NULL,NULL,'ANUSJCHVB',NULL,0,'2026-06-17 09:12:01'),(47,'Garvita Bansal','garvitabansal7@gmail.com','7049734305','$2y$12$JrBT66q5HM2eRYI52DXDYudvl2.kEmsmqF2Do0bNxzlKZq4LOD1si','student',0,NULL,'2026-06-17 09:43:14','2026-06-17 09:44:38',0,'cjNvcDNsb1BscEZZcGpNNDh3VE9icHhiYnQwaEV1NllaWEJ2N3FYaA==',NULL,NULL,'2026-06-24 09:44:38',NULL,NULL,NULL,'GARVFOPRA',NULL,0,'2026-06-17 09:44:11'),(48,'Varun Mulay','varunmulay9@gmail.com','7709847063','$2y$12$oOlRDkZ4PjVqzzdlBFL8LOBVrQtPTQvQZV0BWZFBVmDJlpZH6iXzu','student',0,NULL,'2026-06-17 09:56:50','2026-06-17 09:57:59',0,'SkRoWTRpZzRkMU5ZQ042bjJBTXFwRjdxS2tXZzl3UzBMVFVSanRpbQ==',NULL,NULL,'2026-06-24 09:57:59',NULL,NULL,NULL,'VARURUET4',NULL,0,'2026-06-17 09:57:51'),(49,'Mohit Tinwar','mohittinwar1234@gmail.com','9011120940','$2y$12$rB8QgCC3zkeWNNkN7cs/cevQS04pFol6s90qXRRtmH8M4RTjiLs5u','student',1,NULL,'2026-06-17 10:03:16','2026-06-17 10:06:16',0,'NUxUUzN0TTFTTW1PbmxLNmpRVnNQcXVYZnd6VGpwaVQzOXBZQ0RKNA==',NULL,NULL,'2026-06-24 10:04:00',NULL,NULL,NULL,'MOHIZ1DJB',NULL,0,'2026-06-17 10:03:53'),(50,'Akansha Malhotra','akanshaagrawal0918@gmail.com','9109994200','$2y$12$z8D24LRONg75RG5d764SOeVTaSUOv1YXKgtCwTJsfuX38KUV7z9yG','student',0,NULL,'2026-06-17 11:05:42','2026-06-22 23:12:06',0,'a1U2SFlaMUlPUXNOQ1Q1SmlteDVFbWN1MFJReTRpSTZvclA3clFoNQ==',NULL,NULL,'2026-06-29 23:12:06',NULL,NULL,NULL,'AKAN8PTOA',NULL,0,'2026-06-17 11:07:03'),(52,'Gautam kasar','kasargautam19@gmail.com','7666776067','$2y$12$Ho0udqpDqiVJ0ilJPtM/ruZa3bsPvSv3dD4A29gK2r9DIYXzdGM3a','student',1,NULL,'2026-06-17 11:23:07','2026-06-17 11:57:41',0,'bjlCOEc2TFppSFpqWWxybG5GWUZjVGNkYlNaNU9mTnZqVmc2NjV4QQ==',NULL,NULL,'2026-06-24 11:57:41',NULL,NULL,NULL,'GAUTKF6IL',NULL,0,'2026-06-17 11:23:49'),(53,'Piyush Agrawal','piyushagrawal4833@gmail.com','8766568212','$2y$12$dTGpNxlsvvfIYd/l.fPrP.XjQpxt4zIEhzVzdq2cgn9Za4xUqU5g.','student',0,NULL,'2026-06-17 11:28:00','2026-06-17 11:29:53',0,'VkFlamFBZ05VdmZNRnZaT0dRWjlUVkJycWtFSHl1SzVxa3JHV21obA==',NULL,NULL,'2026-06-24 11:29:53',NULL,NULL,NULL,'PIYUCMWHT',NULL,0,'2026-06-17 11:28:27'),(54,'Vipin Gujarathi & Co','vipingujarathico@gmail.com','9665041457','$2y$12$RJKj/PDDIznInxmZYoPbJuFo83RRn5JEe7RjPFYGIpd8aC4cVlto.','firm',1,'firm/logo/1781683273_logo.jpeg','2026-06-17 12:16:19','2026-06-17 13:31:13',0,NULL,NULL,NULL,'2026-06-24 13:01:00',NULL,NULL,NULL,'VIPIY6E97',NULL,0,'2026-06-17 13:00:34'),(55,'Prachay Grouo','hr@prachay.com','9028666187','$2y$12$VitB1.pJR1c2a27XygxfH.ARTzGS6aXWM5jscFl0Tf0/I/rc3VHfm','firm',0,NULL,'2026-06-17 12:34:58','2026-06-17 12:36:11',0,'THRabnhEd21helFHbWYzdVF4U1NoUGFlQ0F3Q3lmbmMzZllzRmxjTg==',NULL,NULL,'2026-06-24 12:36:11',NULL,NULL,NULL,'PRACRG3Z7',NULL,0,'2026-06-17 12:35:43'),(56,'A R Totala & Co.','amolrtotla@gmail.com','7447711021','$2y$12$5kNkTCHvy921AZUwE0dMA.HQPzPmO7a1TiskMLxr2Z5.jlDLFIj.y','firm',1,NULL,'2026-06-17 13:28:17','2026-06-17 13:36:12',0,NULL,NULL,NULL,'2026-06-24 13:28:58',NULL,NULL,NULL,'ARTOIHEWG',NULL,0,'2026-06-17 13:28:32'),(57,'Tisha','tishajain0906@gmail.com','7558620917','$2y$12$y8eZJd2wyE0akXmH73NK9uIVYruRNSUq0bNmgdTw/yOof.VgXIuu2','student',1,NULL,'2026-06-17 19:02:39','2026-06-17 19:03:47',0,'UmxpQjVyTjlidUVoRm5ScEVCdkpvWDlHbXhGaFAzN3hLZ3A2ZGNtTg==',NULL,NULL,'2026-06-24 19:03:01',NULL,NULL,NULL,'TISHNDCEH',NULL,0,'2026-06-17 19:03:13'),(58,'Megha anil kukreja','kukrejamekii2703@gmail.com','7058373947','$2y$12$tMrBgCRMOKULMvcVHQXeGegmH6fnw2.E06lw//j/LVdw8rM99hlmu','student',0,NULL,'2026-06-17 20:03:58','2026-06-17 20:04:38',0,'Z1Mzb1V6ZE5QUHN4QmgxeEhvdTlJUzFFR2hBNWFpNlRSbTBkUUQ0SA==',NULL,NULL,'2026-06-24 20:04:16',NULL,NULL,NULL,'MEGHVPT0K',NULL,0,'2026-06-17 20:04:38'),(59,'Radhesham Biyani','carsbiyani@gmail.com','8888654567','$2y$12$k6dMPSYmSqmGOUB3f1cktO01D/CcLAQ2gqe.jwGaJLuq9g6VYRN1m','student',0,NULL,'2026-06-17 20:24:25','2026-06-17 20:26:04',0,'Ukk1OWI4bHlmeDdnVHBjTTJQSlJBTDYxZDdSQXY5dm43RHlaTXFiZg==',NULL,NULL,'2026-06-24 20:26:04',NULL,NULL,NULL,'RADHDD9XV',NULL,0,'2026-06-17 20:24:57'),(60,'R S Biyani & Co','rsbitsolution@gmail.com','7020009889','$2y$12$Hq5N8ODerG9cTbP6pWqBn.wotmOrL00qGEp4hZyupJGzzTydEa5Tm','firm',1,NULL,'2026-06-17 20:28:14','2026-06-17 20:34:08',0,'eGZwUDcxNG1tYjhrUW0wb1BiNEl2Y29XQ1ZjdVRHOEJUQWZPV2FHbQ==',NULL,NULL,'2026-06-24 20:28:53',NULL,NULL,NULL,'RSBIEI4P7',NULL,0,'2026-06-17 20:28:27'),(61,'Nitish Badak','nitishbadak14@gmail.com','9511864107','$2y$12$Cx1RgBAIoSfxQCwRQMSUtuZaeaICWb914MfblxLy4i5C0fM.n5Gf6','student',0,NULL,'2026-06-17 22:36:31','2026-06-17 22:36:31',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'NITIJTXDZ',NULL,0,NULL),(62,'N J LOHE & CO.','cacspriya@tnlac.in','9028485515','$2y$12$x4KOIl8IMtVoWS3U94CYVueKSq09JjEzj2YuTSDUZMsEWY3TRKr5i','firm',1,NULL,'2026-06-17 22:57:44','2026-06-24 12:07:19',0,'NmozWnpsZVB3UTh4eXd2SmF5NnVFTHBlVlU5VG1VaUV4a2laNXhRdg==',NULL,NULL,'2026-07-01 12:00:50',NULL,NULL,NULL,'NJLOBNR93',NULL,0,'2026-06-17 22:58:17'),(63,'Atharv','kakaniatharv06@gmail.com','7028312405','$2y$12$Z95kBMHRRggpeoUsAMNWdO3BHet5AjhYEbHuxYLFXLKTpFz6hEar6','student',1,NULL,'2026-06-17 23:18:46','2026-06-17 23:22:55',0,'NlFwY1lNQm05dFQ0U2d3YzFJWFh6amJLcUhIZ2F1cGUzRVZzZGFMeA==',NULL,NULL,'2026-06-24 23:18:57',NULL,NULL,NULL,'ATHAOIWSA',NULL,0,'2026-06-17 23:19:50'),(64,'Anjali Bhutada','anjumantri@gmail.com','9922222335','$2y$12$NjFpkMBokf/MEzaYh3Qmx.gZPGsJkV0XR3TR4mo5eRMOdJWux0cna','student',1,NULL,'2026-06-17 23:26:20','2026-06-20 19:57:38',0,'Q2NZeGxGa2x0VkN6ZFpOOFBqZm5iUlVIeVM5RGY0VXYxOUFBakFGbg==',NULL,NULL,'2026-06-27 19:57:38',NULL,NULL,NULL,'ANJAJU66Z',NULL,0,'2026-06-17 23:27:02'),(67,'Priyani Saklecha','priyanisaklecha9@gmail.com','9404086715','$2y$12$UMH0Gbra3B/ShWTeREJqI.OceOe9JFUhxrvbRzdB00q1i7EfmNiXu','student',1,NULL,'2026-06-18 13:07:38','2026-06-24 14:30:31',0,'MTRicGtSNWNKMlVQSTducEROMDJ3MUgzRVRBNnlPV1laQ1pFRWZNcw==',NULL,NULL,'2026-07-01 14:30:31',NULL,NULL,NULL,'PRIY5E5ZV',NULL,0,'2026-06-18 13:08:40'),(68,'Mohit Patil','mohitbalrampatil@gmail.com','9096162704','$2y$12$LGN60brRiQXQ7U0NpzcKteIb5WNgauG/pHQvmtO1ULomQj.ardp0y','student',0,NULL,'2026-06-18 15:02:36','2026-06-18 15:02:36',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'MOHIUEG88',NULL,0,NULL),(69,'Sharwari Adep','adepsharwari8@gmail.com','9657055283','$2y$12$CuOCgbt/LFjBHdtG9ZhPb.MSA0SQIFkeFFgKj4qKwZPukhDzbTRh6','student',0,NULL,'2026-06-18 15:03:55','2026-06-18 15:05:16',0,'RzFVQUFOaHhBb3hoZTFyclluRnJUNjdkZUd5M0xHdGVaZWJXaXc0dg==',NULL,NULL,'2026-06-25 15:05:16',NULL,NULL,NULL,'SHARE8E07',NULL,0,'2026-06-18 15:05:07'),(71,'CA Kiran Bafna','cakiranbafna@gmail.com','8329714526','$2y$12$n17q3qoXQkxV5AVVc8RmmebSrHI5j5Vng573LKV0WUd6CtVR9xdQm','student',0,NULL,'2026-06-18 17:59:18','2026-06-18 18:24:35',0,'M3U2dzlCVFhDV29kVnhVUzN3MnNjMng0UDRPaXNiZDJ5RXBLT09CVQ==',NULL,NULL,'2026-06-25 18:24:35',NULL,NULL,NULL,'CAKIEKEII',NULL,0,'2026-06-18 18:02:03'),(72,'Hemanshu K','hemanshukopulwar21@gmail.com','9370538734','$2y$12$/T2QBS8E7FyXQ2pJ/wtHQOLTHHsu7xiKuwG93Txpwe5KbA9ktcrlO','student',1,NULL,'2026-06-18 18:40:22','2026-06-18 19:56:09',0,'ODRTZ1JVeW1NYkRVN2h0ektjZGxabFk2SEZ5YkpDZW5zZVp6YWEwOQ==',NULL,NULL,'2026-06-25 18:41:05',NULL,NULL,NULL,'HEMA50JIL',NULL,0,'2026-06-18 18:40:56'),(73,'RAJESH VARMA & ASSOCIATES','raj.varma2004@gmail.com','9920048834','$2y$12$cSSldJ2sX74OhgD3lVNGkeNAN6HXAUzHtcv2Ya0.HvjIW4P06u8pm','firm',0,NULL,'2026-06-18 19:33:35','2026-06-18 20:10:30',0,'Z1FQWXBCc0tPa1IySzVXM2JwV1NtZUpHUnl3U2lJSU5NWlBrVHpwcg==',NULL,NULL,'2026-06-25 20:10:30',NULL,NULL,NULL,'RAJED7Z9R',NULL,0,'2026-06-18 19:35:11'),(74,'K A R M & CO. Chartered Accountants','cakarmandco@gmail.com','9920449339','$2y$12$rPPztdFH6caCsis6mzq1V.0klgkfUlcRsDkmGYoXnECS02kMYci9y','firm',1,NULL,'2026-06-18 20:28:41','2026-06-19 14:17:44',0,'bVd6cFd3cTZJV1pGVU10MHhxMU1abFdRQW9iWTNSNkRvdG5ITDJDWA==',NULL,NULL,'2026-06-26 14:17:44',NULL,NULL,NULL,'KARMABSI4',NULL,0,'2026-06-18 20:29:11'),(75,'RCO','hr@rajendraco.com','9024559987','$2y$12$cnIfp8GeIjxysAEuTZHDGeCrQXBxE8ORI/aHiBr7.3lEnoH7X.eYK','firm',0,NULL,'2026-06-19 14:41:58','2026-06-19 14:41:58',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'RCO8S49W',NULL,0,NULL),(76,'MANMATH TOPAJI','patil431717@gmail.com','9923360595','$2y$12$U6J.FL4hhjWLzwvROY82WOG8Vu0M0ulX5lBQLN6PLziQZgJdelvt6','student',0,NULL,'2026-06-19 17:52:54','2026-06-19 18:04:53',0,'VDVwQjM2dHBGcHRhQjBxQ2g2R2YyMGtRcWRSVVc4bmY0alRIcW54NQ==',NULL,NULL,'2026-06-26 18:04:33',NULL,NULL,NULL,'MANMISPE9',NULL,0,'2026-06-19 17:53:29'),(77,'Komal Mopkar','komal.mopkar@gmail.com','9405902180','$2y$12$CW24dtWd3iZZCJZeTODQ4OXHliRDsNKL0w7NT6D3gNoo4Mwt5ViIW','student',1,NULL,'2026-06-20 10:37:12','2026-06-20 10:40:03',0,NULL,NULL,NULL,'2026-06-27 10:37:33',NULL,NULL,NULL,'KOMAJYMMY',NULL,0,'2026-06-20 10:38:07'),(78,'Rahul Chandak','rahulc1403@gmail.com','8421440120','$2y$12$5mkYQ51xTo6rPiYsW50Ar.ETU.ztHLzJHSx3zDXDPYfUdzB6mf4km','student',0,NULL,'2026-06-20 10:59:22','2026-06-20 11:00:05',0,'U1dRMUFmWXlOWThCdFlWSTlHN0JjYU56RVNxZ2tlV2hMMmNhNHE1cQ==',NULL,NULL,'2026-06-27 11:00:05',NULL,NULL,NULL,'RAHUF6F3Z',NULL,0,'2026-06-20 10:59:49'),(79,'SMW AND Co.','ca.sunil.wadhwani@gmail.com','7709912344','$2y$12$Dq4xWKdfgww1CC2EYXXVk.appKT6/sXuc/nKVqUQyWEMCyt8CesEy','firm',1,NULL,'2026-06-20 11:36:59','2026-06-21 14:53:45',0,NULL,NULL,NULL,'2026-06-28 14:53:45',NULL,NULL,NULL,'SMWAAW4VO',NULL,0,'2026-06-20 11:37:27'),(81,'Mrunmayi Mulay','mrunmayimulay124421@gmail.com','7385625714','$2y$12$dcnA2WhzduYkXpiaZlQz9uEeDAgTyDcUWxyCN3fK4rjqEV0bwaRK6','student',0,NULL,'2026-06-20 14:58:50','2026-06-20 15:01:01',0,'Qjh4ckV4dDJtbHdxMkVOZHlRTmRJVVNUZjBSY1dwYXNEbzhRU0JHTA==',NULL,NULL,'2026-06-27 15:01:01',NULL,NULL,NULL,'MRUNJCJHK',NULL,0,'2026-06-20 15:00:49'),(82,'SKPN & Associates LLP','hr@skpn.in','8788662179','$2y$12$pxjLGtq3A4mNlCdvmAUS2e4ZmQovMIXlfeWs0Fqmn2Xxao7M87I86','firm',0,NULL,'2026-06-20 18:10:57','2026-06-20 18:11:31',0,NULL,NULL,NULL,'2026-06-27 18:11:31',NULL,NULL,NULL,'SKPNCS6QP',NULL,0,NULL),(83,'Kunal shinde','mr.shindekunal99@gmail.com','9209371168','$2y$12$Bhd4Bc6aUkAvyWjUML44FuVBCoO9HOZTUDHJ9FKsTW0.8nYKAe0I2','student',1,NULL,'2026-06-21 14:49:51','2026-06-24 09:09:44',0,NULL,NULL,NULL,'2026-07-01 09:09:44',NULL,NULL,NULL,'KUNAVEMWB',NULL,0,'2026-06-21 14:50:24'),(84,'Vaishnavi Hirodkar','hirodkarv@gmail.com','9423880048','$2y$12$Ie/v1/mmZR/a7Jnxhhv06Op5Qpzx54b5qt6.26Pc.d9QmClFOBjNu','student',1,NULL,'2026-06-21 15:44:40','2026-06-21 15:51:25',0,'cHpRZTFjUFRuek5ua1Nqc1pTaFR0MmFZNGRaZE16UHlYdHYwOXNTVw==',NULL,NULL,'2026-06-28 15:45:05',NULL,NULL,NULL,'VAISNGHDK',NULL,1,'2026-06-21 15:49:49'),(85,'B R M A S S & ASSOCIATES','brmass.ca@gmail.com','8856022331','$2y$12$Zf11BthoSNOk90szCfnNcuwu0rFT2UzRtRbxUYlNC1gVj2pXT3ujG','firm',0,NULL,'2026-06-21 16:02:43','2026-06-21 16:03:04',0,'b0FPV3V3aHlLdGFkQnFMRkRSV0Zxc0xyREhIWmlxWXZQcjYxbVNBRg==',NULL,NULL,'2026-06-28 16:03:04',NULL,NULL,NULL,'BRMAH7VY9',84,0,NULL),(86,'Kothari Chandel & Co.','sonal789987456@gmail.com','7879053989','$2y$12$CykcacWCVb8Eu6FM4jtoUuioEXXxho2QrXvdFB6vfYRpK8d2hf5hG','firm',0,NULL,'2026-06-21 20:09:03','2026-06-22 14:34:16',0,'NVYwNlVwQmhXcVA5YTJ4QTdOZmw4RGpuNW8zNjgzUUpHRm5sWGdjeQ==',NULL,NULL,'2026-06-29 14:34:16',NULL,NULL,NULL,'KOTHDUA1J',NULL,0,'2026-06-22 14:33:21'),(88,'V S A P & Company','hr@vsap.co.in','9322120781','$2y$12$9I8EXG1uQAL3XBeNW9CX.eH2XQoqUtHm3MN0/F/QHxJ.YoVe88Pu.','firm',1,'firm/logo/1782119234_logo.png','2026-06-22 12:24:32','2026-06-23 09:47:15',0,'T29aWk5BcUR4RWZ4cHdGWnlKeUp5SmFvTHlCU2N5eDdjTndTVG11Rw==',NULL,NULL,'2026-06-30 09:47:15',NULL,NULL,NULL,'VSAP5HWMB',NULL,0,'2026-06-22 12:25:37'),(89,'Rohit Mantri','rohit01mantri@gmail.com','9422281831','$2y$12$tBFu3RQl6dVDlm8YYkEYj.s28SWQQswKLXIVyHvq42.O2tbJrA0Te','student',0,NULL,'2026-06-22 20:28:39','2026-06-22 20:28:54',0,'cGZCR1ZTQ3hITEdDR25WNlNrdGRjeFdiaE5raUlSZkRNVFdMbEtERQ==',NULL,NULL,'2026-06-29 20:28:54',NULL,NULL,NULL,'ROHIK9J8L',NULL,0,NULL),(90,'Jigmi Nishitbhai Chunawala','jigs180902@gmail.com','6353179356','$2y$12$La9ecwvG0ar.bOTCjiaSWeiExHNVqPlG0RfCkcTu9wPi1Zz.2RBiW','student',0,NULL,'2026-06-23 01:13:01','2026-06-23 01:15:01',0,'QjZiempNNzUxbDZDdVpmZjZld3QxTnVBRjdQT0hMWk40TjlFSkcweQ==',NULL,NULL,'2026-06-30 01:15:01',NULL,NULL,NULL,'JIGMDMJP5',NULL,0,'2026-06-23 01:14:14'),(91,'H Mistry & Associates','harshal@cahmistry.com','9921240004','$2y$12$rNwYPMe68ZUbm6RDFywjGerOQ50yuYVBILVLPlw98YdfmDI4YJggy','firm',1,NULL,'2026-06-23 19:07:33','2026-06-24 09:56:46',0,'RmVoVjdmWUprZjhoQXl0b2JUSFRTdWlYeHVUZUh6RVNMZFlNS0x2cA==',NULL,NULL,'2026-07-01 09:56:46',NULL,NULL,NULL,'HMISMS6PP',NULL,0,'2026-06-23 19:07:53'),(92,'Ketki Dagha And Associates','ketkidaghaandassociates@gmail.com','9892948848','$2y$12$hqe4fuBTPsGoW2CU4dWRRuz2ELXBKuAK7GdgjIMfkFpiVoYDHQ/rq','firm',1,'firm/logo/1782283065_logo.jpeg','2026-06-23 20:57:45','2026-06-24 13:09:44',0,'Zk1FVmlzUzJ2Y1I2aEdHUnE2Z3FwUkFIREEyQW5FaHN1OUc1RWN2cA==',NULL,NULL,'2026-07-01 13:09:44',NULL,NULL,NULL,'KETKN7HV2',NULL,0,'2026-06-23 20:58:28'),(93,'Mukul mittal','Mittalmukul80@gmail.com','8000824336','$2y$12$mZU3nPAss8Ee6QkLcKXAEuRI38Khr7LxjR2McPOBzVZuM1xrCuMMi','student',0,NULL,'2026-06-23 23:49:11','2026-06-23 23:49:56',0,'UE1Sc0J1NDYyaHl5S3ZQaEt6U21zREhzb2lURnUyMVBPcnBwcXV5Yg==',NULL,NULL,'2026-06-30 23:49:56',NULL,NULL,NULL,'MUKUR80F4',NULL,0,'2026-06-23 23:49:50'),(94,'Kunal shinde','rajputmangla4@gmail.com','7666829942','$2y$12$JzqrXq1mJFLTgXQitzkSUexfT61vQAg6zCiO857m9gqTXlZc5moTG','student',0,NULL,'2026-06-24 09:15:52','2026-06-24 09:17:34',0,'aXVMTFI2dEVFa2MwTENQM1hXeTlnZHRMakl3MWU1V00xYkY1OVpueA==',NULL,NULL,'2026-07-01 09:15:58',NULL,NULL,NULL,'KUNAE4O4H',NULL,0,'2026-06-24 09:17:34'),(95,'M R O & Associates','admin@mrogroup.in','8275451063','$2y$12$ltzofvFshzsAt8NG52o8p.Oz0sB/c2dOCmS.TfnJ3GBzKCzZWDVdC','firm',0,NULL,'2026-06-24 10:05:59','2026-06-24 10:06:19',0,'Q2ZKTGNsbWtQcjRUaVdOZDlCOGNkdFBZMElhRVM2NDRScHp4eFVjbQ==',NULL,NULL,'2026-07-01 10:06:19',NULL,NULL,NULL,'MROAVLAQV',NULL,0,NULL),(96,'MUTTHA AND LAHOTI','mutthalahoti@gmail.com','9422003371','$2y$12$KDhyUh9clyBGEB7V752Br.4dAcAlczOgPk0IJYVP6PCPcxbc.X7nG','firm',1,'firm/logo/1782277089_logo.png','2026-06-24 10:19:45','2026-06-24 10:28:09',0,'bDEyMmh2c3A0SnhRU3hkUFdJZXRzdnphVzJwOEtpNzdsSGpLVldvOQ==',NULL,NULL,'2026-07-01 10:20:15',NULL,NULL,NULL,'MUTTUK90P',NULL,0,'2026-06-24 10:20:04'),(97,'ARTH & Associates','hr@arth.net.in','8390448000','$2y$12$yS2vFDRn3CIEZ40o/ly5q.ir4yCShEAjsr49hAtbt4UvRiqlws.cq','firm',1,NULL,'2026-06-24 11:13:55','2026-06-24 12:46:36',0,'cXVrdDFXWFNOMU03RnNLWFhlT1cwejhwSUI1Y0lKU3lxU3pxQ1Nlbg==',NULL,NULL,'2026-07-01 12:46:36',NULL,NULL,NULL,'ARTHM5G5G',NULL,0,'2026-06-24 11:15:10'),(98,'Kevin Mendonca','kevinmendonca4@gmail.com','9987426421','$2y$12$Ff9/afBth/AQtfPmBbMTUuy/GGy6lUecCXxg.rbqakRdLa.F2X.Nq','student',1,'profile/1782285208_profile.JPG','2026-06-24 12:35:15','2026-06-24 12:46:03',0,'cWhQRE5UMlZWNmhqa1ZXaFFBbW5tUTBhMk55eFYzUGplQWlXRnFkTg==',NULL,NULL,'2026-07-01 12:46:03',NULL,NULL,NULL,'KEVIQ5DAT',NULL,0,'2026-06-24 12:35:39');
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

-- Dump completed on 2026-06-24  9:12:48

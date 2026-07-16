-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: komagin_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activity_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=193 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,'admin_1','admin','login','admin','::1','curl/8.19.0','2026-05-06 07:43:42'),(2,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-06 07:45:51'),(3,'admin_1','admin','update_settings','[\"store_name\",\"store_email\",\"store_phone\",\"store_address\",\"facebook\",\"linkedin\",\"twitter\",\"instagram\",\"footer_tagline\"]','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-06 07:46:54'),(4,'admin_1','admin','update_settings','[\"default_currency\",\"partner_portal\",\"hr_admin_email\",\"hero_background_image\",\"cta_background_image\",\"hero_badge_text\",\"hero_title_line_1\",\"hero_title_line_2\",\"hero_title_line_3\",\"hero_description\",\"hero_primary_label\",\"hero_primary_target\",\"hero_secondary_label\",\"hero_secondary_target\",\"mission_title\",\"mission_text\",\"vision_title\",\"vision_text\",\"about_page_title\",\"about_page_subtitle\",\"about_story_label\",\"about_story_title\",\"about_story_content\"]','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-06 07:46:55'),(5,NULL,'system','submit_contact','contact_1778024202_69fa7f0ae4d85','::1','curl/8.19.0','2026-05-06 09:36:42'),(6,NULL,'system','submit_contact','contact_1778026350_69fa876e99f65','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-06 10:12:30'),(7,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-06 10:13:14'),(8,'admin_1','admin','update_contact_status','contact_1778024202_69fa7f0ae4d85','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-06 10:13:36'),(9,'admin_1','admin','update_contact_status','contact_1778026350_69fa876e99f65','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-06 10:13:46'),(10,'admin_1','admin','login','admin','::1','curl/8.19.0','2026-05-06 10:25:30'),(11,'admin_1','admin','login','admin','::1','curl/8.19.0','2026-05-06 10:25:30'),(12,'admin_1','admin','login','admin','::1','curl/8.19.0','2026-05-06 10:28:38'),(13,'admin_1','admin','login','admin','::1','curl/8.19.0','2026-05-06 10:28:59'),(14,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-06 15:56:17'),(15,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-06 15:56:50'),(16,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-08 14:08:32'),(17,'admin_1','admin','hire_items_save','hire_69fd62ac66e1c','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-08 14:12:28'),(18,'admin_1','admin','update_settings','[\"hire_page_badge\",\"hire_page_title\",\"hire_page_subtitle\",\"hire_page_intro\",\"hire_page_contact_phone\",\"hire_page_contact_email\",\"hire_page_cta_label\",\"hire_page_cta_target\",\"hire_page_background_image\"]','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-08 14:13:31'),(19,'admin_1','admin','hire_items_save','hire_69fd62ac66e1c','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-08 15:03:10'),(20,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-08 19:29:16'),(21,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-09 17:08:40'),(22,'admin_1','admin','delete_document_record','registration','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-09 17:11:40'),(23,'admin_1','admin','documents_save','doc_69fedf4c34407','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-09 17:16:28'),(24,'admin_1','admin','save_document_record','business_registration_certificate','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-09 17:16:28'),(25,'admin_1','admin','upload_document','registration','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-09 17:16:52'),(26,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-09 21:10:54'),(27,'admin_1','admin','hire_items_save','hire_69ff16f0d28e9','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-09 21:13:52'),(28,'admin_1','admin','create_project','proj_1778325555_69ff183364c13','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-09 21:19:15'),(29,'admin_1','admin','create_service','svc_1778325808_69ff19306f0bc','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-09 21:23:28'),(30,'admin_1','admin','update_project','proj_1778325555_69ff183364c13','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-10 00:28:01'),(31,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36','2026-05-11 09:39:32'),(32,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-14 21:56:45'),(33,'admin_1','admin','files_upload','WhatsApp Image 2026-05-14 at 10.27.44 AM.jpeg','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-14 22:15:11'),(34,'admin_1','admin','files_delete','file_6a05bccef2385','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-14 22:18:55'),(35,'admin_1','admin','files_upload','OOP Tutorial 6 SemI 2026 Abstraction and Interface.pdf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-14 22:19:18'),(36,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-15 09:18:32'),(37,'admin_1','admin','files_upload','Lecture 11 Collections Framework Final.pdf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-15 09:20:06'),(38,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-15 11:01:34'),(39,'admin_1','admin','login','admin','::1','curl/8.19.0','2026-05-15 11:41:16'),(40,'admin_1','admin','hire_items_save','hire_6a067a12851eb','::1','curl/8.19.0','2026-05-15 11:42:42'),(41,'admin_1','admin','create_service','svc_1778809362_6a067a12a9c84','::1','curl/8.19.0','2026-05-15 11:42:42'),(42,'admin_1','admin','update_contact_status','contact_1778026350_69fa876e99f65','::1','curl/8.19.0','2026-05-15 11:42:43'),(43,'admin_1','admin','hire_items_save','hire_6a067a12851eb','::1','curl/8.19.0','2026-05-15 11:42:43'),(44,'admin_1','admin','update_service','svc_1778809362_6a067a12a9c84','::1','curl/8.19.0','2026-05-15 11:42:43'),(45,'admin_1','admin','hire_item_delete','hire_6a067a12851eb','::1','curl/8.19.0','2026-05-15 11:42:43'),(46,'admin_1','admin','delete_service','svc_1778809362_6a067a12a9c84','::1','curl/8.19.0','2026-05-15 11:42:43'),(47,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-15 15:49:36'),(48,'admin_1','admin','delete_contact','contact_1778026350_69fa876e99f65','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-15 16:29:32'),(49,'admin_1','admin','delete_contact','contact_1778024202_69fa7f0ae4d85','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-15 16:29:37'),(50,'admin_1','admin','files_toggle_template','file_6a0658a6a4e91','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-15 16:29:50'),(51,'admin_1','admin','files_create_category','Hero','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-15 16:42:12'),(52,'admin_1','admin','files_upload','OOP Tutorial 6 SemI 2026 Abstraction and Interface.pdf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-15 16:43:36'),(53,'admin_1','admin','files_delete','file_6a06c098cc7d9','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-15 16:53:37'),(54,'admin_1','admin','files_delete','file_6a0658a6a4e91','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-15 16:53:41'),(55,'admin_1','admin','files_delete_category','cat_custom_6a06c0442494d','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-15 16:53:46'),(56,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-17 02:19:05'),(57,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-18 11:14:56'),(58,'admin_1','admin','login','admin','::1','curl/8.19.0','2026-05-18 15:45:46'),(59,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Codex/26.513.40821 Chrome/148.0.7778.97 Electron/42.0.1 Safari/537.36','2026-05-18 15:47:40'),(60,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-18 19:03:47'),(61,'admin_1','admin','submit_contact','contact_1779103198_6a0af5dee6897','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-18 21:19:58'),(62,'admin_1','admin','files_upload','WhatsApp Image 2026-05-14 at 10.29.28 AM.jpeg','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-18 21:21:39'),(63,'admin_1','admin','files_toggle_template','file_6a0af643b28ca','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-18 21:21:54'),(64,'admin_1','admin','files_delete','file_6a0af643b28ca','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-18 21:22:08'),(65,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-18 22:24:30'),(66,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8457','2026-05-18 23:05:05'),(67,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8457','2026-05-18 23:06:32'),(68,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8457','2026-05-18 23:07:07'),(69,NULL,'unknown','job_application_received','app_1779109628_c50941c4','::1','curl/8.19.0','2026-05-18 23:07:10'),(70,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8457','2026-05-18 23:08:28'),(71,NULL,'unknown','job_application_received','app_1779109809_d4286217','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-18 23:10:11'),(72,'admin_1','admin','job_application_status','app_1779109809_d4286217 shortlisted','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-18 23:16:45'),(73,'admin_1','admin','partners_save','partner_6a0b17aa2d02f','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-18 23:44:10'),(74,'admin_1','admin','partners_save','partner_6a0b17ac75605','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-18 23:44:12'),(75,'admin_1','admin','partners_save','partner_6a0b17ae9a758','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-18 23:44:14'),(76,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8457','2026-05-19 01:10:01'),(77,NULL,'unknown','job_application_received','app_1779141687_92fef640','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-19 08:01:29'),(78,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-19 11:58:16'),(79,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-20 18:18:17'),(80,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-20 18:42:10'),(81,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-20 22:33:35'),(82,NULL,'system','partners_save','partner_6a0b17ae9a758','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8457','2026-05-20 23:10:06'),(83,'admin_1','admin','partners_update_status','partner_6a0b17ae9a758 rejected','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-20 23:12:01'),(84,'admin_1','admin','partner_showcase_delete','ps_dace173d6a9b','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-20 23:21:38'),(85,'admin_1','admin','partner_showcase_delete','ps_b93ef653014c','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-20 23:21:42'),(86,'admin_1','admin','partner_showcase_delete','ps_ad48822b8e5e','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-20 23:29:24'),(87,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0','2026-05-20 23:31:13'),(88,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 01:58:03'),(89,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8457','2026-05-21 08:59:06'),(90,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 09:01:18'),(91,'admin_1','admin','update_team','mock_team_rachel_ipa','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 09:49:06'),(92,'admin_1','admin','upload_document','labour','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 10:02:04'),(93,'admin_1','admin','documents_save','doc_registration','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 10:04:23'),(94,'admin_1','admin','save_document_record','registration','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 10:04:23'),(95,'admin_1','admin','files_toggle_template','file_6a05bdc604c08','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 10:22:10'),(96,'admin_1','admin','update_contact_status','contact_1779103198_6a0af5dee6897','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 10:31:48'),(97,'admin_1','admin','blog_update','Site Safety Month Focuses on Prevention, Reporting, and Daily Discipline','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 11:16:00'),(98,'admin_1','admin','login','admin','::1','node','2026-05-21 11:20:00'),(99,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8457','2026-05-21 11:20:13'),(100,'admin_1','admin','login','admin','::1','curl/8.19.0','2026-05-21 11:21:06'),(101,'admin_1','admin','blog_update','Project Kickoff: Mobilising for Gerehu Roadside Drainage Upgrades','::1','curl/8.19.0','2026-05-21 11:21:15'),(102,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 13:57:54'),(103,'admin_1','admin','login','admin','::1','curl/8.19.0','2026-05-21 14:26:11'),(104,'admin_1','admin','login','admin','::1','curl/8.19.0','2026-05-21 14:34:31'),(105,'admin_1','admin','login','admin','::1','curl/8.19.0','2026-05-21 14:52:18'),(106,'admin_1','admin','job_application_status','app_1779141687_92fef640 hired','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 15:14:50'),(107,'admin_1','admin','job_application_status','app_1779109809_d4286217 interview','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 15:15:10'),(108,NULL,'unknown','job_application_received','app_1779342101_7e29fa4d','::1','curl/8.19.0','2026-05-21 15:41:43'),(109,'admin_1','admin','job_application_status','app_1779141687_92fef640 hired','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 15:43:58'),(110,NULL,'unknown','job_application_received','app_1779342306_2d56cbef','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 15:45:08'),(111,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 19:17:29'),(112,'admin_1','admin','partners_save','partner_6a0ecdfd4e90e','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 19:18:54'),(113,'admin_1','admin','partners_update_status','partner_6a0b17ae9a758 rejected','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 19:20:37'),(114,'admin_1','admin','partners_update_status','partner_6a0b17ae9a758 rejected','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 19:20:54'),(115,'admin_1','admin','partners_update_status','partner_6a0b17ae9a758 approved','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 19:21:27'),(116,'admin_1','admin','partners_update_status','partner_6a0b17ae9a758 rejected','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 19:22:23'),(117,'admin_1','admin','partners_generate_nda','partner_6a0b17ae9a758','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 19:22:36'),(118,'admin_1','admin','login','admin','::1','curl/8.19.0','2026-05-21 19:32:54'),(119,'admin_1','admin','partners_generate_nda','partner_6a0b17aa2d02f','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 19:41:41'),(120,NULL,'system','partners_save','partner_6a0ed3c4896a0','::1','curl/8.19.0','2026-05-21 19:43:32'),(121,'admin_1','admin','partners_update_status','partner_6a0ed3c4896a0 under_review','::1','curl/8.19.0','2026-05-21 19:44:01'),(122,'admin_1','admin','partners_update_status','partner_6a0ed3c4896a0 rejected','::1','curl/8.19.0','2026-05-21 19:44:23'),(123,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8457','2026-05-21 19:44:53'),(124,'admin_1','admin','partners_update_status','partner_6a0ed3c4896a0 rejected','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8457','2026-05-21 19:44:53'),(125,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8457','2026-05-21 19:45:07'),(126,'admin_1','admin','partners_delete','partner_6a0ed3c4896a0','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8457','2026-05-21 19:45:07'),(127,'admin_1','admin','partners_save','partner_6a0ed4d3decbe','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 19:48:04'),(128,'admin_1','admin','partners_generate_nda','partner_6a0b17ae9a758','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 19:48:59'),(129,'admin_1','admin','login','admin','::1','curl/8.19.0','2026-05-21 20:00:00'),(130,'admin_1','admin','partners_generate_nda','partner_6a0b17ae9a758','::1','curl/8.19.0','2026-05-21 20:00:14'),(131,'admin_1','admin','update_settings','[]','::1','curl/8.19.0','2026-05-21 20:00:58'),(132,'admin_1','admin','update_settings','[\"partner_nda_document_title\",\"partner_nda_intro_text\",\"partner_nda_purpose_text\",\"partner_nda_confidential_text\",\"partner_nda_obligations_text\",\"partner_nda_exclusions_text\",\"partner_nda_duration_text\",\"partner_nda_return_text\",\"partner_nda_additional_text\",\"partner_nda_left_signatory\",\"partner_nda_right_signatory\",\"partner_nda_left_footer\",\"partner_nda_right_footer\"]','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 20:03:34'),(133,'admin_1','admin','partners_generate_nda','partner_6a0b17ac75605','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 20:03:53'),(134,'admin_1','admin','partners_save','partner_6a0ed9108806d','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 20:06:09'),(135,'admin_1','admin','partners_generate_nda','partner_6a0ed9108806d','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 20:07:08'),(136,'admin_1','admin','partners_generate_nda','partner_6a0ed9108806d','::1','curl/8.19.0','2026-05-21 20:08:57'),(137,'admin_1','admin','partners_generate_nda','partner_6a0ed9108806d','::1','curl/8.19.0','2026-05-21 20:10:13'),(138,'admin_1','admin','partners_generate_nda','partner_6a0ed9108806d','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 20:11:01'),(139,'admin_1','admin','partners_generate_nda','partner_6a0b17aa2d02f','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 20:11:13'),(140,'admin_1','admin','partners_save','partner_6a0eda6b2910c','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 20:11:55'),(141,'admin_1','admin','partners_generate_nda','partner_6a0eda6b2910c','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 20:12:42'),(142,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8457','2026-05-21 20:14:39'),(143,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8457','2026-05-21 20:14:39'),(144,'admin_1','admin','partners_generate_nda','partner_6a0ed9108806d','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8457','2026-05-21 20:14:41'),(145,'admin_1','admin','partners_save','partner_6a0edc03769ea','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 20:18:43'),(146,'admin_1','admin','partners_generate_nda','partner_6a0edc03769ea','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 20:19:23'),(147,'admin_1','admin','partners_delete','partner_6a0edc03769ea','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 20:20:22'),(148,'admin_1','admin','partners_delete','partner_6a0ed9108806d','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 20:20:35'),(149,'admin_1','admin','partners_update_status','partner_6a0b17ac75605 rejected','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 20:21:01'),(150,'admin_1','admin','partners_delete','partner_6a0b17ac75605','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-21 20:21:13'),(151,'admin_1','admin','update_profile','admin','::1','curl/8.19.0','2026-05-21 20:40:29'),(152,'admin_1','admin','login','admin','::1','curl/8.19.0','2026-05-22 22:27:59'),(153,'admin_1','admin','login','admin','::1','curl/8.19.0','2026-05-22 22:28:17'),(154,'admin_1','admin','login','admin','::1','curl/8.19.0','2026-05-22 22:31:51'),(155,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Codex/26.519.31651 Chrome/148.0.7778.97 Electron/42.1.0 Safari/537.36','2026-05-22 22:34:21'),(156,'admin_1','admin','login','admin','::1','curl/8.19.0','2026-05-22 22:38:19'),(157,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-22 22:48:14'),(158,'admin_1','admin','login','admin','::1','curl/8.19.0','2026-05-23 14:24:12'),(159,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Codex/26.519.31651 Chrome/148.0.7778.97 Electron/42.1.0 Safari/537.36','2026-05-23 14:24:46'),(160,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-23 14:30:02'),(161,'admin_1','admin','job_application_status','app_1779109628_c50941c4 rejected','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-23 14:41:42'),(162,'admin_1','admin','job_application_status','app_1779109628_c50941c4 rejected','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-23 14:41:48'),(163,'admin_1','admin','update_settings','[\"store_name\",\"store_email\",\"secondary_email\",\"store_phone\",\"whatsapp_number\",\"whatsapp_url\",\"store_address\",\"office_map_url\",\"business_hours\",\"facebook\",\"youtube\",\"youtube_url\",\"linkedin\",\"twitter\",\"instagram\",\"footer_tagline\",\"email_transport\",\"smtp_host\",\"smtp_port\",\"smtp_encryption\",\"smtp_username\",\"smtp_password\",\"smtp_from_email\",\"smtp_from_name\",\"smtp_reply_to\",\"smtp_test_recipient\"]','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-23 15:27:48'),(164,'admin_1','admin','update_settings','[\"default_currency\",\"partner_portal\",\"hr_admin_email\",\"hero_background_image\",\"hero_background_images\",\"cta_background_image\",\"hero_badge_text\",\"hero_title_line_1\",\"hero_title_line_2\",\"hero_title_line_3\",\"hero_description\",\"hero_primary_label\",\"hero_primary_target\",\"hero_secondary_label\",\"hero_secondary_target\",\"mission_title\",\"mission_text\",\"vision_title\",\"vision_text\",\"about_page_title\",\"about_page_subtitle\",\"about_story_label\",\"about_story_title\",\"about_story_content\"]','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-23 15:27:48'),(165,'admin_1','admin','update_settings','[\"store_name\",\"store_email\",\"secondary_email\",\"store_phone\",\"whatsapp_number\",\"whatsapp_url\",\"store_address\",\"office_map_url\",\"business_hours\",\"facebook\",\"youtube\",\"youtube_url\",\"linkedin\",\"twitter\",\"instagram\",\"footer_tagline\",\"email_transport\",\"smtp_host\",\"smtp_port\",\"smtp_encryption\",\"smtp_username\",\"smtp_password\",\"smtp_from_email\",\"smtp_from_name\",\"smtp_reply_to\",\"smtp_test_recipient\"]','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-23 15:28:24'),(166,'admin_1','admin','update_settings','[\"default_currency\",\"partner_portal\",\"hr_admin_email\",\"hero_background_image\",\"hero_background_images\",\"cta_background_image\",\"hero_badge_text\",\"hero_title_line_1\",\"hero_title_line_2\",\"hero_title_line_3\",\"hero_description\",\"hero_primary_label\",\"hero_primary_target\",\"hero_secondary_label\",\"hero_secondary_target\",\"mission_title\",\"mission_text\",\"vision_title\",\"vision_text\",\"about_page_title\",\"about_page_subtitle\",\"about_story_label\",\"about_story_title\",\"about_story_content\"]','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-23 15:28:24'),(167,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-23 15:55:37'),(168,'admin_1','admin','documents_save','doc_gov','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-23 16:50:49'),(169,'admin_1','admin','save_document_record','governance','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-23 16:50:49'),(170,'admin_1','admin','update_settings','[\"governance_intro\",\"governance_commitment_items\"]','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-23 17:18:56'),(171,'admin_1','admin','update_settings','[\"governance_intro\",\"governance_commitment_items\",\"governance_image\"]','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-23 18:02:37'),(172,'admin_1','admin','documents_save','doc_registration','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-23 18:04:42'),(173,'admin_1','admin','save_document_record','registration','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-23 18:04:42'),(174,'admin_1','admin','documents_save','doc_registration','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-23 18:12:30'),(175,'admin_1','admin','save_document_record','registration','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-23 18:12:30'),(176,'admin_1','admin','update_settings','[\"governance_intro\",\"governance_commitment_items\",\"governance_image\"]','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-23 18:22:58'),(177,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-23 20:58:36'),(178,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-25 19:38:25'),(179,'admin_1','admin','update_project','proj_test_pending_001','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-25 21:23:44'),(180,'admin_1','admin','update_project','proj_1778325555_69ff183364c13','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-05-25 22:14:34'),(181,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36','2026-05-26 15:55:17'),(182,'admin_1','admin','create_project','proj_1779774917_6a1535c585a44','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36','2026-05-26 15:55:17'),(183,'admin_1','admin','update_project','proj_1779774917_6a1535c585a44','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36','2026-05-26 15:55:18'),(184,'admin_1','admin','login','admin','::1','curl/8.18.0','2026-05-26 15:56:09'),(185,'admin_1','admin','login','admin','::1','curl/8.18.0','2026-05-26 15:56:17'),(186,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36','2026-05-26 15:56:43'),(187,'admin_1','admin','create_project','proj_1779775003_6a15361bc4756','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36','2026-05-26 15:56:43'),(188,'admin_1','admin','update_project','proj_1779775003_6a15361bc4756','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36','2026-05-26 15:56:44'),(189,'admin_1','admin','login','admin','::1','curl/8.18.0','2026-05-26 15:57:02'),(190,'admin_1','admin','delete_project','proj_1779775003_6a15361bc4756','::1','curl/8.18.0','2026-05-26 15:57:13'),(191,'admin_1','admin','delete_project','proj_1779774917_6a1535c585a44','::1','curl/8.18.0','2026-05-26 15:57:14'),(192,'admin_1','admin','login','admin','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/148.0.7778.96 Safari/537.36','2026-05-26 15:59:46');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_maintenance`
--

DROP TABLE IF EXISTS `asset_maintenance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_maintenance` (
  `id` varchar(50) NOT NULL,
  `asset_id` varchar(50) NOT NULL,
  `maintenance_type` enum('scheduled','repair','inspection','upgrade') NOT NULL,
  `description` text NOT NULL,
  `cost` decimal(12,2) DEFAULT 0.00,
  `performed_by` varchar(255) DEFAULT NULL,
  `scheduled_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
  `next_maintenance_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_maintenance`
--

LOCK TABLES `asset_maintenance` WRITE;
/*!40000 ALTER TABLE `asset_maintenance` DISABLE KEYS */;
/*!40000 ALTER TABLE `asset_maintenance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assets`
--

DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assets` (
  `id` varchar(50) NOT NULL,
  `asset_tag` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` enum('vehicle','equipment','tool','it','furniture','other') DEFAULT 'equipment',
  `description` text DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_cost` decimal(12,2) DEFAULT NULL,
  `current_value` decimal(12,2) DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `condition` enum('excellent','good','fair','poor','decommissioned') DEFAULT 'good',
  `status` enum('available','assigned','maintenance','disposed') DEFAULT 'available',
  `assigned_to_staff_id` varchar(50) DEFAULT NULL,
  `assigned_to_branch` varchar(100) DEFAULT NULL,
  `assigned_date` date DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_tag` (`asset_tag`),
  KEY `idx_assets_status` (`status`),
  FULLTEXT KEY `idx_search` (`name`,`asset_tag`,`serial_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assets`
--

LOCK TABLES `assets` WRITE;
/*!40000 ALTER TABLE `assets` DISABLE KEYS */;
/*!40000 ALTER TABLE `assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blog_posts`
--

DROP TABLE IF EXISTS `blog_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blog_posts` (
  `id` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `category` varchar(100) DEFAULT 'news',
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `featured_image` varchar(500) DEFAULT NULL,
  `attachment_path` varchar(500) DEFAULT NULL,
  `attachment_name` varchar(255) DEFAULT NULL,
  `attachment_mime` varchar(120) DEFAULT NULL,
  `attachment_size` bigint(20) DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `author` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_blog_slug` (`slug`),
  KEY `idx_blog_status` (`status`),
  KEY `idx_blog_category` (`category`),
  KEY `idx_blog_published` (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_posts`
--

LOCK TABLES `blog_posts` WRITE;
/*!40000 ALTER TABLE `blog_posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `blog_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branch_assets`
--

DROP TABLE IF EXISTS `branch_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branch_assets` (
  `id` varchar(50) NOT NULL,
  `branch_id` varchar(50) NOT NULL,
  `asset_id` varchar(50) NOT NULL,
  `assigned_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `condition_on_assignment` varchar(50) DEFAULT NULL,
  `condition_on_return` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branch_assets`
--

LOCK TABLES `branch_assets` WRITE;
/*!40000 ALTER TABLE `branch_assets` DISABLE KEYS */;
/*!40000 ALTER TABLE `branch_assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branch_content_submissions`
--

DROP TABLE IF EXISTS `branch_content_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branch_content_submissions` (
  `id` varchar(50) NOT NULL,
  `branch_id` varchar(50) NOT NULL,
  `project_id` varchar(50) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `submission_type` enum('document','announcement','progress_update','photo','other') DEFAULT 'document',
  `description` text DEFAULT NULL,
  `content_body` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `status` enum('submitted','under_review','approved','rejected','published','archived') DEFAULT 'submitted',
  `submitted_by` varchar(100) DEFAULT NULL,
  `reviewed_by` varchar(100) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_branch_content_branch` (`branch_id`),
  KEY `idx_branch_content_status` (`status`),
  KEY `idx_branch_content_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branch_content_submissions`
--

LOCK TABLES `branch_content_submissions` WRITE;
/*!40000 ALTER TABLE `branch_content_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `branch_content_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branch_expenses`
--

DROP TABLE IF EXISTS `branch_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branch_expenses` (
  `id` varchar(50) NOT NULL,
  `branch_id` varchar(50) NOT NULL,
  `project_id` varchar(50) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'PGK',
  `expense_date` date NOT NULL,
  `receipt_file` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` varchar(100) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `submitted_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_branch_expenses_branch` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branch_expenses`
--

LOCK TABLES `branch_expenses` WRITE;
/*!40000 ALTER TABLE `branch_expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `branch_expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branch_kpis`
--

DROP TABLE IF EXISTS `branch_kpis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branch_kpis` (
  `id` varchar(50) NOT NULL,
  `branch_id` varchar(50) NOT NULL,
  `period` varchar(7) NOT NULL,
  `projects_active` int(11) DEFAULT 0,
  `projects_completed` int(11) DEFAULT 0,
  `projects_delayed` int(11) DEFAULT 0,
  `budget_total` decimal(15,2) DEFAULT 0.00,
  `budget_spent` decimal(15,2) DEFAULT 0.00,
  `budget_variance` decimal(15,2) DEFAULT 0.00,
  `avg_milestone_completion` decimal(5,2) DEFAULT 0.00,
  `safety_incidents` int(11) DEFAULT 0,
  `staff_headcount` int(11) DEFAULT 0,
  `assets_deployed` int(11) DEFAULT 0,
  `client_satisfaction` decimal(3,1) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `generated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branch_period` (`branch_id`,`period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branch_kpis`
--

LOCK TABLES `branch_kpis` WRITE;
/*!40000 ALTER TABLE `branch_kpis` DISABLE KEYS */;
/*!40000 ALTER TABLE `branch_kpis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branch_milestones`
--

DROP TABLE IF EXISTS `branch_milestones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branch_milestones` (
  `id` varchar(50) NOT NULL,
  `project_id` varchar(50) NOT NULL,
  `branch_id` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `status` enum('pending','in_progress','completed','overdue','blocked') DEFAULT 'pending',
  `weight_percent` int(3) DEFAULT 0,
  `progress_percent` int(3) DEFAULT 0,
  `assigned_to` varchar(255) DEFAULT NULL,
  `blockers` text DEFAULT NULL,
  `evidence_file` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_branch_milestones_project` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branch_milestones`
--

LOCK TABLES `branch_milestones` WRITE;
/*!40000 ALTER TABLE `branch_milestones` DISABLE KEYS */;
/*!40000 ALTER TABLE `branch_milestones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branch_projects`
--

DROP TABLE IF EXISTS `branch_projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branch_projects` (
  `id` varchar(50) NOT NULL,
  `source_project_id` varchar(50) DEFAULT NULL,
  `branch_id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `project_type` varchar(50) DEFAULT 'civil',
  `description` text DEFAULT NULL,
  `client_name` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `expected_end_date` date DEFAULT NULL,
  `actual_end_date` date DEFAULT NULL,
  `budget` decimal(15,2) DEFAULT NULL,
  `contract_value` decimal(15,2) DEFAULT NULL,
  `retention_percent` int(3) DEFAULT 5,
  `variations_count` int(11) DEFAULT 0,
  `variations_value` decimal(12,2) DEFAULT 0.00,
  `spent` decimal(15,2) DEFAULT 0.00,
  `progress_percent` int(3) DEFAULT 0,
  `status` enum('planning','active','on_hold','completed','cancelled') DEFAULT 'planning',
  `qc_status` varchar(20) DEFAULT 'not_started',
  `milestones` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`milestones`)),
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `submitted_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_branch_projects_branch` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branch_projects`
--

LOCK TABLES `branch_projects` WRITE;
/*!40000 ALTER TABLE `branch_projects` DISABLE KEYS */;
/*!40000 ALTER TABLE `branch_projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branch_rfis`
--

DROP TABLE IF EXISTS `branch_rfis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branch_rfis` (
  `id` varchar(50) NOT NULL,
  `branch_id` varchar(50) NOT NULL,
  `project_id` varchar(50) DEFAULT NULL,
  `rfi_number` varchar(30) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('open','under_review','answered','closed') DEFAULT 'open',
  `raised_by` varchar(100) DEFAULT NULL,
  `answered_by` varchar(100) DEFAULT NULL,
  `answer` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `answered_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_branch_rfis_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branch_rfis`
--

LOCK TABLES `branch_rfis` WRITE;
/*!40000 ALTER TABLE `branch_rfis` DISABLE KEYS */;
/*!40000 ALTER TABLE `branch_rfis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branch_site_reports`
--

DROP TABLE IF EXISTS `branch_site_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branch_site_reports` (
  `id` varchar(50) NOT NULL,
  `branch_id` varchar(50) NOT NULL,
  `project_id` varchar(50) DEFAULT NULL,
  `report_date` date NOT NULL,
  `report_type` enum('daily','weekly','monthly','incident') DEFAULT 'weekly',
  `weather` varchar(100) DEFAULT NULL,
  `workers_on_site` int(11) DEFAULT 0,
  `activities_done` text DEFAULT NULL,
  `issues_raised` text DEFAULT NULL,
  `materials_used` text DEFAULT NULL,
  `equipment_used` text DEFAULT NULL,
  `safety_incidents` int(11) DEFAULT 0,
  `incident_detail` text DEFAULT NULL,
  `photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`photos`)),
  `attachment_path` varchar(500) DEFAULT NULL,
  `attachment_name` varchar(255) DEFAULT NULL,
  `attachment_mime` varchar(120) DEFAULT NULL,
  `attachment_size` bigint(20) DEFAULT NULL,
  `submitted_by` varchar(100) DEFAULT NULL,
  `verified_by` varchar(100) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `status` enum('submitted','verified','flagged') DEFAULT 'submitted',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_site_reports_branch_date` (`branch_id`,`report_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branch_site_reports`
--

LOCK TABLES `branch_site_reports` WRITE;
/*!40000 ALTER TABLE `branch_site_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `branch_site_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branch_template_submissions`
--

DROP TABLE IF EXISTS `branch_template_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branch_template_submissions` (
  `id` varchar(50) NOT NULL,
  `template_id` varchar(50) NOT NULL,
  `branch_id` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `form_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`form_data`)),
  `save_directory` varchar(255) DEFAULT 'Branch Templates',
  `status` enum('draft','submitted','reviewed','archived') DEFAULT 'draft',
  `submitted_by` varchar(100) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `reviewed_by` varchar(100) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_template_submission_template` (`template_id`),
  KEY `idx_template_submission_branch` (`branch_id`),
  KEY `idx_template_submission_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branch_template_submissions`
--

LOCK TABLES `branch_template_submissions` WRITE;
/*!40000 ALTER TABLE `branch_template_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `branch_template_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branch_users`
--

DROP TABLE IF EXISTS `branch_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branch_users` (
  `id` varchar(50) NOT NULL,
  `branch_id` varchar(50) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `role` varchar(50) DEFAULT 'branch_user',
  `portal_scope` varchar(20) DEFAULT 'admin',
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_branch_id` (`branch_id`),
  KEY `idx_scope` (`portal_scope`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branch_users`
--

LOCK TABLES `branch_users` WRITE;
/*!40000 ALTER TABLE `branch_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `branch_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branches` (
  `id` varchar(50) NOT NULL,
  `branch_code` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `region` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Papua New Guinea',
  `address` text DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `manager_name` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','pending') DEFAULT 'active',
  `template_id` varchar(50) DEFAULT NULL,
  `registered_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branch_code` (`branch_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contact_submissions`
--

DROP TABLE IF EXISTS `contact_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_submissions` (
  `id` varchar(50) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'new',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_submissions`
--

LOCK TABLES `contact_submissions` WRITE;
/*!40000 ALTER TABLE `contact_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contacts`
--

DROP TABLE IF EXISTS `contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contacts` (
  `id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT 'contact',
  `status` varchar(50) DEFAULT 'new',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_contacts_status_created` (`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contacts`
--

LOCK TABLES `contacts` WRITE;
/*!40000 ALTER TABLE `contacts` DISABLE KEYS */;
INSERT INTO `contacts` VALUES ('contact_1779103198_6a0af5dee6897','Mathew Jonathan','mjonathan10072@gmail.com','81264637','Equipment Hire','Test','contact','read','','2026-05-18 21:19:58','2026-05-21 10:31:48','admin');
/*!40000 ALTER TABLE `contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `csr_items`
--

DROP TABLE IF EXISTS `csr_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `csr_items` (
  `id` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `dropdown_header` varchar(255) DEFAULT NULL,
  `dropdown_subheader` text DEFAULT NULL,
  `dropdown_bullets` longtext DEFAULT NULL,
  `icon` varchar(100) DEFAULT 'fa-hands-helping',
  `image` varchar(500) DEFAULT NULL,
  `button_label` varchar(120) DEFAULT 'Explore More',
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_csr_active` (`is_active`),
  KEY `idx_csr_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `csr_items`
--

LOCK TABLES `csr_items` WRITE;
/*!40000 ALTER TABLE `csr_items` DISABLE KEYS */;
INSERT INTO `csr_items` VALUES ('csr_kongos_rugby','Komagin Kongos Rugby Club','Fostering unity, pride, and teamwork through sport in local communities.','Sport and Community Development','A community-facing initiative that uses sport to strengthen discipline, teamwork, and shared identity.','[\"Encourages teamwork, positive identity, and community pride.\",\"Highlights Komagin support for constructive youth and local engagement.\",\"Gives the website a simple way to present key outcomes without overcrowding the card.\"]','fa-football-ball','images/hero-bg.jpeg','Explore More',2,1,'2026-05-06 07:43:12','2026-05-06 07:43:12','system'),('csr_plowshares','Plowshares Ministry International','Supporting their mission of empowerment and faith development across Papua New Guinea.','Community Partnership Focus','A values-led partnership built around practical support, outreach, and long-term community strengthening.','[\"Supports family-focused outreach and community empowerment initiatives.\",\"Creates room for practical collaboration that benefits local networks.\",\"Allows the public website to explain the purpose and outcomes of the partnership clearly.\"]','fa-church','images/hero-bg.jpeg','Explore More',1,1,'2026-05-06 07:43:12','2026-05-06 07:43:12','system');
/*!40000 ALTER TABLE `csr_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documents` (
  `id` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `category` varchar(100) DEFAULT 'legal',
  `summary` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT 'fa-file-alt',
  `sort_order` int(11) DEFAULT 0,
  `is_visible` tinyint(1) DEFAULT 1,
  `allow_public_download` tinyint(1) DEFAULT 0,
  `filename` varchar(255) DEFAULT NULL,
  `file_url` varchar(500) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documents`
--

LOCK TABLES `documents` WRITE;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
INSERT INTO `documents` VALUES ('doc_gov','Corporate Governance Summary','governance','legal','Placeholder for the public-facing corporate governance summary and board-level compliance overview.xxxx','fa-file-alt',0,1,0,NULL,NULL,NULL,NULL,'2026-05-05 21:43:04','2026-05-23 08:50:49','admin'),('doc_labour','Labour Law References','labour','legal','Placeholder for employment, labour, and workforce-related compliance references published on the website.','fa-file-alt',0,1,0,'doc_labour_20260521_020204.pdf','http://localhost/Komagin/webkomagin/admin/uploads/doc_labour_20260521_020204.pdf',4041059,'application/pdf','2026-05-05 21:43:04','2026-05-21 10:02:04','admin'),('doc_registration','Business Registration Certificate','registration','legal','Placeholder for the official company business registration certificate and related registration evidence.','fa-file-alt',1,1,1,'doc_registration_20260509_091652.pdf','http://localhost/Komagin/webkomagin/admin/uploads/doc_registration_20260509_091652.pdf',1089952,'application/pdf','2026-05-09 17:11:40','2026-05-23 10:12:30','admin'),('doc_tax','Tax Compliance Letter','tax','legal','Placeholder for the latest tax compliance confirmation and supporting documentation.','fa-file-alt',0,1,0,NULL,NULL,NULL,NULL,'2026-05-05 21:43:04',NULL,NULL);
/*!40000 ALTER TABLE `documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `file_categories`
--

DROP TABLE IF EXISTS `file_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `file_categories` (
  `id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `parent_id` varchar(50) DEFAULT NULL,
  `access_role` enum('admin','hr_admin','branch','partner','public') DEFAULT 'admin',
  `description` text DEFAULT NULL,
  `is_system_folder` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `file_categories`
--

LOCK TABLES `file_categories` WRITE;
/*!40000 ALTER TABLE `file_categories` DISABLE KEYS */;
INSERT INTO `file_categories` VALUES ('cat_branch_reports','Branch Reports','branch-reports',NULL,'admin','Branch project, expense, and asset reports',1,'2026-05-05 21:43:04'),('cat_custom_blog','Blog Media','blog-media',NULL,'public','Blog featured images and article visuals',0,'2026-05-18 15:46:24'),('cat_custom_csr','Community Media','community-media',NULL,'public','CSR and community card cover images',0,'2026-05-18 15:46:24'),('cat_custom_general','General Website Media','general-website-media',NULL,'admin','General reusable website assets',0,'2026-05-18 15:46:24'),('cat_custom_hire','Plant Hire Media','plant-hire-media',NULL,'public','Plant and machinery hire item photography',0,'2026-05-18 15:46:24'),('cat_custom_projects','Project Images','project-images',NULL,'public','Project cards, galleries, and progress visuals',0,'2026-05-18 15:46:24'),('cat_custom_services','Service Images','service-images',NULL,'public','Service cards and service detail visuals',0,'2026-05-18 15:46:24'),('cat_custom_site_hero','Hero Images','hero-images',NULL,'public','Homepage hero and slideshow imagery',0,'2026-05-18 15:46:24'),('cat_custom_team','Team Profiles','team-profiles',NULL,'public','Team member profile photography',0,'2026-05-18 15:46:24'),('cat_engineering','Engineering Docs','engineering-docs',NULL,'admin','Technical drawings, project files, and engineering documents',1,'2026-05-05 21:43:04'),('cat_financial','Financial Reports','financial-reports',NULL,'admin',NULL,1,'2026-05-22 22:26:11'),('cat_hr','Careers Files','careers-files',NULL,'admin','Job descriptions, vacancy packs, and careers-related website files',1,'2026-05-05 21:43:04'),('cat_legal','Legal & Compliance','legal-compliance',NULL,'admin','Company legal and compliance files',1,'2026-05-05 21:43:04'),('cat_partner','Partner Resources','partner-resources',NULL,'admin','Partner onboarding and collaboration resources',1,'2026-05-05 21:43:04'),('cat_projects','Project Documents','project-documents',NULL,'admin',NULL,1,'2026-05-22 22:26:11'),('cat_public','Public Documents','public-documents',NULL,'public',NULL,1,'2026-05-22 22:26:11'),('cat_templates','Templates','templates',NULL,'admin','Reusable branch and partner templates',1,'2026-05-05 21:43:04');
/*!40000 ALTER TABLE `file_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hire_items`
--

DROP TABLE IF EXISTS `hire_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hire_items` (
  `id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT 'equipment',
  `short_description` text DEFAULT NULL,
  `specifications` text DEFAULT NULL,
  `rate_note` varchar(255) DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `availability_status` varchar(50) DEFAULT 'available',
  `operator_option` varchar(50) DEFAULT 'optional',
  `delivery_option` varchar(50) DEFAULT 'available',
  `image` varchar(500) DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `featured` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hire_category` (`category`),
  KEY `idx_hire_active` (`is_active`),
  KEY `idx_hire_featured` (`featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hire_items`
--

LOCK TABLES `hire_items` WRITE;
/*!40000 ALTER TABLE `hire_items` DISABLE KEYS */;
INSERT INTO `hire_items` VALUES ('hire_69fd62ac66e1c','Truck','dump_trucks','New truck','this and that','to be discusssed with the director','Pom','available','included','available','image_20260508_061200_69fd6290d52af.jpeg','Komagin',1,3,1,'2026-05-08 06:12:28','2026-05-08 07:03:10','admin'),('hire_69ff16f0d28e9','Truck','dump_trucks','short discription here','key specifications','to be discusssed with the director','Pom','available','optional','available','image_20260509_131351_69ff16ef28ed4.jpeg','Komagin',0,1,1,'2026-05-09 13:13:52','2026-05-09 13:13:52','admin');
/*!40000 ALTER TABLE `hire_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_templates`
--

DROP TABLE IF EXISTS `hr_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_templates` (
  `id` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT 'standard',
  `template_schema` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`template_schema`)),
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_hr_templates_active` (`is_active`),
  KEY `idx_hr_templates_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_templates`
--

LOCK TABLES `hr_templates` WRITE;
/*!40000 ALTER TABLE `hr_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_applications`
--

DROP TABLE IF EXISTS `job_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_applications` (
  `id` varchar(50) NOT NULL,
  `job_id` varchar(50) NOT NULL,
  `applicant_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `cover_letter` text DEFAULT NULL,
  `cv_file` varchar(255) DEFAULT NULL,
  `document_bundle_name` varchar(255) DEFAULT NULL,
  `document_manifest` longtext DEFAULT NULL,
  `document_extract_dir` varchar(500) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'received',
  `notes` text DEFAULT NULL,
  `reviewed_by` varchar(100) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_job_applications_job` (`job_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_applications`
--

LOCK TABLES `job_applications` WRITE;
/*!40000 ALTER TABLE `job_applications` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_listings`
--

DROP TABLE IF EXISTS `job_listings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_listings` (
  `id` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'full_time',
  `description` text NOT NULL,
  `requirements` text DEFAULT NULL,
  `salary_range` varchar(100) DEFAULT NULL,
  `show_salary_range` tinyint(1) DEFAULT 0,
  `closing_date` date DEFAULT NULL,
  `status` enum('draft','published','closed') DEFAULT 'draft',
  `applications_count` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_jobs_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_listings`
--

LOCK TABLES `job_listings` WRITE;
/*!40000 ALTER TABLE `job_listings` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_listings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_requests`
--

DROP TABLE IF EXISTS `leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_requests` (
  `id` varchar(50) NOT NULL,
  `staff_id` varchar(50) NOT NULL,
  `leave_type` enum('annual','sick','maternity','paternity','compassionate','unpaid') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days_requested` int(11) DEFAULT 1,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') DEFAULT 'pending',
  `approved_by` varchar(100) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_leave_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_requests`
--

LOCK TABLES `leave_requests` WRITE;
/*!40000 ALTER TABLE `leave_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `leave_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `managed_files`
--

DROP TABLE IF EXISTS `managed_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `managed_files` (
  `id` varchar(50) NOT NULL,
  `category_id` varchar(50) DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `version` int(11) DEFAULT 1,
  `parent_file_id` varchar(50) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `access_role` enum('admin','hr_admin','branch','partner','public') DEFAULT 'admin',
  `is_template` tinyint(1) DEFAULT 0,
  `download_count` int(11) DEFAULT 0,
  `uploaded_by` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_files_category` (`category_id`),
  FULLTEXT KEY `idx_search` (`title`,`original_name`,`description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `managed_files`
--

LOCK TABLES `managed_files` WRITE;
/*!40000 ALTER TABLE `managed_files` DISABLE KEYS */;
INSERT INTO `managed_files` VALUES ('file_6a05bdc604c08','cat_templates','20260514_141918_6a05bdc602ec7.pdf','OOP Tutorial 6 SemI 2026 Abstraction and Interface.pdf','managed/templates/20260514_141918_6a05bdc602ec7.pdf',636905,'application/pdf',1,NULL,'OOP Tutorial 6 SemI 2026 Abstraction and Interface.pdf','',NULL,'admin',1,0,'admin','2026-05-14 22:19:18','2026-05-21 10:22:10');
/*!40000 ALTER TABLE `managed_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newsletter`
--

DROP TABLE IF EXISTS `newsletter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `newsletter` (
  `id` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `status` varchar(50) DEFAULT 'active',
  `subscribed_at` datetime DEFAULT NULL,
  `unsubscribed_at` datetime DEFAULT NULL,
  `last_email_sent` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newsletter`
--

LOCK TABLES `newsletter` WRITE;
/*!40000 ALTER TABLE `newsletter` DISABLE KEYS */;
INSERT INTO `newsletter` VALUES ('sub_1778026452_69fa87d470e2b','mjonathan10072@gmail.com','active','2026-05-06 10:14:12',NULL,NULL);
/*!40000 ALTER TABLE `newsletter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newsletter_history`
--

DROP TABLE IF EXISTS `newsletter_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `newsletter_history` (
  `id` varchar(50) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `recipient_count` int(11) DEFAULT 0,
  `sent_count` int(11) DEFAULT 0,
  `failed_count` int(11) DEFAULT 0,
  `status` varchar(50) DEFAULT 'sent',
  `sent_at` datetime DEFAULT NULL,
  `sent_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newsletter_history`
--

LOCK TABLES `newsletter_history` WRITE;
/*!40000 ALTER TABLE `newsletter_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `newsletter_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newsletter_subscribers`
--

DROP TABLE IF EXISTS `newsletter_subscribers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `newsletter_subscribers` (
  `id` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_ns_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newsletter_subscribers`
--

LOCK TABLES `newsletter_subscribers` WRITE;
/*!40000 ALTER TABLE `newsletter_subscribers` DISABLE KEYS */;
/*!40000 ALTER TABLE `newsletter_subscribers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `partner_enquiries`
--

DROP TABLE IF EXISTS `partner_enquiries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `partner_enquiries` (
  `id` varchar(50) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `contact_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `enquiry_type` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'new',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partner_enquiries`
--

LOCK TABLES `partner_enquiries` WRITE;
/*!40000 ALTER TABLE `partner_enquiries` DISABLE KEYS */;
/*!40000 ALTER TABLE `partner_enquiries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `partner_showcase`
--

DROP TABLE IF EXISTS `partner_showcase`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `partner_showcase` (
  `id` varchar(50) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `logo` varchar(500) DEFAULT NULL,
  `website_url` varchar(500) DEFAULT NULL,
  `partnership_purpose` text DEFAULT NULL,
  `delivered_value` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_partner_showcase_active` (`is_active`),
  KEY `idx_partner_showcase_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partner_showcase`
--

LOCK TABLES `partner_showcase` WRITE;
/*!40000 ALTER TABLE `partner_showcase` DISABLE KEYS */;
INSERT INTO `partner_showcase` VALUES ('ps_3a9e300f7580','Highlands Resource Group','uploads/image_20260505_055538_69f96a3ae5436.jpeg','https://example.com/highlands-resource-group','Operational partnership for remote project access, logistics readiness, and field construction support.','Komagin delivered project mobilization planning, workforce coordination, and dependable civil works support in challenging locations.',2,1,'2026-05-20 22:52:38','2026-05-20 22:52:38'),('ps_4ad5157952dd','Pacific Build Authority','uploads/image_20260426_121152_69ede4e90003f.jpeg','https://example.com/pacific-build-authority','Infrastructure planning support and long-term civil delivery coordination across urban growth corridors.','Komagin delivered execution support, technical coordination, and site-ready implementation planning for staged works.',1,1,'2026-05-20 22:52:38','2026-05-20 22:52:38'),('ps_7bf371e35b5b','Horizon Water Projects','uploads/image_20260505_234644_69fa654458203.jpeg','https://example.com/horizon-water-projects','Water infrastructure partnership centered on construction support, phased rollout planning, and dependable execution.','Komagin delivered field coordination, staged deployment support, and construction-focused project assistance from start to finish.',8,1,'2026-05-20 22:52:38','2026-05-20 22:52:38'),('ps_b3feb20b7492','Coastal Freight Systems','uploads/image_20260505_055930_69f96b2272d4c.jpeg','https://example.com/coastal-freight-systems','Transport and site access partnership supporting materials movement and construction sequencing.','Komagin delivered route planning support, coordinated delivery staging, and improved site readiness for active work fronts.',3,1,'2026-05-20 22:52:38','2026-05-20 22:52:38'),('ps_bec1d025c860','Seaside Housing Trust','uploads/image_20260505_124028_69f9c91c22f39.jpeg','https://example.com/seaside-housing-trust','Community-focused partnership around housing support works, site improvements, and public-facing infrastructure delivery.','Komagin delivered practical construction management, site preparation support, and dependable coordination for milestone completion.',6,1,'2026-05-20 22:52:38','2026-05-20 22:52:38');
/*!40000 ALTER TABLE `partner_showcase` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `partners`
--

DROP TABLE IF EXISTS `partners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `partners` (
  `id` varchar(50) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `contact_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `expertise` varchar(500) DEFAULT NULL,
  `portfolio_url` varchar(500) DEFAULT NULL,
  `status` enum('enquiry','under_review','approved','rejected','active','inactive') DEFAULT 'enquiry',
  `access_scope` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`access_scope`)),
  `nda_signed` tinyint(1) DEFAULT 0,
  `nda_date` date DEFAULT NULL,
  `document_bundle_path` varchar(500) DEFAULT NULL,
  `document_bundle_name` varchar(255) DEFAULT NULL,
  `document_manifest` longtext DEFAULT NULL,
  `document_extract_dir` varchar(500) DEFAULT NULL,
  `rejection_reason` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `enquiry_date` datetime DEFAULT NULL,
  `approved_date` datetime DEFAULT NULL,
  `approved_by` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_partners_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partners`
--

LOCK TABLES `partners` WRITE;
/*!40000 ALTER TABLE `partners` DISABLE KEYS */;
/*!40000 ALTER TABLE `partners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll`
--

DROP TABLE IF EXISTS `payroll`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll` (
  `id` varchar(50) NOT NULL,
  `staff_id` varchar(50) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `base_salary` decimal(12,2) NOT NULL,
  `allowances` decimal(12,2) DEFAULT 0.00,
  `deductions` decimal(12,2) DEFAULT 0.00,
  `tax` decimal(12,2) DEFAULT 0.00,
  `net_pay` decimal(12,2) NOT NULL,
  `payment_method` enum('bank_transfer','cash','cheque') DEFAULT 'bank_transfer',
  `payment_status` enum('pending','processed','paid') DEFAULT 'pending',
  `processed_at` datetime DEFAULT NULL,
  `processed_by` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_payroll_period` (`period_start`,`period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll`
--

LOCK TABLES `payroll` WRITE;
/*!40000 ALTER TABLE `payroll` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_categories`
--

DROP TABLE IF EXISTS `project_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_categories` (
  `id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_project_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_categories`
--

LOCK TABLES `project_categories` WRITE;
/*!40000 ALTER TABLE `project_categories` DISABLE KEYS */;
INSERT INTO `project_categories` VALUES ('projcat_building','Building','building','Commercial and residential building construction',2,'2026-05-25 20:06:40','2026-05-25 20:06:40'),('projcat_infrastructure','Infrastructure','infrastructure','Roads, utilities, and civil infrastructure',3,'2026-05-25 20:06:40','2026-05-25 20:06:40'),('projcat_subdivision','Subdivision','subdivision','Residential and land subdivision projects',1,'2026-05-25 20:06:40','2026-05-25 20:06:40');
/*!40000 ALTER TABLE `project_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects` (
  `id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT 'subdivision',
  `status` varchar(20) DEFAULT 'PENDING',
  `branch_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `branch_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `gallery_images` longtext DEFAULT NULL,
  `technologies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`technologies`)),
  `scope_sections` longtext DEFAULT NULL,
  `featured` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_projects_category` (`category`),
  KEY `idx_projects_status` (`status`),
  FULLTEXT KEY `idx_search` (`name`,`description`,`location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
INSERT INTO `projects` VALUES ('proj_1778325555_69ff183364c13','ATS Subdivision Development','Portion 695','infrastructure','COMPLETED',NULL,'Port Moresby Branch','Development of allotents around ATS.','admin/uploads/image_20260509_131841_69ff181123a71.jpeg','[\"admin\\/uploads\\/image_20260509_162721_69ff444993e38.jpeg\",\"admin\\/uploads\\/image_20260509_162733_69ff4455134e9.jpeg\",\"admin\\/uploads\\/image_20260509_162754_69ff446abe606.jpeg\"]','[\"Technologies used here\",\"Drone.\"]','[{\"title\":\"Project Scope\",\"text\":\"this will be wheree a comprehensive summary of the project will be.\",\"points\":[\"Development of allotents around ATS.\",\"New Developments\",\"Fresh Developments\"]}]',1,'2026-05-09 21:19:15','2026-05-25 22:14:34','admin','admin'),('proj_test_pending_001','New Building Project','Port Moresby','building','COMPLETED',NULL,NULL,'A new building construction project.','images/hero-bg.jpeg','[]','[]','[{\"title\":\"Project Scope\",\"text\":\"\",\"points\":[\"A new building construction project.\"]}]',0,'2026-05-25 19:45:25','2026-05-25 21:23:44',NULL,'admin');
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `services` (
  `id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(50) DEFAULT 'core',
  `description` text DEFAULT NULL,
  `detail_intro` text DEFAULT NULL,
  `detail_sections` longtext DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'fa-cog',
  `image` varchar(255) DEFAULT NULL,
  `featured` tinyint(1) DEFAULT 0,
  `order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_services_category_order` (`category`,`order`),
  FULLTEXT KEY `idx_search` (`name`,`description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES ('svc_1778325808_69ff19306f0bc','engineeering and Design','structural','standard engineering and design services offered by komagin.',NULL,NULL,'fa-cog','image_20260509_132322_69ff192aef3d8.jpeg',0,1,'2026-05-09 21:23:28','2026-05-09 21:23:28','admin',NULL);
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=184 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'store_name','Komagin Limited','2026-05-23 15:28:24','admin'),(2,'store_email','info@komagin.com','2026-05-23 15:28:24','admin'),(3,'store_phone','+675 1234 5678','2026-05-23 15:28:24','admin'),(4,'store_address','Port Moresby, Papua New Guinea','2026-05-23 15:28:24','admin'),(5,'facebook','https://https://facebook.com/komaginlimited','2026-05-23 15:28:24','admin'),(6,'linkedin','https://https://linkedin.com/company/komagin-limited','2026-05-23 15:28:24','admin'),(7,'twitter','https://https://twitter.com/komaginlimited','2026-05-23 15:28:24','admin'),(8,'instagram','https://https://instagram.com/komaginlimited','2026-05-23 15:28:24','admin'),(9,'footer_tagline','Quality civil and structural engineering services for Papua New Guinea\'s infrastructure development.','2026-05-23 15:28:24','admin'),(10,'default_currency','PGK','2026-05-23 15:28:24','admin'),(11,'branch_template_mode','auto','2026-05-05 21:43:04',NULL),(12,'partner_portal','enabled','2026-05-23 15:28:24','admin'),(13,'hr_admin_email','hr@komagin.com','2026-05-23 15:28:24','admin'),(14,'branch_portal','disabled','2026-05-05 21:43:04',NULL),(27,'hero_background_image','image_20260505_234633_69fa653904eda.jpeg','2026-05-23 15:28:24','admin'),(28,'cta_background_image','image_20260505_234644_69fa654458203.jpeg','2026-05-23 15:28:24','admin'),(29,'hero_badge_text','Est. 2015 | Engineering Excellence Across Markets','2026-05-23 15:28:24','admin'),(30,'hero_title_line_1','Quality Civil &','2026-05-23 15:28:24','admin'),(31,'hero_title_line_2','Structural Engineering','2026-05-23 15:28:24','admin'),(32,'hero_title_line_3','for Complex Developments','2026-05-23 15:28:24','admin'),(33,'hero_description','Delivering technical excellence through innovative engineering solutions, combining precision delivery with sustainable development practices for clients, projects, and infrastructure programs across local, regional, and international markets.','2026-05-23 15:28:24','admin'),(34,'hero_primary_label','Our Projects','2026-05-23 15:28:24','admin'),(35,'hero_primary_target','projects','2026-05-23 15:28:24','admin'),(36,'hero_secondary_label','Request Consultation','2026-05-23 15:28:24','admin'),(37,'hero_secondary_target','contact','2026-05-23 15:28:24','admin'),(38,'mission_title','Our Mission','2026-05-23 15:28:24','admin'),(39,'mission_text','To provide high-quality civil and engineering services to our clients through the provision of professional, technology-driven engineering solutions that supports businesses, and the infrastructure development of the country.','2026-05-23 15:28:24','admin'),(40,'vision_title','Our Vision','2026-05-23 15:28:24','admin'),(41,'vision_text','We strive to make Papua New Guinea a better place to live and do business by helping develop the nation through providing quality civil and structural engineering services.','2026-05-23 15:28:24','admin'),(42,'about_page_title','About Komagin Limited','2026-05-23 15:28:24','admin'),(43,'about_page_subtitle','Delivering engineering excellence in Papua New Guinea since 2015','2026-05-23 15:28:24','admin'),(44,'about_story_label','OUR STORY','2026-05-23 15:28:24','admin'),(45,'about_story_title','Company History','2026-05-23 15:28:24','admin'),(46,'about_story_content','Established in November 2015, Komagin Limited was founded in response to the increasing demand for civil and structural engineering projects development as the result of Papua New Guinea being a developing nation where many infrastructural activities are taking place.\n\nDespite being a relatively new entrant, the company is backed by a wealth of practical experience drawn from its management team and frontline operatives. These professionals come with years of background in engineering, technical services, and business expertise.\n\nKomagin Limited\'s operational model is distinguished by its commitment to leveraging human expertise in combination with modern engineering technologies, including GPS, Total Stations (GNSS), and drones.\n\nWe aim to retain all our professionals within PNG to help build our economy. Furthermore, we use every opportunity to train our skilled technical professionals, with the intent to become competent at the global level.','2026-05-23 15:28:24','admin'),(47,'hire_page_badge','Plant & Machines for Hire','2026-05-08 14:13:31','admin'),(48,'hire_page_title','Reliable Equipment Hire for Demanding Projects','2026-05-08 14:13:31','admin'),(49,'hire_page_subtitle','Access well-maintained plant, machinery, and support equipment backed by Komagin’s engineering and project delivery standards.','2026-05-08 14:13:31','admin'),(50,'hire_page_intro','Browse available plant and machine hire options for civil works, infrastructure delivery, surveying support, and site operations. Each listing can be updated from the admin panel as availability changes.','2026-05-08 14:13:31','admin'),(51,'hire_page_contact_phone','+675 7159 0097','2026-05-08 14:13:31','admin'),(52,'hire_page_contact_email','jkoma@komagin.com','2026-05-08 14:13:31','admin'),(53,'hire_page_cta_label','Request Equipment Hire','2026-05-08 14:13:31','admin'),(54,'hire_page_cta_target','contact','2026-05-08 14:13:31','admin'),(55,'hire_page_background_image','images/hero-bg.jpeg','2026-05-08 14:13:31','admin'),(56,'partner_nda_document_title','Non-Disclosure Agreement','2026-05-21 20:03:34','admin'),(57,'partner_nda_intro_text','This Non-Disclosure Agreement is entered into on {{effective_date}} between {{komagin_company}} and {{partner_company}}. The parties may exchange technical, commercial, operational, financial, and project information while assessing or carrying out collaboration opportunities.','2026-05-21 20:03:34','admin'),(58,'partner_nda_purpose_text','Confidential information may be shared strictly for partnership assessment, project coordination, service delivery preparation, due diligence, and any related business discussions approved by both parties.','2026-05-21 20:03:34','admin'),(59,'partner_nda_confidential_text','Confidential information includes documents, drawings, designs, specifications, schedules, commercial terms, technical processes, pricing, client data, internal reports, and any other non-public information shared in written, digital, verbal, or visual form.','2026-05-21 20:03:34','admin'),(60,'partner_nda_obligations_text','The receiving party must keep confidential information secure, restrict access to personnel with a legitimate need to know, avoid disclosure to unauthorized third parties, and use the information only for the agreed business purpose.','2026-05-21 20:03:34','admin'),(61,'partner_nda_exclusions_text','Confidentiality obligations do not apply to information that is already publicly available, independently developed without access to the disclosed material, lawfully received from another source without restriction, or required to be disclosed by law or regulation.','2026-05-21 20:03:34','admin'),(62,'partner_nda_duration_text','The confidentiality obligations begin on the effective date of this agreement and continue during discussions, project activity, and after the working relationship ends unless released in writing by the disclosing party.','2026-05-21 20:03:34','admin'),(63,'partner_nda_return_text','Upon request, the receiving party must return, delete, or securely destroy confidential materials and any copies in its possession, except for records required to be retained by law or internal compliance obligations.','2026-05-21 20:03:34','admin'),(64,'partner_nda_additional_text','This template may be used as the standard NDA for partner engagements unless a project-specific legal review requires additional clauses or revisions before signing.','2026-05-21 20:03:34','admin'),(65,'partner_nda_left_signatory','{{komagin_company}}','2026-05-21 20:03:34','admin'),(66,'partner_nda_right_signatory','{{partner_company}}','2026-05-21 20:03:34','admin'),(67,'partner_nda_left_footer','Authorized Representative','2026-05-21 20:03:34','admin'),(68,'partner_nda_right_footer','Date','2026-05-21 20:03:34','admin'),(69,'company_name','Komagin Limited','2026-05-22 22:26:11','setup'),(70,'company_tagline','Engineering Excellence in Papua New Guinea','2026-05-22 22:26:11','setup'),(71,'contact_email','info@komagin.com','2026-05-22 22:26:11','setup'),(72,'contact_phone','+675 1234 5678','2026-05-22 22:26:11','setup'),(73,'office_address','Port Moresby, Papua New Guinea','2026-05-22 22:26:11','setup'),(74,'whatsapp_number','67512345678','2026-05-23 15:28:24','admin'),(75,'show_partner_portal','0','2026-05-22 22:26:11','setup'),(78,'secondary_email','projects@komagin.com','2026-05-23 15:28:24','admin'),(81,'whatsapp_url','https://https://wa.me/67512345678','2026-05-23 15:28:24','admin'),(83,'office_map_url','https://https://maps.google.com/?q=Port%20Moresby%2C%20Papua%20New%20Guinea','2026-05-23 15:28:24','admin'),(84,'business_hours','Monday - Friday: 8:00 AM - 5:00 PM\nSaturday: 9:00 AM - 1:00 PM','2026-05-23 15:28:24','admin'),(86,'youtube','https://https://youtube.com/@komaginlimited','2026-05-23 15:28:24','admin'),(87,'youtube_url','https://https://youtube.com/@komaginlimited','2026-05-23 15:28:24','admin'),(92,'email_transport','php_mail','2026-05-23 15:28:24','admin'),(93,'smtp_host','','2026-05-23 15:28:24','admin'),(94,'smtp_port','587','2026-05-23 15:28:24','admin'),(95,'smtp_encryption','tls','2026-05-23 15:28:24','admin'),(96,'smtp_username','','2026-05-23 15:28:24','admin'),(97,'smtp_password','','2026-05-23 15:28:24','admin'),(98,'smtp_from_email','info@komagin.com','2026-05-23 15:28:24','admin'),(99,'smtp_from_name','Komagin Limited','2026-05-23 15:28:24','admin'),(100,'smtp_reply_to','info@komagin.com','2026-05-23 15:28:24','admin'),(101,'smtp_test_recipient','info@komagin.com','2026-05-23 15:28:24','admin'),(106,'hero_background_images','[\"image_20260505_234633_69fa653904eda.jpeg\",\"images\\/hero-bg.jpeg\",\"image_20260505_055538_69f96a3ae5436.jpeg\",\"image_20260505_055930_69f96b2272d4c.jpeg\",\"image_20260505_060920_69f96d70ce8d8.jpeg\",\"image_20260505_122337_69f9c52928f9e.jpeg\"]','2026-05-23 15:28:24','admin'),(176,'governance_intro','Done. Here\'s what changed and why:\n\nButtons — Added display: inline-flex; align-items: center; gap: 6px; height: 36px; line-height: 1 to .download-btn. The inline-flex + align-items: center locks the icon and text to the same baseline regardless of font rendering. Also scoped .document-item i → .document-info > i so the gold icon box style only applies to the document-type icon, not the icon inside the button.\n\nGovernance card image — Removed aspect-ratio, border-radius, box-shadow from the image entirely. The image container now uses negative margins (margin: -40px -36px -40px 0) to bleed flush against the card\'s right/top/bottom edges, with border-radius: 0 14px 14px 0 matching the card\'s own corner radius. align-items: stretch on the flex container makes the image column grow to exactly the height of the text column — no fixed frame, just a natural fill. On mobile the image flips to the bottom, bleeds to the card edges with margin: 24px -Xpx -Xpx -Xpx (values matched to each breakpoint\'s card padding), and gets a fixed height: 220px / 180px since there\'s no sibling height to stretch to.','2026-05-23 18:22:58','admin'),(177,'governance_commitment_items','','2026-05-23 18:22:58','admin'),(180,'governance_image','image_20260523_100229_6a115f15c9dac.jpg','2026-05-23 18:22:58','admin');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `social_media_library`
--

DROP TABLE IF EXISTS `social_media_library`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `social_media_library` (
  `id` varchar(50) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `usage_count` int(11) DEFAULT 0,
  `uploaded_by` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `social_media_library`
--

LOCK TABLES `social_media_library` WRITE;
/*!40000 ALTER TABLE `social_media_library` DISABLE KEYS */;
/*!40000 ALTER TABLE `social_media_library` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `social_platforms`
--

DROP TABLE IF EXISTS `social_platforms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `social_platforms` (
  `id` varchar(50) NOT NULL,
  `platform` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `is_enabled` tinyint(1) DEFAULT 1,
  `access_token` text DEFAULT NULL,
  `page_id` varchar(100) DEFAULT NULL,
  `account_name` varchar(100) DEFAULT NULL,
  `api_key` text DEFAULT NULL,
  `api_secret` text DEFAULT NULL,
  `connected_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `verification_status` varchar(30) DEFAULT 'pending',
  `verification_message` text DEFAULT NULL,
  `verification_checked_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `platform` (`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `social_platforms`
--

LOCK TABLES `social_platforms` WRITE;
/*!40000 ALTER TABLE `social_platforms` DISABLE KEYS */;
INSERT INTO `social_platforms` VALUES ('splat_whatsapp','whatsapp','WhatsApp Channel',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'pending',NULL,NULL,'2026-05-06 15:59:55','2026-05-26 16:00:17'),('sp_fb','facebook','Facebook Page',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'incomplete','No access token is configured for this platform.','2026-05-06 16:00:03','2026-05-05 21:43:04','2026-05-26 16:00:17'),('sp_ig','instagram','Instagram Business',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'incomplete','No access token is configured for this platform.','2026-05-21 15:58:59','2026-05-05 21:43:04','2026-05-26 16:00:17'),('sp_li','linkedin','LinkedIn Page',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'pending',NULL,NULL,'2026-05-05 21:43:04','2026-05-26 16:00:17'),('sp_tt','tiktok','TikTok Business',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'pending',NULL,NULL,'2026-05-05 21:43:04','2026-05-26 16:00:17'),('sp_tw','twitter','X / Twitter',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'pending',NULL,NULL,'2026-05-05 21:43:04','2026-05-26 16:00:17');
/*!40000 ALTER TABLE `social_platforms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `social_post_results`
--

DROP TABLE IF EXISTS `social_post_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `social_post_results` (
  `id` varchar(50) NOT NULL,
  `post_id` varchar(50) NOT NULL,
  `platform` varchar(50) NOT NULL,
  `platform_post_id` varchar(200) DEFAULT NULL,
  `status` enum('pending','published','failed') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `post_url` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_social_results_post` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `social_post_results`
--

LOCK TABLES `social_post_results` WRITE;
/*!40000 ALTER TABLE `social_post_results` DISABLE KEYS */;
/*!40000 ALTER TABLE `social_post_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `social_posts`
--

DROP TABLE IF EXISTS `social_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `social_posts` (
  `id` varchar(50) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text NOT NULL,
  `media_path` varchar(500) DEFAULT NULL,
  `media_type` varchar(20) DEFAULT NULL,
  `link_url` varchar(500) DEFAULT NULL,
  `hashtags` text DEFAULT NULL,
  `platforms` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`platforms`)),
  `status` enum('draft','scheduled','publishing','published','failed','partial') DEFAULT 'draft',
  `scheduled_at` datetime DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_social_posts_status` (`status`),
  KEY `idx_social_posts_scheduled` (`scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `social_posts`
--

LOCK TABLES `social_posts` WRITE;
/*!40000 ALTER TABLE `social_posts` DISABLE KEYS */;
/*!40000 ALTER TABLE `social_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff` (
  `id` varchar(50) NOT NULL,
  `employee_id` varchar(30) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(255) NOT NULL,
  `employment_type` enum('full_time','part_time','contract','casual') DEFAULT 'full_time',
  `status` enum('active','on_leave','terminated','probation') DEFAULT 'active',
  `date_hired` date DEFAULT NULL,
  `date_terminated` date DEFAULT NULL,
  `salary` decimal(12,2) DEFAULT NULL,
  `bank_account` varchar(100) DEFAULT NULL,
  `tax_file_number` varchar(50) DEFAULT NULL,
  `emergency_contact_name` varchar(255) DEFAULT NULL,
  `emergency_contact_phone` varchar(50) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  KEY `idx_staff_status` (`status`),
  FULLTEXT KEY `idx_search` (`full_name`,`position`,`department`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff`
--

LOCK TABLES `staff` WRITE;
/*!40000 ALTER TABLE `staff` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `team`
--

DROP TABLE IF EXISTS `team`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `team` (
  `id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `bio` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `team`
--

LOCK TABLES `team` WRITE;
/*!40000 ALTER TABLE `team` DISABLE KEYS */;
/*!40000 ALTER TABLE `team` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `testimonials`
--

DROP TABLE IF EXISTS `testimonials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `testimonials` (
  `id` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `role` varchar(255) DEFAULT NULL,
  `rating` int(11) DEFAULT 5,
  `content` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `testimonials`
--

LOCK TABLES `testimonials` WRITE;
/*!40000 ALTER TABLE `testimonials` DISABLE KEYS */;
/*!40000 ALTER TABLE `testimonials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` varchar(50) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `role` varchar(50) DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `password_changed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES ('admin_1','admin','$2y$10$TtmXCtJmQq5euR1erQoIPu0S0lUc9XaITYs51e2Jf6YoaiFGQtn86','admin@komagin.com','admin',1,'2026-05-05 21:43:03','2026-05-21 20:40:29','2026-05-26 15:59:46','2026-05-21 08:59:06'),('hradmin_1','hradmin','$2y$10$OXJpoNPMZKzzsXzorQ1vRetSHMmgEshMoq4wiibBKWg7prCtglgqu','hr@komagin.com','hr_admin',1,'2026-05-05 21:43:03',NULL,NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'komagin_db'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-26 16:47:05

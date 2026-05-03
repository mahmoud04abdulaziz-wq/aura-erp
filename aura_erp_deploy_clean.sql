-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: aura_stone_erp
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
-- Table structure for table `bom_bill_lines`
--

DROP TABLE IF EXISTS `bom_bill_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bom_bill_lines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bill_id` int(11) NOT NULL,
  `item_id` varchar(50) NOT NULL,
  `quantity_consumed` decimal(12,3) NOT NULL,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_cost` decimal(15,2) GENERATED ALWAYS AS (`quantity_consumed` * `unit_cost`) STORED,
  PRIMARY KEY (`id`),
  KEY `idx_bill` (`bill_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bom_bill_lines`
--

LOCK TABLES `bom_bill_lines` WRITE;
/*!40000 ALTER TABLE `bom_bill_lines` DISABLE KEYS */;
INSERT INTO `bom_bill_lines` VALUES (1,1,'RM-002',60.000,0.60,36.00),(2,1,'RM-004',40.000,1.20,48.00),(3,1,'RM-006',2.000,8.00,16.00),(4,1,'RM-012',18.000,0.01,0.18),(5,2,'RM-001',200.000,0.85,170.00),(6,2,'RM-004',480.000,1.20,576.00),(7,2,'RM-011',360.000,0.15,54.00),(8,2,'RM-012',84.000,0.01,0.84),(9,2,'RM-013',1.080,5.00,5.40),(10,2,'RM-014',0.260,3.50,0.91),(11,3,'RM-001',250.000,0.85,212.50),(12,3,'RM-004',600.000,1.20,720.00),(13,3,'RM-011',450.000,0.15,67.50),(14,3,'RM-012',105.000,0.01,1.05),(15,3,'RM-013',1.350,5.00,6.75),(16,3,'RM-014',0.325,3.50,1.14);
/*!40000 ALTER TABLE `bom_bill_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bom_bills`
--

DROP TABLE IF EXISTS `bom_bills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bom_bills` (
  `bill_id` int(11) NOT NULL AUTO_INCREMENT,
  `production_id` varchar(50) NOT NULL,
  `recipe_id` int(11) NOT NULL,
  `mix_runs` int(11) DEFAULT 1,
  `total_material_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('Generated','Sent to Finance','Acknowledged') DEFAULT 'Generated',
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `generated_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`bill_id`),
  KEY `idx_prod` (`production_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bom_bills`
--

LOCK TABLES `bom_bills` WRITE;
/*!40000 ALTER TABLE `bom_bills` DISABLE KEYS */;
INSERT INTO `bom_bills` VALUES (1,'MX-260419-3279',2,1,100.18,'Generated','2026-04-19 07:18:26',1,NULL),(2,'MX-260419-0083',1,4,807.15,'Generated','2026-04-19 07:25:28',1,NULL),(3,'MX-260419-7714',1,5,1008.94,'Generated','2026-04-23 10:00:01',1,NULL);
/*!40000 ALTER TABLE `bom_bills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chart_of_accounts`
--

DROP TABLE IF EXISTS `chart_of_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chart_of_accounts` (
  `account_id` int(11) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `account_type` enum('Asset','Liability','Equity','Revenue','Expense') NOT NULL,
  PRIMARY KEY (`account_id`),
  UNIQUE KEY `account_name` (`account_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chart_of_accounts`
--

LOCK TABLES `chart_of_accounts` WRITE;
/*!40000 ALTER TABLE `chart_of_accounts` DISABLE KEYS */;
INSERT INTO `chart_of_accounts` VALUES (1000,'Cash','Asset'),(1100,'Accounts Receivable','Asset'),(1200,'Inventory - Raw','Asset'),(1300,'Inventory - Finished','Asset'),(2000,'Accounts Payable','Liability'),(2100,'Accrued Wages','Liability'),(3000,'Owner\'s Equity','Equity'),(4000,'Sales Revenue','Revenue'),(4100,'Other Income','Revenue'),(5000,'Cost of Goods Sold','Expense'),(5100,'Wages Expense','Expense'),(5200,'Materials Expense','Expense'),(5300,'Overhead Expense','Expense');
/*!40000 ALTER TABLE `chart_of_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `lead_status` enum('New','Contacted','Quoted','Converted') DEFAULT 'New',
  `default_address` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`customer_id`),
  UNIQUE KEY `company_name` (`company_name`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,'Al-Aqsa Construction Co.','Mohammad Al-Khatib','+962-79-555-0101','info@alaqsa-const.jo','Converted','Amman, Jordan'),(2,'Petra Stone Designs','Layla Hammoud','+962-79-555-0202','sales@petrastone.jo','Converted','Aqaba, Jordan'),(3,'Gulf Building Materials','Abdullah Al-Rashid','+971-50-555-0303','procurement@gulfbm.ae','Converted','Dubai, UAE'),(4,'none','Mahmoud Abdelaziz','0797319978','martisasura.04@gmail.com','Contacted','Gardens'),(5,'Mediterranean Builders','Elena Papadopoulos','+30-210-555-0505','elena@medbuild.gr','Contacted','Athens, Greece'),(6,'Sahara Interior Design','Fatima Benkhadra','+212-66-555-0606','fatima@sahara-id.ma','Converted','Casablanca, Morocco'),(7,'Test Corp','Test User','+962 79 000 0000','test@example.com','Converted','=== ONLINE ORDER ===\nArtificial Granite Tile 60x60 ×2 @$35.00 = $70.00\n---\nTotal: $70.00\nPayment: Bank Transfer\nDelivery: Amman, Jordan'),(9,'Alpha Corporation','Alice','','alice@alpha.com','Converted','Inquiry about: Artificial Granite Tile 60x60 - Note: '),(10,'Omega Corp','Oliver','','oliver@omega.com','Converted','Inquiry about: Artificial Granite Tile 60x60 - Note: '),(11,'Motaz\'s construction','Mahmoud Abdelaziz','0797319978','martisasura.04@gmail.com','Converted','Inquiry about: Decorative Stone Panel 100x50 - Note: 50'),(12,'Adam\'s construction','adam','0797319978','adam@gmail.com','Converted','Inquiry about: Decorative Stone Panel 100x50 - Note: 50'),(13,'CART TEST ORDER','Martis','009627319978','martis@hotmail.com','Converted','=== ONLINE ORDER ===\nBathroom Vanity Top ×2 @$85.00 = $170.00\nDecorative Stone Panel 100x50 ×2 @$65.00 = $130.00\n---\nTotal: $300.00\nPayment: Bank Transfer\nDelivery: Amman jordan'),(15,'TestPipeline Corp','Pipeline Test','+962-79-000-0000','test@pipeline.com','Converted','=== ONLINE ORDER ===\nArtificial Granite Tile 60x60 ×1 @$35.00 = $35.00\n---\nTotal: $35.00\nPayment: Bank Transfer'),(16,'TestPipeline Corp V2','Pipeline Tester','','tester@pipeline.com','Converted','=== ONLINE ORDER ===\nArtificial Granite Tile 60x60 ×1 @$35.00 = $35.00\n---\nTotal: $35.00\nPayment: Bank Transfer\nDelivery: Test Street 1'),(17,'Sabri\'s Consturction','Mohammad Sabri','','Sabri@example.com','Converted','=== ONLINE ORDER ===\nArtificial Marble Slab 120x60 ×3 @$45.00 = $135.00\n---\nTotal: $135.00\nPayment: Cash on Delivery\nDelivery: Amman,Jordan'),(18,'yazan','YZ','0797319978','example@test.com','Converted','=== ONLINE ORDER ===\nArtificial Granite Tile 60x60 ×5 @$35.00 = $175.00\n---\nTotal: $175.00\nPayment: Credit Card\nDelivery: Gardens'),(19,'al aqsa','Mahmoud Abdelaziz','+962797319978','martisasura.04@gmail.com','Converted','=== ONLINE ORDER ===\nArtificial Granite Tile 60x60 ×8 @$35.00 = $280.00\n---\nTotal: $280.00\nPayment: Cash on Delivery\nDelivery: Gardens');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `defect_logs`
--

DROP TABLE IF EXISTS `defect_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `defect_logs` (
  `defect_id` int(11) NOT NULL AUTO_INCREMENT,
  `production_id` varchar(50) NOT NULL,
  `defect_reason` varchar(255) NOT NULL,
  `scrap_quantity` decimal(12,3) NOT NULL,
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`defect_id`),
  KEY `production_id` (`production_id`),
  CONSTRAINT `defect_logs_ibfk_1` FOREIGN KEY (`production_id`) REFERENCES `production_orders` (`production_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `defect_logs`
--

LOCK TABLES `defect_logs` WRITE;
/*!40000 ALTER TABLE `defect_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `defect_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL AUTO_INCREMENT,
  `department_name` varchar(100) NOT NULL,
  PRIMARY KEY (`department_id`),
  UNIQUE KEY `department_name` (`department_name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'Executive Board'),(5,'Finance'),(6,'Information Technology'),(4,'Procurement'),(2,'Production'),(3,'Sales'),(7,'Warehouse & Inventory');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL AUTO_INCREMENT,
  `department_id` int(11) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(150) NOT NULL,
  `cv_document_url` varchar(255) DEFAULT NULL,
  `verification_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `hire_date` date DEFAULT NULL,
  `performance_score` decimal(3,2) DEFAULT 0.00,
  `base_allowances` decimal(12,2) DEFAULT 0.00,
  `overtime_rate` decimal(12,2) DEFAULT 0.00,
  `base_salary` decimal(10,2) DEFAULT 0.00,
  `ssc_number` varchar(50) DEFAULT NULL,
  `available_annual_leave` int(11) DEFAULT 14,
  PRIMARY KEY (`employee_id`),
  UNIQUE KEY `email` (`email`),
  KEY `department_id` (`department_id`),
  KEY `fk_employees_role` (`role_id`),
  CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`),
  CONSTRAINT `fk_employees_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1002 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (1,1,1,'Salem','H','salem.h@company.com',NULL,'Pending',NULL,0.00,0.00,0.00,0.00,NULL,14),(2,2,2,'Tayseer','K','tayseer.k@company.com',NULL,'Pending',NULL,0.00,0.00,0.00,0.00,NULL,14),(3,2,2,'Omar','Abu Saleh','omar.a@company.com',NULL,'Approved','2023-02-10',0.88,400.00,22.00,0.00,NULL,14),(4,3,3,'Lina','Darwish','lina.d@company.com',NULL,'Approved','2023-05-20',0.92,350.00,18.00,0.00,NULL,14),(5,4,4,'Khaled','Nasser','khaled.n@company.com',NULL,'Approved','2023-06-01',0.85,380.00,20.00,0.00,NULL,14),(6,2,2,'Fadi','Masri','fadi.m@company.com',NULL,'Approved','2023-08-15',0.80,350.00,18.00,0.00,NULL,14),(7,3,3,'Rania','Haddad','rania.h@company.com',NULL,'Approved','2024-01-10',0.78,300.00,15.00,0.00,NULL,14),(8,5,5,'Huda','Awad','huda.hr@company.com',NULL,'Approved','2020-01-01',0.91,420.00,20.00,0.00,NULL,14),(9,2,2,'Nabil','Qasem','nabil.q@company.com',NULL,'Pending','2026-03-25',0.00,300.00,15.00,0.00,NULL,14),(10,4,4,'Sara','Younis','sara.y@company.com',NULL,'Pending','2026-04-01',0.00,320.00,16.00,0.00,NULL,14),(11,1,1,'Ahmad','Barakat','ahmad.b@company.com',NULL,'Approved','2021-11-05',0.87,500.00,25.00,0.00,NULL,14),(12,2,2,'Mazen','Tawfiq','mazen.t@company.com',NULL,'Approved','2024-06-15',0.82,350.00,18.00,0.00,NULL,14),(13,7,6,'Tariq','Mansour','tariq.m@company.com',NULL,'Approved','2022-06-01',0.89,400.00,20.00,0.00,NULL,14),(14,6,7,'Yazan','Othman','yazan.it@company.com',NULL,'Approved','2021-09-15',0.93,450.00,22.00,0.00,NULL,14),(15,5,8,'Nour','Sabbagh','nour.fin@company.com',NULL,'Approved','2022-04-10',0.90,430.00,21.00,0.00,NULL,14),(100,1,NULL,'John','Doe','john.doe@1company.com',NULL,'Approved','2022-01-15',0.00,0.00,0.00,1200.00,'SSC-123456',14),(101,2,NULL,'Jane','Smith','jane.smith@2company.com',NULL,'Approved','2023-03-10',0.00,0.00,0.00,950.00,'SSC-654321',14),(102,3,NULL,'Mike','Johnson','mike.johnson@3company.com',NULL,'Pending','2021-08-22',0.00,0.00,0.00,1500.00,'SSC-111222',14),(103,4,NULL,'Sarah','Williams','sarah.williams@4company.com',NULL,'Approved','2024-01-05',0.00,0.00,0.00,850.00,'SSC-333444',14);
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `finance_ledger`
--

DROP TABLE IF EXISTS `finance_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `finance_ledger` (
  `transaction_id` varchar(50) NOT NULL,
  `transaction_date` date NOT NULL,
  `transaction_type` enum('Income','Expense') NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `reference_id` varchar(50) DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `balance_after_transaction` decimal(15,2) DEFAULT NULL,
  PRIMARY KEY (`transaction_id`),
  KEY `recorded_by` (`recorded_by`),
  CONSTRAINT `finance_ledger_ibfk_1` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `finance_ledger`
--

LOCK TABLES `finance_ledger` WRITE;
/*!40000 ALTER TABLE `finance_ledger` DISABLE KEYS */;
INSERT INTO `finance_ledger` VALUES ('BOM-1775846093','2026-04-10','Expense','Production Materials (BOM)',201.79,'SO-260409-5299 (Adam\'s construction)',1,NULL),('BOM-260419-224170','2026-04-19','Expense','Production Materials (BOM)',807.15,'Mix MX-260419-0083 (Artificial Marble Slab 120x60)',1,NULL),('BOM-260419-4ed7b9','2026-04-19','Expense','Production Materials (BOM)',100.18,'Mix MX-260419-3279 (Artificial Granite Tile 60x60)',1,NULL),('BOM-260423-66e2a0','2026-04-23','Expense','Production Materials (BOM)',1008.94,'Mix MX-260419-7714 (Artificial Marble Slab 120x60)',1,NULL),('FIN-260301-002','2026-03-01','Expense','Raw Materials',8500.00,'PO-260301-001',1,NULL),('FIN-260305-002','2026-03-05','Expense','Raw Materials',12300.00,'PO-260305-001',1,NULL),('FIN-260310-002','2026-03-10','Expense','Raw Materials',6700.00,'PO-260310-001',1,NULL),('FIN-260315-001','2026-03-15','Expense','Utilities',1250.00,NULL,1,NULL),('FIN-260320-001','2026-03-20','Expense','Payroll',8500.00,NULL,1,NULL),('FIN-260325-001','2026-03-25','Expense','Equipment',3200.00,NULL,1,NULL),('FIN-260328-001','2026-03-28','Expense','Logistics',950.00,NULL,1,NULL),('FIN-260330-001','2026-03-30','Income','Service Income',2500.00,NULL,1,NULL),('FIN-260402-001','2026-04-02','Expense','Maintenance',780.00,NULL,1,NULL),('FIN-260402-002','2026-04-02','Expense','Marketing',1500.00,NULL,1,NULL),('INC-1775846111','2026-04-10','Income','Sales Revenue',5000.00,'SO-260409-5299 (Adam\'s construction)',1,NULL),('INC-CAPITAL-001','2026-04-18','Income','Initial Capital',25000.00,'Company Investment',1,NULL),('INC-SALES-001','2026-04-18','Income','Sales Revenue',8500.00,'Batch Sales Q1',1,NULL),('INC-SALES-002','2026-04-18','Income','Sales Revenue',6200.00,'Batch Sales Q2',1,NULL),('INC-SALES-003','2026-04-18','Income','Sales Revenue',4800.00,'Export Order March',1,NULL),('REV-260419-75d559','2026-04-19','Income','Sales Revenue',175.00,'Delivery SO-260419-8400 — yazan',1,NULL),('REV-260419-88f472','2026-04-19','Income','Sales Revenue',35.00,'Delivery SO-260418-1736 — TestPipeline Corp',1,NULL),('REV-260419-d425b0','2026-04-19','Income','Sales Revenue',35.00,'Delivery SO-260418-5738 — TestPipeline Corp V2',1,NULL),('REV-260423-34c2ff','2026-04-23','Income','Sales Revenue',135.00,'Delivery SO-260419-5049 — Sabri\'s Consturction',1,NULL);
/*!40000 ALTER TABLE `finance_ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_finished_goods`
--

DROP TABLE IF EXISTS `inventory_finished_goods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_finished_goods` (
  `item_id` varchar(50) NOT NULL,
  `item_name` varchar(150) DEFAULT NULL,
  `stone_measurement` varchar(50) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `quantity_in_stock` int(11) DEFAULT NULL,
  `warehouse_location` varchar(100) DEFAULT NULL,
  `unit_cost` decimal(12,2) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  CONSTRAINT `inventory_finished_goods_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `item_master` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_finished_goods`
--

LOCK TABLES `inventory_finished_goods` WRITE;
/*!40000 ALTER TABLE `inventory_finished_goods` DISABLE KEYS */;
INSERT INTO `inventory_finished_goods` VALUES ('FG-001','Artificial Marble Slab 120x60','120cm x 60cm x 2cm','Marble',51,'Finished Goods Store',45.00),('FG-002','Artificial Granite Tile 60x60','60cm x 60cm x 1.5cm','Granite',30,'Finished Goods Store',35.00),('FG-003','Decorative Stone Panel 100x50','100cm x 50cm x 3cm','Decorative',20,'Finished Goods Store',65.00),('FG-004','Kitchen Countertop Slab 200x60','200cm x 60cm x 3cm','Countertop',0,'Finished Goods Store',120.00),('FG-005','Wall Cladding Tile 30x30','30cm x 30cm x 1cm','Cladding',0,'Finished Goods Store',15.00),('FG-006','Bathroom Vanity Top','90cm x 55cm x 2cm','Vanity',0,'Finished Goods Store',85.00),('FG-C01','تاج 50 (Crown 50)',NULL,'Finished Good',14,NULL,0.00),('FG-C02','تاج 40 (Crown 40)',NULL,'Finished Good',12,NULL,0.00),('FG-C03','قاعده 50 (Base 50)',NULL,'Finished Good',0,NULL,0.00),('FG-C04','قاعده 40 (Base 40)',NULL,'Finished Good',0,NULL,0.00),('FG-COL1','عامود صمدي (Solid Column)',NULL,'Finished Good',14,NULL,0.00),('FG-COL2','عامود مشط (Comb Column 50)',NULL,'Finished Good',7,NULL,0.00),('FG-COL3','عامود مشط 50 (Large Comb)',NULL,'Finished Good',0,NULL,0.00),('FG-COL4','عامود مشط 40 (Med Comb)',NULL,'Finished Good',0,NULL,0.00),('FG-COL5','عامود سادة 40 (Plain Column)',NULL,'Finished Good',0,NULL,0.00),('FG-COL6','عامود سادة 40 (Plain Col 40)',NULL,'Finished Good',0,NULL,0.00),('FG-SC01','كرانيش درج (Stair Cornice)',NULL,'Finished Good',68,NULL,0.00),('FG-SC02','كرانيش قشره (Shell Cornice)',NULL,'Finished Good',55,NULL,0.00),('FG-SC03','كرانيش 25 (Cornice 25)',NULL,'Finished Good',17,NULL,0.00),('FG-SC04','كرانيش 20 (Cornice 20)',NULL,'Finished Good',19,NULL,0.00),('FG-SC05','كرانيش قلب (Heart Cornice)',NULL,'Finished Good',26,NULL,0.00),('FG-SCC1','زوايا 15 (Corner 15)',NULL,'Finished Good',14,NULL,0.00),('FG-SCC3','زوايا 25 (Corner 25)',NULL,'Finished Good',28,NULL,0.00),('FG-SCC4','زوايا 20 (Corner 20)',NULL,'Finished Good',35,NULL,0.00),('FG-ST01','سراميك مطبه (Patterned Tile)',NULL,'Finished Good',148,NULL,0.00),('FG-ST02','سراميك مسمسم (Sesame Tile)',NULL,'Finished Good',117,NULL,0.00),('FG-ST03','سراميك رملي (Sand Tile)',NULL,'Finished Good',90,NULL,0.00),('FG-ST04','سراميك ساده (Plain Tile)',NULL,'Finished Good',35,NULL,0.00),('FG-ST05','سراميك طبّيزه (Tabiza Tile)',NULL,'Finished Good',16,NULL,0.00),('FG-ST06','سراميك مجفر (Textured Tile)',NULL,'Finished Good',30,NULL,0.00),('FG-ST07','سراميك بروازي (Frame Tile)',NULL,'Finished Good',46,NULL,0.00),('FG-STT1','سراميك مطبه فرز (Sorted Patterned)',NULL,'Finished Good',27,NULL,0.00),('FG-STT2','سراميك مسمسم (Sesame Sorted)',NULL,'Finished Good',1,NULL,0.00),('FG-STT3','سراميك رملي فرزه (Sand Sorted)',NULL,'Finished Good',21,NULL,0.00);
/*!40000 ALTER TABLE `inventory_finished_goods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_ledger`
--

DROP TABLE IF EXISTS `inventory_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_ledger` (
  `transaction_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` varchar(50) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `transaction_type` enum('Receipt','Dispatch','Consumed','Produced','Scrap','Adjustment') NOT NULL,
  `quantity_change` decimal(12,3) NOT NULL,
  `reference_id` varchar(50) DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`transaction_id`),
  KEY `item_id` (`item_id`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `recorded_by` (`recorded_by`),
  CONSTRAINT `inventory_ledger_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `item_master` (`item_id`),
  CONSTRAINT `inventory_ledger_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`warehouse_id`),
  CONSTRAINT `inventory_ledger_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=245 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_ledger`
--

LOCK TABLES `inventory_ledger` WRITE;
/*!40000 ALTER TABLE `inventory_ledger` DISABLE KEYS */;
INSERT INTO `inventory_ledger` VALUES (134,'RM-001',1,'Adjustment',2000.000,'Initial Stock',1,'2026-04-10 18:33:09'),(135,'RM-002',1,'Adjustment',2400.000,'Initial Stock',1,'2026-04-10 18:33:09'),(136,'RM-003',1,'Adjustment',5000.000,'Initial Stock',1,'2026-04-10 18:33:10'),(137,'RM-004',1,'Adjustment',3000.000,'Initial Stock',1,'2026-04-10 18:33:10'),(138,'RM-005',1,'Adjustment',1000.000,'Initial Stock',1,'2026-04-10 18:33:10'),(139,'RM-006',1,'Adjustment',200.000,'Initial Stock',1,'2026-04-10 18:33:10'),(140,'RM-007',1,'Adjustment',200.000,'Initial Stock',1,'2026-04-10 18:33:10'),(141,'RM-008',1,'Adjustment',300.000,'Initial Stock',1,'2026-04-10 18:33:10'),(142,'RM-009',1,'Adjustment',500.000,'Initial Stock',1,'2026-04-10 18:33:10'),(143,'RM-010',1,'Adjustment',150.000,'Initial Stock',1,'2026-04-10 18:33:10'),(144,'RM-011',1,'Adjustment',10000.000,'Initial Stock',1,'2026-04-10 18:33:10'),(145,'RM-012',1,'Adjustment',5000.000,'Initial Stock',1,'2026-04-10 18:33:10'),(146,'RM-013',1,'Adjustment',50.000,'Initial Stock',1,'2026-04-10 18:33:10'),(147,'RM-014',1,'Adjustment',100.000,'Initial Stock',1,'2026-04-10 18:33:10'),(148,'FG-001',1,'Adjustment',50.000,'Initial Stock',1,'2026-04-10 18:33:10'),(149,'FG-002',1,'Adjustment',30.000,'Initial Stock',1,'2026-04-10 18:33:10'),(150,'FG-003',1,'Adjustment',20.000,'Initial Stock',1,'2026-04-10 18:33:10'),(151,'FG-ST01',1,'Adjustment',100.000,'Initial Stock',1,'2026-04-10 18:33:10'),(152,'FG-ST02',1,'Adjustment',80.000,'Initial Stock',1,'2026-04-10 18:33:10'),(153,'FG-ST03',1,'Adjustment',60.000,'Initial Stock',1,'2026-04-10 18:33:10'),(154,'FG-SC01',1,'Adjustment',40.000,'Initial Stock',1,'2026-04-10 18:33:10'),(155,'FG-SC02',1,'Adjustment',35.000,'Initial Stock',1,'2026-04-10 18:33:10'),(156,'FG-COL1',1,'Adjustment',10.000,'Initial Stock',1,'2026-04-10 18:33:10'),(157,'RM-001',1,'Consumed',-50.000,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:52'),(158,'RM-004',1,'Consumed',-120.000,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:52'),(159,'RM-011',1,'Consumed',-90.000,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:53'),(160,'RM-012',1,'Consumed',-21.000,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:53'),(161,'RM-013',1,'Consumed',-0.270,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:53'),(162,'RM-014',1,'Consumed',-0.065,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:53'),(163,'FG-001',1,'Produced',1.000,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:53'),(164,'FG-ST01',1,'Produced',48.000,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:53'),(165,'FG-STT1',1,'Produced',27.000,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:53'),(166,'FG-ST02',1,'Produced',37.000,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:53'),(167,'FG-STT2',1,'Produced',1.000,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:53'),(168,'FG-ST03',1,'Produced',30.000,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:53'),(169,'FG-STT3',1,'Produced',21.000,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:53'),(170,'FG-ST04',1,'Produced',35.000,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:53'),(171,'FG-ST05',1,'Produced',16.000,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:53'),(172,'FG-ST06',1,'Produced',30.000,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:53'),(173,'FG-ST07',1,'Produced',46.000,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:53'),(174,'FG-SC01',1,'Produced',28.000,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:53'),(175,'FG-SC02',1,'Produced',20.000,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:53'),(176,'FG-SC03',1,'Produced',17.000,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:53'),(177,'FG-SC04',1,'Produced',19.000,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:53'),(178,'FG-SC05',1,'Produced',26.000,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:53'),(179,'FG-SCC1',1,'Produced',14.000,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:53'),(180,'FG-SCC3',1,'Produced',28.000,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:53'),(181,'FG-SCC4',1,'Produced',35.000,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:53'),(182,'FG-COL1',1,'Produced',4.000,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:53'),(183,'FG-COL2',1,'Produced',7.000,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:53'),(184,'FG-C01',1,'Produced',14.000,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:53'),(185,'FG-C02',1,'Produced',12.000,'SO-260409-5299 (Adam\'s construction)',1,'2026-04-10 18:34:53'),(186,'RM-001',1,'Receipt',50.000,'INIT-STOCK-FIX',1,'2026-04-18 20:33:33'),(187,'RM-005',1,'Receipt',1000.000,'INIT-STOCK-FIX',1,'2026-04-18 20:33:33'),(188,'RM-006',1,'Receipt',1800.000,'INIT-STOCK-FIX',1,'2026-04-18 20:33:33'),(189,'RM-007',1,'Receipt',1800.000,'INIT-STOCK-FIX',1,'2026-04-18 20:33:33'),(190,'RM-008',1,'Receipt',1700.000,'INIT-STOCK-FIX',1,'2026-04-18 20:33:33'),(191,'RM-009',1,'Receipt',1500.000,'INIT-STOCK-FIX',1,'2026-04-18 20:33:33'),(192,'RM-010',1,'Receipt',1850.000,'INIT-STOCK-FIX',1,'2026-04-18 20:33:33'),(193,'RM-013',1,'Receipt',1950.270,'INIT-STOCK-FIX',1,'2026-04-18 20:33:33'),(194,'RM-014',1,'Receipt',1900.065,'INIT-STOCK-FIX',1,'2026-04-18 20:33:33'),(195,'RM-001',1,'Adjustment',3000.000,'Initial Stock Fill',1,'2026-04-18 21:20:55'),(196,'RM-002',1,'Adjustment',2600.000,'Initial Stock Fill',1,'2026-04-18 21:20:55'),(197,'RM-004',1,'Adjustment',2120.000,'Initial Stock Fill',1,'2026-04-18 21:20:55'),(198,'RM-005',1,'Adjustment',3000.000,'Initial Stock Fill',1,'2026-04-18 21:20:55'),(199,'RM-006',1,'Adjustment',3000.000,'Initial Stock Fill',1,'2026-04-18 21:20:55'),(200,'RM-007',1,'Adjustment',3000.000,'Initial Stock Fill',1,'2026-04-18 21:20:55'),(201,'RM-008',1,'Adjustment',3000.000,'Initial Stock Fill',1,'2026-04-18 21:20:55'),(202,'RM-009',1,'Adjustment',3000.000,'Initial Stock Fill',1,'2026-04-18 21:20:55'),(203,'RM-010',1,'Adjustment',3000.000,'Initial Stock Fill',1,'2026-04-18 21:20:55'),(204,'RM-012',1,'Adjustment',21.000,'Initial Stock Fill',1,'2026-04-18 21:20:55'),(205,'RM-013',1,'Adjustment',3000.000,'Initial Stock Fill',1,'2026-04-18 21:20:55'),(206,'RM-014',1,'Adjustment',3000.000,'Initial Stock Fill',1,'2026-04-18 21:20:55'),(207,'FG-002',3,'Dispatch',-1.000,'Delivery: SO-260418-1736',1,'2026-04-19 06:27:32'),(208,'FG-002',3,'Dispatch',-1.000,'Delivery: SO-260418-5738',1,'2026-04-19 06:27:38'),(209,'RM-002',1,'Consumed',-60.000,'Mix MX-260419-3279 (Artificial Granite Tile 60x60)',1,'2026-04-19 07:18:26'),(210,'RM-004',1,'Consumed',-40.000,'Mix MX-260419-3279 (Artificial Granite Tile 60x60)',1,'2026-04-19 07:18:26'),(211,'RM-006',1,'Consumed',-2.000,'Mix MX-260419-3279 (Artificial Granite Tile 60x60)',1,'2026-04-19 07:18:26'),(212,'RM-012',1,'Consumed',-18.000,'Mix MX-260419-3279 (Artificial Granite Tile 60x60)',1,'2026-04-19 07:18:26'),(213,'RM-001',1,'Consumed',-200.000,'Mix MX-260419-0083 (Artificial Marble Slab 120x60)',1,'2026-04-19 07:25:28'),(214,'RM-004',1,'Consumed',-480.000,'Mix MX-260419-0083 (Artificial Marble Slab 120x60)',1,'2026-04-19 07:25:28'),(215,'RM-011',1,'Consumed',-360.000,'Mix MX-260419-0083 (Artificial Marble Slab 120x60)',1,'2026-04-19 07:25:28'),(216,'RM-012',1,'Consumed',-84.000,'Mix MX-260419-0083 (Artificial Marble Slab 120x60)',1,'2026-04-19 07:25:28'),(217,'RM-013',1,'Consumed',-1.080,'Mix MX-260419-0083 (Artificial Marble Slab 120x60)',1,'2026-04-19 07:25:28'),(218,'RM-014',1,'Consumed',-0.260,'Mix MX-260419-0083 (Artificial Marble Slab 120x60)',1,'2026-04-19 07:25:28'),(219,'FG-001',1,'Produced',28.000,'Mix MX-260419-0083 (Artificial Marble Slab 120x60)',1,'2026-04-19 07:42:02'),(220,'FG-004',1,'Produced',20.000,'Mix MX-260419-0083 (Artificial Marble Slab 120x60)',1,'2026-04-19 07:42:02'),(221,'FG-SC03',1,'Produced',16.000,'Mix MX-260419-0083 (Artificial Marble Slab 120x60)',1,'2026-04-19 07:42:02'),(222,'FG-ST01',1,'Produced',40.000,'Mix MX-260419-0083 (Artificial Marble Slab 120x60)',1,'2026-04-19 07:42:02'),(223,'FG-ST03',1,'Produced',24.000,'Mix MX-260419-0083 (Artificial Marble Slab 120x60)',1,'2026-04-19 07:42:02'),(224,'FG-ST04',1,'Produced',32.000,'Mix MX-260419-0083 (Artificial Marble Slab 120x60)',1,'2026-04-19 07:42:02'),(225,'FG-002',1,'Produced',13.000,'Mix MX-260419-3279 (Artificial Granite Tile 60x60)',1,'2026-04-19 07:42:17'),(226,'FG-005',1,'Produced',4.000,'Mix MX-260419-3279 (Artificial Granite Tile 60x60)',1,'2026-04-19 07:42:17'),(227,'FG-SC04',1,'Produced',3.000,'Mix MX-260419-3279 (Artificial Granite Tile 60x60)',1,'2026-04-19 07:42:17'),(228,'FG-SCC1',1,'Produced',5.000,'Mix MX-260419-3279 (Artificial Granite Tile 60x60)',1,'2026-04-19 07:42:17'),(229,'FG-ST02',1,'Produced',6.000,'Mix MX-260419-3279 (Artificial Granite Tile 60x60)',1,'2026-04-19 07:42:17'),(230,'FG-ST05',1,'Produced',7.000,'Mix MX-260419-3279 (Artificial Granite Tile 60x60)',1,'2026-04-19 07:42:17'),(231,'FG-002',3,'Dispatch',-5.000,'Delivery: SO-260419-8400',1,'2026-04-19 09:44:11'),(232,'RM-001',1,'Consumed',-250.000,'Mix MX-260419-7714 (Artificial Marble Slab 120x60)',1,'2026-04-23 10:00:01'),(233,'RM-004',1,'Consumed',-600.000,'Mix MX-260419-7714 (Artificial Marble Slab 120x60)',1,'2026-04-23 10:00:01'),(234,'RM-011',1,'Consumed',-450.000,'Mix MX-260419-7714 (Artificial Marble Slab 120x60)',1,'2026-04-23 10:00:01'),(235,'RM-012',1,'Consumed',-105.000,'Mix MX-260419-7714 (Artificial Marble Slab 120x60)',1,'2026-04-23 10:00:01'),(236,'RM-013',1,'Consumed',-1.350,'Mix MX-260419-7714 (Artificial Marble Slab 120x60)',1,'2026-04-23 10:00:01'),(237,'RM-014',1,'Consumed',-0.325,'Mix MX-260419-7714 (Artificial Marble Slab 120x60)',1,'2026-04-23 10:00:01'),(238,'FG-001',1,'Produced',35.000,'Mix MX-260419-7714 (Artificial Marble Slab 120x60)',1,'2026-04-23 10:01:14'),(239,'FG-004',1,'Produced',25.000,'Mix MX-260419-7714 (Artificial Marble Slab 120x60)',1,'2026-04-23 10:01:14'),(240,'FG-SC03',1,'Produced',20.000,'Mix MX-260419-7714 (Artificial Marble Slab 120x60)',1,'2026-04-23 10:01:14'),(241,'FG-ST01',1,'Produced',50.000,'Mix MX-260419-7714 (Artificial Marble Slab 120x60)',1,'2026-04-23 10:01:14'),(242,'FG-ST03',1,'Produced',30.000,'Mix MX-260419-7714 (Artificial Marble Slab 120x60)',1,'2026-04-23 10:01:14'),(243,'FG-ST04',1,'Produced',40.000,'Mix MX-260419-7714 (Artificial Marble Slab 120x60)',1,'2026-04-23 10:01:14'),(244,'FG-001',3,'Dispatch',-3.000,'Delivery: SO-260419-5049',1,'2026-04-23 10:01:48');
/*!40000 ALTER TABLE `inventory_ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices_jo`
--

DROP TABLE IF EXISTS `invoices_jo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoices_jo` (
  `invoice_id` varchar(50) NOT NULL,
  `so_id` varchar(50) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `qr_code` text DEFAULT NULL,
  `submission_status` varchar(20) DEFAULT 'Pending',
  `istd_ref` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices_jo`
--

LOCK TABLES `invoices_jo` WRITE;
/*!40000 ALTER TABLE `invoices_jo` DISABLE KEYS */;
INSERT INTO `invoices_jo` VALUES ('INV-20260410-83cc75','SO-260409-5299','{\"InvoiceNumber\":\"INV-20260410-83cc75\",\"IssueDate\":\"2026-04-10\",\"SellerTaxID\":\"JO-PENDING-TAX-ID\",\"BuyerName\":\"Adam\'s construction\",\"BuyerEmail\":\"adam@gmail.com\",\"TotalAmount\":\"5000.00\",\"TaxRate\":0.16,\"TaxAmount\":800,\"GrandTotal\":5800,\"Currency\":\"JOD\",\"PaymentMethod\":\"Credit Card\",\"ReferenceOrderID\":\"SO-260409-5299\"}','AT1NaXNrU3RvbmUgLSDZhdiz2YMg2YTZhNit2KzYsSDYp9mE2LXZhtin2LnZiiDZiNin2YTYr9mK2YPZiNixAhFKTy1QRU5ESU5HLVRBWC1JRAMKMjAyNi0wNC0xMAQHNTgwMC4wMAUGODAwLjAwBkBkNTk0YmVmOTM1NmMyM2U1YmEwYzQ5OGI4ZWFiOThlOGNmZTkzNTM2ZjZmM2M1MDhlYjFhZjNhMTczMTY5MWI3','Submitted','SIM-20260410203511-6682','2026-04-10 18:35:11');
/*!40000 ALTER TABLE `invoices_jo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `item_master`
--

DROP TABLE IF EXISTS `item_master`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `item_master` (
  `item_id` varchar(50) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `category` enum('Raw Material','Finished Good','Equipment','Consumable') NOT NULL,
  `dimensions` varchar(20) DEFAULT NULL,
  `base_uom` varchar(20) NOT NULL,
  `standard_cost` decimal(12,2) DEFAULT 0.00,
  `min_stock_level` decimal(12,3) DEFAULT 0.000,
  PRIMARY KEY (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `item_master`
--

LOCK TABLES `item_master` WRITE;
/*!40000 ALTER TABLE `item_master` DISABLE KEYS */;
INSERT INTO `item_master` VALUES ('FG-001','Artificial Marble Slab 120x60','Finished Good',NULL,'pcs',45.00,20.000),('FG-002','Artificial Granite Tile 60x60','Finished Good',NULL,'pcs',35.00,25.000),('FG-003','Decorative Stone Panel 100x50','Finished Good',NULL,'pcs',65.00,15.000),('FG-004','Kitchen Countertop Slab 200x60','Finished Good',NULL,'pcs',120.00,10.000),('FG-005','Wall Cladding Tile 30x30','Finished Good',NULL,'pcs',15.00,50.000),('FG-006','Bathroom Vanity Top','Finished Good',NULL,'pcs',85.00,8.000),('FG-C01','تاج 50 (Crown 50)','Finished Good','50x60','',20.00,0.000),('FG-C02','تاج 40 (Crown 40)','Finished Good','40x60','',18.00,0.000),('FG-C03','قاعده 50 (Base 50)','Finished Good','50x60','',20.00,0.000),('FG-C04','قاعده 40 (Base 40)','Finished Good','40x60','',18.00,0.000),('FG-COL1','عامود صمدي (Solid Column)','Finished Good','50x50','',25.00,0.000),('FG-COL2','عامود مشط (Comb Column 50)','Finished Good','50x100','',30.00,0.000),('FG-COL3','عامود مشط 50 (Large Comb)','Finished Good','50x120','',35.00,0.000),('FG-COL4','عامود مشط 40 (Med Comb)','Finished Good','40x120','',32.00,0.000),('FG-COL5','عامود سادة 40 (Plain Column)','Finished Good','50x120','',28.00,0.000),('FG-COL6','عامود سادة 40 (Plain Col 40)','Finished Good','40x120','',26.00,0.000),('FG-SC01','كرانيش درج (Stair Cornice)','Finished Good','15x100','',12.00,0.000),('FG-SC02','كرانيش قشره (Shell Cornice)','Finished Good','15x100','',12.00,0.000),('FG-SC03','كرانيش 25 (Cornice 25)','Finished Good','25x100','',14.00,0.000),('FG-SC04','كرانيش 20 (Cornice 20)','Finished Good','20x100','',13.00,0.000),('FG-SC05','كرانيش قلب (Heart Cornice)','Finished Good','40x70','',18.00,0.000),('FG-SCC1','زوايا 15 (Corner 15)','Finished Good','15x30','',6.00,0.000),('FG-SCC3','زوايا 25 (Corner 25)','Finished Good','25x50','',8.00,0.000),('FG-SCC4','زوايا 20 (Corner 20)','Finished Good','20x30','',7.00,0.000),('FG-ST01','سراميك مطبه (Patterned Tile)','Finished Good','75x51','',8.50,0.000),('FG-ST02','سراميك مسمسم (Sesame Tile)','Finished Good','75x51','',8.50,0.000),('FG-ST03','سراميك رملي (Sand Tile)','Finished Good','75x51','',8.50,0.000),('FG-ST04','سراميك ساده (Plain Tile)','Finished Good','75x51','',8.00,0.000),('FG-ST05','سراميك طبّيزه (Tabiza Tile)','Finished Good','12x57','',5.00,0.000),('FG-ST06','سراميك مجفر (Textured Tile)','Finished Good','25x60','',7.00,0.000),('FG-ST07','سراميك بروازي (Frame Tile)','Finished Good','25x60','',7.00,0.000),('FG-STT1','سراميك مطبه فرز (Sorted Patterned)','Finished Good','75x51','',9.00,0.000),('FG-STT2','سراميك مسمسم (Sesame Sorted)','Finished Good','75x51','',9.00,0.000),('FG-STT3','سراميك رملي فرزه (Sand Sorted)','Finished Good','75x51','',9.00,0.000),('RM-001','White Cement','Raw Material',NULL,'kg',0.85,500.000),('RM-002','Grey Cement','Raw Material',NULL,'kg',0.60,800.000),('RM-003','Crushed Limestone','Raw Material',NULL,'kg',0.25,1000.000),('RM-004','Quartz Aggregate','Raw Material',NULL,'kg',1.20,300.000),('RM-005','Marble Chips','Raw Material',NULL,'kg',2.50,200.000),('RM-006','Iron Oxide Pigment (Red)','Raw Material',NULL,'kg',8.00,50.000),('RM-007','Iron Oxide Pigment (Yellow)','Raw Material',NULL,'kg',7.50,50.000),('RM-008','Polyester Resin','Raw Material',NULL,'litre',12.00,100.000),('RM-009','Fiberglass Mesh','Raw Material',NULL,'sqm',3.50,150.000),('RM-010','Silicon Sealant','Raw Material',NULL,'tube',5.00,80.000),('RM-011','Sand (رمل)','Raw Material',NULL,'',0.15,0.000),('RM-012','Water (ماء)','Raw Material',NULL,'',0.01,0.000),('RM-013','SMF Additive','Raw Material',NULL,'',5.00,0.000),('RM-014','Pigment Mix (صبغ)','Raw Material',NULL,'',3.50,0.000);
/*!40000 ALTER TABLE `item_master` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `journal_entries`
--

DROP TABLE IF EXISTS `journal_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `journal_entries` (
  `journal_id` int(11) NOT NULL AUTO_INCREMENT,
  `entry_date` date NOT NULL,
  `source_module` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `reference_id` varchar(50) DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  PRIMARY KEY (`journal_id`),
  KEY `recorded_by` (`recorded_by`),
  CONSTRAINT `journal_entries_ibfk_1` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `journal_entries`
--

LOCK TABLES `journal_entries` WRITE;
/*!40000 ALTER TABLE `journal_entries` DISABLE KEYS */;
INSERT INTO `journal_entries` VALUES (1,'2026-04-10','Production','BOM Cost Estimate Report | Recipe: Marble Slab Standard Mix | Qty: 100 | Est. Total Cost: $1,975.00','EST-1',10),(2,'2026-04-10','Production','BOM Cost Estimate Report | Recipe: Marble Slab Standard Mix | Qty: 100 | Est. Total Cost: $1,975.00','EST-1',10),(3,'2026-04-10','Production','BOM Cost Estimate Report | Recipe: Granite Tile Mix | Qty: 10 | Est. Total Cost: $66.67','EST-2',1),(4,'2026-04-10','Production','BOM Cost Estimate Report | Recipe: Standard Artificial Stone Mix (خلطة حجر صناعي) | Qty: 100 | Est. Total Cost: $20,178.75','EST-1',1);
/*!40000 ALTER TABLE `journal_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `journal_lines`
--

DROP TABLE IF EXISTS `journal_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `journal_lines` (
  `line_id` int(11) NOT NULL AUTO_INCREMENT,
  `journal_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `direction` enum('Debit','Credit') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  PRIMARY KEY (`line_id`),
  KEY `journal_id` (`journal_id`),
  KEY `account_id` (`account_id`),
  CONSTRAINT `journal_lines_ibfk_1` FOREIGN KEY (`journal_id`) REFERENCES `journal_entries` (`journal_id`) ON DELETE CASCADE,
  CONSTRAINT `journal_lines_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `journal_lines`
--

LOCK TABLES `journal_lines` WRITE;
/*!40000 ALTER TABLE `journal_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `journal_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_requests`
--

DROP TABLE IF EXISTS `leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `leave_type` enum('Annual','Sick','Maternity','Paternity','Bereavement') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `reason` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_leave_employee_id` (`employee_id`),
  CONSTRAINT `fk_leave_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_requests`
--

LOCK TABLES `leave_requests` WRITE;
/*!40000 ALTER TABLE `leave_requests` DISABLE KEYS */;
INSERT INTO `leave_requests` VALUES (1,1,'Paternity','2026-04-12','2026-04-16','Approved','Generated mock reason 199'),(2,1,'Annual','2026-05-09','2026-05-19','Pending','Generated mock reason 1939'),(3,2,'Bereavement','2026-04-11','2026-04-21','Approved','Generated mock reason 247'),(4,2,'Paternity','2026-05-11','2026-05-19','Pending','Generated mock reason 2000'),(5,100,'Sick','2026-04-06','2026-04-13','Rejected','Generated mock reason 604'),(6,100,'Sick','2026-05-07','2026-05-16','Pending','Generated mock reason 1550'),(7,101,'Bereavement','2026-04-10','2026-04-20','Pending','Generated mock reason 878'),(8,101,'Sick','2026-05-05','2026-05-22','Pending','Generated mock reason 1844'),(9,102,'Paternity','2026-04-04','2026-04-20','Rejected','Generated mock reason 255'),(10,102,'Bereavement','2026-05-08','2026-05-14','Pending','Generated mock reason 1344'),(11,103,'Bereavement','2026-04-04','2026-04-16','Pending','Generated mock reason 844');
/*!40000 ALTER TABLE `leave_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_tickets`
--

DROP TABLE IF EXISTS `leave_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_tickets` (
  `ticket_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `manager_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `hr_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ticket_id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `leave_tickets_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_tickets`
--

LOCK TABLES `leave_tickets` WRITE;
/*!40000 ALTER TABLE `leave_tickets` DISABLE KEYS */;
INSERT INTO `leave_tickets` VALUES (1,3,'2026-04-05','2026-04-07','Family emergency — need 3 days off','Approved','Approved','2026-04-02 14:49:11'),(2,4,'2026-04-10','2026-04-12','Annual vacation — visiting family abroad','Approved','Pending','2026-04-02 14:49:11'),(3,6,'2026-04-08','2026-04-08','Medical appointment','Pending','Pending','2026-04-02 14:49:11'),(4,7,'2026-04-15','2026-04-18','Personal travel','Rejected','Pending','2026-04-02 14:49:11'),(5,12,'2026-04-20','2026-04-22','Wedding celebration','Pending','Pending','2026-04-02 14:49:11');
/*!40000 ALTER TABLE `leave_tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `material_requests`
--

DROP TABLE IF EXISTS `material_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `material_requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `requested_by` int(11) NOT NULL,
  `item_id` varchar(50) NOT NULL,
  `quantity_requested` decimal(12,3) NOT NULL,
  `unit_price` decimal(12,2) DEFAULT 0.00,
  `reason` text DEFAULT NULL,
  `urgency` enum('Normal','High','Critical') DEFAULT 'Normal',
  `status` enum('Requested','Accepted','Declined') DEFAULT 'Requested',
  `handled_by` int(11) DEFAULT NULL,
  `decline_reason` text DEFAULT NULL,
  `po_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`request_id`),
  KEY `requested_by` (`requested_by`),
  KEY `item_id` (`item_id`),
  KEY `handled_by` (`handled_by`),
  CONSTRAINT `material_requests_ibfk_1` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `material_requests_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `item_master` (`item_id`),
  CONSTRAINT `material_requests_ibfk_3` FOREIGN KEY (`handled_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `material_requests`
--

LOCK TABLES `material_requests` WRITE;
/*!40000 ALTER TABLE `material_requests` DISABLE KEYS */;
INSERT INTO `material_requests` VALUES (1,1,'RM-003',500.000,0.25,'','Normal','Requested',NULL,NULL,NULL,'2026-04-19 08:11:57',NULL);
/*!40000 ALTER TABLE `material_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mix_outputs`
--

DROP TABLE IF EXISTS `mix_outputs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mix_outputs` (
  `output_id` int(11) NOT NULL AUTO_INCREMENT,
  `recipe_id` int(11) NOT NULL,
  `item_id` varchar(50) NOT NULL,
  `output_quantity` decimal(12,3) NOT NULL,
  PRIMARY KEY (`output_id`),
  UNIQUE KEY `unique_recipe_item` (`recipe_id`,`item_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `mix_outputs_ibfk_1` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`recipe_id`) ON DELETE CASCADE,
  CONSTRAINT `mix_outputs_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `item_master` (`item_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mix_outputs`
--

LOCK TABLES `mix_outputs` WRITE;
/*!40000 ALTER TABLE `mix_outputs` DISABLE KEYS */;
INSERT INTO `mix_outputs` VALUES (1,1,'FG-001',7.000),(2,1,'FG-004',5.000),(3,1,'FG-ST01',10.000),(4,1,'FG-ST04',8.000),(5,1,'FG-ST03',6.000),(6,1,'FG-SC03',4.000),(7,2,'FG-002',13.000),(8,2,'FG-005',4.000),(9,2,'FG-ST02',6.000),(10,2,'FG-SCC1',5.000),(11,2,'FG-ST05',7.000),(12,2,'FG-SC04',3.000),(13,3,'FG-003',6.000),(14,3,'FG-006',3.000),(15,3,'FG-SC01',8.000),(16,3,'FG-COL1',5.000),(17,3,'FG-C01',4.000),(18,3,'FG-C03',4.000);
/*!40000 ALTER TABLE `mix_outputs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `otp_logs`
--

DROP TABLE IF EXISTS `otp_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `otp_logs` (
  `otp_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_used` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`otp_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `otp_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `otp_logs`
--

LOCK TABLES `otp_logs` WRITE;
/*!40000 ALTER TABLE `otp_logs` DISABLE KEYS */;
INSERT INTO `otp_logs` VALUES (1,4,'$2y$10$d3rKqrhs8BcRryUH30enVuXcBZiX4cDnkOBLiIzMln5hSULE5974y','2026-04-08 17:47:48',1),(2,3,'$2y$10$4QByXksSe3aRGlPlzufgk.Cblp/H93afZQyLyQUu9vNhSc/8tBjF2','2026-04-08 17:49:03',1),(3,5,'$2y$10$Jh/ozSyX.Seq6jjkIKM9LeHGu/T4J2z4WYIkw9dps7vWbZKWoepwC','2026-04-08 17:51:05',1),(4,3,'$2y$10$LSp5vRdaYerYh7hFoCg2P.Bny.d9FRbiczjxfaKaDk63aAymKjUCW','2026-04-08 18:05:51',1),(5,4,'$2y$10$9rPPBuXGVwsRj2b0dTq1ieFYShnZdI.m/7kEq4TsewyhCU7hdLB9K','2026-04-08 18:08:25',1),(6,5,'$2y$10$0YWMqQodzIwE4PYquZLFf.cGWxccDmEqglmcS6bpzJaC3XCzudmrq','2026-04-08 18:14:14',1),(7,3,'$2y$10$SFam9Av3mhH9faHRljxgk.5jKW5bzmP4SimOBgKFOYO5x3WoH4jue','2026-04-08 18:49:11',1),(8,4,'$2y$10$xc4FzAV6rEshwpqMe3xleeqmhJQEwYRn1cDmquPYVflrlibi1sPia','2026-04-08 18:50:02',1),(9,3,'$2y$10$K8xo.M3Qy8bm.CZWIJu3.uH4Q0S4of8fNKf9FOHFicQVWNWcKBWTG','2026-04-08 19:11:06',1),(10,4,'$2y$10$jYtJuebMEEE6ov0Gia8V4uTh2KltQcY4dZyv4s.fZ9K8MaIs7EYF.','2026-04-08 19:15:27',1),(11,3,'$2y$10$6RYM8qrwrS.1DG.s1j1.4O5Nv9h31Wxsij.J4AR8qeTSOF8BKt.Vy','2026-04-08 19:22:48',1),(12,4,'$2y$10$xlftmJCMp1FAA9w6tVFAZ.qRYoQ6a4vlMX6F35QHF/pcEhOoNKTIG','2026-04-08 19:28:44',1),(13,3,'$2y$10$Y7OF7g5xAAZTAdVR9qeo5.4QdwfZmq6cdwXr3.T6ILojOYsCr/MSK','2026-04-08 19:33:52',1),(14,4,'$2y$10$yUJNstXRrv6in45Qxyz1EOOoIjDzYHbvSv0sO7Hr93mFVMqGBu4bK','2026-04-09 05:59:23',1),(15,3,'$2y$10$dKWoUvwS9bOrDUWtXt5sle1DRi0kOCOSC6U2sHwAdqXv8L.hGWiJK','2026-04-09 06:02:50',1),(16,4,'$2y$10$VlQ5OfbgyzY1MEoa43VNKuAewttBU6.swtlH3n1MHoMqsnomOG9EC','2026-04-09 06:16:57',1),(17,3,'$2y$10$l1QJ9cPM7UGtHEpn/BDg9eoI631vRWQswkpx1DzIIsvIZ617gCIGy','2026-04-09 06:18:21',1),(18,4,'$2y$10$xayyKsYIUBgT6HMssdCvpe2jfMC8buUA3xA7MuYvZYjqH5XfSjUHe','2026-04-09 08:12:38',1),(19,3,'$2y$10$YfUWd37Y0UdLXXeclr9ZYe90MQy/EKgpWYp84budpVi0mrD9WBDqa','2026-04-09 08:29:39',1),(20,4,'$2y$10$cK80H0mqA.X6hgDCx3qi9e2K1tT14mriFrzyCMELqzTncsT9WM3Zu','2026-04-09 09:06:21',1),(21,3,'$2y$10$W/k4MY.FudjW61bIdzk0n.v8HmqgvwA30LdCnA972tcAXG1UskvNS','2026-04-09 09:15:29',1),(22,4,'$2y$10$0s01sQsKIfYHzeRL8wo4xe8nRV8.h4JBMp3X5XIFtFKpFc9P0kMr6','2026-04-09 09:42:48',1),(23,3,'$2y$10$k.gZS4Rd9iQFoDVyhwwepeziEuvF/dgG00GqICJy0QSH7WuwdGLwC','2026-04-09 09:43:53',1),(24,10,'$2y$10$qdOwPBkdl8sCjSlO.709du6wmfO.O/J12.QPePhxfSYfApdAAeoB2','2026-04-10 08:57:04',1),(25,10,'$2y$10$hRmtb5aroK6o7FGUEKt3reMqYRa1jp.sKggGJzsgSfDaU2ENBe8Ei','2026-04-10 09:02:36',1),(26,10,'$2y$10$9pw8hB7InwMi9SCicaCjPODBdAiQhqh3F7.3sP6QOp4ispf1ibLKm','2026-04-10 09:21:39',1),(27,11,'$2y$10$6T9JZOP0GSf.kdhQ1br2G.A1kdejAIrsGoMk6yPco.bwCxJtVIFsy','2026-04-10 09:32:06',1),(28,11,'$2y$10$O25R/K3tbPsHF1dNJsOPbu03bq2hVHUnyArp514Ts1y1/53FRqny.','2026-04-10 09:35:14',1),(29,3,'$2y$10$XRRQIXoRQg3N.nrbQ3YIKeMz02ovszcV3qwnoXF6/gxAxIpps6s7i','2026-04-10 17:04:48',1),(30,3,'$2y$10$mpPg4HIOqXWRVa0sCr8L/OXgYUCjarqabvOdbrwt8B33XaEN5Fdle','2026-04-12 09:22:15',1),(31,4,'$2y$10$NdhfnhMNpiFbavlF9LqeeePiRW.eRj1KLDXKkUXM2PCYZ6P144giS','2026-04-12 10:34:37',1),(32,4,'$2y$10$zgSdI88gF.wZRW4mSixkX.o6gQtW6dUUn.YZxwYKF9aeGxAq0rtvy','2026-04-14 16:48:38',1),(33,4,'$2y$10$sA6//Tqoe/74T4xiVpnJgucThUERPCTAmOGPFfvsWYPXTzPi/aFaS','2026-04-14 17:43:09',1),(34,4,'$2y$10$LNEIBxKeAc1ijGqVBqwX3eYJ7BUuzqhiOMNifITNBcTL/H/DyVjeu','2026-04-18 19:25:04',1);
/*!40000 ALTER TABLE `otp_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `permission_id` int(11) NOT NULL AUTO_INCREMENT,
  `module_access` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`permission_id`),
  UNIQUE KEY `module_access` (`module_access`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'dashboard','Dashboard access'),(2,'auth','Authentication & user management'),(3,'hr','Human Resources module'),(4,'manufacturing','Production & batch management'),(5,'inventory','Inventory & warehouse'),(6,'procurement','Purchase orders & suppliers'),(7,'finance','Accounting & ledger'),(8,'crm','Sales pipeline & customers'),(9,'admin','Access to User Account Management panel');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `po_lines`
--

DROP TABLE IF EXISTS `po_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `po_lines` (
  `line_id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` varchar(50) NOT NULL,
  `item_id` varchar(50) NOT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(15,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  PRIMARY KEY (`line_id`),
  KEY `idx_po` (`po_id`),
  KEY `idx_item` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `po_lines`
--

LOCK TABLES `po_lines` WRITE;
/*!40000 ALTER TABLE `po_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `po_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `production_orders`
--

DROP TABLE IF EXISTS `production_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `production_orders` (
  `production_id` varchar(50) NOT NULL,
  `item_id` varchar(50) NOT NULL,
  `recipe_id` int(11) DEFAULT NULL,
  `so_id` varchar(50) DEFAULT NULL,
  `machine_id` varchar(20) DEFAULT NULL,
  `production_date` date DEFAULT NULL,
  `target_quantity` decimal(12,3) NOT NULL,
  `actual_yield` decimal(12,3) DEFAULT 0.000,
  `status` enum('Planned','Mixing','Curing','Completed','Failed') DEFAULT 'Planned',
  `qa_status` enum('Pending','Passed','Failed','Rework') DEFAULT 'Pending',
  `operator_user_id` int(11) NOT NULL,
  `inspected_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`production_id`),
  KEY `item_id` (`item_id`),
  KEY `operator_user_id` (`operator_user_id`),
  KEY `inspected_by` (`inspected_by`),
  CONSTRAINT `production_orders_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `item_master` (`item_id`),
  CONSTRAINT `production_orders_ibfk_2` FOREIGN KEY (`operator_user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `production_orders_ibfk_3` FOREIGN KEY (`inspected_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `production_orders`
--

LOCK TABLES `production_orders` WRITE;
/*!40000 ALTER TABLE `production_orders` DISABLE KEYS */;
INSERT INTO `production_orders` VALUES ('MX-20260410-908','FG-001',NULL,NULL,NULL,NULL,1.000,1.000,'Completed','Passed',1,NULL,'2026-04-10 18:34:21'),('MX-260419-0083','FG-001',1,NULL,'','2026-04-19',4.000,0.000,'Completed','Pending',3,NULL,'2026-04-19 07:25:14'),('MX-260419-3279','FG-002',2,NULL,'','2026-04-19',1.000,1.000,'Completed','Pending',8,NULL,'2026-04-19 07:18:07'),('MX-260419-7714','FG-001',1,NULL,'','2026-04-19',5.000,0.000,'Completed','Pending',7,NULL,'2026-04-19 09:45:54'),('PROD-260315-001','FG-001',NULL,NULL,'PRESS-01','2026-03-15',100.000,100.000,'Completed','Passed',3,1,'2026-04-02 14:49:10'),('PROD-260318-001','FG-002',NULL,NULL,'PRESS-02','2026-03-18',150.000,150.000,'Completed','Passed',3,1,'2026-04-02 14:49:10'),('PROD-260320-001','FG-003',NULL,NULL,'PRESS-01','2026-03-20',80.000,60.000,'Completed','Passed',3,1,'2026-04-02 14:49:10'),('PROD-260325-001','FG-004',NULL,NULL,'PRESS-03','2026-03-25',30.000,25.000,'Completed','Rework',3,NULL,'2026-04-02 14:49:10'),('PROD-260328-001','FG-005',NULL,NULL,'PRESS-02','2026-03-28',250.000,200.000,'Completed','Pending',3,NULL,'2026-04-02 14:49:10');
/*!40000 ALTER TABLE `production_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_orders` (
  `po_id` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `total_amount` decimal(15,2) DEFAULT NULL,
  `currency` varchar(10) DEFAULT NULL,
  `delivery_location` varchar(255) DEFAULT NULL,
  `order_status` enum('Pending','Received','Cancelled') DEFAULT 'Pending',
  `payment_status` enum('Pending','Paid') DEFAULT 'Pending',
  `created_by` int(11) DEFAULT NULL,
  `item_id` varchar(50) DEFAULT NULL,
  `requested_quantity` decimal(12,3) DEFAULT NULL,
  PRIMARY KEY (`po_id`),
  KEY `supplier_id` (`supplier_id`),
  CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
INSERT INTO `purchase_orders` VALUES ('PO-260301-001',1,'2026-03-01',8500.00,'JOD','Main Warehouse','Received','Paid',5,NULL,NULL),('PO-260305-001',2,'2026-03-05',12300.00,'USD','Raw Material Store','Received','Paid',5,NULL,NULL),('PO-260310-001',3,'2026-03-10',6700.00,'USD','Raw Material Store','Received','Paid',5,NULL,NULL),('PO-260318-001',4,'2026-03-18',4200.00,'SAR','Raw Material Store','Received','Pending',5,NULL,NULL),('PO-260325-001',1,'2026-03-25',9800.00,'JOD','Main Warehouse','Pending','Pending',5,NULL,NULL),('PO-260328-001',5,'2026-03-28',18500.00,'EUR','Raw Material Store','Pending','Pending',5,NULL,NULL),('PO-260401-001',2,'2026-04-01',7600.00,'USD','Raw Material Store','Pending','Pending',5,NULL,NULL);
/*!40000 ALTER TABLE `purchase_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotation_lines`
--

DROP TABLE IF EXISTS `quotation_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quotation_lines` (
  `quote_line_id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_id` int(11) NOT NULL,
  `item_id` varchar(50) NOT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `total_price` decimal(15,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  PRIMARY KEY (`quote_line_id`),
  KEY `quote_id` (`quote_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `quotation_lines_ibfk_1` FOREIGN KEY (`quote_id`) REFERENCES `quotations` (`quote_id`) ON DELETE CASCADE,
  CONSTRAINT `quotation_lines_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `item_master` (`item_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotation_lines`
--

LOCK TABLES `quotation_lines` WRITE;
/*!40000 ALTER TABLE `quotation_lines` DISABLE KEYS */;
INSERT INTO `quotation_lines` VALUES (1,1,'FG-001',50.000,55.00,2750.00),(2,1,'FG-005',200.000,20.50,4100.00),(3,2,'FG-003',80.000,75.00,6000.00),(4,2,'FG-004',30.000,145.00,4350.00),(5,3,'FG-002',60.000,42.00,2520.00),(6,3,'FG-005',100.000,17.00,1700.00),(7,1,'FG-001',50.000,55.00,2750.00),(8,1,'FG-005',200.000,20.50,4100.00),(9,2,'FG-003',80.000,75.00,6000.00),(10,2,'FG-004',30.000,145.00,4350.00),(11,3,'FG-002',60.000,42.00,2520.00),(12,3,'FG-005',100.000,17.00,1700.00);
/*!40000 ALTER TABLE `quotation_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotations`
--

DROP TABLE IF EXISTS `quotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quotations` (
  `quote_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `valid_until` date DEFAULT NULL,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `status` enum('Draft','Sent','Accepted','Rejected') DEFAULT 'Draft',
  `prepared_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`quote_id`),
  KEY `customer_id` (`customer_id`),
  KEY `prepared_by` (`prepared_by`),
  CONSTRAINT `quotations_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  CONSTRAINT `quotations_ibfk_2` FOREIGN KEY (`prepared_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotations`
--

LOCK TABLES `quotations` WRITE;
/*!40000 ALTER TABLE `quotations` DISABLE KEYS */;
INSERT INTO `quotations` VALUES (1,4,'2026-04-15',8200.00,'Sent',4,'2026-04-02 14:49:10'),(2,5,'2026-04-20',12500.00,'Draft',4,'2026-04-02 14:49:10'),(3,6,'2026-04-10',3800.00,'Sent',4,'2026-04-02 14:49:10');
/*!40000 ALTER TABLE `quotations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recipe_ingredients`
--

DROP TABLE IF EXISTS `recipe_ingredients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recipe_ingredients` (
  `recipe_id` int(11) NOT NULL,
  `raw_material_id` varchar(50) NOT NULL,
  `quantity_required` decimal(12,3) NOT NULL,
  PRIMARY KEY (`recipe_id`,`raw_material_id`),
  KEY `raw_material_id` (`raw_material_id`),
  CONSTRAINT `recipe_ingredients_ibfk_1` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`recipe_id`) ON DELETE CASCADE,
  CONSTRAINT `recipe_ingredients_ibfk_2` FOREIGN KEY (`raw_material_id`) REFERENCES `item_master` (`item_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipe_ingredients`
--

LOCK TABLES `recipe_ingredients` WRITE;
/*!40000 ALTER TABLE `recipe_ingredients` DISABLE KEYS */;
INSERT INTO `recipe_ingredients` VALUES (1,'RM-001',50.000),(1,'RM-004',120.000),(1,'RM-011',90.000),(1,'RM-012',21.000),(1,'RM-013',0.270),(1,'RM-014',0.065),(2,'RM-002',60.000),(2,'RM-004',40.000),(2,'RM-006',2.000),(2,'RM-012',18.000),(3,'RM-001',30.000),(3,'RM-005',50.000),(3,'RM-007',3.000),(3,'RM-009',10.000),(3,'RM-012',15.000);
/*!40000 ALTER TABLE `recipe_ingredients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recipes`
--

DROP TABLE IF EXISTS `recipes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recipes` (
  `recipe_id` int(11) NOT NULL AUTO_INCREMENT,
  `finished_item_id` varchar(50) NOT NULL,
  `recipe_name` varchar(150) NOT NULL,
  `base_yield_qty` decimal(12,3) NOT NULL,
  `curing_time_hours` int(11) DEFAULT 24,
  PRIMARY KEY (`recipe_id`),
  KEY `finished_item_id` (`finished_item_id`),
  CONSTRAINT `recipes_ibfk_1` FOREIGN KEY (`finished_item_id`) REFERENCES `item_master` (`item_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipes`
--

LOCK TABLES `recipes` WRITE;
/*!40000 ALTER TABLE `recipes` DISABLE KEYS */;
INSERT INTO `recipes` VALUES (1,'FG-001','Standard Artificial Stone Mix (خلطة حجر صناعي)',1.000,24),(2,'FG-002','Granite Texture Mix (خلطة جرانيت)',1.000,18),(3,'FG-003','Decorative Pattern Mix (خلطة ديكور)',1.000,36);
/*!40000 ALTER TABLE `recipes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(1,8),(1,9),(2,1),(2,4),(2,5),(3,1),(3,5),(3,7),(3,8),(4,1),(4,5),(4,6),(5,1),(5,3),(6,1),(6,5),(7,1),(7,2),(7,9),(8,1),(8,7);
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Executive Board','Full system access'),(2,'Production Manager','Manage production and inventory'),(3,'Sales Engineer','Manage sales and CRM'),(4,'Procurement Officer','Manage purchases and suppliers'),(5,'HR Manager','Human Resources administration'),(6,'Inventory Manager','Warehouse control, safety stock, and material registration'),(7,'IT Administrator','System maintenance, user provisioning, and security'),(8,'Finance Manager','General ledger, payroll, and financial compliance');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_order_lines`
--

DROP TABLE IF EXISTS `sales_order_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_order_lines` (
  `line_id` int(11) NOT NULL AUTO_INCREMENT,
  `so_id` varchar(50) NOT NULL,
  `item_id` varchar(50) NOT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  PRIMARY KEY (`line_id`),
  KEY `so_id` (`so_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `sales_order_lines_ibfk_1` FOREIGN KEY (`so_id`) REFERENCES `sales_orders` (`so_id`) ON DELETE CASCADE,
  CONSTRAINT `sales_order_lines_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `item_master` (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_order_lines`
--

LOCK TABLES `sales_order_lines` WRITE;
/*!40000 ALTER TABLE `sales_order_lines` DISABLE KEYS */;
INSERT INTO `sales_order_lines` VALUES (1,'SO-260418-1736','FG-002',1.000,35.00),(2,'SO-260418-5738','FG-002',1.000,35.00),(3,'SO-260419-5049','FG-001',3.000,45.00),(4,'SO-260419-8400','FG-002',5.000,35.00),(5,'SO-260423-7623','FG-002',8.000,35.00);
/*!40000 ALTER TABLE `sales_order_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_orders`
--

DROP TABLE IF EXISTS `sales_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_orders` (
  `so_id` varchar(50) NOT NULL,
  `quote_id` int(11) DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `total_price` decimal(12,2) DEFAULT NULL,
  `delivery_location` varchar(100) DEFAULT NULL,
  `order_status` enum('Pending','In Production','Pending Delivery','Delivered','Archived') DEFAULT 'Pending',
  `handled_by` int(11) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`so_id`),
  KEY `quote_id` (`quote_id`),
  KEY `customer_id` (`customer_id`),
  KEY `handled_by` (`handled_by`),
  CONSTRAINT `sales_orders_ibfk_1` FOREIGN KEY (`quote_id`) REFERENCES `quotations` (`quote_id`) ON DELETE SET NULL,
  CONSTRAINT `sales_orders_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON UPDATE CASCADE,
  CONSTRAINT `sales_orders_ibfk_3` FOREIGN KEY (`handled_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_orders`
--

LOCK TABLES `sales_orders` WRITE;
/*!40000 ALTER TABLE `sales_orders` DISABLE KEYS */;
INSERT INTO `sales_orders` VALUES ('SO-260301-0001',NULL,1,'2026-03-01',4500.00,'Amman, Jordan','In Production',4,'Bank Transfer'),('SO-260305-0001',NULL,2,'2026-03-05',7800.00,'Aqaba, Jordan','In Production',4,'Credit Card'),('SO-260310-0001',NULL,3,'2026-03-10',15200.00,'Dubai, UAE','In Production',4,'Bank Transfer'),('SO-260315-0001',NULL,1,'2026-03-15',3200.00,'Amman, Jordan','Pending Delivery',4,'Cash'),('SO-260320-0001',NULL,4,'2026-03-20',6500.00,'Irbid, Jordan','Pending',4,'Credit Card'),('SO-260325-0001',NULL,2,'2026-03-25',9100.00,'Aqaba, Jordan','In Production',4,'Bank Transfer'),('SO-260328-0001',NULL,3,'2026-03-28',22500.00,'Dubai, UAE','Delivered',4,'Bank Transfer'),('SO-260401-0001',NULL,5,'2026-04-01',1800.00,'Athens, Greece','Pending Delivery',4,'Credit Card'),('SO-260402-0001',NULL,1,'2026-04-02',5400.00,'Amman, Jordan','Pending',4,'Cash'),('SO-260408-5281',NULL,9,'2026-04-08',750.00,'','Pending Delivery',4,'Credit Card'),('SO-260408-5779',NULL,7,'2026-04-08',500.00,'','Pending Delivery',4,'Credit Card'),('SO-260409-3065',NULL,10,'2026-04-09',1200.00,'','Pending Delivery',4,'Credit Card'),('SO-260409-4903',NULL,11,'2026-04-09',5000.00,'tlaa al ali','Delivered',4,'Cash'),('SO-260409-5299',NULL,12,'2026-04-09',5000.00,'','Archived',4,'Credit Card'),('SO-260412-7420',NULL,6,'2026-04-12',0.00,'Casablanca, Morocco','Pending',4,'TBD'),('SO-260414-7883',NULL,13,'2026-04-14',0.00,'=== ONLINE ORDER ===\nBathroom Vanity Top ×2 @$85.00 = $170.00\nDecorative Stone Panel 100x50 ×2 @$65.','Pending',4,'TBD'),('SO-260418-1736',NULL,15,'2026-04-18',35.00,'=== ONLINE ORDER ===\nArtificial Granite Tile 60x60 ×1 @$35.00 = $35.00\n---\nTotal: $35.00\nPayment: Ba','Archived',1,'TBD'),('SO-260418-3258',NULL,7,'2026-04-18',0.00,'=== ONLINE ORDER ===\nArtificial Granite Tile 60x60 ×2 @$35.00 = $70.00\n---\nTotal: $70.00\nPayment: Ba','Pending',4,'TBD'),('SO-260418-5738',NULL,16,'2026-04-19',35.00,'=== ONLINE ORDER ===\nArtificial Granite Tile 60x60 ×1 @$35.00 = $35.00\n---\nTotal: $35.00\nPayment: Ba','Archived',1,'TBD'),('SO-260419-5049',NULL,17,'2026-04-19',135.00,'=== ONLINE ORDER ===\nArtificial Marble Slab 120x60 ×3 @$45.00 = $135.00\n---\nTotal: $135.00\nPayment: ','Delivered',1,'TBD'),('SO-260419-8400',NULL,18,'2026-04-19',175.00,'=== ONLINE ORDER ===\nArtificial Granite Tile 60x60 ×5 @$35.00 = $175.00\n---\nTotal: $175.00\nPayment: ','Archived',1,'TBD'),('SO-260423-7623',NULL,19,'2026-04-23',280.00,'=== ONLINE ORDER ===\nArtificial Granite Tile 60x60 ×8 @$35.00 = $280.00\n---\nTotal: $280.00\nPayment: ','Pending',1,'TBD');
/*!40000 ALTER TABLE `sales_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_items`
--

DROP TABLE IF EXISTS `supplier_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL,
  `item_id` varchar(50) NOT NULL,
  `unit_price` decimal(12,2) DEFAULT NULL COMMENT 'Supplier-specific price per unit',
  `lead_time_days` int(11) DEFAULT 7 COMMENT 'Typical delivery time in days',
  `is_preferred` tinyint(1) DEFAULT 0 COMMENT 'Preferred supplier for this item',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_supplier_item` (`supplier_id`,`item_id`),
  KEY `idx_supplier` (`supplier_id`),
  KEY `idx_item` (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_items`
--

LOCK TABLES `supplier_items` WRITE;
/*!40000 ALTER TABLE `supplier_items` DISABLE KEYS */;
INSERT INTO `supplier_items` VALUES (1,1,'RM-001',6.80,7,1),(2,1,'RM-002',5.50,7,1),(3,1,'RM-012',0.08,7,1),(4,2,'RM-004',9.60,7,1),(5,2,'RM-005',12.00,7,1),(6,2,'RM-003',4.20,7,1),(7,2,'RM-011',1.20,7,1),(8,3,'RM-008',18.50,7,1),(9,3,'RM-006',22.00,7,1),(10,3,'RM-007',24.00,7,1),(11,3,'RM-010',8.50,7,1),(12,3,'RM-013',40.00,7,1),(13,3,'RM-009',15.00,7,1),(14,4,'RM-014',28.00,7,1),(15,4,'RM-013',42.00,7,0),(16,4,'RM-010',9.00,7,0),(17,5,'RM-005',14.50,7,0),(18,5,'RM-004',11.00,7,0),(19,5,'RM-003',5.00,7,0);
/*!40000 ALTER TABLE `supplier_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_name` varchar(255) NOT NULL,
  `preferred_currency` varchar(10) DEFAULT 'JOD',
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `supplies_items` text DEFAULT NULL,
  PRIMARY KEY (`supplier_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'Jordan Quarry Corp','JOD','Ahmad Al-Rashid','+962-79-555-1001','ahmad@rashidcement.jo','Industrial Zone, Zarqa','White Cement, Grey Cement'),(2,'Turkish Marble Exports','USD','Mohammad Jabari','+962-79-555-1002','info@jabari-minerals.jo','Fuheis, Balqa','Quartz Aggregate, Marble Chips, Crushed Limestone, Sand'),(3,'Egyptian Aggregate Supply','USD','Sara Kasim','+962-79-555-1003','sara@chemicals-jo.com','Sahab Industrial Area','Polyester Resin, Iron Oxide Pigments, Silicon Sealant, SMF Additive'),(4,'Saudi Chemical Solutions','SAR',NULL,NULL,NULL,NULL,NULL),(5,'Italian Stone Masters','EUR',NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_config`
--

DROP TABLE IF EXISTS `system_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_config` (
  `config_key` varchar(100) NOT NULL,
  `config_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_config`
--

LOCK TABLES `system_config` WRITE;
/*!40000 ALTER TABLE `system_config` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_logs`
--

DROP TABLE IF EXISTS `system_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `status` enum('Success','Failure','Warning') NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=256 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_logs`
--

LOCK TABLES `system_logs` WRITE;
/*!40000 ALTER TABLE `system_logs` DISABLE KEYS */;
INSERT INTO `system_logs` VALUES (1,1,'LOGIN','Successful login','Success','2026-03-28 14:14:07'),(2,1,'LOGIN','Successful login','Success','2026-03-31 12:23:18'),(3,1,'LOGOUT','User logged out','Success','2026-03-31 12:24:53'),(4,1,'LOGIN','Successful login','Success','2026-04-02 13:47:54'),(5,1,'LOGOUT','User logged out','Success','2026-04-02 13:53:49'),(6,1,'LOGIN','Successful login','Success','2026-04-02 13:54:00'),(7,1,'LOGOUT','User logged out','Success','2026-04-02 14:26:46'),(8,1,'LOGIN','Successful login','Success','2026-04-02 14:27:06'),(9,1,'LOGIN','Successful login','Success','2026-04-02 14:49:58'),(10,1,'UPDATE_ORDER_STATUS','Updated order SO-260402-0001 status to Pending','Success','2026-04-02 15:01:14'),(11,1,'ACCOUNT_PROVISIONED','Provisioned system account for employee ID: 6','Success','2026-04-02 15:06:29'),(12,1,'LOGIN','Successful login','Success','2026-04-03 13:07:29'),(13,1,'LOGIN','Successful login','Success','2026-04-05 06:09:46'),(14,1,'UPDATE_ORDER_STATUS','Updated order SO-260325-0001 status to In Production','Success','2026-04-05 06:10:51'),(15,1,'UPDATE_ORDER_STATUS','Updated order SO-260325-0001 status to Pending Delivery','Success','2026-04-05 06:10:53'),(16,1,'UPDATE_ORDER_STATUS','Updated order SO-260325-0001 status to Pending Delivery','Success','2026-04-05 06:10:55'),(17,1,'UPDATE_ORDER_STATUS','Updated order SO-260325-0001 status to Pending','Success','2026-04-05 06:10:58'),(18,1,'LOGIN','Successful login','Success','2026-04-05 08:07:39'),(19,1,'LOGOUT','User logged out','Success','2026-04-05 08:11:02'),(20,1,'LOGIN','Successful login','Success','2026-04-05 08:11:28'),(21,1,'UPDATE_ORDER_STATUS','Updated order SO-260402-0001 status to In Production','Success','2026-04-05 08:13:13'),(22,1,'UPDATE_ORDER_STATUS','Updated order SO-260315-0001 status to Pending','Success','2026-04-05 08:13:29'),(23,1,'UPDATE_ORDER_STATUS','Updated order SO-260315-0001 status to In Production','Success','2026-04-05 08:13:36'),(24,1,'UPDATE_ORDER_STATUS','Updated order SO-260315-0001 status to Pending Delivery','Success','2026-04-05 08:13:38'),(25,1,'UPDATE_ORDER_STATUS','Updated order SO-260402-0001 status to Pending Delivery','Success','2026-04-05 08:14:17'),(26,1,'UPDATE_ORDER_STATUS','Updated order SO-260402-0001 status to In Production','Success','2026-04-05 08:21:33'),(27,1,'UPDATE_ORDER_STATUS','Updated order SO-260320-0001 status to In Production','Success','2026-04-05 08:21:39'),(28,1,'UPDATE_ORDER_STATUS','Updated order SO-260320-0001 status to Pending','Success','2026-04-05 08:21:47'),(29,1,'LOGOUT','User logged out','Success','2026-04-05 08:24:15'),(30,1,'LOGIN','Successful login','Success','2026-04-05 08:25:31'),(31,1,'LOGOUT','User logged out','Success','2026-04-05 08:25:41'),(32,1,'LOGIN','Successful login','Success','2026-04-05 08:26:02'),(33,1,'UPDATE_ORDER_STATUS','Updated order SO-260320-0001 status to In Production','Success','2026-04-05 08:26:17'),(34,1,'UPDATE_ORDER_STATUS','Updated order SO-260310-0001 status to Delivered','Success','2026-04-05 08:26:36'),(35,1,'LOGOUT','User logged out','Success','2026-04-05 08:27:29'),(36,1,'LOGIN','Successful login','Success','2026-04-05 08:34:35'),(37,1,'UPDATE_ORDER_STATUS','Updated order SO-260401-0001 status to Pending Delivery','Success','2026-04-05 08:34:45'),(38,1,'UPDATE_ORDER_STATUS','Updated order SO-260401-0001 status to Delivered','Success','2026-04-05 08:34:56'),(39,1,'LOGOUT','User logged out','Success','2026-04-05 08:37:16'),(40,1,'LOGIN','Successful login','Success','2026-04-05 08:38:26'),(41,1,'UPDATE_ORDER_STATUS','Updated order SO-260301-0001 status to Pending','Success','2026-04-05 08:38:37'),(42,1,'UPDATE_ORDER_STATUS','Updated order SO-260301-0001 status to Delivered','Success','2026-04-05 08:38:42'),(43,1,'LOGOUT','User logged out','Success','2026-04-05 08:47:31'),(44,1,'LOGIN','Successful login','Success','2026-04-05 08:48:00'),(45,1,'UPDATE_ORDER_STATUS','Updated order SO-260325-0001 status to In Production','Success','2026-04-05 08:59:18'),(46,1,'UPDATE_ORDER_STATUS','Updated order SO-260328-0001 status to In Production','Success','2026-04-05 08:59:20'),(47,1,'UPDATE_ORDER_STATUS','Updated order SO-260328-0001 status to Pending','Success','2026-04-05 09:02:22'),(48,1,'UPDATE_ORDER_STATUS','Updated order SO-260325-0001 status to Pending','Success','2026-04-05 09:02:23'),(49,1,'UPDATE_ORDER_STATUS','Updated order SO-260320-0001 status to Pending','Success','2026-04-05 09:02:24'),(50,1,'LOGOUT','User logged out','Success','2026-04-05 09:02:48'),(51,1,'LOGIN','Successful login','Success','2026-04-05 09:03:39'),(52,1,'LOGOUT','User logged out','Success','2026-04-05 09:06:08'),(53,1,'LOGIN','Successful login','Success','2026-04-05 09:14:36'),(54,1,'UPDATE_ORDER_STATUS','Updated order SO-260328-0001 status to In Production','Success','2026-04-05 09:15:11'),(55,1,'UPDATE_ORDER_STATUS','Updated order SO-260328-0001 status to Pending','Success','2026-04-05 09:15:18'),(56,1,'LOGOUT','User logged out','Success','2026-04-05 09:16:36'),(57,1,'LOGIN','Successful login','Success','2026-04-05 09:17:14'),(58,1,'LOGOUT','User logged out','Success','2026-04-05 09:46:57'),(59,1,'LOGIN','Successful login','Success','2026-04-05 09:47:14'),(60,1,'LOGIN','Successful login','Success','2026-04-08 17:44:21'),(61,1,'LOGOUT','User logged out','Success','2026-04-08 17:47:25'),(62,4,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-08 17:47:48'),(63,4,'UPDATE_ORDER_STATUS','Updated order SO-260328-0001 status to In Production','Success','2026-04-08 17:48:16'),(64,4,'UPDATE_ORDER_STATUS','Updated order SO-260328-0001 status to Pending','Success','2026-04-08 17:48:18'),(65,4,'LOGOUT','User logged out','Success','2026-04-08 17:48:46'),(66,3,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-08 17:49:03'),(67,3,'LOGOUT','User logged out','Success','2026-04-08 17:50:48'),(68,5,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-08 17:51:05'),(69,5,'LOGOUT','User logged out','Success','2026-04-08 17:53:11'),(70,1,'LOGIN','Successful login','Success','2026-04-08 17:53:19'),(71,1,'LOGOUT','User logged out','Success','2026-04-08 18:03:32'),(72,1,'LOGIN','Successful login','Success','2026-04-08 18:04:01'),(73,1,'LOGOUT','User logged out','Success','2026-04-08 18:05:23'),(74,3,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-08 18:05:51'),(75,3,'LOGOUT','User logged out','Success','2026-04-08 18:08:08'),(76,4,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-08 18:08:25'),(77,4,'LOGOUT','User logged out','Success','2026-04-08 18:13:57'),(78,5,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-08 18:14:14'),(79,1,'LOGIN','Successful login','Success','2026-04-08 18:38:23'),(80,1,'LOGOUT','User logged out','Success','2026-04-08 18:44:43'),(81,5,'LOGOUT','User logged out','Success','2026-04-08 18:48:51'),(82,3,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-08 18:49:11'),(83,3,'LOGOUT','User logged out','Success','2026-04-08 18:49:23'),(84,4,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-08 18:50:02'),(85,1,'LOGIN','Successful login','Success','2026-04-08 19:08:30'),(86,1,'LOGOUT','User logged out','Success','2026-04-08 19:09:03'),(87,4,'LOGOUT','User logged out','Success','2026-04-08 19:10:15'),(88,3,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-08 19:11:06'),(89,3,'LOGOUT','User logged out','Success','2026-04-08 19:11:15'),(90,4,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-08 19:15:27'),(91,4,'CREATE_ORDER','Created new sales order: SO-260408-5779','Success','2026-04-08 19:15:52'),(92,4,'UPDATE_ORDER_STATUS','Updated order SO-260408-5779 status to Pending','Success','2026-04-08 19:17:00'),(93,4,'UPDATE_ORDER_STATUS','Updated order SO-260408-5779 status to Pending','Success','2026-04-08 19:18:06'),(94,4,'LOGOUT','User logged out','Success','2026-04-08 19:21:22'),(95,3,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-08 19:22:48'),(96,3,'UPDATE_ORDER_STATUS','Updated order SO-260408-5779 status to In Production','Success','2026-04-08 19:23:09'),(97,3,'UPDATE_ORDER_STATUS','Updated order SO-260408-5779 status to Delivered','Success','2026-04-08 19:23:45'),(98,3,'LOGOUT','User logged out','Success','2026-04-08 19:27:01'),(99,4,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-08 19:28:44'),(100,4,'CREATE_ORDER','Created new sales order: SO-260408-5281','Success','2026-04-08 19:29:13'),(101,4,'UPDATE_ORDER_STATUS','Updated order SO-260408-5281 status to In Production','Success','2026-04-08 19:31:53'),(102,4,'LOGOUT','User logged out','Success','2026-04-08 19:32:03'),(103,3,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-08 19:33:52'),(104,3,'UPDATE_ORDER_STATUS','Updated order SO-260325-0001 status to In Production','Success','2026-04-08 19:35:53'),(105,3,'UPDATE_ORDER_STATUS','Updated order SO-260408-5281 status to Pending Delivery','Success','2026-04-08 19:36:13'),(106,3,'UPDATE_ORDER_STATUS','Updated order SO-260408-5281 status to Pending Delivery','Success','2026-04-08 19:36:41'),(107,3,'UPDATE_ORDER_STATUS','Updated order SO-260315-0001 status to Delivered','Success','2026-04-08 19:37:30'),(108,3,'UPDATE_ORDER_STATUS','Updated order SO-260408-5281 status to Delivered','Success','2026-04-08 19:37:37'),(109,3,'UPDATE_ORDER_STATUS','Updated order SO-260408-5281 status to Pending Delivery','Success','2026-04-08 19:37:41'),(110,3,'UPDATE_ORDER_STATUS','Updated order SO-260315-0001 status to Pending Delivery','Success','2026-04-08 19:37:42'),(111,4,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-09 05:59:23'),(112,4,'CREATE_ORDER','Created new sales order: SO-260409-3065','Success','2026-04-09 05:59:44'),(113,4,'UPDATE_ORDER_STATUS','Updated order SO-260409-3065 status to In Production','Success','2026-04-09 06:01:34'),(114,4,'LOGOUT','User logged out','Success','2026-04-09 06:01:41'),(115,3,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-09 06:02:50'),(116,3,'UPDATE_ORDER_STATUS','Updated order SO-260409-3065 status to Pending Delivery','Success','2026-04-09 06:03:25'),(117,3,'LOGOUT','User logged out','Success','2026-04-09 06:06:48'),(118,4,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-09 06:16:57'),(119,4,'CREATE_ORDER','Created new sales order: SO-260409-4903','Success','2026-04-09 06:17:55'),(120,4,'LOGOUT','User logged out','Success','2026-04-09 06:18:07'),(121,3,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-09 06:18:21'),(122,3,'UPDATE_ORDER_STATUS','Updated order SO-260409-4903 status to In Production','Success','2026-04-09 06:18:39'),(123,3,'UPDATE_ORDER_STATUS','Updated order SO-260409-4903 status to Pending Delivery','Success','2026-04-09 06:19:28'),(124,3,'UPDATE_ORDER_STATUS','Updated order SO-260409-4903 status to Pending','Success','2026-04-09 06:19:30'),(125,3,'LOGOUT','User logged out','Success','2026-04-09 06:19:37'),(126,4,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-09 08:12:38'),(127,4,'LOGOUT','User logged out','Success','2026-04-09 08:29:18'),(128,3,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-09 08:29:39'),(129,3,'LOGOUT','User logged out','Success','2026-04-09 08:32:41'),(130,4,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-09 09:06:21'),(131,4,'LOGOUT','User logged out','Success','2026-04-09 09:13:47'),(132,3,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-09 09:15:29'),(133,3,'UPDATE_BATCH','Updated Production Batch PRD-20260409-673 status','Success','2026-04-09 09:16:06'),(134,3,'LOGOUT','User logged out','Success','2026-04-09 09:18:57'),(135,4,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-09 09:42:48'),(136,4,'CREATE_ORDER','Created new sales order: SO-260409-5299','Success','2026-04-09 09:43:12'),(137,4,'LOGOUT','User logged out','Success','2026-04-09 09:43:34'),(138,3,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-09 09:43:53'),(139,3,'UPDATE_ORDER_STATUS','Updated order SO-260409-5299 status to In Production','Success','2026-04-09 09:44:23'),(140,3,'UPDATE_BATCH','Updated Production Batch PRD-20260409-647 status','Success','2026-04-09 09:44:46'),(141,1,'LOGIN','Successful login','Success','2026-04-09 11:49:14'),(142,1,'UPDATE_ORDER_STATUS','Updated order SO-260409-4903 status to In Production','Success','2026-04-09 11:50:07'),(143,1,'UPDATE_ORDER_STATUS','Updated order SO-260409-5299 status to Pending','Success','2026-04-09 11:50:37'),(144,1,'LOGIN','Successful login','Success','2026-04-10 08:47:26'),(145,1,'LOGOUT','User logged out','Success','2026-04-10 08:55:01'),(146,10,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-10 08:57:04'),(147,10,'LOGOUT','User logged out','Success','2026-04-10 08:59:31'),(148,10,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-10 09:02:36'),(149,10,'UNAUTHORIZED_ACCESS','Attempted to access /admin without permission. Role: Finance Manager','Failure','2026-04-10 09:02:42'),(150,10,'LOGOUT','User logged out','Success','2026-04-10 09:02:52'),(151,10,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-10 09:21:39'),(152,11,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-10 09:32:06'),(153,11,'LOGOUT','User logged out','Success','2026-04-10 09:32:28'),(154,1,'LOGIN','Successful login','Success','2026-04-10 09:32:42'),(155,1,'LOGOUT','User logged out','Success','2026-04-10 09:33:18'),(156,1,'LOGIN','Successful login','Success','2026-04-10 09:33:57'),(157,1,'LOGOUT','User logged out','Success','2026-04-10 09:34:17'),(158,11,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-10 09:35:14'),(159,11,'LOGOUT','User logged out','Success','2026-04-10 10:47:15'),(160,1,'LOGIN','Successful login','Success','2026-04-10 10:47:50'),(161,1,'UPDATE_ORDER_STATUS','Updated order SO-260320-0001 status to In Production','Success','2026-04-10 10:50:31'),(162,1,'UPDATE_ORDER_STATUS','Updated order SO-260320-0001 status to Pending','Success','2026-04-10 10:50:33'),(163,1,'UPDATE_ORDER_STATUS','Updated order SO-260409-5299 status to Pending Delivery','Success','2026-04-10 10:50:52'),(164,1,'UPDATE_ORDER_STATUS','Updated order SO-260409-5299 status to Pending','Success','2026-04-10 10:50:57'),(165,1,'UPDATE_ORDER_STATUS','Updated order SO-260328-0001 status to In Production','Success','2026-04-10 11:03:23'),(166,1,'UPDATE_ORDER_STATUS','Updated order SO-260409-4903 status to Pending Delivery','Success','2026-04-10 11:05:56'),(167,1,'LOGOUT','User logged out','Success','2026-04-10 11:08:18'),(168,1,'LOGIN','Successful login','Success','2026-04-10 11:08:54'),(169,1,'UPDATE_ORDER_STATUS','Updated order SO-260409-4903 status to Delivered','Success','2026-04-10 11:09:09'),(170,1,'UPDATE_ORDER_STATUS','Updated order SO-260409-3065 status to Delivered','Success','2026-04-10 12:43:58'),(171,1,'LOGIN','Successful login','Success','2026-04-10 16:53:18'),(172,1,'UPDATE_ORDER_STATUS','Updated order SO-260402-0001 status to Pending','Success','2026-04-10 16:53:41'),(173,1,'UPDATE_ORDER_STATUS','Updated order SO-260409-5299 status to Pending Delivery','Success','2026-04-10 16:55:18'),(174,1,'UPDATE_ORDER_STATUS','Updated order SO-260409-5299 status to Delivered','Success','2026-04-10 16:55:23'),(175,1,'LOGOUT','User logged out','Success','2026-04-10 17:03:12'),(176,3,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-10 17:04:48'),(177,3,'UPDATE_ORDER_STATUS','Updated order SO-260409-5299 status to Pending','Success','2026-04-10 17:05:00'),(178,3,'UPDATE_ORDER_STATUS','Updated order SO-260310-0001 status to In Production','Success','2026-04-10 17:05:10'),(179,3,'UPDATE_ORDER_STATUS','Updated order SO-260401-0001 status to Pending Delivery','Success','2026-04-10 17:05:22'),(180,3,'UPDATE_ORDER_STATUS','Updated order SO-260315-0001 status to Delivered','Success','2026-04-10 17:05:24'),(181,3,'UPDATE_ORDER_STATUS','Updated order SO-260401-0001 status to Pending Delivery','Success','2026-04-10 17:05:29'),(182,3,'UPDATE_ORDER_STATUS','Updated order SO-260408-5281 status to Delivered','Success','2026-04-10 17:05:31'),(183,3,'UPDATE_ORDER_STATUS','Updated order SO-260328-0001 status to Delivered','Success','2026-04-10 17:05:35'),(184,3,'UPDATE_ORDER_STATUS','Updated order SO-260409-3065 status to Pending Delivery','Success','2026-04-10 17:05:40'),(185,3,'UPDATE_ORDER_STATUS','Updated order SO-260305-0001 status to In Production','Success','2026-04-10 17:05:41'),(186,3,'UPDATE_ORDER_STATUS','Updated order SO-260315-0001 status to Pending Delivery','Success','2026-04-10 17:05:43'),(187,3,'LOGOUT','User logged out','Success','2026-04-10 17:42:50'),(188,1,'LOGIN','Successful login','Success','2026-04-10 17:43:20'),(189,1,'UPDATE_ORDER_STATUS','Updated order SO-260409-5299 (Adam\'s construction) to In Production','Success','2026-04-10 17:43:42'),(190,1,'UPDATE_ORDER_STATUS','Updated order SO-260409-5299 (Adam\'s construction) to Pending Delivery','Success','2026-04-10 17:47:24'),(191,1,'UPDATE_ORDER_STATUS','Updated order SO-260409-5299 (Adam\'s construction) to Delivered','Success','2026-04-10 17:47:44'),(192,1,'UPDATE_BATCH','Updated Production Batch MX-20260410-968','Success','2026-04-10 17:55:23'),(193,1,'UPDATE_BATCH','Updated Production Batch PROD-260328-001','Success','2026-04-10 17:55:32'),(194,1,'UPDATE_ORDER_STATUS','Updated order SO-260315-0001 (Al-Aqsa Construction Co.) to Delivered','Success','2026-04-10 17:57:24'),(195,1,'UPDATE_ORDER_STATUS','Updated order SO-260409-5299 (Adam\'s construction) to Pending','Success','2026-04-10 17:57:33'),(196,1,'UPDATE_ORDER_STATUS','Updated order SO-260409-3065 (Omega Corp) to Delivered','Success','2026-04-10 18:22:33'),(197,1,'UPDATE_ORDER_STATUS','Updated order SO-260408-5779 (Test Corp) to Pending Delivery','Success','2026-04-10 18:22:41'),(198,1,'UPDATE_ORDER_STATUS','Updated order SO-260408-5281 (Alpha Corporation) to Pending Delivery','Success','2026-04-10 18:22:45'),(199,1,'UPDATE_ORDER_STATUS','Updated order SO-260328-0001 (Gulf Building Materials) to Pending Delivery','Success','2026-04-10 18:22:47'),(200,1,'UPDATE_ORDER_STATUS','Updated order SO-260301-0001 (Al-Aqsa Construction Co.) to In Production','Success','2026-04-10 18:22:48'),(201,1,'UPDATE_ORDER_STATUS','Updated order SO-260328-0001 (Gulf Building Materials) to Delivered','Success','2026-04-10 18:23:56'),(202,1,'UPDATE_ORDER_STATUS','Updated order SO-260301-0001 (Al-Aqsa Construction Co.) to Pending','Success','2026-04-10 18:23:57'),(203,1,'LOGIN','Successful login','Success','2026-04-10 18:30:55'),(204,1,'LOGIN','Successful login','Success','2026-04-10 18:33:40'),(205,1,'UPDATE_ORDER_STATUS','Updated order SO-260409-5299 (Adam\'s construction) to In Production','Success','2026-04-10 18:34:20'),(206,1,'UPDATE_ORDER_STATUS','Updated order SO-260409-5299 (Adam\'s construction) to Pending Delivery','Success','2026-04-10 18:34:52'),(207,1,'UPDATE_ORDER_STATUS','Updated order SO-260409-5299 (Adam\'s construction) to Delivered','Success','2026-04-10 18:35:11'),(208,1,'LOGIN','Successful login','Success','2026-04-11 17:17:10'),(209,1,'LOGIN','Successful login','Success','2026-04-12 09:14:32'),(210,1,'LOGOUT','User logged out','Success','2026-04-12 09:21:58'),(211,3,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-12 09:22:15'),(212,1,'LOGIN','Successful login','Success','2026-04-12 10:28:49'),(213,1,'LOGOUT','User logged out','Success','2026-04-12 10:34:23'),(214,4,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-12 10:34:37'),(215,4,'LOGOUT','User logged out','Success','2026-04-12 10:35:54'),(216,4,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-14 16:48:38'),(217,4,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-14 17:43:10'),(218,1,'LOGIN','Successful login','Success','2026-04-16 16:28:16'),(219,1,'LOGIN','Successful login','Success','2026-04-18 19:21:21'),(220,1,'LOGOUT','User logged out','Success','2026-04-18 19:24:51'),(221,4,'LOGIN_OTP','OTP verified, login successful','Success','2026-04-18 19:25:04'),(222,1,'LOGIN','Successful login','Success','2026-04-18 20:44:44'),(223,1,'UPDATE_ORDER_STATUS','Updated order SO-260418-1736 (TestPipeline Corp) to Completed','Success','2026-04-18 20:58:20'),(224,1,'LOGIN','Successful login','Success','2026-04-18 21:25:38'),(225,1,'LOGOUT','User logged out','Success','2026-04-18 21:27:00'),(226,1,'LOGIN','Successful login','Success','2026-04-19 06:17:01'),(227,1,'DELIVERY','Dispatched SO-260418-1736 — TestPipeline Corp — $35','Success','2026-04-19 06:27:32'),(228,1,'DELIVERY','Dispatched SO-260418-5738 — TestPipeline Corp V2 — $35','Success','2026-04-19 06:27:38'),(229,1,'LOGIN','Successful login','Success','2026-04-19 07:17:19'),(230,1,'CREATE_BATCH','Created batch MX-260419-3279 (Recipe #2)','Success','2026-04-19 07:18:07'),(231,1,'UPDATE_BATCH','Updated batch MX-260419-3279 → Mixing','Success','2026-04-19 07:18:26'),(232,1,'CREATE_BATCH','Created batch MX-260419-0083 (Recipe #1)','Success','2026-04-19 07:25:14'),(233,1,'UPDATE_BATCH','Updated batch MX-260419-0083 → Mixing','Success','2026-04-19 07:25:28'),(234,1,'ARCHIVE','Manually archived SO-260418-1736','Success','2026-04-19 07:35:38'),(235,1,'UPDATE_BATCH','Updated batch MX-260419-0083 → Curing','Success','2026-04-19 07:41:57'),(236,1,'UPDATE_BATCH','Updated batch MX-260419-0083 → Completed','Success','2026-04-19 07:42:02'),(237,1,'UPDATE_BATCH','Updated batch MX-260419-3279 → Curing','Success','2026-04-19 07:42:12'),(238,1,'UPDATE_BATCH','Updated batch MX-260419-3279 → Completed','Success','2026-04-19 07:42:17'),(239,1,'MATERIAL_REQUEST','Requested 500 of Crushed Limestone','Success','2026-04-19 08:11:57'),(240,1,'LOGIN','Successful login','Success','2026-04-19 09:11:26'),(241,1,'ARCHIVE','Manually archived SO-260418-5738','Success','2026-04-19 09:20:23'),(242,1,'ARCHIVE','Manually archived SO-260409-5299','Success','2026-04-19 09:20:27'),(243,1,'UPDATE_ORDER_STATUS','Updated order SO-260409-3065 (Omega Corp) to Pending Delivery','Success','2026-04-19 09:20:28'),(244,1,'UPDATE_ORDER_STATUS','Updated order SO-260419-8400 (yazan) to In Production','Success','2026-04-19 09:42:34'),(245,1,'DELIVERY','Dispatched SO-260419-8400 — yazan — $175','Success','2026-04-19 09:44:11'),(246,1,'ARCHIVE','Manually archived SO-260419-8400','Success','2026-04-19 09:44:39'),(247,1,'UPDATE_ORDER_STATUS','Updated order SO-260301-0001 (Al-Aqsa Construction Co.) to In Production','Success','2026-04-19 09:45:45'),(248,1,'CREATE_BATCH','Created batch MX-260419-7714 (Recipe #1)','Success','2026-04-19 09:45:54'),(249,1,'LOGIN','Successful login','Success','2026-04-23 09:13:02'),(250,1,'UPDATE_BATCH','Updated batch MX-260419-7714 → Mixing','Success','2026-04-23 10:00:00'),(251,1,'UPDATE_BATCH','Updated batch MX-260419-7714 → Curing','Success','2026-04-23 10:01:04'),(252,1,'UPDATE_BATCH','Updated batch MX-260419-7714 → Completed','Success','2026-04-23 10:01:14'),(253,1,'DELIVERY','Dispatched SO-260419-5049 — Sabri\'s Consturction — $135','Success','2026-04-23 10:01:48'),(254,1,'LOGIN','Successful login','Success','2026-04-28 10:44:27'),(255,1,'LOGIN','Successful login','Success','2026-04-28 14:02:59');
/*!40000 ALTER TABLE `system_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `mfa_enabled` tinyint(1) DEFAULT 1,
  `account_status` enum('Active','Suspended') DEFAULT 'Active',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE,
  CONSTRAINT `users_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,1,'salem.h@company.com','$2y$10$XNKEgCIPdUKmnbQ.rfOOBu6G1bXWCed/.ngiGPZrvM8cRYyAoq81a',0,'Active'),(2,2,1,'tayseer.k@company.com','Admin@123',0,'Active'),(3,3,2,'omar.a@company.com','$2y$10$zyEe8gsgLzhAygbEVn2ktOXfzrmwofOo0LZGIVSWIehBHTUX6s/wi',1,'Active'),(4,4,3,'lina.d@company.com','$2y$10$zyEe8gsgLzhAygbEVn2ktOXfzrmwofOo0LZGIVSWIehBHTUX6s/wi',1,'Active'),(5,5,4,'khaled.n@company.com','$2y$10$zyEe8gsgLzhAygbEVn2ktOXfzrmwofOo0LZGIVSWIehBHTUX6s/wi',1,'Active'),(6,8,5,'huda.hr@company.com','$2y$10$zyEe8gsgLzhAygbEVn2ktOXfzrmwofOo0LZGIVSWIehBHTUX6s/wi',1,'Active'),(7,11,1,'ahmad.b@company.com','$2y$10$zyEe8gsgLzhAygbEVn2ktOXfzrmwofOo0LZGIVSWIehBHTUX6s/wi',1,'Active'),(8,6,2,'fadi.m@company.com','$2y$10$uTgWjFUUfdpBr1boC7Lqx.4hDMzHBFOEtyFyKp5jbCmhsKssruC7O',1,'Active'),(9,14,7,'yazan.it@company.com','$2y$10$qP3apNG8dWVJ54L0OtyrxO5pYX/uKHgMihfI9Ghtrocr3SLedU.Rq',1,'Active'),(10,15,8,'nour.fin@company.com','$2y$10$qP3apNG8dWVJ54L0OtyrxO5pYX/uKHgMihfI9Ghtrocr3SLedU.Rq',1,'Active'),(11,13,6,'tariq.m@company.com','$2y$10$Ce/l0qA11Q0H1S0JgF7RP.erue.5I1.G6wvQX4VmvqFjVUry5dJbq',1,'Active');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `warehouses` (
  `warehouse_id` int(11) NOT NULL AUTO_INCREMENT,
  `warehouse_name` varchar(100) NOT NULL,
  PRIMARY KEY (`warehouse_id`),
  UNIQUE KEY `warehouse_name` (`warehouse_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `warehouses`
--

LOCK TABLES `warehouses` WRITE;
/*!40000 ALTER TABLE `warehouses` DISABLE KEYS */;
INSERT INTO `warehouses` VALUES (3,'Finished Goods Store'),(1,'Main Warehouse'),(2,'Raw Material Store');
/*!40000 ALTER TABLE `warehouses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `web_order_items`
--

DROP TABLE IF EXISTS `web_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `web_order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `item_id` varchar(50) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `web_order_items_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  CONSTRAINT `web_order_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `item_master` (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `web_order_items`
--

LOCK TABLES `web_order_items` WRITE;
/*!40000 ALTER TABLE `web_order_items` DISABLE KEYS */;
INSERT INTO `web_order_items` VALUES (1,15,'FG-002','Artificial Granite Tile 60x60',1,35.00,'2026-04-18 20:55:02'),(2,16,'FG-002','Artificial Granite Tile 60x60',1,35.00,'2026-04-18 21:05:59'),(3,17,'FG-001','Artificial Marble Slab 120x60',3,45.00,'2026-04-19 09:10:49'),(4,18,'FG-002','Artificial Granite Tile 60x60',5,35.00,'2026-04-19 09:41:56'),(5,19,'FG-002','Artificial Granite Tile 60x60',8,35.00,'2026-04-23 09:58:32');
/*!40000 ALTER TABLE `web_order_items` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-28 18:21:01

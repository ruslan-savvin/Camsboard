-- MySQL dump 10.13  Distrib 8.4.13, for Linux (x86_64)
--
-- Host: localhost    Database: camsboard
-- ------------------------------------------------------
-- Server version	8.4.13

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
-- Table structure for table `cookies`
--

DROP TABLE IF EXISTS `cookies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cookies` (
  `idCookies` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires` datetime DEFAULT NULL,
  `id` int NOT NULL AUTO_INCREMENT,
  `userId` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_cookies_idCookies` (`idCookies`),
  KEY `fk_cookies_users` (`userId`),
  CONSTRAINT `fk_cookies_users` FOREIGN KEY (`userId`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=515 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `deviceEvents`
--

DROP TABLE IF EXISTS `deviceEvents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deviceEvents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `device` int NOT NULL,
  `eventTime` datetime NOT NULL,
  `sceneScore` decimal(8,2) NOT NULL DEFAULT '0.00',
  `isPhotoReady` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0-Photos missing, 1-Files moved to archive',
  `isHumanChecked` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0-AI pending, 1-Analysis completed: person NOT found, 2-Analysis completed: person FOUND',
  `humanCheckedFrame` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '""',
  `elapsed` double NOT NULL DEFAULT '0',
  `filesChecked` int NOT NULL DEFAULT '0',
  `confidence` int unsigned NOT NULL DEFAULT '0',
  `totalDetectionTime` double unsigned DEFAULT '0',
  `isVideoReady` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_events_camera` (`device`),
  CONSTRAINT `fk_events_camera` FOREIGN KEY (`device`) REFERENCES `devices` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6288 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `devices`
--

DROP TABLE IF EXISTS `devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `devices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `deviceName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `isDelete` int unsigned NOT NULL DEFAULT '0',
  `isDefault` int unsigned NOT NULL DEFAULT '0',
  `address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `streamKey` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `height` int unsigned DEFAULT '0',
  `width` int unsigned DEFAULT '0',
  `onlyKeyFrames` int unsigned NOT NULL DEFAULT '0',
  `sceneThreshold` decimal(4,3) unsigned NOT NULL DEFAULT '0.040',
  `fps` int unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `generalSettings`
--

DROP TABLE IF EXISTS `generalSettings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `generalSettings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `domain` varchar(255) DEFAULT NULL,
  `mailHost` varchar(50) DEFAULT NULL,
  `mailPass` varchar(50) DEFAULT NULL,
  `mailUser` varchar(50) DEFAULT NULL,
  `telegramBotIp` varchar(60) DEFAULT NULL,
  `testDomain` varchar(255) DEFAULT NULL,
  `viberBotIp` varchar(50) DEFAULT NULL,
  `ffmpegService` text NOT NULL,
  `detectService` text NOT NULL,
  `timerService` text NOT NULL,
  `timerCleaner` text NOT NULL,
  `timerCopier` text NOT NULL,
  `timerDetector` text,
  `timerTester` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `languages`
--

DROP TABLE IF EXISTS `languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `languages` (
  `languageCode` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `languageName` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `base` int NOT NULL DEFAULT '0',
  `flag` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id` int NOT NULL AUTO_INCREMENT,
  `ordering` int NOT NULL DEFAULT '0',
  `adminPanel` int NOT NULL DEFAULT '0',
  `isDefault` int NOT NULL DEFAULT '0',
  `isDelete` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `languageValue` (`languageCode`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `localization`
--

DROP TABLE IF EXISTS `localization`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `localization` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `textKey` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `languageId` int NOT NULL DEFAULT '0',
  `textValue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_textkey_lang_id` (`textKey`,`languageId`)
) ENGINE=InnoDB AUTO_INCREMENT=1134 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks` (
  `userId` int NOT NULL DEFAULT '0',
  `task` int NOT NULL DEFAULT '0',
  `id` int NOT NULL AUTO_INCREMENT,
  `isDelete` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `fk_task_user` (`userId`,`task`) USING BTREE,
  CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `testers`
--

DROP TABLE IF EXISTS `testers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `testers` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `testDateTime` datetime NOT NULL,
  `name` varchar(255) NOT NULL,
  `testResult` varchar(255) NOT NULL,
  `inter_val` int unsigned NOT NULL,
  `testJsonResult` json NOT NULL,
  `errCount` int unsigned NOT NULL DEFAULT '0',
  `errTarget` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `token` bigint unsigned NOT NULL,
  `chatCod` json NOT NULL DEFAULT (json_object()),
  `pass` int NOT NULL DEFAULT '0',
  `id` int NOT NULL AUTO_INCREMENT,
  `role` tinyint NOT NULL DEFAULT '0',
  `languageId` int NOT NULL DEFAULT '0',
  `isDelete` int NOT NULL DEFAULT '0',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `surname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_users_user` (`token`),
  KEY `fk_user_language` (`languageId`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`languageId`) REFERENCES `languages` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `check_min_token_length` CHECK ((`token` >= 1000000000)),
  CONSTRAINT `chk_users_chat_cod_json` CHECK (json_valid(`chatCod`))
) ENGINE=InnoDB AUTO_INCREMENT=415 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-20 18:07:03
-- MySQL dump 10.13  Distrib 8.4.13, for Linux (x86_64)
--
-- Host: localhost    Database: camsboard
-- ------------------------------------------------------
-- Server version	8.4.13

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
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1234567891,'{\"email\": \"****@****.com\", \"viber\": \"*****\", \"telegram\": 1111}',0,1,2,1,0,'Name','Surname');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
INSERT INTO `tasks` VALUES (1,1,9,0),(1,2,10,0);
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `languages`
--

LOCK TABLES `languages` WRITE;
/*!40000 ALTER TABLE `languages` DISABLE KEYS */;
INSERT INTO `languages` VALUES ('EN','English',0,'england',1,1,1,0,0),('UA','Українська',1,'ukraine',3,0,1,1,0);
/*!40000 ALTER TABLE `languages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `localization`
--

LOCK TABLES `localization` WRITE;
/*!40000 ALTER TABLE `localization` DISABLE KEYS */;
INSERT INTO localization VALUES (91,'global.menu.add',1,'Add'),(92,'global.menu.add',3,'Додати'),(93,'global.menu.save',1,'Save'),(94,'global.menu.save',3,'Зберегти'),(95,'global.menu.delete',1,'Delete'),(96,'global.menu.delete',3,'Видалити'),(97,'global.menu.edit',1,'Edit'),(98,'global.menu.edit',3,'Редагувати'),(167,'global.menu.up',1,'Move Up'),(168,'global.menu.up',3,'Вгору'),(169,'global.menu.down',1,'Move Down'),(170,'global.menu.down',3,'Вниз'),(171,'table.languages.col.delete',1,'Deleted'),(172,'table.languages.col.delete',3,'Видалена'),(173,'table.languages.col.languageCode',1,'Language Code'),(174,'table.languages.col.languageCode',3,'Код мови'),(175,'table.languages.col.languageName',1,'Language Name'),(176,'table.languages.col.languageName',3,'Назва мови'),(177,'table.languages.col.base',1,'Base'),(178,'table.languages.col.base',3,'Основна'),(179,'table.languages.col.flag',1,'Flag'),(180,'table.languages.col.flag',3,'Прапор'),(181,'table.languages.col.ordering',1,'Ordering'),(182,'table.languages.col.ordering',3,'Порядок'),(183,'table.languages.col.adminPanel',1,'Admin Panel'),(184,'table.languages.col.adminPanel',3,'Панель адміністратора'),(185,'table.languages.editor.delete',1,'Deleted'),(186,'table.languages.editor.delete',3,'Видалена'),(187,'table.languages.editor.languageCode',1,'Language Code'),(188,'table.languages.editor.languageCode',3,'Код мови'),(189,'table.languages.editor.languageName',1,'Language Name'),(190,'table.languages.editor.languageName',3,'Назва мови'),(191,'table.languages.editor.base',1,'Base'),(192,'table.languages.editor.base',3,'Основна'),(193,'table.languages.editor.flag',1,'Flag'),(194,'table.languages.editor.flag',3,'Прапор'),(195,'table.languages.editor.adminPanel',1,'Admin Panel'),(196,'table.languages.editor.adminPanel',3,'Панель адміністратора'),(215,'table.users.col.delete',1,'Deleted'),(216,'table.users.col.delete',3,'Видалена'),(217,'table.users.col.token',1,'Token'),(218,'table.users.col.token',3,'Токен'),(219,'table.users.col.name',1,'Name'),(220,'table.users.col.name',3,'Ім\'я'),(221,'table.users.col.role',1,'Role'),(222,'table.users.col.role',3,'Роль'),(223,'table.users.col.chat',1,'Chat'),(224,'table.users.col.chat',3,'Чат'),(225,'table.users.col.languageId',1,'Language'),(226,'table.users.col.languageId',3,'Мова'),(227,'table.users.editor.delete',1,'Deleted'),(228,'table.users.editor.delete',3,'Видалена'),(229,'table.users.editor.token',1,'Token'),(230,'table.users.editor.token',3,'Токен'),(231,'table.users.editor.name',1,'First Name'),(232,'table.users.editor.name',3,'Ім\'я'),(233,'table.users.editor.surname',1,'Surname'),(234,'table.users.editor.surname',3,'Прізвище'),(235,'table.users.editor.role',1,'Role'),(236,'table.users.editor.role',3,'Роль'),(237,'table.users.editor.languageId',1,'Language'),(238,'table.users.editor.languageId',3,'Мова'),(239,'table.tasks.col.delete',1,'Deleted'),(240,'table.tasks.col.delete',3,'Видалена'),(241,'table.tasks.col.userId',1,'User'),(242,'table.tasks.col.userId',3,'Користувач'),(243,'table.tasks.col.task',1,'Task'),(244,'table.tasks.col.task',3,'Завдання'),(245,'table.tasks.editor.delete',1,'Deleted'),(246,'table.tasks.editor.delete',3,'Видалена'),(247,'table.tasks.editor.userId',1,'User'),(248,'table.tasks.editor.userId',3,'Користувач'),(249,'table.tasks.editor.task',1,'Task'),(250,'table.tasks.editor.task',3,'Завдання'),(327,'global.nav.page4',1,'Events'),(328,'global.nav.page4',3,'Події'),(337,'global.nav.page5',1,'Settings'),(338,'global.nav.page5',3,'Налаштування'),(339,'global.nav.page6',1,'Reference'),(340,'global.nav.page6',3,'Довідники'),(341,'global.nav.page9',1,'Log'),(342,'global.nav.page9',3,'Лог'),(350,'table.logs.col.date',1,'Date'),(351,'table.logs.col.date',3,'Дата'),(352,'table.logs.col.information',1,'Information'),(353,'table.logs.col.information',3,'Інформація'),(354,'global.menu.refresh',1,'Refresh'),(355,'global.menu.refresh',3,'Оновити'),(372,'enumeration.yesNo.no',1,'No'),(373,'enumeration.yesNo.no',3,'Ні'),(374,'enumeration.yesNo.yes',1,'Yes'),(375,'enumeration.yesNo.yes',3,'Так'),(376,'global.button.no',1,'No'),(377,'global.button.no',3,'Ні'),(378,'global.button.yes',1,'Yes'),(379,'global.button.yes',3,'Так'),(384,'modalWindow.inscription.changeData',1,'Change data'),(385,'modalWindow.inscription.changeData',3,'Зміна даних'),(440,'forma.global.button.save',1,'Save settings'),(441,'forma.global.button.save',3,'Зберегти налаштування'),(446,'forma.systemSettings.inscription.mailHost',1,'Outgoing mail server (SMTP)'),(447,'forma.systemSettings.inscription.mailHost',3,'Сервер вихідної пошти'),(448,'forma.systemSettings.inscription.mailUser',1,'Outgoing mail server login'),(449,'forma.systemSettings.inscription.mailUser',3,'Логін сервера вихідної пошти'),(450,'forma.systemSettings.inscription.mailPass',1,'Outgoing mail server password'),(451,'forma.systemSettings.inscription.mailPass',3,'Пароль сервера вихідної пошти'),(454,'forma.systemSettings.inscription.telegramBotIp',1,'Telegram Bot IP'),(455,'forma.systemSettings.inscription.telegramBotIp',3,'Telegram Bot Ip'),(460,'forma.systemSettings.inscription.viberBotIp',1,'Viber token'),(461,'forma.systemSettings.inscription.viberBotIp',3,'Viber token'),(466,'forma.systemSettings.inscription.domain',1,'Domain'),(467,'forma.systemSettings.inscription.domain',3,'Домен'),(468,'forma.systemSettings.inscription.testDomain',1,'Test domain'),(469,'forma.systemSettings.inscription.testDomain',3,'Тестовий домен'),(480,'forma.systemSettings.inscription.settings',1,'General settings'),(481,'forma.systemSettings.inscription.settings',3,'Загальні налаштування'),(482,'forma.reference.inscription.tasks',1,'Tasks'),(483,'forma.reference.inscription.tasks',3,'Завдання'),(484,'forma.reference.inscription.users',1,'Users'),(485,'forma.reference.inscription.users',3,'Користувачі'),(488,'forma.reference.inscription.languages',1,'Languages'),(489,'forma.reference.inscription.languages',3,'Мова'),(494,'global.nav.action',1,'Menu section'),(495,'global.nav.action',3,'Розділ меню'),(498,'global.nav.out',1,'Log out'),(499,'global.nav.out',3,'Вихід'),(500,'forma.authorization.inscription.login',1,'Login'),(501,'forma.authorization.inscription.login',3,'Логін'),(502,'forma.authorization.button.login',1,'Get dynamic password'),(503,'forma.authorization.button.login',3,'Отримати динамічний пароль'),(512,'forma.authorization.inscription.dynamicPassword',1,'Dynamic password'),(513,'forma.authorization.inscription.dynamicPassword',3,'Динамічний пароль'),(514,'forma.authorization.button.authorize',1,'Log in'),(515,'forma.authorization.button.authorize',3,'Авторизуватися'),(516,'forma.authorization.button.didNotReceivePassword',1,'Did not receive dynamic password'),(517,'forma.authorization.button.didNotReceivePassword',3,'Не отримали динамічний пароль'),(622,'modalWindow.inscription.info',1,'Information'),(623,'modalWindow.inscription.info',3,'Інформація'),(634,'modalWindow.inscription.noDataToSave',1,'No data to save'),(635,'modalWindow.inscription.noDataToSave',3,'Немає даних для збереження'),(638,'modalWindow.inscription.fillRequiredFields',1,'Please fill in the fields highlighted in red.'),(639,'modalWindow.inscription.fillRequiredFields',3,'Заповніть поля, виділені червоним кольором.'),(640,'modalWindow.button.ok',1,'OK'),(641,'modalWindow.button.ok',3,'Добре'),(644,'modalWindow.inscription.question',1,'Question'),(645,'modalWindow.inscription.question',3,'Питання'),(658,'message.authorization.dynamicPasswordNotSpecified',1,'Dynamic password is not specified'),(659,'message.authorization.dynamicPasswordNotSpecified',3,'Динамічний пароль не вказано'),(660,'message.authorization.invalidPassword',1,'Invalid password'),(661,'message.authorization.invalidPassword',3,'Невірний пароль'),(662,'message.authorization.invalidLogin',1,'Invalid login'),(663,'message.authorization.invalidLogin',3,'Невірний логін'),(728,'message.system.save',1,'Saved!'),(729,'message.system.save',3,'Сбережено!'),(732,'message.dataProvider.NoDataToSave',1,'No data to save'),(733,'message.dataProvider.NoDataToSave',3,'Немає даних для збереження'),(734,'message.system.EmailSuccessfullySent',1,'Email successfully sent %s'),(735,'message.system.EmailSuccessfullySent',3,'Email успішно надіслано %s'),(738,'message.system.NoMessengersAvailableToSendMessage',1,'No messengers available to send message'),(739,'message.system.NoMessengersAvailableToSendMessage',3,'Немає доступних месенджерів для надсилання повідомлення'),(740,'message.system.MessageWasNotSentThroughAnyCommunicationChannel',1,'Message was not sent through any communication channel.'),(741,'message.system.MessageWasNotSentThroughAnyCommunicationChannel',3,'Повідомлення не було надіслано через жоден канал звʼязку.'),(798,'message.system.ViberSuccessfullySent',1,'Viber successfully sent'),(799,'message.system.ViberSuccessfullySent',3,'Viber успішно надіслано'),(800,'message.system.ViberFileSuccessfullySent',1,'Viber file successfully sent'),(801,'message.system.ViberFileSuccessfullySent',3,'Файл у Viber успішно надіслано'),(804,'message.system.CurlError',1,'cURL Error: %s'),(805,'message.system.CurlError',3,'Помилка cURL: %s'),(806,'message.system.TelegramSuccessfullySent',1,'Telegram successfully sent'),(807,'message.system.TelegramSuccessfullySent',3,'Telegram успішно надіслано'),(808,'message.system.TelegramFileSuccessfullySent',1,'Telegram file successfully sent'),(809,'message.system.TelegramFileSuccessfullySent',3,'Файл у Telegram успішно надіслано'),(810,'message.messengerBot.unknownTelegramError',1,'Unknown Telegram Error: %s'),(811,'message.messengerBot.unknownTelegramError',3,'Невідома помилка Telegram: %s'),(850,'message.wayForPay.invalidLogin',1,'Invalid login'),(851,'message.wayForPay.invalidLogin',3,'Невірний логін'),(918,'global.message',1,'List of unsaved data'),(919,'global.message',3,'Список не збереженних данних'),(991,'generalFunctions.message.emailNotProvided',3,'Необхідно вказати хоча б одну адресу електронної пошти одержувача.'),(1096,'global.nav.selectDevice',1,'Select device'),(1097,'global.nav.selectDevice',3,'Обрати пристрій'),(1098,'forma.reference.inscription.devices',1,'Devices'),(1099,'forma.reference.inscription.devices',3,'Пристрої'),(1100,'table.devices.col.deviceName',1,'Device name'),(1101,'table.devices.col.deviceName',3,'Ім\'я пристрою'),(1102,'table.devices.col.isDelete',1,'Delete'),(1103,'table.devices.col.isDelete',3,'Видалена'),(1104,'table.devices.col.isDefault',1,'Default'),(1105,'table.devices.col.isDefault',3,'За замовчуванням'),(1106,'table.deviceEvents.col.id',1,'Event'),(1107,'table.deviceEvents.col.id',3,'Подія'),(1110,'table.deviceEvents.col.eventTime',1,'Event time'),(1111,'table.deviceEvents.col.eventTime',3,'Час подіі'),(1112,'table.deviceEvents.col.sceneScore',1,'Lighting change'),(1113,'table.deviceEvents.col.sceneScore',3,'Зміна освітлення'),(1114,'table.deviceEvents.col.isPhotoReady',1,'loaded'),(1115,'table.deviceEvents.col.isPhotoReady',3,'Завантажено'),(1116,'table.deviceEvents.col.isHumanChecked',1,'Object present'),(1117,'table.deviceEvents.col.isHumanChecked',3,'Об\'єкт присутній'),(1126,'global.button.close',1,'Close'),(1127,'global.button.close',3,'Закрити'),(1128,'global.menu.showFoto',1,'Show foto'),(1129,'global.menu.showFoto',3,'Показати фото'),(1130,'enumeration.yesNoPending.pending',1,'Pending'),(1131,'enumeration.yesNoPending.pending',3,'Не відомо');


/*!40000 ALTER TABLE `localization` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `generalSettings`
--

LOCK TABLES `generalSettings` WRITE;
/*!40000 ALTER TABLE `generalSettings` DISABLE KEYS */;
INSERT INTO `generalSettings` VALUES (1,'https://','ssl://','*****','*****@*****.com','*****','https://','*****','#sudo nano /etc/systemd/system/camsboard_device-@.service\n\n[Unit]\nDescription=FFmpeg Camera %i Stream Processor and Archiver1\nAfter=network.target\n\n[Service]\nType=simple\nUser=www-data\nGroup=www-data\n\nEnvironmentFile=/etc/default/camsboard_device-%i\n\nStandardOutput=journal\nStandardError=journal\nExecStartPre=/bin/bash -c \'rm -f /tmp/scene-%i.fifo && mkfifo -m 660 /tmp/scene-%i.fifo\'\n\nExecStart=/bin/bash -c \'/usr/bin/php /var/www/camsboard/src/FFmpegScene.php %i /tmp/scene-%i.fifo & \\\n    stdbuf -o0 -eL /usr/bin/ffmpeg \\\n    -rtsp_transport tcp \\\n    -timeout 5000000 \\\n    ${FFMPEG_INPUT_OPTS} \\\n    -an \\\n    -loglevel repeat+level+error \\\n    -i \"rtsp://127.0.0.1:8554/${STREAM_KEY}\" \\\n    -filter_complex \"[0:v]fps=${FPS},split=2[out1][v2]; [v2]select=gt(scene\\\\,${SCENE_THRESHOLD}),metadata=print:file=/tmp/scene-%i.fifo:direct=1[out2]\" \\\n    -map \"[out1]\" -q:v 1 -fps_mode vfr -f image2pipe -vcodec mjpeg - \\\n    -map \"[out2]\" -f null - \\\n    2> >(/usr/bin/php /var/www/camsboard/src/FFmpegStderr.php %i) \\\n    | /usr/bin/php /var/www/camsboard/src/FFmpegStdout.php %i\'\n\nRestart=always\nRestartSec=60s\n\n[Install]\nWantedBy=multi-user.target','#sudo nano /etc/systemd/system/camsboard_detect.service\n[Unit]\nDescription=Camsboard YOLO Memory Service\nAfter=network.target\n\n[Service]\nType=simple\nWorkingDirectory=/var/www/camsboard/src\nExecStart=/var/www/camsboard/venv/bin/python3 /var/www/camsboard/src/detect.py\nRestart=always\nRestartSec=60s\nNice=10\n\n[Install]\nWantedBy=multi-user.target','#sudo nano /etc/systemd/system/camsboard_timer@.service\n[Unit]\nDescription=Camsboard Executer for Task: %I\nAfter=network.target\n\n[Service]\nType=oneshot\nExecStart=/usr/bin/php /var/www/camsboard/src/%I.php\nUser=root\nTimeoutStartSec=60s','#sudo nano /etc/systemd/system/cleaner.timer\n\n[Unit]\nDescription=Cleaner Timer\n\n[Timer]\nOnBootSec=30sec\nOnUnitActiveSec=1min\nAccuracySec=1ms\nUnit=camsboard_timer@Cleaner.service\n\n[Install]\nWantedBy=timers.target','#sudo nano /etc/systemd/system/copier.timer\n\n[Unit]\nDescription=Copier Timer\n\n[Timer]\nOnBootSec=5sec\nOnUnitActiveSec=15sec\nAccuracySec=1ms\nUnit=camsboard_timer@Copier.service\n\n[Install]\nWantedBy=timers.target','#sudo nano /etc/systemd/system/detector.timer\n\n[Unit]\nDescription=Detector Timer\n\n[Timer]\nOnBootSec=10sec\nOnUnitActiveSec=15sec\nAccuracySec=1ms\nUnit=camsboard_timer@Detector.service\n\n[Install]\nWantedBy=timers.target','#sudo nano /etc/systemd/system/tester.timer\n\n[Unit]\nDescription=Tester Timer\n\n[Timer]\nOnBootSec=10sec\nOnUnitActiveSec=10sec\nAccuracySec=1ms\nUnit=camsboard_timer@Tester.service\n\n[Install]\nWantedBy=timers.target');
/*!40000 ALTER TABLE `generalSettings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `testers`
--

LOCK TABLES `testers` WRITE;
/*!40000 ALTER TABLE `testers` DISABLE KEYS */;
INSERT INTO `testers` VALUES (1,'2026-11-20 16:50:40','detection','OK',60,'{}',0,1),(2,'2026-11-20 16:50:49','deviceOnline','ERR',60,'{}',0,1),(3,'2026-11-20 16:50:59','cleaner','OK',60,'{}',0,1);
/*!40000 ALTER TABLE `testers` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `devices` VALUES (1,'cam 1',0,1,'Address 1','aiuJkk2WIM',1200,1600,1,0.040,1);

-- Dump completed on 2026-11-20 18:07:37

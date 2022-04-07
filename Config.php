<?php

CONST CLIENT_ID = '4c87f242b0004df492e0b4322c8ac9c4';
CONST CLIENT_SECRET = '3b09da34d6b54933986ee72c478cf863';

// настройка для подключения в БД
CONST NAME_DB = 'test';
CONST USER_DB = 'root';
CONST PASSWORD_DB = '';
CONST HOST_DB = 'localhost';

// настройка для создания дампов
CONST LOCAL_PATH = 'backup_db/';
CONST DUMP_NAME = 'dump.sql';

// настройка для крона
CONST QTY = 10;
CONST INTERVAL = 3;
CONST TIME = 'minutes';
CONST HOURLY = 'hours';
CONST DAILY = 'day';
CONST WEEKLY = 'week';
CONST MONTHLY = 'month';

// настройка для zip арихва
CONST DIR_NAME = 'backup';
CONST ZIP_NAME = 'output.zip';
CONST DIRECTORY_NAME = 'files_for_backup';
CONST FLAGS = ZipArchive::CREATE;
CONST LOCAL_NAME = '';
CONST EXTENSION = [];
CONST DIRECTORY = [];

// настройка для Яндекс Диска
CONST LIMIT_BACKUP = '5';
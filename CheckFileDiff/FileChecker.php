<?php

//require_once '../Config.php';

// включить в сканирование файлы со следующими расширениями. Если массив пустой, то ищем сканируем всё
$arrayIncludeExtension = [];

// папочка, которую контролируем (включая подпапки)
$rootdir = __DIR__ . '/../files_for_backup/';

// куда кладём инфу о изменениях
$diffdir = "\\Diff\\";

include_once("dBug.php"); // этот дебаг класс для отображения расхождений, берём тута  http://dbug.ospinto.com

// если id задан смотрим старые изменения по номеру
if (isset($_GET['id'])) {
	$id = $_GET['id'];
	$buf = file_get_contents($diffdir.$id);
	new dBug(unserialize($buf));
	die();
}
var_dump(__DIR__);
if (!rename(__DIR__ . $diffdir . "curr.txt", __DIR__ . $diffdir."old.txt")) {
	dir("rename error");
}

$fp = fopen(__DIR__ .$diffdir."curr.txt","a+");
//собираем длинны всех файлов указанной директории $rootdir
function executeDiff() {
    global $rootdir;
    global $fp;
    checkmd($rootdir);
    fclose($fp);
    var_dump(array_diff(file(__DIR__ . '/Diff/old.txt'), file(__DIR__ . '/Diff/curr.txt')));
}

// непосредственно сравнивает два файла (средствами команды ОС diff)
//exec("diff ".$diffdir."curr.txt ".$diffdir."old.txt >".$diffdir."diff.txt");
//exec("fc Diff\curr.txt Diff\old.txt > Diff\diff.txt");

// обрабатываем и выплёвываем в удобный нам вид результат предыдущей команды
$arr = file(__DIR__ . $diffdir."diff.txt");
unset($arr[0]);
foreach ($arr as $ar) {
	$str = trim($ar," \r\n\t");
	@list($dir,$len,$file) = explode(" ",$str);
	if (!isset($file)) continue;
	// пропускаем файлы из папки diffstat
	if (strstr($file,$diffdir)) continue;

	$diff[$file][$dir] = $len;
	$message.=$file."\t".$dir." ".$len."\n";
}

// функция сбора информации о файлах в директории
function checkmd($cat) {
	global $fp;
	$dir = dir($cat);
	while($file = $dir->read()) {
		if ($file=='.' or $file=='..') continue;
		if (is_dir($cat.$file)) {
			checkmd($cat.$file.'/'/*,$arrayIncludeExtension*/);
		}
		//включаем в сканирование только файлы с расширениями из массива
		//if ((count($arrayIncludeExtension)==0)||!in_array(pathinfo($file, PATHINFO_EXTENSION),$arrayIncludeExtension)) { continue; }
		
		$md5 = filesize($cat.$file);
		fwrite($fp,$md5." ".$cat.$file."\n");

	}
}
?>
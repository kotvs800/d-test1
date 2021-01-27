<?php

/**
 * Поиск реализован без учета регистра и расширения файла
 * scandir и opendir самые быстрые
 * exec(find) - небезапасно, долго и зависити от ОС
 * RecursiveDirectoryIterator чуть дольше, чем scandir, но зато более элегантный код
 *
 */

function getScanDirCount(string $dir, string $fileName): int
{
    $count = 0;

    if ($entries = scandir($dir)) {
        foreach ($entries as $entry) {
            if (($entry === '..') || ($entry === '.')) {
                continue;
            }
            $fullEntry = $dir . '/' . $entry;
            if (is_dir($fullEntry)) {
                $count += getScanDirCount($fullEntry, $fileName);
            } else {
                $pathInfo = pathinfo($fullEntry);
                if (isset($pathInfo['filename']) && (strcasecmp($pathInfo['filename'], $fileName) === 0)) {
                    $content = file_get_contents($fullEntry);
                    $count += (int) $content;
                }
            }
        }
    }

    return $count;
}

function getOpenDirCount(string $dir, string $fileName): int
{
    $count = 0;

    if ($handle = opendir($dir)) {
        while(($entry = readdir($handle)) !== false) {
            if (($entry === '..') || ($entry === '.')) {
                continue;
            }
            $inEntry = $dir . '/' . $entry;
            if (is_dir($inEntry)) {
                $count += getOpenDirCount($inEntry, $fileName);
            } else {
                $pathInfo = pathinfo($inEntry);
                if (isset($pathInfo['filename']) && (strcasecmp($pathInfo['filename'], $fileName) === 0)) {
                    $content = file_get_contents($inEntry);
                    $count += (int) $content;
                }
            }
        }
        closedir($handle);
    }

    return $count;
}

function getFindExecCount(string $dir, string $fileName): int
{
    $entries = [];
    $count = 0;

    exec("find $dir -type f -iname $fileName -o -iname $fileName.*", $entries);

    if (!empty($entries)) {
        foreach ($entries as $entry) {
            $content = file_get_contents($entry);
            $count += (int) $content;
        }
    }

    return $count;
}

function getDirectoryIterator(string $dir, string $fileName): int
{
    $dirIterator = new RecursiveDirectoryIterator($dir);
    $entryIterator = new RecursiveIteratorIterator($dirIterator);
    $count = 0;

    foreach ($entryIterator as $file) {
        if (($file instanceof SplFileInfo) && ($file->getType() === 'file')) {
            $pathInfo = pathinfo($file->getFilename());
            if (isset($pathInfo['filename']) && (strcasecmp($pathInfo['filename'], $fileName) === 0)) {
                $content = file_get_contents($file->getPathname());
                $count += (int) $content;
            }
        }
    }

    return $count;
}

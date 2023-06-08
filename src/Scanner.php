<?php
namespace Lubed\FileSystem;
class Scanner {
    const CURRENT_DIR='current';
    const BFS='bfs';
    const DFS='dfs';
    const EXCLUDE_DIR='exclude';
    const TYPE_DIR='dir';
    const TYPE_FILE='file';
    private static $exclude_dirs=[];

    public static function glob(string $path,?Matcher $matcher,string $strategy=FSScanner::DFS) {
        if (!is_dir($path) || !$matcher) {
            FSExceptions::invalidArgument(sprintf('invalid %s or %s.', $path, $pattern), [
                'class'=>__CLASS__,
                'method'=>__METHOD__
            ]);
        }
        $files=self::scan($path, $strategy);
        $result=[];
        foreach ($files as $file) {
            if (false === $matcher->match($file)) {
                continue;
            }
            $result[]=$file;
        }
        return $result;
    }

    public static function scan(string $path, string $strategy=FSScanner::CURRENT_DIR,
        bool $is_exclude_dir=true) : array {
        if (!is_dir($path)) {
            FSExceptions::invalidArgument(sprintf('invalid %s.', $path), [
                'class'=>__CLASS__,
                'method'=>__METHOD__
            ]);
        }
        switch ($strategy) {
        case self::CURRENT_DIR:
            $files=self::scanCurrentDir($path, $is_exclude_dir);
            break;
        case self::BFS:
            $files=self::scanBfs($path, $is_exclude_dir);
            break;
        case self::DFS:
            $files=self::scanDfs($path, $is_exclude_dir);
            break;
        default:
            FSExceptions::invalidArgument(sprintf('invalid strategy(%s).', $strategy), [
                'class'=>__CLASS__,
                'method'=>__METHOD__
            ]);
        }
        return $files;
    }

    public static function formatPath(string $path) : string {
        if ('/' === substr($path, -1)) {
            return $path;
        }
        return $path . '/';
    }

    public static function basename(array $paths, string $suffix='') : array {
        if (!$paths) {
            return [];
        }
        $ret=[];
        foreach ($paths as $path) {
            $ret[]=basename($path, $suffix);
        }
        return $ret;
    }

    public static function setExcludeDirs(array $dirs) {
        self::$exclude_dirs=$dirs;
    }

    private static function scanCurrentDir(string $path, bool $is_exclude_dir=false) : array {
        $path=self::formatPath($path);
        if (self::$exclude_dirs) {
            $find=in_array($path, self::$exclude_dirs);
            if ($find) {
                return [];
            }
        }
        $dh=opendir($path);
        if (!$dh) {
            return [];
        }
        $files=[];
        while (false !== ($file=readdir($dh))) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            $file_type=filetype($path . $file);
            if (self::TYPE_DIR === $file_type && false === $is_exclude_dir) {
                $files[]=$path . $file . '/';
            }
            if (self::TYPE_FILE === $file_type) {
                $files[]=$path . $file;
            }
        }
        closedir($dh);
        return $files;
    }

    private static function scanBfs(string $path, bool $is_exclude_dir=true) : array {
        $files=[];
        $queue=new \SplQueue;
        $queue->enqueue($path);
        while (!$queue->isEmpty()) {
            $file=$queue->dequeue();
            $file_type=filetype($file);
            if (self::TYPE_DIR == $file_type) {
                $sub_files=self::scanCurrentDir($file, false);
                foreach ($sub_files as $sub_file) {
                    $queue->enqueue($sub_file);
                }
                if (false === $is_exclude_dir && $file != $path) {
                    $files[]=$file;
                }
            }
            if (self::TYPE_FILE == $file_type) {
                $files[]=$file;
            }
        }
        return $files;
    }

    private static function scanDfs(string $path, bool $is_exclude_dir=true) : array {
        $files=[];
        $sub_files=self::scanCurrentDir($path, false);
        foreach ($sub_files as $sub_file) {
            $file_type=filetype($sub_file);
            if (self::TYPE_DIR == $file_type) {
                $inner_files=self::scanDfs($sub_file, $is_exclude_dir);
                foreach ($inner_files as $row) {
                    $files[]=$row;
                }
                if (false === $is_exclude_dir) {
                    $files[]=$sub_file;
                }
            }
            if (self::TYPE_FILE == $file_type) {
                $files[]=$sub_file;
            }
        }
        return $files;
    }
}

<?php
namespace Lubed\FileSystem;

use FilesystemIterator;
use Error;
use Exception;

final class FSO {
    public function exists($path) : bool {
        return file_exists($path);
    }

    public function get($path) {
        if ($this->isFile($path)) {
            return file_get_contents($path);
        }
        FSExceptions::fileNotFound(sprintf("File does not exist at path %s",$path), [
            'class'=>__CLASS__,
            'method'=>__METHOD__
        ]);
    }

    public function getRequire($path) {
        if ($this->isFile($path)) {
            return require $path;
        }
        FSExceptions::fileNotFound(sprintf("File does not exist at path",$path), [
            'class'=>__CLASS__,
            'method'=>__METHOD__
        ]);
    }

    public function put($path, $contents, $lock=false) {
        return file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
    }

    public function prepend($path, $data) {
        if ($this->exists($path)) {
            return $this->put($path, $data . $this->get($path));
        }
        return $this->put($path, $data);
    }

    public function append($path, $data) {
        return file_put_contents($path, $data, FILE_APPEND);
    }

    public function delete($paths) {
        $paths=is_array($paths) ? $paths : func_get_args();
        $success=true;
        foreach ($paths as $path) {
            try {
                if (!@unlink($path)) {
                    $success=false;
                }
            } catch(Error|Exception $e) {
                $success=false;
            }
        }
        return $success;
    }

    public function move($path, $target) {
        return rename($path, $target);
    }

    public function copy($path, $target) {
        return copy($path, $target);
    }

    public function name($path) {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    public function extension($path) {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    public function type($path) {
        return filetype($path);
    }

    public function mimeType($path) {
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
    }

    public function size($path) {
        return filesize($path);
    }

    public function lastModified($path) {
        return filemtime($path);
    }

    public function isDirectory($directory) {
        return is_dir($directory);
    }

    public function isWritable($path) {
        return is_writable($path);
    }

    public function isFile($file) {
        return is_file($file);
    }

    public function glob($pattern, $flags=0) {
        return glob($pattern, $flags);
    }

    public function files($directory) {
        $glob=glob($directory . '/*');
        if ($glob === false) {
            return [];
        }
        return array_filter($glob, function($file) {
            return filetype($file) == 'file';
        });
    }

    public function makeDirectory($path, $mode=0755, $recursive=false, $force=false) {
        if ($force) {
            return @mkdir($path, $mode, $recursive);
        }
        return mkdir($path, $mode, $recursive);
    }

    public function copyDirectory($directory, $destination, $options=null) {
        if (!$this->isDirectory($directory)) {
            return false;
        }
        $options=$options ?: FilesystemIterator::SKIP_DOTS;
        if (!$this->isDirectory($destination)) {
            $this->makeDirectory($destination, 0777, true);
        }
        $items=new FilesystemIterator($directory, $options);
        foreach ($items as $item) {
            $target=$destination . '/' . $item->getBasename();
            if ($item->isDir()) {
                $path=$item->getPathname();
                if (!$this->copyDirectory($path, $target, $options)) {
                    return false;
                }
            } else {
                if (!$this->copy($item->getPathname(), $target)) {
                    return false;
                }
            }
        }
        return true;
    }

    public function deleteDirectory($directory, $preserve=false) {
        if (!$this->isDirectory($directory)) {
            return false;
        }
        $items=new FilesystemIterator($directory);
        foreach ($items as $item) {
            if ($item->isDir() && !$item->isLink()) {
                $this->deleteDirectory($item->getPathname());
            } else {
                $this->delete($item->getPathname());
            }
        }
        if (!$preserve) {
            @rmdir($directory);
        }
        return true;
    }

    public function cleanDirectory($directory) {
        return $this->deleteDirectory($directory, true);
    }
}
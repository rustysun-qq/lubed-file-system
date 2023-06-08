<?php
nsmespace Lubed\FileSystem;

use Lubed\Supports\Resource;

class FSResource implements Resource {
    const SCHEME='file://';
    const SCHEME_LEN=7;

    protected $filename;
    protected $fd;
    protected $context;

    public function __construct(string $filename, $context=null) {
        $filename=str_replace(self::SCHEME, '', $filename);
        $realPath=realpath($filename);
        $this->filename=$realPath ? $realPath : $filename;
        $this->fd=false;
        $this->context=$context;
    }

    public function exists() : bool {
        return false !== $this->filename && file_exists($this->filename);
    }

    public function isOpen() : bool {
        return false !== $this->fd;
    }

    public function getURL() : string {
        return self::SCHEME . $this->filename;
    }

    public function getStream() {
        if (false !== $this->fd ) {
            return $this->fd;
        }
        $paras=[$this->getURL(), 'r', false];

        if(false !== $this->context)
        {
            $paras[]=$this->context;
        }
        $this->fd = @fopen(...$params);
        if (false === $this->fd) {
            FSExceptions::invalidResource(sprintf('Could not open: %s', $this->filename));
        }
        return $this->fd;
    }

    public function createRelative(string $relativePath):self {
        $filename = sprintf('%s%s%s%s',self::SCHEME, $this->getFilename(), DIRECTORY_SEPARATOR,$relativePath);
        return new FSResource($filename);
    }

    public function getFilename() : string {
        return $this->filename;
    }
}

<?php


namespace Christophedlr\FileFormats\Quake;


/**
 * Read PAK archive file
 * @package Christophedlr\FileFormats\Quake
 */
class PakReader
{
    /**
     * @var string
     */
    private $filename;

    /**
     * @var array
     */
    private $header;

    /**
     * @var array
     */
    private $files;

    /**
     * @var resource
     */
    private $handle;

    public function __construct()
    {
        $this->filename = '';
        $this->header = [];
        $this->files = [];
    }

    public function __destruct()
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
    }

    /**
     * Load archive
     * @param string $filename
     * @throws \Exception
     */
    public function load(string $filename)
    {
        if (!file_exists($filename)) {
            throw new \Exception('The <b>'.$filename.'</b> does not exist');
        }

        $this->filename = $filename;
        $this->handle = fopen($filename, 'rb');

        $this->readArchiveHeader();
        $this->readFiles();
    }

    /**
     * Extract selected file
     * @param string $filename
     * @param string $destination
     */
    public function extract(string $filename, string $destination)
    {
        foreach ($this->files as $file) {
            if ($file['name'] === $filename) {
                $pathInfo = pathinfo($file['name']);
                $dirname = $destination;

                if ($pathInfo['dirname'] !== '.') {
                    $dirname .= '/'.$pathInfo['dirname'];
                }

                @mkdir($dirname, '0755', true);
                fseek($this->handle, $file['offset'], SEEK_SET);

                $data = fread($this->handle, $file['size']);

                $dest = fopen($dirname.'/'.$pathInfo['basename'], 'wb');
                fwrite($dest, $data);
                fclose($dest);

                echo '<b>'.$filename.'</b> has been extracted to <b>'.$destination.'</b>';

                break;
            }
        }
    }

    /**
     * Read header of archive
     * @throws \Exception
     */
    private function readArchiveHeader()
    {
        rewind($this->handle);
        $data = fread($this->handle, 4);

        if ($data !== 'PACK') {
            throw new \Exception('Invalid PAK file');
        }

        $this->header['id'] = $data;
        $this->header['offset'] = unpack('V', fread($this->handle, 4))[1];
        $this->header['size'] = unpack('V', fread($this->handle, 4))[1];
    }

    /**
     * Read header of files
     */
    private function readFiles()
    {
        $countFiles = $this->header['size']/64;
        fseek($this->handle, $this->header['offset'], SEEK_SET);

        for ($i = 0; $i < $countFiles; $i++) {
            $array = [];

            $array['name'] = trim(unpack('a*', fread($this->handle, 56))[1]);
            $array['offset'] = unpack('V', fread($this->handle, 4))[1];
            $array['size'] = unpack('V', fread($this->handle, 4))[1];

            $this->files[] = $array;
        }
    }
}

<?php
namespace Rocker\Utils\FileStorage;

use Aws\S3\S3Client;
use Guzzle\Http\Client as HttpClient;
use Rocker\Utils\ErrorHandler;
use Rocker\Utils\FileStorage\Image\ImageModifier;


/**
 * Class that can store files locally
 *
 * @package PHP-Rocker
 * @author Victor Jonsson (http://victorjonsson.se)
 * @license MIT license (http://opensource.org/licenses/MIT)
 */
class S3 extends Storage {

    /**
     * @var S3Client
     */
    public $client;

    /**
     * @var string
     */
    protected $bucket;

    /**
     * @var string
     */
    protected $baseURI;

    /**
     * @param array $config
     */
    public function __construct($config) {
        $this->client = S3Client::factory(array(
                'key' => $config['application.files']['s3_key'],
                'secret' => $config['application.files']['s3_secret']
            ));

        $this->bucket = $config['application.files']['s3_bucket'];
        $this->baseURI = $config['application.files']['s3_endpoint'];

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    function storeFile($file, $name, $mime, array $versions = array())
    {
        $location = $this->putFileOnAmazon($file, $name, $mime);

        $fileData = array(
            'location' => $location,
            'size' => $this->getRemoteFileSize($location),
            'name' => $name,
            'extension' => pathinfo($name, PATHINFO_EXTENSION),
            'mime' => $mime
        );

        $versionData = null;
        if( Storage::isImage($fileData['extension']) ) {
            $tmpFile = tempnam(sys_get_temp_dir(), (string)time());
            if( is_resource($file) ) {
                rewind($file);
                $content = stream_get_contents($file);
            } else {
                $content = file_get_contents($file);
            }
            file_put_contents($tmpFile, $content);
            $data = @getimagesize($tmpFile);
            if( !$data ) {
                throw new \Exception('Unable to analyze image with getimagesize()');
            }
            $fileData['width'] = $data[0];
            $fileData['height'] = $data[1];
            $versionData = $this->generateImageVersions($versions, $fileData['extension'], $fileData['size'], $tmpFile, basename($name));
            $tmpDir = dirname($tmpFile);
            $fileDir = dirname($name);
            if( is_array($versionData) ) {
                foreach($versionData as $name => $fileName) {
                    $this->putFileOnAmazon($tmpDir.'/'.$fileName, $fileDir.'/'.$fileName, $mime);
                }
            }
        }

        if( !empty($versionData) ) {
            $fileData['versions'] = $versionData;
        }

        if( empty($data['mime']) )
            $data['mime'] = 'text/plain';

        return $fileData;
    }

    /**
     * @param string $location
     * @return int
     */
    private function getRemoteFileSize($location)
    {
        $parts = parse_url($location);
        $http = new HttpClient($parts['scheme'] .'://'. $parts['host']);
        $response = $http->head($parts['path'])->send();
        return (int)current($response->getHeader('Content-Length')->toArray());
    }

    /**
     * @param $file
     * @param $name
     * @param $mime
     * @return string
     */
    protected function putFileOnAmazon($file, $name, $mime)
    {
        $arr = array(
            'ACL' => 'public-read',
            'Bucket' => $this->bucket,
            'Key' => $name,
            'Body' => is_resource($file) ? $file:file_get_contents($file),
            'ValidateMD5' => false,
            'ContentMD5' => false,
            'ContentType' => $mime
        );

        /* @var \Guzzle\Service\Resource\Model $obj */
        $obj = $this->client->putObject($arr);

        return str_replace('https://', 'http://', $obj->get('ObjectURL'));
    }

    /**
     * @param string $name
     */
    protected function removeFileFromAmazon($name)
    {
        $this->client->deleteObject(array(
                'Bucket'=> $this->bucket,
                'Key' => $name
            ));
    }

    /**
     * @param $name
     * @return void
     */
    function removeFile($name)
    {
        $this->removeFileFromAmazon($name);
    }

    /**
     * Remove image versions
     * @param string $name
     * @param array $versions
     */
    function removeVersions($name, array $versions)
    {
        $dir = dirname($name);
        foreach($versions as $ver) {
            $this->removeFileFromAmazon( $dir.'/'.$ver );
        }
    }

    /**
     * @see \Rocker\Utils\FileStorage\Image\ImageModifier::create()
     * @param string $name
     * @param string $sizeName eg. 300x200 400x100
     * @return string|bool Base name of file or false if failed
     */
    function generateVersion($name, $sizeName)
    {
        $remoteFile = $this->baseURI .'/'. $name;
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        if( self::isImage($extension) ) {
            $tmpFile = tempnam(sys_get_temp_dir(), (string)time());
            file_put_contents($tmpFile, file_get_contents($remoteFile));
            if( filesize($tmpFile) < $this->maxImageSize && $this->hasAllowedDimension($tmpFile) ) {
                $versionGenerator = new ImageModifier($tmpFile);
                $version = $versionGenerator->create($sizeName,  $this->versionQuality);
                return basename($version);
            }
        }
        return false;
    }
}
<?php

namespace Modules\Common\Services;

use Cloudinary\Api\ApiResponse;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Api\Exception\ApiError;
use Illuminate\Support\Facades\Log;

class CloudImageService
{
    protected $cloud_name;
    protected $api_key;
    protected $api_secret;

    public function __construct()
    {
        $this->cloud_name = env('CLOUDINARY_CLOUD_NAME');
        $this->api_key = env('CLOUDINARY_API_KEY');
        $this->api_secret = env('CLOUDINARY_API_SECRET');
        // Load Cloudinary configuration from environment variables
        Configuration::instance([
            'cloud' => [
                'cloud_name' => $this->cloud_name,
                'api_key' => $this->api_key,
                'api_secret' => $this->api_secret,
            ],
            'url' => [
                'secure' => true,
            ],
        ]);
    }

    /**
     * Upload an image to Cloudinary.
     *
     * @param string $imagePath The local path or URL of the image to upload.
     * @param array $options Optional parameters for the upload.
     * @return ApiResponse The upload result.
     * @throws ApiError If the upload fails.
     */
    public function upload(string $imagePath, array $options = []): ApiResponse
    {
        try {
            $uploadApi = new UploadApi();
            return $uploadApi->upload($imagePath, $options);
        } catch (ApiError $e) {
            // Log the error
            Log::error('Failed to upload image to Cloudinary', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function multipleUpload(array $imagePaths, array $options = []): ApiResponse
    {
        try {
            $uploadApi = new UploadApi();
            return $uploadApi->upload($imagePaths, $options);
        } catch (ApiError $e) {
            // Log the error
            Log::error('Failed to upload image to Cloudinary', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}

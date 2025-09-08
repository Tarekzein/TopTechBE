<?php

namespace Modules\Chat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Chat\Models\Attachment;
use Modules\Common\Services\CloudImageService;

class AttachmentController extends Controller
{
    protected CloudImageService $cloudImageService;

    public function __construct(CloudImageService $cloudImageService)
    {
        $this->cloudImageService = $cloudImageService;
    }

    public function store(Request $request)
    {
        $request->validate([
            'message_id' => 'required|exists:messages,id',
            'file'       => 'required|file|max:10240', // 10 MB max
        ]);

        // رفع الصورة على Cloudinary
        $uploadedFile = $request->file('file')->getRealPath();
        $uploadResult = $this->cloudImageService->upload($uploadedFile, [
            'folder' => 'chat_attachments'
        ]);

        // حفظ في قاعدة البيانات
        return Attachment::create([
            'message_id' => $request->message_id,
            'file_path'  => $uploadResult['secure_url'],  // رابط Cloudinary
            'file_type'  => $request->file('file')->getClientMimeType(),
        ]);
    }
}

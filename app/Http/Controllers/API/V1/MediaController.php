<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MediaController extends Controller
{

    public function __construct()
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v1/media",
     *     summary="Upload a file",
     *     operationId="uploadFile",
     *     tags={"Media"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     description="File to upload",
     *                     property="file",
     *                     type="string",
     *                     format="binary"
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="path",
     *                 type="string",
     *                 example="uploads/filename.jpg"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:102400', // Adjust the max file size as needed
        ]);

        $path = $request->file('file')->store('uploads');

        return response()->json(['path' => $path], 200);
    }

}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IssueResource extends JsonResource
{
    /**
     * @OA\Schema(
     *     schema="IssueResource",
     *     type="object",
     *     @OA\Property(
     *         property="id",
     *         type="integer",
     *         description="Id of the Issue"
     *     ),
     * *   @OA\Property(
     *         property="title",
     *         type="string",
     *         description="Title"
     *     ),
     * *   @OA\Property(
     *         property="description",
     *         type="string",
     *         description="Description"
     *     ),
     * *   @OA\Property(
     *         property="create_date",
     *         type="string",
     *         description="Create date"
     *     ),
     * *   @OA\Property(
     *         property="last_update_date",
     *         type="string",
     *         description="Last update date"
     *     ),
     * *   @OA\Property(
     *         property="due_date",
     *         type="string",
     *         description="Due date"
     *     ),
     * *   @OA\Property(
     *         property="priority",
     *         type="integer",
     *         description="Priority"
     *     ),
     * *   @OA\Property(
     *         property="status",
     *         type="string",
     *         description="Status"
     *     )
     * )
     */

    public function toArray(Request $request): array
    {

        return [
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
        ];

        /*
    return [
    'id' => $this->id,
    'title' => $this->title,
    'description' => $this->description,
    'create_date' => $this->create_date,
    'last_update_date' => $this->last_update_date,
    'due_date' => $this->due_date,
    'priority' => $this->priority,
    'status' => $this->status,
    ];
     */
    }
}

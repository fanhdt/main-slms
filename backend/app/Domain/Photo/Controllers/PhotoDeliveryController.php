<?php

declare(strict_types=1);

namespace App\Domain\Photo\Controllers;

use App\Core\Http\Controllers\ApiController;
use App\Domain\Photo\Requests\ApprovalRequest;
use App\Domain\Photo\Requests\SelectionRequest;
use App\Domain\Photo\Requests\UploadPreviewRequest;
use App\Domain\Photo\Resources\PhotoFileResource;
use App\Domain\Photo\Resources\PhotoProjectResource;
use App\Domain\Photo\Services\PhotoDeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PhotoDeliveryController extends ApiController
{
    public function __construct(
        private readonly PhotoDeliveryService $photoService,
    ) {}

    /**
     * Photographer upload foto preview.
     * POST /photo-projects/{uuid}/previews
     */
    public function uploadPreviews(UploadPreviewRequest $request, string $uuid): JsonResponse
    {
        $project = $this->photoService->findByUuid($uuid);
        $files = $this->photoService->uploadPreviews($project, $request->file('files'));

        return $this->created(PhotoFileResource::collection($files), 'Preview berhasil diupload.');
    }

    /**
     * Editor upload hasil edit (high-res).
     * POST /photo-projects/{uuid}/edited
     */
    public function uploadEdited(UploadPreviewRequest $request, string $uuid): JsonResponse
    {
        $project = $this->photoService->findByUuid($uuid);
        $files = $this->photoService->uploadEdited($project, $request->file('files'));

        return $this->created(PhotoFileResource::collection($files), 'Hasil edit berhasil diupload.');
    }

    /**
     * Customer submit foto pilihan.
     * POST /photo-projects/{uuid}/selection
     */
    public function submitSelection(SelectionRequest $request, string $uuid): JsonResponse
    {
        $project = $this->photoService->findByUuid($uuid, ['booking.user', 'lab', 'files']);
        $this->photoService->assertAccess($project, $request->user());

        $project = $this->photoService->submitSelection(
            $project,
            $request->input('photo_file_uuids'),
            $request->input('customer_note')
        );

        return $this->success(new PhotoProjectResource($project), 'Pilihan foto berhasil disimpan.');
    }

    /**
     * Editor menandai project siap direview customer.
     * POST /photo-projects/{uuid}/submit-approval
     */
    public function submitForApproval(string $uuid): JsonResponse
    {
        $project = $this->photoService->findByUuid($uuid);
        $project = $this->photoService->submitForApproval($project);

        return $this->success(new PhotoProjectResource($project), 'Project dikirim untuk approval customer.');
    }

    /**
     * Customer approve atau minta revisi.
     * POST /photo-projects/{uuid}/approval
     */
    public function resolveApproval(ApprovalRequest $request, string $uuid): JsonResponse
    {
        $project = $this->photoService->findByUuid($uuid, ['booking.user', 'lab', 'files']);
        $this->photoService->assertAccess($project, $request->user());

        $project = $this->photoService->resolveApproval(
            $project,
            $request->input('decision'),
            $request->input('revision_note')
        );

        $message = $request->input('decision') === 'approve'
            ? 'Hasil foto disetujui, siap didownload.'
            : 'Revisi diminta, dikirim kembali ke editor.';

        return $this->success(new PhotoProjectResource($project), $message);
    }

    /**
     * Customer download 1 file final.
     * GET /photo-projects/{uuid}/files/{fileUuid}/download
     */
    public function downloadFile(Request $request, string $uuid, string $fileUuid): JsonResponse
    {
        $project = $this->photoService->findByUuid($uuid, ['booking.user', 'lab', 'files']);
        $this->photoService->assertAccess($project, $request->user());

        $project = $this->photoService->checkExpiry($project);

        if ($project->status->value !== 'delivered') {
            return $this->error('File belum tersedia untuk didownload atau sudah kadaluarsa.', 422);
        }

        $file = $project->files->firstWhere('uuid', $fileUuid);
        if (!$file) {
            return $this->notFound('File tidak ditemukan.');
        }

        return $this->success([
            'url'      => $file->getTemporaryUrl(30),
            'filename' => $file->filename,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Photo\Services;

use App\Core\Exceptions\ApiException;
use App\Core\Services\BaseService;
use App\Domain\Booking\Models\Booking;
use App\Domain\Notification\Services\NotificationService;
use App\Domain\Photo\Enums\PhotoFileType;
use App\Domain\Photo\Enums\PhotoProjectStatus;
use App\Domain\Photo\Events\PhotoApprovalRequested;
use App\Domain\Photo\Events\PhotoDelivered;
use App\Domain\Photo\Events\PhotoPreviewUploaded;
use App\Domain\Photo\Events\PhotoSelectionSubmitted;
use App\Domain\Photo\Models\PhotoFile;
use App\Domain\Photo\Models\PhotoProject;
use App\Domain\Photo\Models\PhotoSelection;
use App\Domain\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PhotoDeliveryService extends BaseService
{
    private const DEFAULT_RETENTION_DAYS = 14;
    private const DISK = 's3';

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PhotoProject::query()->with(['booking.user', 'lab']);

        if (isset($filters['lab_id'])) {
            $query->where('lab_id', $filters['lab_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function findByUuid(string $uuid, array $with = ['booking.user', 'lab', 'files']): PhotoProject
    {
        $project = PhotoProject::with($with)->where('uuid', $uuid)->first();

        if (!$project) {
            throw ApiException::notFound('Photo project');
        }

        return $project;
    }

    /**
     * Pastikan user berhak akses project ini:
     * - staff lab (punya permission media.view/media.upload DAN terdaftar di lab project ini), ATAU
     * - customer pemilik booking terkait project ini.
     */
    public function assertAccess(PhotoProject $project, User $user): void
    {
        $isStaff = ($user->can('media.view') || $user->can('media.upload'))
            && $user->hasLabAccess($project->lab_id);

        if ($isStaff) {
            return;
        }

        $ownerId = $project->relationLoaded('booking')
            ? $project->booking?->user_id
            : $project->booking()->value('user_id');

        if ($ownerId !== null && (int) $ownerId === (int) $user->id) {
            return;
        }

        throw ApiException::forbidden('Kamu tidak punya akses ke photo project ini.');
    }

    /**
     * Dipanggil admin/operator setelah booking selesai (completed),
     * untuk membuka project foto untuk booking tersebut.
     */
    public function createForBooking(Booking $booking, int $maxSelection = 0): PhotoProject
    {
        $existing = PhotoProject::where('booking_id', $booking->id)->first();
        if ($existing) {
            throw ApiException::conflict('Booking ini sudah punya photo project.');
        }

        return PhotoProject::create([
            'booking_id'    => $booking->id,
            'lab_id'        => $booking->lab_id,
            'status'        => PhotoProjectStatus::Pending,
            'max_selection' => $maxSelection,
        ]);
    }

    public function updateMaxSelection(PhotoProject $project, int $maxSelection): PhotoProject
    {
        $project->update(['max_selection' => $maxSelection]);

        return $project;
    }

    /**
     * Photographer upload foto preview (low-res/watermark).
     *
     * @param  UploadedFile[]  $files
     * @return PhotoFile[]
     */
    public function uploadPreviews(PhotoProject $project, array $files): array
    {
        if (!in_array($project->status, [PhotoProjectStatus::Pending, PhotoProjectStatus::PreviewUploaded], true)) {
            throw ApiException::unprocessable(
                "Tidak bisa upload preview di status: {$project->status->label()}."
            );
        }

        $created = $this->storeFiles($project, $files, PhotoFileType::Preview, function () use ($project) {
            if ($project->status === PhotoProjectStatus::Pending) {
                $project->status = PhotoProjectStatus::PreviewUploaded;
            }
        });

        $project->loadMissing('booking');
        $this->notificationService->notifyUser(
            userId: $project->booking->user_id,
            type: 'PhotoPreviewUploaded',
            title: 'Foto preview sudah siap',
            body: 'Yuk pilih foto favoritmu.',
            data: ['project_uuid' => $project->uuid],
            labId: $project->lab_id,
        );
        event(new PhotoPreviewUploaded($project));

        return $created;
    }

    /**
     * Editor upload hasil edit (high-res).
     *
     * @param  UploadedFile[]  $files
     * @return PhotoFile[]
     */
    public function uploadEdited(PhotoProject $project, array $files): array
    {
        if (!in_array($project->status, [PhotoProjectStatus::Selection, PhotoProjectStatus::Editing, PhotoProjectStatus::Approval], true)) {
            throw ApiException::unprocessable(
                "Tidak bisa upload hasil edit di status: {$project->status->label()}. Customer harus memilih foto dulu."
            );
        }

        return $this->storeFiles($project, $files, PhotoFileType::Edited, function () use ($project) {
            // Balik lagi ke Editing kalau sebelumnya sudah minta approval tapi ada revisi
            $project->status = PhotoProjectStatus::Editing;
        });
    }

    /**
     * Customer submit foto pilihan dari daftar preview.
     */
    public function submitSelection(PhotoProject $project, array $photoFileUuids, ?string $customerNote): PhotoProject
    {
        if ($project->status !== PhotoProjectStatus::PreviewUploaded) {
            throw ApiException::unprocessable(
                "Tidak bisa memilih foto di status: {$project->status->label()}."
            );
        }

        if ($project->max_selection > 0 && count($photoFileUuids) > $project->max_selection) {
            throw ApiException::unprocessable(
                "Maksimal {$project->max_selection} foto yang boleh dipilih, kamu memilih " . count($photoFileUuids) . '.'
            );
        }

        $project = DB::transaction(function () use ($project, $photoFileUuids, $customerNote) {
            $files = PhotoFile::where('project_id', $project->id)
                ->where('type', PhotoFileType::Preview->value)
                ->whereIn('uuid', $photoFileUuids)
                ->get();

            if ($files->count() !== count($photoFileUuids)) {
                throw ApiException::unprocessable('Ada foto yang dipilih tidak ditemukan di project ini.');
            }

            foreach ($files as $file) {
                $file->update(['is_selected' => true]);

                PhotoSelection::updateOrCreate(
                    ['project_id' => $project->id, 'photo_file_id' => $file->id],
                    ['customer_note' => $customerNote, 'selected_at' => now()],
                );
            }

            $project->update([
                'status'           => PhotoProjectStatus::Selection,
                'selection_count'  => $files->count(),
                'customer_note'    => $customerNote,
            ]);

            return $project->fresh(['files', 'selections']);
        });

        $this->notificationService->notifyLabStaff(
            labId: $project->lab_id,
            type: 'PhotoSelectionSubmitted',
            title: 'Customer sudah pilih foto',
            body: "Project {$project->uuid} siap untuk diedit.",
            data: ['project_uuid' => $project->uuid],
        );
        event(new PhotoSelectionSubmitted($project));

        return $project;
    }

    /**
     * Editor menandai hasil edit siap direview customer.
     */
    public function submitForApproval(PhotoProject $project): PhotoProject
    {
        if ($project->status !== PhotoProjectStatus::Editing) {
            throw ApiException::unprocessable(
                "Tidak bisa minta approval di status: {$project->status->label()}."
            );
        }

        if ($project->editedFiles()->count() === 0) {
            throw ApiException::unprocessable('Belum ada hasil edit yang diupload.');
        }

        $project->update(['status' => PhotoProjectStatus::Approval]);

        $project->loadMissing('booking');
        $this->notificationService->notifyUser(
            userId: $project->booking->user_id,
            type: 'PhotoApprovalRequested',
            title: 'Hasil edit siap direview',
            body: 'Cek hasil edit dan berikan persetujuan.',
            data: ['project_uuid' => $project->uuid],
            labId: $project->lab_id,
        );
        event(new PhotoApprovalRequested($project));

        return $project;
    }

    /**
     * Customer approve hasil edit, atau minta revisi.
     */
    public function resolveApproval(PhotoProject $project, string $decision, ?string $revisionNote, int $retentionDays = null): PhotoProject
    {
        if ($project->status !== PhotoProjectStatus::Approval) {
            throw ApiException::unprocessable(
                "Tidak ada yang perlu disetujui di status: {$project->status->label()}."
            );
        }

        if ($decision === 'revise') {
            $project->update([
                'status'       => PhotoProjectStatus::Editing,
                'editor_note'  => $revisionNote,
            ]);

            return $project;
        }

        // decision === 'approve'
        $days = $retentionDays
            ?? $project->lab?->settings['photo_retention_days']
            ?? self::DEFAULT_RETENTION_DAYS;

        // Foto final = salinan referensi dari file "edited" yang sudah di-approve
        $editedFiles = $project->editedFiles;
        foreach ($editedFiles as $file) {
            PhotoFile::create([
                'project_id' => $project->id,
                'type'       => PhotoFileType::Final,
                'filename'   => $file->filename,
                'path'       => $file->path,
                'disk'       => $file->disk,
                'size'       => $file->size,
                'mime_type'  => $file->mime_type,
                'order'      => $file->order,
            ]);
        }

        $project->update([
            'status'      => PhotoProjectStatus::Delivered,
            'expires_at'  => now()->addDays($days),
        ]);

        $project = $project->fresh(['files', 'booking']);

        $this->notificationService->notifyUser(
            userId: $project->booking->user_id,
            type: 'PhotoDelivered',
            title: 'Foto siap didownload',
            body: "Foto akan tersedia sampai {$project->expires_at->translatedFormat('d M Y')}.",
            data: ['project_uuid' => $project->uuid],
            labId: $project->lab_id,
        );
        event(new PhotoDelivered($project));

        return $project;
    }

    /**
     * Cek & tandai expired kalau sudah lewat expires_at.
     * Dipanggil di findByUuid untuk delivery page, dan lewat scheduled command `photo:expire`.
     */
    public function checkExpiry(PhotoProject $project): PhotoProject
    {
        if ($project->status === PhotoProjectStatus::Delivered && $project->isExpired()) {
            $project->update(['status' => PhotoProjectStatus::Expired]);
        }

        return $project;
    }

    /**
     * @param  UploadedFile[]  $files
     * @return PhotoFile[]
     */
    private function storeFiles(PhotoProject $project, array $files, PhotoFileType $type, callable $beforeSave): array
    {
        return DB::transaction(function () use ($project, $files, $type, $beforeSave) {
            $created = [];
            $order = PhotoFile::where('project_id', $project->id)
                ->where('type', $type->value)
                ->max('order') ?? 0;

            foreach ($files as $file) {
                $order++;
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $path = "photo-projects/{$project->uuid}/{$type->value}/{$filename}";

                Storage::disk(self::DISK)->put($path, file_get_contents($file->getRealPath()));

                $created[] = PhotoFile::create([
                    'project_id' => $project->id,
                    'type'       => $type,
                    'filename'   => $file->getClientOriginalName(),
                    'path'       => $path,
                    'disk'       => self::DISK,
                    'size'       => $file->getSize(),
                    'mime_type'  => $file->getMimeType(),
                    'order'      => $order,
                ]);
            }

            $beforeSave();

            $countField = $type === PhotoFileType::Preview ? 'preview_count' : null;
            if ($countField) {
                $project->preview_count = PhotoFile::where('project_id', $project->id)
                    ->where('type', PhotoFileType::Preview->value)
                    ->count();
            }
            $project->save();

            return $created;
        });
    }
}

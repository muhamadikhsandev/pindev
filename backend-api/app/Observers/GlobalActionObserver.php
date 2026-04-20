<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class GlobalActionObserver
{
    /**
     * Handle event saat data baru dibuat
     */
    public function created(Model $model)
    {
        Log::info("!!! OBSERVER TRIGGERED: CREATED !!! Model: " . get_class($model));
        
        if (method_exists($model, 'createLog')) {
            $modelName = class_basename($model);
            $this->executeLog($model, 'CREATE', "Menambahkan data {$modelName}", null, $model->getAttributes());
        }
    }

    /**
     * Handle event saat data diupdate
     */
    public function updated(Model $model)
    {
        Log::info("!!! OBSERVER TRIGGERED: UPDATED !!! Model: " . get_class($model) . " ID: " . $model->id);

        if (method_exists($model, 'createLog')) {
            // getChanges() hanya mengambil kolom yang benar-benar berubah di DB
            $newData = $model->getChanges();
            
            // Abaikan jika hanya updated_at yang berubah (opsional)
            unset($newData['updated_at']);

            if (empty($newData)) {
                Log::info("OBSERVER INFO: Update terdeteksi tapi tidak ada data kolom yang berubah.");
                return;
            }

            $oldData = array_intersect_key($model->getOriginal(), $newData);
            $modelName = class_basename($model);

            $this->executeLog($model, 'UPDATE', "Mengubah data {$modelName}", $oldData, $newData);
        }
    }

    /**
     * Handle event saat data dihapus
     */
    public function deleted(Model $model)
    {
        Log::info("!!! OBSERVER TRIGGERED: DELETED !!! Model: " . get_class($model) . " ID: " . $model->id);
        
        if (method_exists($model, 'createLog')) {
            $modelName = class_basename($model);
            // Mengambil semua data lama sebelum dihapus
            $oldData = $model->getOriginal(); 
            $this->executeLog($model, 'DELETE', "Menghapus data {$modelName}", $oldData, null);
        }
    }

    /**
     * Helper untuk eksekusi log dengan try-catch
     */
    private function executeLog($model, $action, $message, $old, $new)
    {
        try {
            // Pastikan tidak mencatat log aktivitas logging itu sendiri (mencegah rekursif)
            if (class_basename($model) === 'Log') {
                return;
            }

            $model->createLog($action, $message, $old, $new);
            Log::info("OBSERVER SUCCESS: Log {$action} untuk " . get_class($model) . " berhasil.");
        } catch (\Exception $e) {
            Log::error("OBSERVER CRITICAL ERROR: Gagal simpan ke database. Pesan: " . $e->getMessage());
        }
    }
}
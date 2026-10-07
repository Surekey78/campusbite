<?php
require_once APP_ROOT . '/app/Controllers/Controller.php';

final class AccountController extends Controller
{
    public function profile(): void
    {
        $user = require_permission('profile.manage');
        $this->view('account/profile', compact('user') + ['title' => 'My profile']);
    }

    public function profileSave(): void
    {
        $this->postOnly();
        $user = require_permission('profile.manage');
        $userId = (int) $user['id'];
        $name = InputValidator::text($_POST['full_name'] ?? '', 120, 'Full name');
        $phone = InputValidator::text($_POST['phone'] ?? '', 30, 'Phone number', false);
        $phoneValue = $phone !== '' ? $phone : null;
        $changedFields = [];
        if ($name !== (string) $user['full_name']) $changedFields[] = 'full_name';
        if ($phoneValue !== ($user['phone'] ?? null)) $changedFields[] = 'phone';

        $removeValue = $_POST['remove_profile_picture'] ?? '';
        if (!is_string($removeValue) || !in_array($removeValue, ['', '1'], true)) {
            throw new InvalidArgumentException('Choose a valid profile-picture action.');
        }
        $removePicture = $removeValue === '1';
        $file = $_FILES['profile_picture'] ?? null;
        if ($file !== null && !is_array($file)) {
            throw new InvalidArgumentException('Choose a valid profile picture.');
        }
        $uploadError = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if (!is_int($uploadError) && !(is_string($uploadError) && ctype_digit($uploadError))) {
            throw new InvalidArgumentException('The image upload could not be verified.');
        }
        $hasUpload = (int) $uploadError !== UPLOAD_ERR_NO_FILE;
        if ($removePicture && $hasUpload) {
            throw new InvalidArgumentException('Choose a new picture or remove the current one, not both.');
        }

        $db = db();
        $db->begin_transaction();
        $pictureChange = 'unchanged';
        try {
            $existingPhoto = db_one('SELECT media_id FROM user_profile_photos WHERE user_id=? FOR UPDATE', 'i', [$userId]);
            $newMediaId = $hasUpload ? upload_media($file, $userId) : null;
            if ($hasUpload && !$newMediaId) {
                throw new InvalidArgumentException('Choose a profile picture to upload.');
            }

            db_exec('UPDATE users SET full_name=?, phone=?, updated_at=NOW() WHERE id=?', 'ssi', [
                $name,
                $phoneValue,
                $userId,
            ]);

            if ($newMediaId !== null) {
                db_exec('INSERT INTO user_profile_photos (user_id, media_id, created_at, updated_at) VALUES (?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE media_id=VALUES(media_id), updated_at=NOW()', 'ii', [$userId, $newMediaId]);
                $pictureChange = 'updated';
            } elseif ($removePicture && $existingPhoto) {
                db_exec('DELETE FROM user_profile_photos WHERE user_id=?', 'i', [$userId]);
                $pictureChange = 'removed';
            }

            if ($existingPhoto && ($newMediaId !== null || ($removePicture && $existingPhoto))) {
                $oldMediaId = (int) $existingPhoto['media_id'];
                $otherUsage = db_one('SELECT (SELECT COUNT(*) FROM foods WHERE media_id=?)+(SELECT COUNT(*) FROM vendors WHERE logo_media_id=?)+(SELECT COUNT(*) FROM cms_content WHERE image_media_id=?)+(SELECT COUNT(*) FROM user_profile_photos WHERE media_id=?) AS n', 'iiii', [$oldMediaId, $oldMediaId, $oldMediaId, $oldMediaId]);
                if ((int) ($otherUsage['n'] ?? 0) === 0) {
                    db_exec('DELETE FROM media WHERE id=?', 'i', [$oldMediaId]);
                }
            }

            $db->commit();
        } catch (Throwable $e) {
            $db->rollback();
            throw $e;
        }

        if ($pictureChange !== 'unchanged') $changedFields[] = 'profile_picture';
        audit_log('Updated account profile', 'user', $userId, ['fields_changed' => $changedFields, 'profile_picture' => $pictureChange]);
        flash_set('success', 'Your profile was saved.');
        redirect('profile');
    }
}

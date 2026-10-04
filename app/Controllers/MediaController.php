<?php
require_once APP_ROOT . '/app/Controllers/Controller.php';
require_once APP_ROOT . '/app/Services/MediaService.php';

final class MediaController extends Controller
{
    public function serve(): void
    {
        $id = $this->positiveId($_GET['id'] ?? null, 'Image');
        $public = db_one("SELECT m.id FROM media m WHERE m.id=? AND (
            EXISTS (SELECT 1 FROM foods f JOIN vendors v ON v.id=f.vendor_id WHERE f.media_id=m.id AND f.is_available=1 AND v.status='ACTIVE')
            OR EXISTS (SELECT 1 FROM vendors v WHERE v.logo_media_id=m.id AND v.status='ACTIVE')
            OR EXISTS (SELECT 1 FROM cms_content c WHERE c.image_media_id=m.id AND c.is_published=1)
        ) LIMIT 1", 'i', [$id]);
        $profilePhoto = db_one('SELECT user_id FROM user_profile_photos WHERE media_id=? LIMIT 1', 'i', [$id]);
        $viewer = current_user();
        $canManageMedia = $viewer && can('media.manage');
        $ownsProfilePhoto = $profilePhoto && $viewer && (int) $profilePhoto['user_id'] === (int) $viewer['id'];

        if ($profilePhoto) {
            if (!$public && !$ownsProfilePhoto && !$canManageMedia) {
                throw new HttpException(404, 'That image could not be found.');
            }
        } elseif (!$public && !$canManageMedia) {
            throw new HttpException(404, 'That image could not be found.');
        }

        MediaService::stream($id, (bool) ($profilePhoto && !$public));
    }
}

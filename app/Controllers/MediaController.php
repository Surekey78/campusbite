<?php
require_once APP_ROOT . '/app/Controllers/Controller.php';
require_once APP_ROOT . '/app/Services/MediaService.php';

final class MediaController extends Controller
{
    public function view(): void
    {
        $id = $this->positiveId($_GET['id'] ?? null, 'Image');
        $public = db_one("SELECT m.id FROM media m WHERE m.id=? AND (
            EXISTS (SELECT 1 FROM foods f JOIN vendors v ON v.id=f.vendor_id WHERE f.media_id=m.id AND f.is_available=1 AND v.status='ACTIVE')
            OR EXISTS (SELECT 1 FROM vendors v WHERE v.logo_media_id=m.id AND v.status='ACTIVE')
            OR EXISTS (SELECT 1 FROM cms_content c WHERE c.image_media_id=m.id AND c.is_published=1)
        ) LIMIT 1", 'i', [$id]);
        if (!$public && !can('media.manage')) throw new HttpException(404, 'That image could not be found.');
        MediaService::stream($id);
    }
}

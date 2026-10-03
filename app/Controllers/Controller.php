<?php
abstract class Controller
{
    protected mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    protected function view(string $template, array $data = []): void
    {
        $viewFile = APP_ROOT . '/views/' . $template . '.php';
        if (!is_file($viewFile)) {
            throw new RuntimeException('Missing view: ' . $template);
        }
        extract($data, EXTR_SKIP);
        require APP_ROOT . '/views/layouts/header.php';
        require $viewFile;
        require APP_ROOT . '/views/layouts/footer.php';
    }

    protected function postOnly(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            throw new HttpException(405, 'This action requires a form submission.');
        }
    }

    protected function pageNotFound(): void
    {
        throw new HttpException(404, 'We could not find that page.');
    }

    protected function positiveId($value, string $label = 'ID'): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) throw new HttpException(404, $label . ' was not found.');
        return (int) $id;
    }
}

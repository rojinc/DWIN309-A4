<?php
namespace App\Core;

/**
 * Handles composition of PHP view templates and shared layouts.
 */
class View
{
    /**
     * Renders a full view within the main layout.
     */
    public function render(string $template, array $data = [], string $layout = 'main'): void
    {
        $viewFile = __DIR__ . '/../Views/' . $template . '.php';
        if (!file_exists($viewFile)) {
            throw new \RuntimeException('View not found: ' . $template);
        }
        $flash = Session::flash('flash');
        extract($data);
        ob_start();
        include $viewFile;
        $content = ob_get_clean();
        $layoutFile = __DIR__ . '/../Views/layouts/' . $layout . '.php';
        if (!file_exists($layoutFile)) {
            echo $content;
            return;
        }
        include $layoutFile;
    }

    /**
     * Loads a partial view directly (without a layout wrapper).
     */
    public function partial(string $template, array $data = []): void
    {
        $viewFile = __DIR__ . '/../Views/' . $template . '.php';
        if (!file_exists($viewFile)) {
            throw new \RuntimeException('Partial not found: ' . $template);
        }
        extract($data);
        include $viewFile;
    }
}
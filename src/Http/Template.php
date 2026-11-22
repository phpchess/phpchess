<?php

declare(strict_types=1);

namespace PhpChess\Http;

use RuntimeException;

/**
 * Renders the plain PHP templates in templates/ (ARCHITECTURE.md §7, §11).
 *
 * A template is an ordinary PHP file with HTML. The values passed to render()
 * become variables of the same name inside it ("title" becomes $title), and
 * every value printed goes through e() (contract K6). Each template lists the
 * variables it expects in its header comment.
 *
 * A file in templates/local/ with the same name wins over the core one, so a
 * server admin can change a page without touching the core files (A5).
 */
final class Template
{
    public function __construct(private readonly string $templateFolder)
    {
    }

    /**
     * Renders one template and returns the HTML.
     *
     * @param string               $name       for example "install/database" (no ".php")
     * @param array<string, mixed> $variables  name => value, available in the template
     */
    public function render(string $name, array $variables = []): string
    {
        if (preg_match('#^[a-z0-9_/-]+$#', $name) !== 1) {
            throw new RuntimeException("Not a template name: {$name}");
        }

        $file = $this->templateFolder . '/local/' . $name . '.php';
        if (!is_file($file)) {
            $file = $this->templateFolder . '/' . $name . '.php';
        }
        if (!is_file($file)) {
            throw new RuntimeException("Template not found: {$name}");
        }

        // The template sees the variables by name, plus $template for
        // rendering parts (for example the shared layout).
        $template = $this;
        extract($variables, EXTR_SKIP);

        ob_start();
        try {
            require $file;
        } finally {
            $html = (string) ob_get_clean();
        }

        return $html;
    }
}

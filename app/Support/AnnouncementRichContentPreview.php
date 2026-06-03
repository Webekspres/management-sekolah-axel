<?php

namespace App\Support;

use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class AnnouncementRichContentPreview implements Htmlable
{
    public function __construct(
        private string|array|null $content,
        private ?string $title = null,
    ) {}

    public static function make(string|array|null $content, ?string $title = null): self
    {
        return new self($content, $title);
    }

    public function toHtml(): string
    {
        $renderedContent = blank($this->content)
            ? '<p class="text-gray-500 dark:text-gray-400">Belum ada isi pengumuman.</p>'
            : RichContentRenderer::make($this->content)->toHtml();

        return view('filament.announcements.rich-content-preview', [
            'title' => $this->title,
            'content' => new HtmlString($renderedContent),
        ])->render();
    }
}

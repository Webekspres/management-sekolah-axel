<div @class(['space-y-4' => filled($title)])>
    @if (filled($title))
        <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
            {{ $title }}
        </h2>
    @endif

    <div class="fi-fo-rich-editor overflow-hidden rounded-lg ring-1 ring-gray-950/10 dark:ring-white/20">
        <div class="fi-fo-rich-editor-main">
            <div class="fi-fo-rich-editor-content fi-prose">
                <div class="tiptap ProseMirror">
                    {!! $content !!}
                </div>
            </div>
        </div>
    </div>
</div>

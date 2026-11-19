<?php

namespace App\Filament\Forms;

use App\Support\Prose;
use Filament\Forms\Components\RichEditor;

/**
 * The one rich text editor used for long content in the admin (college, department and
 * program pages, news). Same Tiptap editor and the same toolbar as the requested
 * "Rich Text Editor" template: undo/redo, paragraph and headings, bold, italic, underline,
 * strikethrough, code, highlight, link, alignment, lists, quote, divider; plus "details"
 * (a collapsible block) and clear formatting.
 *
 * Headings start at H2: the page title is the only H1 (WCAG 1.3.1).
 */
class ContentEditor
{
    public const TOOLBAR = [
        ['undo', 'redo'],
        ['paragraph', 'h2', 'h3', 'h4'],
        ['bold', 'italic', 'underline', 'strike', 'code', 'highlight'],
        ['link'],
        ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'],
        ['bulletList', 'orderedList', 'blockquote', 'horizontalRule'],
        ['details', 'clearFormatting'],
    ];

    public static function make(string $name): RichEditor
    {
        return RichEditor::make($name)
            ->toolbarButtons(self::TOOLBAR)
            ->floatingToolbars([
                'paragraph' => ['bold', 'italic', 'underline', 'strike', 'link', 'highlight'],
                'heading' => ['h2', 'h3', 'h4'],
            ])
            // Older content was written in Markdown: open it as formatted text, save it as HTML.
            ->formatStateUsing(fn (?string $state): ?string => Prose::isMarkdown($state) ? Prose::html($state) : $state)
            ->extraInputAttributes(['style' => 'min-height: 14rem'])
            ->columnSpanFull();
    }
}

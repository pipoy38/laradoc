<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use App\Support\HtmlSanitizer;
use Illuminate\Database\Seeder;

class SampleContentSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::firstOrCreate(
            ['slug' => 'getting-started', 'parent_id' => null],
            ['name' => 'Getting started', 'icon' => '🚀', 'description' => 'How to use this documentation site.']
        );

        $admin = User::where('role', 'admin')->oldest('id')->first();

        $body = $this->body();
        $html = HtmlSanitizer::clean($body);

        $article = Article::create([
            'category_id' => $category->id,
            'title' => 'Welcome to your documentation',
            'slug' => 'welcome',
            'excerpt' => HtmlSanitizer::excerpt($html),
            'body_html' => $html,
            'body_text' => HtmlSanitizer::toText($html),
            'status' => 'published',
            'published_at' => now(),
            'created_by' => $admin?->id,
        ]);

        $article->revisions()->create([
            'user_id' => $admin?->id,
            'title' => $article->title,
            'body_html' => $article->body_html,
            'excerpt' => $article->excerpt,
            'seo_title' => $article->seo_title,
            'meta_description' => $article->meta_description,
            'change_note' => 'Initial version',
            'created_at' => now(),
        ]);

        $tag = Tag::firstOrCreate(['slug' => 'guide'], ['name' => 'guide']);
        $article->tags()->sync([$tag->id]);
    }

    private function body(): string
    {
        return <<<'HTML'
            <p>This site is running <strong>Laradoc</strong>, a small self-hosted documentation wiki. This article is a
            quick tour of the admin area so you know where everything lives before you write your first real page.
            You can safely delete or rewrite this article once you're comfortable.</p>

            <h2>Writing articles</h2>
            <p>Articles live under categories. In the admin sidebar, open <strong>Articles &rarr; New</strong>, pick a
            category, and write in the rich text editor. The toolbar supports headings, links, images, tables and a
            code view for pasting raw HTML. Save as <em>Draft</em> while you work, and switch the status to
            <em>Published</em> when the page is ready &mdash; only published articles show up on the public site and
            in search.</p>

            <h2>Categories</h2>
            <p>Categories can be nested to build a sidebar tree. Create them under <strong>Categories</strong> in the
            admin area; each one has a name, a URL slug, an optional icon and description, and you can reorder
            siblings with the &uarr;/&darr; buttons next to each row. A category with articles or sub-categories in
            it can't be deleted until it's empty.</p>

            <h2>Revisions</h2>
            <p>Every time you save an article, Laradoc keeps a snapshot: who saved it, when, and an optional change
            note. Open an article and switch to the <strong>History</strong> tab to see past versions and restore an
            older one if you need to undo a mistake.</p>

            <h2>SEO fields</h2>
            <p>Each article has optional SEO fields &mdash; SEO title, meta description, canonical URL and a
            no-index flag &mdash; alongside an image field for social sharing previews. Leave them blank and
            Laradoc falls back to sensible defaults built from the title and an excerpt of the body.</p>

            <h2>Media</h2>
            <p>Use the image button in the editor toolbar to upload pictures, or manage everything already uploaded
            from the <strong>Media</strong> library. Uploads are limited to <code>jpg</code>, <code>png</code>,
            <code>gif</code> and <code>webp</code> &mdash; SVG is not accepted, so convert any logo to PNG first.</p>

            <h2>Menu, users and roles</h2>
            <p>The top navigation menu is managed under <strong>Menu</strong>, where you can add links to categories,
            articles or external URLs and reorder them with the same &uarr;/&darr; buttons. Under
            <strong>Users</strong>, an admin can create more accounts directly &mdash; there's no invitation email;
            you set the new account's password yourself and assign a role:</p>

            <table>
              <thead><tr><th>Role</th><th>Can do</th></tr></thead>
              <tbody>
                <tr><td>admin</td><td>Everything: manage articles, categories, media, menu, users and settings.</td></tr>
                <tr><td>editor</td><td>Create and edit articles, categories, tags, media and their own profile; no access to the menu, users or settings.</td></tr>
              </tbody>
            </table>

            <h2>Site name, logo and the admin URL</h2>
            <p>Site name, tagline, logo and favicon are set in <strong>Settings &rsaquo; General</strong> and
            <strong>Settings &rsaquo; Branding</strong>. Email delivery (SMTP, sendmail, or a log-only driver for
            testing) is configured in <strong>Settings &rsaquo; Email</strong>, with a button to send yourself a test
            message. The admin area itself doesn't have to live at <code>/admin</code>: change it in
            <strong>Settings &rsaquo; Admin URL</strong> if you'd rather keep it less guessable. If you ever forget
            the current prefix, run this from the server:</p>

            <pre><code class="language-bash">php artisan wiki:admin-prefix
            php artisan wiki:admin-prefix reset   # back to /admin</code></pre>

            <p>That's it &mdash; head back to the admin dashboard and start writing.</p>
            HTML;
    }
}

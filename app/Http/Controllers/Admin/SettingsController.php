<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TestMail;
use App\Models\Media;
use App\Support\AdminPrefix;
use App\Support\HtmlSanitizer;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function edit(Settings $settings)
    {
        return view('admin.settings.edit', ['s' => $settings, 'images' => Media::latest()->get()->filter->isImage(), 'tab' => request('tab', 'general')]);
    }

    public function update(Request $request, Settings $settings)
    {
        $section = $request->input('section');
        switch ($section) {
            case 'general':
                $data = $request->validate(['site_name' => 'required|string|max:80', 'site_tagline' => 'nullable|string|max:160', 'home_intro' => 'nullable|string|max:5000', 'footer_text' => 'nullable|string|max:1000']);
                $data['home_intro'] = HtmlSanitizer::clean((string) $data['home_intro']);
                $data['footer_text'] = HtmlSanitizer::clean((string) $data['footer_text']);
                $settings->setMany($data);
                break;
            case 'branding':
                $data = $request->validate(['logo_media_id' => 'nullable|integer|exists:media,id', 'favicon_media_id' => 'nullable|integer|exists:media,id']);
                $settings->setMany(['logo_media_id' => $data['logo_media_id'] ?: null, 'favicon_media_id' => $data['favicon_media_id'] ?: null]);
                break;
            case 'email':
                $data = $request->validate(['mail_mailer' => ['required', Rule::in(['smtp', 'sendmail', 'log'])], 'mail_host' => 'nullable|string|max:255', 'mail_port' => 'nullable|integer|between:1,65535',
                    'mail_username' => 'nullable|string|max:255', 'mail_password' => 'nullable|string|max:255', 'mail_encryption' => ['required', Rule::in(['tls', 'ssl', 'none'])],
                    'mail_from_address' => 'nullable|email|max:255', 'mail_from_name' => 'nullable|string|max:80']);
                if (($data['mail_password'] ?? '') === '') { unset($data['mail_password']); }
                $settings->setMany($data);
                break;
            case 'admin_url':
                $data = $request->validate(['admin_prefix' => ['required', 'string', function ($a, $v, $fail) { if (! AdminPrefix::isValid($v)) { $fail('Use 2–32 lowercase letters, numbers or dashes, and avoid reserved words like search, tags, api.'); } if (\App\Models\Category::whereNull('parent_id')->where('slug', $v)->exists()) { $fail('A top-level category already uses that slug.'); } }]]);
                $settings->set('admin_prefix', $data['admin_prefix']);
                Artisan::call('route:clear');
                return redirect('/'.$data['admin_prefix'].'/settings?tab=admin_url')->with('ok', 'Admin URL changed. Bookmark it: /'.$data['admin_prefix']);
            default:
                abort(422);
        }
        return redirect()->route('admin.settings.edit', ['tab' => $section])->with('ok', 'Settings saved.');
    }

    public function testMail(Request $request)
    {
        try {
            Mail::to($request->user()->email)->send(new TestMail());
            return back()->with('ok', 'Test email sent to '.$request->user()->email.'.');
        } catch (\Throwable $e) {
            return back()->with('err', 'Sending failed: '.$e->getMessage());
        }
    }
}

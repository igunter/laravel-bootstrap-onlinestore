<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

class MediaAssetController extends Controller
{
    /**
     * How long the last-used "usable for" selection is remembered as the
     * default for new uploads, once the admin changes it from the built-in
     * "product" default. Cookie::forever() effectively means "until changed
     * again"; this is far past the 30-minute minimum that was asked for.
     */
    private const LAST_USABLE_FOR_COOKIE = 'media_last_usable_for';

    public function index(): View
    {
        return view('admin.media.index', [
            'assets' => MediaAsset::latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.media.create', [
            'defaultPurposes' => $this->rememberedPurposes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request, requireFile: true);

        $asset = MediaAsset::create([
            'name' => $data['name'],
            'usable_for' => $data['usable_for'],
        ]);

        $asset->addMediaFromRequest('file')->toMediaCollection('file');

        Cookie::queue(Cookie::forever(self::LAST_USABLE_FOR_COOKIE, json_encode($data['usable_for'])));

        return redirect()->route('admin.media.index')->with('success', 'Media item created.');
    }

    public function edit(MediaAsset $medium): View
    {
        return view('admin.media.edit', [
            'asset' => $medium,
        ]);
    }

    public function update(Request $request, MediaAsset $medium): RedirectResponse
    {
        $data = $this->validateData($request);

        $medium->update([
            'name' => $data['name'],
            'usable_for' => $data['usable_for'],
        ]);

        if ($request->hasFile('file')) {
            $medium->addMediaFromRequest('file')->toMediaCollection('file');
        }

        Cookie::queue(Cookie::forever(self::LAST_USABLE_FOR_COOKIE, json_encode($data['usable_for'])));

        return redirect()->route('admin.media.index')->with('success', 'Media item updated.');
    }

    public function destroy(MediaAsset $medium): RedirectResponse
    {
        $medium->delete();

        return redirect()->route('admin.media.index')->with('success', 'Media item deleted.');
    }

    private function validateData(Request $request, bool $requireFile = false): array
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'usable_for' => ['nullable', 'array'],
            'usable_for.*' => ['string', 'in:'.implode(',', MediaAsset::PURPOSES)],
            'file' => [$requireFile ? 'required' : 'nullable', 'image', 'max:4096'],
        ]);

        unset($data['file']);

        $data['usable_for'] = empty($data['usable_for']) ? ['product'] : $data['usable_for'];

        return $data;
    }

    private function rememberedPurposes(): array
    {
        $remembered = json_decode(Cookie::get(self::LAST_USABLE_FOR_COOKIE, ''), true);

        if (! is_array($remembered)) {
            return ['product'];
        }

        $sanitized = array_values(array_intersect($remembered, MediaAsset::PURPOSES));

        return empty($sanitized) ? ['product'] : $sanitized;
    }
}

@php
    use App\Support\VendorProfile;
    $vendor = $vendor ?? null;
    $socials = $vendor?->socials ?? [];
    $categorySuggestions = $categorySuggestions ?? [];
    $selectedCategories = old('categories', $vendor?->categories ?? []);
    // Inline SVG icons keyed by platform.
    $icons = [
        'facebook'  => '<path d="M22 12a10 10 0 1 0-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6V12h2.8l-.4 2.9h-2.4v7A10 10 0 0 0 22 12z"/>',
        'instagram' => '<path d="M12 2.2c3.2 0 3.6 0 4.9.1 1.2.1 1.8.3 2.2.4.6.2 1 .5 1.4.9.4.4.7.8.9 1.4.1.4.3 1 .4 2.2.1 1.3.1 1.7.1 4.9s0 3.6-.1 4.9c-.1 1.2-.3 1.8-.4 2.2-.2.6-.5 1-.9 1.4-.4.4-.8.7-1.4.9-.4.1-1 .3-2.2.4-1.3.1-1.7.1-4.9.1s-3.6 0-4.9-.1c-1.2-.1-1.8-.3-2.2-.4-.6-.2-1-.5-1.4-.9-.4-.4-.7-.8-.9-1.4-.1-.4-.3-1-.4-2.2C2.2 15.6 2.2 15.2 2.2 12s0-3.6.1-4.9c.1-1.2.3-1.8.4-2.2.2-.6.5-1 .9-1.4.4-.4.8-.7 1.4-.9.4-.1 1-.3 2.2-.4C8.4 2.2 8.8 2.2 12 2.2zm0 1.8c-3.1 0-3.5 0-4.7.1-1.1.1-1.7.2-2.1.4-.5.2-.9.4-1.3.8-.4.4-.6.8-.8 1.3-.2.4-.3 1-.4 2.1C2.6 9.9 2.6 10.3 2.6 12s0 2.1.1 3.3c.1 1.1.2 1.7.4 2.1.2.5.4.9.8 1.3.4.4.8.6 1.3.8.4.2 1 .3 2.1.4 1.2.1 1.6.1 4.7.1s3.5 0 4.7-.1c1.1-.1 1.7-.2 2.1-.4.5-.2.9-.4 1.3-.8.4-.4.6-.8.8-1.3.2-.4.3-1 .4-2.1.1-1.2.1-1.6.1-3.3s0-2.1-.1-3.3c-.1-1.1-.2-1.7-.4-2.1-.2-.5-.4-.9-.8-1.3-.4-.4-.8-.6-1.3-.8-.4-.2-1-.3-2.1-.4-1.2-.1-1.6-.1-4.7-.1zm0 3.1a4.9 4.9 0 1 1 0 9.8 4.9 4.9 0 0 1 0-9.8zm0 8a3.1 3.1 0 1 0 0-6.2 3.1 3.1 0 0 0 0 6.2zm6.3-8.2a1.15 1.15 0 1 1-2.3 0 1.15 1.15 0 0 1 2.3 0z"/>',
        'x'         => '<path d="M17.5 3h3l-6.6 7.6L21.8 21h-5.9l-4.6-6-5.3 6H3l7-8L2.7 3h6l4.2 5.5L17.5 3zm-1 16h1.6L7.6 4.7H5.9L16.5 19z"/>',
        'tiktok'    => '<path d="M16.6 5.8c-1-.7-1.7-1.7-1.9-2.8h-3v12.2c0 1.4-1.1 2.5-2.5 2.5S6.7 16.6 6.7 15.2s1.1-2.5 2.5-2.5c.3 0 .5 0 .8.1v-3.1c-.3 0-.5-.1-.8-.1A5.6 5.6 0 1 0 14.8 15V9.3c1.1.8 2.5 1.3 4 1.3V7.5c-.8 0-1.6-.2-2.2-.6z"/>',
        'youtube'   => '<path d="M23 12s0-3.2-.4-4.7c-.2-.8-.9-1.5-1.7-1.7C19.4 5.2 12 5.2 12 5.2s-7.4 0-8.9.4c-.8.2-1.5.9-1.7 1.7C1 8.8 1 12 1 12s0 3.2.4 4.7c.2.8.9 1.5 1.7 1.7 1.5.4 8.9.4 8.9.4s7.4 0 8.9-.4c.8-.2 1.5-.9 1.7-1.7.4-1.5.4-4.7.4-4.7zM9.8 15.2V8.8l5.5 3.2-5.5 3.2z"/>',
    ];
@endphp

<div class="form-row">
    <label>Your name
        <input type="text" name="contact_name" value="{{ old('contact_name', $vendor?->contact_name) }}" required>
    </label>
    <label>Company name
        <input type="text" name="business_name" value="{{ old('business_name', $vendor?->business_name) }}" required>
    </label>
</div>

<div class="form-row">
    <label>Phone
        <input type="text" name="phone" value="{{ old('phone', $vendor?->phone) }}">
    </label>
    <label>Website
        <input type="url" name="website" placeholder="https://" value="{{ old('website', $vendor?->website) }}">
    </label>
</div>

<label>Address
    <textarea name="address" rows="2">{{ old('address', $vendor?->address) }}</textarea>
</label>

<div class="tag-field" data-tag-field>
    <span class="tag-field-label">What do you sell? <span class="muted">(add one or more categories)</span></span>
    <div class="tag-chips" data-tag-chips>
        @foreach ($selectedCategories as $cat)
            <span class="tag-chip">
                <span>{{ $cat }}</span>
                <button type="button" class="tag-chip-x" data-tag-remove aria-label="Remove">&times;</button>
                <input type="hidden" name="categories[]" value="{{ $cat }}">
            </span>
        @endforeach
    </div>
    <div class="tag-input-row">
        <input type="text" class="tag-input" data-tag-input list="category-suggestions"
               placeholder="e.g. Candles, Scarves, Decorations…" autocomplete="off">
        <button type="button" class="btn-secondary sm" data-tag-add>Add</button>
    </div>
    <datalist id="category-suggestions">
        @foreach ($categorySuggestions as $name)
            <option value="{{ $name }}"></option>
        @endforeach
    </datalist>
</div>

<fieldset class="socials">
    <legend>Social media</legend>
    @foreach (VendorProfile::SOCIALS as $key => $label)
        <div class="social-row">
            <span class="social-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="currentColor">{!! $icons[$key] !!}</svg>
            </span>
            <input type="text" name="socials[{{ $key }}]"
                   placeholder="{{ $label }}"
                   value="{{ old('socials.' . $key, $socials[$key] ?? '') }}">
        </div>
    @endforeach
</fieldset>

<script>
(function () {
    // Lightweight category tag-input: add chips from the suggestion list or a
    // custom typed value; each chip carries a hidden categories[] input.
    document.querySelectorAll('[data-tag-field]').forEach(function (field) {
        var chips = field.querySelector('[data-tag-chips]');
        var input = field.querySelector('[data-tag-input]');
        var addBtn = field.querySelector('[data-tag-add]');

        function existing() {
            return Array.from(chips.querySelectorAll('input[name="categories[]"]'))
                .map(function (i) { return i.value.toLowerCase(); });
        }
        function addTag(name) {
            name = (name || '').trim();
            if (!name || existing().indexOf(name.toLowerCase()) !== -1) return;
            var chip = document.createElement('span');
            chip.className = 'tag-chip';
            var label = document.createElement('span');
            label.textContent = name;
            var x = document.createElement('button');
            x.type = 'button'; x.className = 'tag-chip-x'; x.innerHTML = '&times;';
            x.setAttribute('aria-label', 'Remove');
            x.addEventListener('click', function () { chip.remove(); });
            var hidden = document.createElement('input');
            hidden.type = 'hidden'; hidden.name = 'categories[]'; hidden.value = name;
            chip.appendChild(label); chip.appendChild(x); chip.appendChild(hidden);
            chips.appendChild(chip);
        }
        function commit() { addTag(input.value); input.value = ''; input.focus(); }

        chips.querySelectorAll('[data-tag-remove]').forEach(function (btn) {
            btn.addEventListener('click', function () { btn.closest('.tag-chip').remove(); });
        });
        addBtn.addEventListener('click', commit);
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); commit(); }
        });
        // Don't lose a typed-but-not-added category: flush it into a chip on submit.
        var form = field.closest('form');
        if (form) form.addEventListener('submit', function () { addTag(input.value); input.value = ''; });
    });
})();
</script>

@php
    $iconOptions = [
        '👗' => '👗 نساء',
        '👔' => '👔 رجال',
        '👟' => '👟 أحذية',
        '👜' => '👜 حقائب',
        '💄' => '💄 تجميل',
        '🏠' => '🏠 المنزل',
        '📱' => '📱 إلكترونيات',
    ];
    $currentIcon = old('icon', $category->icon);
    $isKnownIcon = $currentIcon !== null && $currentIcon !== '' && array_key_exists($currentIcon, $iconOptions);
    $isCustomIcon = $currentIcon !== null && $currentIcon !== '' && ! $isKnownIcon;
@endphp

<div class="col-md-4">
    <label class="form-label">Icon (emoji)</label>
    <select id="icon_select" class="form-select">
        <option value="">-- اختر أيقونة --</option>
        @foreach($iconOptions as $emoji => $label)
            <option value="{{ $emoji }}" @selected($currentIcon === $emoji)>{{ $label }}</option>
        @endforeach
        <option value="__other__" @selected($isCustomIcon)>أخرى (اكتب الإيموجي بنفسك)</option>
    </select>

    <input
        type="text"
        id="icon_custom"
        class="form-control text-center fs-4 mt-2 {{ $isCustomIcon ? '' : 'd-none' }}"
        placeholder="اكتب أو الصق الإيموجي هنا"
        maxlength="10"
        value="{{ $isCustomIcon ? $currentIcon : '' }}"
    />

    <input type="hidden" name="icon" id="icon_value" value="{{ $currentIcon }}" />
</div>

<script>
    (function () {
        const select = document.getElementById('icon_select');
        const customInput = document.getElementById('icon_custom');
        const hiddenValue = document.getElementById('icon_value');

        select.addEventListener('change', function () {
            if (select.value === '__other__') {
                customInput.classList.remove('d-none');
                hiddenValue.value = customInput.value;
                customInput.focus();
            } else {
                customInput.classList.add('d-none');
                hiddenValue.value = select.value;
            }
        });

        customInput.addEventListener('input', function () {
            hiddenValue.value = customInput.value;
        });
    })();
</script>

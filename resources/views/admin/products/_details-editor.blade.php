@php
    $specs = old('specs', $product->specs ?: []);
    if ($specs === []) {
        $specs = [['label' => '', 'value' => '']];
    }
    $chart = old('size_chart', $product->size_chart ?: []);
    $chartColumns = $chart['columns'] ?? ['مقاس', 'قياس الصدر', 'قياس الخصر', 'الطول', 'طول الأشرطة'];
    $chartRows = $chart['rows'] ?? [];
    $chartUnit = $chart['unit'] ?? 'cm';
    $chartNote = $chart['note'] ?? 'تم الحصول على هذه البيانات عبر قياس المنتج يدوياً، وقد تتباين القياسات بمقدار 1-2 سم';
@endphp

<div class="col-12">
    <hr class="my-2">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <label class="form-label fw-bold mb-0">تفاصيل المنتج (جدول المواصفات)</label>
        <button type="button" class="btn btn-sm btn-outline-primary" id="addSpecBtn">+ صف (نسخ الأخير)</button>
    </div>
    <p class="form-text mt-0">مثل SHEIN: خط العنق، الخامة، الطول… كل صف خاصية. انسخي الصف وعدّلي.</p>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0" id="specsTable">
            <thead class="table-light">
                <tr>
                    <th>الخاصية</th>
                    <th>القيمة</th>
                    <th style="width:7.5rem"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($specs as $i => $row)
                    <tr>
                        <td><input type="text" name="specs[{{ $i }}][label]" value="{{ $row['label'] ?? '' }}" class="form-control form-control-sm spec-label" placeholder="مثلاً: خط العنق"></td>
                        <td><input type="text" name="specs[{{ $i }}][value]" value="{{ $row['value'] ?? '' }}" class="form-control form-control-sm spec-value" placeholder="مثلاً: الأشرطة السباغيتي"></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary copy-spec">نسخ</button>
                                <button type="button" class="btn btn-outline-danger remove-spec">حذف</button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="col-12 mt-3">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <label class="form-label fw-bold mb-0">المقاس والقياس</label>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="fillChartSizesBtn">كل المقاسات</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="addChartColBtn">+ عمود</button>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addChartRowBtn">+ المقاس التالي</button>
        </div>
    </div>
    <div class="row g-2 mb-2">
        <div class="col-md-3">
            <label class="form-label small mb-1">الوحدة</label>
            <select name="size_chart[unit]" class="form-select form-select-sm">
                <option value="cm" @selected($chartUnit === 'cm')>سم (CM)</option>
                <option value="in" @selected($chartUnit === 'in')>إنش (IN)</option>
            </select>
        </div>
        <div class="col-md-9">
            <label class="form-label small mb-1">ملاحظة أسفل الجدول</label>
            <input type="text" name="size_chart[note]" value="{{ $chartNote }}" class="form-control form-control-sm">
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0" id="sizeChartTable">
            <thead class="table-light">
                <tr>
                    @foreach($chartColumns as $cIndex => $col)
                        <th>
                            <div class="d-flex gap-1">
                                <input type="text" name="size_chart[columns][]" value="{{ $col }}" class="form-control form-control-sm chart-col">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-chart-col" title="حذف العمود">×</button>
                            </div>
                        </th>
                    @endforeach
                    <th style="width:7.5rem"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($chartRows as $rIndex => $row)
                    <tr>
                        @foreach($chartColumns as $cIndex => $col)
                            <td><input type="text" name="size_chart[rows][{{ $rIndex }}][]" value="{{ $row[$cIndex] ?? '' }}" class="form-control form-control-sm" dir="ltr"></td>
                        @endforeach
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary copy-chart-row">نسخ</button>
                                <button type="button" class="btn btn-outline-danger remove-chart-row">حذف</button>
                            </div>
                        </td>
                    </tr>
                @empty
                @endforelse
            </tbody>
        </table>
    </div>
</div>

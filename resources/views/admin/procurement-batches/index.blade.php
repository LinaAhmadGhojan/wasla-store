@extends('admin.layout')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">دفعات الشراء</h1>
            <p class="text-muted mb-0">اجمعي Orders واشتري من الإمارات مرة واحدة.</p>
        </div>
        <a href="{{ route('admin.procurement-batches.create') }}" class="btn btn-primary">+ دفعة جديدة</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>المرجع</th>
                        <th>العنوان</th>
                        <th>الحالة</th>
                        <th>طلبات</th>
                        <th>تكلفة (د.إ)</th>
                        <th>التاريخ</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                        <tr>
                            <td><code>{{ $batch->reference }}</code></td>
                            <td>{{ $batch->title }}</td>
                            <td><span class="badge bg-secondary">{{ $batch->statusLabel() }}</span></td>
                            <td>{{ $batch->purchase_requests_count }}</td>
                            <td dir="ltr">
                                @if($batch->total_aed)
                                    {{ number_format($batch->total_aed, 2) }} د.إ
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-muted small">{{ $batch->created_at->format('Y-m-d') }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.procurement-batches.show', $batch) }}" class="btn btn-sm btn-outline-primary">فتح</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4">لا توجد دفعات — أنشئي دفعة واجمعي الطلبات.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $batches->links('pagination::bootstrap-5') }}</div>
</div>
@endsection

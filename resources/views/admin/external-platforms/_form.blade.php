<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Name</label>
        <input type="text" name="name" value="{{ old('name', $platform->name) }}" class="form-control" required />
    </div>
    <div class="col-md-4">
        <label class="form-label">Slug</label>
        <input type="text" name="slug" value="{{ old('slug', $platform->slug) }}" class="form-control" placeholder="Optional" />
    </div>
    <div class="col-md-4">
        <label class="form-label">Type</label>
        <input type="text" name="type" value="{{ old('type', $platform->type ?? 'marketplace') }}" class="form-control" required />
    </div>
    <div class="col-md-4">
        <label class="form-label">Website</label>
        <input type="text" name="website" value="{{ old('website', $platform->website) }}" class="form-control" placeholder="https://..." />
    </div>
    <div class="col-md-4">
        <label class="form-label">Logo URL</label>
        <input type="text" name="logo" value="{{ old('logo', $platform->logo) }}" class="form-control" placeholder="https://..." />
    </div>
    <div class="col-md-4">
        <label class="form-label">Currency</label>
        <input type="text" name="currency" value="{{ old('currency', $platform->currency ?? 'USD') }}" class="form-control" required />
    </div>
    <div class="col-md-4">
        <label class="form-label">Country</label>
        <input type="text" name="country" value="{{ old('country', $platform->country) }}" class="form-control" placeholder="e.g. CN, TR, AE" />
    </div>
    <div class="col-md-4">
        <label class="form-label">Commission rate (%)</label>
        <input type="number" step="0.01" name="commission_rate" value="{{ old('commission_rate', $platform->commission_rate ?? 0) }}" class="form-control" required />
    </div>
    <div class="col-md-4">
        <label class="form-label">Markup rate (%)</label>
        <input type="number" step="0.01" name="markup_rate" value="{{ old('markup_rate', $platform->markup_rate ?? 0) }}" class="form-control" required />
    </div>
    <div class="col-md-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select" required>
            <option value="active" @selected(old('status', $platform->status ?? 'active') == 'active')>Active</option>
            <option value="inactive" @selected(old('status', $platform->status ?? '') == 'inactive')>Inactive</option>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Visible / enabled</label>
        <select name="is_active" class="form-select" required>
            <option value="1" @selected(old('is_active', $platform->is_active ?? true) == 1)>Yes</option>
            <option value="0" @selected(old('is_active', $platform->is_active ?? true) == 0)>No</option>
        </select>
    </div>
</div>

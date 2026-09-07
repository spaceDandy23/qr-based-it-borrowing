<div>
    <x-toast-flash />

    <div class="toolbar">
        <div class="input-wrap grow"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4-4"/></svg>
            <input class="flt" style="width:100%" placeholder="Search by name, tag, or serial…" wire:model.live.debounce.300ms="q">
        </div>
        <a href="{{ route('export.equipment') }}" class="btn btn-ghost"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg> Export CSV</a>
        <button class="btn btn-primary" wire:click="openForm">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg> Add equipment
        </button>
    </div>

    @if ($equipment->isEmpty())
        <x-empty-state title="No equipment found" sub="Try a different search." />
    @else
        <div class="tbl-wrap"><table>
            <thead><tr><th>Asset</th><th>Tag</th><th>Category</th><th>Serial</th><th>Condition</th><th>Status</th><th>Location</th><th></th></tr></thead>
            <tbody>
            @foreach ($equipment as $e)
                <tr wire:key="eq-{{ $e->id }}">
                    <td><div style="display:flex;align-items:center;gap:10px">
                        <div style="width:34px;height:34px;border-radius:8px;background:var(--surface-2);display:grid;place-items:center;flex:0 0 auto">
                            @if ($e->image)
                                <img src="{{ $e->image }}" style="width:100%;height:100%;object-fit:cover;border-radius:8px">
                            @else
                                {!! \App\Support\Ui::category($e->category, 18, '#5e544d') !!}
                            @endif
                        </div>
                        <div class="row-main">{{ $e->name }}</div></div></td>
                    <td class="mono row-sub" style="color:var(--ink-2)">{{ $e->asset_tag }}</td>
                    <td><span class="chip">{{ $e->category }}</span></td>
                    <td class="mono row-sub">{{ $e->serial }}</td>
                    <td class="row-sub">{{ $e->condition }}</td>
                    <td><x-status-pill :status="$e->status" /></td>
                    <td class="row-sub">{{ $e->location }}</td>
                    <td><div class="actions">
                        <button class="icon-btn" style="width:32px;height:32px" title="Details / QR" wire:click="openDetail({{ $e->id }})"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/><path d="M7 12h10"/></svg></button>
                        <button class="icon-btn" style="width:32px;height:32px" title="Edit" wire:click="openForm({{ $e->id }})"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></svg></button>
                        <button class="icon-btn" style="width:32px;height:32px;color:var(--bad)" title="Delete" wire:click="confirmDelete({{ $e->id }})"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg></button>
                    </div></td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    @endif

    {{-- Add / edit form modal --}}
    @if ($showForm)
        <div class="modal-bg" wire:click.self="$set('showForm', false)">
            <div class="modal wide">
                <div class="modal-head">
                    <div><h3>{{ $editingId ? 'Edit equipment' : 'Add equipment' }}</h3><p>{{ $editingId ? 'Update this asset record.' : 'Register a new asset into inventory.' }}</p></div>
                    <button class="x" wire:click="$set('showForm', false)">✕</button>
                </div>
                <div class="modal-body"><div class="form-grid">
                    <div class="field"><label>Equipment name</label><input wire:model="name" placeholder="e.g. Dell Latitude 5440">@error('name')<p class="hint" style="color:var(--bad)">{{ $message }}</p>@enderror</div>
                    <div class="field"><label>Asset tag</label><input wire:model="assetTag" class="mono" placeholder="FDCP-LT-001">@error('assetTag')<p class="hint" style="color:var(--bad)">{{ $message }}</p>@enderror</div>
                    <div class="field"><label>Category</label><select wire:model="category">
                        @foreach (['Laptop','Desktop','Monitor','Projector','Camera','Networking','Printer','Peripheral','Tablet','Audio'] as $c)
                            <option value="{{ $c }}">{{ $c }}</option>
                        @endforeach
                    </select></div>
                    <div class="field"><label>Serial number</label><input wire:model="serial" class="mono" placeholder="Serial / IMEI"></div>
                    <div class="field"><label>Condition</label><select wire:model="condition">
                        @foreach (['Excellent','Good','Fair','Poor'] as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
                    </select></div>
                    <div class="field"><label>Purchase date</label><input type="date" wire:model="purchaseDate"></div>
                    <div class="field"><label>Location</label><input wire:model="location" placeholder="Storage Room A"></div>
                    <div class="field full"><label>Image URL (optional)</label><input wire:model="image" placeholder="https://… (leave blank for a category icon)"></div>
                </div></div>
                <div class="modal-foot">
                    <button class="btn btn-ghost" wire:click="$set('showForm', false)">Cancel</button>
                    <button class="btn btn-primary" wire:click="save">{{ $editingId ? 'Save changes' : 'Add to inventory' }}</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Detail / QR modal --}}
    @if ($detailId)
        @php($detail = $equipment->firstWhere('id', $detailId) ?? \App\Models\Equipment::find($detailId))
        <div class="modal-bg" wire:click.self="closeDetail">
            <div class="modal wide">
                <div class="modal-head"><div><h3>{{ $detail->name }}</h3><p class="mono">{{ $detail->asset_tag }}</p></div><button class="x" wire:click="closeDetail">✕</button></div>
                <div class="modal-body">
                    <div class="detail-img" style="margin-bottom:16px">
                        @if ($detail->image)
                            <img src="{{ $detail->image }}">
                        @else
                            {!! \App\Support\Ui::category($detail->category, 72, '#bdb0aa') !!}
                        @endif
                    </div>
                    <dl class="kv" style="margin-bottom:18px">
                        <dt>Status</dt><dd><x-status-pill :status="$detail->status" /></dd>
                        <dt>Category</dt><dd>{{ $detail->category }}</dd>
                        <dt>Serial</dt><dd class="mono">{{ $detail->serial }}</dd>
                        <dt>Condition</dt><dd>{{ $detail->condition }}</dd>
                        <dt>Location</dt><dd>{{ $detail->location }}</dd>
                        <dt>Purchased</dt><dd>{{ optional($detail->purchase_date)->format('M j, Y') ?? '—' }}</dd>
                        <dt>Loan records</dt><dd>{{ $detail->requests()->count() }}</dd>
                    </dl>
                    <div class="card-head" style="padding:0 0 10px;border:none"><h3>Asset QR code</h3></div>
                    <div class="qr-box">
                        <div class="qr" id="qrTarget" wire:ignore></div>
                        <div class="qr-cap">Scan this from the <b>QR Scan</b> screen to check this item in or out. Encodes the asset tag <span class="mono">{{ $detail->asset_tag }}</span>.</div>
                    </div>
                </div>
                <div class="modal-foot">
                    <button class="btn btn-ghost" wire:click="closeDetail">Close</button>
                    @if ($detail->status !== 'Maintenance')
                        <button class="btn btn-ghost" wire:click="setMaintenance({{ $detail->id }})">Mark maintenance</button>
                    @else
                        <button class="btn btn-ghost" wire:click="clearMaintenance({{ $detail->id }})">Return to service</button>
                    @endif
                    <button class="btn btn-primary" wire:click="editFromDetail({{ $detail->id }})">Edit</button>
                </div>
            </div>
        </div>
        <script>
            (function () {
                const el = document.getElementById('qrTarget');
                if (el) new QRCode(el, { text: @json($detail->asset_tag), width: 148, height: 148, correctLevel: QRCode.CorrectLevel.M });
            })();
        </script>
    @endif

    {{-- Delete confirm modal --}}
    @if ($deletingId)
        @php($del = \App\Models\Equipment::find($deletingId))
        <div class="modal-bg" wire:click.self="$set('deletingId', null)">
            <div class="modal">
                <div class="modal-head"><div><h3>Delete equipment</h3><p>This removes the asset record permanently.</p></div><button class="x" wire:click="$set('deletingId', null)">✕</button></div>
                <div class="modal-body"><p>Delete <b>{{ $del->name }}</b> (<span class="mono">{{ $del->asset_tag }}</span>)? This can't be undone.</p></div>
                <div class="modal-foot"><button class="btn btn-ghost" wire:click="$set('deletingId', null)">Cancel</button><button class="btn btn-danger" wire:click="delete">Delete</button></div>
            </div>
        </div>
    @endif
</div>

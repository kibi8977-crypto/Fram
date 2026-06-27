@php
    $proto_map = [0 => 'Any', 1 => 'ICMP', 6 => 'TCP', 17 => 'UDP'];
    $type_map  = [0 => 'Block', 1 => 'Rate limit', 2 => 'Allow only'];
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PAKKT.io — Protection</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="{{ asset('extensions/pakkt/pakkt.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js" defer></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }
        body { background: #06080C; color: #D8E8F4; font-family: Inter, sans-serif; overflow-y: auto; }
        .pakkt-wrap { max-width: 960px; margin: 0 auto; padding: 20px 16px 40px; }

        #pakkt-marketplace { background: transparent; }
    </style>
</head>
<body>
<div class="pakkt-wrap">

    @if(session('success'))
        <div class="pakkt-alert pakkt-alert--success" style="margin-bottom:14px;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="pakkt-alert pakkt-alert--error" style="margin-bottom:14px;">{{ session('error') }}</div>
    @endif

    @if(!$configured)
        <div class="pakkt-card" style="text-align:center; padding:40px;">
            <div style="font-size:32px; margin-bottom:14px;">🛡️</div>
            <div style="font-size:18px; font-weight:700; margin-bottom:8px;">PAKKT.io Protection non configuré</div>
            <div style="color:#5A7A9A; font-size:13px;">Contactez votre hébergeur pour activer la protection PAKKT.</div>
        </div>
    @elseif(!$agent_id)
        <div class="pakkt-alert pakkt-alert--warn">
            Aucun agent PAKKT associé au node de ce serveur. Contactez votre hébergeur.
        </div>
    @else

    {{-- ── Header ──────────────────────────────────────────────────────────── --}}
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
        <div style="display:flex; align-items:center; gap:12px;">
            <span style="font-size:18px; font-weight:700; font-family:'Barlow Condensed',sans-serif; text-transform:uppercase; letter-spacing:.04em; color:#00D2FF;">
                Protection PAKKT
            </span>
            <span class="pakkt-badge pakkt-badge--{{ $agent_online ? 'online' : 'offline' }}">
                {{ $agent_online ? 'Agent actif' : 'Agent hors ligne' }}
            </span>
        </div>
        <div class="pakkt-ports" style="font-size:12px; color:#5A7A9A;">
            Vos ports :
            @forelse($ports as $p)
                <span style="background:rgba(0,210,255,0.08); padding:1px 6px; border-radius:2px; margin-left:3px; color:#00D2FF; font-family:monospace;">{{ $p }}</span>
            @empty
                <span style="color:#3A5A7A;">aucun</span>
            @endforelse
        </div>
    </div>

    {{-- ── Stats ───────────────────────────────────────────────────────────── --}}
    <div class="pakkt-card" style="margin-bottom:16px;">
        <div class="pakkt-section-header">
            <h3 style="margin:0;">Trafic (1h) — <span style="font-size:11px;color:#5A7A9A;font-weight:400;">filtré sur vos ports</span></h3>
            <div style="display:flex;gap:8px;align-items:center;">
                <select id="stat-window" onchange="pakktLoadStats()" class="pakkt-btn pakkt-btn--sm" style="background:#0F1520;color:#8BA4B4;border:1px solid #1E3040;clip-path:none;cursor:pointer;padding:4px 8px;">
                    <option value="10m">10 min</option>
                    <option value="1h" selected>1 heure</option>
                    <option value="24h">24 heures</option>
                </select>
                <button id="btn-refresh" onclick="pakktLoadStats()" class="pakkt-btn pakkt-btn--sm"
                        style="background:rgba(0,210,255,0.1); color:#00D2FF; clip-path:none; min-width:100px; transition:opacity .2s;">
                    <i class="fa fa-refresh"></i> Actualiser
                </button>
            </div>
        </div>
        <div class="pakkt-grid-stats" style="grid-template-columns:repeat(4,1fr);margin-bottom:16px;">
            <div class="pakkt-stat">
                <span class="pakkt-stat__value" id="stat-passed">—</span>
                <span class="pakkt-stat__label">Passés <span class="pakkt-stat__unit">pkkt</span></span>
            </div>
            <div class="pakkt-stat">
                <span class="pakkt-stat__value" id="stat-dropped" style="color:#FF2D55;">—</span>
                <span class="pakkt-stat__label">Bloqués <span class="pakkt-stat__unit">pkkt</span></span>
            </div>
            <div class="pakkt-stat">
                <span class="pakkt-stat__value" id="stat-bytes-passed">—</span>
                <span class="pakkt-stat__label">Volume passé</span>
            </div>
            <div class="pakkt-stat">
                <span class="pakkt-stat__value" id="stat-bytes-dropped" style="color:#FF2D55;">—</span>
                <span class="pakkt-stat__label">Volume bloqué</span>
            </div>
        </div>
        {{-- Chart --}}
        <div style="position:relative;height:140px;margin:0 -4px;">
            <canvas id="pakkt-chart" style="width:100%;height:140px;"></canvas>
            <div id="chart-empty" style="display:none;position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#5A7A9A;font-size:12px;">Pas encore de données</div>
        </div>
    </div>

    {{-- ── Tabs ────────────────────────────────────────────────────────────── --}}
    <div class="pakkt-tabs">
        <div class="pakkt-tab pakkt-tab--active-xdp" id="tab-xdp" onclick="pakktTab('xdp')">
            <i class="fa fa-bolt" style="margin-right:4px;"></i> XDP / eBPF
        </div>
        <div class="pakkt-tab" id="tab-nft" onclick="pakktTab('nft')">
            <i class="fa fa-fire" style="margin-right:4px;"></i> NFTables
        </div>
    </div>

    {{-- ════════════════════════ XDP TAB ════════════════════════════════════ --}}
    <div id="panel-xdp">

        {{-- Active rules --}}
        <div class="pakkt-card" style="margin-bottom:12px;">
            <div class="pakkt-section-header">
                <h3 style="margin:0;">Règles XDP actives</h3>
                <span style="font-size:12px; color:#5A7A9A;">Filtrées sur vos ports</span>
            </div>
            @if(count($xdp_programs) === 0)
                <div class="pakkt-empty">Aucune règle XDP active sur vos ports.</div>
            @else
            <table class="pakkt-table">
                <thead><tr><th>Nom</th><th>Ports</th><th>Proto</th><th>Type</th><th>Max PPS</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                @foreach($xdp_programs as $prog)
                <tr>
                    <td>{{ $prog['name'] ?? '—' }}</td>
                    <td><code>{{ $prog['port_range_start'] }}@if($prog['port_range_end'] !== $prog['port_range_start']) – {{ $prog['port_range_end'] }}@endif</code></td>
                    <td>{{ $proto_map[$prog['protocol'] ?? 0] ?? '?' }}</td>
                    <td><span class="pakkt-badge pakkt-badge--xdp" style="font-size:10px;">{{ $type_map[$prog['rule_type'] ?? 0] ?? '?' }}</span></td>
                    <td>{{ $prog['max_pps'] > 0 ? number_format($prog['max_pps']).' pps' : '—' }}</td>
                    @php $is_ok = in_array($prog['status'] ?? '', ['loaded','active']); $is_pend = in_array($prog['status'] ?? '', ['pending_load','pending_unload']); @endphp
                    <td><span style="font-size:10px; padding:2px 8px; border-radius:2px;
                        background:{{ $is_ok ? 'rgba(0,255,136,0.15)' : ($is_pend ? 'rgba(255,184,48,0.15)' : 'rgba(255,45,85,0.15)') }};
                        color:{{ $is_ok ? '#00FF88' : ($is_pend ? '#FFB830' : '#FF2D55') }}">
                        {{ $is_ok ? 'active' : ($prog['status'] ?? 'unknown') }}</span></td>
                    <td>
                        <form method="POST"
                              action="{{ route('server.extensions.pakkt.xdp.delete', ['server_uuid'=>$server->uuidShort,'program_id'=>$prog['id']]) }}"
                              onsubmit="return confirm('Supprimer cette règle XDP ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="pakkt-btn pakkt-btn--danger pakkt-btn--sm"><i class="fa fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>

    </div>

    {{-- ════════════════════════ NFT TAB ════════════════════════════════════ --}}
    <div id="panel-nft" style="display:none;">

        {{-- Active rules --}}
        <div class="pakkt-card" style="margin-bottom:12px;">
            <div class="pakkt-section-header">
                <h3 style="margin:0;">Règles NFTables actives</h3>
                <span style="font-size:12px;color:#5A7A9A;">Firewall stateful sur vos ports</span>
            </div>
            @if(count($nft_rules) === 0)
                <div class="pakkt-empty">Aucune règle nftables active sur vos ports.</div>
            @else
            <table class="pakkt-table">
                <thead><tr><th>Nom</th><th>Ports</th><th>Proto</th><th>Action</th><th>Limit</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                @foreach($nft_rules as $rule)
                <tr>
                    <td>{{ $rule['name'] }}</td>
                    <td><code>{{ $rule['port_start'] }}@if($rule['port_end']!==$rule['port_start']) – {{ $rule['port_end'] }}@endif</code></td>
                    <td>{{ strtoupper($rule['protocol']??'any') }}</td>
                    <td><span class="pakkt-badge" style="font-size:10px;
                        background:{{ $rule['action']==='drop'?'rgba(255,45,85,0.15)':($rule['action']==='accept'?'rgba(0,255,136,0.15)':'rgba(255,184,48,0.15)') }};
                        color:{{ $rule['action']==='drop'?'#FF2D55':($rule['action']==='accept'?'#00FF88':'#FFB830') }}">
                        {{ strtoupper($rule['action']) }}</span></td>
                    <td style="font-size:12px;color:#5A7A9A;">{{ $rule['limit_rate'] ? $rule['limit_rate'].'/'.($rule['limit_unit']??'s') : '—' }}</td>
                    <td><span class="pakkt-badge" style="font-size:10px;
                        background:{{ ($rule['status']??'')==='active'?'rgba(0,255,136,0.15)':'rgba(255,184,48,0.15)' }};
                        color:{{ ($rule['status']??'')==='active'?'#00FF88':'#FFB830' }}">
                        {{ $rule['status']??'unknown' }}</span></td>
                    <td>
                        <form method="POST"
                              action="{{ route('server.extensions.pakkt.nft.delete', ['server_uuid'=>$server->uuidShort,'rule_id'=>$rule['id']]) }}"
                              onsubmit="return confirm('Supprimer cette règle nftables ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="pakkt-btn pakkt-btn--danger pakkt-btn--sm"><i class="fa fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>

    </div>

    {{-- ════════════════════ PAKKT MARKETPLACE ══════════════════════════════ --}}
    {{--
        Widget React standalone — affiche tous les templates Gaming (XDP + NFTables).
        Les templates sont pré-chargés côté serveur (via pakktRequest) pour éviter
        d'exposer la clé API dans le navigateur.
        Les ports sont fixés aux allocations du serveur (non modifiables).
    --}}
    <div class="pakkt-card" style="margin-top:16px; padding:0; overflow:hidden;">
        <div style="padding:14px 16px; border-bottom:1px solid rgba(255,255,255,0.06);">
            <h3 style="margin:0; font-size:14px; font-weight:600;">Déployer une protection</h3>
        </div>
        <div id="pakkt-marketplace">
            <div id="pakkt-widget-loading" style="padding:24px 16px; color:#5A7A9A; font-family:monospace; font-size:13px;">
                Chargement du widget PAKKT…
            </div>
        </div>
    </div>

    @endif
</div>

<script>
function pakktTab(tab) {
    document.getElementById('panel-xdp').style.display = tab==='xdp' ? '' : 'none';
    document.getElementById('panel-nft').style.display  = tab==='nft' ? '' : 'none';
    document.getElementById('tab-xdp').className = 'pakkt-tab'+(tab==='xdp'?' pakkt-tab--active-xdp':'');
    document.getElementById('tab-nft').className = 'pakkt-tab'+(tab==='nft'?' pakkt-tab--active-nft':'');
    window.dispatchEvent(new CustomEvent('pakkt:tab', { detail: tab==='nft' ? 'nftables' : 'XDP' }));
}
function fmtNum(n){n=Number(n)||0;if(n>=1e9)return(n/1e9).toFixed(1)+'B pkkt';if(n>=1e6)return(n/1e6).toFixed(1)+'M pkkt';if(n>=1e3)return(n/1e3).toFixed(1)+'K pkkt';return n+' pkkt';}
function fmtBytes(b){b=Number(b)||0;if(b>=1e9)return(b/1e9).toFixed(2)+' GB';if(b>=1e6)return(b/1e6).toFixed(1)+' MB';if(b>=1e3)return(b/1e3).toFixed(1)+' KB';return b+' B';}

var _pakktChart = null;
function initChart(history) {
    var ctx = document.getElementById('pakkt-chart');
    if (!ctx) return;
    var empty = document.getElementById('chart-empty');
    if (!history || history.length === 0) {
        ctx.style.display = 'none';
        if (empty) empty.style.display = 'flex';
        return;
    }
    ctx.style.display = 'block';
    if (empty) empty.style.display = 'none';

    var labels  = history.map(function(r){ var d=new Date(r.bucket); return d.getHours().toString().padStart(2,'0')+':'+d.getMinutes().toString().padStart(2,'0'); });
    var passed  = history.map(function(r){ return Number(r.packets_passed)||0; });
    var dropped = history.map(function(r){ return Number(r.packets_dropped)||0; });

    if (_pakktChart) { _pakktChart.destroy(); }
    _pakktChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                { label: 'Passés', data: passed,  borderColor: '#00D2FF', backgroundColor: 'rgba(0,210,255,0.08)', tension: 0.35, fill: true, pointRadius: 0, borderWidth: 1.5 },
                { label: 'Bloqués', data: dropped, borderColor: '#FF2D55', backgroundColor: 'rgba(255,45,85,0.06)',  tension: 0.35, fill: true, pointRadius: 0, borderWidth: 1.5 },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false, animation: false,
            plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false,
                callbacks: { label: function(ctx){ return ctx.dataset.label+': '+fmtNum(ctx.parsed.y); } } } },
            scales: {
                x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#5A7A9A', font: { size: 10 }, maxTicksLimit: 8 } },
                y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#5A7A9A', font: { size: 10 },
                    callback: function(v){ if(v>=1e6)return(v/1e6).toFixed(0)+'M'; if(v>=1e3)return(v/1e3).toFixed(0)+'K'; return v; } } }
            }
        }
    });
}

function pakktLoadStats() {
    var btn    = document.getElementById('btn-refresh');
    var window = document.getElementById('stat-window') ? document.getElementById('stat-window').value : '1h';
    if (btn) { btn.disabled=true; btn.style.opacity='0.6'; btn.innerHTML='<i class="fa fa-spinner fa-spin"></i>'; }
    fetch('{{ route("server.extensions.pakkt.stats", $server->uuidShort) }}?window='+window, { headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } })
        .then(function(r){ return r.ok ? r.json() : null; })
        .then(function(d){
            if (!d) return;
            var s = d.summary || {};
            var passed  = Number(s.total_passed)||0;
            var dropped = Number(s.total_dropped)||0;
            document.getElementById('stat-passed').textContent       = fmtNum(passed);
            document.getElementById('stat-dropped').textContent      = fmtNum(dropped);
            document.getElementById('stat-bytes-passed').textContent = fmtBytes(Number(s.total_bytes_passed)||0);
            document.getElementById('stat-bytes-dropped').textContent= fmtBytes(Number(s.total_bytes_dropped)||0);
            if (typeof Chart !== 'undefined') initChart(d.history || []);
        })
        .catch(function(){})
        .finally(function(){
            if (btn) { btn.disabled=false; btn.style.opacity='1'; btn.innerHTML='<i class="fa fa-refresh"></i> Actualiser'; }
        });
}
document.addEventListener('DOMContentLoaded', function(){
    pakktLoadStats();
    setInterval(pakktLoadStats, 30000);
});
</script>

@if($configured && $agent_id)
{{-- Widget config — injected before the widget script so it's available at load time.
     Templates are pre-loaded server-side: the PAKKT API key is never exposed in the browser. --}}
<script>
window.PAKKT_WIDGET_CONFIG = {
    category:       'Gaming',
    initial_tab:    'XDP',
    templates:      {!! json_encode($templates) !!},
    fixed_ports:    {!! json_encode(array_values($ports)) !!},
    deploy_xdp_url: '{{ route("server.extensions.pakkt.xdp.create", $server->uuidShort) }}',
    deploy_nft_url: '{{ route("server.extensions.pakkt.nft.create", $server->uuidShort) }}',
    csrf_token:     '{{ csrf_token() }}',
};
</script>
<script src="{{ asset('extensions/pakkt/marketplace-widget.js') }}"
    defer
    onerror="document.getElementById('pakkt-widget-loading').innerHTML='❌ Widget PAKKT introuvable — relancez l\'installateur PAKKT.';"
></script>
@endif
</body>
</html>

@extends('layouts.admin')

@section('title', 'PAKKT.io Protection — Configuration')

@section('content-header')
    <h1>PAKKT.io Protection <small>Configuration de la protection XDP/nftables</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Administration</a></li>
        <li class="active">PAKKT.io Protection</li>
    </ol>
@endsection

@section('content')
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Barlow+Condensed:wght@700&family=Space+Mono&display=swap');
.pakkt-wrap { font-family: Inter, sans-serif; color: #D8E8F4; max-width: 960px; }
.pakkt-card { background: #0F1520; border: 1px solid rgba(0,210,255,0.15); border-radius: 4px; padding: 20px; margin-bottom: 16px; }
.pakkt-card h3 { color: #D8E8F4; font-family: 'Barlow Condensed', Inter, sans-serif; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; margin: 0 0 14px; }
.pakkt-card p, .pakkt-card label { color: #5A7A9A; }
.pakkt-badge { display: inline-block; padding: 2px 8px; border-radius: 2px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
.pakkt-badge--online  { background: rgba(0,255,136,.15); color: #00FF88; }
.pakkt-badge--offline { background: rgba(255,45,85,.15);  color: #FF2D55; }
.pakkt-badge--pending { background: rgba(255,184,48,.15); color: #FFB830; }
.pakkt-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.pakkt-table th { color: #5A7A9A; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: .05em; padding: 6px 10px; border-bottom: 1px solid rgba(255,255,255,.06); text-align: left; }
.pakkt-table td { padding: 8px 10px; border-bottom: 1px solid rgba(255,255,255,.04); color: #D8E8F4; vertical-align: middle; }
.pakkt-table tr:last-child td { border-bottom: none; }
.pakkt-table code { font-family: 'Space Mono', monospace; font-size: 11px; color: #00D2FF; background: rgba(0,210,255,.08); padding: 1px 5px; border-radius: 2px; }
.pakkt-table strong { color: #D8E8F4; }
.pakkt-table small { color: #3A5A7A; }
.pakkt-btn { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; border: none; cursor: pointer; clip-path: polygon(6px 0%,100% 0%,calc(100% - 6px) 100%,0% 100%); transition: opacity .15s; text-decoration: none; }
.pakkt-btn:hover { opacity: .85; }
.pakkt-btn--primary { background: #00D2FF; color: #06080C; }
.pakkt-btn--danger  { background: rgba(255,45,85,.2); color: #FF2D55; clip-path: none; border: 1px solid rgba(255,45,85,.3); padding: 4px 10px; }
.pakkt-input { background: #06080C; border: 1px solid rgba(0,210,255,.2); border-radius: 3px; color: #D8E8F4; padding: 7px 10px; font-size: 13px; width: 100%; box-sizing: border-box; transition: border-color .15s; }
.pakkt-input:focus { outline: none; border-color: #00D2FF; }
.pakkt-select { background: #06080C; border: 1px solid rgba(0,210,255,.2); border-radius: 3px; color: #D8E8F4; padding: 7px 10px; font-size: 13px; width: 100%; }
.pakkt-alert { padding: 10px 14px; border-radius: 3px; font-size: 13px; margin-bottom: 14px; }
.pakkt-alert--success { background: rgba(0,255,136,.08); border-left: 3px solid #00FF88; color: #00FF88; }
.pakkt-alert--error   { background: rgba(255,45,85,.08);  border-left: 3px solid #FF2D55; color: #FF2D55; }
.pakkt-alert--warn    { background: rgba(255,184,48,.08); border-left: 3px solid #FFB830; color: #FFB830; }
.pakkt-alert a        { color: inherit; text-decoration: underline; }
.pakkt-empty { text-align: center; padding: 32px; color: #3A5A7A; font-size: 13px; }
</style>
<div class="pakkt-wrap">

    @if(session('success'))
        <div class="pakkt-alert pakkt-alert--success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="pakkt-alert pakkt-alert--error">{{ session('error') }}</div>
    @endif

    {{-- ── Connection status banner ─────────────────────────────────────────── --}}
    @if($api_key)
        @if($connection_status === 'ok')
            <div class="pakkt-alert pakkt-alert--success">
                Connexion PAKKT API active
                @if($subscription)
                    — Plan <strong>{{ strtoupper($subscription['plan'] ?? '?') }}</strong>
                    @if(($subscription['status'] ?? '') === 'trialing')
                        <span class="pakkt-badge pakkt-badge--pending" style="margin-left:6px;">Trial</span>
                    @endif
                @endif
            </div>
        @else
            <div class="pakkt-alert pakkt-alert--error">Connexion PAKKT échouée — vérifiez la clé API.</div>
        @endif
    @else
        <div class="pakkt-alert pakkt-alert--warn">
            Aucune clé API configurée. Connectez-vous sur
            <a href="https://app.pakkt.io/api-keys" target="_blank" style="color:#FFB830;">app.pakkt.io</a>
            pour créer une clé API, puis configurez-la ci-dessous.
        </div>
    @endif

    {{-- ── API Settings ──────────────────────────────────────────────────────── --}}
    <div class="pakkt-card">
        <h3>Connexion PAKKT API</h3>
        <form action="{{ route('admin.extensions.pakkt.settings') }}" method="POST">
            @csrf
            <div style="margin-bottom:14px;">
                <label style="font-size:11px; color:#5A7A9A; text-transform:uppercase; letter-spacing:.05em; display:block; margin-bottom:5px;">
                    Clé API PAKKT
                </label>
                <input type="password" name="api_key" class="pakkt-input"
                       value="{{ $api_key }}" placeholder="pakkt_api_xxxxxxxxxxxxxxxxxxxx" autocomplete="off">
                @error('api_key')
                    <span style="color:#FF2D55; font-size:12px;">{{ $message }}</span>
                @enderror
            </div>
            <button type="submit" class="pakkt-btn pakkt-btn--primary">
                <i class="fa fa-save"></i> Sauvegarder et vérifier
            </button>
        </form>
    </div>


    {{-- ── Node → Agent mapping ─────────────────────────────────────────────── --}}
    <div class="pakkt-card">
        <h3>Mapping Nodes → Agents PAKKT</h3>
        <p style="font-size:13px; color:#5A7A9A; margin-bottom:14px;">
            Associez chaque node Pterodactyl (Wings) à son agent PAKKT installé dessus.
            L'agent PAKKT doit être installé sur chaque node via le script d'installation.
        </p>

        @if(count($nodes) === 0)
            <div class="pakkt-empty">Aucun node configuré dans Pterodactyl.</div>
        @else
        <form action="{{ route('admin.extensions.pakkt.nodes') }}" method="POST">
            @csrf
            <table class="pakkt-table" style="margin-bottom:14px;">
                <thead>
                    <tr>
                        <th>Node</th>
                        <th>FQDN / IP</th>
                        <th>Agent PAKKT ID</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($nodes as $node)
                    @php
                        $mapped_agent = $node_agents->get($node->id);
                        $agent_info   = $mapped_agent
                            ? collect($pakkt_agents)->firstWhere('id', $mapped_agent->agent_id)
                            : null;
                    @endphp
                    <tr>
                        <td><strong>{{ $node->name }}</strong> <small style="color:#3A5A7A;">#{{ $node->id }}</small></td>
                        <td><code>{{ $node->fqdn }}</code></td>
                        <td>
                            @if(count($pakkt_agents) > 0)
                                <select name="mappings[{{ $node->id }}]" class="pakkt-select" style="width:220px;">
                                    <option value="">— Non mappé —</option>
                                    @foreach($pakkt_agents as $agent)
                                        <option value="{{ $agent['id'] }}"
                                            {{ ($mapped_agent && $mapped_agent->agent_id === $agent['id']) ? 'selected' : '' }}>
                                            {{ $agent['name'] }} ({{ $agent['id'] }})
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" name="mappings[{{ $node->id }}]"
                                       class="pakkt-input" style="width:220px;"
                                       value="{{ $mapped_agent->agent_id ?? '' }}"
                                       placeholder="ID agent PAKKT">
                            @endif
                        </td>
                        <td>
                            @if($agent_info)
                                <span class="pakkt-badge pakkt-badge--{{ ($agent_info['status'] ?? '') === 'online' ? 'online' : 'offline' }}">
                                    {{ $agent_info['status'] ?? 'inconnu' }}
                                </span>
                            @elseif($mapped_agent)
                                <span class="pakkt-badge pakkt-badge--pending">Non vérifié</span>
                            @else
                                <span style="color:#3A5A7A; font-size:12px;">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <button type="submit" class="pakkt-btn pakkt-btn--primary">
                <i class="fa fa-link"></i> Sauvegarder le mapping
            </button>
        </form>
        @endif
    </div>

    {{-- ── Wings installation instructions ───────────────────────────────────── --}}
    <div class="pakkt-card">
        <h3>Installer l'agent sur les nodes Wings</h3>
        <p style="font-size:13px; color:#5A7A9A; margin-bottom:12px;">
            Chaque node Wings doit avoir son propre agent PAKKT.
        </p>
        <ol style="font-size:13px; color:#5A7A9A; margin:0 0 14px; padding-left:20px; line-height:2;">
            <li>Rendez-vous sur <a href="https://app.pakkt.io/agents" target="_blank" style="color:#00D2FF; text-decoration:underline;">app.pakkt.io/agents</a> et créez <strong style="color:#D8E8F4;">un agent par node Wings</strong>.</li>
            <li>Installez chaque agent sur son node en suivant les instructions affichées lors de la création.</li>
            <li>Revenez ici et <strong style="color:#D8E8F4;">mappez chaque node</strong> à son agent dans le tableau ci-dessus.</li>
        </ol>
        <p style="font-size:12px; color:#3A5A7A;">
            L'agent se met à jour automatiquement depuis PAKKT.io — aucune maintenance requise après installation.
        </p>
    </div>

</div>
@endsection

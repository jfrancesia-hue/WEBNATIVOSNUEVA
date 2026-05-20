<?php
declare(strict_types=1);

// Dashboard frontend-only (PHP + HTML + vanilla JS)
// No hardcoded metrics: everything comes from API.
$apiBase = rtrim((string)($_ENV['ANALYTICS_API_BASE'] ?? ''), '/');

// Defaults (dynamic, not hardcoded values)
$today = new DateTimeImmutable('today');
$fromDefault = $today->sub(new DateInterval('P6D'))->format('Y-m-d'); // last 7 days incl today
$toDefault   = $today->add(new DateInterval('P1D'))->format('Y-m-d'); // exclusive upper bound

$from = isset($_GET['from']) ? preg_replace('/[^0-9\-]/', '', (string)$_GET['from']) : $fromDefault;
$to   = isset($_GET['to'])   ? preg_replace('/[^0-9\-]/', '', (string)$_GET['to'])   : $toDefault;
$g    = isset($_GET['g'])    ? strtolower(preg_replace('/[^a-z]/', '', (string)$_GET['g'])) : 'day';
if (!in_array($g, ['day','week','month'], true)) $g = 'day';

// Endpoint configurable (same host by default)
$endpoint = ($apiBase !== '' ? $apiBase : '') . '/api/metrics.php';

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard Analytics</title>

  <style>
    :root { color-scheme: light; }
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 0; background:#f6f7fb; color:#111; }
    .wrap { max-width: 1100px; margin: 0 auto; padding: 18px; }
    header { display:flex; gap:12px; align-items:center; justify-content:space-between; flex-wrap:wrap; }
    h1 { font-size: 18px; margin: 0; }
    .card { background:#fff; border:1px solid #e7e8ef; border-radius:14px; padding:14px; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
    .grid { display:grid; gap:12px; }
    .kpis { grid-template-columns: repeat(4, minmax(0,1fr)); }
    .kpi .label { font-size: 12px; color:#666; }
    .kpi .value { font-size: 22px; font-weight: 700; margin-top: 6px; }
    .filters { display:flex; gap:10px; flex-wrap:wrap; align-items:end; }
    label { font-size: 12px; color:#555; display:block; margin-bottom:6px; }
    input, select, button {
      border:1px solid #d8d9e3; border-radius:10px; padding:10px 12px;
      font-size: 14px; background:#fff;
    }
    button { cursor:pointer; font-weight:600; }
    button:disabled { cursor:not-allowed; opacity:.6; }
    .row { display:grid; gap:12px; grid-template-columns: 1.6fr .9fr; }
    .muted { color:#666; font-size: 12px; }
    .error { color:#b00020; font-size: 13px; }
    canvas { width:100%; height:280px; display:block; }
    table { width:100%; border-collapse:collapse; font-size: 13px; }
    th, td { text-align:left; padding:10px; border-bottom:1px solid #eee; }
    th { color:#555; font-weight:700; }
    @media (max-width: 900px){
      .kpis { grid-template-columns: repeat(2, minmax(0,1fr)); }
      .row { grid-template-columns: 1fr; }
    }
  </style>

  <!-- ✅ SUPABASE: cargar e inicializar ANTES de usarlo -->
  <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
  <script>
    // ⚠️ RECOMENDADO: mover estas claves a variables de entorno del servidor si podés.
    // Pero la anon key puede estar en frontend (la seguridad real la da RLS).
    const SUPABASE_URL = "https://dhhhftzdfpqthzvkrqoz.supabase.co";
    const SUPABASE_ANON_KEY = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImRoaGhmdHpkZnBxdGh6dmtycW96Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDQ2OTQyODUsImV4cCI6MjA2MDI3MDI4NX0.-atBYl9Uica9quKZQzqmgWQ8wNd1PFB4ivLrSNv89OQ";

    // Lo guardo como window.sb para evitar conflictos
    window.sb = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);
  </script>
</head>

<body>
  <div class="wrap">
    <header>
      <h1>Dashboard Analytics</h1>
      <div class="muted" id="status">Listo.</div>
    </header>


    <div class="grid kpis" style="margin-top:12px;">
      <div class="card kpi">
        

      </div>
     <div class="card kpi">
  <div class="label">Packs vendidos hoy</div>
  <div class="value" id="k_packs_hoy">—</div>

  <div class="muted" style="margin-top:8px; line-height:1.6" id="k_packs_detalle">
    —
  </div>
</div>

      <div class="card kpi">
  <div class="label">Registros de trabajadores TOTALES</div>

  <div class="value" id="k_workers_completos">—</div>

  <div class="muted" style="margin-top:8px; line-height:1.5">
    No completados: <strong id="k_workers_incompletos">—</strong>
  </div>
</div>


      <div class="card kpi">
  <div class="label">Servicios ofrecidos TOTALES</div>
  <div class="value" id="k_servicios_total">—</div>
</div>

    </div>
          <select id="metric">
            <option value="active_users">Usuarios activos</option>
            <option value="signups">Registros</option>
            <option value="hires_created">Contrataciones creadas</option>
            <option value="hires_completed">Contrataciones completadas</option>
          </select>
    
    <div class="column" style="margin-top:12px;">
  <div class="card">
    <div style="font-weight:700;">Supabase · pagos_procesados (últimos 10)</div>
    <div class="muted">Si no aparecen datos, mirá consola (F12). 404 = tabla mal; 401/403 = RLS/policies.</div>
    <div id="pp_err" class="error" style="margin-top:10px; display:none;"></div>
    <div style="margin-top:10px; overflow:auto;">
      <table>
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Email</th>
            <th>Título</th>
            <th>Payment ID</th>
            <th>Transaction</th>
          </tr>
        </thead>
        <tbody id="pp_tbody">
          <tr><td colspan="5" class="muted">Cargando…</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div style="font-weight:700;">Supabase · usuarios (últimos 10)</div>
    <div class="muted">Muestra campos básicos.</div>
    <div id="u_err" class="error" style="margin-top:10px; display:none;"></div>
    <div style="margin-top:10px; overflow:auto;">
      <table>
        <thead>
          <tr>
            <th>Creado</th>
            <th>Email</th>
            <th>Nombre</th>
            <th>Rol</th>
            <th>Pago</th>
            <th>Créditos</th>
          </tr>
        </thead>
        <tbody id="u_tbody">
          <tr><td colspan="6" class="muted">Cargando…</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
    

  </div>

<script>
(() => {
  const endpoint = <?= json_encode($endpoint, JSON_UNESCAPED_SLASHES) ?>;

  const els = {
    status: document.getElementById('status'),
    err: document.getElementById('err'),
    btn: document.getElementById('btn'),
    metric: document.getElementById('metric'),
    subtitle: document.getElementById('subtitle'),

    k_active_users: document.getElementById('k_active_users'),
    k_signups: document.getElementById('k_signups'),
    k_hires_created: document.getElementById('k_hires_created'),
    k_hires_completed: document.getElementById('k_hires_completed'),

    tbody: document.getElementById('tbody'),
    chart: document.getElementById('chart'),

    lista: document.getElementById('lista'),
    sb_err: document.getElementById('sb_err'),
  };

  function fmt(n){
    if (n === null || n === undefined || Number.isNaN(Number(n))) return '—';
    return new Intl.NumberFormat('es-AR').format(Number(n));
  }

  function setError(msg){
    els.err.style.display = msg ? 'block' : 'none';
    els.err.textContent = msg || '';
  }

  async function load(){
    setError('');
    els.btn.disabled = true;
    els.status.textContent = 'Cargando datos…';

    const params = new URLSearchParams(window.location.search);
    const url = endpoint + (endpoint.includes('?') ? '&' : '?') + params.toString();

    try{
      const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
      if(!res.ok){
        throw new Error(`API respondió ${res.status}`);
      }
      const data = await res.json();
      render(data);
      els.status.textContent = 'Actualizado.';
    }catch(e){
      console.error(e);
      setError('No se pudo cargar la API. Verificá el endpoint y el formato JSON esperado.');
      els.status.textContent = 'Error.';
      ['k_active_users','k_signups','k_hires_created','k_hires_completed'].forEach(id => els[id].textContent = '—');
      els.tbody.innerHTML = `<tr><td colspan="5" class="muted">Sin datos</td></tr>`;
      drawChart([], 'active_users');
    }finally{
      els.btn.disabled = false;
    }
  }

  function render(data){
    const k = data?.kpis || {};
    els.k_active_users.textContent = fmt(k.active_users);
    els.k_signups.textContent = fmt(k.signups);
    els.k_hires_created.textContent = fmt(k.hires_created);
    els.k_hires_completed.textContent = fmt(k.hires_completed);

    const series = Array.isArray(data?.series) ? data.series : [];
    els.subtitle.textContent = `${series.length} períodos en el rango`;

    if(series.length === 0){
      els.tbody.innerHTML = `<tr><td colspan="5" class="muted">Sin datos</td></tr>`;
    } else {
      els.tbody.innerHTML = series
        .slice(-50)
        .map(r => `
          <tr>
            <td>${escapeHtml(r.period ?? '')}</td>
            <td>${fmt(r.active_users)}</td>
            <td>${fmt(r.signups)}</td>
            <td>${fmt(r.hires_created)}</td>
            <td>${fmt(r.hires_completed)}</td>
          </tr>
        `).join('');
    }

    drawChart(series, els.metric.value);
  }

  function escapeHtml(s){
    return String(s).replace(/[&<>"']/g, (m) => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
    }[m]));
  }

  function drawChart(series, metric){
    const c = els.chart;
    const ctx = c.getContext('2d');
    const w = c.width, h = c.height;
    ctx.clearRect(0,0,w,h);

    const padL = 50, padR = 16, padT = 18, padB = 34;
    const plotW = w - padL - padR;
    const plotH = h - padT - padB;

    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0,0,w,h);

    ctx.fillStyle = '#111';
    ctx.font = '12px system-ui';
    ctx.fillText(metricLabel(metric), padL, 14);

    const values = series.map(r => Number(r?.[metric] ?? 0));
    const maxV = values.length ? Math.max(...values) : 0;

    ctx.strokeStyle = '#eee';
    ctx.lineWidth = 1;
    const gridLines = 4;
    for(let i=0;i<=gridLines;i++){
      const y = padT + (plotH * i / gridLines);
      ctx.beginPath();
      ctx.moveTo(padL, y);
      ctx.lineTo(padL + plotW, y);
      ctx.stroke();
      const v = Math.round(maxV - (maxV * i / gridLines));
      ctx.fillStyle = '#666';
      ctx.fillText(fmt(v), 6, y+4);
    }

    ctx.strokeStyle = '#ddd';
    ctx.beginPath();
    ctx.moveTo(padL, padT + plotH);
    ctx.lineTo(padL + plotW, padT + plotH);
    ctx.stroke();

    if(series.length < 2){
      ctx.fillStyle = '#666';
      ctx.fillText('Sin datos suficientes para graficar', padL, padT + 30);
      return;
    }

    ctx.strokeStyle = '#111';
    ctx.lineWidth = 2;
    ctx.beginPath();

    series.forEach((r, i) => {
      const x = padL + (plotW * i / (series.length - 1));
      const v = Number(r?.[metric] ?? 0);
      const y = padT + plotH - (maxV > 0 ? (plotH * v / maxV) : 0);
      if(i === 0) ctx.moveTo(x, y);
      else ctx.lineTo(x, y);
    });
    ctx.stroke();

    ctx.fillStyle = '#666';
    ctx.font = '11px system-ui';
    ctx.fillText(String(series[0].period ?? ''), padL, padT + plotH + 22);
    const endLabel = String(series[series.length-1].period ?? '');
    const textW = ctx.measureText(endLabel).width;
    ctx.fillText(endLabel, padL + plotW - textW, padT + plotH + 22);
  }

  function metricLabel(m){
    return ({
      active_users: 'Usuarios activos',
      signups: 'Registros',
      hires_created: 'Contrataciones creadas',
      hires_completed: 'Contrataciones completadas'
    })[m] || m;
  }

  // ✅ Supabase demo loader
  async function cargarOrders(){
    try{
      els.sb_err.style.display = 'none';
      els.sb_err.textContent = '';

      if(!window.sb){
        throw new Error('Supabase no inicializado (window.sb es undefined).');
      }

      const { data, error } = await window.sb
        .from("orders")
        .select("id, title, created_at")
        .limit(10);

      if (error) throw error;

      console.log("Supabase orders:", data);

      els.lista.innerHTML = '';
      if(!data || data.length === 0){
        els.lista.innerHTML = '<li class="muted">Sin filas (o RLS bloqueando).</li>';
        return;
      }

      data.forEach(item => {
        const li = document.createElement("li");
        li.textContent = `${item.title ?? "(sin title)"} — ${item.created_at ?? ""}`;
        els.lista.appendChild(li);
      });

    }catch(e){
      console.error("Supabase error:", e);
      els.lista.innerHTML = '';
      els.sb_err.style.display = 'block';
      els.sb_err.textContent =
        'Supabase no devolvió datos. Mirá la consola (F12). Si el error dice "RLS", necesitás policies o login.';
    }
  }

  els.metric.addEventListener('change', () => load());

  // initial loads
load();
cargarPagosProcesados();
cargarUsuarios();
cargarPacksHoy();
cargarWorkersCompletosEIncompletos();
cargarTotalServicios();


})();


function showErr(el, msg){
  el.style.display = msg ? 'block' : 'none';
  el.textContent = msg || '';
}

function td(val){
  return (val === null || val === undefined || val === '') ? '—' : String(val);
}

async function cargarPagosProcesados(){
  const tbody = document.getElementById('pp_tbody');
  const errEl = document.getElementById('pp_err');

  try{
    showErr(errEl, '');
    if(!window.sb) throw new Error('Supabase no inicializado (window.sb).');

    const { data, error } = await window.sb
      .from('pagos_procesados')
      .select('creado_en,email,title,payment_id,transaction_id,libelula_id_transaccion')
      .order('creado_en', { ascending: false })
      .limit(10);

    if(error) throw error;

    if(!data || data.length === 0){
      tbody.innerHTML = `<tr><td colspan="5" class="muted">Sin filas (o bloqueado por RLS).</td></tr>`;
      return;
    }

    tbody.innerHTML = data.map(r => `
      <tr>
        <td>${td(r.creado_en)}</td>
        <td>${td(r.email)}</td>
        <td>${td(r.title)}</td>
        <td>${td(r.payment_id)}</td>
        <td>${td(r.transaction_id || r.libelula_id_transaccion)}</td>
      </tr>
    `).join('');

  }catch(e){
    console.error('Supabase pagos_procesados error:', e);
    tbody.innerHTML = `<tr><td colspan="5" class="muted">Error al cargar</td></tr>`;
    showErr(errEl, 'No se pudo leer pagos_procesados. Si el error dice RLS/401/403, faltan policies o login.');
  }
}

async function cargarUsuarios(){
  const tbody = document.getElementById('u_tbody');
  const errEl = document.getElementById('u_err');

  try{
    showErr(errEl, '');
    if(!window.sb) throw new Error('Supabase no inicializado (window.sb).');

    const { data, error } = await window.sb
      .from('usuarios')
      .select('creado_en,email,nombre,apellido,rol,pago,creditos')
      .order('creado_en', { ascending: false })
      .limit(10);

    if(error) throw error;

    if(!data || data.length === 0){
      tbody.innerHTML = `<tr><td colspan="6" class="muted">Sin filas (o bloqueado por RLS).</td></tr>`;
      return;
    }

    tbody.innerHTML = data.map(r => `
      <tr>
        <td>${td(r.creado_en)}</td>
        <td>${td(r.email)}</td>
        <td>${td([r.nombre, r.apellido].filter(Boolean).join(' '))}</td>
        <td>${td(r.rol)}</td>
        <td>${r.pago ? 'Sí' : 'No'}</td>
        <td>${td(r.creditos)}</td>
      </tr>
    `).join('');

  }catch(e){
    console.error('Supabase usuarios error:', e);
    tbody.innerHTML = `<tr><td colspan="6" class="muted">Error al cargar</td></tr>`;
    showErr(errEl, 'No se pudo leer usuarios. Si el error dice RLS/401/403, faltan policies o login.');
  }
}


function normalizarTexto(s){
  return String(s || '')
    .trim()
    .toLowerCase()
    .normalize('NFD')                 // separa tildes
    .replace(/[\u0300-\u036f]/g, '')  // borra tildes
}

function startEndHoyCatamarca(){
  // Calcula inicio/fin de "hoy" en America/Argentina/Catamarca,
  // pero devolviendo ISO en UTC para comparar con timestamptz.
  const tz = 'America/Argentina/Catamarca';

  // Hora local en esa TZ
  const now = new Date();
  const parts = new Intl.DateTimeFormat('en-CA', {
    timeZone: tz,
    year: 'numeric', month: '2-digit', day: '2-digit'
  }).formatToParts(now);

  const y = parts.find(p => p.type === 'year').value;
  const m = parts.find(p => p.type === 'month').value;
  const d = parts.find(p => p.type === 'day').value;

  // Armamos "YYYY-MM-DDT00:00:00" interpretado en TZ, y lo convertimos a UTC aproximando:
  // Truco: generamos Date UTC y luego ajustamos por offset real de esa TZ.
  // Para evitar líos, hacemos el filtro por string "YYYY-MM-DD" usando la TZ en el loop
  // (más abajo). Igual necesitamos limitar datos: usamos últimos 200 del día aprox.
  return { y, m, d, tz };
}

async function cargarPacksHoy(){
  const elTotal = document.getElementById('k_packs_hoy');
  const elDet = document.getElementById('k_packs_detalle');

  try{
    if(!window.sb) throw new Error('Supabase no inicializado (window.sb).');

    // Traemos una ventana "amplia" (últimos 2 días) y filtramos por TZ en JS
    // Así evitamos problemas de UTC vs -03.
    const now = new Date();
    const startWide = new Date(now.getTime() - 48 * 60 * 60 * 1000).toISOString();

    const { data, error } = await window.sb
      .from('pagos_procesados')
      .select('title, creado_en')
      .gte('creado_en', startWide)
      .order('creado_en', { ascending: false })
      .limit(500);

    if(error) throw error;

    // Packs a contar (por prefijo)
    const packs = [
      { key: 'credito simple', label: 'Credito Simple' },
      { key: 'plan basico', label: 'Plan Básico' },
      { key: 'plan ilimitado', label: 'Plan Ilimitado' },
      { key: 'plan pro', label: 'Plan Pro' },
    ];

    const counts = Object.fromEntries(packs.map(p => [p.label, 0]));
    let total = 0;

    // Fecha de "hoy" en Catamarca: YYYY-MM-DD
    const { y, m, d, tz } = startEndHoyCatamarca();
    const hoyStr = `${y}-${m}-${d}`;

    // Func para obtener YYYY-MM-DD de un timestamptz en esa TZ
    const ymdInTZ = (iso) => {
      const parts = new Intl.DateTimeFormat('en-CA', {
        timeZone: tz,
        year: 'numeric', month: '2-digit', day: '2-digit'
      }).formatToParts(new Date(iso));
      const yy = parts.find(p => p.type === 'year').value;
      const mm = parts.find(p => p.type === 'month').value;
      const dd = parts.find(p => p.type === 'day').value;
      return `${yy}-${mm}-${dd}`;
    };

    (data || []).forEach(r => {
      // Filtrar solo los que caen "hoy" en Catamarca
      if(!r?.creado_en) return;
      if(ymdInTZ(r.creado_en) !== hoyStr) return;

      const t = normalizarTexto(r?.title);

      // Match por "empieza con"
      for(const p of packs){
        if(t.startsWith(p.key)){
          counts[p.label] += 1;
          total += 1;
          break;
        }
      }
    });

    elTotal.textContent = new Intl.NumberFormat('es-AR').format(total);
    elDet.innerHTML = packs.map(p => {
      const n = counts[p.label] || 0;
      return `• ${p.label}: <strong>${new Intl.NumberFormat('es-AR').format(n)}</strong>`;
    }).join('<br>');

  }catch(e){
    console.error('Supabase packs hoy error:', e);
    elTotal.textContent = '—';
    elDet.textContent = 'No se pudo cargar (revisá consola / RLS).';
  }
}


async function cargarWorkersCompletosEIncompletos(){
  const elCompletos = document.getElementById('k_workers_completos');
  const elIncompletos = document.getElementById('k_workers_incompletos');

  try{
    if(!window.sb) throw new Error('Supabase no inicializado (window.sb).');

    // 1️⃣ Workers COMPLETOS
    const { count: completos, error: errCompletos } = await window.sb
      .from('usuarios')
      .select('id', { count: 'exact', head: true })
      .eq('rol', 'worker')
      .eq('perfil_completo', true);

    if(errCompletos) throw errCompletos;

    // 2️⃣ Registros NO completados
    const { count: incompletos, error: errIncompletos } = await window.sb
      .from('usuarios')
      .select('id', { count: 'exact', head: true })
      .neq('rol', 'worker')
      .eq('perfil_completo', false);

    if(errIncompletos) throw errIncompletos;

    elCompletos.textContent = new Intl.NumberFormat('es-AR').format(completos ?? 0);
    elIncompletos.textContent = new Intl.NumberFormat('es-AR').format(incompletos ?? 0);

  }catch(e){
    console.error('Supabase workers completos/incompletos error:', e);
    elCompletos.textContent = '—';
    elIncompletos.textContent = '—';
  }
}

async function cargarTotalServicios(){
  const el = document.getElementById('k_servicios_total');

  try{
    if(!window.sb) throw new Error('Supabase no inicializado (window.sb).');

    const { count, error } = await window.sb
      .from('servicios')
      .select('id', { count: 'exact', head: true });

    if(error) throw error;

    el.textContent = new Intl.NumberFormat('es-AR').format(count ?? 0);

  }catch(e){
    console.error('Supabase servicios total error:', e);
    el.textContent = '—';
  }
}


</script>



</body>
</html>

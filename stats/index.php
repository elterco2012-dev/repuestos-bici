<?php
$USUARIO = 'jorge';
$CLAVE    = 'bici2024';

session_start();

if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['user']) && isset($_POST['pass'])) {
    if ($_POST['user'] === $USUARIO && $_POST['pass'] === $CLAVE) {
        $_SESSION['ok'] = true;
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }
}

$logueado = isset($_SESSION['ok']) && $_SESSION['ok'] === true;

// --- Leer datos ---
$visits = array();
if ($logueado) {
    $file = __DIR__ . '/../visits.json';
    if (file_exists($file)) {
        $raw = file_get_contents($file);
        if ($raw !== false) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $visits = $decoded;
            }
        }
    }
    ksort($visits);

    // Tarjetas
    $hoy     = date('Y-m-d');
    $semana  = date('Y-m-d', strtotime('-6 days'));
    $mes     = date('Y-m');

    $total   = array_sum($visits);
    $visHoy  = isset($visits[$hoy]) ? $visits[$hoy] : 0;

    $visSemana = 0;
    foreach ($visits as $dia => $cnt) {
        if ($dia >= $semana) {
            $visSemana += $cnt;
        }
    }

    $visMes = 0;
    foreach ($visits as $dia => $cnt) {
        if (substr($dia, 0, 7) === $mes) {
            $visMes += $cnt;
        }
    }

    // Últimos 30 días para el gráfico
    $graf = array();
    for ($i = 29; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $graf[$d] = isset($visits[$d]) ? $visits[$d] : 0;
    }
    $maxGraf = max(array_values($graf));
    if ($maxGraf === 0) { $maxGraf = 1; }

    // Tabla mensual
    $meses = array();
    foreach ($visits as $dia => $cnt) {
        $m = substr($dia, 0, 7);
        if (!isset($meses[$m])) { $meses[$m] = 0; }
        $meses[$m] += $cnt;
    }
    ksort($meses);
}

function mesLabel($ym) {
    $nombres = array(
        '01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril',
        '05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto',
        '09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'
    );
    $partes = explode('-', $ym);
    return $nombres[$partes[1]] . ' ' . $partes[0];
}
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Estadísticas — Repuestos de Bici Jorge</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: system-ui, sans-serif;
    background: #f1f5f9;
    color: #1e293b;
    min-height: 100vh;
  }

  /* --- Login --- */
  .login-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
  }
  .login-box {
    background: #fff;
    border-radius: 12px;
    padding: 2.5rem 2rem;
    width: 100%;
    max-width: 360px;
    box-shadow: 0 4px 24px rgba(0,0,0,.08);
  }
  .login-box h1 {
    font-size: 1.3rem;
    margin-bottom: .25rem;
  }
  .login-box p { color: #64748b; font-size: .9rem; margin-bottom: 1.5rem; }
  .login-box label { display: block; font-size: .85rem; font-weight: 600; margin-bottom: .3rem; }
  .login-box input[type=text],
  .login-box input[type=password] {
    width: 100%;
    padding: .55rem .75rem;
    border: 1.5px solid #cbd5e1;
    border-radius: 7px;
    font-size: 1rem;
    margin-bottom: 1rem;
    outline: none;
  }
  .login-box input:focus { border-color: #166534; }
  .login-box button {
    width: 100%;
    background: #166534;
    color: #fff;
    border: none;
    border-radius: 7px;
    padding: .65rem;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
  }
  .login-box button:hover { background: #14532d; }
  .error-msg {
    background: #fee2e2;
    color: #b91c1c;
    padding: .5rem .75rem;
    border-radius: 6px;
    font-size: .85rem;
    margin-bottom: 1rem;
  }

  /* --- Panel --- */
  header {
    background: #166534;
    color: #fff;
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  header h1 { font-size: 1.1rem; }
  header form button {
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.4);
    color: #fff;
    padding: .35rem .85rem;
    border-radius: 6px;
    cursor: pointer;
    font-size: .85rem;
  }
  header form button:hover { background: rgba(255,255,255,.25); }

  main { max-width: 900px; margin: 0 auto; padding: 1.5rem 1rem 3rem; }

  h2 { font-size: 1rem; font-weight: 700; margin-bottom: 1rem; color: #334155; }

  /* Tarjetas */
  .cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
  }
  .card {
    background: #fff;
    border-radius: 10px;
    padding: 1.25rem 1rem;
    box-shadow: 0 1px 6px rgba(0,0,0,.07);
    text-align: center;
  }
  .card .num {
    font-size: 2.2rem;
    font-weight: 800;
    color: #166534;
    line-height: 1;
  }
  .card .lbl {
    font-size: .8rem;
    color: #64748b;
    margin-top: .35rem;
  }

  /* Gráfico */
  .chart-wrap {
    background: #fff;
    border-radius: 10px;
    padding: 1.25rem;
    box-shadow: 0 1px 6px rgba(0,0,0,.07);
    margin-bottom: 2rem;
    overflow-x: auto;
  }
  .chart {
    display: flex;
    align-items: flex-end;
    gap: 3px;
    height: 120px;
    padding-bottom: 24px;
    min-width: 600px;
    position: relative;
  }
  .bar-col {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-end;
    height: 100%;
    position: relative;
  }
  .bar {
    width: 100%;
    background: #166534;
    border-radius: 3px 3px 0 0;
    min-height: 2px;
    transition: opacity .15s;
  }
  .bar:hover { opacity: .75; }
  .bar-label {
    position: absolute;
    bottom: 0;
    font-size: 9px;
    color: #94a3b8;
    white-space: nowrap;
    transform: rotate(-45deg) translateX(-4px);
    transform-origin: top left;
  }
  .bar-val {
    font-size: 9px;
    color: #475569;
    margin-bottom: 2px;
    font-weight: 600;
  }

  /* Tabla */
  .table-wrap {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 1px 6px rgba(0,0,0,.07);
    overflow: hidden;
  }
  table { width: 100%; border-collapse: collapse; }
  th, td {
    padding: .65rem 1rem;
    text-align: left;
    font-size: .9rem;
  }
  th {
    background: #f8fafc;
    color: #64748b;
    font-weight: 600;
    font-size: .8rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    border-bottom: 1px solid #e2e8f0;
  }
  tr:not(:last-child) td { border-bottom: 1px solid #f1f5f9; }
  tr:hover td { background: #f8fafc; }
  td:last-child { text-align: right; font-weight: 700; color: #166534; }

  .empty { color: #94a3b8; text-align: center; padding: 2rem; }
</style>
</head>
<body>

<?php if (!$logueado): ?>
<div class="login-wrap">
  <div class="login-box">
    <h1>🚲 Repuestos de Bici</h1>
    <p>Panel de estadísticas</p>
    <?php if (isset($error)): ?>
      <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="POST">
      <label for="user">Usuario</label>
      <input type="text" id="user" name="user" autocomplete="username" required>
      <label for="pass">Contraseña</label>
      <input type="password" id="pass" name="pass" autocomplete="current-password" required>
      <button type="submit">Entrar</button>
    </form>
  </div>
</div>

<?php else: ?>
<header>
  <h1>🚲 Estadísticas de visitas</h1>
  <form method="POST">
    <button type="submit" name="logout" value="1">Cerrar sesión</button>
  </form>
</header>

<main>
  <h2>Resumen</h2>
  <div class="cards">
    <div class="card">
      <div class="num"><?php echo $visHoy; ?></div>
      <div class="lbl">Hoy</div>
    </div>
    <div class="card">
      <div class="num"><?php echo $visSemana; ?></div>
      <div class="lbl">Últimos 7 días</div>
    </div>
    <div class="card">
      <div class="num"><?php echo $visMes; ?></div>
      <div class="lbl">Este mes</div>
    </div>
    <div class="card">
      <div class="num"><?php echo $total; ?></div>
      <div class="lbl">Total histórico</div>
    </div>
  </div>

  <h2>Últimos 30 días</h2>
  <div class="chart-wrap">
    <?php if ($total === 0): ?>
      <p class="empty">Sin datos todavía.</p>
    <?php else: ?>
    <div class="chart">
      <?php foreach ($graf as $dia => $cnt): ?>
        <?php $pct = round(($cnt / $maxGraf) * 96); ?>
        <div class="bar-col">
          <?php if ($cnt > 0): ?><div class="bar-val"><?php echo $cnt; ?></div><?php endif; ?>
          <div class="bar" style="height:<?php echo max($pct, $cnt > 0 ? 4 : 0); ?>%;" title="<?php echo $dia . ': ' . $cnt; ?>"></div>
          <span class="bar-label"><?php echo substr($dia, 5); ?></span>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <h2>Por mes</h2>
  <div class="table-wrap">
    <?php if (empty($meses)): ?>
      <p class="empty">Sin datos todavía.</p>
    <?php else: ?>
    <table>
      <thead>
        <tr><th>Mes</th><th style="text-align:right">Visitas</th></tr>
      </thead>
      <tbody>
        <?php foreach (array_reverse($meses, true) as $ym => $cnt): ?>
          <tr>
            <td><?php echo mesLabel($ym); ?></td>
            <td><?php echo $cnt; ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</main>
<?php endif; ?>
</body>
</html>

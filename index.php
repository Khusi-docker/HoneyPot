<?php
session_start();

// --- Handle incoming JS-reported IP (AJAX POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['js_ip']) && !isset($_POST['username'])) {
    $js_ip = trim($_POST['js_ip'] ?? '');
    if (filter_var($js_ip, FILTER_VALIDATE_IP)) {
        $_SESSION['visitor_ip'] = $js_ip;
        file_put_contents(__DIR__ . '/loggedip.txt', $js_ip . PHP_EOL, FILE_APPEND | LOCK_EX);
        echo 'OK';
        exit;
    }
    http_response_code(400);
    echo 'Invalid IP';
    exit;
}

function getFallbackIP() {
    return !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'UNKNOWN';
}

//  Last system IP from file
//  $sys_ip = shell_exec("tail -n 1 sys_ip | cut -d ' ' -f 4");                                                  //for ssh hosting
    $sys_ip = shell_exec("tail -n 1 /var/log/apache2/access.log | cut -d '-' -f1 ");                             //for non ssh hosting
//  $ssh_url = shell_exec("tail -n 1 sys_ip | cut -d ' ' -f 6");


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {

    $username = $_POST['username'];
    $password = $_POST['password'];
    $visitor_ip = $_SESSION['visitor_ip'] ?? getFallbackIP();
    file_put_contents(__DIR__ . '/loggedip.txt', "[login] " . $visitor_ip . PHP_EOL, FILE_APPEND | LOCK_EX);

    $ok = shell_exec("bash bash_actions/auth.sh $username $password $sys_ip");

    // IPv6 detection
    $visitor_ip6 = null;
    if (!empty($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $visitor_ip6 = $_SERVER['REMOTE_ADDR'];
    } else {
        $headers = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CF_CONNECTING_IP'];
        foreach ($headers as $h) {
            if (!empty($_SERVER[$h])) {
                $ips = explode(',', $_SERVER[$h]);
                foreach ($ips as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                        $visitor_ip6 = $ip;
                        break 2;
                    }
                }
            }
        }
    }
    $_SESSION['visitor_ip6'] = $visitor_ip6 ?? '::1';
    file_put_contents(__DIR__ . '/loggedip.txt',
        "[Connected] IPv4: " . $visitor_ip . " | IPv6: " . $_SESSION['visitor_ip6'] . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );

    $pure = "Location: web/pure.html";
    $bait = "Location: web/bait.html";
    $error = "Location: web/errorllama.html";

    if ($ok == 1) {
        // keep original behavior (analysis + redirect)
        echo "<h2>Login successful — running analysis for IP: " . htmlspecialchars($visitor_ip) . "</h2>";
        echo "<pre style='background:#111;color:#dcdcdc;padding:1rem;border-radius:6px;'>";
        $ipArg = escapeshellarg($visitor_ip);

        $sp_inspect = shell_exec("bash bash_actions/spoof.sh $sys_ip");
        $sc_inspect = shell_exec("bash bash_actions/scan.sh $sys_ip $visitor_ip6");
        $v_inspect = shell_exec("bash bash_actions/paymal.sh $sys_ip");

        if ($sp_inspect == 0) {
            if ($sc_inspect == 0) {
                if ($v_inspect == 0) {
                    header($pure, true, 301);
                    exit;
                } else {
                    if ($v_inspect == 711) { header($error, true, 301); exit; }
                    else { header($bait, true, 301); exit; }
                }
            } else {
                if ($sc_inspect == 711) { header($error, true, 301); exit; }
                else { header($bait, true, 301); exit; }
            }
        } else {
            if ($sp_inspect == 711) { header($error, true, 301); exit; }
            else { header($bait, true, 301); exit; }
        }

        echo "</pre>";
    } else {

      header($bait, true, 301);
//    header("Location: " . $_SERVER['PHP_SELF']);
      exit;

    }
}
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Honeypot · Tech Login</title>
<link rel="icon" href="data:;base64,iVBORw0KGgo=">
<style>
:root{
  --bg:#06060a;--card:#071020;--muted:#9aa3b2;--accent:#40c4ff;--neon:#7cf2ff;
  --glass: rgba(255,255,255,0.02);
}
*{box-sizing:border-box}
html,body{height:100%;margin:0;font-family:Inter, ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial;background:
  radial-gradient(1200px 600px at 10% 10%, rgba(64,196,255,0.03), transparent), var(--bg);color:#e6eef6;display:flex;align-items:center;justify-content:center}
.stage{width:880px;max-width:96vw;padding:1.25rem;border-radius:14px;background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(0,0,0,0.12));border:1px solid rgba(124,242,255,0.04);backdrop-filter: blur(8px);box-shadow:0 20px 80px rgba(2,6,23,0.7);position:relative;overflow:hidden}
.topbar{display:flex;align-items:center;gap:1rem;margin-bottom:1rem;padding-bottom:.5rem;border-bottom:1px solid rgba(255,255,255,0.02)}
.brand-dot{width:14px;height:14px;border-radius:50%;background:linear-gradient(90deg,var(--accent),var(--neon));box-shadow:0 6px 28px rgba(124,242,255,0.06)}
.title-wrap{display:flex;flex-direction:column;gap:2px}
h1{margin:0;font-size:1.05rem;letter-spacing:.3px}
p.sub{margin:0;color:var(--muted);font-size:.85rem}
.top-right{margin-left:auto;text-align:right;font-size:.82rem;color:var(--muted)}
.stat-row{display:flex;gap:.5rem;align-items:center;justify-content:flex-end}
.net-bars{width:46px;height:22px;display:inline-grid;grid-template-columns:repeat(4,6px);gap:3px;align-items:end}
.net-bars span{display:block;height:6px;background:rgba(255,255,255,0.06);border-radius:2px;box-shadow:0 2px 6px rgba(64,196,255,0.03) inset}
.net-bars span.on{background:linear-gradient(180deg,var(--accent),var(--neon));}

/* layout */
.vis{display:flex;gap:1.6rem;align-items:flex-start;margin-top:1rem}
.eyes-wrap{width:300px;height:170px;position:relative;flex:0 0 300px}
.eye{position:absolute;top:28px;width:132px;height:102px;border-radius:999px;background:linear-gradient(180deg,#14282f,#061018);box-shadow:inset 0 10px 28px rgba(0,0,0,0.6), 0 10px 40px rgba(0,0,0,0.6);display:flex;align-items:center;justify-content:center;overflow:hidden;border:1px solid rgba(255,255,255,0.02)}
.eye.left{left:0}
.eye.right{right:0}
.iris{width:44px;height:44px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;transform:translate(0,0);transition:transform .12s cubic-bezier(.2,.9,.2,1)}
.pupil{width:18px;height:18px;border-radius:50%;background:#001;box-shadow:0 6px 22px rgba(0,0,0,0.6)}

/* lids */
.lid{position:absolute;left:0;right:0;height:50%;background:var(--bg);transform-origin:top center;transition:transform .12s ease}
.lid.top{top:0}
.lid.bottom{bottom:0;transform-origin:bottom center}

/* terminal */
.terminal-wrap{flex:1;min-height:180px;background:#010114;border-radius:10px;padding:1rem;position:relative;border:1px solid rgba(124,242,255,0.04);overflow:hidden}
.term-screen{height:240px;background:linear-gradient(180deg,#020312,#00070b);border-radius:6px;padding:1rem;font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, monospace;font-size:.92rem;color:#9df0ff;overflow:auto;position:relative;z-index:2}
.term-overlay{position:absolute;inset:0;pointer-events:none;opacity:.06;background:
  radial-gradient(800px 200px at 10% 10%, rgba(124,242,255,0.025), transparent),
  repeating-linear-gradient(0deg, rgba(64,196,255,0.02), rgba(64,196,255,0.02) 1px, transparent 1px, transparent 10px),
  repeating-linear-gradient(90deg, rgba(64,196,255,0.02), rgba(64,196,255,0.02) 1px, transparent 1px, transparent 10px);
  z-index:1;border-radius:6px}

/* terminal flicker */
@keyframes terminalFlicker { 0%{filter:brightness(.96)} 50%{filter:brightness(1.03)} 100%{filter:brightness(.98)} }
.terminal-wrap.hi-tech { animation: terminalFlicker 2.2s infinite alternate; }

/* prompt & cursor */
.prompt-line{white-space:pre-wrap}
.cursor{display:inline-block;width:8px;height:18px;background:#9df0ff;margin-left:2px;vertical-align:bottom;opacity:1;animation:blink 1s steps(2) infinite}
@keyframes blink{50%{opacity:0}}

/* checking overlay */
.checking{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;flex-direction:column;background:linear-gradient(180deg, rgba(0,0,0,0.4), rgba(0,0,0,0.6));backdrop-filter: blur(4px);opacity:0;pointer-events:none;transform:scale(.98);transition:all .28s ease;overflow:hidden;border-radius:8px}
.checking.show{opacity:1;pointer-events:auto;transform:scale(1)}
/* moving background while checking */
.checking::before{content:"";position:absolute;inset:0;background:repeating-linear-gradient(45deg, rgba(64,196,255,0.03), rgba(64,196,255,0.03) 10px, rgba(64,196,255,0.075) 20px);animation:move-bg 1.2s linear infinite;border-radius:8px;z-index:0}
@keyframes move-bg{0%{background-position:0 0}100%{background-position:40px 40px}}
.checking>div{position:relative;z-index:1}
.dots{display:flex;gap:.5rem;margin-top:.6rem}
.dot{width:10px;height:10px;border-radius:50%;background:#9df0ff;opacity:.14;animation:dot 1s infinite;box-shadow:0 0 8px rgba(64,196,255,0.35)}
.dot:nth-child(2){animation-delay:.15s}
.dot:nth-child(3){animation-delay:.3s}
@keyframes dot{0%{opacity:.14;transform:translateY(0)}50%{opacity:1;transform:translateY(-6px)}100%{opacity:.14;transform:translateY(0)}}
.status{margin-top:.6rem;color:var(--muted);font-size:.85rem;text-align:center}

/* eyelid states */
.eyes-wrap.suspicious .lid.top{transform:translateY(12px)}
.eyes-wrap.suspicious .lid.bottom{transform:translateY(-12px)}
.eyes-wrap.confused .lid.top{transform:translateY(8px) scaleY(.9)}
.eyes-wrap.confused .lid.bottom{transform:translateY(-6px) scaleY(.95)}
.eyes-wrap.normal .lid.top{transform:translateY(0)}
.eyes-wrap.normal .lid.bottom{transform:translateY(0)}

/* sad vibration (only for wrong password) */
@keyframes eye-vibrate {
  0% { transform: translate(0,0); }
  20% { transform: translate(-2px,1px); }
  40% { transform: translate(2px,-1px); }
  60% { transform: translate(-1px,2px); }
  80% { transform: translate(1px,-2px); }
  100% { transform: translate(0,0); }
}
.eyes-wrap.sad { animation: eye-vibrate 0.3s linear 0s 5; }

/* particles canvas sits behind stage */
.stage-canvas { position:absolute; inset:0; pointer-events:none; z-index:0; opacity:0.08 }

/* footer */
.footer{margin-top:1rem;color:var(--muted);font-size:.82rem;text-align:center}

/* responsive */
@media (max-width:900px){ .stage{width:94vw} }
@media (max-width:700px){ .vis{flex-direction:column-reverse}.eyes-wrap{display:none} }
</style>
</head>
<body>
<!-- particle background -->
<canvas class="stage-canvas" id="bgCanvas"></canvas>

<div class="stage hi-tech" role="main">
  <div class="topbar">
    <div class="brand-dot" aria-hidden></div>
    <div class="title-wrap">
      <h1>Honeypot · Secure Analysis</h1>
      <p class="sub">Visual verification UI — public IP collected for research</p>
    </div>

    <div class="top-right">
      <div class="stat-row">
        <div style="margin-right:12px">IP: <strong style="color:#dff6ff"><?php echo htmlspecialchars($sys_ip ?? '—'); ?></strong></div>
        <div id="dateTime">--</div>
        <div style="width:12px"></div>
        <div class="net-bars" aria-hidden title="Signal">
          <span class="on"></span><span class="on"></span><span></span><span></span>
        </div>
      </div>
    </div>
  </div>

  <div class="vis">
    <div class="eyes-wrap normal" id="eyes">
      <div class="eye left">
        <div class="lid top"></div>
        <div class="lid bottom"></div>
        <div class="iris" id="irisL"><div class="pupil"></div></div>
      </div>
      <div class="eye right">
        <div class="lid top"></div>
        <div class="lid bottom"></div>
        <div class="iris" id="irisR"><div class="pupil"></div></div>
      </div>
    </div>

    <div class="terminal-wrap hi-tech" id="terminalWrap">
      <div class="term-overlay" aria-hidden></div>
      <div class="term-screen" id="term">
        <div id="lines">
          <div class="prompt-line">Enter username:</div>
          <div class="prompt-line" id="inputLine">root@honeypot:~$ <span id="typed"></span><span class="cursor" id="cursor"></span></div>
        </div>
      </div>

      <div class="checking" id="checking" aria-hidden="true">
        <div style="text-align:center">
          <div style="font-family:ui-monospace, Menlo, monospace; font-size:1.02rem;">Verifying credentials…</div>
          <div class="dots" aria-hidden>
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
          </div>
          <div class="status" id="checkStatus">Checking with secure database</div>
        </div>
      </div>

      <form id="loginForm" method="post" autocomplete="off" style="display:none">
        <input name="username" id="realUser" />
        <input name="password" id="realPass" type="password" />
      </form>
    </div>
  </div>

  <div class="footer">
    &copy; <?php echo date("Y"); ?> Tech Honeypot · Analysis in progress...
  </div>
</div>

<script>
/* ====== UI behavior & animations ====== */
(function(){
  // DOM
  const typedEl = document.getElementById('typed');
  const cursor = document.getElementById('cursor');
  const eyesWrap = document.getElementById('eyes');
  const irisL = document.getElementById('irisL'), irisR = document.getElementById('irisR');
  const checking = document.getElementById('checking'), checkStatus = document.getElementById('checkStatus');
  const lines = document.getElementById('lines');
  const realUser = document.getElementById('realUser'), realPass = document.getElementById('realPass');

  // state
  let buffer = '', isPasswordMode = false, username = '', password = '';
  let typing = false, typingTimer = null, TYPING_IDLE_MS = 700;
  let targetIris = {x:0,y:0}, currentIris = {x:0,y:0}, MAX_MOVE = 12;
  let scanInterval = null, scanPaused = false;

  // Post JS-detected IP (optional)
  fetch('https://api.ipify.org?format=json').then(r=>r.json()).then(data=>{
    const fd = new URLSearchParams(); fd.append('js_ip', data.ip);
    fetch(window.location.href, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:fd.toString(), credentials:'same-origin' }).catch(()=>{});
  }).catch(()=>{});

  // clamp
  function clamp(v,a,b){ return Math.max(a,Math.min(b,v)); }

  // iris animation
  function animateIris(){
    if(!scanPaused){
      currentIris.x += (targetIris.x - currentIris.x) * 0.18;
      currentIris.y += (targetIris.y - currentIris.y) * 0.18;
    }
    irisL.style.transform = `translate(${currentIris.x}px, ${currentIris.y}px)`;
    irisR.style.transform = `translate(${currentIris.x}px, ${currentIris.y}px)`;
    requestAnimationFrame(animateIris);
  }
  animateIris();

  // eye state toggles
  function setTypingFlag(v){
    typing = v;
    eyesWrap.classList.remove('normal','confused','suspicious','sad');
    if(v) eyesWrap.classList.add('suspicious');
    else eyesWrap.classList.add('normal');
  }

  function focusEyesOnTerminal(){
    const t = document.getElementById('term').getBoundingClientRect();
    const e = eyesWrap.getBoundingClientRect();
    const dx = (t.left + t.width/2) - (e.left + e.width/2);
    const dy = (t.top + t.height/2) - (e.top + e.height/2);
    targetIris.x = clamp(dx/20, -MAX_MOVE, MAX_MOVE);
    targetIris.y = clamp(dy/30, -MAX_MOVE, MAX_MOVE);
    setTypingFlag(true);
  }

  function resumeEyeFollow(){
    targetIris.x = 0; targetIris.y = 0; setTypingFlag(false);
  }

  function renderTyped(){ typedEl.textContent = isPasswordMode ? '•'.repeat(buffer.length) : buffer; }

  function resetTypingTimer(){ clearTimeout(typingTimer); typingTimer = setTimeout(()=>{ typing=false; resumeEyeFollow(); }, TYPING_IDLE_MS); typing = true; }

  // scanning eye movement (when verifying)
  function startEyeScan(){ if(scanInterval) return; let t=0; scanInterval = setInterval(()=>{ t += 0.12; targetIris.x = Math.sin(t)*8; targetIris.y = Math.cos(t*0.7)*6 + 4; }, 80); }
  function stopEyeScan(){ if(scanInterval){ clearInterval(scanInterval); scanInterval = null; targetIris.x = 0; targetIris.y = 0; } }

  // UI reset after wrong password (sad) -> keep username, ask password again

function flashSadEyes(){
    // show sad vibration
    eyesWrap.classList.remove('normal','suspicious','confused');
    eyesWrap.classList.add('sad');
    stopEyeScan();

    // hide checking overlay and restore terminal opacity
    checking.classList.remove('show');
    document.getElementById('term').style.opacity = 1;

    // show the "Password incorrect" message briefly
    const msg = document.createElement('div');
    msg.className = 'prompt-line';
    msg.style.color = '#ff9aa2';
    msg.textContent = 'Credentials incorrect — who are you?';
    lines.insertBefore(msg, document.getElementById('inputLine'));

    // after vibration, remove sad and reset login process
    setTimeout(()=>{
        // remove message
        if (msg && msg.parentNode) msg.parentNode.removeChild(msg);

        eyesWrap.classList.remove('sad');

        // reset both username and password
        username = '';
        password = '';
        buffer = '';
        isPasswordMode = false;

        renderTyped();

        // add "Enter username:" prompt (avoid duplicates)
        const exists = Array.from(lines.children).some(c => c.textContent && c.textContent.includes('Enter username:'));
        if(!exists){
            const p = document.createElement('div');
            p.className = 'prompt-line';
            p.textContent = 'Enter username:';
            lines.insertBefore(p, document.getElementById('inputLine'));
        }

        focusEyesOnTerminal(); // eyelids show suspicious for typing again
    }, 1100);
}



  // typing input
  document.addEventListener('keydown', ev=>{
    const key = ev.key;
    if(key === 's'){ scanPaused = !scanPaused; return; } // pause scan
    // allow basic browser shortcuts (Ctrl+R etc)
    if(ev.ctrlKey || ev.metaKey) return;

    ev.preventDefault();

    if(key === 'Enter'){
      if(!isPasswordMode){
        username = buffer.trim();
        buffer = '';
        renderTyped();
        isPasswordMode = true;
        // render "Enter password:" line
        const p = document.createElement('div'); p.className = 'prompt-line'; p.textContent = 'Enter password:';
        // insert before the input line if not already present
        const found = Array.from(lines.children).some(c => c.textContent && c.textContent.includes('Enter password:'));
        if(!found) lines.insertBefore(p, document.getElementById('inputLine'));
        focusEyesOnTerminal();
        resetTypingTimer();
      } else {
        password = buffer;
        buffer = '';
        renderTyped();
        // submit
        submitCredentials(username, password);
      }
    } else if(key === 'Backspace'){
      buffer = buffer.slice(0,-1);
      renderTyped();
      resetTypingTimer();
      focusEyesOnTerminal();
    } else if(key === 'Escape'){
      buffer = '';
      renderTyped();
      resumeEyeFollow();
    } else if(key.length === 1 && !ev.ctrlKey && !ev.metaKey && !ev.altKey){
      buffer += key;
      renderTyped();
      focusEyesOnTerminal();
      resetTypingTimer();
    }
  });

  // cursor follow
  document.addEventListener('mousemove', ev=>{
    if(typing) return; // don't jump while typing
    const mx = ev.clientX, my = ev.clientY;
    const eRect = eyesWrap.getBoundingClientRect();
    const centerX = eRect.left + eRect.width / 2, centerY = eRect.top + eRect.height / 2;
    const dx = (mx - centerX) / 18, dy = (my - centerY) / 24;
    targetIris.x = clamp(dx, -MAX_MOVE, MAX_MOVE);
    targetIris.y = clamp(dy, -MAX_MOVE, MAX_MOVE);
  });

  window.addEventListener('mouseout', (e)=>{ if(!e.relatedTarget){ targetIris.x = 0; targetIris.y = 0; } });

  // submit (AJAX-check + fallback to real form submit for redirect/analysis)


function submitCredentials(user, pass) {
  // Create hidden form (same as before)
  const f = document.createElement('form');
  f.method = 'POST';
  f.action = window.location.href;
  f.style.display = 'none';

  const a = document.createElement('input');
  a.name = 'username';
  a.value = user;
  f.appendChild(a);

  const b = document.createElement('input');
  b.name = 'password';
  b.value = pass;
  f.appendChild(b);

  document.body.appendChild(f);

  // --- 1️⃣ Show verifying overlay immediately ---
  document.getElementById('term').style.opacity = 0.25;
  checking.classList.add('show');
  checkStatus.textContent = 'Verifying credentials...';
  startEyeScan();

  // --- 2️⃣ Allow browser to render the overlay *before* PHP starts ---
  // This ensures the animation runs smoothly before the blocking network call begins.
  requestAnimationFrame(() => {
    // A tiny delay (30–50ms) gives the browser time to paint the animation
    setTimeout(() => {
      // --- 3️⃣ Submit form normally (sends data to PHP immediately) ---
      f.submit();
    }, 50);
  });
}





  // Expose submitCredentials in scope (if needed)
  window.submitCredentials = submitCredentials;

  // small helpers for UI initial state
  function initUI(){
    // ensure eyelids in normal position
    eyesWrap.classList.add('normal');
  }
  initUI();

  // live date/time
  const dtEl = document.getElementById('dateTime');
  function updateDateTime(){
    const now = new Date();
    // show date and 24h time
    dtEl.textContent = now.toLocaleString(undefined, {hour12:false});
  }
  setInterval(updateDateTime, 1000);
  updateDateTime();

})(); /* end UI IIFE */

/* ====== particles background (subtle) ====== */
(function(){
  const canvas = document.getElementById('bgCanvas');
  const ctx = canvas.getContext('2d');
  let w = canvas.width = innerWidth;
  let h = canvas.height = innerHeight;
  const NUM = Math.floor((w*h)/90000) + 40;
  const particles = [];
  function rand(a,b){ return a + Math.random()*(b-a); }
  for(let i=0;i<NUM;i++){
    particles.push({
      x: Math.random()*w, y: Math.random()*h,
      r: rand(0.3,1.4), vx: rand(-0.15,0.15), vy: rand(-0.05,0.05),
      alpha: rand(0.02,0.12)
    });
  }
  function onResize(){
    w = canvas.width = innerWidth; h = canvas.height = innerHeight;
  }
  addEventListener('resize', onResize);
  function frame(){
    ctx.clearRect(0,0,w,h);
    for(const p of particles){
      p.x += p.vx; p.y += p.vy;
      if(p.x < 0) p.x = w; if(p.x > w) p.x = 0;
      if(p.y < 0) p.y = h; if(p.y > h) p.y = 0;
      ctx.beginPath();
      ctx.fillStyle = `rgba(64,196,255,${p.alpha})`;
      ctx.arc(p.x, p.y, p.r, 0, Math.PI*2);
      ctx.fill();
    }
    requestAnimationFrame(frame);
  }
  frame();
})();

</script>
</body>
</html>
